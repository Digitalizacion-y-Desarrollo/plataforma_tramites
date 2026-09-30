<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Formulario extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'departamento_accesos_id',
        'departamento_nombre_snapshot',
        'subdepartamento_accesos_id',
        'subdepartamento_nombre_snapshot',
        'creado_por_accesos_id',
    ];

    public function versiones(): HasMany
    {
        return $this->hasMany(FormularioVersion::class);
    }

    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(FormularioVersion::class)->ofMany('numero_version', 'max');
    }
}
