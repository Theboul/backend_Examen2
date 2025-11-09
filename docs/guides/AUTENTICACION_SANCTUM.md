# 🔐 GUÍA DE PRUEBAS - LARAVEL SANCTUM IMPLEMENTADO

## ✅ **CONFIGURACIÓN COMPLETADA**

- ✅ Laravel Sanctum instalado
- ✅ Tabla `personal_access_tokens` creada
- ✅ Expiración configurada: **2 horas de inactividad**
- ✅ Middleware `auth:sanctum` aplicado a rutas protegidas
- ✅ Logout revoca tokens correctamente

---

## 🧪 **PRUEBAS EN POSTMAN**

### **1️⃣ LOGIN - Obtener Token**

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
      "id_rol": 1,
      "rol": "Administrador",
      "perfil": { ... },
      "docente": null
    },
    "token": "1|abc123def456xyz789...",
    "token_type": "Bearer",
    "expires_in": 120,
    "primer_ingreso": false,
    "debe_cambiar_password": false
  }
}
```

**⚠️ IMPORTANTE:** Copia el valor de `token` para las siguientes peticiones.

---

### **2️⃣ USAR TOKEN - Listar Gestiones**

```http
GET http://localhost:8000/api/gestiones
Authorization: Bearer 1|abc123def456xyz789...
Content-Type: application/json
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": [ ... ]
}
```

---

### **3️⃣ LOGOUT - Cerrar Sesión**

```http
POST http://localhost:8000/api/auth/logout
Authorization: Bearer 1|abc123def456xyz789...
Content-Type: application/json
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Sesión cerrada exitosamente"
}
```

**Verificación:**
Después del logout, si intentas usar el mismo token:

```http
GET http://localhost:8000/api/gestiones
Authorization: Bearer 1|abc123def456xyz789...
```

**Respuesta esperada:**
```json
{
  "message": "Unauthenticated."
}
```
❌ El token ya no es válido

---

### **4️⃣ EXPIRACIÓN AUTOMÁTICA**

Después de **2 horas de inactividad**, el token expira automáticamente.

**Para probar esto rápidamente (cambiar config temporalmente):**

1. Edita `config/sanctum.php`:
```php
'expiration' => 1, // 1 minuto (solo para prueba)
```

2. Reinicia servidor:
```bash
php artisan config:clear
php artisan serve
```

3. Login y obtén token
4. Espera 1 minuto
5. Intenta usar el token:

```http
GET http://localhost:8000/api/gestiones
Authorization: Bearer [TOKEN_EXPIRADO]
```

**Respuesta esperada:**
```json
{
  "message": "Unauthenticated."
}
```

6. Vuelve a cambiar a 120 minutos:
```php
'expiration' => 120, // 2 horas
```

---

## 📱 **INTEGRACIÓN CON FRONTEND (React)**

### **1. Guardar token en localStorage**

```javascript
// Después del login exitoso
const handleLogin = async (credentials) => {
  try {
    const response = await axios.post('http://localhost:8000/api/auth/login', credentials);
    
    if (response.data.success) {
      const { token, user, expires_in } = response.data.data;
      
      // Guardar en localStorage
      localStorage.setItem('auth_token', token);
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('token_expires_at', Date.now() + (expires_in * 60 * 1000));
      
      // Configurar header por defecto de axios
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      console.log('Login exitoso');
    }
  } catch (error) {
    console.error('Error al iniciar sesión:', error);
  }
};
```

---

### **2. Verificar expiración antes de cada request**

```javascript
// axios interceptor
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    const expiresAt = localStorage.getItem('token_expires_at');
    
    // Verificar si el token expiró
    if (expiresAt && Date.now() > parseInt(expiresAt)) {
      // Token expirado - logout automático
      localStorage.clear();
      window.location.href = '/login';
      return Promise.reject('Token expirado');
    }
    
    // Agregar token al header
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);
```

---

### **3. Manejar respuestas 401 (Unauthorized)**

```javascript
// axios response interceptor
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token inválido o expirado - logout
      localStorage.clear();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

---

### **4. Logout manual**

```javascript
const handleLogout = async () => {
  try {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
      // Llamar endpoint de logout (revoca token en BD)
      await axios.post('http://localhost:8000/api/auth/logout', {}, {
        headers: { Authorization: `Bearer ${token}` }
      });
    }
    
    // Limpiar localStorage
    localStorage.clear();
    
    // Limpiar header de axios
    delete axios.defaults.headers.common['Authorization'];
    
    // Redirigir a login
    window.location.href = '/login';
    
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
    // Limpiar localStorage de todos modos
    localStorage.clear();
  }
};
```

---

### **5. Verificar si usuario está autenticado**

```javascript
const isAuthenticated = () => {
  const token = localStorage.getItem('auth_token');
  const expiresAt = localStorage.getItem('token_expires_at');
  
  if (!token || !expiresAt) {
    return false;
  }
  
  // Verificar si el token no ha expirado
  return Date.now() < parseInt(expiresAt);
};

// Uso en componentes
useEffect(() => {
  if (!isAuthenticated()) {
    navigate('/login');
  }
}, []);
```

---

## 🔍 **VERIFICAR EN BASE DE DATOS**

### **Ver tokens activos:**
```sql
SELECT 
    t.id,
    t.tokenable_id,
    u.email,
    t.name,
    t.created_at,
    t.last_used_at,
    t.expires_at
FROM personal_access_tokens t
INNER JOIN users u ON t.tokenable_id = u.id_usuario
ORDER BY t.created_at DESC;
```

### **Eliminar todos los tokens de un usuario:**
```sql
DELETE FROM personal_access_tokens WHERE tokenable_id = 1;
```

### **Eliminar tokens expirados:**
```sql
DELETE FROM personal_access_tokens 
WHERE expires_at < NOW();
```

---

## ⚙️ **CONFIGURACIÓN ACTUAL**

### **`config/sanctum.php`:**
```php
'expiration' => 120, // 2 horas (120 minutos)
```

### **Cambiar tiempo de expiración:**
```php
// 1 hora
'expiration' => 60,

// 4 horas
'expiration' => 240,

// 24 horas
'expiration' => 1440,

// Sin expiración
'expiration' => null,
```

---

## 🎯 **FLUJO COMPLETO**

```
1. Usuario hace LOGIN
   ↓
2. Backend crea token en tabla personal_access_tokens
   ↓
3. Frontend guarda token en localStorage
   ↓
4. Cada request incluye: Authorization: Bearer {token}
   ↓
5. Sanctum valida token automáticamente
   ↓
6. Si token expiró (>2 horas) → 401 Unauthorized
   ↓
7. Frontend detecta 401 → Logout automático
   ↓
8. Usuario vuelve a hacer LOGIN
```

---

## ✅ **VENTAJAS DE ESTA IMPLEMENTACIÓN**

1. ✅ **Seguridad:** Tokens hasheados en BD
2. ✅ **Control:** Puedes revocar tokens individualmente
3. ✅ **Expiración:** Automática después de 2 horas
4. ✅ **Logout real:** Elimina token de BD
5. ✅ **Escalable:** Múltiples dispositivos con tokens únicos
6. ✅ **Trazabilidad:** `last_used_at` rastrea actividad

---

## 🧪 **TESTS A REALIZAR**

- [ ] Login exitoso devuelve token
- [ ] Token permite acceso a rutas protegidas
- [ ] Logout revoca el token
- [ ] Token expirado retorna 401
- [ ] Usuario inactivo retorna 403
- [ ] Rol incorrecto retorna 403
- [ ] Sin token retorna 401

---

¡Todo listo para probar! 🚀
