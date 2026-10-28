<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;

class AccesosClient
{
    public function iniciarSesion(string $email, string $password): array
    {
        $respuesta = $this->peticion()->post(config('services.accesos.login_url'), ['email' => $email, 'password' => $password, 'system_key' => config('services.accesos.system_key')])->throw();

        return $respuesta->json('data', []);
    }

    public function perfil(string $token): array
    {
        $respuesta = $this->peticion()->withToken($token)->get(config('services.accesos.profile_url'), ['system_key' => config('services.accesos.system_key')])->throw();

        return $respuesta->json('data', []);
    }

    public function cerrarSesion(string $token): void
    {
        $this->peticion()->withToken($token)->post(config('services.accesos.logout_url'))->throw();
    }

    public function departamentosActivos(): array
    {
        $pagina = 1;
        $ultimaPagina = 1;
        $departamentos = [];

        do {
            $peticion = $this->peticion();

            $respuesta = $peticion
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

    private function peticion()
    {
        $peticion = Http::acceptJson()->timeout(config('services.accesos.timeout'));
        if ($caBundle = config('services.accesos.ca_bundle')) {
            $peticion = $peticion->withOptions(['verify' => $caBundle]);
        }

        return $peticion;
    }
}
