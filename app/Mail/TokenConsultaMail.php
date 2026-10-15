<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenConsultaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Token temporal para consultar tus trámites');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'title' => 'Consulta segura de trámites', 'kicker' => 'TOKEN TEMPORAL',
            'intro' => 'Tu token temporal es: '.$this->token.'. Tiene una vigencia de 15 minutos y solo puede utilizarse una vez.',
            'statusLabel' => 'Token temporal', 'statusTone' => 'info',
            'notice' => 'No compartas este token con terceros.',
        ]);
    }
}
