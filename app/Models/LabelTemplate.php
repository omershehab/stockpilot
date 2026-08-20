<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelTemplate extends Model
{
    protected $fillable = [
        'company_id', 'name', 'width_mm', 'height_mm', 'show_name', 'show_sku',
        'show_price', 'is_default', 'layout',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'show_name' => 'boolean',
            'show_sku' => 'boolean',
            'show_price' => 'boolean',
            'is_default' => 'boolean',
            'layout' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
