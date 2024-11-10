<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchGuardianArticlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command successfully fetches and stores articles.
     *
     * @return void
     */
    public function test_command_fetches_and_stores_articles_successfully()
    {
        // Arrange: Mock The Guardian API response
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'status' => 'ok',
                    'results' => [
                        [
                            'webTitle' => 'Test Article 1',
                            'webUrl' => 'https://www.theguardian.com/test-article-1',
                            'webPublicationDate' => '2023-10-01',
                            'fields' => [
                                'body' => 'Content of Test Article 1',
                                'byline' => 'Author One',
                            ],
                        ],
                        [
                            'webTitle' => 'Test Article 2',
                            'webUrl' => 'https://www.theguardian.com/test-article-2',
                            'webPublicationDate' => '2023-10-02',
                            'fields' => [
                                'body' => 'Content of Test Article 2',
                                'byline' => 'Author Two',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-guardian-articles')
             ->expectsOutput('Guardian articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Check that the articles are stored in the database
        $this->assertDatabaseCount('sources', 1);
        $this->assertDatabaseHas('sources', [
            'name' => 'The Guardian',
            'url' => 'https://www.theguardian.com',
        ]);

        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 1',
            'url'          => 'https://www.theguardian.com/test-article-1',
            'published_at' => '2023-10-01',
            'author'       => 'Author One',
        ]);

        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 2',
            'url'          => 'https://www.theguardian.com/test-article-2',
            'published_at' => '2023-10-02',
            'author'       => 'Author Two',
        ]);
    }

    /**
     * Test that the command handles API failures gracefully.
     *
     * @return void
     */
    public function test_command_handles_api_failures_gracefully()
    {
        // Arrange: Mock The Guardian API failure
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response('Service Unavailable', 503),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-guardian-articles')
             ->expectsOutput('Failed to fetch articles: Service Unavailable')
             ->assertExitCode(0); // Exit code might still be 0 unless you handle it differently

        // Assert: Ensure no articles are stored
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('articles', 0);
    }

    /**
     * Test that the command does not store duplicate articles.
     *
     * @return void
     */
    public function test_command_does_not_store_duplicate_articles()
    {
        // Arrange: Create a source and an existing article
        $source = Source::factory()->create([
            'name' => 'The Guardian',
            'url' => 'https://www.theguardian.com',
        ]);

        Article::factory()->create([
            'title'        => 'Existing Article',
            'url'          => 'https://www.theguardian.com/existing-article',
            'published_at' => '2023-09-30',
            'author'       => 'Existing Author',
            'source_id'    => $source->id,
        ]);

        // Mock The Guardian API response with a duplicate article
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response([
                'response' => [
                    'status' => 'ok',
                    'results' => [
                        [
                            'webTitle' => 'Existing Article', // Duplicate title
                            'webUrl' => 'https://www.theguardian.com/existing-article', // Duplicate URL
                            'webPublicationDate' => '2023-09-30',
                            'fields' => [
                                'body' => 'Content of Existing Article',
                                'byline' => 'Existing Author',
                            ],
                        ],
                        [
                            'webTitle' => 'New Article',
                            'webUrl' => 'https://www.theguardian.com/new-article',
                            'webPublicationDate' => '2023-10-03',
                            'fields' => [
                                'body' => 'Content of New Article',
                                'byline' => 'Author Three',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-guardian-articles')
             ->expectsOutput('Guardian articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Ensure only one new article is added
        $this->assertDatabaseCount('articles', 2); // 1 existing + 1 new

        $this->assertDatabaseHas('articles', [
            'title'        => 'New Article',
            'url'          => 'https://www.theguardian.com/new-article',
            'published_at' => '2023-10-03',
            'author'       => 'Author Three',
        ]);

        // Ensure the duplicate article was not duplicated
        $this->assertDatabaseCount('articles', 2);
    }

    /**
     * Test that the command logs errors when an exception occurs.
     *
     * @return void
     */
    public function test_command_logs_errors_on_exception()
    {
        // Arrange: Mock The Guardian API to throw an exception
        Http::fake([
            'https://content.guardianapis.com/search*' => Http::response(function () {
                throw new \Exception('Network error');
            }),
        ]);

        // Mock the Log facade
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Guardian Fetch Error' &&
                       isset($context['exception']) &&
                       $context['exception']->getMessage() === 'Network error';
            });

        // Act: Run the command
        $this->artisan('app:fetch-guardian-articles')
             ->expectsOutput('An error occurred: Network error')
             ->assertExitCode(0);

        // Assert: Ensure no articles are stored
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('articles', 0);
    }

}
