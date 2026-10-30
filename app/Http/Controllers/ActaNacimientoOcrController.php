<?php

namespace App\Http\Controllers;

use App\Integrations\OpenaiVisionClient;
use App\Models\Auditoria;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Extrae con IA (OpenAI) los datos del acta de nacimiento adjunta en una
 * solicitud y los devuelve mapeados a los campos de la captura administrativa
 * para que el servidor público los verifique antes de guardar. Nunca
 * persiste respuestas: solo propone el prellenado.
 */
class ActaNacimientoOcrController extends Controller
{
    private const CLAVE_CAPTURA = 'datos_acta_nacimiento';

    private const MIME_PERMITIDOS = ['application/pdf', 'image/jpeg', 'image/png'];

    private const TAMANO_MAXIMO_BYTES = 10 * 1024 * 1024;

    public function __invoke(Request $request, Solicitud $solicitud): JsonResponse
    {
        $captura = DB::table('formulario_capturas_administrativas')
            ->where('formulario_version_id', $solicitud->formulario_version_id)
            ->where('clave', self::CLAVE_CAPTURA)
            ->where('activo', true)
            ->first();
        abort_unless($captura, 404, 'Esta solicitud no tiene captura asociada al acta de nacimiento.');
        abort_unless(filled($captura->campo_fuente_id), 404, 'La captura del acta no está vinculada a un documento.');

        $metadata = $this->metadataArchivoActa($solicitud, $captura->campo_fuente_id);
        $contenido = Storage::disk('local')->get($metadata['storage_key']);
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contenido) ?: $metadata['mime_type'];

        abort_unless(in_array($mimeType, self::MIME_PERMITIDOS, true), 422, 'El acta adjunta no es compatible con la extracción automática (formatos admitidos: PDF, JPG o PNG). Solicita al ciudadano reenviarla en alguno de esos formatos o captura los datos manualmente.');
        abort_unless(strlen($contenido) <= self::TAMANO_MAXIMO_BYTES, 422, 'El acta adjunta supera el tamaño máximo de 10 MB para la extracción automática.');

        abort_unless(filled(config('services.openai.api_key')), 503, 'La extracción con OCR no está disponible porque falta configurar la credencial de OPENAI_API_KEY.');

        try {
            $resultado = (new OpenaiVisionClient)->extraerDatosActa($contenido, $mimeType);
        } catch (Throwable $excepcion) {
            report($excepcion);
            abort(502, 'El servicio de extracción de datos no está disponible en este momento. Intenta de nuevo o captura los datos manualmente.');
        }

        $extracto = is_array($resultado['datos'] ?? null) ? $resultado['datos'] : [];
        $campos = DB::table('formulario_captura_administrativa_campos')
            ->where('captura_administrativa_id', $captura->id)
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $propuestos = $resultado['es_acta_nacimiento'] ? $this->mapearCampos($campos, $extracto) : [];

        Auditoria::registrar(
            $request,
            'ACTA_OCR_EXTRAIDA',
            'SOLICITUD',
            $solicitud->id,
            null,
            [
                'es_acta_nacimiento' => $resultado['es_acta_nacimiento'],
                'campos_propuestos' => count($propuestos),
            ]
        );

        return response()->json([
            'es_acta_nacimiento' => $resultado['es_acta_nacimiento'],
            'motivo' => $resultado['motivo'],
            'datos' => $propuestos,
        ]);
    }

    private function metadataArchivoActa(Solicitud $solicitud, string $campoFuenteId): array
    {
        $registro = DB::table('solicitud_respuestas')
            ->where('solicitud_id', $solicitud->id)
            ->where('campo_id', $campoFuenteId)
            ->value('valor');
        $metadata = json_decode((string) $registro, true) ?: [];

        abort_unless(
            isset($metadata['storage_key']) && filled($metadata['storage_key']) && Storage::disk('local')->exists($metadata['storage_key']),
            404,
            'No se encontró el archivo del acta de nacimiento adjunto por el ciudadano.'
        );

        return $metadata + ['mime_type' => ''];
    }

    /**
     * Convierte los datos de la IA en un mapa campo_captura_id => valor listo
     * para rellenar los inputs `respuestas[campo_id]` del formulario.
     *
     * Los campos tipo DOMICILIO se excluyen deliberadamente: el domicilio se
     * captura manualmente con el componente validado contra la capa de datos
     * del municipio (colonias y códigos postales oficiales).
     */
    private function mapearCampos($campos, array $extracto): array
    {
        $propuestos = [];

        foreach ($campos as $campo) {
            $valor = $extracto[$campo->clave] ?? null;
            if ($campo->tipo === 'DOMICILIO') {
                continue;
            }

            if (is_string($valor) || is_numeric($valor)) {
                $texto = trim((string) $valor);
                if ($texto !== '') {
                    $propuestos[$campo->id] = mb_substr($texto, 0, 500);
                }
            }
        }

        return $propuestos;
    }
}
