<?php

namespace Tests\Feature;

use App\Mail\TokenConsultaMail;
use Tests\TestCase;

class TokenConsultaMailTest extends TestCase
{
    public function test_it_highlights_the_code_without_exposing_it_in_the_preheader(): void
    {
        $correo = new TokenConsultaMail('482731');
        $contenido = $correo->content();
        $html = view($contenido->view, $contenido->with)->render();

        $this->assertSame('Tu código para consultar tus trámites', $correo->envelope()->subject);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('>482731</h1>', $html);
        $this->assertStringContainsString('vence en 15 minutos', $html);
        $this->assertStringContainsString('Ir a la consulta de trámites', $html);
        $this->assertStringContainsString(route('portal.publico').'#consulta', $html);
        $this->assertStringContainsString('Si no solicitaste el acceso', $html);
        $this->assertStringNotContainsString('482731', $contenido->with['preheader']);
    }
}
