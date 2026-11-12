<?php

namespace Tests\Feature;

use App\Integrations\TesoreriaClient;
use App\Models\Expediente;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TesoreriaClientTest extends TestCase
{
    public function test_inicia_sesion_y_registra_un_contribuyente_fisico(): void
    {
        config([
            'services.tesoreria.url' => 'https://tesoreria.test/api',
            'services.tesoreria.email' => 'integracion@municipio.gob.mx',
            'services.tesoreria.password' => 'secreto',
        ]);
        Http::fake([
            'https://tesoreria.test/api/auth/login' => Http::response([
                'access_token' => 'token-de-tesoreria',
                'refresh_token' => 'refresh-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://tesoreria.test/api/contribuyentes' => Http::response([
                'success' => true,
                'message' => 'Contribuyente creado.',
                'data' => ['id' => 125],
            ], 201),
        ]);
        $expediente = new Expediente([
            'email_contacto' => 'persona@ejemplo.mx',
            'nombres_solicitante' => 'Ana',
            'apellido_paterno_solicitante' => 'Pérez',
            'apellido_materno_solicitante' => 'López',
            'calle' => 'Reforma',
            'numero_exterior' => '10',
            'colonia' => 'Centro',
            'codigo_postal' => '57000',
        ]);

        $id = app(TesoreriaClient::class)->registrarContribuyente($expediente);

        $this->assertSame('125', $id);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://tesoreria.test/api/contribuyentes'
                && $request->hasHeader('Authorization', 'Bearer token-de-tesoreria')
                && $request['municipio_id'] === 715
                && $request['regimen_fiscal_id'] === 1;
        });
    }
}
