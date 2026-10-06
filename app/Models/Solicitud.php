<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['folio', 'tramite_id', 'formulario_version_id', 'solicitud_estado_id', 'curp_consulta', 'email_contacto', 'fecha_envio', 'fecha_terminacion'];

    protected function casts(): array
    {
        return ['fecha_envio' => 'datetime', 'fecha_terminacion' => 'datetime'];
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoSolicitud::class, 'solicitud_estado_id');
    }
}
