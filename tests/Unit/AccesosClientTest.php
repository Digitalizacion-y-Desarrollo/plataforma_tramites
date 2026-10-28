<?php

namespace Tests\Unit;

use App\Integrations\AccesosClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccesosClientTest extends TestCase
{
    public function test_it_reads_all_pages_and_keeps_only_active_departments(): void
    {
        Cache::forget('accesos.departamentos_activos');
        config()->set('services.accesos.departamentos_url', 'https://accesos.example.test/api/departamentos');
        config()->set('services.accesos.ca_bundle', null);

        Http::fake([
            'accesos.example.test/api/departamentos?page=1' => Http::response([
                'data' => [
                    ['id' => 1, 'nombre' => 'Desarrollo Urbano', 'activo' => true, 'parent_id' => null],
                    ['id' => 2, 'nombre' => 'Inactivo', 'activo' => false, 'parent_id' => null],
                ],
                'meta' => ['last_page' => 2],
            ]),
            'accesos.example.test/api/departamentos?page=2' => Http::response([
                'data' => [['id' => 3, 'nombre' => 'Licencias', 'activo' => true, 'parent_id' => 1]],
                'meta' => ['last_page' => 2],
            ]),
        ]);

        $departamentos = app(AccesosClient::class)->departamentosActivos();

        $this->assertCount(2, $departamentos);
        $this->assertSame('Desarrollo Urbano', $departamentos[0]['nombre']);
        $this->assertSame('1', $departamentos[1]['parent_id']);
        Http::assertSentCount(2);
    }

    public function test_it_caches_active_departments_for_five_minutes(): void
    {
        Cache::forget('accesos.departamentos_activos');
        config()->set('services.accesos.departamentos_url', 'https://accesos.example.test/api/departamentos');
        config()->set('services.accesos.ca_bundle', null);

        Http::fake([
            'accesos.example.test/api/departamentos?page=1' => Http::response([
                'data' => [['id' => 1, 'nombre' => 'Desarrollo Urbano', 'activo' => true, 'parent_id' => null]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $client = app(AccesosClient::class);
        $client->departamentosActivos();
        $client->departamentosActivos();

        Http::assertSentCount(1);
    }

    public function test_it_authenticates_reads_profile_and_revokes_the_token(): void
    {
        config()->set('services.accesos.login_url', 'https://accesos.example.test/api/auth/login');
        config()->set('services.accesos.profile_url', 'https://accesos.example.test/api/auth/me');
        config()->set('services.accesos.logout_url', 'https://accesos.example.test/api/auth/logout');
        config()->set('services.accesos.system_key', 'system-key');
        config()->set('services.accesos.ca_bundle', null);

        Http::fake([
            'accesos.example.test/api/auth/login' => Http::response(['data' => ['access_token' => 'token-prueba']]),
            'accesos.example.test/api/auth/me*' => Http::response(['data' => ['user' => ['id' => 1], 'roles' => ['superuser']]]),
            'accesos.example.test/api/auth/logout' => Http::response([], 204),
        ]);

        $client = app(AccesosClient::class);
        $login = $client->iniciarSesion('persona@example.test', 'secreto');
        $perfil = $client->perfil($login['access_token']);
        $client->cerrarSesion($login['access_token']);

        $this->assertSame('token-prueba', $login['access_token']);
        $this->assertSame(['superuser'], $perfil['roles']);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://accesos.example.test/api/auth/login'
                && $request['system_key'] === 'system-key'
                && $request['email'] === 'persona@example.test';
        });
        Http::assertSent(fn ($request): bool => $request->url() === 'https://accesos.example.test/api/auth/me?system_key=system-key' && $request->hasHeader('Authorization', 'Bearer token-prueba'));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://accesos.example.test/api/auth/logout' && $request->hasHeader('Authorization', 'Bearer token-prueba'));
    }
}
