<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre',
        'prefijo',
        'token_hash',
        'activo',
        'expira_en',
        'ultimo_uso_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'expira_en' => 'datetime',
            'ultimo_uso_at' => 'datetime',
        ];
    }

    public static function generar(string $nombre, ?string $expiraEn = null): array
    {
        do {
            $token = 'pt_live_'.bin2hex(random_bytes(32));
            $prefijo = Str::substr($token, 0, 16);
        } while (self::query()->where('prefijo', $prefijo)->exists());

        $apiKey = self::query()->create([
            'id' => (string) Str::uuid(),
            'nombre' => $nombre,
            'prefijo' => $prefijo,
            'token_hash' => hash('sha256', $token),
            'activo' => true,
            'expira_en' => $expiraEn,
        ]);

        return [$apiKey, $token];
    }
}
