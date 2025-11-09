# 🎓 CU7: Generación Automática de Horarios - MEJORADO

## ✅ Mejoras Implementadas (Opción B)

### 1. **Cálculo Real de Duración de Bloques**

**Antes:**
- Asumía todos los bloques = 1.5 horas (90 min)
- Cálculo fijo: `bloques_necesarios = hrs_asignadas / 1.5`

**Ahora:**
- Lee duración real desde `bloque_horario.minutos_duracion`
- Soporta bloques de **90 min (1.5 hrs)** y **135 min (2.25 hrs)**
- Calcula estrategias precisas según duración real

### 2. **Patrones de Días Académicos Reales**

```
✅ Lun-Mie-Vie (1, 3, 5)
✅ Mar-Jue (2, 4)
✅ Lun-Mie-Vie-Sab (1, 3, 5, 6) - Para materias especiales
❌ Domingo (7) - Excluido
```

### 3. **Algoritmo de Estrategias Inteligentes**

El sistema ahora:

1. **Calcula todas las estrategias posibles:**
   - Para cada bloque disponible (90 min o 135 min)
   - Con cada patrón de días (Lun-Mie-Vie, Mar-Jue, Lun-Mie-Vie-Sab)
   - Calcula horas totales de cada combinación

2. **Prioriza estrategias por cercanía:**
   ```php
   Materia con 4.5 hrs requeridas:
   
   Estrategia A: Lun-Mie-Vie con bloque 90 min
     → 3 días × 1.5 hrs = 4.5 hrs ✅ (diferencia: 0 hrs) PRIORIDAD 1
   
   Estrategia B: Mar-Jue con bloque 135 min
     → 2 días × 2.25 hrs = 4.5 hrs ✅ (diferencia: 0 hrs) PRIORIDAD 1
   
   Estrategia C: Mar-Jue con bloque 90 min
     → 2 días × 1.5 hrs = 3 hrs ❌ (diferencia: 1.5 hrs) PRIORIDAD 2
   ```

3. **Intenta estrategias en orden de prioridad:**
   - Primera que no tenga conflictos → se asigna
   - Si falla, prueba siguiente estrategia
   - Penaliza uso de sábados (+10 puntos de prioridad)

### 4. **Validación Exhaustiva**

Antes de asignar cada horario, valida:
- ✅ Docente no esté ocupado en ese bloque
- ✅ Grupo no tenga otra clase
- ✅ Aula esté disponible
- ✅ Aula tenga capacidad suficiente
- ✅ Aula no esté en mantenimiento
- ✅ Tipo de aula adecuado (lab para prácticas)
- ✅ Máximo 3 bloques consecutivos por día

### 5. **Reporte Detallado**

```json
{
  "exitosas": [
    {
      "materia": "PROGRAMACION I",
      "grupo": "Z1",
      "hrs_requeridas": 4.5,
      "hrs_asignadas": 4.5,
      "completado": "SI",
      "porcentaje": 100.0,
      "horarios": [
        {
          "dia": "Lunes",
          "bloque": "Bloque 1",
          "aula": "Lab 5",
          "horas": 1.5
        },
        {
          "dia": "Miércoles",
          "bloque": "Bloque 1",
          "aula": "Lab 5",
          "horas": 1.5
        },
        {
          "dia": "Viernes",
          "bloque": "Bloque 1",
          "aula": "Lab 5",
          "horas": 1.5
        }
      ]
    }
  ],
  "fallidas": [
    {
      "materia": "CALCULO I",
      "grupo": "F1",
      "razon": "Solo se asignaron 3 de 4.5 horas requeridas",
      "hrs_requeridas": 4.5,
      "hrs_asignadas": 3.0,
      "horarios_parciales": [...]
    }
  ]
}
```

## 📊 Bloques Horarios Disponibles

### Bloques Estándar (90 minutos = 1.5 horas):
- **Bloque 1:** 06:45-08:15
- **Bloque 2:** 08:15-09:45
- **Bloque 3:** 09:45-11:15
- **Bloque 4:** 11:15-12:45
- **Bloque 5:** 14:30-16:00
- **Bloque 6:** 16:00-17:30
- **Bloque 7:** 17:30-19:00
- **Bloque 8:** 19:00-20:30

### Bloques Largos (135 minutos = 2.25 horas):
- **Bloque Largo 1:** 07:00-09:15 ⭐
- **Bloque Largo 2:** 09:15-11:30 ⭐
- **Bloque Largo 3:** 14:30-16:45 ⭐
- **Bloque Largo 4:** 16:45-19:00 ⭐
- **Bloque Largo 5:** 18:15-20:30 ⭐

## 🎯 Ejemplos de Asignación

### Ejemplo 1: Materia con 4.5 hrs
```
Opción A (seleccionada por prioridad):
  Lun 07:00-08:30 (1.5 hrs)
  Mie 07:00-08:30 (1.5 hrs)
  Vie 07:00-08:30 (1.5 hrs)
  TOTAL: 4.5 hrs ✅

Opción B (también válida):
  Mar 07:00-09:15 (2.25 hrs)
  Jue 07:00-09:15 (2.25 hrs)
  TOTAL: 4.5 hrs ✅
```

### Ejemplo 2: Materia con 6 hrs
```
Opción A (preferida):
  Lun 10:00-11:30 (1.5 hrs)
  Mie 10:00-11:30 (1.5 hrs)
  Vie 10:00-11:30 (1.5 hrs)
  Sab 10:00-11:30 (1.5 hrs)
  TOTAL: 6 hrs ✅

Opción B:
  Mar 07:00-09:15 (2.25 hrs)
  Jue 07:00-09:15 (2.25 hrs)
  Mar 11:30-13:00 (1.5 hrs)
  TOTAL: 6 hrs ✅
```

## 🚀 Cómo Usar

### Endpoint:
```
POST /api/horarios-clase/generar-automatico
```

### Request:
```json
{
  "id_gestion": 1,
  "id_carrera": 1  // Opcional
}
```

### Response:
```json
{
  "success": true,
  "message": "Generación automática completada",
  "resumen": {
    "total_asignaciones": 15,
    "exitosas": 13,
    "fallidas": 2,
    "porcentaje_exito": 86.67
  },
  "detalles": {
    "exitosas": [...],
    "fallidas": [...]
  }
}
```

## 📝 Notas Importantes

1. **Preferencia de Aulas:** Siempre asigna el aula más pequeña que cumpla la capacidad (minimiza desperdicio)

2. **Laboratorios:** Solo se asignan para materias de tipo "Práctica" o "Laboratorio"

3. **Sábados:** Se penalizan en prioridad, solo se usan si es necesario

4. **Conflictos:** Si una asignación tiene conflictos, se reporta en "fallidas" con razón detallada

5. **Asignación Parcial:** Si no se pueden asignar todas las horas, se reporta en "fallidas" pero se mantienen los horarios parciales creados

6. **Transacciones:** Todo se hace en una transacción, si hay error crítico se hace rollback completo
