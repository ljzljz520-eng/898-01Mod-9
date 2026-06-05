<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // API requests should return JSON, not redirect
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        // For web routes, redirect to login (if route exists)
        try {
            return route('login');
        } catch (\Exception $e) {
            return null;
        }
    }
}
