<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can access the preferences index.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_preferences_index()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create some preferences for the user
        UserPreference::factory()->count(10)->for($user)->create();

        // Send a GET request to the preferences index route
        $response = $this->getJson('/api/v1/preferences');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the response has the correct structure
        $response->assertJsonStructure([
            '*' => [ // Assuming preferences are returned as a JSON array
                'id',
                'preference_type',
                'preference_value',
                'user_id',
                'created_at',
                'updated_at',
            ],
        ]);

        // Assert that exactly 10 preferences are returned
        $response->assertJsonCount(10);

        // Optionally, assert specific data if necessary
        $preference = $user->preferences()->first();
        $response->assertJsonFragment([
            'id' => $preference->id,
            'preference_type' => $preference->preference_type,
            'preference_value' => $preference->preference_value,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that an authenticated user can store preferences with valid data.
     *
     * @return void
     */
    public function test_authenticated_user_can_store_preferences_with_valid_data()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Define valid preferences payload
        $payload = [
            'preferences' => [
                [
                    'type' => 'source',
                    'value' => 'The New York Times',
                ],
                [
                    'type' => 'category',
                    'value' => 'Technology',
                ],
                [
                    'type' => 'author',
                    'value' => 'Jane Doe',
                ],
            ],
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $payload);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences updated successfully.',
                 ]);

        // Assert that the preferences are correctly stored in the database
        $this->assertDatabaseCount('user_preferences', 3);
        foreach ($payload['preferences'] as $pref) {
            $this->assertDatabaseHas('user_preferences', [
                'user_id' => $user->id,
                'preference_type' => $pref['type'],
                'preference_value' => $pref['value'],
            ]);
        }
    }

    // /**
    //  * Test that an unauthenticated user cannot store preferences.
    //  *
    //  * @return void
    //  */
    public function test_unauthenticated_user_cannot_store_preferences()
    {

        // Define a preferences payload
        $payload = [
            'preferences' => [
                [
                    'type' => 'source',
                    'value' => 'The New York Times',
                ],
            ],
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $payload);

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);

        // Assert that no preferences are stored in the database
        $this->assertDatabaseCount('user_preferences', 0);
    }

    /**
     * Test that storing preferences with invalid data returns validation errors.
     *
     * @return void
     */
    public function test_storing_preferences_with_invalid_data_returns_validation_errors()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Define invalid preferences payload (missing 'value' and invalid 'type')
        $payload = [
            'preferences' => [
                [
                    'type' => 'invalid_type',
                    // 'value' is missing
                ],
                [
                    // 'type' is missing
                    'value' => 'Technology',
                ],
                [
                    'type' => 'author',
                    'value' => '', // Empty value
                ],
            ],
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $payload);

        // Assert that the response status is 422 Unprocessable Entity
        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'preferences.0.type',
                     'preferences.0.value',
                     'preferences.1.type',
                     'preferences.2.value',
                 ]);

        // Assert that no preferences are stored in the database
        $this->assertDatabaseCount('user_preferences', 0);
    }

    /**
     * Test that storing preferences replaces existing preferences.
     *
     * @return void
     */
    public function test_storing_preferences_replaces_existing_preferences()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create initial preferences
        $initialPreferences = [
            [
                'type' => 'source',
                'value' => 'Old Source',
            ],
            [
                'type' => 'category',
                'value' => 'Old Category',
            ],
        ];

        foreach ($initialPreferences as $pref) {
            UserPreference::factory()->create([
                'user_id' => $user->id,
                'preference_type' => $pref['type'],
                'preference_value' => $pref['value'],
            ]);
        }

        // Define new preferences payload to replace existing ones
        $newPreferences = [
            'preferences' => [
                [
                    'type' => 'source',
                    'value' => 'The New York Times',
                ],
                [
                    'type' => 'author',
                    'value' => 'Jane Doe',
                ],
            ],
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $newPreferences);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Preferences updated successfully.',
            ]);

        // Assert that the new preferences are correctly stored
        foreach ($newPreferences['preferences'] as $pref) {
            $this->assertDatabaseHas('user_preferences', [
                'user_id' => $user->id,
                'preference_type' => $pref['type'],
                'preference_value' => $pref['value'],
            ]);
        }
    }

    /**
     * Test that storing preferences with duplicate types updates existing preferences.
     *
     * @return void
     */
    public function test_storing_preferences_with_duplicate_types_updates_existing_preferences()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create an initial preference
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preference_type' => 'source',
            'preference_value' => 'Old Source',
        ]);

        // Define preferences payload with duplicate 'source' type to update
        $payload = [
            'preferences' => [
                [
                    'type' => 'source',
                    'value' => 'The New York Times',
                ],
                [
                    'type' => 'author',
                    'value' => 'Jane Doe',
                ],
            ],
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $payload);

        // Assert that the response status is 200 OK
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Preferences updated successfully.',
                 ]);

        // Assert that the 'source' preference has been updated
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'preference_type' => 'source',
            'preference_value' => 'The New York Times',
        ]);

        // Assert that the 'author' preference has been inserted
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'preference_type' => 'author',
            'preference_value' => 'Jane Doe',
        ]);
    }

    /**
     * Test that storing preferences with an empty preferences array returns a validation error.
     *
     * @return void
     */
    public function test_storing_preferences_with_empty_preferences_array_returns_validation_error()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Define payload with an empty preferences array
        $payload = [
            'preferences' => [] // Empty array
        ];

        // Send a POST request to the preferences store route
        $response = $this->postJson('/api/v1/preferences', $payload);

        // Assert that the response status is 422 Unprocessable Entity
        $response->assertStatus(422);

        // Assert that the response has a validation error for 'preferences'
        $response->assertJsonValidationErrors([
            'preferences'
        ]);

        // Optionally, assert the specific validation message
        $response->assertJsonFragment([
            'preferences' => ['The preferences field is required.']
        ]);

        // Assert that no preferences are stored in the database
        $this->assertDatabaseCount('user_preferences', 0);
    }
}
