<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Member;
use App\Models\PaymentToken;
use Illuminate\Support\Facades\Auth;

class MagicLinkLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_member_can_login_with_a_valid_magic_link()
    {
        // Create a member
        $member = Member::factory()->create();

        // Create a valid payment token
        $token = PaymentToken::create([
            'member_id' => $member->id,
            'token' => 'valid-token',
            'expires_at' => now()->addDay(),
        ]);

        // Hit the magic link endpoint
        $response = $this->get(route('magic.login', ['token' => 'valid-token']));

        // Assert that the member is logged in
        $this->assertTrue(Auth::guard('members')->check());
        $this->assertEquals($member->id, Auth::guard('members')->user()->id);

        // Assert that the token is marked as used
        $this->assertNotNull($token->fresh()->used_at);

        // Assert that the user is redirected to the dashboard
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function a_member_cannot_login_with_an_invalid_magic_link()
    {
        // Hit the magic link endpoint with an invalid token
        $response = $this->get(route('magic.login', ['token' => 'invalid-token']));

        // Assert that the member is not logged in
        $this->assertFalse(Auth::guard('members')->check());

        // Assert that the user is redirected to the login page with an error
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired token.');
    }

    /** @test */
    public function a_member_cannot_login_with_an_expired_magic_link()
    {
        // Create a member
        $member = Member::factory()->create();

        // Create an expired payment token
        $token = PaymentToken::create([
            'member_id' => $member->id,
            'token' => 'expired-token',
            'expires_at' => now()->subDay(),
        ]);

        // Hit the magic link endpoint
        $response = $this->get(route('magic.login', ['token' => 'expired-token']));

        // Assert that the member is not logged in
        $this->assertFalse(Auth::guard('members')->check());

        // Assert that the user is redirected to the login page with an error
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired token.');
    }

    /** @test */
    public function a_member_cannot_login_with_a_used_magic_link()
    {
        // Create a member
        $member = Member::factory()->create();

        // Create a used payment token
        $token = PaymentToken::create([
            'member_id' => $member->id,
            'token' => 'used-token',
            'expires_at' => now()->addDay(),
            'used_at' => now(),
        ]);

        // Hit the magic link endpoint
        $response = $this->get(route('magic.login', ['token' => 'used-token']));

        // Assert that the member is not logged in
        $this->assertFalse(Auth::guard('members')->check());

        // Assert that the user is redirected to the login page with an error
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Invalid or expired token.');
    }

use Illuminate\Support\Facades\Mail;

    /** @test */
    public function a_magic_link_can_be_sent_to_a_member()
    {
        Mail::fake();

        // Create a member
        $member = Member::factory()->create();

        // Hit the send magic link endpoint
        $response = $this->post(route('member.send.magic.link'), ['member_id' => $member->id]);

        // Assert that a payment token was created
        $this->assertDatabaseHas('payment_tokens', [
            'member_id' => $member->id,
        ]);

        // Assert that an email was sent
        Mail::assertSent(MagicLinkEmail::class, function ($mail) use ($member) {
            return $mail->hasTo($member->select_member->email);
        });

        // Assert that the user is redirected back with a success message
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Magic link sent successfully!');
    }
}
