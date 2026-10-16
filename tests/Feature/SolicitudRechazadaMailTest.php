<?php

namespace Tests\Feature;

use App\Mail\SolicitudRechazadaMail;
use App\Models\Solicitud;
use App\Models\Tramite;
use Tests\TestCase;

class SolicitudRechazadaMailTest extends TestCase
{
    public function test_it_contains_the_rejection_reason_and_consultation_guidance(): void
    {
        $solicitud = new Solicitud([
            'folio' => 'SOL-20261014-ABC123',
            'email_contacto' => 'ana@example.test',
        ]);
        $solicitud->setRelation('tramite', new Tramite(['nombre' => 'Constancia de regularización']));

        $mail = new SolicitudRechazadaMail($solicitud, 'Falta adjuntar el documento requerido.');
        $html = $mail->render();

        $this->assertSame('Solicitud rechazada · Folio SOL-20261014-ABC123', $mail->envelope()->subject);
        $this->assertStringContainsString('SOL-20261014-ABC123', $html);
        $this->assertStringContainsString('Constancia de regularización', $html);
        $this->assertStringContainsString('Falta adjuntar el documento requerido.', $html);
        $this->assertStringContainsString('Consultar mi trámite', $html);
    }
}
