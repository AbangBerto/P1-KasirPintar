<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'cash_shift_id',
        'customer_id',
        'subtotal',
        'discount_amount',
        'discount_percent',
        'tax_amount',
        'total_amount',
        'total_cost',
        'gross_profit',
        'payment_method',
        'payment_status',
        'cash_received',
        'cash_change',
        'transaction_date',
        'notes',
        'void_reason',
        'voided_by',
        'voided_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'cash_change' => 'decimal:2',
        'transaction_date' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isVoid(): bool
    {
        return $this->payment_status === 'void';
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === 'refunded';
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'TRX-' . date('Ymd') . '-';
        $latest = static::where('invoice_number', 'LIKE', $prefix . '%')->latest('id')->first();

        if ($latest) {
            $lastNumber = (int) substr($latest->invoice_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }
}
