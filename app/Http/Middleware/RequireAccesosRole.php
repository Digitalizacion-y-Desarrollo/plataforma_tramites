<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAccesosRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $rolesFromSession = $request->session()->get('accesos.roles', $request->session()->get('accesos.role', []));
        $currentRoles = collect(is_array($rolesFromSession) ? $rolesFromSession : [$rolesFromSession])
            ->flatten()
            ->map(fn ($role) => mb_strtolower(trim((string) $role)))
            ->all();

        $allowed = collect($roles)->map(fn ($role) => mb_strtolower(trim($role)));

        abort_unless($allowed->intersect($currentRoles)->isNotEmpty(), 403, 'No tienes el rol necesario para realizar esta acción.');

        return $next($request);
    }
}
