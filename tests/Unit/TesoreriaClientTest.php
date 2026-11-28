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

    public function test_it_reads_the_procedures_catalog_with_a_bearer_token(): void
    {
        config()->set('services.tesoreria.url', 'https://tesoreria.example.test/api');
        config()->set('services.tesoreria.email', 'integracion@example.test');
        config()->set('services.tesoreria.password', 'secreto');
        config()->set('services.tesoreria.ca_bundle', null);

        Http::fake([
            'tesoreria.example.test/api/auth/login' => Http::response(['access_token' => 'token-prueba']),
            'tesoreria.example.test/api/catalogo/tramites' => Http::response(['data' => [[
                'id_area' => 46,
                'nombre' => 'Secretaría del Ayuntamiento',
                'tramites' => [['id_tramite' => 715, 'nombre' => 'Constancia de identidad']],
            ]]]),
        ]);

        $tramites = app(TesoreriaClient::class)->tramitesCatalogo();

        $this->assertSame([[
            'id' => 715,
            'nombre' => 'Constancia de identidad',
            'area_nombre' => 'Secretaría del Ayuntamiento',
        ]], $tramites);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://tesoreria.example.test/api/catalogo/tramites'
            && $request->hasHeader('Authorization', 'Bearer token-prueba'));
    }

    public function test_it_gets_a_diverse_payment_settlement_with_a_bearer_token(): void
    {
        config()->set('services.tesoreria.url', 'https://tesoreria.example.test/api');
        config()->set('services.tesoreria.email', 'integracion@example.test');
        config()->set('services.tesoreria.password', 'secreto');
        config()->set('services.tesoreria.ca_bundle', null);

        Http::fake([
            'tesoreria.example.test/api/auth/login' => Http::response(['access_token' => 'token-prueba']),
            'tesoreria.example.test/api/liquidador/diversos/1759720' => Http::response(['data' => ['referencia' => 'TES-123']]),
        ]);

        $liquidacion = app(TesoreriaClient::class)->liquidarDiverso(1759720);

        $this->assertSame(['data' => ['referencia' => 'TES-123']], $liquidacion);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://tesoreria.example.test/api/liquidador/diversos/1759720'
            && $request->hasHeader('Authorization', 'Bearer token-prueba'));
    }

    public function test_it_gets_existing_contributor_settlements_with_a_bearer_token(): void
    {
        config()->set('services.tesoreria.url', 'https://tesoreria.example.test/api');
        config()->set('services.tesoreria.email', 'integracion@example.test');
        config()->set('services.tesoreria.password', 'secreto');
        config()->set('services.tesoreria.ca_bundle', null);

        Http::fake([
            'tesoreria.example.test/api/auth/login' => Http::response(['access_token' => 'token-prueba']),
            'tesoreria.example.test/api/contribuyentes/886572/liquidaciones?page=1&per_page=15' => Http::response(['data' => [['linea_captura' => '12345678901234567890', 'pagado' => false]]]),
        ]);

        $liquidaciones = app(TesoreriaClient::class)->liquidacionesContribuyente('886572');

        $this->assertSame('12345678901234567890', $liquidaciones[0]['linea_captura']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://tesoreria.example.test/api/contribuyentes/886572/liquidaciones?page=1&per_page=15'
            && $request->hasHeader('Authorization', 'Bearer token-prueba'));
    }

    public function test_it_gets_payment_agreements_with_a_bearer_token(): void
    {
        config()->set('services.tesoreria.url', 'https://tesoreria.example.test/api');
        config()->set('services.tesoreria.email', 'integracion@example.test');
        config()->set('services.tesoreria.password', 'secreto');
        config()->set('services.tesoreria.ca_bundle', null);

        Http::fake([
            'tesoreria.example.test/api/auth/login' => Http::response(['access_token' => 'token-prueba']),
            'tesoreria.example.test/api/convenios' => Http::response(['data' => [['alt_convenio' => 'bancomer', 'numero_convenio' => '1534920']]]),
        ]);

        $convenios = app(TesoreriaClient::class)->convenios();

        $this->assertSame('1534920', $convenios[0]['numero_convenio']);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://tesoreria.example.test/api/convenios'
            && $request->hasHeader('Authorization', 'Bearer token-prueba'));
    }
}
