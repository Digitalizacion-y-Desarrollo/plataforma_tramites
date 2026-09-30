<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormularioGrupoCampo extends Model
{
    protected $table = 'formulario_grupos_campos';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'formulario_version_id', 'titulo', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormularioVersion::class, 'formulario_version_id');
    }

    public function campos(): BelongsToMany
    {
        return $this->belongsToMany(FormularioCampo::class, 'formulario_grupo_campo', 'grupo_id', 'campo_id')->withTimestamps();
    }

    public function condicionesDestino(): HasMany
    {
        return $this->hasMany(CampoCondicion::class, 'grupo_destino_id');
    }
}
