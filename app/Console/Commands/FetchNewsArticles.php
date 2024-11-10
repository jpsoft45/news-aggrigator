<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use jcobhams\NewsApi\NewsApi;

class FetchNewsArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-news-articles';

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
        $apiKey = config('services.newsapi.api_key');
        $query = 'technology'; // You can make this dynamic

        $url = 'https://newsapi.org/v2/everything';

        try {
            $response = Http::get($url, [
                'q' => $query,
                'apiKey' => $apiKey,
                'pageSize' => 100, // Max 100
            ]);

            if ($response->successful()) {
                $articles = $response->json()['articles'];
                foreach ($articles as $articleData) {
                    $this->saveArticle($articleData);
                }

                $this->info('NewsAPI articles fetched and stored successfully.');
                Log::info('NewsAPI articles fetched and stored successfully.');
            } else {
                $this->error('Failed to fetch articles: ' . $response->body());
                Log::error('NewsAPI error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('NewsAPI Fetch Error', ['exception' => $e]);
        }
    }

    protected function saveArticle($articleData)
    {
        $title = $articleData['title'] ?? null;
        $content = $articleData['content'] ?? null;
        $url = $articleData['url'] ?? null;
        $publishedAt = $articleData['publishedAt'] ?? null;
        $author = $articleData['author'] ?? null;
        $category = $articleData['source']['name'] ?? null;

        if (!$url) {
            return;
        }

        $existingArticle = Article::where('url', $url)->first();

        if (!$existingArticle) {
            Article::create([
                'title'        => $title,
                'content'      => $content,
                'url'          => $url,
                'published_at' => $publishedAt,
                'author'       => $author,
                'source_id'    => $this->getSourceId(),
                'category'     => $category
                // Add other fields as necessary
            ]);
        }
        dd("here");
    }

    protected function getSourceId()
    {
        $source = Source::firstOrCreate([
            'name' => 'News Api',
        ], [
            'description' => 'Search worldwide news with code',
            'url'         => 'https://newsapi.org/',
        ]);

        return $source->id;
    }
}
