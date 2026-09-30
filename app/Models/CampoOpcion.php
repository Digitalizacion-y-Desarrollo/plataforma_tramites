<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampoOpcion extends Model
{
    protected $table = 'campo_opciones';
    
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'campo_id', 'valor', 'etiqueta', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function campo(): BelongsTo
    {
        return $this->belongsTo(FormularioCampo::class, 'campo_id');
    }
}
