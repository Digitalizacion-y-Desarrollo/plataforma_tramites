<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudComentarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Solicitud $solicitud, public string $comentario) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Actualización de tu trámite · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: ['subject' => 'Actualización de tu trámite · Folio '.$this->solicitud->folio, 'preheader' => 'Tienes un mensaje nuevo del área responsable.', 'kicker' => 'MENSAJE DEL ÁREA RESPONSABLE', 'title' => 'Tienes un nuevo mensaje', 'intro' => 'El área responsable dejó una actualización en tu expediente.', 'highlightMessageTitle' => 'Mensaje del área responsable', 'highlightMessage' => $this->comentario, 'tramiteName' => $this->solicitud->tramite->nombre, 'folio' => $this->solicitud->folio, 'statusLabel' => $this->solicitud->estado->nombre, 'statusTone' => 'info', 'actionUrl' => route('portal.publico').'#consulta', 'actionLabel' => 'Consultar mi trámite', 'actionHint' => 'Ingresa con tu CURP y el código temporal enviado a tu correo.', 'farewell' => 'Gracias por utilizar la Plataforma de Trámites.', 'helpUrl' => route('portal.publico').'#consulta']);
    }
}
