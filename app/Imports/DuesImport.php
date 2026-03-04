<?php

namespace App\Imports;

use App\Models\MemberDue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use App\Models\DuesUploadBatch;

class DuesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithEvents
{
    private $batch;
    private $rowCount = 0;

    public function __construct(DuesUploadBatch $batch)
    {
        $this->batch = $batch;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        //  dd($row);
        // ++$this->rowCount;
        $this->rowCount++;
        
        return new MemberDue([
            'member_code'         => $row['mcode'] ?? null,
            'upload_batch_id'     => $this->batch->batch_id,
            'outstanding_balance' => $row['total'] ?? 0,
            'dues_for_this_month' => $row['total'] ?? 0,
            'paid_amount'         => $row['paid_amount'] ?? 0,
            'status'              => 'pending',
            'month_no'            => $this->batch->month_no,
            'month_name'          => $this->batch->month_name,
            'year'                => $this->batch->year,
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $this->batch->update(['total_records' => $this->rowCount]);
            },
        ];
    }
}
