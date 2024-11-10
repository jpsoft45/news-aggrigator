<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/preferences",
     *     summary="Retrieve user preferences",
     *     operationId="getUserPreferences",
     *     tags={"Preferences"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user preferences",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Preference")
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
    public function index(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences()->get();
        return response()->json($preferences);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/preferences",
     *     summary="Set user preferences",
     *     operationId="setPreferences",
     *     tags={"Preferences"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="preferences",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="type", type="string", example="category"),
     *                     @OA\Property(property="value", type="string", example="Technology")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Preferences updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validatedData = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.type' => 'required|string|in:source,category,author',
            'preferences.*.value' => 'required|string',
        ]);

        // Delete existing preferences
        $preferences = collect($request->input('preferences'))->map(function ($pref) use ($user) {
            return [
                'user_id' => $user->id,
                'preference_type' => $pref['type'],
                'preference_value' => $pref['value'],
            ];
        })->toArray();
        // Insert new preferences

        UserPreference::upsert(
            $preferences,
            ['user_id', 'preference_type'], // Unique constraints
            ['preference_value']            // Columns to update
        );

        return response()->json(['message' => 'Preferences updated successfully.']);
    }
}
