<?php

namespace App\Integrations;

use App\Models\Expediente;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TesoreriaClient
{
    public function buscarPorCurp(string $curp): ?array
    {
        $respuesta = $this->autenticada()->get($this->url('/contribuyentes/buscar/curp/'.rawurlencode($curp)))->throw();

        return $respuesta->json('existe') ? $respuesta->json() : null;
    }

    public function contribuyente(string $id): array
    {
        return $this->autenticada()->get($this->url('/contribuyentes/'.rawurlencode($id)))->throw()->json('data', []);
    }

    public function regimenesFiscales(string $tipoPersona): array
    {
        $tipo = $tipoPersona === 'MORAL' ? 2 : 1;

        return $this->autenticada()->get($this->url('/catalogo/regimenes-fiscales'), ['tipo_persona' => $tipo])
            ->throw()
            ->json('data', []);
    }

    public function registrarContribuyente(Expediente $expediente): string
    {
        $respuesta = $this->autenticada()->post($this->url('/contribuyentes'), $this->datosContribuyente($expediente))->throw();
        $id = $respuesta->json('contribuyente_id') ?? $respuesta->json('data.id');
        if (blank($id)) {
            throw new RuntimeException('Tesorería no devolvió el identificador del contribuyente.');
        }

        return (string) $id;
    }

    public function actualizarContribuyente(Expediente $expediente): void
    {
        if (blank($expediente->id_externo)) {
            throw new RuntimeException('No es posible actualizar un contribuyente sin identificador externo.');
        }

        $this->autenticada()
            ->put($this->url('/contribuyentes/'.rawurlencode($expediente->id_externo)), $this->datosContribuyente($expediente))
            ->throw();
    }

    private function autenticada()
    {
        $email = config('services.tesoreria.email');
        $password = config('services.tesoreria.password');
        if (blank($email) || blank($password)) {
            throw new RuntimeException('La integración de Tesorería no está configurada.');
        }

        try {
            $login = $this->peticion()
                ->post($this->url('/auth/login'), ['email' => $email, 'password' => $password])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible autenticarse con Tesorería.', 0, $exception);
        }

        $token = $login->json('access_token');
        if (blank($token)) {
            throw new RuntimeException('Tesorería no devolvió un token de acceso.');
        }

        return $this->peticion()->withToken($token);
    }

    private function peticion()
    {
        $peticion = Http::acceptJson()->timeout(config('services.tesoreria.timeout'));
        if (filled(config('services.tesoreria.ca_bundle'))) {
            $peticion = $peticion->withOptions(['verify' => config('services.tesoreria.ca_bundle')]);
        }

        return $peticion;
    }

    private function url(string $ruta): string
    {
        return rtrim((string) config('services.tesoreria.url'), '/').$ruta;
    }

    private function datosContribuyente(Expediente $expediente): array
    {
        $datos = [
            'correo' => $expediente->email_contacto,
            'telefono' => $expediente->telefono_contacto,
            'calle' => $expediente->calle,
            'numero_exterior' => $expediente->numero_exterior,
            'colonia' => $expediente->colonia,
            'codigo_postal' => $expediente->codigo_postal,
            'municipio_id' => 715,
            'regimen_fiscal_id' => $expediente->regimen_fiscal_externo_id,
        ];

        if ($expediente->tipo_persona === 'MORAL') {
            $datos['razonSocial'] = $expediente->razon_social;
            $datos['rfc'] = $expediente->rfc;
        } else {
            $datos['curp'] = $expediente->curp_consulta;
            $datos['nombre'] = $expediente->nombres_solicitante;
            $datos['apellido_paterno'] = $expediente->apellido_paterno_solicitante;
            $datos['apellido_materno'] = $expediente->apellido_materno_solicitante;
        }

        return $datos;
    }
}
