<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'tax_id',
        'footer_note',
        'paper_size',
        'receipt_language',
    ];

    /**
     * The app has exactly one settings row; create it on first access
     * so every caller can rely on it existing.
     */
    public static function current(): self
    {
        return static::firstOrCreate([]);
    }
}
