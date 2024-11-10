<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/articles",
     *     summary="Fetch articles with optional filters and pagination",
     *     operationId="getArticles",
     *     tags={"Articles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Keyword to search in title or content",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter articles by published date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter articles by category",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="author",
     *         in="query",
     *         description="Filter articles by author",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter articles by source name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http://yourappurl/api/articles?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=10),
     *             @OA\Property(property="last_page_url", type="string", example="http://yourappurl/api/articles?page=10"),
     *             @OA\Property(property="next_page_url", type="string", example="http://yourappurl/api/articles?page=2"),
     *             @OA\Property(property="path", type="string", example="http://yourappurl/api/articles"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     *             @OA\Property(property="to", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid date format.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Article::query();

        // Filter by keyword in title or content
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('content', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter by date
        if ($request->has('date')) {
            $date = $request->input('date');
            $query->whereDate('published_at', $date);
        }

        // Filter by category
        if ($request->has('category')) {
            $category = $request->input('category');
            $query->where('category', $category);
        }
        // Filter by author
        if ($request->has('author')) {
            $author = $request->input('author');
            $query->where('author', $author);
        }

        // Filter by source
        if ($request->has('source')) {
            $source = $request->input('source');
            $query->whereHas('source', function ($q) use ($source) {
                $q->where('name', $source);
            });
        }

        $articles = $query->paginate(10);
        return response()->json($articles);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/articles/{id}",
     *     summary="Get article details",
     *     operationId="getArticleById",
     *     tags={"Articles"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the article",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article details",
     *         @OA\JsonContent(ref="#/components/schemas/Article")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Article] 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $article = Article::with('source')->findOrFail($id);
        return response()->json($article);
    }


    /**
     * @OA\Get(
     *     path="/api/v1/feed",
     *     summary="Get personalized news feed based on user preferences",
     *     operationId="getPersonalizedFeed",
     *     tags={"Articles"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of personalized articles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Article")
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http://yourappurl/api/feed?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=10),
     *             @OA\Property(property="last_page_url", type="string", example="http://yourappurl/api/feed?page=10"),
     *             @OA\Property(property="next_page_url", type="string", example="http://yourappurl/api/feed?page=2"),
     *             @OA\Property(property="path", type="string", example="http://yourappurl/api/feed"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     *             @OA\Property(property="to", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function personalizedFeed(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        $query = Article::query();

        // Filter by sources
        $sources = $preferences->where('preference_type', 'source')->pluck('preference_value');
        if ($sources->isNotEmpty()) {
            $query->whereHas('source', function ($q) use ($sources) {
                $q->whereIn('name', $sources);
            });
        }

        // Filter by categories
        $categories = $preferences->where('preference_type', 'category')->pluck('preference_value');
        if ($categories->isNotEmpty()) {
            $query->whereIn('category', $categories);
        }

        // Filter by authors
        $authors = $preferences->where('preference_type', 'author')->pluck('preference_value');
        if ($authors->isNotEmpty()) {
            $query->whereIn('author', $authors);
        }

        $articles = $query->paginate(10);
        return response()->json($articles);
    }
}
