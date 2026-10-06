<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicoViewsTest extends TestCase
{
    public function test_public_procedures_index_is_available(): void
    {
        $this->withoutVite();

        $this->get(route('portal.tramites.index'))
            ->assertOk()
            ->assertSee('Todos los trámites')
            ->assertDontSee('Pago de predial');
    }
}
