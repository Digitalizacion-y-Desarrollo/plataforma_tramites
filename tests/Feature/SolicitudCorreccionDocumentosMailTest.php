<?php

namespace Tests\Feature;

use App\Mail\SolicitudCorreccionDocumentosMail;
use App\Models\Solicitud;
use App\Models\Tramite;
use Tests\TestCase;

class SolicitudCorreccionDocumentosMailTest extends TestCase
{
    public function test_it_contains_the_correction_access_and_reasons(): void
    {
        $solicitud = new Solicitud([
            'folio' => 'SOL-20261015-ABC123',
            'email_contacto' => 'ana@example.test',
        ]);
        $solicitud->setRelation('tramite', new Tramite(['nombre' => 'Constancia de regularización']));

        $mail = new SolicitudCorreccionDocumentosMail(
            $solicitud,
            [['nombre' => 'Identificación oficial', 'motivo' => 'El documento no es legible.']],
            'https://example.test/correcciones/token-seguro',
            'ClaveTemporal123',
        );
        $html = $mail->render();

        $this->assertSame('Corrección de documentos requerida · Folio SOL-20261015-ABC123', $mail->envelope()->subject);
        $this->assertStringContainsString('Identificación oficial', $html);
        $this->assertStringContainsString('El documento no es legible.', $html);
        $this->assertStringContainsString('ClaveTemporal123', $html);
        $this->assertStringContainsString('https://example.test/correcciones/token-seguro', $html);
    }
}
