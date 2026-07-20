<?php

namespace App\Jobs;

use App\Services\ClubmanMemberProfileSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class MemberProfileUpdate implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $user_code;
    public $tries = 3;
    public $timeout = 90;
    public $backoff = 15;
    public $uniqueFor = 120;

    public function __construct($userCode)
    {
        $this->user_code = $userCode;
    }

    public function handle(ClubmanMemberProfileSync $profileSync): void
    {
        $profileSync->syncByMemberCode((string) $this->user_code);

        Log::info('Clubman member profile synchronized.', [
            'member_code' => $this->user_code,
        ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->user_code;
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Clubman member profile synchronization failed.', [
            'member_code' => $this->user_code,
            'error' => $exception->getMessage(),
        ]);
    }
}
