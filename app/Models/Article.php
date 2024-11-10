<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     title="Article",
 *     required={"id", "title", "content", "url", "published_at"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="title", type="string", example="Article Title"),
 *     @OA\Property(property="content", type="string", example="Article content..."),
 *     @OA\Property(property="author", type="string", nullable=true, example="John Doe"),
 *     @OA\Property(property="source", type="string", example="NewsAPI"),
 *     @OA\Property(property="category", type="string", nullable=true, example="Technology"),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2023-11-10T04:57:15Z"),
 *     @OA\Property(property="url", type="string", format="uri", example="https://example.com/article"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-10T05:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-10T05:00:00Z")
 * )
 */
class Article extends Model
{

    protected $fillable = [
        'title',
        'content',
        'url',
        'published_at',
        'author',
        'source_id',
        'category'
    ];
    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function setPublishedAtAttribute($value)
    {
        $this->attributes['published_at'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
