# Solución al Error: "No nurses found in the database"

## Problema
La aplicación Angular muestra el error "Connection Error: No nurses found in the database" aunque hay 5 enfermeros en la base de datos.

## Solución Paso a Paso

### 1. Verificar que el servidor Symfony esté corriendo

Abre una terminal en la carpeta del proyecto Symfony y ejecuta:

```bash
# Opción 1: Usando Symfony CLI (recomendado)
symfony server:start

# Opción 2: Usando PHP built-in server
php -S localhost:8000 -t public
```

El servidor debería estar corriendo en `http://localhost:8000`

### 2. Verificar que el endpoint funciona

Abre tu navegador o usa curl para probar el endpoint:

```
http://localhost:8000/nurse/index
```

Deberías ver un JSON con los 5 enfermeros.

### 3. Verificar la URL del backend en Angular

Asegúrate de que tu aplicación Angular esté configurada para conectarse a:
- `http://localhost:8000/nurse/index` (o el puerto que uses)

### 4. Verificar CORS

La configuración de CORS ya está correcta en `.env`:
```
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

Esto permite conexiones desde `localhost:4200` (Angular).

### 5. Limpiar caché de Symfony

Si después de hacer cambios no funciona, limpia la caché:

```bash
php bin/console cache:clear
```

## Endpoints Disponibles

- `GET http://localhost:8000/nurse/index` - Obtener todos los enfermeros
- `GET http://localhost:8000/nurse/{id}` - Obtener enfermero por ID
- `GET http://localhost:8000/nurse/name/{name}` - Obtener enfermero por nombre
- `POST http://localhost:8000/nurse/create` - Crear nuevo enfermero

## Verificación Rápida

Ejecuta este comando para verificar que hay datos:

```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM nurse"
```

Debería mostrar: 5 enfermeros

## Si el problema persiste

1. Verifica la consola del navegador (F12) para ver errores de CORS o conexión
2. Verifica que el servidor Symfony esté escuchando en el puerto correcto
3. Verifica que Angular esté usando la URL correcta del backend
