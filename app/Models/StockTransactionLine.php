<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransactionLine extends Model
{
    protected $fillable = ['stock_transaction_id', 'product_id', 'location_id', 'quantity_delta', 'unit_cost'];

    protected function casts(): array
    {
        return ['quantity_delta' => 'decimal:3', 'unit_cost' => 'decimal:2'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
