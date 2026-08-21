<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerCategory extends Model
{
    protected $fillable = ['parent_id', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CustomerCategory::class, 'parent_id');
    }

    public function isSubcategory(): bool
    {
        return $this->parent_id !== null;
    }
}
