<?php

namespace App\Mail;

use App\Models\Solicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudCorreccionDocumentosMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Solicitud $solicitud,
        public array $documentos,
        public string $url,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Corrección de documentos requerida · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        $pasos = collect($this->documentos)
            ->map(fn (array $documento) => ['title' => $documento['nombre'], 'description' => $documento['motivo']])
            ->all();
        $pasos[] = ['title' => 'Ingresa de forma segura', 'description' => 'Usa la contraseña temporal: '.$this->password];

        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Corrección de documentos requerida · Folio '.$this->solicitud->folio,
            'logoPath' => public_path('images/logo/logo-sin-fondo.png'),
            'preheader' => 'Necesitamos que corrijas documentos de tu solicitud '.$this->solicitud->folio.'.',
            'kicker' => 'CORRECCIÓN DE DOCUMENTOS',
            'title' => 'Necesitamos información corregida',
            'intro' => 'Revisa los motivos indicados y reemplaza únicamente los archivos solicitados para que podamos continuar con la revisión.',
            'tramiteName' => $this->solicitud->tramite->nombre,
            'folio' => $this->solicitud->folio,
            'statusLabel' => 'Corrección solicitada',
            'statusTone' => 'warning',
            'stepsTitle' => 'Documentos por corregir',
            'steps' => $pasos,
            'noticeTitle' => 'Acceso temporal',
            'notice' => 'La URL y contraseña dejarán de funcionar cuando envíes todos los documentos corregidos. No compartas este correo con terceros.',
            'actionUrl' => $this->url,
            'actionLabel' => 'Corregir documentos',
            'farewell' => 'Gracias por utilizar la Plataforma de Trámites.',
            'helpUrl' => route('portal.publico').'#consulta',
        ]);
    }
}
