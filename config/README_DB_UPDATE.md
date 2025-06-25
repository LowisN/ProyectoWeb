# Actualización de Estructura de Base de Datos - ChambaNet

Este documento describe los cambios realizados a la estructura de la base de datos del proyecto ChambaNet y los pasos para implementarlos.

## Cambios principales

1. **Nueva tabla `usuario`**: Contiene datos generales comunes a todos los tipos de usuarios
   - ID único
   - Referencias a auth.users
   - Datos personales básicos (nombre, apellidos, teléfono, email)

2. **Tabla `perfiles` actualizada**: Ahora solo contiene los 3 tipos de perfiles
   - Administrador
   - Candidato
   - Reclutador
   - Referencia a la tabla usuario

3. **Tablas de datos específicos actualizadas**:
   - `candidatos`: Solo contiene datos específicos de candidatos
   - `reclutadores`: Solo contiene datos específicos de reclutadores

4. **Tabla `empresas`**: Se mantiene con la misma estructura

## Archivos SQL actualizados

1. **database_updated.sql**: Contiene la nueva estructura de tablas
2. **functions_updated.sql**: Funciones adaptadas a la nueva estructura
3. **policies_updated.sql**: Políticas de seguridad RLS actualizadas
4. **migration.sql**: Script para migrar datos desde la estructura anterior
5. **db_check.sql**: Script para verificar la integridad de la estructura

## Guía de implementación

### Para una instalación nueva

1. Accede a Supabase Studio: https://app.supabase.com
2. Selecciona tu proyecto
3. Ve a la sección "SQL Editor"
4. Ejecuta primero el archivo `database_updated.sql`
5. Ejecuta luego el archivo `functions_updated.sql`
6. Finalmente, ejecuta el archivo `policies_updated.sql`

### Para migrar desde la estructura anterior

1. Accede a Supabase Studio
2. Crea una copia de seguridad de los datos actuales (importante!)
3. Ejecuta los archivos en este orden:
   - `database_updated.sql`
   - `migration.sql`
   - `functions_updated.sql`
   - `policies_updated.sql`

### Verificación

Puedes verificar que la estructura se ha creado correctamente:

1. Abriendo el archivo `setup_database_updated.php` en tu navegador
2. O ejecutando el script `db_check.sql` en el Editor SQL de Supabase

## Cambios en el código PHP

Los controladores y modelos deberán actualizarse para:
- Crear usuarios en la tabla `usuario` en lugar de directamente en `perfiles`
- Asociar los perfiles con los usuarios correspondientes
- Actualizar las consultas para obtener datos según la nueva estructura

## Nuevas relaciones entre tablas

```
usuario ---> perfiles ---> candidatos/reclutadores
```

- Un `usuario` puede tener un `perfil`
- Un `perfil` puede ser de tipo candidato, reclutador o administrador
- Si es candidato, tendrá datos en la tabla `candidatos`
- Si es reclutador, tendrá datos en la tabla `reclutadores`
- Los reclutadores se relacionan con una `empresa`

## Ventajas de la nueva estructura

1. Mejor organización de los datos
2. Menos duplicación de información
3. Mayor claridad en los roles de usuario
4. Mayor facilidad para añadir nuevos tipos de perfil en el futuro
5. Separación más limpia entre datos comunes y específicos
