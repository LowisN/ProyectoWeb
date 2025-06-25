# Solución al problema de recursión infinita en políticas RLS de Supabase

## Descripción del problema

El error `infinite recursion detected in policy for relation "perfiles"` se produce cuando una política de seguridad de filas (RLS) en Supabase contiene una referencia circular. Esto ocurre cuando:

1. Una política hace referencia a la misma tabla que está protegiendo de una manera que crea un bucle.
2. Hay referencias cruzadas entre políticas de diferentes tablas que forman un ciclo.

Este error impide que los usuarios puedan acceder a sus perfiles, lo que provoca fallos en la autenticación, registro y funcionamiento general de la aplicación.

## Causas comunes

Las causas más frecuentes son:

1. **Políticas autorreferenciales**: Cuando una política incluye una consulta a la misma tabla.
   ```sql
   -- Ejemplo problemático
   CREATE POLICY "perfiles_policy" ON perfiles FOR SELECT
     USING (user_id IN (SELECT user_id FROM perfiles WHERE ...));
   ```

2. **Referencias circulares entre tablas**: Cuando las políticas de varias tablas se referencian entre sí formando un ciclo.
   ```sql
   -- Tabla A referencia a Tabla B
   CREATE POLICY "policy_a" ON tabla_a USING (id IN (SELECT ref_id FROM tabla_b ...));
   
   -- Tabla B referencia a Tabla A
   CREATE POLICY "policy_b" ON tabla_b USING (ref_id IN (SELECT id FROM tabla_a ...));
   ```

3. **Complejidad excesiva**: Políticas con subqueries anidadas o demasiado complejas.

## Soluciones implementadas

Para resolver este problema, hemos implementado varias soluciones:

### 1. Corrección de políticas RLS

Hemos creado un script SQL (`corregir_politicas_rls.sql`) que:

- Elimina todas las políticas existentes con problemas
- Crea nuevas políticas simplificadas que evitan recursiones
- Implementa reglas claras y concisas para cada operación (SELECT, INSERT, UPDATE, DELETE)
- Crea una función RPC especial para acceso de emergencia

### 2. Implementación de bypass para RLS

Hemos agregado la función `getProfileBypass()` en `supabase.php` que permite:
- Obtener perfiles de usuario evitando las políticas RLS problemáticas
- Usar métodos alternativos cuando se detectan errores de recursión
- Mantener la funcionalidad de la aplicación mientras se corrigen las políticas

### 3. Herramienta de diagnóstico para RLS

Creamos `diagnostico_rls.php`, que:
- Detecta automáticamente problemas con las políticas RLS
- Propone soluciones específicas para cada problema
- Ofrece scripts SQL para corregir las políticas
- Permite probar el acceso a perfiles específicos

### 4. Mejoras en el manejo de errores

- Función `obtenerPerfilRobusto()` en `rls_helper.php` que intenta múltiples métodos de acceso
- Detección automática de errores de RLS con `esErrorRLS()`
- Mensajes de error más descriptivos con enlaces a herramientas de diagnóstico

## Cómo resolver el problema

### Solución permanente (recomendada):

1. Accede al panel de administración de Supabase
2. Ve al editor SQL
3. Ejecuta el script `corregir_politicas_rls.sql`
4. Verifica que las nuevas políticas funcionan correctamente

### Solución temporal (si no tienes acceso a Supabase):

1. Utiliza la función `getProfileBypass()` para acceder a los perfiles
2. Implementa la función `obtenerPerfilRobusto()` en tu código
3. Usa la herramienta `diagnostico_rls.php` para verificar el estado de las políticas

## Prevención de futuros problemas

Para evitar que este problema vuelva a ocurrir:

1. **Simplifica las políticas RLS**: Evita consultas complejas o autoreferencia
2. **Prueba antes de implementar**: Verifica cada política nueva con varios casos
3. **Documenta las políticas**: Mantén un registro de las políticas y su propósito
4. **Implementa herramientas de diagnóstico**: Usa las herramientas creadas para detectar problemas temprano

## Referencias

- [Documentación de Supabase sobre RLS](https://supabase.io/docs/guides/auth/row-level-security)
- [Políticas de PostgreSQL](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [Diagnóstico y solución de problemas de recursión](https://supabase.com/docs/guides/database/postgres/row-level-security#debugging-row-level-security)
