# Solución al error "Error al crear el perfil de usuario"

## Problema

El sistema estaba experimentando un error durante el proceso de registro donde se creaba correctamente el usuario en el sistema de autenticación de Supabase (auth.users), pero fallaba al crear el perfil correspondiente en la tabla `perfiles`. Esto provocaba que al intentar iniciar sesión, el usuario recibiera el mensaje "No se pudo obtener el perfil de usuario" o "Error al cargar el perfil: estructura de datos inválida".

## Causa raíz

1. **Transaccionalidad ausente**: El proceso de registro no era transaccional, por lo que si fallaba la creación del perfil después de crear el usuario de autenticación, quedaba un usuario "huérfano" sin perfil asociado.

2. **Manejo inadecuado de errores**: No se estaba registrando información detallada sobre los errores durante la creación del perfil, lo que dificultaba el diagnóstico.

3. **Falta de verificación de estructura de respuesta**: No se validaba adecuadamente la estructura de la respuesta cuando se creaba un perfil, lo que podía llevar a errores al intentar acceder al ID del perfil recién creado.

4. **Problemas de permisos en Supabase**: Es posible que existieran problemas de permisos RLS (Row Level Security) en Supabase que impidieran la inserción correcta en la tabla `perfiles`.

5. **Tabla inexistente**: Es posible que la tabla `perfiles` no existiera en la base de datos, lo que causaría errores de inserción sin mensajes claros.

## Soluciones implementadas

### 1. Mejora en el registro de errores y mensajes de diagnóstico

Se ha mejorado la función `supabaseInsert` para incluir información detallada sobre errores:

```php
function supabaseInsert($table, $data) {
    // Crear headers para obtener el registro creado
    $headers = [
        'Prefer: return=representation'
    ];
    
    // Registrar la llamada para diagnóstico
    error_log("supabaseInsert: Insertando en tabla $table con datos: " . json_encode($data));
    
    // Verificar que la tabla existe
    try {
        $tablaCheck = supabaseRequest("/rest/v1/$table?limit=0", 'GET');
        if (isset($tablaCheck['error'])) {
            error_log("Error al verificar la tabla $table: " . json_encode($tablaCheck['error']));
        }
    } catch (Exception $e) {
        error_log("Excepción al verificar tabla $table: " . $e->getMessage());
    }
    
    // Realizar la inserción con los headers especiales
    $response = supabaseRequest("/rest/v1/$table", 'POST', $data, false, $headers);
    
    // Registrar información de diagnóstico detallada
    if (isset($response['error'])) {
        error_log("Error en supabaseInsert para tabla $table: " . json_encode($response['error']));
        error_log("Datos que se intentaron insertar: " . json_encode($data));
    }
    
    return $response;
}
```

### 2. Implementación de métodos alternativos de inserción

Se ha mejorado el controlador `registro_candidato_controller.php` para implementar múltiples estrategias de inserción:

```php
// Cuando falla el método principal, intentar con REST API directo
if (!isset($perfilResponse[0]) || !isset($perfilResponse[0]['id'])) {
    error_log("Intentando crear perfil directamente por REST API");
    
    // Usar headers más específicos para la API de Supabase
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=representation'
    ];
    
    // Configurar cURL para conexión directa
    // ... (código de implementación)
}
```

También se ha desarrollado una herramienta específica (`crear_perfiles_faltantes.php`) que:

- Identifica usuarios registrados en auth.users que no tienen perfil asociado
- Permite crear perfiles faltantes de manera selectiva o masiva
- Determina automáticamente el tipo de usuario basado en los metadatos disponibles
- Crea registros complementarios en las tablas `candidatos` o `reclutadores` según corresponda

### 3. Herramienta de verificación de tablas

Se ha creado una herramienta (`verificar_tablas.php`) que:

- Verifica si existen todas las tablas necesarias en Supabase
- Proporciona el SQL para crear las tablas si no existen
- Muestra información detallada sobre la estructura esperada de cada tabla

### 4. Mejora en los mensajes de error y enlaces de diagnóstico

Se han actualizado los mensajes de error en la página de inicio de sesión para ofrecer soluciones específicas según el tipo de error:

```php
// Si el error es específicamente sobre crear el perfil de usuario
if (strpos($_GET['error'], 'Error al crear el perfil de usuario') !== false) {
    echo '<p class="info-message">
        Hemos detectado un problema al crear tu perfil de usuario. Un administrador puede 
        <a href="../config/verificar_tablas.php?debug=chambanetsetup2024">verificar las tablas en Supabase</a>
        para asegurarse de que la estructura de la base de datos es correcta.
    </p>';
}

// Para perfiles faltantes
echo '<p class="info-message">
    Si es un usuario nuevo, es posible que tu perfil no haya sido creado correctamente. Un administrador puede 
    <a href="../config/crear_perfiles_faltantes.php?debug=chambanetfix2024">ejecutar la herramienta para crear perfiles faltantes</a>
    para solucionar este problema.
</p>';
```

### 5. Mejoras en supabaseRequest

Se ha mejorado la función `supabaseRequest` para proporcionar información más detallada sobre errores HTTP:

```php
// Error HTTP
if ($httpCode >= 400) {
    error_log("Error HTTP $httpCode en $endpoint: $response");
    
    // Intentar decodificar la respuesta de error
    $errorData = json_decode($response, true);
    $errorMessage = "Error en la petición (HTTP $httpCode)";
    
    if (json_last_error() === JSON_ERROR_NONE && is_array($errorData)) {
        if (isset($errorData['message'])) {
            $errorMessage .= ": " . $errorData['message'];
        } elseif (isset($errorData['error'])) {
            if (is_string($errorData['error'])) {
                $errorMessage .= ": " . $errorData['error'];
            } elseif (is_array($errorData['error']) && isset($errorData['error']['message'])) {
                $errorMessage .= ": " . $errorData['error']['message'];
            }
        }
    } else {
        $errorMessage .= ": " . substr($response, 0, 200);
    }
    
    return [
        'error' => $errorMessage, 
        'statusCode' => $httpCode, 
        'response' => substr($response, 0, 1000)
    ];
}
```

### 6. Documentación del problema

Se ha documentado en detalle el problema y su solución para facilitar la resolución de casos similares en el futuro.

## Cómo utilizar la herramienta de corrección

1. Cuando un usuario reporte el error "No se pudo obtener el perfil de usuario", dirigirlo a iniciar sesión y comprobar que el error persiste.

2. Como administrador, acceder a la herramienta de corrección:
   ```
   /config/crear_perfiles_faltantes.php?debug=chambanetfix2024
   ```

3. Usar la opción "Crear perfil para un email específico" proporcionando el correo del usuario afectado.

4. Verificar que el perfil se ha creado correctamente.

5. Solicitar al usuario que intente iniciar sesión nuevamente.

## Recomendaciones para prevención futura

1. **Implementar transacciones**: Considerar el uso de transacciones para garantizar que tanto el usuario como su perfil se crean o no se crea ninguno.

2. **Verificación automática**: Implementar una verificación durante el inicio de sesión que identifique y corrija automáticamente usuarios sin perfil.

3. **Mejorar pruebas**: Incluir pruebas específicas para validar que el proceso de registro crea correctamente todos los registros necesarios.

4. **Revisar las políticas RLS**: Asegurar que las políticas de seguridad en Supabase permiten las operaciones necesarias para la creación de perfiles.

---
Documento creado: <?php echo date('d/m/Y'); ?>
