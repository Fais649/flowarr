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
        if ($request->isMethod('GET') && ! $request->is('register*', 'login*', 'forgot-password*', 'reset-password*', '.well-known/*', 'health', 'up')) {
            try {
                if (! User::exists()) {
                    return redirect()->to('/register');
                }
            } catch (\Throwable) {
                // DB unavailable — allow request through
            }
        }

        return $next($request);
    }
}
