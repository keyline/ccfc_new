<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberDue extends Model
{
    use HasFactory;

    protected $table = 'member_dues';

    protected $fillable = [
        'member_code',
        'upload_batch_id',
        'outstanding_balance',
        'paid_amount',
        'status',
        'month_no',
        'month_name',
        'year',
    ];

    public function duesUploadBatch()
    {
        return $this->belongsTo(DuesUploadBatch::class, 'upload_batch_id', 'batch_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_code', 'member_code');
    }

    public function paymentTokens()
    {
        return $this->hasMany(PaymentToken::class);
    }
}
