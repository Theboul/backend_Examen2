# ============================================================================
# GUÍA DE IMPLEMENTACIÓN CU6: Asignación Manual de Horarios
# ============================================================================

## ✅ COMPLETADO

### 1. Base de Datos
- ✅ Tabla `dia` poblada con 7 días (Lunes-Domingo)
- ✅ Tabla `bloque_horario` poblada con 8 bloques horarios (06:45-20:30)
- ✅ Tabla `tipo_clase` poblada con 4 tipos (Teórica, Práctica, Laboratorio, Taller)
- ✅ Tabla `horario_clase` modificada:
  - ❌ ANTES: usaba `id_materia_grupo`
  - ✅ AHORA: usa `id_asignacion_docente` con FK a `asignacion_docente`

### 2. Modelos
- ✅ `HorarioClase.php` - Modelo principal con relaciones correctas
- ✅ `TipoClase.php` - Modelo creado para tipos de clase
- ✅ Relaciones configuradas:
  - `asignacionDocente()` -> AsignacionDocente (CU16)
  - `aula()` -> Aula
  - `dia()` -> Dia
  - `bloqueHorario()` -> BloqueHorario
  - `tipoClase()` -> TipoClase

### 3. Controlador
- ✅ `HorarioClaseController.php` con todas las operaciones CRUD
- ✅ Método `validarConflictos()` implementado con:
  - Validación de disponibilidad del docente (mismo horario)
  - Validación de disponibilidad del grupo (mismo horario)
  - Validación de disponibilidad del aula (mismo horario)
  - Validación de capacidad del aula vs estudiantes del grupo
  - Validación de estado del aula (no en mantenimiento)

### 4. Rutas API
- ✅ GET `/api/horarios-clase` - Listar horarios con filtros
- ✅ POST `/api/horarios-clase` - Crear horario (CU6)
- ✅ GET `/api/horarios-clase/{id}` - Ver detalle
- ✅ PUT `/api/horarios-clase/{id}` - Actualizar
- ✅ DELETE `/api/horarios-clase/{id}` - Eliminar (soft delete)

---

## 📋 ESTRUCTURA DE DATOS

### Tabla `horario_clase` (FINAL)
```
id_horario_clase       INTEGER PRIMARY KEY
id_asignacion_docente  INTEGER FK -> asignacion_docente  ✅ CAMBIO CLAVE
id_aula                INTEGER FK -> aula
id_dia                 INTEGER FK -> dia
id_bloque_horario      INTEGER FK -> bloque_horario
id_tipo_clase          INTEGER FK -> tipo_clase
activo                 BOOLEAN
fecha_creacion         TIMESTAMP
```

### Datos Catálogo Disponibles

**Días (7 registros):**
- Lunes, Martes, Miércoles, Jueves, Viernes, Sábado, Domingo

**Bloques Horarios (8 registros):**
- Bloque 1: 06:45 - 08:15
- Bloque 2: 08:15 - 09:45
- Bloque 3: 09:45 - 11:15
- Bloque 4: 11:15 - 12:45
- Bloque 5: 14:30 - 16:00
- Bloque 6: 16:00 - 17:30
- Bloque 7: 17:30 - 19:00
- Bloque 8: 19:00 - 20:30

**Tipos de Clase (4 registros):**
- Teórica, Práctica, Laboratorio, Taller

---

## 🧪 PRUEBAS - Cómo Probar CU6

### 1. Obtener IDs necesarios

Primero necesitas tener datos en:
- ✅ `asignacion_docente` (CU16 ya implementado)
- ✅ `aula` (debe existir previamente)
- ✅ `dia` (✅ ya poblado)
- ✅ `bloque_horario` (✅ ya poblado)
- ✅ `tipo_clase` (✅ ya poblado)

### 2. Ejemplo de Creación de Horario (POST)

**Endpoint:** `POST /api/horarios-clase`

**Headers:**
```
Authorization: Bearer {token_admin_o_coordinador}
Content-Type: application/json
```

**Body:**
```json
{
  "id_asignacion_docente": 1,  // ID de una asignación existente
  "id_aula": 101,               // ID de un aula existente
  "id_dia": 1,                  // 1=Lunes (de nuestro seeder)
  "id_bloque_horario": 1,       // Bloque 1: 06:45-08:15
  "id_tipo_clase": 1            // 1=Teórica (de nuestro seeder)
}
```

**Respuesta Exitosa (201):**
```json
{
  "mensaje": "Horario de clase creado exitosamente",
  "horario": {
    "id_horario_clase": 1,
    "id_asignacion_docente": 1,
    "id_aula": 101,
    "id_dia": 1,
    "id_bloque_horario": 1,
    "id_tipo_clase": 1,
    "activo": true,
    "fecha_creacion": "2025-11-08T02:30:00.000000Z",
    "asignacion_docente": { ... },
    "aula": { ... },
    "dia": { "nombre": "Lunes" },
    "bloque_horario": { "hr_inicio": "06:45:00", "hr_fin": "08:15:00" },
    "tipo_clase": { "nombre": "Teórica" }
  }
}
```

**Respuesta con Conflicto (422):**
```json
{
  "error": "Conflictos detectados",
  "conflictos": [
    "El docente Juan Pérez ya tiene clase asignada el Lunes a las 06:45-08:15",
    "El grupo SIS-101 ya tiene clase asignada el Lunes a las 06:45-08:15"
  ]
}
```

### 3. Listar Horarios (GET)

**Endpoint:** `GET /api/horarios-clase`

**Parámetros opcionales:**
- `gestion_id` - Filtrar por gestión
- `carrera_id` - Filtrar por carrera
- `semestre_id` - Filtrar por semestre
- `docente_id` - Filtrar por docente
- `grupo_id` - Filtrar por grupo
- `activo` - Filtrar por estado (true/false)

**Ejemplo:**
```
GET /api/horarios-clase?gestion_id=1&activo=true
```

---

## 🔍 VALIDACIONES IMPLEMENTADAS

### Automáticas en `validarConflictos()`

1. **Conflicto de Docente:**
   - ❌ El docente NO puede tener 2 clases al mismo tiempo
   - Verifica: mismo día + mismo bloque horario + otro id_asignacion_docente del mismo docente

2. **Conflicto de Grupo:**
   - ❌ El grupo NO puede tener 2 clases al mismo tiempo
   - Verifica: mismo día + mismo bloque horario + otro grupo

3. **Conflicto de Aula:**
   - ❌ El aula NO puede estar ocupada en el mismo horario
   - Verifica: mismo día + mismo bloque horario + misma aula

4. **Capacidad del Aula:**
   - ❌ El aula debe tener capacidad suficiente para los estudiantes del grupo
   - Verifica: aula.capacidad >= grupo.num_estudiantes

5. **Estado del Aula:**
   - ❌ El aula NO debe estar en mantenimiento
   - Verifica: aula.en_mantenimiento = false

---

## 🚀 PRÓXIMOS PASOS

### Pendiente: CU7 - Generación Automática de Horarios

**Objetivo:** Generar automáticamente horarios para toda una gestión/carrera

**Enfoque:**
- Usar el método `validarConflictos()` existente para cada intento
- Algoritmo de asignación inteligente:
  1. Obtener todas las asignaciones docente-materia de la gestión
  2. Por cada asignación, intentar encontrar aula + día + bloque disponible
  3. Validar con `validarConflictos()` antes de guardar
  4. Optimización: priorizar por carga horaria, preferencias, etc.

**Ruta sugerida:**
```
POST /api/horarios-clase/generar-automatico
Body: {
  "gestion_id": 1,
  "carrera_id": 2,
  "preferencias": {
    "priorizar_docentes_tiempo_completo": true,
    "evitar_horas_pico": true
  }
}
```

---

## 📝 NOTAS IMPORTANTES

### Cambio Crítico Realizado
- **ANTES:** `horario_clase.id_materia_grupo` → Horario asignado a materia-grupo (sin saber qué docente)
- **AHORA:** `horario_clase.id_asignacion_docente` → Horario asignado a docente específico que imparte esa materia-grupo

### Justificación
Este cambio permite:
1. ✅ Saber QUÉ DOCENTE imparte cada clase
2. ✅ Validar conflictos de horario del docente
3. ✅ Integración directa con CU16 (Asignación de Docentes)
4. ✅ Lógica más robusta y realista

### Migración Ejecutada
```bash
php artisan migrate
# Aplicó: 2025_11_08_021724_modify_horario_clase_change_materia_grupo_to_asignacion_docente
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Seeders creados y ejecutados (dia, bloque_horario, tipo_clase)
- [x] Migración para cambiar id_materia_grupo → id_asignacion_docente
- [x] Modelo HorarioClase ajustado
- [x] Modelo TipoClase creado
- [x] Controller con CRUD completo
- [x] Validación de conflictos implementada
- [x] Rutas API registradas
- [ ] **Probar endpoints en Postman/Thunder Client**
- [ ] **Implementar CU7 (Generación Automática)**

---

**Fecha de implementación:** 8 de noviembre de 2025
**Estado:** ✅ CU6 LISTO PARA PRUEBAS
