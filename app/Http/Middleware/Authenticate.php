<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        $result = null;

        if (!$request->expectsJson()) {
            $result = response()->json([
                'message' => 'You are not authorized. First you need to get a token via api/users/login',
            ], 401);
        }

        return $result;
    }
}
