<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPago extends Model
{
    protected $table = 'solicitud_pagos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'solicitud_id', 'referencia', 'importe', 'fecha_emision', 'fecha_vencimiento', 'pdf_nombre_original', 'pdf_storage_key', 'pdf_hash_archivo', 'comprobante_nombre_original', 'comprobante_storage_key', 'comprobante_mime_type', 'comprobante_tamano_bytes', 'comprobante_hash_archivo', 'fecha_comprobante', 'emisor_accesos_id', 'emisor_nombre_snapshot', 'fecha_pago_validado', 'comentario_validacion', 'validador_accesos_id', 'validador_nombre_snapshot', 'validador_departamento_snapshot'];

    protected function casts(): array
    {
        return ['importe' => 'decimal:2', 'fecha_emision' => 'datetime', 'fecha_vencimiento' => 'datetime', 'fecha_comprobante' => 'datetime', 'fecha_pago_validado' => 'datetime'];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function vencido(): bool
    {
        return now()->greaterThan($this->fecha_vencimiento);
    }

    public function tieneComprobante(): bool
    {
        return filled($this->comprobante_storage_key);
    }
}
