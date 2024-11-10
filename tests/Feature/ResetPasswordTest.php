<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user can successfully reset their password with valid data.
     *
     * @return void
     */
    public function test_user_can_reset_password_with_valid_data()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        // Generate a valid password reset token
        $token = Password::createToken($user);

        // Prepare the payload
        $payload = [
            'token' => $token,
            'email' => 'johndoe@example.com',
            'password' => 'newsecurepassword',
            'password_confirmation' => 'newsecurepassword',
        ];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/reset', $payload);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => __('passwords.reset'),
                 ]);

        // Assert that the user's password has been updated
        $this->assertTrue(Hash::check('newsecurepassword', $user->fresh()->password));

        // Assert that the password reset token has been deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'johndoe@example.com',
        ]);
    }

    /**
     * Test that password reset fails with invalid or expired token.
     *
     * @return void
     */
    public function test_password_reset_fails_with_invalid_or_expired_token()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'janedoe@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        // Prepare the payload with an invalid token
        $payload = [
            'token' => 'invalid-token',
            'email' => 'janedoe@example.com',
            'password' => 'newsecurepassword',
            'password_confirmation' => 'newsecurepassword',
        ];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/reset', $payload);

        // Assert that the response status is 400 Bad Request
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => __('passwords.token'),
                 ]);

        // Assert that the user's password has not been changed
        $this->assertTrue(Hash::check('oldpassword', $user->fresh()->password));
    }

    /**
     * Test that password reset fails when email does not exist.
     *
     * @return void
     */
    public function test_password_reset_fails_when_email_does_not_exist()
    {
        // Prepare the payload with a non-existing email
        $payload = [
            'token' => 'some-valid-token',
            'email' => 'nonexistinguser@example.com',
            'password' => 'newsecurepassword',
            'password_confirmation' => 'newsecurepassword',
        ];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/reset', $payload);

        // Assert that the response status is 400 Bad Request
        $response->assertStatus(400)
                 ->assertJson([
                     'message' => __('passwords.user'),
                 ]);
    }

    /**
     * Test that password reset fails when required fields are missing.
     *
     * @return void
     */
    public function test_password_reset_fails_when_required_fields_are_missing()
    {
        // Prepare an empty payload
        $payload = [];

        // Send a POST request to the password reset route
        $response = $this->postJson('/api/v1/password/reset', $payload);

        // Assert that the response status is 422 Unprocessable Entity
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['token', 'email', 'password']);
    }



}
