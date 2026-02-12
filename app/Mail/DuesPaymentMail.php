<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\PaymentToken;

class DuesPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $plainTextToken;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PaymentToken $token, $plainTextToken)
    {
        $this->token = $token;
        $this->plainTextToken = $plainTextToken;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Monthly Dues Payment Link')
                    ->view('emails.dues_payment');
    }
}
