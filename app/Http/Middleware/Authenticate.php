<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle unauthenticated requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // If the request does not expect JSON, return null to avoid redirection
        if (! $request->expectsJson()) {
            abort(response()->json(['message' => 'Unauthorized'], 401));
        }
    }
}
