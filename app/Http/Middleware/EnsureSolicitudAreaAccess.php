<?php

namespace App\Http\Middleware;

use App\Models\AreaOperativa;
use App\Models\Solicitud;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSolicitudAreaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $roles = collect($request->session()->get('accesos.roles', $request->session()->get('accesos.role', [])))
            ->flatten()->map(fn ($role) => mb_strtolower(trim((string) $role)));

        if ($roles->contains('superuser')) {
            return $next($request);
        }

        $solicitud = $request->route('solicitud');
        abort_unless($solicitud instanceof Solicitud, 404);

        $areaActiva = AreaOperativa::query()
            ->where('accesos_departamento_id', $solicitud->departamento_accesos_id_snapshot)
            ->where('activa', true);

        if ($roles->contains('superadmin')) {
            abort_unless($areaActiva->exists(), 403, 'La solicitud no pertenece a un área operativa habilitada.');

            return $next($request);
        }

        abort_unless(
            $roles->contains('colaborador')
                && $areaActiva->exists()
                && (string) $request->session()->get('accesos.departamento_accesos_id') === (string) $solicitud->departamento_accesos_id_snapshot,
            403,
            'No tienes acceso a esta solicitud.'
        );

        return $next($request);
    }
}
