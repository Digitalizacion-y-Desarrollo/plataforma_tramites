<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDocumentoEmitido extends Model
{
    protected $table = 'solicitud_documentos_emitidos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'solicitud_id', 'nombre_original', 'storage_key', 'mime_type', 'tamano_bytes', 'hash_archivo', 'actor_accesos_id', 'actor_nombre_snapshot', 'fecha_emision', 'fecha_expiracion'];

    protected function casts(): array
    {
        return ['fecha_emision' => 'datetime', 'fecha_expiracion' => 'datetime'];
    }

    public function expirado(): bool
    {
        return $this->fecha_expiracion !== null && now()->greaterThan($this->fecha_expiracion);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }
}
