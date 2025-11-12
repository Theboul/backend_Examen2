<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencia</title>
    <style>
        /* Estilos básicos para el PDF */
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-size: 11px;
            font-weight: bold;
        }
        h1, h2 {
            text-align: center;
            margin: 0;
            padding: 0;
        }
        h1 { font-size: 18px; }
        h2 { font-size: 16px; margin-bottom: 20px; }
        .stats-container {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .stats-item {
            display: inline-block; /* (DOMPDF no maneja bien Flexbox) */
            width: 20%;
            font-size: 12px;
            padding: 5px;
        }
    </style>
</head>
<body>

    <h1>Reporte de Asistencia de Docentes</h1>
    <h2>Gestión (ID: {{ $filtros['id_gestion'] ?? 'N/A' }})</h2>

    <div class="stats-container">
        <strong>Estadísticas del Periodo</strong><br><br>
        <span class="stats-item">
            <strong>Clases Registradas:</strong> {{ $estadisticas['total_clases_registradas'] ?? 0 }}
        </span>
        <span class="stats-item">
            <strong>Presentes:</strong> {{ $estadisticas['total_presente'] ?? 0 }}
        </span>
        <span class="stats-item">
            <strong>Tardanzas:</strong> {{ $estadisticas['total_tardanza'] ?? 0 }}
        </span>
        <span class="stats-item">
            <strong>Ausentes (Just.):</strong> {{ $estadisticas['total_ausente_justificado'] ?? 0 }}
        </span>
        <span class="stats-item">
            <strong>Ausentes (No Just.):</strong> {{ $estadisticas['total_ausente_injustificado'] ?? 0 }}
        </span>
        <br><br>
        <span class="stats-item" style="width: 40%;">
            <strong>Porcentaje Asistencia Efectiva:</strong> {{ $estadisticas['porcentaje_asistencia_efectiva'] ?? 0 }}%
        </span>
        <span class="stats-item" style="width: 40%;">
            <strong>Porcentaje Ausentismo Real:</strong> {{ $estadisticas['porcentaje_ausentismo_real'] ?? 0 }}%
        </span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora Reg.</th>
                <th>Docente</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Día</th>
                <th>Bloque</th>
                <th>Estado</th>
                <th>Tipo Reg.</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asistencias as $asistencia)
                <tr>
                    <td>{{ $asistencia->fecha_registro ? \Carbon\Carbon::parse($asistencia->fecha_registro)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $asistencia->hora_registro ? \Carbon\Carbon::parse($asistencia->hora_registro)->format('H:i:s') : 'N/A' }}</td>
                    
                    <td>{{ $asistencia->asignacionDocente->docente->perfil->nombre_completo ?? 'N/A' }}</td>
                    
                    <td>{{ $asistencia->asignacionDocente->materiaGrupo->materia->nombre ?? 'N/A' }}</td>
                    <td>{{ $asistencia->asignacionDocente->materiaGrupo->grupo->nombre ?? 'N/A' }}</td>

                    <td>{{ $asistencia->horarioClase->dia->nombre ?? 'N/A' }}</td>
                    <td>{{ $asistencia->horarioClase->bloqueHorario->nombre ?? 'N/A' }}</td>

                    <td style="font-weight: bold;">{{ $asistencia->estado->nombre ?? 'N/A' }}</td>
                    <td>{{ $asistencia->tipo_registro ?? '-' }}</td>
                    <td>{{ $asistencia->observacion ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center;">No hay datos detallados para mostrar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>