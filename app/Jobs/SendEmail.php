<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentToken;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DuesPaymentMail;
use App\Models\User;

class SendEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $member = User::where('user_code', $this->token->member_code)->first();

        if (!$member) {
            Log::error("Member not found for member code: {$this->token->member_code}");
            return;
        }

        Mail::to($member->email)->send(new DuesPaymentMail($this->token, $this->plainTextToken));

        // Log the notification
        NotificationLog::create([
            'token_id' => $this->token->id,
            'member_code' => $this->token->member_code,
            'notification_type' => 'email',
            'recipient' => $member->email,
            'subject' => 'Your monthly due payment link',
            'message_body' => 'Dear Member, your due payment link is: ' . url('/payment/' . $this->plainTextToken),
            'status' => 'sent',
        ]);

        $this->token->update(['email_sent' => true, 'email_sent_at' => now()]);
    }
}
