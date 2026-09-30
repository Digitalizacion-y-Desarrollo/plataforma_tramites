<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;

class AccesosClient
{
    public function departamentosActivos(): array
    {
        $pagina = 1;
        $ultimaPagina = 1;
        $departamentos = [];

        do {
            $respuesta = Http::acceptJson()
                ->timeout(config('services.accesos.timeout'))
                ->get(config('services.accesos.departamentos_url'), ['page' => $pagina])
                ->throw();

            $departamentos = [...$departamentos, ...$respuesta->json('data', [])];
            $ultimaPagina = (int) $respuesta->json('meta.last_page', 1);
            $pagina++;
        } while ($pagina <= $ultimaPagina);

        return collect($departamentos)
            ->filter(fn (array $departamento) => (bool) ($departamento['activo'] ?? false))
            ->map(fn (array $departamento) => [
                'id' => (string) $departamento['id'],
                'nombre' => $departamento['nombre'],
                'siglas' => $departamento['siglas'] ?? null,
                'parent_id' => isset($departamento['parent_id']) ? (string) $departamento['parent_id'] : null,
            ])
            ->sortBy('nombre')
            ->values()
            ->all();
    }
}
