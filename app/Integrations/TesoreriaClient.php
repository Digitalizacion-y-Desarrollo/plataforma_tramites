<?php

namespace App\Integrations;

use App\Models\Expediente;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TesoreriaClient
{
    public function registrarContribuyente(Expediente $expediente): string
    {
        $email = config('services.tesoreria.email');
        $password = config('services.tesoreria.password');
        if (blank($email) || blank($password)) {
            throw new RuntimeException('La integración de Tesorería no está configurada.');
        }

        try {
            $login = $this->peticion()->post($this->url('/auth/login'), [
                'email' => $email,
                'password' => $password,
            ])->throw();
            $token = $login->json('access_token');
            if (blank($token)) {
                throw new RuntimeException('Tesorería no devolvió un token de acceso.');
            }

            $respuesta = $this->peticion()->withToken($token)->post($this->url('/contribuyentes'), [
                'correo' => $expediente->email_contacto,
                'nombre' => $expediente->nombres_solicitante,
                'apellido_paterno' => $expediente->apellido_paterno_solicitante,
                'apellido_materno' => $expediente->apellido_materno_solicitante,
                'calle' => $expediente->calle,
                'numero_exterior' => $expediente->numero_exterior,
                'colonia' => $expediente->colonia,
                'codigo_postal' => $expediente->codigo_postal,
                'municipio_id' => 715,
                'regimen_fiscal_id' => 1,
            ])->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible comunicarse con Tesorería.', 0, $exception);
        }

        $idExterno = $respuesta->json('data.id');
        if (blank($idExterno)) {
            throw new RuntimeException('Tesorería no devolvió el identificador del contribuyente.');
        }

        return (string) $idExterno;
    }

    private function peticion()
    {
        return Http::acceptJson()->timeout(config('services.tesoreria.timeout'));
    }

    private function url(string $ruta): string
    {
        return rtrim((string) config('services.tesoreria.url'), '/').$ruta;
    }
}
