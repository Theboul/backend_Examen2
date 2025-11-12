<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Horarios\Asistencia;
use App\Models\Sistema\Estado;
use Illuminate\Support\Facades\Validator;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsistenciaExport;


class ReporteAsistenciaController extends Controller
{
    /**
     * CU11: Generar Reporte de Asistencia
     * GET /api/reportes/asistencia
     */
    public function generarReporte(Request $request)
    {
        // --- 1. VALIDACIÓN DE FILTROS ---
        $validator = Validator::make($request->all(), [
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'id_docente' => 'nullable|integer|exists:docente,cod_docente',
            'id_materia' => 'nullable|integer|exists:materia,id_materia',
            'id_grupo' => 'nullable|integer|exists:grupo,id_grupo',
            'exportar' => 'nullable|in:pdf,excel', // El switch dinámico/estático
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Filtros inválidos', 'errors' => $validator->errors()], 422);
        }

        try {
            // --- 2. CONSTRUCCIÓN DE LA CONSULTA BASE ---
            $query = $this->construirConsultaReporte($request);

            // --- 3. OBTENER DATOS DETALLADOS ---
            $asistencias = $query->with([
                'estado',
                'asignacionDocente.docente.perfil',
                'asignacionDocente.materiaGrupo.materia',
                'asignacionDocente.materiaGrupo.grupo',
                'horarioClase.dia',
                'horarioClase.bloqueHorario'
            ])
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('hora_registro', 'desc')
            ->get();

            if ($asistencias->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Sin Registros de Asistencia para los filtros seleccionados'], 404);
            }

            // --- 4. CALCULAR ESTADÍSTICAS ---
            $estadisticas = $this->calcularEstadisticas($asistencias);

            // --- 5. DECIDIR EL FORMATO DE SALIDA ---
            if ($request->filled('exportar')) {
                
                // Preparamos los datos para la vista estática
                $datosReporte = [
                    'filtros' => $request->all(), // Para mostrar en la cabecera del reporte
                    'estadisticas' => $estadisticas,
                    'asistencias' => $asistencias
                ];

                if ($request->exportar == 'pdf') {
                    // CÓDIGO PDF (ACTUALIZADO)
                    $pdf = Pdf::loadView('reportes.asistencia_pdf', $datosReporte)
                             ->setPaper('a4', 'landscape'); // Horizontal para tablas
                    
                    return $pdf->download('reporte_asistencia.pdf');
                }
                
                if ($request->exportar == 'excel') {
                    // CÓDIGO EXCEL (ACTUALIZADO)
                    return Excel::download(new AsistenciaExport($datosReporte), 'reporte_asistencia.xlsx');
                }
            }
            
            // SALIDA DINÁMICA (JSON para la pantalla)
            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente',
                'filtros_aplicados' => $request->all(),
                'estadisticas' => $estadisticas,
                'data_detallada' => $asistencias // Los registros detallados para la grilla
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar el reporte', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Construye la consulta base aplicando los filtros dinámicos.
     * (Esta función no cambia, ya era correcta)
     */
    private function construirConsultaReporte(Request $request)
    {
        $query = Asistencia::query();

        // FILTRO OBLIGATORIO: GESTIÓN
        $query->whereHas('asignacionDocente.materiaGrupo', function ($q) use ($request) {
            $q->where('id_gestion', $request->id_gestion);
        });

        // FILTRO OPCIONAL: RANGO DE FECHAS
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_registro', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // FILTRO OPCIONAL: DOCENTE
        if ($request->filled('id_docente')) {
            $query->whereHas('asignacionDocente', function ($q) use ($request) {
                $q->where('id_docente', $request->id_docente);
            });
        }

        // FILTRO OPCIONAL: MATERIA
        if ($request->filled('id_materia')) {
            $query->whereHas('asignacionDocente.materiaGrupo', function ($q) use ($request) {
                $q->where('id_materia', $request->id_materia);
            });
        }

        // FILTRO OPCIONAL: GRUPO
        if ($request->filled('id_grupo')) {
            $query->whereHas('asignacionDocente.materiaGrupo', function ($q) use ($request) {
                $q->where('id_grupo', $request->id_grupo);
            });
        }

        return $query;
    }

    /**
     * Calcula los totales y porcentajes para el reporte.
     * (Esta función no cambia, ya era correcta)
     */
    private function calcularEstadisticas($asistencias)
    {
        $totalRegistros = $asistencias->count();
        if ($totalRegistros == 0) {
            return ['total_clases_registradas' => 0];
        }

        $conteoPorEstado = $asistencias->groupBy('estado.nombre')->map->count();

        $presente = $conteoPorEstado->get('Presente', 0);
        $tardanza = $conteoPorEstado->get('Tardanza', 0);
        $ausente = $conteoPorEstado->get('Ausente', 0);
        $justificado = $conteoPorEstado->get('Ausente Justificado', 0);

        $totalAsistenciasEfectivas = $presente + $tardanza + $justificado;

        return [
            'total_clases_registradas' => $totalRegistros,
            'total_presente' => $presente,
            'total_tardanza' => $tardanza,
            'total_ausente_injustificado' => $ausente,
            'total_ausente_justificado' => $justificado,
            'porcentaje_asistencia_efectiva' => round(($totalAsistenciasEfectivas / $totalRegistros) * 100, 2),
            'porcentaje_ausentismo_real' => round(($ausente / $totalRegistros) * 100, 2),
        ];
    }
}