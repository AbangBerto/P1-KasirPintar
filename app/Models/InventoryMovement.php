<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'transaction_id',
        'type', // 'initial', 'restock', 'sale', 'return', 'damaged', 'adjustment'
        'quantity',
        'before_stock',
        'after_stock',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_stock' => 'integer',
        'after_stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            'initial' => 'Stok Awal',
            'restock' => 'Pemasukan / Restok',
            'sale' => 'Penjualan Kasir',
            'return' => 'Retur Barang',
            'damaged' => 'Barang Rusak / Kadaluarsa',
            'adjustment' => 'Penyesuaian Manual',
            default => ucfirst($this->type),
        };
    }
}
