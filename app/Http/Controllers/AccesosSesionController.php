<?php

namespace App\Http\Controllers;

use App\Integrations\AccesosClient;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AccesosSesionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return session()->has('accesos.id') ? redirect()->route('admin.dashboard') : view('accesos.login');
    }

    public function store(Request $request, AccesosClient $accesos): RedirectResponse
    {
        $datos = $request->validate(['email' => ['required', 'email', 'max:191'], 'password' => ['required', 'string', 'max:255']]);

        try {
            $login = $accesos->iniciarSesion($datos['email'], $datos['password']);
            $token = $login['access_token'] ?? null;
            abort_unless(is_string($token) && $token !== '', 502, 'Accesos no devolvió un token de sesión válido.');
            $perfil = $accesos->perfil($token);
        } catch (\Throwable $exception) {
            Log::warning('No fue posible iniciar sesión con Accesos.', ['exception' => $exception->getMessage()]);

            return back()->withInput($request->except('password'))->withErrors(['email' => 'No fue posible validar tus credenciales con Accesos.']);
        }

        $usuario = $perfil['user'] ?? $login['user'] ?? [];
        $roles = collect($perfil['roles'] ?? $login['roles'] ?? [])
            ->filter(fn ($rol): bool => is_string($rol))
            ->values()
            ->all();
        $rolesPermitidos = collect($roles)->map(fn (string $rol) => mb_strtolower(trim($rol)))->intersect(['colaborador', 'superadmin', 'superuser']);
        $departamento = $usuario['departamento']['departamento_padre'] ?? $usuario['departamento']['departamento_hijo'] ?? null;

        if (blank($usuario['id'] ?? null) || $rolesPermitidos->isEmpty() || ($rolesPermitidos->contains('colaborador') && blank($departamento['id'] ?? null))) {
            return back()->withInput($request->except('password'))->withErrors(['email' => 'Tu cuenta no tiene acceso configurado para este portal.']);
        }

        $request->session()->regenerate();
        $request->session()->put('accesos', [
            'id' => (string) $usuario['id'],
            'nombre' => $usuario['name'] ?? trim(($usuario['nombre'] ?? '').' '.($usuario['apellido_paterno'] ?? '')),
            'roles' => $roles,
            'departamento_accesos_id' => isset($departamento['id']) ? (string) $departamento['id'] : null,
            'departamento' => $departamento['nombre'] ?? null,
            'token' => $token,
        ]);
        Auditoria::registrar($request, 'ACCESO_INICIADO', 'SESION', (string) $usuario['id'], null, ['email' => $datos['email']]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, AccesosClient $accesos): RedirectResponse
    {
        if ($token = $request->session()->get('accesos.token')) {
            try {
                $accesos->cerrarSesion($token);
            } catch (\Throwable $exception) {
                Log::warning('No fue posible cerrar la sesión central de Accesos.', ['exception' => $exception->getMessage()]);
            }
        }
        Auditoria::registrar($request, 'ACCESO_CERRADO', 'SESION', filled($request->session()->get('accesos.id')) ? (string) $request->session()->get('accesos.id') : null, ['nombre' => $request->session()->get('accesos.nombre')], null);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accesos.login')->with('success', 'Sesión cerrada correctamente.');
    }
}
