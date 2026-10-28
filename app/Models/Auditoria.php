<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'actor_accesos_id',
        'actor_nombre_snapshot',
        'actor_departamento_snapshot',
        'accion',
        'entidad',
        'entidad_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
        static::deleting(fn (): bool => false);
    }

    /**
     * Registra una acción realizada por un servidor público.
     *
     * Los registros de auditoría son inmutables: no deben actualizarse ni eliminarse.
     */
    public static function registrar(
        Request $request,
        string $accion,
        string $entidad,
        ?string $entidadId = null,
        ?array $anteriores = null,
        ?array $nuevos = null
    ): void {
        $actorId = $request->session()->get('accesos.id');
        $actorNombre = $request->session()->get('accesos.nombre');
        $departamento = $request->session()->get('accesos.departamento');

        self::query()->create([
            'id' => (string) Str::uuid(),
            'actor_accesos_id' => filled($actorId) ? $actorId : 'PENDIENTE_INTEGRACION_ACCESOS',
            'actor_nombre_snapshot' => filled($actorNombre) ? $actorNombre : 'Servidor público pendiente de integración con Accesos',
            'actor_departamento_snapshot' => $departamento,
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos_anteriores' => $anteriores,
            'datos_nuevos' => $nuevos,
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
