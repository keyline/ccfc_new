<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentToken;
use App\Models\NotificationLog;
use App\Models\Member;
use Illuminate\Support\Facades\Log;

class SendSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token;
    protected $plainTextToken;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(PaymentToken $token, $plainTextToken)
    {
        $this->token = $token;
        $this->plainTextToken = $plainTextToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $member = Member::where('member_code', $this->token->member_code)->first();

        if (!$member) {
            Log::error("Member not found for member code: {$this->token->member_code}");
            return;
        }

        // Placeholder for sending SMS
        Log::info("Sending SMS to member {$this->token->member_code} at {$member->phone} for due {$this->token->member_due_id}");

        // Log the notification
        NotificationLog::create([
            'token_id' => $this->token->id,
            'member_code' => $this->token->member_code,
            'notification_type' => 'sms',
            'recipient' => $member->phone,
            'message_body' => 'Dear Member, your due payment link is: ' . url('/payment/' . $this->plainTextToken),
            'status' => 'sent',
        ]);

        $this->token->update(['sms_sent' => true, 'sms_sent_at' => now()]);
    }
}
