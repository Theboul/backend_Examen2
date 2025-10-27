<!DOCTYPE html>
<html>
<head>
    <title>Mi App</title>
</head>
<body>
    <!-- Cambia el action a /api/carreras -->
    <form method="POST" action="/api/carreras">
        @csrf  <!-- Esto puede causar conflicto con API -->
        {{ $content }}
        <button type="submit">Enviar</button>
    </form>
</body>
</html>