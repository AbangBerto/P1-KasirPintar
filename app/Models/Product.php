<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'stock',
        'min_stock',
        'unit',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock && $this->stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function getMarginAmountAttribute(): float
    {
        return (float) ($this->selling_price - $this->cost_price);
    }

    public function getMarginPercentageAttribute(): float
    {
        if ($this->selling_price > 0) {
            return round((($this->selling_price - $this->cost_price) / $this->selling_price) * 100, 1);
        }
        return 0.0;
    }
}
