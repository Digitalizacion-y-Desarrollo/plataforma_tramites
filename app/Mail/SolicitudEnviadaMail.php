<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudEnviadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Solicitud $solicitud) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud enviada correctamente · Folio '.$this->solicitud->folio,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.layouts.notificacion',
            with: [
                'subject' => 'Solicitud enviada correctamente · Folio '.$this->solicitud->folio,
                'logoPath' => public_path('images/logo/logo-sin-fondo.png'),
                'preheader' => 'Recibimos tu solicitud. Conserva tu folio '.$this->solicitud->folio.'.',
                'kicker' => 'SOLICITUD ENVIADA CORRECTAMENTE',
                'title' => 'Recibimos tu solicitud',
                'intro' => 'Tu solicitud fue registrada correctamente. Conserva tu folio para dar seguimiento y mantente atenta o atento a las actualizaciones que te enviaremos a este correo.',
                'tramiteName' => $this->solicitud->tramite->nombre,
                'folio' => $this->solicitud->folio,
                'statusLabel' => 'Enviada',
                'statusTone' => 'info',
                'stepsTitle' => '¿Qué sigue?',
                'steps' => [
                    ['title' => 'Conserva tu folio', 'description' => 'Tu folio es '.$this->solicitud->folio.'. Te ayudará a identificar tu solicitud.'],
                    ['title' => 'Revisa tu correo', 'description' => 'Cualquier actualización, requerimiento u observación se te notificará por este mismo medio.'],
                    ['title' => 'Consulta el estado de tu trámite', 'description' => 'Ingresa al portal y consulta el avance de tu solicitud con tu CURP.'],
                ],
                'noticeTitle' => 'Mantente atenta o atento',
                'notice' => 'El envío de la solicitud no equivale a una aprobación. Recibirás una notificación por correo cuando exista una actualización.',
                'actionUrl' => route('portal.publico').'#consulta',
                'actionLabel' => 'Consultar estado de mi trámite',
                'actionHint' => 'Para consultar, ten a la mano tu CURP y tu folio.',
                'farewell' => 'Gracias por utilizar la Plataforma de Trámites.',
                'helpUrl' => route('portal.publico').'#consulta',
            ],
        );
    }
}
