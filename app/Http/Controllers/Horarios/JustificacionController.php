<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate_Support_Facades_Storage; // Para manejar la subida de archivos
use App\Models\Horarios\Asistencia;
use App\Models\Horarios\Justificacion;
use App\Models\Sistema\Estado;
use App\Models\Sistema\Bitacora;

class JustificacionController extends Controller
{
    // Límite de 7 días para justificar
    private const DIAS_LIMITE_PARA_JUSTIFICAR = 7;

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
     * CU20 (Docente): Enviar una justificación para una ausencia
     * POST /api/asistencia/{id}/justificar
     */
    public function store(Request $request, $idAsistencia)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|max:500',
            // Validar PDF o Imagen, máximo 5MB (5120 KB)
            'documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $asistencia = Asistencia::with('asignacionDocente')->findOrFail($idAsistencia);
            $docente = Auth::user()->docente;

            // V-1: Validar Pertenencia
            if ($asistencia->asignacionDocente->id_docente !== $docente->cod_docente) {
                return response()->json(['success' => false, 'message' => 'No autorizado. Esta ausencia no le pertenece.'], 403);
            }

            // V-2: Validar Plazo (Excepción: Plazo vencido)
            if ($asistencia->fecha_registro->diffInDays(now()) > self::DIAS_LIMITE_PARA_JUSTIFICAR) {
                return response()->json(['success' => false, 'message' => 'Plazo vencido. No puede justificar una ausencia de más de 7 días.'], 422);
            }

            // V-3: Validar si ya hay una "Pendiente" (Excepción: Ya justificada)
            $idEstadoPendiente = $this->getEstadoId('Pendiente');
            $pendiente = Justificacion::where('id_asistencia', $idAsistencia)
                ->where('id_estado', $idEstadoPendiente)
                ->exists();
                
            if ($pendiente) {
                return response()->json(['success' => false, 'message' => 'Ya tiene una justificación pendiente de revisión para esta ausencia.'], 422);
            }

            // V-4: Manejar subida de archivo
            $rutaArchivo = null;
            if ($request->hasFile('documento')) {
                // El archivo se guarda en 'storage/app/public/justificaciones'
                $ruta = $request->file('documento')->store('public/justificaciones');
                // Guardamos la URL pública para que el front la pueda consumir
                $rutaArchivo = Storage::url($ruta);
            }

            // V-5: Crear la justificación
            $justificacion = Justificacion::create([
                'id_asistencia' => $idAsistencia,
                'id_estado' => $idEstadoPendiente,
                'motivo' => $request->motivo,
                'documento_adjunto' => $rutaArchivo,
            ]);

            // V-6: Registrar en Bitácora
            Bitacora::registrar(
                'JUSTIFICACION_ENVIADA', 
                "Docente {$docente->cod_docente} envió justificación para asistencia ID {$idAsistencia}", 
                Auth::id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Justificación enviada exitosamente. En espera de revisión.',
                'data' => $justificacion
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al enviar la justificación', 'error' => $e->getMessage()], 500);
        }
    }
}