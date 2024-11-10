<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Sources",
 *     type="object",
 *     title="Source",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="NewsAPI"),
 *     @OA\Property(property="description", type="string", nullable=true, example="An API providing news articles."),
 *     @OA\Property(property="url", type="string", format="uri", nullable=true, example="https://newsapi.org/"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-10T05:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-10T05:00:00Z")
 * )
 */
class Source extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'url'];

}
