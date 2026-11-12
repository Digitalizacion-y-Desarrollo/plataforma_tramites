<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TokenExpedienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código para continuar tu trámite');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Tu código para continuar tu trámite',
            'preheader' => 'Confirma tu identidad para reutilizar tus datos registrados. El código vence en 15 minutos.',
            'kicker' => 'CÓDIGO DE VERIFICACIÓN',
            'title' => $this->token,
            'intro' => 'Ingresa este código en el formulario para confirmar tu identidad y reutilizar tus datos registrados. Es de un solo uso y vence en 15 minutos.',
            'stepsTitle' => '¿Cómo continuar?',
            'steps' => [
                ['title' => 'Vuelve al formulario', 'description' => 'Regresa al trámite que estabas llenando en el mismo navegador.'],
                ['title' => 'Ingresa el código', 'description' => 'Escribe los seis dígitos en la ventana de verificación.'],
            ],
            'noticeTitle' => 'Tu información está protegida',
            'notice' => 'No compartas este código. Si no solicitaste esta verificación, ignora este correo.',
            'actionUrl' => route('portal.publico'),
            'actionLabel' => 'Ir al portal de trámites',
            'actionHint' => 'El enlace no confirma tu identidad automáticamente; deberás ingresar el código en el formulario.',
            'helpText' => 'Si el código vence o no funciona, solicita uno nuevo desde el formulario.',
        ]);
    }
}
