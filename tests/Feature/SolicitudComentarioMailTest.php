<?php

namespace Tests\Feature;

use App\Mail\SolicitudComentarioMail;
use App\Models\EstadoSolicitud;
use App\Models\Solicitud;
use App\Models\Tramite;
use Tests\TestCase;

class SolicitudComentarioMailTest extends TestCase
{
    public function test_it_emphasizes_the_staff_message_in_a_dedicated_callout(): void
    {
        $solicitud = new Solicitud([
            'folio' => 'SOL-20261023-ABC123',
        ]);
        $solicitud->setRelation('tramite', new Tramite(['nombre' => 'Constancia de regularización']));
        $solicitud->setRelation('estado', new EstadoSolicitud(['nombre' => 'En revisión']));

        $mail = new SolicitudComentarioMail($solicitud, "Comparte la aclaración solicitada.\nGracias.");
        $html = $mail->render();

        $this->assertSame('Actualización de tu trámite · Folio SOL-20261023-ABC123', $mail->envelope()->subject);
        $this->assertStringContainsString('Mensaje del área responsable', $html);
        $this->assertStringContainsString('Comparte la aclaración solicitada.', $html);
        $this->assertStringContainsString('border-left:5px solid #601633', $html);
        $this->assertStringContainsString('Consultar mi trámite', $html);
    }
}
