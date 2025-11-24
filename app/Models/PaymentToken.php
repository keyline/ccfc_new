<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentToken extends Model
{
    use HasFactory;

    protected $table = 'payment_tokens';

    protected $fillable = [
        'token',
        'member_code',
        'member_due_id',
        'generated_at',
        'expires_at',
        'used_at',
        'status',
        'sms_sent',
        'sms_sent_at',
        'email_sent',
        'email_sent_at',
        'access_count',
        'last_accessed_at',
        'ip_address',
        'user_agent',
    ];

    public function memberDue()
    {
        return $this->belongsTo(MemberDue::class);
    }

    public function tokenPayments()
    {
        return $this->hasMany(TokenPayment::class, 'token_id');
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class, 'token_id');
    }
}
