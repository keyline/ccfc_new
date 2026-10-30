<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PrepareEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Do not automatically replay partially dispatched batches.
    public $tries = 1;
    public $timeout = 60;

    protected $campaignId;
    protected $afterId;

    public function __construct($campaignId, $afterId = 0)
    {
        $this->campaignId = $campaignId;
        $this->afterId = $afterId;
    }

    public function handle()
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        $memberTypes = json_decode($campaign->ec_member_type, true) ?: [];

        $users = DB::table('users')
            ->where('users.id', '>', $this->afterId)
            ->where('users.email', '!=', '')
            ->where('users.email', '!=', 'admin@admin.com')
            ->where('users.status', '=', 'ACTIVE')
            ->whereExists(function ($query) use ($memberTypes) {
                $query->select(DB::raw(1))
                    ->from('user_details')
                    ->whereColumn('user_details.user_code_id', 'users.id');

                if (count($memberTypes)) {
                    $query->whereIn('user_details.member_type', $memberTypes);
                }
            })
            ->orderBy('users.id')
            ->limit(200)
            ->get(['users.id', 'users.name', 'users.email']);

        foreach ($users as $user) {
            EmailCampaignJob::dispatch($this->campaignId, $user)
                ->onQueue('sendinblueemail');
        }

        if ($users->count() === 200) {
            self::dispatch($this->campaignId, $users->last()->id)
                ->onQueue('sendinblueemail');
        }
    }
}
