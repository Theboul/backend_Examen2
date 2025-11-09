# 🚀 Guía Rápida de Desarrollo

Comandos y workflows para desarrollo diario.

---

## ⚡ Inicio Rápido

### Levantar servidor local
```bash
php artisan serve
# API: http://localhost:8000/api
```

### Base de datos
```bash
# Resetear BD completa
php artisan migrate:fresh --seed

# Ejecutar solo migraciones nuevas
php artisan migrate

# Ejecutar seeder específico
php artisan db:seed --class=EstadoHorarioSeeder
```

### Limpiar caché
```bash
php artisan optimize:clear
# O individualmente:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🧪 Testing

### Con archivos .http (REST Client VS Code)

1. Instalar extensión: **REST Client** (humao.rest-client)
2. Abrir archivo: `tests/api/PRUEBA_CU*.http`
3. Click en "Send Request" sobre cada petición

### Orden recomendado:
```
1. Login (cualquier .http)
2. Copiar token de respuesta
3. Ejecutar endpoint deseado
```

### Credenciales de prueba
```
Admin:
- usuario: admin@example.com
- password: [CI del admin en BD]

Docente:
- usuario: juan.perez@example.com
- password: 12345678
```

---

## 🔍 Debugging

### Ver logs en tiempo real
```powershell
# PowerShell
Get-Content storage\logs\laravel.log -Wait -Tail 50

# Bash/Linux
tail -f storage/logs/laravel.log
```

### Buscar errores específicos
```powershell
Get-Content storage\logs\laravel.log | Select-String "ERROR" -Context 2,5 | Select-Object -Last 10
```

### Ver rutas registradas
```bash
php artisan route:list
# Filtrar por nombre:
php artisan route:list --name=horarios
# Filtrar por método:
php artisan route:list --method=POST
```

---

## 📊 Base de Datos

### Acceder a PostgreSQL
```bash
psql -U postgres -d sistema_horarios
```

### Queries útiles
```sql
-- Ver usuarios
SELECT id_usuario, usuario, id_rol FROM users;

-- Ver horarios por estado
SELECT h.id_horario_clase, e.nombre as estado, h.created_at
FROM horario_clase h
JOIN estado e ON h.id_estado = e.id_estado;

-- Ver asignaciones docentes
SELECT d.cod_docente, pu.nombre_completo, COUNT(*) as total_asignaciones
FROM asignacion_docente ad
JOIN docente d ON ad.id_docente = d.cod_docente
JOIN perfil_usuario pu ON d.id_perfil = pu.id_perfil
WHERE ad.activo = true
GROUP BY d.cod_docente, pu.nombre_completo;
```

---

## 🔧 Comandos Útiles

### Crear nuevo controlador
```bash
php artisan make:controller NombreController --resource
```

### Crear modelo con migración
```bash
php artisan make:model Nombre -m
```

### Crear middleware
```bash
php artisan make:middleware NombreMiddleware
```

### Crear seeder
```bash
php artisan make:seeder NombreSeeder
```

### Crear request validation
```bash
php artisan make:request NombreRequest
```

---

## 📝 Workflows Comunes

### Agregar nuevo endpoint

1. **Definir ruta** en `routes/api.php`:
```php
Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::post('/nuevo-endpoint', [Controller::class, 'metodo']);
});
```

2. **Crear método** en controlador:
```php
public function metodo(Request $request) {
    // Lógica
    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}
```

3. **Registrar en bitácora**:
```php
Bitacora::registrar('ACCION', 'Descripción', auth()->id());
```

4. **Crear prueba** en `tests/api/`:
```http
### Nuevo endpoint
POST {{baseUrl}}/nuevo-endpoint
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "campo": "valor"
}
```

---

### Agregar nuevo caso de uso

1. **Crear documentación**: `docs/casos_uso/CU##_NOMBRE.md`
2. **Implementar lógica**: Controlador + Service (si es complejo)
3. **Definir rutas**: Con middleware apropiado
4. **Crear pruebas**: `tests/api/PRUEBA_CU##.http`
5. **Actualizar README**: Marcar como completado

---

## 🐛 Solución de Problemas

### Error: "Route [login] not defined"
```
Causa: Token inválido o expirado
Solución: Hacer login nuevamente
```

### Error: "SQLSTATE[42703]: Undefined column"
```
Causa: Columna no existe en BD
Solución: 
1. Verificar migración
2. Ejecutar: php artisan migrate
```

### Error: "Maximum execution time exceeded"
```
Causa: Bucle infinito o consulta pesada
Solución:
1. Revisar logs
2. Optimizar query o lógica
```

### Error: "Unauthenticated"
```
Causa: Token no enviado o middleware mal configurado
Solución:
1. Verificar header Authorization
2. Verificar que ruta tenga middleware auth:sanctum
```

---

## 📦 Gestión de Dependencias

### Instalar nueva dependencia
```bash
composer require vendor/package
```

### Actualizar dependencias
```bash
composer update
```

### Ver dependencias instaladas
```bash
composer show
```

---

## 🔐 Seguridad

### Regenerar APP_KEY
```bash
php artisan key:generate
```

### Limpiar tokens expirados
```bash
# Crear comando personalizado o ejecutar SQL:
DELETE FROM personal_access_tokens WHERE expires_at < NOW();
```

---

## 📊 Performance

### Cachear configuraciones (producción)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Limpiar cachés
```bash
php artisan optimize:clear
```

---

## 🔗 Enlaces Útiles

- [Laravel Docs](https://laravel.com/docs/11.x)
- [Sanctum Docs](https://laravel.com/docs/11.x/sanctum)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)
- [REST Client Extension](https://marketplace.visualstudio.com/items?itemName=humao.rest-client)

---

## 📞 Ayuda Rápida

### ¿Cómo pruebo un endpoint?
→ Usar archivos en `tests/api/`

### ¿Cómo agrego un rol nuevo?
→ Insertar en tabla `rol`, actualizar middleware

### ¿Cómo cambio estructura de BD?
→ Crear migración: `php artisan make:migration nombre`

### ¿Cómo veo los errores?
→ `storage/logs/laravel.log`

### ¿Cómo reseteo la BD?
→ `php artisan migrate:fresh --seed`

---

**Tip**: Guarda este archivo en favoritos para acceso rápido 📌
