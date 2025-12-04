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
        return $this->belongsTo(MemberDue::class, 'member_code', 'member_code');
    }

    public function paymentTokens()
    {
        return $this->hasMany(PaymentToken::class);
    }

    public static function processPayment($dueId, $amount)
    {
        $due = self::find($dueId);

        if (!$due) {
            return false;
        }

        // Add amount
        $due->paid_amount = ($due->paid_amount ?? 0) + $amount;

        // Update due status
        if ($due->outstanding_balance <= $due->paid_amount) {
            $due->status = 'paid';
        } else {
            $due->status = 'partial';
        }

        $due->save();

        return $due;
    }
}
