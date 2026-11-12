<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    protected $table = 'expedientes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'curp_consulta',
        'tipo_persona',
        'id_externo',
        'email_contacto',
        'telefono_contacto',
        'nombres_solicitante',
        'apellido_paterno_solicitante',
        'apellido_materno_solicitante',
        'razon_social',
        'calle',
        'numero_exterior',
        'colonia',
        'codigo_postal',
        'municipio_id',
        'regimen_fiscal_id',
    ];

    public function regimenFiscal(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }
}
