<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'preference_type',
        'preference_value',
    ];
    /**
     * @OA\Schema(
     *     schema="Preference",
     *     type="object",
     *     title="Preference",
     *     required={"id", "user_id", "type", "value"},
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="user_id", type="integer", example=1),
     *     @OA\Property(
     *         property="type",
     *         type="string",
     *         example="category",
     *         enum={"source", "category", "author"},
     *         description="Type of preference"
     *     ),
     *     @OA\Property(property="value", type="string", example="Technology"),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-11-10T05:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-11-10T05:00:00Z")
     * )
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
