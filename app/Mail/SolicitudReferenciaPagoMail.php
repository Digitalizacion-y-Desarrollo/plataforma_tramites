<?php

namespace App\Mail;

use App\Models\Solicitud;
use App\Models\SolicitudPago;
use App\Models\SolicitudPagoFicha;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudReferenciaPagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Solicitud $solicitud,
        public SolicitudPago|SolicitudPagoFicha $pago,
        public bool $esReemplazo = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->esReemplazo ? 'Ficha de pago actualizada' : 'Ficha de pago').' · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => ($this->esReemplazo ? 'Ficha de pago actualizada' : 'Ficha de pago').' · Folio '.$this->solicitud->folio,
            'preheader' => 'Tu ficha de pago está lista.',
            'kicker' => 'FICHA DE PAGO',
            'title' => $this->esReemplazo ? 'Tu ficha de pago fue actualizada' : 'Tu ficha de pago está lista',
            'intro' => $this->esReemplazo
                ? 'Adjuntamos la nueva ficha de pago, que sustituye a la enviada anteriormente.'
                : 'Adjuntamos tu ficha de pago. Realiza el pago antes de la fecha límite para continuar con tu trámite.',
            'tramiteName' => $this->solicitud->tramite->nombre,
            'folio' => $this->solicitud->folio,
            'statusLabel' => 'En espera de pago',
            'statusTone' => 'warning',
            'stepsTitle' => 'Indicaciones de pago',
            'steps' => [['title' => 'Indicaciones', 'description' => $this->indicaciones()]],
            'noticeTitle' => 'Importante',
            'notice' => 'Conserva esta ficha y tu comprobante. El área responsable validará el pago para continuar con la revisión final.',
            'actionUrl' => route('portal.publico').'#consulta',
            'actionLabel' => 'Consultar mi trámite',
            'farewell' => 'Gracias por utilizar la Plataforma de Trámites.',
            'helpUrl' => route('portal.publico').'#consulta',
        ]);
    }

    public function attachments(): array
    {
        return [Attachment::fromStorageDisk('local', $this->storageKey())->as($this->nombreOriginal())->withMime('application/pdf')];
    }

    private function indicaciones(): string
    {
        return $this->pago instanceof SolicitudPagoFicha
            ? $this->pago->indicaciones
            : 'Consulta la ficha adjunta para conocer los datos de pago.';
    }

    private function storageKey(): string
    {
        return $this->pago instanceof SolicitudPagoFicha ? $this->pago->storage_key : $this->pago->pdf_storage_key;
    }

    private function nombreOriginal(): string
    {
        return $this->pago instanceof SolicitudPagoFicha ? $this->pago->nombre_original : $this->pago->pdf_nombre_original;
    }
}
