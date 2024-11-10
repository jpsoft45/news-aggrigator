<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SourceTest extends TestCase
{
    use RefreshDatabase;


    /**
     * Test that an authenticated user can access the sources index.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_sources_index()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create some sources
        Source::factory()->count(20)->create();

        // Send a GET request to the sources index route
        $response = $this->getJson('/api/v1/sources');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the response has the correct structure
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'url',
                    'created_at',
                    'updated_at',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        // Assert that the pagination is set to 15 per page (default)
        $response->assertJsonPath('per_page', 15);

        // Assert that the total number of sources is 20
        $response->assertJsonPath('total', 20);
    }

    /**
     * Test that an unauthenticated user cannot access the sources index.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_sources_index()
    {
        // Revoke authentication by not authenticating any user

        // Send a GET request to the sources index route
        $response = $this->getJson('/api/v1/sources');

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }

    /**
     * Test that the sources index returns the correct number of sources per page.
     *
     * @return void
     */
    public function test_sources_index_returns_correct_number_of_sources_per_page()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create 30 sources
        Source::factory()->count(30)->create();

        // Send a GET request to the first page
        $responsePage1 = $this->getJson('/api/v1/sources?page=1');

        // Assert that the response status is 200 OK
        $responsePage1->assertStatus(200);

        // Assert that the first page contains 15 sources (default per page)
        $responsePage1->assertJsonCount(15, 'data');

        // Send a GET request to the second page
        $responsePage2 = $this->getJson('/api/v1/sources?page=2');

        // Assert that the second page contains 15 sources
        $responsePage2->assertStatus(200)
                      ->assertJsonCount(15, 'data');

        // Send a GET request to a non-existing third page
        $responsePage3 = $this->getJson('/api/v1/sources?page=3');

        // Assert that the third page contains 0 sources
        $responsePage3->assertStatus(200)
                      ->assertJsonCount(0, 'data');
    }

    /**
     * Test that the sources index includes the correct data.
     *
     * @return void
     */
    public function test_sources_index_includes_correct_data()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create a specific source
        $source = Source::factory()->create([
            'name' => 'The New York Times News',
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
            'url' => 'https://www.nytimesnews.com',
        ]);

        // Send a GET request to the sources index route
        $response = $this->getJson('/api/v1/sources');

        // Assert that the response contains the specific source
        $response->assertJsonFragment([
            'id' => $source->id,
            'name' => 'The New York Times News',
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
            'url' => 'https://www.nytimesnews.com',
        ]);
    }

    /**
     * Test that the sources index handles empty results gracefully.
     *
     * @return void
     */
    public function test_sources_index_handles_empty_results_gracefully()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Ensure no sources exist
        Source::factory()->count(0)->create();

        // Send a GET request to the sources index route
        $response = $this->getJson('/api/v1/sources');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the data array is empty
        $response->assertJsonCount(0, 'data');

        // Assert pagination fields are present and correctly set
        $response->assertJson([
            'current_page' => 1,
            'data' => [],
            'from' => null,
            'last_page' => 1,
            'to' => null,
            'total' => 0,
        ]);
    }
}
