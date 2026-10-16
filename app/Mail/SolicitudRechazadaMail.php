<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudRechazadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Solicitud $solicitud, public string $motivo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Solicitud rechazada · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Solicitud rechazada · Folio '.$this->solicitud->folio,
            'logoPath' => public_path('images/logo/logo-sin-fondo.png'),
            'preheader' => 'Tu solicitud '.$this->solicitud->folio.' fue rechazada.',
            'kicker' => 'SOLICITUD RECHAZADA',
            'title' => 'Tu trámite fue rechazado',
            'intro' => 'Tu solicitud no puede continuar con la información presentada. Consulta el motivo registrado para conocer el detalle.',
            'tramiteName' => $this->solicitud->tramite->nombre,
            'folio' => $this->solicitud->folio,
            'statusLabel' => 'Rechazada',
            'statusTone' => 'danger',
            'stepsTitle' => 'Motivo del rechazo',
            'steps' => [
                ['title' => 'Motivo registrado', 'description' => $this->motivo],
                ['title' => 'Conserva tu folio', 'description' => 'Utiliza el folio '.$this->solicitud->folio.' para cualquier seguimiento.'],
            ],
            'noticeTitle' => 'Seguimiento seguro',
            'notice' => 'Consulta el estado de tu trámite mediante tu CURP y el token temporal enviado a tu correo.',
            'actionUrl' => route('portal.publico').'#consulta',
            'actionLabel' => 'Consultar mi trámite',
            'farewell' => 'Gracias por utilizar la Plataforma de Trámites.',
            'helpUrl' => route('portal.publico').'#consulta',
        ]);
    }
}
