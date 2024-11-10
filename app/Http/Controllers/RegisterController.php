<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterValidationRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
/**
 * @OA\Post(
 *     path="/api/v1/register",
 *     tags={"Authentication"},
 *     summary="Register a new user",
 *     description="Creates a new user and returns the user details along with an access token.",
 *     operationId="register",
 *     @OA\RequestBody(
 *         required=true,
 *         content={
 *             @OA\MediaType(
 *                 mediaType="application/json",
 *                 @OA\Schema(
 *                     required={"name", "email", "password", "password_confirmation"},
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *                     @OA\Property(property="password", type="string", format="password", example="Password123"),
 *                     @OA\Property(property="password_confirmation", type="string", format="password", example="Password123")
 *                 )
 *             )
 *         }
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", ref="#/components/schemas/User"),
 *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGci...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 additionalProperties=@OA\Property(
 *                     type="array",
 *                     @OA\Items(type="string", example="The email has already been taken.")
 *                 )
 *             )
 *         )
 *     )
 * )
 */
 public function register(RegisterValidationRequest $request)
    {
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $request['password'],
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
