<?php

namespace App\Http\Middleware;

use App\Models\AreaOperativa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAreaOperativaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $departamentoId = (string) $request->route('departamento');
        $area = AreaOperativa::query()
            ->where('accesos_departamento_id', $departamentoId)
            ->where('activa', true)
            ->firstOrFail();

        $roles = collect($request->session()->get('accesos.roles', $request->session()->get('accesos.role', [])))
            ->flatten()
            ->map(fn ($role) => mb_strtolower(trim((string) $role)));

        if (! $roles->contains('superuser') && ! $roles->contains('superadmin')) {
            abort_unless(
                $roles->contains('colaborador')
                    && (string) $request->session()->get('accesos.departamento_accesos_id') === $departamentoId,
                403,
                'No tienes acceso a esta área operativa.'
            );
        }

        $request->attributes->set('area_operativa', $area);

        return $next($request);
    }
}
