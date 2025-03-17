<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 *
 */
class PageController extends Controller
{
    /**
     * @param Request $request
     * @return Application|Factory|View|\Illuminate\Foundation\Application
     * @throws \Random\RandomException
     */
    public function index(Request $request)
    {
        $users = User::paginate(6);
        $auth_token = bin2hex(random_bytes(16));

        return view('index', compact('users', 'auth_token'));
    }
}
