<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FetchNYTimesArticlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command successfully fetches and stores articles.
     *
     * @return void
     */
    public function test_command_fetches_and_stores_articles_successfully()
    {
        // Arrange: Mock NYTimes API response
        Http::fake([
            'https://api.nytimes.com/svc/search/v2/articlesearch.json*' => Http::response([
                'status' => 'OK',
                'response' => [
                    'docs' => [
                        [
                            'headline' => ['main' => 'Test Article 1'],
                            'abstract' => 'Abstract of Test Article 1',
                            'web_url' => 'https://www.nytimes.com/test-article-1',
                            'pub_date' => '2023-10-01T10:00:00Z',
                            'byline' => ['original' => 'By Author One'],
                            'subsection_name' => 'Technology',
                        ],
                        [
                            'headline' => ['main' => 'Test Article 2'],
                            'abstract' => 'Abstract of Test Article 2',
                            'web_url' => 'https://www.nytimes.com/test-article-2',
                            'pub_date' => '2023-10-02T11:00:00Z',
                            'byline' => ['original' => 'By Author Two'],
                            'subsection_name' => 'Health',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-n-y-times-articles')
             ->expectsOutput('NYTimes articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Check that the source is stored
        $this->assertDatabaseCount('sources', 1);
        $this->assertDatabaseHas('sources', [
            'name' => 'The New York Times',
            'url'  => 'https://www.nytimes.com',
        ]);

        // Assert: Check that the articles are stored in the database
        $this->assertDatabaseCount('articles', 2);
        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 1',
            'content'      => 'Abstract of Test Article 1',
            'url'          => 'https://www.nytimes.com/test-article-1',
            'published_at' => '2023-10-01 10:00:00',
            'author'       => 'By Author One',
            'category'     => 'Technology',
        ]);

        $this->assertDatabaseHas('articles', [
            'title'        => 'Test Article 2',
            'content'      => 'Abstract of Test Article 2',
            'url'          => 'https://www.nytimes.com/test-article-2',
            'published_at' => '2023-10-02 11:00:00',
            'author'       => 'By Author Two',
            'category'     => 'Health',
        ]);
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
            'name'        => 'The New York Times',
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
            'url'         => 'https://www.nytimes.com',
        ]);

        Article::factory()->create([
            'title'        => 'Existing Article',
            'content'      => 'Content of Existing Article',
            'url'          => 'https://www.nytimes.com/existing-article',
            'published_at' => '2023-09-30 09:00:00',
            'author'       => 'By Existing Author',
            'source_id'    => $source->id,
            'category'     => 'Technology',
        ]);

        // Mock NYTimes API response with a duplicate article and a new article
        Http::fake([
            'https://api.nytimes.com/svc/search/v2/articlesearch.json*' => Http::response([
                'status' => 'OK',
                'response' => [
                    'docs' => [
                        [
                            'headline' => ['main' => 'Existing Article'], // Duplicate title
                            'abstract' => 'Updated Abstract of Existing Article',
                            'web_url' => 'https://www.nytimes.com/existing-article', // Duplicate URL
                            'pub_date' => '2023-09-30T09:00:00Z',
                            'byline' => ['original' => 'By Existing Author'],
                            'subsection_name' => 'Technology',
                        ],
                        [
                            'headline' => ['main' => 'New Article'],
                            'abstract' => 'Abstract of New Article',
                            'web_url' => 'https://www.nytimes.com/new-article',
                            'pub_date' => '2023-10-03T12:00:00Z',
                            'byline' => ['original' => 'By Author Three'],
                            'subsection_name' => 'Finance',
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('app:fetch-n-y-times-articles')
             ->expectsOutput('NYTimes articles fetched and stored successfully.')
             ->assertExitCode(0);

        // Assert: Ensure only one new article is added
        $this->assertDatabaseCount('sources', 1);
        $this->assertDatabaseCount('articles', 2);

        // Existing article should remain unchanged
        $this->assertDatabaseHas('articles', [
            'title'        => 'Existing Article',
            'content'      => 'Content of Existing Article', // Should not be updated
            'url'          => 'https://www.nytimes.com/existing-article',
            'published_at' => '2023-09-30 09:00:00',
            'author'       => 'By Existing Author',
            'category'     => 'Technology',
        ]);

        // New article should be added
        $this->assertDatabaseHas('articles', [
            'title'        => 'New Article',
            'content'      => 'Abstract of New Article',
            'url'          => 'https://www.nytimes.com/new-article',
            'published_at' => '2023-10-03 12:00:00',
            'author'       => 'By Author Three',
            'category'     => 'Finance',
        ]);
    }
}
