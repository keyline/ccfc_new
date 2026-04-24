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
        'member_id',
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

    protected $casts = [
    'expires_at' => 'datetime',
    'used_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_code', 'user_code');
    }

    public function memberDue()
    {
        return $this->belongsTo(MemberDue::class, 'member_due_id', 'id');
    }

    public function tokenPayments()
    {
        return $this->hasMany(TokenPayment::class, 'token_id');
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class, 'token_id');
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isUsed()
    {
        return $this->used_at !== null;
    }

    public function markAsUsed($ipAddress, $userAgent)
    {
        $this->update([
            'used_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'last_accessed_at' => now(),
            'access_count' => $this->access_count + 1,
        ]);
    }
}
