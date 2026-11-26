<?php

namespace App\Jobs;

use App\Integrations\TesoreriaClient;
use App\Models\Expediente;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegistrarContribuyenteTesoreria implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(public string $expedienteId) {}

    public function handle(TesoreriaClient $tesoreria): void
    {
        $expediente = Expediente::query()->find($this->expedienteId);
        if (! $expediente) {
            return;
        }

        try {
            if (filled($expediente->id_externo)) {
                $tesoreria->actualizarContribuyente($expediente);
            } else {
                $expediente->update(['id_externo' => $tesoreria->registrarContribuyente($expediente)]);
            }
        } catch (\Throwable $exception) {
            Log::warning('No fue posible sincronizar el contribuyente con Tesorería; se reintentará.', [
                'expediente_id' => $expediente->id,
                'exception' => $exception,
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff()[$this->attempts() - 1] ?? 900);
            }
        }
    }
}
