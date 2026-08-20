<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'email', 'locale', 'currency', 'timezone', 'plan', 'product_limit', 'user_limit', 'warehouse_limit',
        'subscription_status', 'trial_ends_at', 'onboarding_completed_at', 'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'is_active' => 'boolean',
            'product_limit' => 'integer',
            'user_limit' => 'integer',
            'warehouse_limit' => 'integer',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status', 'permissions'])
            ->withTimestamps();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function stockCounts(): HasMany
    {
        return $this->hasMany(StockCount::class);
    }

    public function labelTemplates(): HasMany
    {
        return $this->hasMany(LabelTemplate::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function hasReachedProductLimit(?int $usage = null): bool
    {
        return $this->product_limit !== null
            && ($usage ?? $this->products()->count()) >= $this->product_limit;
    }

    public function hasReachedUserLimit(?int $usage = null): bool
    {
        return $this->user_limit !== null
            && ($usage ?? $this->users()->count()) >= $this->user_limit;
    }

    public function hasReachedWarehouseLimit(?int $usage = null): bool
    {
        return $this->warehouse_limit !== null
            && ($usage ?? $this->locations()->count()) >= $this->warehouse_limit;
    }
}
