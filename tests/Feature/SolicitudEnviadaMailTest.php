<?php

namespace Tests\Feature;

use App\Mail\SolicitudEnviadaMail;
use App\Models\Solicitud;
use App\Models\Tramite;
use Tests\TestCase;

class SolicitudEnviadaMailTest extends TestCase
{
    public function test_it_contains_the_submission_details_and_consultation_guidance(): void
    {
        $solicitud = new Solicitud([
            'folio' => 'SOL-20261014-ABC123',
            'email_contacto' => 'ana@example.test',
        ]);
        $solicitud->setRelation('tramite', new Tramite(['nombre' => 'Constancia de regularización']));

        $mail = new SolicitudEnviadaMail($solicitud);
        $html = $mail->render();

        $this->assertSame('Solicitud enviada correctamente · Folio SOL-20261014-ABC123', $mail->envelope()->subject);
        $this->assertStringContainsString('SOL-20261014-ABC123', $html);
        $this->assertStringContainsString('Constancia de regularización', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Cualquier actualización, requerimiento u observación', $html);
        $this->assertStringContainsString('Consultar estado de mi trámite', $html);
        $this->assertStringContainsString(route('portal.publico').'#consulta', $html);
    }
}
