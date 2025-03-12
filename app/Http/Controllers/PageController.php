<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $users = User::paginate(6);
        $auth_token = bin2hex(random_bytes(16));

        return view('index', compact('users', 'auth_token'));
    }
}
