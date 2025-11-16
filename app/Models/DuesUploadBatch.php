<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuesUploadBatch extends Model
{
    use HasFactory;

    protected $table = 'dues_upload_batches';

    protected $fillable = [
        'batch_id',
        'month_no',
        'month_name',
        'year',
        'upload_date',
        'uploaded_by',
        'total_records',
        'file_name',
        'status',
    ];

    public function memberDues()
    {
        return $this->hasMany(MemberDue::class, 'upload_batch_id', 'batch_id');
    }
}
