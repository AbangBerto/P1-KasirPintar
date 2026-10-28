<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_phone',
        'message_type', // 'daily_report', 'stock_alert', 'receipt', 'test'
        'content',
        'status', // 'sent', 'delivered', 'failed', 'demo_dispatched'
        'response_payload',
    ];
}
