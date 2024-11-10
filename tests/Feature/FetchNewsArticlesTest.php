<?php

namespace Tests\Feature;

use App\Console\Commands\FetchNewsArticles;
use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchNewsArticlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command successfully fetches and stores articles.
     *
     * @return void
     */
    public function test_command_fetches_and_stores_articles_successfully()
    {
        // Arrange: Mock NewsAPI response
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response([
                'status' => 'ok',
                'totalResults' => 2,
                'articles' => [
                    [
                        'title' => 'Test Article 1',
                        'content' => 'Content of Test Article 1',
                        'url' => 'https://newsapi.org/test-article-1',
                        'publishedAt' => '2023-10-01T10:00:00Z',
                        'author' => 'Author One',
                        'source' => [
                            'id' => 'newsapi',
                            'name' => 'News Api',
                        ],
                    ],
                    [
                        'title' => 'Test Article 2',
                        'content' => 'Content of Test Article 2',
                        'url' => 'https://newsapi.org/test-article-2',
                        'publishedAt' => '2023-10-02T11:00:00Z',
                        'author' => 'Author Two',
                        'source' => [
                            'id' => 'newsapi',
                            'name' => 'News Api',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-news-articles')
             ->expectsOutput('NewsAPI articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Check that the articles are stored in the database
        $this->assertDatabaseCount('sources', 1);
        $this->assertDatabaseHas('sources', [
            'name' => 'News Api',
            'url'  => 'https://newsapi.org/',
        ]);

        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 1',
            'content'      => 'Content of Test Article 1',
            'url'          => 'https://newsapi.org/test-article-1',
            'published_at' => '2023-10-01 10:00:00',
            'author'       => 'Author One',
            'category'     => 'News Api',
        ]);

        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 2',
            'content'      => 'Content of Test Article 2',
            'url'          => 'https://newsapi.org/test-article-2',
            'published_at' => '2023-10-02 11:00:00',
            'author'       => 'Author Two',
            'category'     => 'News Api',
        ]);
    }

    /**
     * Test that the command handles API failures gracefully.
     *
     * @return void
     */
    public function test_command_handles_api_failures_gracefully()
    {
        // Arrange: Mock NewsAPI failure
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response('Service Unavailable', 503),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-news-articles')
             ->expectsOutput('Failed to fetch articles: Service Unavailable')
             ->assertExitCode(0); // Exit code might still be 0 unless handled differently

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
            'name'        => 'News Api',
            'description' => 'Search worldwide news with code',
            'url'         => 'https://newsapi.org/',
        ]);

        Article::factory()->create([
            'title'        => 'Existing Article',
            'content'      => 'Content of Existing Article',
            'url'          => 'https://newsapi.org/existing-article',
            'published_at' => '2023-09-30 09:00:00',
            'author'       => 'Existing Author',
            'source_id'    => $source->id,
            'category'     => 'News Api',
        ]);

        // Mock NewsAPI response with a duplicate article and a new article
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response([
                'status' => 'ok',
                'totalResults' => 2,
                'articles' => [
                    [
                        'title' => 'Existing Article', // Duplicate title
                        'content' => 'Updated Content of Existing Article',
                        'url' => 'https://newsapi.org/existing-article', // Duplicate URL
                        'publishedAt' => '2023-09-30T09:00:00Z',
                        'author' => 'Existing Author',
                        'source' => [
                            'id' => 'newsapi',
                            'name' => 'News Api',
                        ],
                    ],
                    [
                        'title' => 'New Article',
                        'content' => 'Content of New Article',
                        'url' => 'https://newsapi.org/new-article',
                        'publishedAt' => '2023-10-03T12:00:00Z',
                        'author' => 'Author Three',
                        'source' => [
                            'id' => 'newsapi',
                            'name' => 'News Api',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-news-articles')
             ->expectsOutput('NewsAPI articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Ensure only one new article is added
        $this->assertDatabaseCount('sources', 1);
        $this->assertDatabaseCount('articles', 2);

        // Existing article should remain unchanged or updated based on implementation
        $this->assertDatabaseHas('articles', [
            'title'        => 'Existing Article',
            'url'          => 'https://newsapi.org/existing-article',
            'published_at' => '2023-09-30 09:00:00',
            'author'       => 'Existing Author',
            'content'      => 'Content of Existing Article', // Ensure it wasn't updated
        ]);

        // New article should be added
        $this->assertDatabaseHas('articles', [
            'title'        => 'New Article',
            'url'          => 'https://newsapi.org/new-article',
            'published_at' => '2023-10-03 12:00:00',
            'author'       => 'Author Three',
            'category'     => 'News Api',
        ]);
    }

    /**
     * Test that the command logs errors when an exception occurs.
     *
     * @return void
     */
    public function test_command_logs_errors_on_exception()
    {
        // Arrange: Mock NewsAPI to throw an exception
        Http::fake([
            'https://newsapi.org/v2/everything*' => Http::response(function () {
                throw new \Exception('Network error');
            }),
        ]);

        // Mock the Log facade
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'NewsAPI Fetch Error' &&
                       isset($context['exception']) &&
                       $context['exception']->getMessage() === 'Network error';
            });

        // Act: Run the command
        $this->artisan('app:fetch-news-articles')
             ->expectsOutput('An error occurred: Network error')
             ->assertExitCode(0);

        // Assert: Ensure no articles are stored
        $this->assertDatabaseCount('sources', 0);
        $this->assertDatabaseCount('articles', 0);
    }
}
