<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Models\Horarios\Asistencia;
use App\Models\Horarios\HorarioClase;
use App\Models\Sistema\Estado;
use App\Models\Sistema\Bitacora;
use Carbon\Carbon; // ¡Esencial para el manejo de tiempo!

class AsistenciaController extends Controller
{
    // =========================================================================
    // CONSTANTES Y HELPERS
    // =========================================================================

    // Reglas de negocio
    private const MINUTOS_ANTES_PERMITIDOS = 5;
    private const MINUTOS_DESPUES_PUNTUAL = 10;
    // Definamos un límite para tardanza (20 min después de la hora puntual)
    private const MINUTOS_DESPUES_TARDANZA = 20; 

    /**
     * Obtiene el ID de un estado por su nombre (cacheado)
     */
    private function getEstadoId($nombreEstado)
    {
        static $estadosCache = [];
        if (!isset($estadosCache[$nombreEstado])) {
            $estado = Estado::where('nombre', $nombreEstado)->first();
            $estadosCache[$nombreEstado] = $estado ? $estado->id_estado : null;
        }
        return $estadosCache[$nombreEstado];
    }

    /**
     * Calcula la distancia en Metros entre dos puntos GPS
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    /**
     * Obtiene la geovalla de la facultad desde el .env
     */
    private function getGeovallaFacultad()
    {
        return [
            'latitud' => (float) env('FACULTAD_LATITUD', -17.7833), // Coords. UAGRM (Aprox)
            'longitud' => (float) env('FACULTAD_LONGITUD', -63.1822),
            'radio' => (int) env('GEOVALLA_RADIO_METROS', 250) // Radio de 250 metros
        ];
    }

    // =========================================================================
    // MÉTODO A: REGISTRO POR BOTÓN (Formulario Digital)
    // =========================================================================

    /**
     * POST /api/asistencia/registrar
     * (CU9 - Método 1: Botón de Asistencia)
     */
    public function registrarAsistencia(Request $request)
    {
        return $this->procesarRegistro($request, false);
    }

    /**
     * POST /api/asistencia/registrar-qr
     * (CU9 - Método 2: Escaneo de QR de Aula)
     */
    public function registrarAsistenciaQR(Request $request)
    {
        return $this->procesarRegistro($request, true);
    }


    /**
     * Lógica central que procesa ambas solicitudes de asistencia
     */
    private function procesarRegistro(Request $request, bool $esQR)
    {
        // 1. Validación de Entrada
        $validationRules = [
            'id_horario_clase' => 'required|integer|exists:horario_clase,id_horario_clase',
            'coordenadas' => 'required|array',
            'coordenadas.latitud' => 'required|numeric|between:-90,90',
            'coordenadas.longitud' => 'required|numeric|between:-180,180',
        ];
        
        if ($esQR) {
            $validationRules['id_aula_escaneada'] = 'required|integer|exists:aula,id_aula';
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // 2. Obtener Datos Esenciales
            $usuario = Auth::user();
            $docente = $usuario->docente;
            $horaActual = Carbon::now();
            $fechaActual = $horaActual->toDateString();
            $diaSemanaActual = $horaActual->dayOfWeekIso; // Lunes=1, Sabado=6

            if (!$docente) {
                return response()->json(['success' => false, 'message' => 'Usuario no es un docente válido'], 403);
            }

            // 3. Obtener el HorarioClase con todas sus relaciones
            $horarioClase = HorarioClase::with(['bloqueHorario', 'dia', 'asignacionDocente'])
                ->find($request->id_horario_clase);

            // =================================================================
            // INICIO DE VALIDACIONES DE NEGOCIO (CU9)
            // =================================================================

            // V-1: Validar Pertenencia
            if ($horarioClase->asignacionDocente->id_docente !== $docente->cod_docente) {
                return response()->json(['success' => false, 'message' => 'Este horario no le pertenece'], 403);
            }

            // V-2: Validar Día Correcto
            if ($horarioClase->id_dia != $diaSemanaActual) {
                return response()->json(['success' => false, 'message' => 'Clase No Programada Hoy'], 422);
            }

            // V-3: Validar Ventana de Tiempo
            $horaInicioClase = Carbon::parse($horarioClase->bloqueHorario->hr_inicio);
            $ventanaInicio = $horaInicioClase->copy()->subMinutes(self::MINUTOS_ANTES_PERMITIDOS);
            $ventanaFinPuntual = $horaInicioClase->copy()->addMinutes(self::MINUTOS_DESPUES_PUNTUAL);
            $ventanaFinTardanza = $ventanaFinPuntual->copy()->addMinutes(self::MINUTOS_DESPUES_TARDANZA);

            if (!$horaActual->isBetween($ventanaInicio, $ventanaFinTardanza)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Fuera del Rango de Horario. Solo puede marcar desde ' . $ventanaInicio->format('H:i') . ' hasta ' . $ventanaFinTardanza->format('H:i')
                ], 422);
            }

            // V-4: Validar Geolocalización (GPS)
            $geovalla = $this->getGeovallaFacultad();
            $distancia = $this->haversineDistance(
                $request->coordenadas['latitud'],
                $request->coordenadas['longitud'],
                $geovalla['latitud'],
                $geovalla['longitud']
            );
            $distanciaRedondeada = round($distancia);

            if ($distancia > $geovalla['radio']) {
                return response()->json([
                    'success' => false, 
                    'message' => "Debe estar físicamente en la facultad. Se encuentra a {$distanciaRedondeada}m de distancia."
                ], 422);
            }

            // V-5: Validar Aula (Solo para QR)
            if ($esQR && $horarioClase->id_aula != $request->id_aula_escaneada) {
                return response()->json([
                    'success' => false, 
                    'message' => 'QR de Aula Incorrecto. Este QR no pertenece al aula de su clase programada.'
                ], 422);
            }

            // =================================================================
            // FIN DE VALIDACIONES
            // =================================================================

            // 4. Determinar Estado (Puntual o Tardanza)
            $tipoRegistro = $esQR ? 'QR_VALIDADO' : 'BOTON_GPS';
            $observacionBase = "Distancia: {$distanciaRedondeada}m. Método: {$tipoRegistro}.";

            if ($horaActual->isBetween($ventanaInicio, $ventanaFinPuntual)) {
                $idEstado = $this->getEstadoId('Presente');
                $observacion = "Asistencia puntual. " . $observacionBase;
                $mensajeExito = "✓ Asistencia registrada exitosamente";
            } else {
                $idEstado = $this->getEstadoId('Tardanza');
                $observacion = "Registro con tardanza. " . $observacionBase;
                $mensajeExito = "⚠ Asistencia registrada con tardanza";
            }

            // 5. Guardar Asistencia
            // La V-6 (Validación de Duplicado) se maneja automáticamente por la
            // restricción 'uq_asistencia_clase_dia' (id_horario_clase, fecha_registro)
            $asistencia = Asistencia::create([
                'id_asignacion_docente' => $horarioClase->id_asignacion_docente,
                'id_horario_clase' => $horarioClase->id_horario_clase,
                'id_estado' => $idEstado,
                'fecha_registro' => $fechaActual,
                'hora_registro' => $horaActual->toTimeString(),
                'tipo_registro' => $tipoRegistro,
                'observacion' => $observacion,
            ]);

            // 6. Registrar en Bitácora
            $metodoRegistro = $esQR ? 'por QR' : 'por GPS';
            Bitacora::registrar(
                'REGISTRAR_ASISTENCIA',
                "Docente {$docente->cod_docente} registró asistencia {$metodoRegistro} para clase ID {$horarioClase->id_horario_clase}",
                $usuario->id_usuario
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $mensajeExito,
                'data' => $asistencia
            ], 201);

        } catch (QueryException $e) {
            DB::rollBack();
            // Error 23505 es violación de restricción UNIQUE
            if ($e->getCode() == '23505') {
                return response()->json(['success' => false, 'message' => 'Asistencia Ya Registrada para esta clase hoy'], 409);
            }
            return response()->json(['success' => false, 'message' => 'Error de base de datos', 'error' => $e->getMessage()], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error interno del servidor', 'error' => $e->getMessage()], 500);
        }
    }
}
