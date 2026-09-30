<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormularioSeccion extends Model
{
    protected $table = 'formulario_secciones';     

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'formulario_version_id', 'clave', 'titulo', 'descripcion', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormularioVersion::class, 'formulario_version_id');
    }

    public function campos(): HasMany
    {
        return $this->hasMany(FormularioCampo::class, 'seccion_id');
    }
}
