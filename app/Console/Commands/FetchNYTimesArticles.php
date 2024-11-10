<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchNYTimesArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-n-y-times-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('services.nytimes.api_key');
        $query = ''; // You can make this dynamic or configurable

        $url = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';

        try {
            $response = Http::get($url, [
                'q' => $query,
                'api-key' => $apiKey,
            ]);

            if ($response->successful()) {
                $articles = $response->json()['response']['docs'];

                foreach ($articles as $articleData) {
                    // Process and save each article
                    $this->saveArticle($articleData);
                }

                $this->info('NYTimes articles fetched and stored successfully.');
            } else {
                $this->error('Failed to fetch articles: ' . $response->body());
                Log::error('NYTimes API error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('NYTimes Fetch Error', ['exception' => $e]);
        }
    }

    protected function saveArticle($articleData)
    {
        // dd($articleData['subsection_name']);
        // Extract necessary fields
        $title = $articleData['headline']['main'] ?? null;
        $abstract = $articleData['abstract'] ?? null;
        $url = $articleData['web_url'] ?? null;
        $publishedAt = $articleData['pub_date'] ?? null;
        $author = $articleData['byline']['original'] ?? null;
        $category = $articleData['subsection_name'] ?? null;

        // Check if the article already exists to prevent duplicates
        $existingArticle = Article::where('url', $url)->first();

        if (!$existingArticle) {
            // Create a new article
            Article::create([
                'title'        => $title,
                'content'      => $abstract,
                'url'          => $url,
                'published_at' => $publishedAt,
                'author'       => $author,
                'source_id'    => $this->getSourceId(), // Implement this method or set a default value
                'category'     => $category
                // Add other necessary fields
            ]);
        }
    }

    protected function getSourceId()
    {
        $source = \App\Models\Source::firstOrCreate([
            'name' => 'The New York Times',
        ], [
            'description' => 'The New York Times is an American newspaper based in New York City with worldwide influence and readership.',
            'url'         => 'https://www.nytimes.com',
        ]);

        return $source->id;
    }
}
