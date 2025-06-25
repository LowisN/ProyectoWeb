# Solución al Problema: "Error al crear el perfil de usuario"

## Descripción del problema

El sistema presentaba un error durante el proceso de registro de nuevos usuarios, tanto candidatos como reclutadores. Específicamente, después de crear exitosamente un usuario en el sistema de autenticación de Supabase, fallaba la creación del registro correspondiente en la tabla `perfiles`. 

El error se manifestaba como:

```
Error al crear el perfil de usuario
```

Este error ocurría porque la función `supabaseInsert` no estaba manejando correctamente la inserción en la base de datos o no devolvía la información necesaria para continuar con el proceso de registro.

## Causa raíz

1. **Falta de creación del perfil**: La aplicación creaba un usuario en Supabase Auth pero no siempre insertaba correctamente el registro en la tabla `perfiles`.

2. **Falta de manejo de errores**: No se estaba registrando información detallada sobre el error, lo que dificultaba su diagnóstico.

3. **Inconsistencia en respuestas**: La función `supabaseInsert` no siempre devolvía el registro creado con su ID.

4. **Headers incorrectos**: No se estaban enviando headers específicos a la API de Supabase para obtener el registro recién creado.

## Solución implementada

### 1. Mejora en la función `supabaseInsert`

Se modificó la función `supabaseInsert` para utilizar el header `Prefer: return=representation`, que hace que la API de Supabase devuelva el registro recién creado con su ID:

```php
function supabaseInsert($table, $data) {
    // Crear headers para obtener el registro creado
    $headers = [
        'Prefer: return=representation'
    ];
    
    // Realizar la inserción con los headers especiales
    $response = supabaseRequest("/rest/v1/$table", 'POST', $data, false, $headers);
    
    // Registrar información de diagnóstico
    if (isset($response['error'])) {
        error_log("Error en supabaseInsert para tabla $table: " . print_r($response['error'], true));
        error_log("Datos que se intentaron insertar: " . print_r($data, true));
    } else {
        // Verificar si se recibió el ID del registro creado
        if (isset($response[0]) && isset($response[0]['id'])) {
            error_log("Registro creado en tabla $table con ID: " . $response[0]['id']);
        } else {
            error_log("Registro creado en tabla $table, pero no se pudo obtener el ID");
        }
    }
    
    return $response;
}
```

### 2. Mejora en la función `supabaseRequest`

Se actualizó la función `supabaseRequest` para aceptar headers adicionales y un parámetro `isDryRun` para simular peticiones sin realizarlas realmente:

```php
function supabaseRequest($endpoint, $method = 'GET', $data = null, $isDryRun = false, $additionalHeaders = []) {
    // ...
    
    // Agregar headers adicionales si se proporcionan
    if (!empty($additionalHeaders)) {
        $headers = array_merge($headers, $additionalHeaders);
    }
    
    // Si es una simulación (dry run), solo preparar la solicitud pero no ejecutarla
    if ($isDryRun) {
        curl_close($ch);
        return []; // Simulamos éxito
    }
    
    // ...
}
```

### 3. Manejo mejorado de errores en los controladores de registro

Se mejoró el código en ambos controladores de registro (`registro_candidato_controller.php` y `registro_empresa_controller.php`) para:

- Registrar más información de diagnóstico
- Manejar mejor los errores
- Intentar recuperar el perfil mediante consultas alternativas si la respuesta no tiene la estructura esperada
- Mostrar mensajes de error más descriptivos

### 4. Herramienta para crear perfiles faltantes

Se creó una herramienta administrativa (`crear_perfiles_faltantes.php`) que permite:

- Identificar usuarios que tienen una cuenta en Supabase Auth pero no tienen un registro correspondiente en la tabla `perfiles`
- Crear automáticamente los perfiles faltantes
- Mostrar información detallada sobre el proceso

### 5. Datos adicionales en los perfiles

Se mejoró la estructura de datos para incluir más información útil en los perfiles:

- Nombre y apellidos
- Fecha de creación
- Tipo de usuario consistente

## Impacto de la solución

1. **Registro exitoso**: Los usuarios ahora pueden registrarse correctamente, con su perfil creado adecuadamente.

2. **Mejor diagnóstico**: Los errores se registran con más detalle en los logs del servidor.

3. **Mayor robustez**: Se implementaron mecanismos de recuperación para situaciones donde la primera inserción falla.

4. **Herramientas administrativas**: Ahora los administradores tienen herramientas para diagnosticar y corregir problemas con los perfiles.

## Recomendaciones adicionales

1. **Monitoreo continuo**: Seguir vigilando los logs de error para identificar posibles problemas con la creación de perfiles.

2. **Mejoras en la estructura de la base de datos**: Considerar la implementación de restricciones de integridad referencial o triggers en la base de datos para asegurar que cada usuario tenga un perfil asociado.

3. **Funciones RPC**: Si continúan los problemas, considerar la implementación de funciones RPC de Supabase que encapsulen la lógica de creación de usuarios y perfiles en una sola operación atómica.

## Documentación técnica

Este documento debe ser complementado con la documentación técnica del sistema, específicamente:

1. Diagrama de la base de datos
2. Descripción de los flujos de autenticación
3. Manual de usuario para las herramientas administrativas

---

Fecha de implementación: <?php echo date('Y-m-d'); ?>
