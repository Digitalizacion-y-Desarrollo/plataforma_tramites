<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FormularioCampo extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'seccion_id', 'clave', 'etiqueta', 'tipo', 'requerido', 'orden', 'configuracion', 'activo'];

    protected function casts(): array
    {
        return ['requerido' => 'boolean', 'configuracion' => 'array', 'activo' => 'boolean'];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(FormularioSeccion::class, 'seccion_id');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(CampoOpcion::class, 'campo_id');
    }

    public function condicionesDestino(): HasMany
    {
        return $this->hasMany(CampoCondicion::class, 'campo_destino_id');
    }

    public function condicionesOrigen(): HasMany
    {
        return $this->hasMany(CampoCondicion::class, 'campo_origen_id');
    }

    public function gruposCampos(): BelongsToMany
    {
        return $this->belongsToMany(FormularioGrupoCampo::class, 'formulario_grupo_campo', 'campo_id', 'grupo_id')->withTimestamps();
    }
}
