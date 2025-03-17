<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Front user controller.
 */
class UserController extends Controller
{
    /**
     * Display a paginated list of users along with an auth token.
     *
     * @return View
     */
    public function index(): View
    {
        $users = User::paginate(6);
        $auth_token = bin2hex(random_bytes(16));

        return view('index', compact('users', 'auth_token'));
    }

    /**
     * Store a newly created user.
     * Supports API calls (with Authorization header) and free front page submissions.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'image'       => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'phone'       => ['required', 'regex:/^(\+?380\d{9})$/'],
            'position_id' => 'required|integer',
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
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Error processing image.',
                        'status'  => 'error'
                    ], 500);
                }
                return redirect()->route('users.index')->with('error', 'Error processing image.');
            }
        }

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'photo'         => 'uploads/' . $filename,
            'position_id'   => $data['position_id'],
            'phone' => $data['phone'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($request->wantsJson()) {
            return response()->json([
                'message'       => 'User added successfully!',
                'user'          => $user,
                'status'        => 'success',
                'access_token'  => $token,
            ], 201);
        }

        return redirect()->route('users.index')->with('success', 'User added successfully!');
    }

    /**
     * Endpoint to load more users.
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function loadMore(Request $request)
    {
        if ($request->ajax()) {
            $page = $request->query('page', 2);
            $users = User::paginate(6, ['*'], 'page', $page);

            $html = view('partials.user_grid_items', compact('users'))->render();

            return response()->json([
                'html'          => $html,
                'next_page_url' => $users->nextPageUrl(),
                'current_page'  => $users->currentPage(),
            ]);
        }

        abort(404);
    }
}
