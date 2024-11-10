<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
    /** @test */
    public function it_registers_a_new_user_with_valid_data()
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'password' => 'securepassword',
            'password_confirmation' => 'securepassword',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'created_at',
                         'updated_at',
                     ],
                     'access_token',
                     'token_type',
                 ]);

        // Assert the user is in the database
        $this->assertDatabaseHas('users', [
            'email' => 'janedoe@example.com',
            'name' => 'Jane Doe',
        ]);

        // Retrieve the user
        $user = User::where('email', 'janedoe@example.com')->first();

        // Assert the password is hashed
        $this->assertTrue(Hash::check('securepassword', $user->password));

        // Assert a token was created
        $this->assertCount(1, $user->tokens);
        $this->assertEquals('auth_token', $user->tokens->first()->name);
    }
}
