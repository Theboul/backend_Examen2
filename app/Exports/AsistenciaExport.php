<?php

namespace App\Exports;

// Importamos todas las interfaces que necesitamos de Maatwebsite
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon; // La necesitamos para formatear fechas

/**
 * Esta clase implementa múltiples interfaces de Maatwebsite para un reporte profesional:
 * - FromCollection: Define la fuente de datos (nuestra colección de asistencias).
 * - WithHeadings: Define la fila del encabezado.
 * - WithMapping: Transforma cada modelo de Asistencia en un array plano.
 * - ShouldAutoSize: Ajusta el ancho de las columnas automáticamente.
 * - WithTitle: Nombra la hoja de Excel.
 * - WithEvents: Permite aplicar estilos (ej. negrilla al encabezado).
 */
class AsistenciaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithEvents
{
    protected $asistencias;
    protected $estadisticas;

    /**
     * Recibimos los datos desde el ReporteAsistenciaController.
     */
    public function __construct(array $datosReporte)
    {
        $this->asistencias = $datosReporte['asistencias'];
        $this->estadisticas = $datosReporte['estadisticas'];
    }

    /**
     * Devuelve la colección de datos que se va a exportar.
     */
    public function collection()
    {
        return $this->asistencias;
    }

    /**
     * Define los encabezados de las columnas en el Excel.
     */
    public function headings(): array
    {
        return [
            'ID Asistencia',
            'Fecha Registro',
            'Hora Registro',
            'Cod. Docente',
            'Docente',
            'Materia',
            'Grupo',
            'Día Clase',
            'Bloque Clase',
            'Estado',
            'Tipo Registro',
            'Observación',
        ];
    }

    /**
     * Mapea (aplana) cada fila de la colección a un array.
     * El orden DEBE COINCIDIR con el de headings().
     * @param mixed $asistencia (Usamos 'mixed' para que acepte el objeto de la colección)
     */
    public function map($asistencia): array
    {
        return [
            $asistencia->id_asistencia,
            $asistencia->fecha_registro ? Carbon::parse($asistencia->fecha_registro)->format('d/m/Y') : 'N/A',
            $asistencia->hora_registro ? Carbon::parse($asistencia->hora_registro)->format('H:i:s') : 'N/A',
            
            // Docente
            $asistencia->asignacionDocente->docente->cod_docente ?? 'N/A',
            $asistencia->asignacionDocente->docente->perfil->nombre_completo ?? 'N/A',
            
            // Asignación
            $asistencia->asignacionDocente->materiaGrupo->materia->nombre ?? 'N/A',
            $asistencia->asignacionDocente->materiaGrupo->grupo->nombre ?? 'N/A',

            // Horario
            $asistencia->horarioClase->dia->nombre ?? 'N/A',
            $asistencia->horarioClase->bloqueHorario->nombre ?? 'N/A',

            // Estado
            $asistencia->estado->nombre ?? 'N/A',
            $asistencia->tipo_registro ?? '-',
            $asistencia->observacion ?? '-',
        ];
    }

    /**
     * Define el nombre de la hoja en el archivo Excel.
     */
    public function title(): string
    {
        return 'Reporte de Asistencia';
    }

    /**
     * Registra eventos para dar estilo (ej. poner negrilla al encabezado).
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Aplica estilo de negrilla a la fila 1 (encabezados)
                $event->sheet->getDelegate()->getStyle('A1:L1')->getFont()->setBold(true);
            },
        ];
    }
}