# 🚂 GUÍA DE DESPLIEGUE EN RAILWAY

## 📋 PRE-REQUISITOS

- ✅ Cuenta de GitHub
- ✅ Cuenta de Railway (regístrate en [railway.app](https://railway.app))
- ✅ Proyecto subido a GitHub

---

## 🚀 PASO 1: PREPARAR EL REPOSITORIO

### 1.1 Verifica que estos archivos estén en tu repositorio:
- ✅ `Procfile` (creado)
- ✅ `nixpacks.toml` (creado)
- ✅ `.env.example` (actualizado)
- ✅ `composer.json` (actualizado)

### 1.2 Sube los cambios a GitHub:

```bash
git add .
git commit -m "Preparar proyecto para Railway"
git push origin main
```

---

## 🚀 PASO 2: CREAR PROYECTO EN RAILWAY

1. Ve a [railway.app](https://railway.app)
2. Haz clic en **"Start a New Project"**
3. Selecciona **"Deploy from GitHub repo"**
4. Autoriza Railway para acceder a tu GitHub
5. Selecciona tu repositorio: **backend_Examen2**
6. Railway detectará automáticamente que es Laravel

---

## 🗄️ PASO 3: AGREGAR BASE DE DATOS POSTGRESQL

1. En tu proyecto de Railway, haz clic en **"+ New"**
2. Selecciona **"Database" → "Add PostgreSQL"**
3. Railway creará automáticamente la base de datos
4. **IMPORTANTE:** Las variables de entorno se conectarán automáticamente

---

## ⚙️ PASO 4: CONFIGURAR VARIABLES DE ENTORNO

1. En Railway, ve a tu servicio Laravel
2. Haz clic en **"Variables"**
3. Agrega las siguientes variables:

### Variables Obligatorias:

```bash
APP_NAME=Sistema Académico
APP_ENV=production
APP_DEBUG=false
APP_KEY=                    # Se generará automáticamente o usa: php artisan key:generate --show

# Railway conecta automáticamente estas variables:
# PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD

# Session y Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# CORS (si tienes frontend)
SANCTUM_STATEFUL_DOMAINS=tu-dominio-frontend.com
SESSION_DOMAIN=.railway.app

# Log
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Variables de BD (Railway las crea automáticamente):
Railway crea automáticamente estas variables cuando agregas PostgreSQL:
- `PGHOST`
- `PGPORT`
- `PGDATABASE`
- `PGUSER`
- `PGPASSWORD`

**NO necesitas configurarlas manualmente** ✅

---

## 🔑 PASO 5: GENERAR APP_KEY

### Opción 1: En tu máquina local
```bash
php artisan key:generate --show
```
Copia el resultado y pégalo en Railway como variable `APP_KEY`

### Opción 2: En Railway (después del primer deploy)
1. Ve a tu servicio en Railway
2. Abre la pestaña **"Deployments"**
3. Encuentra el último deployment
4. En **"View Logs"**, busca el comando ejecutado
5. O ejecuta en consola Railway: `php artisan key:generate --show`

---

## 🚀 PASO 6: DESPLEGAR

1. Railway detectará cambios automáticamente y comenzará el deploy
2. Espera a que termine (verás los logs en tiempo real)
3. Busca mensajes de éxito:
   - ✅ "Build successful"
   - ✅ "Deployment successful"

---

## 🌐 PASO 7: OBTENER TU URL

1. En Railway, ve a **"Settings"**
2. En la sección **"Domains"**, haz clic en **"Generate Domain"**
3. Railway te dará una URL como: `https://your-app.up.railway.app`
4. **IMPORTANTE:** Actualiza la variable `APP_URL` con esta URL

---

## ✅ PASO 8: VERIFICAR QUE TODO FUNCIONA

### Prueba tu API:
```bash
# Reemplaza con tu URL de Railway
curl https://your-app.up.railway.app/api/test
```

**Respuesta esperada:**
```json
{
  "message": "API funcionando correctamente"
}
```

### Prueba el login:
```bash
curl -X POST https://your-app.up.railway.app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"admin","password":"admin123"}'
```

---

## 🔧 PASO 9: CONFIGURACIONES ADICIONALES

### 9.1 Configurar CORS (si tienes frontend)

En Railway, agrega estas variables:

```bash
# Permite solicitudes desde tu frontend
SANCTUM_STATEFUL_DOMAINS=tu-frontend.com,tu-frontend.railway.app
SESSION_DOMAIN=.railway.app

# Configuración de sesión
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
```

Actualiza `config/cors.php` si es necesario.

### 9.2 Activar HTTPS (Railway lo hace automáticamente) ✅

### 9.3 Logs en Producción

```bash
LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## 📊 PASO 10: EJECUTAR MIGRACIONES (Opcional)

Las migraciones se ejecutan automáticamente con el `Procfile`, pero si necesitas ejecutarlas manualmente:

1. En Railway, ve a tu servicio
2. En el menú, busca **"Shell"** o **"Console"**
3. Ejecuta:
```bash
php artisan migrate --force
```

---

## 🛠️ COMANDOS ÚTILES EN RAILWAY

### Ver logs en tiempo real:
```bash
# En la consola de Railway
railway logs
```

### Ejecutar comandos:
```bash
railway run php artisan migrate
railway run php artisan config:clear
railway run php artisan cache:clear
```

---

## 🔄 DESPLIEGUE CONTINUO (CI/CD)

Railway despliega automáticamente cuando haces push a GitHub:

```bash
# Hacer cambios en tu código
git add .
git commit -m "Actualización del sistema"
git push origin main

# Railway desplegará automáticamente
```

---

## ⚠️ TROUBLESHOOTING (SOLUCIÓN DE PROBLEMAS)

### Error 500 - Internal Server Error
```bash
# Ver logs en Railway
1. Ve a "Deployments"
2. Selecciona el último deployment
3. Haz clic en "View Logs"
4. Busca errores

# Causas comunes:
- APP_KEY no configurada
- Base de datos no conectada
- Migraciones no ejecutadas
```

### Base de datos no conecta
```bash
# Verifica variables de entorno en Railway:
- PGHOST
- PGPORT
- PGDATABASE
- PGUSER
- PGPASSWORD

# Deben estar presentes automáticamente
```

### Error de CORS
```bash
# Agrega estas variables en Railway:
SANCTUM_STATEFUL_DOMAINS=tu-frontend.com
SESSION_DOMAIN=.railway.app
```

---

## 💰 COSTOS

Railway ofrece:
- ✅ **$5 USD gratis mensuales** (suficiente para proyectos pequeños)
- ✅ **PostgreSQL incluido** en el plan gratuito
- ✅ **500 horas de ejecución** por mes

**Tu proyecto académico debería caber perfectamente en el plan gratuito** 🎉

---

## 🎯 CHECKLIST FINAL

Antes de considerar el deploy exitoso:

- [ ] Aplicación accesible en la URL de Railway
- [ ] Endpoint `/api/test` responde correctamente
- [ ] Login funciona (`/api/auth/login`)
- [ ] Base de datos PostgreSQL conectada
- [ ] Migraciones ejecutadas
- [ ] Variables de entorno configuradas
- [ ] APP_KEY generada
- [ ] Logs sin errores críticos
- [ ] HTTPS funcionando (automático en Railway)

---

## 📚 RECURSOS ADICIONALES

- [Documentación oficial de Railway](https://docs.railway.app)
- [Railway Laravel Template](https://railway.app/template/laravel)
- [Railway Discord Community](https://discord.gg/railway)

---

## 🎉 ¡LISTO!

Tu aplicación Laravel ahora está desplegada en Railway con:
- ✅ PostgreSQL configurado
- ✅ HTTPS habilitado
- ✅ Deploy automático desde GitHub
- ✅ Dominio público accesible
- ✅ Variables de entorno seguras

**URL de tu API:** `https://your-app.up.railway.app/api`

---

## 📞 SIGUIENTES PASOS

1. Actualiza tu frontend para apuntar a la nueva URL
2. Prueba todos los endpoints
3. Configura un dominio personalizado (opcional)
4. Monitorea los logs regularmente
5. Ajusta recursos si es necesario

**¡Tu backend está en producción!** 🚀
