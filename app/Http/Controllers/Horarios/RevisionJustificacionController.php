<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Horarios\Justificacion;
use App\Models\Sistema\Estado;
use App\Models\Sistema\Bitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RevisionJustificacionController extends Controller
{
    /**
     * Obtiene el ID de un estado por su nombre
     */
    private function getEstadoId($nombreEstado)
    {
        static $estadosCache = [];
        if (!isset($estadosCache[$nombreEstado])) {
            $estado = Estado::where('nombre', $nombreEstado)->first();
            $estadosCache[$nombreNnombreEstado] = $estado ? $estado->id_estado : null;
        }
        return $estadosCache[$nombreEstado];
    }

    /**
     * CU20 (Admin): Listar justificaciones Pendientes
     * GET /api/admin/justificaciones/pendientes
     */
    public function indexPendientes(Request $request)
    {
        $idEstadoPendiente = $this->getEstadoId('Pendiente');
        
        $pendientes = Justificacion::with([
            // Cargamos toda la info que el admin necesita ver
            'estado',
            'asistencia.asignacionDocente.docente.perfil',
            'asistencia.horarioClase.dia',
            'asistencia.horarioClase.bloqueHorario',
            'asistencia.horarioClase.asignacionDocente.materiaGrupo.materia'
        ])
        ->where('id_estado', $idEstadoPendiente)
        ->orderBy('fecha_justificacion', 'asc')
        ->get();
            
        return response()->json(['success' => true, 'data' => $pendientes]);
    }

    /**
     * CU20 (Admin): Aprobar o Rechazar una justificación
     * POST /api/admin/justificaciones/{id}/revisar
     */
    public function revisar(Request $request, $idJustificacion)
    {
        $validator = Validator::make($request->all(), [
            'decision' => 'required|in:aprobada,rechazada',
            'respuesta_admin' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $justificacion = Justificacion::with('asistencia')->findOrFail($idJustificacion);
            
            $idEstadoAprobada = $this->getEstadoId('Aprobada');
            $idEstadoRechazada = $this->getEstadoId('Rechazada');
            $idEstadoJustificado = $this->getEstadoId('Ausente Justificado'); // ¡Importante!

            // V-1: Validar que no esté ya procesada
            if ($justificacion->id_estado == $idEstadoAprobada || $justificacion->id_estado == $idEstadoRechazada) {
                return response()->json(['success' => false, 'message' => 'Esta justificación ya fue procesada.'], 422);
            }

            if ($request->decision == 'aprobada') {
                $idEstadoJustificacion = $idEstadoAprobada;
                
                // V-2: IMPORTANTE: Actualizar también la asistencia original
                if ($idEstadoJustificado) {
                    $justificacion->asistencia->update([
                        'id_estado' => $idEstadoJustificado,
                    ]);
                }
                
            } else { // 'rechazada'
                $idEstadoJustificacion = $idEstadoRechazada;
                // No se hace nada a la asistencia, sigue como "Ausente".
            }

            // V-3: Actualizar la justificación
            $justificacion->update([
                'id_estado' => $idEstadoJustificacion,
                'respuesta_admin' => $request->respuesta_admin,
                'revisado_por_id_usuario' => Auth::id(),
                // 'fecha_revision' se actualiza automáticamente por ser UPDATED_AT
            ]);

            // V-4: Registrar en Bitácora
            Bitacora::registrar(
                'REVISION_JUSTIFICACION', 
                "Admin ID ".Auth::id()." revisó justificación ID {$idJustificacion}. Decisión: {$request->decision}", 
                Auth::id()
            );
            
            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => "Justificación marcada como {$request->decision}."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al procesar la revisión', 'error' => $e->getMessage()], 500);
        }
    }
}