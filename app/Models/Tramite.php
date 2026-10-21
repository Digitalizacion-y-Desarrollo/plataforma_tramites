<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tramite extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'departamento_accesos_id',
        'departamento_nombre_snapshot',
        'duracion',
        'costo',
        'costo_pago',
        'flujo_pago',
        'pago_vigencia_dias',
        'tiempo_estimado',
        'modalidades',
        'requisitos',
        'proceso',
        'preguntas_frecuentes',
        'descarga_expira_dias',
        'activo',
        'tramite_estado_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'modalidades' => 'array',
            'requisitos' => 'array',
            'proceso' => 'array',
            'preguntas_frecuentes' => 'array',
            'descarga_expira_dias' => 'integer',
            'costo_pago' => 'decimal:2',
            'pago_vigencia_dias' => 'integer',
        ];
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(TramiteEstado::class, 'tramite_estado_id');
    }

    public function formulariosVersiones(): BelongsToMany
    {
        return $this->belongsToMany(FormularioVersion::class, 'tramite_formulario', 'tramite_id', 'formulario_version_id')
            ->withPivot(['activo', 'fecha_inicio_vigencia', 'fecha_fin_vigencia']);
    }

    public function formularioActivo(): BelongsToMany
    {
        return $this->formulariosVersiones()->wherePivot('activo', true);
    }
}
