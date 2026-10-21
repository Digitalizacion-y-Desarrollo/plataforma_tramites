<?php

namespace App\Mail;

use App\Models\Solicitud;
use App\Models\SolicitudPago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudReferenciaPagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Solicitud $solicitud, public SolicitudPago $pago) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Referencia de pago · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Referencia de pago · Folio '.$this->solicitud->folio,
            'preheader' => 'Tu referencia de pago está lista.', 'kicker' => 'REFERENCIA DE PAGO',
            'title' => 'Tu referencia de pago está lista', 'intro' => 'Adjuntamos tu formato de pago. Realiza el pago antes de la fecha límite para continuar con tu trámite.',
            'tramiteName' => $this->solicitud->tramite->nombre, 'folio' => $this->solicitud->folio,
            'statusLabel' => 'En espera de pago', 'statusTone' => 'warning',
            'stepsTitle' => 'Datos de pago', 'steps' => [
                ['title' => 'Referencia', 'description' => $this->pago->referencia],
                ['title' => 'Importe', 'description' => '$'.number_format((float) $this->pago->importe, 2).' MXN'],
                ['title' => 'Fecha límite', 'description' => $this->pago->fecha_vencimiento->translatedFormat('d \d\e F \d\e Y')],
            ], 'noticeTitle' => 'Importante', 'notice' => 'La referencia es provisional mientras se integra el servicio de pagos. El área responsable validará el pago para continuar con la revisión final.',
            'actionUrl' => route('portal.publico').'#consulta', 'actionLabel' => 'Consultar mi trámite', 'farewell' => 'Gracias por utilizar la Plataforma de Trámites.', 'helpUrl' => route('portal.publico').'#consulta',
        ]);
    }

    public function attachments(): array
    {
        return [Attachment::fromStorageDisk('local', $this->pago->pdf_storage_key)->as($this->pago->pdf_nombre_original)->withMime('application/pdf')];
    }
}
