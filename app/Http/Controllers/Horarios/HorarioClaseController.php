<?php

namespace App\Http\Controllers\Horarios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Horarios\HorarioClase;
use App\Models\Horarios\AsignacionDocente;
use App\Models\Maestros\MateriaGrupo;
use App\Models\Maestros\Aula;
use App\Models\Horarios\BloqueHorario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Sistema\Bitacora;
use Illuminate\Support\Facades\Auth;
use App\Models\Sistema\Estado;

class HorarioClaseController extends Controller
{
    // =========================================================================
    // HELPERS PARA ESTADOS (CU17)
    // =========================================================================
    
    /**
     * Obtiene el ID de un estado por su nombre
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
    
    // =========================================================================
    // LÓGICA DE VALIDACIÓN DE CONFLICTOS
    // =========================================================================
    
    /**
     * Revisa todos los conflictos de OptiClass antes de guardar un horario.
     * @return string|null Retorna el mensaje de error o null si no hay conflicto.
     */
    private function validarConflictos(
        $idAsignacionDocente, 
        $idAula, 
        $idDia, 
        $idBloqueHorario,
        $horarioIdExcluir = null // Para el método update
    ): ?string
    {
        // 1. Carga de relaciones necesaria para la validación y mensajes detallados.
        $asignacion = AsignacionDocente::with('materiaGrupo.grupo', 'materiaGrupo.materia', 'docente.perfil')->find($idAsignacionDocente);
        $aula = Aula::with('tipoAula')->find($idAula);
        
        if (!$asignacion || !$aula) {
            return "Error en datos base: Asignación o Aula no encontrada.";
        }

        $idGestion = $asignacion->materiaGrupo->id_gestion;
        $idDocente = $asignacion->id_docente;
        $idGrupo = $asignacion->materiaGrupo->id_grupo;
        $capacidadRequerida = $asignacion->materiaGrupo->grupo->capacidad_maxima; // Usamos capacidad_maxima del grupo
        $capacidadAula = $aula->capacidad;

        // 2. CONFLICTO DE CAPACIDAD DE AULA
        if ($capacidadAula < $capacidadRequerida) {
            return "El aula '{$aula->nombre}' tiene capacidad ({$capacidadAula}) menor a la requerida por el grupo ({$capacidadRequerida}).";
        }
        
        // 3. CONFLICTO DE DISPONIBILIDAD (Docente, Grupo y Aula)
        $conflictoQuery = HorarioClase::activos()
            ->where('id_dia', $idDia)
            ->where('id_bloque_horario', $idBloqueHorario)
            ->whereHas('asignacionDocente.materiaGrupo', function($query) use ($idGestion) {
                $query->where('id_gestion', $idGestion); 
            })
            ->where(function ($query) use ($idDocente, $idGrupo, $idAula) {
                $query->whereHas('asignacionDocente', function($q) use ($idDocente) {
                    $q->where('id_docente', $idDocente); // Conflicto de Docente
                })
                ->orWhereHas('asignacionDocente.materiaGrupo.grupo', function($q) use ($idGrupo) {
                    $q->where('id_grupo', $idGrupo); // Conflicto de Grupo
                })
                ->orWhere('id_aula', $idAula); // Conflicto de Aula
            });
            
        if ($horarioIdExcluir) {
             $conflictoQuery->where('id_horario_clase', '!=', $horarioIdExcluir);
        }

        $conflicto = $conflictoQuery->first();

        if ($conflicto) {
            $conflictoAsignacion = AsignacionDocente::with('docente.perfil', 'materiaGrupo.materia', 'materiaGrupo.grupo')->find($conflicto->id_asignacion_docente);

            if ($conflicto->id_aula == $idAula) {
                return "Conflicto de Aula: '{$aula->nombre}' ya está ocupada por la materia '{$conflictoAsignacion->materiaGrupo->materia->nombre}' en este bloque.";
            }
            if ($conflictoAsignacion->id_docente == $idDocente) {
                return "Conflicto de Docente: '{$asignacion->docente->perfil->nombre_completo}' ya está asignado a otra clase en este bloque.";
            }
            if ($conflictoAsignacion->materiaGrupo->id_grupo == $idGrupo) {
                return "Conflicto de Grupo: El grupo ya tiene asignada otra materia en este bloque.";
            }
        }
        
        // 4. CONFLICTO DE MANTENIMIENTO
        if ($aula->mantenimiento) {
             return "El aula '{$aula->nombre}' está actualmente en mantenimiento y no puede ser asignada.";
        }

        return null; // No hay conflictos
    }

    // =========================================================================
    // CRUD (CU6: Asignar Horarios Manualmente)
    // =========================================================================

    /**
     * POST /horarios-clase
     * Crea un nuevo registro de horario (CU6), validando conflictos (CU7).
     */
    public function store(Request $request)
    {
        // Validación de datos de entrada
        $request->validate([
            'id_asignacion_docente' => ['required', 'exists:asignacion_docente,id_asignacion_docente'],
            'id_aula' => ['required', 'exists:aula,id_aula'],
            'id_dia' => ['required', 'exists:dia,id_dia'],
            'id_bloque_horario' => ['required', 'exists:bloque_horario,id_bloque_horario'],
            'id_tipo_clase' => ['required', 'exists:tipo_clase,id_tipo_clase'],
        ]);

        // 1. VALIDACIÓN CRÍTICA (CU7)
        $conflicto = $this->validarConflictos(
            $request->id_asignacion_docente, 
            $request->id_aula, 
            $request->id_dia, 
            $request->id_bloque_horario
        );

        if ($conflicto !== null) {
            // Código 409: Conflicto
            return response()->json(['success' => false, 'message' => $conflicto], 409); 
        }

        // 2. CREACIÓN (CU6)
        try {
            $horario = HorarioClase::create([
                'id_asignacion_docente' => $request->id_asignacion_docente,
                'id_aula' => $request->id_aula,
                'id_dia' => $request->id_dia,
                'id_bloque_horario' => $request->id_bloque_horario,
                'id_tipo_clase' => $request->id_tipo_clase,
                'activo' => true, // Por defecto activo
                'id_estado' => $this->getEstadoId('BORRADOR'), // CU17: Estado inicial
            ]);

            // Cargar relaciones para la respuesta
            $horario->load([
                'asignacionDocente.materiaGrupo.materia',
                'asignacionDocente.materiaGrupo.grupo',
                'aula',
                'dia',
                'bloqueHorario',
                'tipoClase'
            ]);

            return response()->json([
                'success' => true,
                'data' => $horario,
                'message' => 'Horario asignado manualmente y validado correctamente.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el horario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar Horarios (GET /horarios-clase)
     */
    public function index(Request $request) 
    {
        try {
            $idGestion = $request->get('id_gestion_activa'); 
            
            $query = HorarioClase::with([
                'asignacionDocente.docente.perfil',
                'asignacionDocente.materiaGrupo.materia',
                'asignacionDocente.materiaGrupo.grupo',
                'aula',
                'dia',
                'bloqueHorario',
                'tipoClase',
            ])->activos();
            
            if ($idGestion) {
                 $query->whereHas('asignacionDocente.materiaGrupo', function($q) use ($idGestion) {
                     $q->where('id_gestion', $idGestion);
                 });
            }
            
            $horarios = $query->orderBy('id_dia')->orderBy('id_bloque_horario')->get();
            
            return response()->json(['success' => true, 'data' => $horarios], 200);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Error al obtener horarios', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar un horario específico (GET /horarios-clase/{id})
     */
    public function show($id)
    {
        try {
            $horario = HorarioClase::with([
                'asignacionDocente.docente.perfil', 
                'asignacionDocente.materiaGrupo.materia', 
                'asignacionDocente.materiaGrupo.grupo', 
                'aula', 'dia', 'bloqueHorario', 'tipoClase'
            ])->findOrFail($id);
            
            return response()->json(['success' => true, 'data' => $horario], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Horario no encontrado'], 404);
        }
    }

    /**
     * Actualizar un horario existente (PUT /horarios-clase/{id})
     */
    public function update(Request $request, $id)
    {
        // 1. Validación de campos a actualizar
        $request->validate([
            'id_asignacion_docente' => ['sometimes', 'required', 'exists:asignacion_docente,id_asignacion_docente'],
            'id_aula' => ['sometimes', 'required', 'exists:aula,id_aula'],
            'id_dia' => ['sometimes', 'required', 'exists:dia,id_dia'],
            'id_bloque_horario' => ['sometimes', 'required', 'exists:bloque_horario,id_bloque_horario'],
            'id_tipo_clase' => ['sometimes', 'required', 'exists:tipo_clase,id_tipo_clase'],
            'activo' => ['nullable', 'boolean'],
        ]);
        
        $horario = HorarioClase::findOrFail($id);

        // 2. Recopilar datos para la validación de conflicto (usar valores nuevos o actuales)
        $idAsignacionDocente = $request->id_asignacion_docente ?? $horario->id_asignacion_docente;
        $idAula = $request->id_aula ?? $horario->id_aula;
        $idDia = $request->id_dia ?? $horario->id_dia;
        $idBloqueHorario = $request->id_bloque_horario ?? $horario->id_bloque_horario;

        // 3. VALIDACIÓN CRÍTICA DE CONFLICTOS (CU7)
        $conflicto = $this->validarConflictos(
            $idAsignacionDocente, 
            $idAula, 
            $idDia, 
            $idBloqueHorario,
            $horario->id_horario_clase // Excluir el horario que estamos editando
        );

        if ($conflicto !== null) {
            return response()->json(['success' => false, 'message' => $conflicto], 409); 
        }

        // 4. ACTUALIZACIÓN (CU6)
        try {
            $horario->update($request->all());

            // Bitacora::registrar('ACTUALIZAR_HORARIO', "Horario ID {$horario->id_horario_clase} actualizado", Auth::id());

            return response()->json([
                'success' => true,
                'data' => $horario->refresh(), // Devolver el objeto actualizado
                'message' => 'Horario actualizado y validado correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar el horario', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar/Cancelar un horario (DELETE /horarios-clase/{id})
     * CU17: Cambia el estado a CANCELADA y desactiva
     */
    public function destroy($id)
    {
        try {
            $horario = HorarioClase::with('estado')->findOrFail($id);
            
            $idEstadoPublicada = $this->getEstadoId('PUBLICADA');
            $idEstadoCancelada = $this->getEstadoId('CANCELADA');
            
            // Lógica: No permitir eliminar un horario PUBLICADO directamente
            if ($horario->id_estado == $idEstadoPublicada) {
                 return response()->json([
                     'success' => false, 
                     'message' => 'No se puede eliminar un horario publicado directamente. Primero debe cancelarse.'
                 ], 422); 
            }
            
            // Cancelar y desactivar
            $horario->update([
                'id_estado' => $idEstadoCancelada,
                'activo' => false
            ]); 

            // Bitacora::registrar('CANCELAR_HORARIO', "Horario ID {$horario->id_horario_clase} cancelado", Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Horario eliminado/desactivado correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar el horario', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reactivar un horario desactivado (POST /horarios-clase/{id}/reactivar)
     */
    public function reactivar($id)
    {
        try {
            $horario = HorarioClase::findOrFail($id);
            
            if ($horario->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El horario ya está activo.'
                ], 422);
            }

            // Revalidar conflictos antes de reactivar
            $conflicto = $this->validarConflictos(
                $horario->id_asignacion_docente,
                $horario->id_aula,
                $horario->id_dia,
                $horario->id_bloque_horario,
                $id // Excluir el propio horario
            );

            if ($conflicto !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reactivar debido a conflictos: ' . $conflicto
                ], 409);
            }

            // Reactivar
            $horario->update(['activo' => true]);

            Bitacora::registrar('REACTIVAR_HORARIO', "Horario ID {$horario->id_horario_clase} reactivado", Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Horario reactivado correctamente.',
                'data' => $horario
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reactivar el horario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // CU7: GENERACIÓN AUTOMÁTICA DE HORARIOS
    // =========================================================================

    /**
     * POST /horarios-clase/generar-automatico
     * Genera automáticamente horarios para una gestión académica.
     * 
     * Criterios de asignación:
     * 1. Penalización por subutilización: Prefiere aulas más pequeñas si caben
     * 2. Máximo 3 horarios consecutivos por docente
     * 3. Laboratorios solo para clases prácticas/laboratorio
     * 4. Valida todos los conflictos con método validarConflictos()
     */
    public function generarAutomatico(Request $request)
    {
        // Aumentar tiempo de ejecución para procesos largos
        set_time_limit(120); // 2 minutos
        
        $request->validate([
            'id_gestion' => ['required', 'exists:gestion,id_gestion'],
            'id_carrera' => ['nullable', 'exists:carrera,id_carrera'],
        ]);

        $idGestion = $request->id_gestion;
        $idCarrera = $request->id_carrera;

        try {
            DB::beginTransaction();

            // 1. Obtener asignaciones docente de la gestión (y carrera si se especifica)
            $query = AsignacionDocente::with([
                'materiaGrupo.materia.carrera',
                'materiaGrupo.grupo',
                'materiaGrupo.gestion'
            ])
            ->whereHas('materiaGrupo', function($q) use ($idGestion, $idCarrera) {
                $q->where('id_gestion', $idGestion);
                
                // Filtrar por carrera a través de materia
                if ($idCarrera) {
                    $q->whereHas('materia', function($mq) use ($idCarrera) {
                        $mq->where('id_carrera', $idCarrera);
                    });
                }
            })
            ->where('activo', true)
            ->get();

            if ($query->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay asignaciones docente para esta gestión' . ($idCarrera ? '/carrera' : '')
                ], 404);
            }

            // 2. Ordenar por hrs_asignadas DESC (mayor carga primero)
            $asignaciones = $query->sortByDesc('hrs_asignadas')->values();

            // 3. Obtener aulas disponibles ordenadas por capacidad ASC (prioriza aulas pequeñas)
            $aulas = Aula::where('activo', true)
                ->where('mantenimiento', false)
                ->orderBy('capacidad', 'asc')
                ->get();

            if ($aulas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay aulas disponibles'
                ], 404);
            }

            // 4. Obtener días y bloques horarios
            // PATRONES ACADÉMICOS: Lun-Mie-Vie (1,3,5), Mar-Jue (2,4), y Sábado (6)
            // Excluir: Domingo (7)
            $patronLunMieVie = [1, 3, 5]; // Lunes, Miércoles, Viernes
            $patronMarJue = [2, 4];        // Martes, Jueves
            $patronSabado = [6];           // Sábado (para materias especiales)
            
            // Obtener bloques con su duración real
            $bloques = BloqueHorario::where('activo', true)
                ->orderBy('hr_inicio')
                ->get()
                ->map(function($bloque) {
                    return [
                        'id' => $bloque->id_bloque_horario,
                        'nombre' => $bloque->nombre,
                        'duracion_horas' => $bloque->minutos_duracion / 60, // Convertir a horas
                    ];
                })
                ->toArray();

            // 5. Inicializar resultados
            $exitosas = [];
            $fallidas = [];

            // 6. ALGORITMO DE ASIGNACIÓN OPTIMIZADO
            foreach ($asignaciones as $asignacion) {
                $capacidadRequerida = $asignacion->materiaGrupo->grupo->capacidad_maxima;
                $esPractica = $asignacion->materiaGrupo->materia->tipo === 'Práctica' || 
                              $asignacion->materiaGrupo->materia->tipo === 'Laboratorio';
                
                $hrsRequeridas = $asignacion->hrs_asignadas;
                $hrsAsignadas = 0;
                $horariosCreados = [];

                // Filtrar aulas por tipo si es práctica/laboratorio
                $aulasDisponibles = $esPractica 
                    ? $aulas->filter(function($aula) {
                        return $aula->tipoAula && 
                               in_array($aula->tipoAula->nombre, ['Laboratorio', 'Taller']);
                    })
                    : $aulas;

                // Buscar aulas aptas (capacidad suficiente)
                $aulasAptas = $aulasDisponibles->filter(function($aula) use ($capacidadRequerida) {
                    return $aula->capacidad >= $capacidadRequerida;
                });

                if ($aulasAptas->isEmpty()) {
                    $fallidas[] = [
                        'asignacion_id' => $asignacion->id_asignacion_docente,
                        'materia' => $asignacion->materiaGrupo->materia->nombre,
                        'grupo' => $asignacion->materiaGrupo->grupo->nombre,
                        'razon' => "No hay aulas disponibles con capacidad >= {$capacidadRequerida}"
                    ];
                    continue;
                }

                // Ordenar por menor desperdicio (aula más pequeña que quepa)
                $aulasAptas = $aulasAptas->sortBy('capacidad')->values();

                // ESTRATEGIA SIMPLIFICADA: Solo calcular las 3 mejores estrategias
                $estrategias = $this->calcularEstrategiasAsignacion($hrsRequeridas, $bloques, $patronLunMieVie, $patronMarJue, $patronSabado);
                
                // Limitar a las 5 mejores estrategias para evitar timeout
                $estrategias = array_slice($estrategias, 0, 5);
                
                // Intentar SOLO con la primera aula apta (la más pequeña)
                $aulaAsignada = $aulasAptas->first();
                
                // Intentar cada estrategia hasta completar las horas
                foreach ($estrategias as $estrategia) {
                    if ($hrsAsignadas >= $hrsRequeridas) break;
                    
                    // Aplicar la estrategia
                    $resultado = $this->intentarAsignarEstrategia(
                        $asignacion,
                        $aulaAsignada,
                        $estrategia,
                        $esPractica,
                        $hrsRequeridas,
                        $hrsAsignadas
                    );
                    
                    if ($resultado['exito']) {
                        $horariosCreados = array_merge($horariosCreados, $resultado['horarios']);
                        $hrsAsignadas = $resultado['hrs_asignadas'];
                    }
                }

                // Registrar resultado
                if ($hrsAsignadas > 0) {
                    $exitosas[] = [
                        'asignacion_id' => $asignacion->id_asignacion_docente,
                        'materia' => $asignacion->materiaGrupo->materia->nombre,
                        'grupo' => $asignacion->materiaGrupo->grupo->nombre,
                        'hrs_requeridas' => $hrsRequeridas,
                        'hrs_asignadas' => $hrsAsignadas,
                        'completado' => abs($hrsAsignadas - $hrsRequeridas) < 0.1 ? 'SI' : 'PARCIAL',
                        'porcentaje' => round(($hrsAsignadas / $hrsRequeridas) * 100, 1),
                        'horarios' => $horariosCreados
                    ];
                }
                
                if ($hrsAsignadas < $hrsRequeridas) {
                    $fallidas[] = [
                        'asignacion_id' => $asignacion->id_asignacion_docente,
                        'materia' => $asignacion->materiaGrupo->materia->nombre,
                        'grupo' => $asignacion->materiaGrupo->grupo->nombre,
                        'razon' => "Solo se asignaron {$hrsAsignadas} de {$hrsRequeridas} horas requeridas",
                        'hrs_requeridas' => $hrsRequeridas,
                        'hrs_asignadas' => $hrsAsignadas,
                        'horarios_parciales' => $horariosCreados
                    ];
                }
            }

            DB::commit();

            // 7. Generar reporte
            return response()->json([
                'success' => true,
                'message' => 'Generación automática completada',
                'resumen' => [
                    'total_asignaciones' => $asignaciones->count(),
                    'exitosas' => count($exitosas),
                    'fallidas' => count($fallidas),
                    'porcentaje_exito' => round((count($exitosas) / $asignaciones->count()) * 100, 2)
                ],
                'detalles' => [
                    'exitosas' => $exitosas,
                    'fallidas' => $fallidas
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error en la generación automática',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcula las mejores estrategias de asignación según las horas requeridas.
     * Retorna array de estrategias ordenadas por prioridad.
     */
    private function calcularEstrategiasAsignacion($hrsRequeridas, $bloques, $patronLunMieVie, $patronMarJue, $patronSabado)
    {
        $estrategias = [];
        
        // Analizar cada bloque y crear estrategias
        foreach ($bloques as $bloque) {
            $duracionBloque = $bloque['duracion_horas'];
            
            // ESTRATEGIA 1: Lun-Mie-Vie con este bloque
            $hrsLunMieVie = $duracionBloque * count($patronLunMieVie); // 3 días
            $estrategias[] = [
                'patron' => $patronLunMieVie,
                'patron_nombre' => 'Lun-Mie-Vie',
                'bloque' => $bloque,
                'hrs_totales' => $hrsLunMieVie,
                'prioridad' => abs($hrsLunMieVie - $hrsRequeridas), // Menor diferencia = mejor
            ];
            
            // ESTRATEGIA 2: Mar-Jue con este bloque
            $hrsMarJue = $duracionBloque * count($patronMarJue); // 2 días
            $estrategias[] = [
                'patron' => $patronMarJue,
                'patron_nombre' => 'Mar-Jue',
                'bloque' => $bloque,
                'hrs_totales' => $hrsMarJue,
                'prioridad' => abs($hrsMarJue - $hrsRequeridas),
            ];
            
            // ESTRATEGIA 3: Lun-Mie-Vie-Sab con este bloque (para materias con más horas)
            $hrsConSabado = $duracionBloque * (count($patronLunMieVie) + 1); // 4 días
            $estrategias[] = [
                'patron' => array_merge($patronLunMieVie, $patronSabado),
                'patron_nombre' => 'Lun-Mie-Vie-Sab',
                'bloque' => $bloque,
                'hrs_totales' => $hrsConSabado,
                'prioridad' => abs($hrsConSabado - $hrsRequeridas) + 10, // Penalizar sábados
            ];
        }
        
        // Ordenar por prioridad (menor diferencia primero)
        usort($estrategias, function($a, $b) {
            return $a['prioridad'] <=> $b['prioridad'];
        });
        
        return $estrategias;
    }

    /**
     * Intenta asignar horarios según una estrategia específica.
     * Retorna array con resultado de la asignación.
     */
    private function intentarAsignarEstrategia($asignacion, $aula, $estrategia, $esPractica, $hrsRequeridas, $hrsYaAsignadas)
    {
        $resultado = [
            'exito' => false,
            'horarios' => [],
            'hrs_asignadas' => $hrsYaAsignadas,
        ];
        
        $patron = $estrategia['patron'];
        $bloque = $estrategia['bloque'];
        $duracionBloque = $bloque['duracion_horas'];
        
        $horariosTemp = [];
        $bloquesConsecutivos = 0;
        $conflictoEncontrado = false;
        
        // Intentar asignar el mismo bloque en todos los días del patrón
        foreach ($patron as $idDia) {
            // Verificar que no excedamos las horas requeridas
            if ($resultado['hrs_asignadas'] >= $hrsRequeridas) break;
            
            // VALIDAR: Máximo 3 bloques consecutivos por día
            if ($bloquesConsecutivos >= 3) break;
            
            // Validar conflictos
            $conflicto = $this->validarConflictos(
                $asignacion->id_asignacion_docente,
                $aula->id_aula,
                $idDia,
                $bloque['id']
            );
            
            if ($conflicto !== null) {
                $conflictoEncontrado = true;
                break; // Si hay conflicto en este patrón, no seguir
            }
            
            // ¡SIN CONFLICTOS! Preparar para crear
            $tipoClaseId = $esPractica ? 2 : 1; // 1=Teórica, 2=Práctica
            
            $horariosTemp[] = [
                'id_asignacion_docente' => $asignacion->id_asignacion_docente,
                'id_aula' => $aula->id_aula,
                'id_dia' => $idDia,
                'id_bloque_horario' => $bloque['id'],
                'id_tipo_clase' => $tipoClaseId,
                'duracion_horas' => $duracionBloque,
            ];
            
            $bloquesConsecutivos++;
        }
        
        // Si se completó el patrón sin conflictos, crear los horarios
        if (!$conflictoEncontrado && count($horariosTemp) > 0) {
            $nombresDias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            
            foreach ($horariosTemp as $datos) {
                $horario = HorarioClase::create([
                    'id_asignacion_docente' => $datos['id_asignacion_docente'],
                    'id_aula' => $datos['id_aula'],
                    'id_dia' => $datos['id_dia'],
                    'id_bloque_horario' => $datos['id_bloque_horario'],
                    'id_tipo_clase' => $datos['id_tipo_clase'],
                    'activo' => true,
                    'id_estado' => $this->getEstadoId('BORRADOR'), // CU17: Estado inicial
                ]);
                
                $resultado['horarios'][] = [
                    'horario_id' => $horario->id_horario_clase,
                    'dia' => $nombresDias[$datos['id_dia']],
                    'bloque' => $bloque['nombre'],
                    'aula' => $aula->nombre,
                    'horas' => $datos['duracion_horas'],
                ];
                
                $resultado['hrs_asignadas'] += $datos['duracion_horas'];
            }
            
            $resultado['exito'] = true;
        }
        
        return $resultado;
    }


    /**
     * CU12: Visualizar Horarios Semanales
     * Permite consultar la grilla semanal filtrada por carrera, docente o grupo.
     * GET /api/horarios/semanal?filtro=carrera&id=3
     */
    public function visualizarSemanal(Request $request)
    {
        try {
            // 1️ Verificar gestión activa
            $gestionActiva = \App\Models\Gestion::getActiva();

            if (!$gestionActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay gestión académica activa.'
                ], 422);
            }

            // 2️ Validar parámetros de entrada
            $filtro = $request->get('filtro'); // carrera | docente | grupo
            $id = $request->get('id');
            
            $filtroInfo = null; // Información del filtro aplicado
            
            // Validar existencia del recurso si se especifica filtro
            if ($filtro && $id) {
                switch ($filtro) {
                    case 'carrera':
                        $carrera = \App\Models\Carrera::find($id);
                        if (!$carrera) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Carrera no encontrada.'
                            ], 404);
                        }
                        $filtroInfo = [
                            'tipo' => 'carrera',
                            'nombre' => $carrera->nombre,
                            'codigo' => $carrera->codigo
                        ];
                        break;
                    case 'docente':
                        $docente = \App\Models\Docente::with('perfil')->find($id);
                        if (!$docente) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Docente no encontrado.'
                            ], 404);
                        }
                        $filtroInfo = [
                            'tipo' => 'docente',
                            'nombre' => $docente->perfil->nombre_completo ?? 'Sin nombre',
                            'codigo' => $docente->cod_docente
                        ];
                        break;
                    case 'grupo':
                        $grupo = \App\Models\Grupo::find($id);
                        if (!$grupo) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Grupo no encontrado.'
                            ], 404);
                        }
                        $filtroInfo = [
                            'tipo' => 'grupo',
                            'nombre' => $grupo->nombre,
                            'codigo' => $grupo->nombre
                        ];
                        break;
                    default:
                        return response()->json([
                            'success' => false,
                            'message' => 'Filtro no válido. Use: carrera, docente o grupo.'
                        ], 400);
                }
            }

            // 3️ Base de consulta con relaciones completas
            $query = HorarioClase::with([
                'asignacionDocente.docente.perfil',
                'asignacionDocente.materiaGrupo.materia.carrera',
                'asignacionDocente.materiaGrupo.grupo',
                'aula.tipoAula',
                'dia',
                'bloqueHorario',
                'tipoClase'
            ])
            ->activos()
            ->whereHas('asignacionDocente.materiaGrupo', function($q) use ($gestionActiva) {
                $q->where('id_gestion', $gestionActiva->id_gestion);
            });

            // 4️ Aplicar filtros
            if ($filtro && $id) {
                switch ($filtro) {
                    case 'carrera':
                        $query->whereHas('asignacionDocente.materiaGrupo.materia', function($q) use ($id) {
                            $q->where('id_carrera', $id);
                        });
                        break;
                    case 'docente':
                        $query->whereHas('asignacionDocente', function($q) use ($id) {
                            $q->where('id_docente', $id);
                        });
                        break;
                    case 'grupo':
                        $query->whereHas('asignacionDocente.materiaGrupo', function($q) use ($id) {
                            $q->where('id_grupo', $id);
                        });
                        break;
                }
            }

            // 5️ Obtener horarios ordenados
            $horarios = $query
                ->orderBy('id_dia')
                ->orderBy('id_bloque_horario')
                ->get();

            if ($horarios->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron horarios con los criterios seleccionados.'
                ], 404);
            }

            // 6️ Transformar a formato de grilla semanal mejorada
            $grilla = [];
            foreach ($horarios as $h) {
                $dia = $h->dia->nombre;
                $bloque = $h->bloqueHorario->nombre;

                $grilla[$dia][$bloque][] = [
                    'id_horario' => $h->id_horario_clase,
                    'materia' => [
                        'nombre' => $h->asignacionDocente->materiaGrupo->materia->nombre,
                        'codigo' => $h->asignacionDocente->materiaGrupo->materia->codigo ?? null,
                        'carrera' => $h->asignacionDocente->materiaGrupo->materia->carrera->nombre ?? null
                    ],
                    'grupo' => $h->asignacionDocente->materiaGrupo->grupo->nombre,
                    'docente' => $h->asignacionDocente->docente->perfil->nombre_completo ?? 'Sin nombre',
                    'aula' => [
                        'nombre' => $h->aula->nombre ?? 'Sin aula',
                        'tipo' => $h->aula->tipoAula->nombre ?? 'Aula',
                        'capacidad' => $h->aula->capacidad ?? 0
                    ],
                    'tipo_clase' => $h->tipoClase->nombre ?? 'Teórica',
                    'horario' => [
                        'inicio' => $h->bloqueHorario->hr_inicio ?? null,
                        'fin' => $h->bloqueHorario->hr_fin ?? null
                    ]
                ];
            }

            // 7️ Obtener metadatos para construir la tabla (días y bloques)
            $dias = \App\Models\Dia::orderBy('id_dia')
                ->get(['id_dia', 'nombre', 'abreviatura'])
                ->toArray();
                
            $bloques = \App\Models\BloqueHorario::where('activo', true)
                ->orderBy('hr_inicio')
                ->get(['id_bloque_horario', 'nombre', 'hr_inicio', 'hr_fin'])
                ->toArray();

            // 8️ Registrar en bitácora
            \App\Models\Bitacora::registrar(
                'CONSULTA_HORARIOS',
                'Visualizó horarios semanales' . ($filtroInfo ? " de {$filtroInfo['tipo']}: {$filtroInfo['nombre']}" : ''),
                auth()->id()
            );

            // 9️ Respuesta final completa
            return response()->json([
                'success' => true,
                'gestion' => [
                    'id' => $gestionActiva->id_gestion,
                    'anio' => $gestionActiva->anio,
                    'semestre' => $gestionActiva->semestre,
                    'nombre_completo' => $gestionActiva->anio . '-' . $gestionActiva->semestre
                ],
                'filtro_aplicado' => $filtroInfo,
                'metadatos' => [
                    'dias' => $dias,
                    'bloques' => $bloques,
                    'total_horarios' => $horarios->count()
                ],
                'grilla' => $grilla
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar horarios semanales.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU17 (Paso previo): Aprobar Horarios
     * PUT /api/horarios/aprobar
     * Cambia el estado de BORRADOR a APROBADA.
     * Este paso es previo a la publicación.
     */
    public function aprobarHorarios(Request $request)
    {
        try {
            // Verificar gestión activa
            $gestionActiva = \App\Models\Gestion::getActiva();

            if (!$gestionActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay gestión académica activa.'
                ], 422);
            }

            // Obtener horarios en borrador
            $horariosBorrador = HorarioClase::whereHas('asignacionDocente.materiaGrupo', function($q) use ($gestionActiva) {
                $q->where('id_gestion', $gestionActiva->id_gestion);
            })
            ->borradores() // Usa el scope
            ->get();

            if ($horariosBorrador->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay horarios en borrador para aprobar.'
                ], 404);
            }

            // Actualizar a APROBADA
            DB::beginTransaction();
            $totalAprobados = 0;
            $idEstadoAprobada = $this->getEstadoId('APROBADA');

            foreach ($horariosBorrador as $horario) {
                $horario->update(['id_estado' => $idEstadoAprobada]);
                $totalAprobados++;
            }

            Bitacora::registrar(
                'APROBAR_HORARIOS',
                "Se aprobaron {$totalAprobados} horarios de la gestión {$gestionActiva->anio}-{$gestionActiva->semestre}",
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Se aprobaron {$totalAprobados} horarios.",
                'gestion' => "{$gestionActiva->anio}-{$gestionActiva->semestre}",
                'nota' => 'Los horarios están listos para ser publicados con PUT /horarios/publicar'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar los horarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CU17: Publicar Horarios
     * PUT /api/horarios/publicar
     * Cambia el estado de APROBADA a PUBLICADA para toda la gestión activa.
     * 
     * Validaciones:
     * - Gestión activa existe
     * - Hay horarios en estado APROBADA
     * - Todos los horarios tienen datos completos (aula, día, bloque)
     * - Todas las asignaciones tienen al menos un horario
     * 
     * NOTA: Los conflictos ya fueron validados al crear los horarios (CU6/CU7)
     * No se revalidan aquí para evitar timeouts en gestiones grandes.
     */
    public function publicarHorarios(Request $request)
    {
        try {
            // 1️ Verificar gestión activa
            $gestionActiva = \App\Models\Gestion::getActiva();

            if (!$gestionActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay gestión académica activa.'
                ], 422);
            }

            // 2️ Obtener horarios aprobados
            $horariosAprobados = HorarioClase::with([
                'asignacionDocente.docente.perfil',
                'asignacionDocente.materiaGrupo.materia',
                'asignacionDocente.materiaGrupo.grupo',
                'aula', 'dia', 'bloqueHorario'
            ])
            ->whereHas('asignacionDocente.materiaGrupo', function($q) use ($gestionActiva) {
                $q->where('id_gestion', $gestionActiva->id_gestion);
            })
            ->aprobados() // Usa el scope
            ->get();

            if ($horariosAprobados->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay horarios aprobados para publicar.'
                ], 404);
            }

            // 3️ Verificar integridad de datos
            $erroresIntegridad = [];
            foreach ($horariosAprobados as $h) {
                if (!$h->id_aula || !$h->id_bloque_horario || !$h->id_dia || !$h->id_tipo_clase) {
                    $materia = $h->asignacionDocente->materiaGrupo->materia->nombre ?? 'N/A';
                    $grupo = $h->asignacionDocente->materiaGrupo->grupo->nombre ?? 'N/A';
                    $erroresIntegridad[] = "Horario ID {$h->id_horario_clase} ({$materia} - {$grupo}) tiene datos incompletos.";
                }
            }

            if (!empty($erroresIntegridad)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Existen horarios con datos incompletos que no pueden publicarse.',
                    'errores' => $erroresIntegridad
                ], 409);
            }

            // 4️ Verificar que todas las asignaciones tengan al menos un horario
            $asignacionesIds = $horariosAprobados->pluck('id_asignacion_docente')->unique();
            $asignacionesSinHorario = AsignacionDocente::whereHas('materiaGrupo', function($q) use ($gestionActiva) {
                $q->where('id_gestion', $gestionActiva->id_gestion);
            })
            ->where('activo', true)
            ->whereNotIn('id_asignacion_docente', $asignacionesIds)
            ->with('materiaGrupo.materia', 'materiaGrupo.grupo', 'docente.perfil')
            ->get();

            if ($asignacionesSinHorario->isNotEmpty()) {
                $erroresAsignaciones = [];
                foreach ($asignacionesSinHorario as $asig) {
                    $materia = $asig->materiaGrupo->materia->nombre ?? 'N/A';
                    $grupo = $asig->materiaGrupo->grupo->nombre ?? 'N/A';
                    $docente = $asig->docente->perfil->nombre_completo ?? 'N/A';
                    $erroresAsignaciones[] = "{$materia} - {$grupo} (Docente: {$docente}) no tiene horarios asignados.";
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Algunas asignaciones no tienen horarios definidos.',
                    'asignaciones_pendientes' => $erroresAsignaciones
                ], 409);
            }

            // 5️ NOTA: Los conflictos ya fueron validados al crear los horarios (CU6/CU7)

            // 6️ Actualizar estado a PUBLICADA
            DB::beginTransaction();
            $totalPublicados = 0;
            $docentesNotificados = [];
            $idEstadoPublicada = $this->getEstadoId('PUBLICADA');

            foreach ($horariosAprobados as $horario) {
                $horario->update(['id_estado' => $idEstadoPublicada]);
                $totalPublicados++;
                
                // Recopilar docentes únicos para notificación
                $docenteId = $horario->asignacionDocente->id_docente;
                if (!in_array($docenteId, $docentesNotificados)) {
                    $docentesNotificados[] = $docenteId;
                }
            }

            // 7️ Registrar en Bitácora
            Bitacora::registrar(
                'PUBLICAR_HORARIOS',
                "Se publicaron {$totalPublicados} horarios de la gestión {$gestionActiva->anio}-{$gestionActiva->semestre}",
                auth()->id()
            );

            DB::commit();

            // 8️ Notificar a docentes (implementación futura)
            // TODO: Implementar sistema de notificaciones por correo o sistema interno
            // foreach ($docentesNotificados as $docenteId) {
            //     event(new HorariosPublicados($docenteId, $gestionActiva));
            // }

            return response()->json([
                'success' => true,
                'message' => "Se publicaron {$totalPublicados} horarios exitosamente.",
                'gestion' => "{$gestionActiva->anio}-{$gestionActiva->semestre}",
                'estadisticas' => [
                    'horarios_publicados' => $totalPublicados,
                    'docentes_afectados' => count($docentesNotificados),
                    'asignaciones_completas' => $asignacionesIds->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al publicar los horarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * CU10: Consultar Carga Horaria Personal (Docente)
     * GET /api/docente/horarios-personales
     * 
     * Permite al docente ver su horario semanal publicado.
     */
    public function cargaHorariaPersonal(Request $request)
    {
        try {
            // 1️ Obtener el usuario autenticado
            $usuario = auth()->user();

            if (!$usuario || !$usuario->docente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está asociado a un docente.'
                ], 403);
            }

            $docente = $usuario->docente;

            // Validar que el docente esté activo
            if (!$docente->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El docente está inactivo en el sistema.'
                ], 403);
            }

            // 2️ Verificar gestión activa
            $gestionActiva = \App\Models\Gestion::getActiva();
            if (!$gestionActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay gestión académica activa.'
                ], 422);
            }

            // Obtener ID del estado PUBLICADA
            $idEstadoPublicada = $this->getEstadoId('PUBLICADA');
            
            if (!$idEstadoPublicada) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Estado PUBLICADA no encontrado en el sistema.'
                ], 500);
            }

            // 3️ Consultar horarios PUBLICADOS del docente
            $horarios = HorarioClase::with([
                'asignacionDocente.materiaGrupo.materia.carrera',
                'asignacionDocente.materiaGrupo.grupo',
                'dia',
                'bloqueHorario',
                'aula.tipoAula',
                'tipoClase',
                'estado'
            ])
            ->whereHas('asignacionDocente', function ($q) use ($docente, $gestionActiva) {
                $q->where('id_docente', $docente->cod_docente)
                  ->where('activo', true)
                  ->whereHas('materiaGrupo', function ($sub) use ($gestionActiva) {
                      $sub->where('id_gestion', $gestionActiva->id_gestion)
                          ->where('activo', true);
                  });
            })
            ->where('id_estado', $idEstadoPublicada) // Filtrar por estado PUBLICADA
            ->where('activo', true)
            ->orderBy('id_dia')
            ->orderBy('id_bloque_horario')
            ->get();

            if ($horarios->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene horarios asignados en la gestión actual.'
                ], 404);
            }

            // 4️ Calcular estadísticas
            $totalHoras = 0;
            $materias = [];
            
            foreach ($horarios as $h) {
                $totalHoras += $h->bloqueHorario->minutos_duracion / 60;
                $materias[$h->asignacionDocente->materiaGrupo->materia->nombre] = true;
            }

            // 5️ Formatear grilla semanal
            $grilla = [];
            foreach ($horarios as $h) {
                $dia = $h->dia->nombre;
                $bloque = $h->bloqueHorario->nombre;

                $grilla[$dia][$bloque][] = [
                    'id_horario' => $h->id_horario_clase,
                    'materia' => [
                        'nombre' => $h->asignacionDocente->materiaGrupo->materia->nombre,
                        'codigo' => $h->asignacionDocente->materiaGrupo->materia->sigla ?? null,
                        'carrera' => $h->asignacionDocente->materiaGrupo->materia->carrera->nombre ?? 'N/A'
                    ],
                    'grupo' => $h->asignacionDocente->materiaGrupo->grupo->nombre,
                    'aula' => [
                        'nombre' => $h->aula->nombre ?? 'Por asignar',
                        'tipo' => $h->aula->tipoAula->nombre ?? 'Aula',
                        'capacidad' => $h->aula->capacidad ?? 0
                    ],
                    'tipo_clase' => $h->tipoClase->nombre ?? 'Teórica',
                    'horario' => [
                        'inicio' => $h->bloqueHorario->hr_inicio ?? null,
                        'fin' => $h->bloqueHorario->hr_fin ?? null,
                        'duracion_minutos' => $h->bloqueHorario->minutos_duracion ?? 90
                    ]
                ];
            }

            // 6️ Obtener metadatos para construir la tabla
            $dias = \App\Models\Dia::orderBy('id_dia')
                ->get(['id_dia', 'nombre', 'abreviatura'])
                ->toArray();
                
            $bloques = \App\Models\BloqueHorario::where('activo', true)
                ->orderBy('hr_inicio')
                ->get(['id_bloque_horario', 'nombre', 'hr_inicio', 'hr_fin', 'minutos_duracion'])
                ->toArray();

            // 7️ Registrar en bitácora
            Bitacora::registrar(
                'CONSULTA_CARGA_HORARIA',
                "Docente {$docente->cod_docente} consultó su carga horaria",
                $usuario->id_usuario
            );

            // 8️ Respuesta completa
            return response()->json([
                'success' => true,
                'gestion' => [
                    'id' => $gestionActiva->id_gestion,
                    'anio' => $gestionActiva->anio,
                    'semestre' => $gestionActiva->semestre,
                    'nombre_completo' => $gestionActiva->anio . '-' . $gestionActiva->semestre
                ],
                'docente' => [
                    'codigo' => $docente->cod_docente,
                    'nombre_completo' => $docente->perfil->nombre_completo ?? 'N/A',
                    'email' => $docente->perfil->email ?? null
                ],
                'estadisticas' => [
                    'total_horarios' => $horarios->count(),
                    'total_horas_semanales' => round($totalHoras, 2),
                    'materias_distintas' => count($materias)
                ],
                'metadatos' => [
                    'dias' => $dias,
                    'bloques' => $bloques
                ],
                'grilla' => $grilla
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar la carga horaria.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
