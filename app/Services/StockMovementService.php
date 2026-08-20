<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * @param  array<int, array{product_id:int, location_id:int, quantity_delta:float|int|string}>  $lines
     */
    public function record(
        Company $company,
        string $type,
        array $lines,
        ?User $user = null,
        ?string $reference = null,
        ?string $notes = null,
    ): StockTransaction {
        return DB::transaction(function () use ($company, $type, $lines, $user, $reference, $notes) {
            $transaction = $company->stockTransactions()->create([
                'user_id' => $user?->id,
                'type' => $type,
                'reference' => $reference,
                'notes' => $notes,
                'occurred_at' => now(),
            ]);

            foreach ($lines as $line) {
                $product = Product::whereBelongsTo($company)->findOrFail($line['product_id']);
                $location = Location::whereBelongsTo($company)->findOrFail($line['location_id']);
                $delta = round((float) $line['quantity_delta'], 3);

                if ($delta === 0.0) {
                    continue;
                }

                Stock::firstOrCreate([
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                ], ['quantity' => 0]);

                $stock = Stock::where('company_id', $company->id)
                    ->where('product_id', $product->id)
                    ->where('location_id', $location->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $newQuantity = round((float) $stock->quantity + $delta, 3);

                if ($newQuantity < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for {$product->name} at {$location->name}.",
                    ]);
                }

                $stock->update(['quantity' => $newQuantity]);
                $transaction->lines()->create([
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'quantity_delta' => $delta,
                    'unit_cost' => $product->cost,
                ]);
            }

            if ($transaction->lines()->doesntExist()) {
                $transaction->delete();
                throw ValidationException::withMessages(['quantity' => 'The stock change cannot be zero.']);
            }

            return $transaction->load('lines.product', 'lines.location', 'user');
        });
    }
}
