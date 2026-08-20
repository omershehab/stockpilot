<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class ProductImportService
{
    private const REQUIRED_HEADERS = ['sku', 'name', 'barcode', 'unit', 'cost', 'price'];

    private const HEADER_ALIASES = [
        'product_name' => 'name',
        'arabic_name' => 'name_ar',
        'product_name_ar' => 'name_ar',
        'min_stock' => 'minimum_stock',
        'quantity' => 'initial_quantity',
        'warehouse' => 'warehouse_code',
    ];

    public function __construct(private readonly StockMovementService $stockMovements) {}

    public function import(Company $company, UploadedFile $file, ?User $user = null): int
    {
        $rows = $this->readRows($file);

        if ($rows === []) {
            throw ValidationException::withMessages(['import' => __('The import file has no product rows.')]);
        }

        if (count($rows) > 1000) {
            throw ValidationException::withMessages(['import' => __('You can import up to 1,000 products at a time.')]);
        }

        $rows = $this->validateRows($company, $rows);

        DB::transaction(function () use ($company, $rows, $user) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);
            $currentCount = $lockedCompany->products()->count();

            if ($lockedCompany->product_limit !== null && $currentCount + count($rows) > $lockedCompany->product_limit) {
                $remaining = max(0, $lockedCompany->product_limit - $currentCount);
                throw ValidationException::withMessages([
                    'import' => __('This file has :count products, but only :remaining product slots remain.', [
                        'count' => count($rows),
                        'remaining' => $remaining,
                    ]),
                ]);
            }

            $categories = $lockedCompany->categories()->get()->keyBy(fn ($category) => mb_strtolower($category->name));
            $stockLines = [];

            foreach ($rows as $row) {
                $categoryId = null;
                if ($row['category'] !== '') {
                    $categoryKey = mb_strtolower($row['category']);
                    $category = $categories->get($categoryKey);
                    if (! $category) {
                        $category = $lockedCompany->categories()->create(['name' => $row['category']]);
                        $categories->put($categoryKey, $category);
                    }
                    $categoryId = $category->id;
                }

                $product = $lockedCompany->products()->create([
                    'category_id' => $categoryId,
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'name_ar' => $row['name_ar'] ?: null,
                    'unit' => $row['unit'],
                    'cost' => $row['cost'],
                    'price' => $row['price'],
                    'minimum_stock' => $row['minimum_stock'],
                    'is_active' => $row['is_active'],
                ]);

                $product->barcodes()->create([
                    'company_id' => $lockedCompany->id,
                    'code' => $row['barcode'],
                    'format' => 'code128',
                    'is_primary' => true,
                ]);

                if ($row['batch_number'] !== '') {
                    $product->batches()->create([
                        'company_id' => $lockedCompany->id,
                        'batch_number' => $row['batch_number'],
                        'expiry_date' => $row['expiry_date'] ?: null,
                    ]);
                }

                if ((float) $row['initial_quantity'] > 0) {
                    $stockLines[] = [
                        'product_id' => $product->id,
                        'location_id' => $row['_location_id'],
                        'quantity_delta' => $row['initial_quantity'],
                    ];
                }
            }

            if ($stockLines !== []) {
                $this->stockMovements->record(
                    $lockedCompany,
                    'receive',
                    $stockLines,
                    $user,
                    'PRODUCT-IMPORT-'.now()->format('YmdHis'),
                    __('Opening stock from product import')
                );
            }
        });

        return count($rows);
    }

    /** @return array<int, array<string, string>> */
    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $matrix = $extension === 'xlsx'
            ? $this->readXlsx($file->getRealPath())
            : $this->readCsv($file->getRealPath());

        if ($matrix === []) {
            return [];
        }

        $headers = array_map(fn ($value) => $this->normaliseHeader((string) $value), array_shift($matrix));
        $missing = array_diff(self::REQUIRED_HEADERS, $headers);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'import' => __('Missing required columns: :columns.', ['columns' => implode(', ', $missing)]),
            ]);
        }

        $rows = [];
        foreach ($matrix as $index => $values) {
            $values = array_pad($values, count($headers), '');
            $row = [];
            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $row[$header] = trim((string) ($values[$column] ?? ''));
                }
            }
            if (collect($row)->contains(fn ($value) => $value !== '')) {
                $row['_row'] = (string) ($index + 2);
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw ValidationException::withMessages(['import' => __('The import file could not be read.')]);
        }

        $firstLine = (string) fgets($handle);
        rewind($handle);
        $delimiters = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        $delimiter = array_search(max($delimiters), $delimiters, true) ?: ',';
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($rows === [] && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }
            $rows[] = array_map(fn ($value) => (string) ($value ?? ''), $row);
        }

        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['import' => __('The XLSX file could not be opened.')]);
        }

        try {
            $sharedStrings = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                $document = $this->xmlDocument($xml);
                $xpath = new DOMXPath($document);
                foreach ($xpath->query('//*[local-name()="si"]') as $item) {
                    $parts = [];
                    foreach ($xpath->query('.//*[local-name()="t"]', $item) as $text) {
                        $parts[] = $text->textContent;
                    }
                    $sharedStrings[] = implode('', $parts);
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new RuntimeException('The first worksheet is missing.');
            }

            $document = $this->xmlDocument($sheetXml);
            $xpath = new DOMXPath($document);
            $rows = [];

            foreach ($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $rowNode) {
                $row = [];
                foreach ($xpath->query('./*[local-name()="c"]', $rowNode) as $cell) {
                    if (! $cell instanceof DOMElement) {
                        continue;
                    }
                    $reference = $cell->getAttribute('r');
                    $column = $this->columnIndex($reference);
                    $type = $cell->getAttribute('t');
                    $valueNode = $xpath->query('./*[local-name()="v"]', $cell)->item(0);
                    $value = $valueNode?->textContent ?? '';

                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $parts = [];
                        foreach ($xpath->query('.//*[local-name()="t"]', $cell) as $text) {
                            $parts[] = $text->textContent;
                        }
                        $value = implode('', $parts);
                    }

                    $row[$column] = $value;
                }

                if ($row !== []) {
                    $width = max(array_keys($row)) + 1;
                    $rows[] = array_replace(array_fill(0, $width, ''), $row);
                }
            }

            return $rows;
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['import' => __('The XLSX file is invalid or unsupported.')]);
        } finally {
            $zip->close();
        }
    }

    private function xmlDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        if (! @$document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Invalid spreadsheet XML.');
        }

        return $document;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return max(0, $index - 1);
    }

    /** @param array<int, array<string, string>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function validateRows(Company $company, array $rows): array
    {
        $units = $company->units()->pluck('id', 'symbol')->mapWithKeys(fn ($id, $symbol) => [mb_strtolower($symbol) => $id]);
        $locations = $company->locations()->pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [mb_strtolower($code) => $id]);
        $existingSkus = $company->products()->pluck('sku')->map(fn ($sku) => mb_strtolower($sku))->flip();
        $existingBarcodes = $company->products()->with('barcodes:id,product_id,code')->get()
            ->flatMap->barcodes->pluck('code')->map(fn ($code) => mb_strtolower($code))->flip();
        $seenSkus = [];
        $seenBarcodes = [];
        $errors = [];
        $validatedRows = [];

        foreach ($rows as $row) {
            $row = array_merge([
                'sku' => '', 'name' => '', 'name_ar' => '', 'barcode' => '', 'category' => '',
                'unit' => '', 'cost' => '', 'price' => '', 'minimum_stock' => '0',
                'is_active' => 'yes', 'batch_number' => '', 'expiry_date' => '',
                'initial_quantity' => '0', 'warehouse_code' => '',
            ], $row);
            $rowNumber = (int) $row['_row'];
            $row['expiry_date'] = $this->normaliseDate($row['expiry_date']);
            $active = $this->normaliseBoolean($row['is_active']);

            $validator = Validator::make($row, [
                'sku' => ['required', 'string', 'max:80'],
                'name' => ['required', 'string', 'max:160'],
                'name_ar' => ['nullable', 'string', 'max:160'],
                'barcode' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
                'category' => ['nullable', 'string', 'max:160'],
                'unit' => ['required', 'string', 'max:30'],
                'cost' => ['required', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'minimum_stock' => ['nullable', 'numeric', 'min:0'],
                'batch_number' => ['nullable', 'required_with:expiry_date', 'string', 'max:100'],
                'expiry_date' => ['nullable', 'date_format:Y-m-d'],
                'initial_quantity' => ['nullable', 'numeric', 'min:0'],
                'warehouse_code' => ['nullable', 'string', 'max:40'],
            ]);

            foreach ($validator->errors()->all() as $message) {
                $errors[] = __('Row :row: :message', ['row' => $rowNumber, 'message' => $message]);
            }

            $skuKey = mb_strtolower($row['sku']);
            $barcodeKey = mb_strtolower($row['barcode']);
            $unitKey = mb_strtolower($row['unit']);
            $warehouseKey = mb_strtolower($row['warehouse_code']);

            if ($active === null) {
                $errors[] = __('Row :row: is_active must be yes/no, true/false, active/inactive, or 1/0.', ['row' => $rowNumber]);
            }
            if (! $units->has($unitKey)) {
                $errors[] = __('Row :row: unit ":unit" does not exist in this company.', ['row' => $rowNumber, 'unit' => $row['unit']]);
            }
            if ($existingSkus->has($skuKey)) {
                $errors[] = __('Row :row: SKU ":sku" already exists.', ['row' => $rowNumber, 'sku' => $row['sku']]);
            } elseif (isset($seenSkus[$skuKey])) {
                $errors[] = __('Row :row: SKU ":sku" is duplicated in the file.', ['row' => $rowNumber, 'sku' => $row['sku']]);
            }
            if ($existingBarcodes->has($barcodeKey)) {
                $errors[] = __('Row :row: barcode ":barcode" already exists.', ['row' => $rowNumber, 'barcode' => $row['barcode']]);
            } elseif (isset($seenBarcodes[$barcodeKey])) {
                $errors[] = __('Row :row: barcode ":barcode" is duplicated in the file.', ['row' => $rowNumber, 'barcode' => $row['barcode']]);
            }

            $initialQuantity = is_numeric($row['initial_quantity']) ? (float) $row['initial_quantity'] : 0;
            if ($initialQuantity > 0 && $row['warehouse_code'] === '') {
                $errors[] = __('Row :row: warehouse_code is required when initial_quantity is greater than zero.', ['row' => $rowNumber]);
            } elseif ($row['warehouse_code'] !== '' && ! $locations->has($warehouseKey)) {
                $errors[] = __('Row :row: warehouse ":warehouse" does not exist in this company.', ['row' => $rowNumber, 'warehouse' => $row['warehouse_code']]);
            }

            $seenSkus[$skuKey] = true;
            $seenBarcodes[$barcodeKey] = true;
            $row['is_active'] = $active ?? true;
            $row['minimum_stock'] = $row['minimum_stock'] === '' ? 0 : $row['minimum_stock'];
            $row['initial_quantity'] = $row['initial_quantity'] === '' ? 0 : $row['initial_quantity'];
            $row['_location_id'] = $locations->get($warehouseKey);
            $validatedRows[] = $row;
        }

        if ($errors !== []) {
            if (count($errors) > 50) {
                $errors = [...array_slice($errors, 0, 50), __('More errors were found. Fix the first 50 and upload the file again.')];
            }
            throw ValidationException::withMessages(['import' => $errors]);
        }

        return $validatedRows;
    }

    private function normaliseHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header));
        $header = mb_strtolower((string) preg_replace('/[\s-]+/', '_', $header));

        return self::HEADER_ALIASES[$header] ?? $header;
    }

    private function normaliseBoolean(string $value): ?bool
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return true;
        }

        return match ($value) {
            '1', 'yes', 'true', 'active' => true,
            '0', 'no', 'false', 'inactive' => false,
            default => null,
        };
    }

    private function normaliseDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) floor((float) $value).' days')->format('Y-m-d');
        }

        return $value;
    }
}
