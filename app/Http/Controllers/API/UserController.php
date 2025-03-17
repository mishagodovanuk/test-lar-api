<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Returns a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $count = (int)$request->query('count', 5);
        $page  = (int)$request->query('page', 1);

        if ($count < 1 || $page < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'fails'   => [
                    'count' => $count < 1 ? ['The count must be at least 1.'] : [],
                    'page'  => $page < 1 ? ['The page must be at least 1.'] : [],
                ]
            ], 422);
        }

        $usersPaginated = User::orderBy('id', 'asc')->paginate($count, ['*'], 'page', $page);

        $users = $usersPaginated->getCollection()->transform(function ($user) {
            return array_merge($user->toArray(), [
                'registration_timestamp' => $user->created_at->timestamp,
                'position'               => $user->position,
            ]);
        });

        return response()->json([
            'success'     => true,
            'page'        => $usersPaginated->currentPage(),
            'total_pages' => $usersPaginated->lastPage(),
            'total_users' => $usersPaginated->total(),
            'count'       => $usersPaginated->count(),
            'links'       => [
                'next_url' => $usersPaginated->nextPageUrl(),
                'prev_url' => $usersPaginated->previousPageUrl(),
            ],
            'users'       => $users,
        ]);
    }

    /**
     * Returns user by id.
     */
    public function show(Request $request, $id): JsonResponse
    {
        if (!ctype_digit((string)$id)) {
            return response()->json([
                'success' => false,
                'message' => 'The user with the requested id does not exist.',
                'fails'   => ['userId' => ['The user ID must be an integer.']]
            ], 400);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $userData = array_merge($user->toArray(), [
            'registration_timestamp' => $user->created_at->timestamp,
            'position'               => $user->position,
        ]);

        return response()->json([
            'success' => true,
            'user'    => $userData
        ]);
    }

    /**
     * Register a new user.
     */
    public function store(Request $request): JsonResponse
    {
        $registrationToken = $request->header('Token');

        if (!$registrationToken || !Cache::has('registration_token:' . $registrationToken)) {
            return response()->json([
                'success' => false,
                'message' => 'The token expired.'
            ], 401);
        }

        Cache::forget('registration_token:' . $registrationToken);

        try {
            $data = $request->validate([
                'name'        => 'required|string|min:2|max:60',
                'email'       => 'required|email|min:6|max:100|unique:users,email',
                'phone'       => ['required', 'regex:/^[\+]?380([0-9]{9})$/', 'unique:users,phone'],
                'position_id' => 'required|integer|min:1',
                'photo'       => 'required|image|mimes:jpeg,jpg|max:5120',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'fails'   => $e->errors(),
            ], 422);
        }

        $file     = $request->file('photo');
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
                    'success' => false,
                    'message' => 'Error processing image.'
                ], 500);
            }
        }

        try {
            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'position_id' => $data['position_id'],
                'photo'       => 'uploads/' . $filename,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User with this phone or email already exist'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'message' => 'New user successfully registered'
        ], 201);
    }

    /**
     * Returns the available user positions.
     */
    public function positions(Request $request): JsonResponse
    {
        $positions = [];
        $positionsHelper = User::getPositions();

        if (empty($positionsHelper)) {
            return response()->json([
                'success' => false,
                'message' => 'Positions not found'
            ], 404);
        }

        foreach ($positionsHelper as $id => $name) {
            $positions[] = ['id' => $id, 'name' => $name];
        }

        return response()->json([
            'success'   => true,
            'positions' => $positions,
        ]);
    }

    /**
     * Generates a new registration token.
     *
     * The token is valid for 40 minutes and can be used for only one registration request.
     */
    public function token(Request $request): JsonResponse
    {
        $token = Str::random(60);
        Cache::put('registration_token:' . $token, true, now()->addMinutes(40));

        return response()->json([
            'success' => true,
            'token'   => $token,
        ]);
    }
}
