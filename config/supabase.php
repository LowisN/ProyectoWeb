<?php
// Configuración de Supabase
define('SUPABASE_URL', 'https://wklyvlosbiylfvovakly.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndrbHl2bG9zYml5bGZ2b3Zha2x5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTA4MTI2ODIsImV4cCI6MjA2NjM4ODY4Mn0.fthVr-m8UkVX4tUW_zzdwhM6mgqohRbez-Oqek4efxo');

// Función para inicializar cliente Supabase usando cURL
function supabaseRequest($endpoint, $method = 'GET', $data = null, $isDryRun = false, $additionalHeaders = []) {
    $url = SUPABASE_URL . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_KEY,
        'apikey: ' . SUPABASE_KEY
    ];
    
    // Agregar headers adicionales si se proporcionan
    if (!empty($additionalHeaders)) {
        $headers = array_merge($headers, $additionalHeaders);
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Si es una simulación (dry run), solo preparar la solicitud pero no ejecutarla
    if ($isDryRun) {
        curl_close($ch);
        return []; // Simulamos éxito
    }
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log para diagnóstico
    error_log("supabaseRequest a $endpoint - HTTP Code: $httpCode");
    
    // Error de cURL
    if ($error) {
        error_log("Error de cURL: $error");
        return ['error' => "Error de conexión: $error"];
    }
    
    // Si la respuesta está vacía pero no hay error, podría ser un conjunto vacío de registros
    if (empty($response) && in_array($httpCode, [200, 201, 204])) {
        error_log("Respuesta vacía, pero código HTTP indica éxito ($httpCode)");
        return [];
    }
    
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
    
    // Decodificar la respuesta JSON
    $decodedResponse = json_decode($response, true);
    
    // Si la decodificación falla, podría ser un error de formato
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error al decodificar JSON: " . json_last_error_msg() . " - Respuesta: " . substr($response, 0, 200));
        return ['error' => 'Error al procesar la respuesta del servidor'];
    }
    
    // Si la respuesta es null pero el status es OK, devolver array vacío
    if ($decodedResponse === null && in_array($httpCode, [200, 204])) {
        return [];
    }
    
    return $decodedResponse;
}

// Función para registrar un nuevo usuario
function supabaseSignUp($email, $password, $userData = []) {
    // Verificar parámetros antes de enviar
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Email inválido', 'details' => 'El formato del email no es correcto'];
    }
    
    if (empty($password) || strlen($password) < 8) {
        return ['error' => 'Contraseña inválida', 'details' => 'La contraseña debe tener al menos 8 caracteres'];
    }
    
    $data = [
        'email' => $email,
        'password' => $password,
        'data' => $userData
    ];
    
    // Registrar inicio de la petición para diagnóstico
    error_log("Iniciando registro de usuario en Supabase: $email");
    
    // Intentar la petición con un tiempo de espera razonable
    $startTime = microtime(true);
    $response = supabaseRequest('/auth/v1/signup', 'POST', $data);
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    // Verificar si el registro fue exitoso
    if (isset($response['user']) && isset($response['user']['id'])) {
        // Registrar detalles para diagnóstico
        error_log("¡Usuario creado correctamente! Email: $email, ID: {$response['user']['id']}, Tiempo: {$executionTime}s");
        
        // Añadimos información de tiempo para diagnóstico
        $response['_debug'] = [
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else if (isset($response['error'])) {
        error_log("Error al crear usuario $email (tiempo: ${executionTime}s): " . print_r($response['error'], true));
        
        // Diagnóstico adicional según el tipo de error
        if (isset($response['statusCode'])) {
            switch ($response['statusCode']) {
                case 400:
                    error_log("Error 400: Solicitud incorrecta. Revisa el formato de los datos enviados.");
                    break;
                case 401:
                    error_log("Error 401: Credenciales API inválidas. Verifica SUPABASE_KEY.");
                    break;
                case 403:
                    error_log("Error 403: Acceso prohibido. Verifica permisos en Supabase.");
                    break;
                case 404:
                    error_log("Error 404: Endpoint no encontrado. Verifica la URL de Supabase.");
                    break;
                case 409:
                    error_log("Error 409: Conflicto. El email probablemente ya está registrado.");
                    break;
                case 422:
                    error_log("Error 422: Datos de usuario inválidos. Formato incorrecto.");
                    break;
                case 429:
                    error_log("Error 429: Demasiadas solicitudes. Límite de API excedido.");
                    break;
                case 500:
                case 502:
                case 503:
                    error_log("Error $response[statusCode]: Error en el servidor de Supabase.");
                    break;
            }
        }
    } else {
        error_log("Respuesta inesperada al registrar usuario $email: " . print_r($response, true));
    }
    
    return $response;
}

// Función para iniciar sesión
function supabaseSignIn($email, $password) {
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    return supabaseRequest('/auth/v1/token?grant_type=password', 'POST', $data);
}

// Función para obtener datos de una tabla
function supabaseFetch($table, $select = '*', $filters = []) {
    $query = "/rest/v1/$table?select=" . urlencode($select);
    
    if (!empty($filters)) {
        foreach ($filters as $column => $value) {
            $query .= "&$column=eq." . urlencode($value);
        }
    }
    
    // Registrar la consulta para diagnóstico
    error_log("supabaseFetch: Consultando tabla $table con filtros: " . json_encode($filters));
    
    $result = supabaseRequest($query);
    
    // Asegurar que el resultado sea un array de registros
    // Si hay un error, devolvemos el error para que pueda ser manejado
    if (isset($result['error'])) {
        error_log("Error en supabaseFetch para tabla $table: " . json_encode($result['error']));
        
        // Detectar errores de recursión infinita en políticas RLS
        if (isset($result['error']) && 
            (strpos($result['error'], 'infinite recursion') !== false || 
             strpos($result['error'], 'recursion') !== false)) {
            error_log("¡ALERTA! Detectada recursión infinita en políticas RLS para tabla $table.");
            // Añadir información adicional para facilitar la solución
            $result['error_details'] = [
                'type' => 'rls_recursion',
                'table' => $table,
                'suggestion' => 'Es necesario revisar y corregir las políticas RLS en Supabase para esta tabla'
            ];
        }
        
        return $result;
    }
    
    // Verificar si la respuesta es un array
    if (!is_array($result)) {
        error_log("supabaseFetch: La respuesta no es un array para tabla $table");
        return [];
    }
    
    // Si está vacío o no es un array asociativo (primera clave no es numérica)
    // ya está en el formato correcto de array de registros
    if (empty($result) || array_key_first($result) !== 0) {
        return $result;
    }
    
    return $result;
}

// Función para insertar datos en una tabla
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
    
    // Registrar información de diagnóstico
    if (isset($response['error'])) {
        error_log("Error en supabaseInsert para tabla $table: " . json_encode($response['error']));
        error_log("Código HTTP: " . (isset($response['statusCode']) ? $response['statusCode'] : 'desconocido'));
        error_log("Datos que se intentaron insertar: " . json_encode($data));
        
        // Intentar determinar el problema específico
        $errorMsg = $response['error']['message'] ?? ($response['error']['msg'] ?? 'Error desconocido');
        
        // Si es un error de permisos, hacerlo más explícito
        if (stripos($errorMsg, 'permission') !== false || stripos($errorMsg, 'not allowed') !== false) {
            error_log("Problema de permisos detectado al insertar en $table. Verifique las reglas RLS en Supabase.");
        }
        
        // Si es un error de violación de restricción única
        if (stripos($errorMsg, 'unique') !== false || stripos($errorMsg, 'duplicate') !== false) {
            error_log("Posible violación de restricción única en tabla $table.");
        }
    } else {
        // Verificar si se recibió el ID del registro creado
        if (isset($response[0]) && isset($response[0]['id'])) {
            error_log("Registro creado correctamente en tabla $table con ID: " . $response[0]['id']);
        } else {
            error_log("Registro aparentemente creado en tabla $table, pero no se pudo obtener el ID. Respuesta: " . json_encode($response));
        }
    }
    
    return $response;
}

// Función para actualizar datos en una tabla
function supabaseUpdate($table, $data, $filters = []) {
    $query = "/rest/v1/$table";
    
    if (!empty($filters)) {
        $query .= "?";
        $filterStrings = [];
        
        foreach ($filters as $column => $value) {
            $filterStrings[] = "$column=eq." . urlencode($value);
        }
        
        $query .= implode('&', $filterStrings);
    }
    
    return supabaseRequest($query, 'PATCH', $data);
}

// Función para eliminar datos de una tabla
function supabaseDelete($table, $filters = []) {
    $query = "/rest/v1/$table";
    
    if (!empty($filters)) {
        $query .= "?";
        $filterStrings = [];
        
        foreach ($filters as $column => $value) {
            $filterStrings[] = "$column=eq." . urlencode($value);
        }
        
        $query .= implode('&', $filterStrings);
    }
    
    return supabaseRequest($query, 'DELETE');
}

// Función para solicitar recuperación de contraseña
function supabaseResetPassword($email) {
    $data = [
        'email' => $email
    ];
    
    return supabaseRequest('/auth/v1/recover', 'POST', $data);
}

// Función para cerrar sesión
function supabaseSignOut($accessToken) {
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];
    
    // Aquí habría que modificar supabaseRequest para aceptar headers personalizados
    return supabaseRequest('/auth/v1/logout', 'POST');
}

/**
 * Función para ejecutar consultas SQL directas
 * Nota: Esta función requiere permisos especiales y solo debe usarse para administración
 * @param string $query Consulta SQL a ejecutar
 * @return array Resultado de la operación
 */
function supabaseRawQuery($query) {
    // Esta función necesitaría una integración especial con Supabase
    // utilizando una función RPC configurada en el lado del servidor
    
    $url = getenv('SUPABASE_URL') ?: SUPABASE_URL;
    $key = getenv('SUPABASE_KEY') ?: SUPABASE_KEY;
    
    // Llamar a una función RPC predefinida en Supabase que ejecute SQL
    // Esto es un ejemplo y debe implementarse correctamente en el servidor
    $endpoint = '/rest/v1/rpc/ejecutar_sql';
    
    $data = [
        'query' => $query
    ];
    
    $ch = curl_init($url . $endpoint);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    
    return json_decode($response, true);
}

/**
 * Función para ejecutar consultas SQL SELECT directas y obtener resultados
 * @param string $query Consulta SQL SELECT a ejecutar
 * @return array Resultados de la consulta
 */
function supabaseFetchRaw($query) {
    // Similar a la función anterior pero para consultas SELECT
    // Esto es un ejemplo y debe implementarse correctamente
    return supabaseRawQuery($query);
}

/**
 * Función alternativa para obtener perfiles cuando hay problemas de recursión en RLS
 * Esta función utiliza un método directo que evita las políticas RLS problemáticas
 * @param string $userId ID del usuario cuyo perfil queremos obtener (opcional)
 * @param string $email Email del usuario cuyo perfil queremos obtener (opcional)
 * @return array Resultado de la operación
 */
function getProfileBypass($userId = null, $email = null) {
    if (empty($userId) && empty($email)) {
        return ['error' => 'Se requiere userId o email para buscar un perfil'];
    }
    
    error_log("Intentando obtener perfil con bypass RLS para " . ($userId ? "userId: $userId" : "email: $email"));
    
    // Usar la API de funciones RPC que puede estar configurada para omitir las políticas RLS
    // O usar un token con privilegios elevados si está disponible
    
    $endpoint = '/rest/v1/rpc/get_profile_bypassing_rls';
    $data = [];
    
    if (!empty($userId)) {
        $data['user_id'] = $userId;
    } else {
        $data['email'] = $email;
    }
    
    try {
        // Intentar primero con una función RPC si existe
        $response = supabaseRequest($endpoint, 'POST', $data);
        
        if (isset($response['error']) && strpos($response['error'], '404') !== false) {
            // Si no existe la función RPC, intentar con un método alternativo
            error_log("Función RPC no encontrada, intentando método directo...");
            
            // Método alternativo usando headers especiales
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . SUPABASE_KEY,
                'Authorization: Bearer ' . SUPABASE_KEY,
                'X-Client-Info: bypass-rls' // Header especial que podría configurarse en el servidor
            ];
            
            $url = SUPABASE_URL . '/rest/v1/perfiles?';
            
            if (!empty($userId)) {
                $url .= 'user_id=eq.' . urlencode($userId);
            } else {
                $url .= 'email=eq.' . urlencode($email);
            }
            
            // Configurar cURL para una llamada directa
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $curlResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return ['error' => "Error en la petición directa: $curlError"];
            }
            
            if ($httpCode >= 400) {
                return [
                    'error' => "Error HTTP $httpCode en petición directa", 
                    'response' => $curlResponse
                ];
            }
            
            $profiles = json_decode($curlResponse, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => "Error al decodificar respuesta JSON: " . json_last_error_msg()];
            }
            
            return $profiles;
        }
        
        return $response;
        
    } catch (Exception $e) {
        return ['error' => "Excepción al intentar bypass: " . $e->getMessage()];
    }
}
?>
