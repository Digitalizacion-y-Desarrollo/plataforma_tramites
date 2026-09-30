<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampoCondicion extends Model
{
    protected $table = 'campo_condiciones';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'campo_destino_id', 'campo_origen_id', 'operador', 'valor_comparacion', 'accion', 'grupo'];

    protected function casts(): array
    {
        return ['valor_comparacion' => 'array'];
    }

    public function campoDestino(): BelongsTo
    {
        return $this->belongsTo(FormularioCampo::class, 'campo_destino_id');
    }

    public function campoOrigen(): BelongsTo
    {
        return $this->belongsTo(FormularioCampo::class, 'campo_origen_id');
    }

    public function grupoDestino(): BelongsTo
    {
        return $this->belongsTo(FormularioGrupoCampo::class, 'grupo_destino_id');
    }
}
