<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notification_log';

    protected $fillable = [
        'token_id',
        'member_code',
        'notification_type',
        'recipient',
        'subject',
        'message_body',
        'status',
        'provider_id',
        'provider_response',
        'error_message',
        'scheduled_at',
        'sent_at',
        'delivered_at',
    ];

    public function paymentToken()
    {
        return $this->belongsTo(PaymentToken::class, 'token_id');
    }
}
