# ✅ CHECKLIST PRE-DEPLOY RAILWAY

## 📋 Archivos Creados/Actualizados
- [ ] `Procfile` - Comando de inicio para Railway
- [ ] `nixpacks.toml` - Configuración de build
- [ ] `.env.example` - Variables de entorno actualizadas
- [ ] `composer.json` - Scripts post-install agregados
- [ ] `DEPLOY_RAILWAY.md` - Guía completa de despliegue
- [ ] `railway.env.example` - Variables de entorno para copiar
- [ ] `prepare-railway.sh` - Script de preparación

## 🔧 Configuración del Proyecto
- [ ] `.gitignore` incluye `.env` (protege credenciales)
- [ ] `composer.lock` está actualizado
- [ ] No hay errores en `php artisan config:clear`
- [ ] APP_KEY será generada en Railway

## 📦 Dependencias
- [ ] Composer dependencies instaladas
- [ ] No hay paquetes dev en producción
- [ ] PostgreSQL compatible (usando pgsql driver)

## 🔐 Seguridad
- [ ] `APP_DEBUG=false` en producción
- [ ] `.env` NO está en el repositorio
- [ ] `.env.example` está actualizado
- [ ] Credenciales sensibles no están hardcodeadas

## 🗄️ Base de Datos
- [ ] Migraciones están en `database/migrations/`
- [ ] Seeders están listos (si los necesitas)
- [ ] PostgreSQL será provisto por Railway

## 🚀 GitHub
- [ ] Repositorio actualizado en GitHub
- [ ] Rama `main` está lista
- [ ] Commits descriptivos realizados

## ⚙️ Variables de Entorno (Railway)
- [ ] APP_NAME
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_KEY (generar con `php artisan key:generate --show`)
- [ ] APP_URL (tu URL de Railway)
- [ ] DB_CONNECTION=pgsql
- [ ] SESSION_DRIVER=database
- [ ] CACHE_STORE=database
- [ ] QUEUE_CONNECTION=database

## 🌐 CORS (si tienes frontend)
- [ ] SANCTUM_STATEFUL_DOMAINS configurado
- [ ] SESSION_DOMAIN configurado
- [ ] config/cors.php actualizado si es necesario

## 📝 Comandos a ejecutar ANTES de subir a GitHub:

```bash
# 1. Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 2. Verificar composer
composer install --no-dev --optimize-autoloader

# 3. Verificar que no hay errores
php artisan config:cache
php artisan route:cache

# 4. Limpiar todo antes de subir
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 5. Subir a GitHub
git add .
git commit -m "Preparar proyecto para Railway"
git push origin main
```

## 🎯 Después del Deploy en Railway:

- [ ] Servicio Laravel creado en Railway
- [ ] PostgreSQL agregado al proyecto
- [ ] Variables de entorno configuradas
- [ ] Dominio generado
- [ ] APP_URL actualizada con dominio de Railway
- [ ] Migraciones ejecutadas (automático con Procfile)
- [ ] `/api/test` responde correctamente
- [ ] Login funciona
- [ ] Logs sin errores

## ✅ TODO LISTO - PUEDES DESPLEGAR

Sigue la guía en `DEPLOY_RAILWAY.md` paso a paso.
