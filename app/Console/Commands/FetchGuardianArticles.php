<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGuardianArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-guardian-articles';

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
        $apiKey = config('services.guardian.api_key');
        $query = ''; // You can make this dynamic or configurable

        $url = 'https://content.guardianapis.com/search';

        try {
            $response = Http::get($url, [
                'q'       => $query,
                'api-key' => $apiKey,
                'show-fields' => 'all', // Fetch all available fields
                'page-size'   => 50,    // Number of articles per request (max 50)
            ]);

            if ($response->successful()) {
                $articles = $response->json()['response']['results'];

                foreach ($articles as $articleData) {
                    // Process and save each article
                    $this->saveArticle($articleData);
                }

                $this->info('Guardian articles fetched and stored successfully.');
                Log::info('Guardian articles fetched and stored successfully.');
            } else {
                $this->error('Failed to fetch articles: ' . $response->body());
                Log::error('Guardian API error', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Guardian Fetch Error', ['exception' => $e]);
        }
    }

    protected function saveArticle($articleData)
    {
        $title = $articleData['webTitle'] ?? null;
        $content = $articleData['fields']['body'] ?? null;
        $url = $articleData['webUrl'] ?? null;
        $publishedAt = $articleData['webPublicationDate'] ?? null;
        $author = $articleData['fields']['byline'] ?? null;
        $category = $articleData['sectionName'] ?? null;

        if (!$url) {
            // Skip if URL is missing
            return;
        }

        // Check if the article already exists to prevent duplicates
        $existingArticle = Article::where('url', $url)->first();

        if (!$existingArticle) {
            Article::create([
                'title'        => $title,
                'content'      => $content,
                'url'          => $url,
                'published_at' => $publishedAt,
                'author'       => $author,
                'source_id'    => $this->getSourceId(),
                'category'     => $category,
                // Add other necessary fields
            ]);
        } else {
            // Optionally update existing article
            // $existingArticle->update([...]);
        }
    }

    protected function getSourceId()
    {
        $source = Source::firstOrCreate([
            'name' => 'The Guardian',
        ], [
            'description' => 'The Guardian is a British daily newspaper. It was founded in 1821 as The Manchester Guardian.',
            'url'         => 'https://www.theguardian.com',
        ]);

        return $source->id;
    }
}
