<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Source;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Set up common test data.
     */
    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     // Create a user
    //     $this->user = User::factory()->create();

    //     // Authenticate the user using Sanctum
    //     Sanctum::actingAs($this->user, ['*']);
    // }

    /**
     * Test that an authenticated user can access the articles index.
     *
     * @return void
     */
    public function authUser(){
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
    }
    public function test_authenticated_user_can_access_articles_index()
    {
        $this->authUser();
        // Create some articles
        Article::factory()->count(15)->create();

        // Send a GET request to the articles index route
        $response = $this->getJson('/api/v1/articles');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the response has the correct structure
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'content',
                    'category',
                    'author',
                    'source' => [
                        'id',
                        'name',
                        // Add other source fields as necessary
                    ],
                    'published_at',
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

        // Assert that the pagination is set to 10 per page
        $response->assertJsonPath('per_page', 10);
    }

    /**
     * Test that an unauthenticated user cannot access the articles index.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_access_articles_index()
    {

        // Send a GET request to the articles index route
        $response = $this->getJson('/api/v1/articles');

        // Assert that the response status is 401 Unauthorized
        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Unauthenticated.',
                 ]);
    }

    /**
     * Test filtering articles by keyword.
     *
     * @return void
     */
    public function test_filter_articles_by_keyword()
    {
        $this->authUser();
        // Create articles with specific titles and content
        Article::factory()->create([
            'title' => 'Laravel Testing',
            'content' => 'Testing in Laravel is essential.',
        ]);

        Article::factory()->create([
            'title' => 'PHPUnit Basics',
            'content' => 'Learn the basics of PHPUnit testing.',
        ]);

        Article::factory()->create([
            'title' => 'JavaScript Essentials',
            'content' => 'Essential concepts in JavaScript.',
        ]);

        // Send a GET request with keyword filter
        $response = $this->getJson('/api/v1/articles?keyword=Laravel');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only articles matching the keyword are returned
        $response->assertJsonCount(1, 'data');

        // Assert the specific article is returned
        $response->assertJsonFragment([
            'title' => 'Laravel Testing',
        ]);
    }

    /**
     * Test filtering articles by date.
     *
     * @return void
     */
    public function test_filter_articles_by_date()
    {
        $this->authUser();
        // Create articles with specific published dates
        Article::factory()->create([
            'title' => 'Article 1',
            'published_at' => '2023-01-01',
        ]);

        Article::factory()->create([
            'title' => 'Article 2',
            'published_at' => '2023-01-02',
        ]);

        Article::factory()->create([
            'title' => 'Article 3',
            'published_at' => '2023-01-01',
        ]);

        // Send a GET request with date filter
        $response = $this->getJson('/api/v1/articles?date=2023-01-01');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only articles with the specified date are returned
        $response->assertJsonCount(2, 'data');

        // Assert the specific articles are returned
        $response->assertJsonFragment([
            'title' => 'Article 1',
        ]);

        $response->assertJsonFragment([
            'title' => 'Article 3',
        ]);
    }

    /**
     * Test filtering articles by category.
     *
     * @return void
     */
    public function test_filter_articles_by_category()
    {
        $this->authUser();
        // Create articles with specific categories
        Article::factory()->create([
            'title' => 'Tech Article',
            'category' => 'Technology',
        ]);

        Article::factory()->create([
            'title' => 'Health Article',
            'category' => 'Health',
        ]);

        Article::factory()->create([
            'title' => 'Another Tech Article',
            'category' => 'Technology',
        ]);

        // Send a GET request with category filter
        $response = $this->getJson('/api/v1/articles?category=Technology');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only articles with the specified category are returned
        $response->assertJsonCount(2, 'data');

        // Assert the specific articles are returned
        $response->assertJsonFragment([
            'title' => 'Tech Article',
        ]);

        $response->assertJsonFragment([
            'title' => 'Another Tech Article',
        ]);
    }

    /**
     * Test filtering articles by author.
     *
     * @return void
     */
    public function test_filter_articles_by_author()
    {
        $this->authUser();
        // Create articles with specific authors
        Article::factory()->create([
            'title' => 'Author One Article',
            'author' => 'Author One',
        ]);

        Article::factory()->create([
            'title' => 'Author Two Article',
            'author' => 'Author Two',
        ]);

        Article::factory()->create([
            'title' => 'Another Author One Article',
            'author' => 'Author One',
        ]);

        // Send a GET request with author filter
        $response = $this->getJson('/api/v1/articles?author=Author One');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only articles with the specified author are returned
        $response->assertJsonCount(2, 'data');

        // Assert the specific articles are returned
        $response->assertJsonFragment([
            'title' => 'Author One Article',
        ]);

        $response->assertJsonFragment([
            'title' => 'Another Author One Article',
        ]);
    }

    /**
     * Test filtering articles by source.
     *
     * @return void
     */
    public function test_filter_articles_by_source()
    {
        $this->authUser();
        // Create sources
        $source1 = Source::factory()->create(['name' => 'Source One']);
        $source2 = Source::factory()->create(['name' => 'Source Two']);

        // Create articles with specific sources
        Article::factory()->create([
            'title' => 'Source One Article',
            'source_id' => $source1->id,
        ]);

        Article::factory()->create([
            'title' => 'Source Two Article',
            'source_id' => $source2->id,
        ]);

        Article::factory()->create([
            'title' => 'Another Source One Article',
            'source_id' => $source1->id,
        ]);

        // Send a GET request with source filter
        $response = $this->getJson('/api/v1/articles?source=Source One');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only articles with the specified source are returned
        $response->assertJsonCount(2, 'data');

        // Assert the specific articles are returned
        $response->assertJsonFragment([
            'title' => 'Source One Article',
        ]);

        $response->assertJsonFragment([
            'title' => 'Another Source One Article',
        ]);
    }

    /**
     * Test that multiple filters can be applied simultaneously.
     *
     * @return void
     */
    public function test_filter_articles_with_multiple_parameters()
    {
        $this->authUser();
        // Create sources
        $source1 = Source::factory()->create(['name' => 'Source One']);
        $source2 = Source::factory()->create(['name' => 'Source Two']);

        // Create articles with varying attributes
        Article::factory()->create([
            'title' => 'Laravel Testing Guide',
            'content' => 'Comprehensive guide to testing in Laravel.',
            'category' => 'Technology',
            'author' => 'John Doe',
            'source_id' => $source1->id,
            'published_at' => '2023-05-01',
            'url' => 'https://example.com/laravel-testing-guide',
        ]);

        Article::factory()->create([
            'title' => 'Health Benefits of Yoga',
            'content' => 'Exploring the health benefits of practicing yoga.',
            'category' => 'Health',
            'author' => 'Jane Smith',
            'source_id' => $source2->id,
            'published_at' => '2023-05-02',
            'url' => 'https://example.com/laravel-testing-guide-1',
        ]);

        Article::factory()->create([
            'title' => 'Advanced Laravel Techniques',
            'content' => 'Delving deeper into Laravel\'s advanced features.',
            'category' => 'Technology',
            'author' => 'John Doe',
            'source_id' => $source1->id,
            'published_at' => '2023-05-01',
            'url' => 'https://example.com/laravel-testing-guide-2',
        ]);

        // Apply multiple filters: keyword, date, category, author, source
        $filters = [
            'keyword' => 'Laravel',
            'date' => '2023-05-01',
            'category' => 'Technology',
            'author' => 'John Doe',
            'source' => 'Source One',
        ];

        // Build query string
        $queryString = http_build_query($filters);

        // Send a GET request with multiple filters
        $response = $this->getJson("/api/v1/articles?{$queryString}");

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that only the article matching all filters is returned
        $response->assertJsonCount(2, 'data'); // Assuming two articles match all filters

        // Assert specific articles are returned
        $response->assertJsonFragment([
            'title' => 'Laravel Testing Guide',
        ]);

        $response->assertJsonFragment([
            'title' => 'Advanced Laravel Techniques',
        ]);
    }

    /**
     * Test pagination of articles.
     *
     * @return void
     */
    public function test_articles_are_paginated_correctly()
    {
        $this->authUser();
        // Create 25 articles
        Article::factory()->count(25)->create();

        // Send a GET request to the first page
        $responsePage1 = $this->getJson('/api/v1/articles?page=1');

        // Assert that the response status is 200 OK
        $responsePage1->assertStatus(200);

        // Assert that the first page contains 10 articles
        $responsePage1->assertJsonCount(10, 'data');

        // Send a GET request to the third page
        $responsePage3 = $this->getJson('/api/v1/articles?page=3');

        // Assert that the response status is 200 OK
        $responsePage3->assertStatus(200);

        // Assert that the third page contains 5 articles
        $responsePage3->assertJsonCount(5, 'data');
    }

    /**
     * Test that invalid pagination parameters are handled gracefully.
     *
     * @return void
     */
    public function test_articles_pagination_with_invalid_parameters()
    {
        $this->authUser();
        // Create 5 articles
        Article::factory()->count(5)->create();

        // Send a GET request with a non-integer page parameter
        $response = $this->getJson('/api/v1/articles?page=invalid');

        // Assert that the response defaults to page 1 with 5 articles
        $response->assertStatus(200)
                 ->assertJsonPath('current_page', 1)
                 ->assertJsonCount(5, 'data');

        // Send a GET request with a negative page parameter
        $responseNegative = $this->getJson('/api/v1/articles?page=-1');

        // Assert that the response defaults to page 1 with 5 articles
        $responseNegative->assertStatus(200)
                         ->assertJsonPath('current_page', 1)
                         ->assertJsonCount(5, 'data');
    }

    /**
     * Test that an authenticated user can view an existing article.
     *
     * @return void
     */
    public function test_authenticated_user_can_view_existing_article()
    {
        $this->authUser();

        // Create a source
        $source = Source::factory()->create([
            'name' => 'The New York Times News',
            'url' => 'https://www.nytimesnews.com',
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
        ]);

        // Create an article associated with the source
        $article = Article::factory()->create([
            'title' => 'Breaking News',
            'content' => 'This is the content of the breaking news article.',
            'url' => 'https://www.example.com/breaking-news',
            'category' => 'Technology',
            'author' => 'Jane Doe',
            'source_id' => $source->id,
            'published_at' => '2023-09-15',
            'created_at'=> "2024-11-10T11:25:15.000000Z",
            'updated_at'=> "2024-11-10T11:25:15.000000Z",
        ]);

        // Send a GET request to the show route
        $response = $this->getJson("/api/v1/articles/{$article->id}");

        // Assert that the response status is 200 OK
        $response->assertStatus(200);


        $response->assertJson([
            'id' => $article->id,
            'title' => "Breaking News",
            'content' => "This is the content of the breaking news article.",
            'url' => "https://www.example.com/breaking-news",
            'category' => "Technology",
            'author' => "Jane Doe",
            'published_at' => "2023-09-15 00:00:00",
            'source_id'=> $source->id,
            'created_at'=> "2024-11-10T11:25:15.000000Z",
            'updated_at'=> "2024-11-10T11:25:15.000000Z",
            'source' => [
                'id' => $source->id,
                'name' => "The New York Times News",
                "description"=> "The New York Times is an American newspaper based in New York City with worldwide influence and readership.",
                "url"=> "https://www.nytimesnews.com",
                "created_at"=> $source->created_at->toJSON(),
                "updated_at"=> $source->updated_at->toJSON()
                // Add other source fields if necessary
            ],
            // Include other article fields as necessary
        ]);
    }

    /**
     * Test that an authenticated user receives a personalized feed based on their preferences.
     *
     * @return void
     */
    public function test_authenticated_user_receives_personalized_feed()
    {
        // Create and authenticate a user
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create sources
        $source1 = Source::factory()->create(['name' => 'The New York Times']);
        $source2 = Source::factory()->create(['name' => 'BBC News']);
        $source3 = Source::factory()->create(['name' => 'TechCrunch']);

        // Assign preferences to the user
        $preferences = [
            ['preference_type' => 'source', 'preference_value' => 'The New York Times'],
            ['preference_type' => 'category', 'preference_value' => 'Technology'],
            ['preference_type' => 'author', 'preference_value' => 'Jane Doe'],
        ];

        foreach ($preferences as $pref) {
            UserPreference::factory()->create([
                'user_id' => $user->id,
                'preference_type' => $pref['preference_type'],
                'preference_value' => $pref['preference_value'],
            ]);
        }

        // Create articles that match the preferences
        $matchingArticle1 = Article::factory()->create([
            'source_id' => $source1->id,
            'category' => 'Technology',
            'author' => 'Jane Doe',
        ]);

        $matchingArticle2 = Article::factory()->create([
            'source_id' => $source1->id,
            'category' => 'Technology',
            'author' => 'Jane Doe',
        ]);

        $matchingArticle3 = Article::factory()->create([
            'source_id' => $source2->id, // BBC News is not in preferences, should be excluded
            'category' => 'Health',
            'author' => 'John Smith',
        ]);

        $matchingArticle4 = Article::factory()->create([
            'source_id' => $source3->id, // TechCrunch is not in preferences, should be excluded
            'category' => 'Technology',
            'author' => 'Jane Doe',
        ]);

        // Send a GET request to the personalized feed route
        $response = $this->getJson('/api/v1/feed');

        // Assert that the response status is 200 OK
        $response->assertStatus(200);

        // Assert that the response has the correct pagination structure
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'content',
                    'url',
                    'category',
                    'author',
                    'source_id',
                    'created_at',
                    'updated_at',
                    'source' => [
                        'id',
                        'name',
                        'description',
                        'url',
                        'created_at',
                        'updated_at',
                    ],
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

        // Assert that only matching articles are returned (2 articles)
        $response->assertJsonCount(2, 'data');

        // Assert that the returned articles are the matching ones
        $response->assertJsonFragment([
            'id' => $matchingArticle1->id,
            'category' => 'Technology',
            'author' => 'Jane Doe',
            'source_id' => $source1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $matchingArticle2->id,
            'category' => 'Technology',
            'author' => 'Jane Doe',
            'source_id' => $source1->id,
        ]);

        // Ensure that non-matching articles are not in the response
        $response->assertJsonMissing([
            'id' => $matchingArticle3->id,
        ]);

        $response->assertJsonMissing([
            'id' => $matchingArticle4->id,
        ]);
    }
}
