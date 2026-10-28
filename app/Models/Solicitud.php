<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['folio', 'tramite_id', 'departamento_accesos_id_snapshot', 'departamento_nombre_snapshot', 'formulario_version_id', 'solicitud_estado_id', 'curp_consulta', 'email_contacto', 'telefono_contacto', 'nombre_solicitante_snapshot', 'nombres_solicitante', 'apellido_paterno_solicitante', 'apellido_materno_solicitante', 'flujo_pago', 'importe_pago', 'pago_vigencia_dias', 'fecha_envio', 'fecha_terminacion', 'aviso_privacidad_aceptado_at'];

    protected function casts(): array
    {
        return ['fecha_envio' => 'datetime', 'fecha_terminacion' => 'datetime', 'aviso_privacidad_aceptado_at' => 'datetime', 'importe_pago' => 'decimal:2', 'pago_vigencia_dias' => 'integer'];
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoSolicitud::class, 'solicitud_estado_id');
    }

    public function documentoEmitido(): HasOne
    {
        return $this->hasOne(SolicitudDocumentoEmitido::class);
    }

    public function pago(): HasOne
    {
        return $this->hasOne(SolicitudPago::class);
    }
}
