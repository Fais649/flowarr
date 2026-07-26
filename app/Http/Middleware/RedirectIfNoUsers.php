<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNoUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! User::exists() && ! $request->is('register*', 'login*', 'forgot-password*', 'reset-password*', '.well-known/*')) {
            return redirect()->to('/register');
        }

        return $next($request);
    }
}
