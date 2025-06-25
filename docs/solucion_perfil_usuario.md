# Solución al error "No se pudo obtener el perfil de usuario"

## Descripción del problema

El sistema mostraba el error "No se pudo obtener el perfil de usuario" porque no se estaba creando correctamente el registro en la tabla `perfiles` cuando un usuario se registraba en el sistema. El flujo de registro en ChambaNet debe incluir dos pasos principales:

1. Crear el usuario en el autenticador de Supabase (tabla auth.users)
2. Crear un registro en la tabla `perfiles` vinculado al usuario por su ID

El problema ocurría porque aunque se estaba creando correctamente el usuario en el sistema de autenticación, en algunos casos no se estaba creando el registro correspondiente en la tabla `perfiles`, lo que causaba errores cuando el usuario intentaba acceder a las páginas del sistema.

## Causas posibles

1. **Errores durante el proceso de registro**: Un fallo en la conexión o un error en Supabase podía causar que se creara el usuario pero no su perfil.

2. **Problemas de permisos**: La cuenta que ejecuta las consultas podría no tener permisos para insertar en la tabla `perfiles`.

3. **Inconsistencias en la migración de datos**: Si se importaron usuarios desde otro sistema o se crearon manualmente, podrían faltar sus perfiles correspondientes.

## Solución implementada

Se han realizado las siguientes mejoras para solucionar el problema:

### 1. Mejoras en los controladores de registro

- Se añadió el campo `email` en la tabla `perfiles` para facilitar la búsqueda de perfiles por correo electrónico.
- Se ha mejorado el registro de errores (logging) cuando ocurre un problema durante la creación del perfil.

### 2. Herramienta de diagnóstico y corrección

- Se ha creado una herramienta en `admin/diagnostico_auth.php` que:
  - Detecta usuarios que existen en auth.users pero no tienen un perfil en la tabla `perfiles`
  - Permite crear automáticamente los perfiles faltantes con el tipo adecuado
  - Para candidatos, crea también un registro en la tabla `candidatos`
  - Para reclutadores, intenta vincularlos con sus empresas correspondientes

### 3. Validación más robusta

- Se ha mejorado la función `verificarAcceso()` para intentar recuperar el tipo de usuario directamente desde la base de datos cuando no está disponible en la sesión.
- Se han añadido validaciones más robustas en las páginas de perfil de candidatos y empresas para manejar mejor los casos donde la estructura de datos no es la esperada.

### 4. Mensajes de error más descriptivos

- Se muestran enlaces específicos a las herramientas de diagnóstico cuando se detecta este tipo de error.
- Los mensajes de error ahora incluyen información más detallada sobre qué falló, lo que facilita la solución del problema.

## Cómo resolver el problema

Si un usuario recibe el error "No se pudo obtener el perfil de usuario", hay dos formas de solucionarlo:

1. **Para administradores**:
   - Acceder a la herramienta de diagnóstico: `admin/diagnostico_auth.php?debug=chambanetdiag2024`
   - Utilizar la opción "Detectar Usuarios Sin Perfil"
   - Crear los perfiles faltantes con el botón "Crear Perfiles Faltantes"

2. **Para el usuario**:
   - Registrarse nuevamente usando un correo electrónico diferente
   - Contactar con un administrador para que resuelva el problema

## Mejoras futuras recomendadas

1. **Implementar transacciones**: Asegurar que la creación del usuario y su perfil se realice en una transacción para evitar inconsistencias.

2. **Sistema de verificación automática**: Implementar un chequeo periódico que busque y corrija inconsistencias entre usuarios y perfiles.

3. **Mejora del proceso de registro**: Actualizar el flujo de registro para manejar mejor los errores y reintentar la creación del perfil si falla.

4. **Validación de usuarios huérfanos**: Al iniciar sesión, verificar que existe el perfil y crearlo automáticamente si no existe.

---

Documento creado: 25 de junio de 2025
