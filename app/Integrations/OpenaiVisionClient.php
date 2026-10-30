<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de OpenAI para la extracción estructurada de los datos del acta de
 * nacimiento adjunta en una solicitud.
 *
 * Utiliza la API de Responses con salida estructurada (JSON Schema) para que
 * la respuesta sea siempre un objeto con la forma esperada. Los archivos
 * admitidos son PDF, JPG y PNG; se envían en base64 como data URI.
 */
class OpenaiVisionClient
{
    /**
     * Claves de "datos"; deben coincidir con las claves de los campos de la
     * captura administrativa del acta (ConstanciaProcedenciaOrigenSeeder).
     */
    private const CLAVES_DATO = [
        'lugar_nacimiento',
        'lugar_registro',
        'numero_acta',
        'no_acta_letra',
        'no_oficialia',
        'no_oficialia_letra',
        'no_libro',
        'no_libro_letra',
        'dia_nacimiento',
        'dia_nacimiento_letra',
        'mes_nacimiento',
        'ano_nacimiento',
        'ano_nacimiento_letra',
        'nombre_padre',
        'nombre_madre',
        'nombre_certifica',
        'cargo',
        'dia_certificacion',
        'dia_certificacion_letra',
        'mes_certificacion',
        'ano_certificacion',
        'ano_certificacion_letra',
    ];

    public function extraerDatosActa(string $contenido, string $mimeType): array
    {
        $respuesta = $this->peticion($this->instrucciones(), $contenido, $mimeType);

        return [
            'es_acta_nacimiento' => (bool) ($respuesta['es_acta_nacimiento'] ?? false),
            'motivo' => filled($respuesta['motivo'] ?? null) ? (string) $respuesta['motivo'] : null,
            'datos' => is_array($respuesta['datos'] ?? null) ? $respuesta['datos'] : [],
        ];
    }

    private function instrucciones(): string
    {
        return <<<'TEXTO'
        Analiza el documento adjunto. Primero determina si es un acta de nacimiento mexicana (cualquier formato emitido por el Registro Civil: formato antiguo en listado, RCPP nuevo o formato certificatorio).

        Reglas de extracción:
        - Si NO es un acta de nacimiento (por ejemplo: comprobante de domicilio, credencial, acta de matrimonio o divorcio, acta de defunción, u otro documento mayormente ilegible o borroso), responde es_acta_nacimiento = false, escribe el motivo y deja todos los campos de "datos" como cadenas vacías.
        - Si SÍ es un acta de nacimiento, responde es_acta_nacimiento = true y extrae TODOS los datos del esquema de forma literal, sin traducirlos, sin abreviar y sin inventar. Si un dato no aparece en el documento, usa cadena vacía.
        - Ignora el domicilio de residencia del acta: no lo extraigas ni lo incluyas en la respuesta.
        - lugar_nacimiento y lugar_registro son las localidades (con municipio y estado) de nacimiento y de registro del interesado.
        - numero_acta, no_oficialia y no_libro son las cifras del acta; no_oficialia corresponde al número de oficialía del Registro Civil.
        - nombre_certifica es el nombre completo de la persona que certifica el acta, y cargo su cargo (usualmente "OFICIAL DEL REGISTRO CIVIL" o el oficial con número).
        - Los campos con sufijo _letra corresponden a la parte literal escrita en el acta junto a la cifra (por ejemplo, el número de acta escrito con letra secundaria). Separa la cifra en su campo y la letra en el campo _letra.
        - dia_nacimiento, mes_nacimiento y ano_nacimiento se obtienen de la fecha de nacimiento; dia_certificacion, mes_certificacion y ano_certificacion de la fecha de certificación. Los meses se escriben tal como aparecen en el acta (por ejemplo "SEIS" o "DICIEMBRE").
        TEXTO;
    }

    private function peticion(string $instrucciones, string $contenido, string $mimeType): array
    {
        $respuesta = Http::withToken((string) config('services.openai.api_key'))
            ->acceptJson()
            ->timeout(config('services.openai.timeout'))
            ->post(config('services.openai.url'), [
                'model' => config('services.openai.model'),
                'input' => [
                    ['role' => 'system', 'content' => [['type' => 'input_text', 'text' => $instrucciones]]],
                    ['role' => 'user', 'content' => [$this->bloqueContenido($contenido, $mimeType)]],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'datos_acta_nacimiento',
                        'strict' => true,
                        'schema' => $this->schemaExtraccion(),
                    ],
                ],
            ]);

        if ($respuesta->failed()) {
            throw new RuntimeException('La petición a OpenAI devolvió el código HTTP '.$respuesta->status().'.');
        }

        return $this->interpretarSalida($respuesta->json());
    }

    private function bloqueContenido(string $contenido, string $mimeType): array
    {
        $base64 = base64_encode($contenido);

        if ($mimeType === 'application/pdf') {
            return [
                'type' => 'input_file',
                'filename' => 'acta-nacimiento.pdf',
                'file_data' => 'data:application/pdf;base64,'.$base64,
            ];
        }

        return [
            'type' => 'input_image',
            'image_url' => 'data:'.$mimeType.';base64,'.$base64,
            'detail' => 'high',
        ];
    }

    private function schemaExtraccion(): array
    {
        $texto = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'es_acta_nacimiento' => [
                    'type' => 'boolean',
                    'description' => 'true solo si el documento es un acta de nacimiento legible.',
                ],
                'motivo' => [
                    'type' => 'string',
                    'description' => 'Razón cuando no es un acta de nacimiento o no se puede leer; cadena vacía en caso contrario.',
                ],
                'datos' => $this->schemaDatos($texto),
            ],
            'required' => ['es_acta_nacimiento', 'motivo', 'datos'],
            'additionalProperties' => false,
        ];
    }

    private function schemaDatos(array $texto): array
    {
        $descripciones = [
            'lugar_nacimiento' => 'Lugar de nacimiento tal como aparece en el acta.',
            'lugar_registro' => 'Lugar de registro del acta tal como aparece.',
            'no_oficialia' => 'Número de oficialía del Registro Civil.',
            'nombre_certifica' => 'Nombre completo de la persona que certifica el acta.',
            'cargo' => 'Cargo de quien certifica (por ejemplo OFICIAL 03 DEL REGISTRO CIVIL).',
        ];

        $propiedades = [];
        foreach (self::CLAVES_DATO as $clave) {
            $propiedades[$clave] = isset($descripciones[$clave]) ? ['type' => 'string', 'description' => $descripciones[$clave]] : $texto;
        }

        return [
            'type' => 'object',
            'properties' => $propiedades,
            'required' => self::CLAVES_DATO,
            'additionalProperties' => false,
        ];
    }

    /**
     * La API de Responses entrega el texto en output[*].content[*]; concatena los
     * fragmentos de texto y decodifica el JSON con salida estructurada.
     */
    private function interpretarSalida(?array $salida): array
    {
        $texto = collect($salida['output'] ?? [])
            ->flatMap(fn ($elemento) => is_array($elemento['content'] ?? null) ? $elemento['content'] : [])
            ->filter(fn ($parte) => ($parte['type'] ?? null) === 'output_text')
            ->map(fn ($parte) => (string) ($parte['text'] ?? ''))
            ->implode('');

        $datos = json_decode($texto, true);

        if (! is_array($datos)) {
            throw new RuntimeException('La respuesta de OpenAI no contiene el esquema esperado.');
        }

        return $datos;
    }
}
