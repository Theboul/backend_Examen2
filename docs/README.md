# 📚 Índice de Documentación

Este directorio contiene toda la documentación técnica del proyecto.

---

## 📂 Estructura

```
docs/
├── 📁 casos_uso/           # Documentación de Casos de Uso
├── 📁 deployment/          # Guías de despliegue
├── 📁 guides/              # Guías técnicas y tutoriales
├── AUDITORIA_CODIGO.md     # Auditoría técnica del código
└── README.md               # Este archivo
```

---

## 📖 Casos de Uso Implementados

### ✅ Completados

| Archivo | Caso de Uso | Descripción |
|---------|-------------|-------------|
| [CU6_ASIGNACION_MANUAL.md](casos_uso/CU6_ASIGNACION_MANUAL.md) | **CU6** | Asignación manual de horarios de clase |
| [CU7_GENERACION_AUTOMATICA.md](casos_uso/CU7_GENERACION_AUTOMATICA.md) | **CU7** | Generación automática de horarios |
| [CU17_PUBLICACION_HORARIOS.md](casos_uso/CU17_PUBLICACION_HORARIOS.md) | **CU17** | Publicación y aprobación de horarios |
| [SISTEMA_ESTADOS.md](casos_uso/SISTEMA_ESTADOS.md) | Sistema | Flujo de estados de horarios |
| [FIX_TIMEOUT_CU17.md](casos_uso/FIX_TIMEOUT_CU17.md) | Fix | Solución de timeout en publicación |

---

## 🚀 Deployment

| Archivo | Tema | Descripción |
|---------|------|-------------|
| [RAILWAY_DEPLOY.md](deployment/RAILWAY_DEPLOY.md) | Railway | Guía completa de deploy en Railway |
| [CHECKLIST.md](deployment/CHECKLIST.md) | Checklist | Lista de verificación pre-deploy |
| [RESUMEN.md](deployment/RESUMEN.md) | Resumen | Resumen de configuraciones |

---

## 🛠️ Guías Técnicas

| Archivo | Tema | Descripción |
|---------|------|-------------|
| [AUTENTICACION_SANCTUM.md](guides/AUTENTICACION_SANCTUM.md) | Auth | Sistema de autenticación con Sanctum |
| [GIT_CONFIGURATION.md](guides/GIT_CONFIGURATION.md) | Git | Configuración de Git y workflows |

---

## 🔍 Auditoría

| Archivo | Descripción |
|---------|-------------|
| [AUDITORIA_CODIGO.md](AUDITORIA_CODIGO.md) | Análisis técnico completo del código, patrones identificados y mejoras sugeridas |

---

## 📝 Cómo usar esta documentación

### Para desarrolladores nuevos:
1. Leer [README.md principal](../README.md)
2. Revisar [AUTENTICACION_SANCTUM.md](guides/AUTENTICACION_SANCTUM.md)
3. Estudiar casos de uso implementados en `casos_uso/`

### Para deployment:
1. Leer [CHECKLIST.md](deployment/CHECKLIST.md)
2. Seguir [RAILWAY_DEPLOY.md](deployment/RAILWAY_DEPLOY.md)
3. Verificar variables de entorno

### Para testing:
1. Ver archivos en `/tests/api/`
2. Seguir ejemplos de cada CU

---

## 🔗 Enlaces Rápidos

- [📖 README Principal](../README.md)
- [🧪 Pruebas API](../tests/api/)
- [📊 Plantillas de Datos](../storage/data/)
- [🔌 Rutas API](../routes/api.php)

---

**Última actualización**: Noviembre 2025
