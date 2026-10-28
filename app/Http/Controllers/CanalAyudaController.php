<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\CanalAyuda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CanalAyudaController extends Controller
{
    public function edit(): View
    {
        $canalesAyuda = CanalAyuda::query()->first() ?? new CanalAyuda(['preguntas_frecuentes' => [], 'redes_sociales' => []]);

        return view('admin.canales-ayuda.edit', compact('canalesAyuda'));
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $canalesAyuda = CanalAyuda::query()->firstOrNew();
        $anteriores = $canalesAyuda->exists ? $canalesAyuda->only(array_keys($datos)) : null;

        DB::transaction(function () use ($canalesAyuda, $datos, $anteriores, $request): void {
            $canalesAyuda->fill($datos);
            $canalesAyuda->save();

            Auditoria::registrar($request, 'CANALES_AYUDA_ACTUALIZADOS', 'canales_ayuda', $canalesAyuda->id, $anteriores, $canalesAyuda->only(array_keys($datos)));
        });

        return redirect()->route('canales-ayuda.edit')->with('success', 'Los canales de ayuda se actualizaron correctamente.');
    }

    private function validar(Request $request): array
    {
        $validador = validator($request->all(), [
            'preguntas' => ['nullable', 'array', 'max:20'],
            'preguntas.*.pregunta' => ['nullable', 'string', 'max:500'],
            'preguntas.*.respuesta' => ['nullable', 'string', 'max:2000'],
            'horario_atencion' => ['nullable', 'string', 'max:500'],
            'telefono_contacto' => ['nullable', 'string', 'max:50'],
            'email_contacto' => ['nullable', 'email', 'max:191'],
            'redes' => ['nullable', 'array', 'max:10'],
            'redes.*.nombre' => ['nullable', Rule::in(['Facebook', 'Instagram', 'TikTok', 'X', 'Threads'])],
            'redes.*.url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        $validador->after(function ($validador) use ($request): void {
            foreach ($request->input('preguntas', []) as $indice => $pregunta) {
                if (filled($pregunta['pregunta'] ?? null) xor filled($pregunta['respuesta'] ?? null)) {
                    $validador->errors()->add('preguntas.'.$indice.'.respuesta', 'Captura la pregunta y la respuesta.');
                }
            }

            foreach ($request->input('redes', []) as $indice => $red) {
                if (filled($red['nombre'] ?? null) xor filled($red['url'] ?? null)) {
                    $validador->errors()->add('redes.'.$indice.'.url', 'Captura el nombre y la URL de la red social.');
                }
            }

            $redesSeleccionadas = collect($request->input('redes', []))->pluck('nombre')->filter();
            if ($redesSeleccionadas->count() !== $redesSeleccionadas->unique()->count()) {
                $validador->errors()->add('redes', 'No puedes registrar la misma red social más de una vez.');
            }
        });

        $datos = $validador->validate();

        return [
            'preguntas_frecuentes' => collect($datos['preguntas'] ?? [])->filter(fn (array $pregunta): bool => filled($pregunta['pregunta'] ?? null) && filled($pregunta['respuesta'] ?? null))->map(fn (array $pregunta): array => ['pregunta' => trim($pregunta['pregunta']), 'respuesta' => trim($pregunta['respuesta'])])->values()->all(),
            'horario_atencion' => filled($datos['horario_atencion'] ?? null) ? trim($datos['horario_atencion']) : null,
            'telefono_contacto' => filled($datos['telefono_contacto'] ?? null) ? trim($datos['telefono_contacto']) : null,
            'email_contacto' => filled($datos['email_contacto'] ?? null) ? Str::lower(trim($datos['email_contacto'])) : null,
            'redes_sociales' => collect($datos['redes'] ?? [])->filter(fn (array $red): bool => filled($red['nombre'] ?? null) && filled($red['url'] ?? null))->map(fn (array $red): array => ['nombre' => trim($red['nombre']), 'url' => trim($red['url'])])->values()->all(),
        ];
    }
}
