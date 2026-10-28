<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_number',
        'opened_at',
        'closed_at',
        'starting_cash',
        'cash_sales',
        'non_cash_sales',
        'cash_in',
        'cash_out',
        'cash_refunds',
        'expected_cash',
        'actual_cash',
        'difference',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'non_cash_sales' => 'decimal:2',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Hitung ekspektasi uang kas fisik yang ada di laci kasir:
     * Saldo Awal + Penjualan Tunai + Kas Masuk - Kas Keluar - Refund Tunai
     */
    public function recalculateExpectedCash(): float
    {
        $expected = (float) $this->starting_cash
            + (float) $this->cash_sales
            + (float) $this->cash_in
            - (float) $this->cash_out
            - (float) $this->cash_refunds;

        $this->expected_cash = $expected;
        return $expected;
    }
}
