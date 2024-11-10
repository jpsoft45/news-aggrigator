<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that a reset link is sent to a valid user email.
     *
     * @return void
     */
    public function test_reset_link_is_sent_to_valid_email()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
        ]);

        // Prepare the payload
        $payload = [
            'email' => 'johndoe@example.com',
        ];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/email', $payload);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => __('passwords.sent'),
                 ]);

        // Assert that a record exists in the password_resets table
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'johndoe@example.com',
            // Note: Laravel hashes the token, so we cannot assert the token's exact value
            'email' => 'johndoe@example.com',
        ]);
    }

    /**
     * Test that a reset link is not sent to a non-existing email.
     *
     * @return void
     */
    public function test_reset_link_is_not_sent_to_non_existing_email()
    {
        // Prepare the payload with a non-existing email
        $payload = [
            'email' => 'nonexistinguser@example.com',
        ];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/email', $payload);

        // Assert that the response status is 400 Bad Request
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => __('passwords.user'),
                 ]);

        // Assert that no record exists in the password_resets table
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nonexistinguser@example.com',
        ]);
    }
}
