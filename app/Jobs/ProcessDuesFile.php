<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\DuesUploadBatch;
use App\Models\MemberDue;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DuesImport;

class ProcessDuesFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;
    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(DuesUploadBatch $batch, $filePath)
    {
        $this->batch = $batch;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Excel::import(new DuesImport($this->batch), storage_path('app/' . $this->filePath));

            $this->batch->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->batch->update(['status' => 'failed']);
            // Log the error
        }
    }
}
