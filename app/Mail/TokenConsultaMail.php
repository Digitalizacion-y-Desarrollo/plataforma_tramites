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
        return new Envelope(subject: 'Tu código para consultar tus trámites');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Tu código para consultar tus trámites',
            'preheader' => 'Recibimos tu solicitud de acceso a la consulta ciudadana. El código vence en 15 minutos.',
            'kicker' => 'CÓDIGO DE VERIFICACIÓN',
            'title' => $this->token,
            'intro' => 'Ingresa este código de seis dígitos en la plataforma para consultar el estado de tus trámites. Es de un solo uso y vence en 15 minutos.',
            'stepsTitle' => '¿Cómo consultar tus trámites?',
            'steps' => [
                ['title' => 'Vuelve a la plataforma', 'description' => 'Abre la página de consulta con el mismo navegador donde solicitaste el código.'],
                ['title' => 'Ingresa el código', 'description' => 'Escribe los seis dígitos en el campo «Código de verificación» y selecciona «Entrar a mi portal».'],
            ],
            'noticeTitle' => 'Tu información está protegida',
            'notice' => 'No compartas este código. Si no solicitaste el acceso, ignora este correo; nadie podrá entrar a tus trámites sin el código.',
            'actionUrl' => route('portal.publico').'#consulta',
            'actionLabel' => 'Ir a la consulta de trámites',
            'actionHint' => 'Este enlace no inicia sesión automáticamente; deberás ingresar el código.',
            'helpText' => 'Si el código vence o no funciona, solicita uno nuevo desde la página de consulta.',
        ]);
    }
}
