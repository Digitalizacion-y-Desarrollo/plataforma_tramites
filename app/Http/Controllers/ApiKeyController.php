<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(): View
    {
        $apiKeys = ApiKey::query()->orderByDesc('created_at')->paginate(20);

        return view('admin.api-keys.index', compact('apiKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'expira_en' => ['nullable', 'date', 'after:today'],
        ]);

        $expiraEn = filled($datos['expira_en'] ?? null)
            ? Carbon::parse($datos['expira_en'])->endOfDay()->toDateTimeString()
            : null;

        [$apiKey, $token] = DB::transaction(function () use ($datos, $expiraEn, $request): array {
            [$apiKey, $token] = ApiKey::generar($datos['nombre'], $expiraEn);
            Auditoria::registrar($request, 'API_KEY_CREADA', 'API_KEY', $apiKey->id, null, [
                'nombre' => $apiKey->nombre,
                'prefijo' => $apiKey->prefijo,
                'expira_en' => $apiKey->expira_en?->toDateTimeString(),
            ]);

            return [$apiKey, $token];
        });

        return redirect()->route('api-keys.index')
            ->with('api_key_generada', $token)
            ->with('success', 'La API Key se creó correctamente. Cópiala ahora: no volverá a mostrarse.');
    }

    public function revocar(Request $request, ApiKey $apiKey): RedirectResponse
    {
        if (! $apiKey->activo) {
            return redirect()->route('api-keys.index')->with('success', 'La API Key ya estaba revocada.');
        }

        DB::transaction(function () use ($apiKey, $request): void {
            $apiKey->update(['activo' => false]);
            Auditoria::registrar($request, 'API_KEY_REVOCADA', 'API_KEY', $apiKey->id, [
                'activo' => true,
            ], [
                'activo' => false,
                'nombre' => $apiKey->nombre,
                'prefijo' => $apiKey->prefijo,
            ]);
        });

        return redirect()->route('api-keys.index')->with('success', 'La API Key fue revocada.');
    }
}
