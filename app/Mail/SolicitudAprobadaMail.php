<?php

namespace App\Mail;

use App\Models\Solicitud;
use App\Models\SolicitudDocumentoEmitido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Solicitud $solicitud, public ?SolicitudDocumentoEmitido $documentoEmitido = null, public ?string $instrucciones = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Solicitud aprobada · Folio '.$this->solicitud->folio);
    }

    public function content(): Content
    {
        $enLinea = $this->documentoEmitido !== null;
        $pasosEnLinea = [
            ['title' => 'Guarda tu documento', 'description' => 'El documento final se adjunta a este correo y también estará disponible en la consulta segura.'],
            ['title' => 'Conserva tu folio', 'description' => 'Utiliza el folio '.$this->solicitud->folio.' para cualquier seguimiento.'],
        ];
        if ($enLinea && filled($this->instrucciones)) {
            array_splice($pasosEnLinea, 1, 0, [['title' => 'Indicaciones adicionales', 'description' => $this->instrucciones]]);
        }
        if ($enLinea && $this->documentoEmitido->fecha_expiracion !== null) {
            $pasosEnLinea[] = ['title' => 'Descarga a tiempo', 'description' => 'El documento estará disponible para descarga hasta el '.$this->documentoEmitido->fecha_expiracion->translatedFormat('d \d\e F \d\e Y').'. Después de esa fecha ya no podrá descargarse.'];
        }

        return new Content(view: 'emails.layouts.notificacion', with: [
            'subject' => 'Solicitud aprobada · Folio '.$this->solicitud->folio,
            'logoPath' => public_path('images/logo/logo-sin-fondo.png'),
            'preheader' => 'Tu solicitud '.$this->solicitud->folio.' fue aprobada.',
            'kicker' => 'SOLICITUD APROBADA',
            'title' => 'Tu trámite fue aprobado',
            'intro' => $enLinea ? 'Tu solicitud fue aprobada. Adjuntamos el documento final emitido para tu trámite.' : 'Tu solicitud fue aprobada. Sigue las instrucciones indicadas para concluir la atención.',
            'tramiteName' => $this->solicitud->tramite->nombre,
            'folio' => $this->solicitud->folio,
            'statusLabel' => 'Aprobada',
            'statusTone' => 'success',
            'stepsTitle' => $enLinea ? 'Documento final disponible' : 'Instrucciones para continuar',
            'steps' => $enLinea ? $pasosEnLinea : [
                ['title' => 'Atiende las instrucciones', 'description' => $this->instrucciones],
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

    public function attachments(): array
    {
        if (! $this->documentoEmitido) {
            return [];
        }

        return [Attachment::fromStorageDisk('local', $this->documentoEmitido->storage_key)->as($this->documentoEmitido->nombre_original)->withMime($this->documentoEmitido->mime_type)];
    }
}
