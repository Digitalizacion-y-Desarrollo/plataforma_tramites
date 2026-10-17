<?php

namespace Tests\Unit;

use App\Integrations\AccesosClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccesosClientTest extends TestCase
{
    public function test_it_reads_all_pages_and_keeps_only_active_departments(): void
    {
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
}
