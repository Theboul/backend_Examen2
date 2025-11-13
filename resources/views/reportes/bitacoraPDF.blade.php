<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Bitácora</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; word-wrap: break-word; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Reporte de Bitácora</h2>
    <table>
        <thead>
            <tr>
                <th>Acción</th>
                <th>Descripción</th>
                <th>Usuario</th>
                <th>IP</th>
                <th>Fecha y Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bitacoras as $bitacora)
                <tr>
                    <td>{{ $bitacora->accion }}</td>
                    <td>{{ $bitacora->descripcion }}</td>
                    
                    <td>{{ $bitacora->nombre_usuario_plano ?? 'Anónimo' }}</td>
                    
                    <td>{{ $bitacora->ip ?? '-' }}</td>
                    
                    <td>{{ $bitacora->fecha ? \Carbon\Carbon::parse($bitacora->fecha)->format('d/m/Y H:i:s') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>