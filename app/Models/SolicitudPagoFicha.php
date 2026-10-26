<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudPagoFicha extends Model
{
    protected $table = 'solicitud_pago_fichas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'solicitud_pago_id', 'version', 'nombre_original', 'storage_key', 'mime_type',
        'tamano_bytes', 'hash_archivo', 'indicaciones', 'motivo_reemplazo', 'es_vigente',
        'actor_accesos_id', 'actor_nombre_snapshot', 'actor_departamento_snapshot',
    ];

    protected function casts(): array
    {
        return ['version' => 'integer', 'tamano_bytes' => 'integer', 'es_vigente' => 'boolean'];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(SolicitudPago::class, 'solicitud_pago_id');
    }
}
