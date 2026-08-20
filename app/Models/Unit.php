<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $fillable = ['company_id', 'name', 'name_ar', 'symbol'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
