# 🎓 Sistema de Gestión de Horarios Académicos

Backend API REST desarrollado en **Laravel 11** con autenticación **Sanctum** para la gestión integral de horarios universitarios.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Casos de Uso Implementados](#-casos-de-uso-implementados)
- [API Endpoints](#-api-endpoints)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Documentación](#-documentación)

---

## ✨ Características

- 🔐 **Autenticación JWT** con Laravel Sanctum
- 👥 **Sistema de roles** (Administrador, Coordinador, Autoridad, Docente)
- 📊 **Gestión de horarios** con validación de conflictos
- 📁 **Carga masiva de usuarios** vía CSV
- 🔄 **Estados de horarios** (Borrador → Aprobada → Publicada)
- 📝 **Bitácora de auditoría** completa
- 🎯 **Validaciones avanzadas** de disponibilidad
- 📱 **API RESTful** con responses estandarizadas

---

## 🛠️ Tecnologías

| Categoría | Tecnología | Versión |
|-----------|-----------|---------|
| **Framework** | Laravel | 11.x |
| **Base de datos** | PostgreSQL | 15+ |
| **Autenticación** | Laravel Sanctum | 4.x |
| **PHP** | PHP | 8.2+ |
| **Deployment** | Railway / Docker | - |

---

## 📦 Requisitos

- PHP >= 8.2
- Composer >= 2.5
- PostgreSQL >= 15
- Node.js >= 18 (opcional, para assets)

---

## 🚀 Instalación

### 1️⃣ Clonar el repositorio

```bash
git clone https://github.com/Theboul/backend_Examen2.git
cd backend_Exam2
```

### 2️⃣ Instalar dependencias

```bash
composer install
npm install  # Opcional
```

### 3️⃣ Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con tus credenciales de BD:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sistema_horarios
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

### 4️⃣ Ejecutar migraciones y seeders

```bash
php artisan migrate:fresh
php artisan db:seed
```

### 5️⃣ Iniciar servidor de desarrollo

```bash
php artisan serve
# API disponible en: http://localhost:8000/api
```

---

## 📁 Estructura del Proyecto

```
backend_Exam2/
├── 📂 app/
│   ├── Http/
│   │   ├── Controllers/       # Controladores organizados por dominio
│   │   ├── Middleware/        # Middleware personalizado (roles)
│   │   └── Requests/          # Form Requests con validaciones
│   ├── Models/                # Modelos Eloquent
│   └── Services/              # Lógica de negocio compleja
├── 📂 database/
│   ├── migrations/            # Migraciones de BD
│   └── seeders/               # Datos iniciales
├── 📂 routes/
│   └── api.php               # Rutas API agrupadas por rol
├── 📂 tests/
│   └── api/                  # Pruebas de endpoints (.http)
├── 📂 docs/
│   ├── casos_uso/            # Documentación de CU
│   ├── deployment/           # Guías de deploy
│   └── guides/               # Guías técnicas
└── 📂 storage/
    └── data/                 # Plantillas CSV
```

---

## ✅ Casos de Uso Implementados

| CU | Nombre | Rol | Estado |
|----|--------|-----|--------|
| **CU1** | Carga Masiva de Usuarios | Admin | ✅ Completo |
| **CU6** | Asignación Manual de Horarios | Admin/Coord | ✅ Completo |
| **CU7** | Generación Automática de Horarios | Admin/Coord | ✅ Completo |
| **CU8** | Verificar Disponibilidad de Aulas | Admin/Coord | ✅ Completo |
| **CU10** | Consultar Carga Horaria (Docente) | Docente | ✅ Completo |
| **CU12** | Visualizar Horarios Semanales | Varios | ✅ Completo |
| **CU14** | Cambio de Password Primer Ingreso | Todos | ✅ Completo |
| **CU16** | Asignación de Docente a Materia | Admin/Coord | ✅ Completo |
| **CU17** | Publicar Horarios | Admin/Coord/Aut | ✅ Completo |

### 🔄 Pendientes
- **CU9**: Registrar asistencia docente
- **CU11**: Generar reportes de asistencia
- **CU20**: Justificar ausencias

---

## 🔌 API Endpoints

### 🔐 Autenticación
```http
POST   /api/auth/login              # Login
POST   /api/auth/logout             # Logout
POST   /api/auth/cambiar-password   # Cambiar password
```

### 👥 Usuarios (Admin)
```http
POST   /api/carga-masiva/usuarios   # CU1: Carga masiva CSV
```

### 📚 Docentes (Admin/Coordinador)
```http
GET    /api/docentes                # Listar docentes
POST   /api/asignaciones-docente    # CU16: Asignar docente
```

### 🏫 Horarios (Admin/Coordinador)
```http
POST   /api/horarios-clase          # CU6: Crear horario manual
POST   /api/horarios/generar        # CU7: Generar automático
GET    /api/aulas/disponibilidad    # CU8: Check disponibilidad
POST   /api/horarios/aprobar        # Aprobar horarios
```

### 📊 Consultas (Autoridad/Coordinador)
```http
GET    /api/horarios/semanal        # CU12: Vista semanal
POST   /api/horarios/publicar       # CU17: Publicar
```

### 👨‍🏫 Docentes (Rol Docente)
```http
GET    /api/docente/horarios-personales  # CU10: Mi horario
```

📝 **Documentación completa**: Ver `docs/casos_uso/` o archivo Postman en `/tests/api/`

---

## 🧪 Testing

### Pruebas con archivos .http

Los archivos de prueba están en `tests/api/`:

```bash
tests/api/
├── PRUEBA_CU6.http    # Asignación manual
├── PRUEBA_CU7.http    # Generación automática
├── PRUEBA_CU8.http    # Disponibilidad aulas
├── PRUEBA_CU10.http   # Horarios docente
├── PRUEBA_CU12.http   # Vista semanal
└── PRUEBA_CU17.http   # Publicación
```

**Uso**: Abrir con extensión REST Client de VS Code

### Credenciales de prueba

```
Administrador:
- Email: admin@example.com
- Password: [CI del admin]

Docente:
- Email: juan.perez@example.com
- Password: 12345678
```

---

## 🚀 Deployment

### Railway (Recomendado)

Ver guía completa: [`docs/deployment/DEPLOY_RAILWAY.md`](docs/deployment/DEPLOY_RAILWAY.md)

```bash
# Configurar variables de entorno
railway env set DB_CONNECTION=pgsql
railway env set APP_ENV=production

# Deploy
railway up
```

### Docker (Alternativo)

```bash
docker-compose up -d
```

---

## 📚 Documentación

### Guías Técnicas
- 📖 [Autenticación Sanctum](docs/guides/GUIA_SANCTUM.md)
- 📖 [Sistema de Estados](docs/casos_uso/RESUMEN_CU17_ESTADOS.md)
- 📖 [Auditoría de Código](docs/AUDITORIA_CODIGO.md)

### Casos de Uso
- 📄 [CU6: Asignación Manual](docs/casos_uso/GUIA_CU6_HORARIOS.md)
- 📄 [CU7: Generación Automática](docs/casos_uso/CU7_MEJORAS.md)
- 📄 [CU17: Publicación](docs/casos_uso/EJECUCION_CU17_COMPLETADA.md)

### Deployment
- 🚢 [Deploy en Railway](docs/deployment/DEPLOY_RAILWAY.md)
- ✅ [Checklist de Deploy](docs/deployment/CHECKLIST_DEPLOY.md)

---

## 🏗️ Arquitectura

### Flujo de Estados de Horarios

```
┌─────────────┐
│  BORRADOR   │ ← Creación inicial (CU6/CU7)
└──────┬──────┘
       │ Aprobar (Admin/Coord)
       ▼
┌─────────────┐
│  APROBADA   │ ← Revisión completada
└──────┬──────┘
       │ Publicar (Admin/Coord/Aut)
       ▼
┌─────────────┐
│  PUBLICADA  │ ← Visible para docentes (CU10)
└─────────────┘
```

### Middleware de Roles

```php
'role:Administrador'                    // Solo admin
'role:Administrador,Coordinador'        // Admin o Coord
'role:Docente'                          // Solo docentes
```

---

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'feat: nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## 📝 Licencia

Este proyecto es parte de un examen académico - Universidad [Nombre]

---

## 👨‍💻 Autor

**Theboul**
- GitHub: [@Theboul](https://github.com/Theboul)
- Repositorio: [backend_Examen2](https://github.com/Theboul/backend_Examen2)

---

## 📞 Soporte

Para reportar bugs o solicitar features, abre un [Issue](https://github.com/Theboul/backend_Examen2/issues)

---

## 🔍 Notas Importantes

### ⚠️ Solución de Problemas Comunes

**Error: Route [login] not defined**
- Causa: Token de Sanctum inválido
- Solución: Realizar login nuevamente

**Error: Timeout en publicación (CU17)**
- Causa: Validación de conflictos en bucle
- Solución: Ya corregido (validación solo en creación)

**Error: Column 'activo' does not exist (tabla dia)**
- Causa: Tabla `dia` no tiene columna `activo`
- Solución: Remover filtro `where('activo', true)`

### 🔧 Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas
php artisan route:list

# Ejecutar migraciones específicas
php artisan migrate --path=/database/migrations/2025_11_08_000001_add_id_estado_to_horario_clase.php

# Ejecutar seeder específico
php artisan db:seed --class=EstadoHorarioSeeder

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

---

**Última actualización**: Noviembre 2025
**Versión del proyecto**: 1.0.0
