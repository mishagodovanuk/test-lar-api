<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Api user controller.
 */
class UserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'image' => 'required|image|mimes:jpeg,png,jpg',
            'password' => 'required|string|min:6',
        ]);

        $file = $request->file('image');
        $filename = time() . '.jpg';
        $tempPath = public_path('uploads/' . $filename);
        $file->move(public_path('uploads'), $filename);

        $apiKey = env('TINYPNG_API_KEY');

        if ($apiKey) {
            \Tinify\setKey($apiKey);
            try {
                \Tinify\fromFile($tempPath)
                    ->resize([
                        "method" => "cover",
                        "width"  => 70,
                        "height" => 70
                    ])
                    ->convert(["type" => ["image/jpeg"]])
                    ->toFile($tempPath);
            } catch (\Exception $e) {
                Log::error('Tinify error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Error processing image.',
                    'status'  => 'error'
                ], 500);
            }
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'avatar'   => 'uploads/' . $filename,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User added successfully!',
            'user'    => $user,
            'access_token' => $token,
        ], 201);
    }
}
