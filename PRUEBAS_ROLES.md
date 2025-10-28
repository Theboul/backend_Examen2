# 🔐 GUÍA DE PRUEBAS - CONTROL DE ACCESO POR ROLES

## 📋 Roles del Sistema

1. **Administrador** - Acceso completo a todos los endpoints
2. **Coordinador** - CRUD de Grupos + Consulta de datos maestros
3. **Autoridad** - Solo lectura/consulta de datos
4. **Docente** - Acceso a su propia información (pendiente implementar)

---

## 🧪 PRUEBAS EN POSTMAN

### Paso 1: Crear Usuarios de Prueba en la Base de Datos

Primero asegúrate de tener roles en tu tabla `rol`:

```sql
-- Verificar roles existentes
SELECT * FROM rol;

-- Si no existen, crearlos
INSERT INTO rol (nombre, descripcion, activo) VALUES
('Administrador', 'Acceso completo al sistema', true),
('Coordinador', 'Gestión de horarios y asignaciones', true),
('Autoridad', 'Solo lectura para reportes', true),
('Docente', 'Acceso a carga horaria propia', true);
```

Crear usuarios de prueba:

```sql
-- Usuario Administrador
INSERT INTO users (id_rol, usuario, email, password, activo) 
VALUES (
    (SELECT id_rol FROM rol WHERE nombre ILIKE 'Administrador' LIMIT 1),
    'admin',
    'admin@test.com',
    -- password: admin123
    '$2y$12$sBRtFwjiIhephW2E/Hwbe.HhQE5EcgNAFEnlmdgo5SIPDhErSx2i6',
    true
);

-- Usuario Coordinador
INSERT INTO users (id_rol, usuario, email, password, activo) 
VALUES (
    (SELECT id_rol FROM rol WHERE nombre ILIKE 'Coordinador' LIMIT 1),
    'coordinador',
    'coordinador@test.com',
    -- password: coord123
    '$2y$12$sBRtFwjiIhephW2E/Hwbe.HhQE5EcgNAFEnlmdgo5SIPDhErSx2i6',
    true
);

-- Usuario Autoridad
INSERT INTO users (id_rol, usuario, email, password, activo) 
VALUES (
    (SELECT id_rol FROM rol WHERE nombre ILIKE 'Autoridad' LIMIT 1),
    'autoridad',
    'autoridad@test.com',
    -- password: auto123
    '$2y$12$sBRtFwjiIhephW2E/Hwbe.HhQE5EcgNAFEnlmdgo5SIPDhErSx2i6',
    true
);
```

---

### Paso 2: Hacer Login y Obtener Token

#### Login como Administrador:
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "username": "admin@test.com",
  "password": "admin123"
}
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Inicio de sesión exitoso",
  "data": {
    "user": {
      "id_usuario": 1,
      "usuario": "admin",
      "email": "admin@test.com",
      "id_rol": 1
    },
    "token": "MToyMDI1MTAyODE0MzAwMA==",
    "primer_ingreso": false
  }
}
```

**⚠️ IMPORTANTE:** Copia el `token` de la respuesta para usarlo en las siguientes peticiones.

---

### Paso 3: Probar Endpoints con Control de Roles

#### ✅ PRUEBA 1: Administrador - Crear Gestión (Debe funcionar)

```http
POST http://localhost:8000/api/gestiones
Authorization: Bearer MToyMDI1MTAyODE0MzAwMA==
Content-Type: application/json

{
  "anio": 2025,
  "semestre": 1,
  "fecha_inicio": "2025-01-15",
  "fecha_fin": "2025-06-30"
}
```

**Resultado esperado:** ✅ 201 Created - Gestión creada exitosamente

---

#### ❌ PRUEBA 2: Coordinador - Crear Gestión (Debe fallar)

Primero haz login como coordinador:
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "username": "coordinador@test.com",
  "password": "coord123"
}
```

Luego intenta crear una gestión con el token del coordinador:
```http
POST http://localhost:8000/api/gestiones
Authorization: Bearer [TOKEN_DEL_COORDINADOR]
Content-Type: application/json

{
  "anio": 2025,
  "semestre": 2,
  "fecha_inicio": "2025-07-01",
  "fecha_fin": "2025-12-31"
}
```

**Resultado esperado:** ❌ 403 Forbidden
```json
{
  "success": false,
  "message": "No tiene permisos para acceder a este recurso",
  "rol_requerido": ["Administrador"],
  "rol_usuario": "Coordinador"
}
```

---

#### ✅ PRUEBA 3: Coordinador - Crear Grupo (Debe funcionar)

```http
POST http://localhost:8000/api/grupos
Authorization: Bearer [TOKEN_DEL_COORDINADOR]
Content-Type: application/json

{
  "id_materia": 1,
  "nombre": "Grupo A",
  "descripcion": "Grupo de prueba",
  "cupos": 30,
  "capacidad_maxima": 35
}
```

**Resultado esperado:** ✅ 201 Created - Grupo creado exitosamente

---

#### ✅ PRUEBA 4: Coordinador - Consultar Carreras (Debe funcionar)

```http
GET http://localhost:8000/api/carreras/consulta
Authorization: Bearer [TOKEN_DEL_COORDINADOR]
```

**Resultado esperado:** ✅ 200 OK - Lista de carreras

---

#### ❌ PRUEBA 5: Coordinador - Crear Carrera (Debe fallar)

```http
POST http://localhost:8000/api/carreras
Authorization: Bearer [TOKEN_DEL_COORDINADOR]
Content-Type: application/json

{
  "nombre": "Ingeniería de Sistemas",
  "codigo": "IS",
  "duracion_anios": 5
}
```

**Resultado esperado:** ❌ 403 Forbidden

---

#### ✅ PRUEBA 6: Autoridad - Consultar Docentes (Debe funcionar)

Login como autoridad primero, luego:

```http
GET http://localhost:8000/api/docentes/consulta
Authorization: Bearer [TOKEN_DE_AUTORIDAD]
```

**Resultado esperado:** ✅ 200 OK - Lista de docentes

---

#### ❌ PRUEBA 7: Autoridad - Crear Grupo (Debe fallar)

```http
POST http://localhost:8000/api/grupos
Authorization: Bearer [TOKEN_DE_AUTORIDAD]
Content-Type: application/json

{
  "id_materia": 1,
  "nombre": "Grupo B"
}
```

**Resultado esperado:** ❌ 403 Forbidden

---

#### ❌ PRUEBA 8: Sin Token - Acceder a Gestiones (Debe fallar)

```http
GET http://localhost:8000/api/gestiones
```

**Resultado esperado:** ❌ 401 Unauthorized
```json
{
  "success": false,
  "message": "No autorizado. Token requerido."
}
```

---

## 📊 Matriz de Pruebas

| Endpoint | Administrador | Coordinador | Autoridad | Sin Token |
|----------|--------------|-------------|-----------|-----------|
| POST /gestiones | ✅ | ❌ | ❌ | ❌ |
| GET /gestiones | ✅ | ❌ | ❌ | ❌ |
| GET /gestiones/consulta | ❌ | ✅ | ✅ | ❌ |
| POST /carreras | ✅ | ❌ | ❌ | ❌ |
| GET /carreras/consulta | ❌ | ✅ | ✅ | ❌ |
| POST /grupos | ✅ | ✅ | ❌ | ❌ |
| GET /grupos | ✅ | ✅ | ❌ | ❌ |
| POST /docentes | ✅ | ❌ | ❌ | ❌ |
| GET /docentes/consulta | ❌ | ✅ | ✅ | ❌ |
| POST /auth/login | ✅ | ✅ | ✅ | ✅ |
| POST /auth/toggle-activo/{id} | ✅ | ❌ | ❌ | ❌ |

---

## 🎯 Resumen de Control de Acceso

### **ADMINISTRADOR** 🔧
- ✅ CRUD completo de: Gestiones, Carreras, Materias, Aulas, Docentes, Grupos
- ✅ Activar/Desactivar cuentas
- ✅ Todas las operaciones

### **COORDINADOR** 📋
- ✅ CRUD completo de: Grupos
- ✅ Consulta (solo lectura) de: Gestiones, Carreras, Materias, Aulas, Docentes
- ❌ NO puede crear/editar/eliminar datos maestros

### **AUTORIDAD** 📊
- ✅ Consulta (solo lectura) de: Gestiones, Carreras, Materias, Aulas, Docentes
- ❌ NO puede crear/editar/eliminar nada

### **DOCENTE** 👨‍🏫
- ⏳ Pendiente implementar (acceso a carga horaria propia)

---

## 🔍 Verificar Roles en la Base de Datos

```sql
-- Ver qué rol tiene cada usuario
SELECT 
    u.id_usuario,
    u.usuario,
    u.email,
    r.nombre as rol,
    u.activo
FROM users u
INNER JOIN rol r ON u.id_rol = r.id_rol;
```

---

## 🐛 Troubleshooting

### Error: "Token inválido"
- Verifica que el header sea: `Authorization: Bearer [TU_TOKEN]`
- El token debe venir del login

### Error: "No tiene permisos para acceder a este recurso"
- El usuario no tiene el rol requerido
- Verifica el rol del usuario en la BD

### Error: "Cuenta inactiva"
- El campo `activo` del usuario está en `false`
- Un admin debe activar la cuenta

---

## ✅ Checklist de Pruebas

- [ ] Login como Administrador
- [ ] Login como Coordinador
- [ ] Login como Autoridad
- [ ] Administrador puede crear Gestiones
- [ ] Coordinador NO puede crear Gestiones
- [ ] Coordinador puede consultar Carreras
- [ ] Coordinador puede crear Grupos
- [ ] Autoridad puede consultar Docentes
- [ ] Autoridad NO puede crear Grupos
- [ ] Sin token retorna 401
- [ ] Token inválido retorna 401
- [ ] Usuario inactivo retorna 403

---

¡Listo para probar! 🚀
