# 📋 RESUMEN: Implementación CU17 usando tabla `estado`

## ✅ Lo que ya está listo:

### 1. **Modelo Estado existente** ✅
- Tabla: `estado` con campos `id_estado`, `nombre`, `descripcion`, `activo`
- Modelo: `app/Models/Estado.php`

### 2. **Migración creada** ✅
- Archivo: `database/migrations/2025_11_08_000001_add_id_estado_to_horario_clase.php`
- Agrega columna `id_estado` (FK) a tabla `horario_clase`

### 3. **Seeder creado** ✅
- Archivo: `database/seeders/EstadoHorarioSeeder.php`
- Inserta: BORRADOR, APROBADA, PUBLICADA, CANCELADA

### 4. **Script SQL alternativo** ✅
- Archivo: `database/scripts/insertar_estados_horario.sql`
- Por si prefieres insertar manualmente en PostgreSQL

### 5. **Modelo HorarioClase actualizado** ✅
- Agregado `id_estado` a `$fillable`
- Agregada relación `estado()`
- Agregados scopes: `publicados()`, `aprobados()`, `borradores()`

### 6. **Controlador actualizado** ✅
- Helper `getEstadoId($nombre)` para obtener IDs
- Método `aprobarHorarios()`: BORRADOR → APROBADA
- Método `publicarHorarios()`: APROBADA → PUBLICADA (con todas las validaciones CU17)
- Método `store()` y `generarAutomatico()`: Crean con estado BORRADOR
- Método `destroy()`: Cambia a CANCELADA

### 7. **Rutas configuradas** ✅
- `PUT /horarios/aprobar` - Admin y Coordinador
- `PUT /horarios/publicar` - Admin, Coordinador y Autoridad

### 8. **Archivo de pruebas** ✅
- `PRUEBA_CU17.http` con flujo completo

---

## 📦 PASOS PARA IMPLEMENTAR:

### Opción A: Usando Seeder (Recomendado)

```powershell
# 1. Ejecutar seeder para insertar estados
php artisan db:seed --class=EstadoHorarioSeeder

# 2. Ejecutar migración para agregar id_estado
php artisan migrate

# 3. Actualizar horarios existentes (si los hay) al estado BORRADOR
# Ejecutar en PostgreSQL:
UPDATE horario_clase SET id_estado = (SELECT id_estado FROM estado WHERE nombre = 'BORRADOR' LIMIT 1) WHERE id_estado IS NULL;

# 4. Limpiar caché
php artisan route:clear
php artisan config:clear

# 5. Iniciar servidor
php artisan serve
```

### Opción B: Usando SQL directo

```powershell
# 1. Ejecutar el script SQL en tu base de datos
# Ver archivo: database/scripts/insertar_estados_horario.sql

# 2. Ejecutar migración
php artisan migrate

# 3. Actualizar horarios existentes (mismo comando que Opción A)

# 4-5. Limpiar caché e iniciar servidor (igual que Opción A)
```

---

## 🔄 FLUJO DE ESTADOS:

```
┌─────────────┐
│  BORRADOR   │ ← Horario recién creado (manual o automático)
└──────┬──────┘
       │ PUT /horarios/aprobar
       ↓
┌─────────────┐
│  APROBADA   │ ← Revisado por coordinador
└──────┬──────┘
       │ PUT /horarios/publicar (CU17)
       ↓
┌─────────────┐
│ PUBLICADA   │ ← Visible para docentes
└──────┬──────┘
       │
       ↓
┌─────────────┐
│ CANCELADA   │ ← Eliminado/Cancelado
└─────────────┘
```

---

## ✅ VALIDACIONES CU17 (en `publicarHorarios`):

1. ✓ Gestión activa existe
2. ✓ Hay horarios en estado APROBADA
3. ✓ Todos los horarios tienen datos completos (aula, día, bloque, tipo)
4. ✓ Todas las asignaciones tienen al menos un horario
5. ✓ No existen conflictos pendientes (usa `validarConflictos()`)
6. ✓ Registra en bitácora
7. ✓ Recopila docentes afectados (para notificaciones futuras)

---

## 🧪 PRUEBAS:

Ver archivo `PRUEBA_CU17.http`:
1. Login
2. Ver gestión activa
3. Ver horarios (deberían estar en BORRADOR)
4. Aprobar horarios → APROBADA
5. Publicar horarios → PUBLICADA (CU17)
6. Verificar horarios publicados

---

## 📊 VENTAJAS de usar tabla `estado`:

✅ No necesitas alterar schema si agregas nuevos estados
✅ Puedes agregar: RECHAZADA, EN_REVISIÓN, etc.
✅ Mantiene consistencia con el resto del sistema
✅ Facilita reportes y estadísticas
✅ Permite auditoría de cambios de estado (con timestamps)

---

## ⚠️ IMPORTANTE:

- Si ya tienes horarios creados, **ejecuta el UPDATE** para asignarles estado BORRADOR
- La columna `id_estado` permite NULL temporalmente para la migración
- Después del UPDATE, puedes hacer la columna NOT NULL si quieres
- Los scopes `publicados()`, `aprobados()`, `borradores()` facilitan las consultas

---

## 🎯 SIGUIENTE PASO:

**Ejecutar uno de los dos métodos (Opción A o B) y luego probar con PRUEBA_CU17.http**
