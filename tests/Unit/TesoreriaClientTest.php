<?php

namespace Tests\Unit;

use App\Integrations\TesoreriaClient;
use App\Models\Expediente;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TesoreriaClientTest extends TestCase
{
    public function test_it_updates_an_existing_contributor_with_a_bearer_token(): void
    {
        config()->set('services.tesoreria.url', 'https://tesoreria.example.test/api');
        config()->set('services.tesoreria.email', 'integracion@example.test');
        config()->set('services.tesoreria.password', 'secreto');
        config()->set('services.tesoreria.ca_bundle', null);

        Http::fake([
            'tesoreria.example.test/api/auth/login' => Http::response(['access_token' => 'token-prueba']),
            'tesoreria.example.test/api/contribuyentes/886572' => Http::response(['success' => true]),
        ]);

        $expediente = new Expediente([
            'id_externo' => '886572',
            'tipo_persona' => 'FISICA',
            'curp_consulta' => 'SATF980320HDFNRR02',
            'email_contacto' => 'francisco@example.test',
            'telefono_contacto' => '5534073167',
            'nombres_solicitante' => 'Francisco Javier',
            'apellido_paterno_solicitante' => 'Sánchez',
            'apellido_materno_solicitante' => 'Tirado',
            'calle' => 'Pichirilo',
            'numero_exterior' => '327',
            'colonia' => 'Benito Juárez',
            'codigo_postal' => '57000',
            'regimen_fiscal_externo_id' => 616,
        ]);

        app(TesoreriaClient::class)->actualizarContribuyente($expediente);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'https://tesoreria.example.test/api/contribuyentes/886572'
                && $request->hasHeader('Authorization', 'Bearer token-prueba')
                && $request['curp'] === 'SATF980320HDFNRR02'
                && $request['municipio_id'] === 715
                && $request['regimen_fiscal_id'] === 616;
        });
    }
}
