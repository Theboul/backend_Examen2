<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log; // ¡Esencial para jobs!
use Illuminate\Support\Facades\DB;
use App\Models\Horarios\HorarioClase;
use App\Models\Horarios\Asistencia;
use App\Models\Sistema\Estado;
use Carbon\Carbon;

class RegistrarAusenciasDiarias extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * (Así lo llamas: php artisan sis:registrar-ausencias)
     */
    protected $signature = 'sis:registrar-ausencias';

    /**
     * La descripción del comando.
     */
    protected $description = 'Verifica todas las clases del día y registra "Ausente" a quienes no marcaron asistencia';

    // IDs de estado cacheados
    private $idEstadoAusente;
    private $idEstadoPublicada;

    /**
     * Obtiene y cachea los IDs de estado necesarios.
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
     * Ejecuta la lógica del comando.
     */
    public function handle()
    {
        Log::info('================================================================');
        Log::info('[JOB INICIADO] sis:registrar-ausencias');

        // 1. Obtener la fecha y día a verificar
        // (Corre a las 23:50, así que usamos la fecha actual)
        $diaActual = Carbon::now(); 
        $idDiaSemana = $diaActual->dayOfWeekIso; // Lunes=1, Domingo=7
        $fechaRegistro = $diaActual->toDateString();

        // 2. Obtener los IDs de Estado que necesitamos
        $this->idEstadoAusente = $this->getEstadoId('Ausente');
        $this->idEstadoPublicada = $this->getEstadoId('PUBLICADA');

        if (!$this->idEstadoAusente || !$this->idEstadoPublicada) {
            Log::error('[JOB FALLIDO] No se encontraron los estados "Ausente" o "PUBLICADA" en la BD.');
            return 1; // Terminar con error
        }

        try {
            // 3. Obtener todas las CLASES PROGRAMADAS Y PUBLICADAS para hoy
            $idsClasesProgramadas = HorarioClase::where('id_dia', $idDiaSemana)
                ->where('activo', true)
                ->where('id_estado', $this->idEstadoPublicada) // ¡Solo clases publicadas!
                ->pluck('id_horario_clase');

            if ($idsClasesProgramadas->isEmpty()) {
                Log::info('[JOB COMPLETADO] No había clases programadas/publicadas para hoy.');
                return 0;
            }

            // 4. Obtener todas las ASISTENCIAS (Presente/Tardanza) registradas hoy
            $idsClasesConAsistencia = Asistencia::where('fecha_registro', $fechaRegistro)
                ->pluck('id_horario_clase');

            // 5. Encontrar la diferencia (Programadas - Asistidas = Ausencias)
            $idsClasesAusentes = $idsClasesProgramadas->diff($idsClasesConAsistencia);

            if ($idsClasesAusentes->isEmpty()) {
                Log::info('[JOB COMPLETADO] Todas las clases programadas (' . $idsClasesProgramadas->count() . ') tuvieron registro de asistencia.');
                return 0;
            }

            // 6. Obtener los datos necesarios para la inserción masiva
            $clasesParaInsertar = HorarioClase::whereIn('id_horario_clase', $idsClasesAusentes)
                ->get(['id_horario_clase', 'id_asignacion_docente']);

            $horaRegistro = $diaActual->toTimeString();
            $dataParaInsertar = [];

            foreach ($clasesParaInsertar as $clase) {
                $dataParaInsertar[] = [
                    'id_asignacion_docente' => $clase->id_asignacion_docente,
                    'id_horario_clase' => $clase->id_horario_clase,
                    'id_estado' => $this->idEstadoAusente,
                    'fecha_registro' => $fechaRegistro,
                    'hora_registro' => $horaRegistro,
                    'tipo_registro' => 'SISTEMA_AUSENTE',
                    'observacion' => 'Registro automático de ausencia por el sistema.',
                ];
            }

            // 7. Insertar todas las ausencias en UNA sola consulta
            Asistencia::insert($dataParaInsertar);

            Log::info('[JOB COMPLETADO] Se registraron ' . count($dataParaInsertar) . ' ausencias.');
            Log::info('================================================================');
            return 0; // Éxito

        } catch (\Exception $e) {
            Log::error('[JOB FALLIDO] ' . $e->getMessage());
            return 1;
        }
    }
}