<?php

namespace App\Console\Commands;

use App\Services\SincronizadorCatalogosAccesos;
use Illuminate\Console\Command;
use Throwable;

class SincronizarCatalogosAccesos extends Command
{
    protected $signature = 'catalogos:sincronizar';

    protected $description = 'Sincroniza departamentos y áreas desde la API de Control de Accesos';

    public function handle(SincronizadorCatalogosAccesos $sincronizador): int
    {
        try {
            $resultado = $sincronizador->sincronizar();
            $this->info("Sincronización terminada: {$resultado['departamentos']} departamentos y {$resultado['areas']} áreas.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error("La sincronización no se completó: {$exception->getMessage()}");

            return self::FAILURE;
        }
    }
}
