<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicoViewsTest extends TestCase
{
    public function test_public_procedure_views_are_connected(): void
    {
        $this->withoutVite();

        $this->get(route('portal.tramites.index'))
            ->assertOk()
            ->assertSee('Todos los trámites')
            ->assertSee(route('portal.tramites.predial'));

        $this->get(route('portal.tramites.predial'))
            ->assertOk()
            ->assertSee('Pago de predial')
            ->assertSee(route('portal.tramites.predial.iniciar'));

        $this->get(route('portal.tramites.predial.iniciar'))
            ->assertOk()
            ->assertSee('Datos del inmueble')
            ->assertSee(route('portal.tramites.predial'));
    }
}
