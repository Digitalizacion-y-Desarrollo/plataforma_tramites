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
        'duracion',
        'costo',
        'tiempo_estimado',
        'modalidades',
        'requisitos',
        'proceso',
        'preguntas_frecuentes',
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
}
