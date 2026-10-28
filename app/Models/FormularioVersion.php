<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormularioVersion extends Model
{
    protected $table = 'formulario_versiones';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'formulario_id',
        'formulario_estado_id',
        'numero_version',
        'fecha_publicacion',
        'creado_por_accesos_id',
        'publicado_por_accesos_id',
    ];

    protected function casts(): array
    {
        return ['fecha_publicacion' => 'datetime'];
    }

    public function formulario(): BelongsTo
    {
        return $this->belongsTo(Formulario::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(FormularioEstado::class, 'formulario_estado_id');
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(FormularioSeccion::class);
    }

    public function gruposCampos(): HasMany
    {
        return $this->hasMany(FormularioGrupoCampo::class, 'formulario_version_id');
    }

    public function tramites(): BelongsToMany
    {
        return $this->belongsToMany(Tramite::class, 'tramite_formulario')
            ->withPivot(['activo', 'fecha_inicio_vigencia', 'fecha_fin_vigencia']);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }
}
