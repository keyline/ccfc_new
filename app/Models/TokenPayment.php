<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenPayment extends Model
{
    use HasFactory;

    protected $table = 'token_payments';

    protected $fillable = [
        'payment_id',
        'token_id',
        'member_code',
        'member_due_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_status',
        'gateway_response',
        'payment_date',
    ];

    public function paymentToken()
    {
        return $this->belongsTo(PaymentToken::class, 'token_id');
    }

    public function memberDue()
    {
        return $this->belongsTo(MemberDue::class);
    }
}
