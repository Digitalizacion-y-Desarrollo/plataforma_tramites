<?php

namespace Tests\Feature;

use Tests\TestCase;

class EmailNotificationLayoutTest extends TestCase
{
    public function test_it_renders_the_reusable_notification_email_layout(): void
    {
        $html = view('emails.layouts.notificacion', [
            'title' => 'Tu trámite está en revisión',
            'recipientName' => 'Ana Torres',
            'intro' => 'Consulta las actualizaciones disponibles en la plataforma.',
            'tramiteName' => 'Constancia de regularización',
            'folio' => 'NEZA-2026-00123',
            'applicantName' => 'Ana Torres',
            'statusLabel' => 'En revisión',
            'steps' => [
                ['title' => 'Ingresa a la plataforma', 'description' => 'Consulta el detalle de tu solicitud.'],
            ],
            'notice' => 'El estado en revisión no equivale a una aprobación.',
            'actionUrl' => 'https://example.test/consulta',
            'actionLabel' => 'Consultar mi trámite',
        ])->render();

        $this->assertStringContainsString('Tu trámite está en revisión', $html);
        $this->assertStringContainsString('NEZA-2026-00123', $html);
        $this->assertStringContainsString('Consultar mi trámite', $html);
    }
}
