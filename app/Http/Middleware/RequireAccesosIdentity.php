<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAccesosIdentity
{
    public function handle(Request $request, Closure $next): Response
    {
        $identity = $request->session()->get('accesos');

        if (! is_array($identity) || blank($identity['id'] ?? null)) {
            return redirect()->guest(route('accesos.login'));
        }

        return $next($request);
    }
}
