<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    /**
     * Test that a user can log in with valid credentials.
     *
     * @return void
     */
    public function test_user_can_login_with_valid_credentials()
    {
        // Create a user with a known password
        $user = User::factory()->create([
            'password' => Hash::make('securepassword'),
        ]);

        // Prepare the login payload
        $payload = [
            'email' => $user->email,
            'password' => 'securepassword',
        ];

        // Send a POST request to the login route
        $response = $this->postJson('/api/v1/login', $payload);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                 ]);

        // Assert that an access token is present in the response
        $this->assertArrayHasKey('access_token', $response->json());
        $this->assertEquals('Bearer', $response->json('token_type'));

        // Optionally, assert that the token is valid by checking the database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'auth_token',
        ]);
    }

    /**
     * Test that login fails with an invalid email format.
     *
     * @return void
     */
    public function test_login_fails_with_invalid_email_format()
    {
        // Prepare the login payload with an invalid email
        $payload = [
            'email' => 'jpsoft55gmail.com',
            'password' => 'somepassword',
        ];

        // Send a POST request to the login route
        $response = $this->postJson('/api/v1/login', $payload);

        // Assert that the response status is 422 Unprocessable Entity
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that login fails when the user does not exist.
     *
     * @return void
     */
    public function test_login_fails_when_user_does_not_exist()
    {
        // Prepare the login payload with a non-existing user
        $payload = [
            'email' => 'nonexistinguser@example.com',
            'password' => 'password123',
        ];

        // Send a POST request to the login route
        $response = $this->postJson('/api/v1/login', $payload);

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Invalid login details',
                 ]);
    }

    /**
     * Test that an authenticated user can successfully log out.
     *
     * @return void
     */
    public function test_authenticated_user_can_logout()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a personal access token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Send a POST request to the logout route with the token
        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/v1/logout');

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logout successful',
                 ]);

        // Assert that the token has been deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'name' => 'auth_token',
        ]);
    }
}
