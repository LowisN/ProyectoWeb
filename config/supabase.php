<?php
// Configuración de Supabase
define('SUPABASE_URL', 'https://wklyvlosbiylfvovakly.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndrbHl2bG9zYml5bGZ2b3Zha2x5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTA4MTI2ODIsImV4cCI6MjA2NjM4ODY4Mn0.fthVr-m8UkVX4tUW_zzdwhM6mgqohRbez-Oqek4efxo');

// Función para inicializar cliente Supabase usando cURL
function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_URL . $endpoint;
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_KEY,
        'apikey: ' . SUPABASE_KEY
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error];
    }
    
    return json_decode($response, true);
}

// Función para registrar un nuevo usuario
function supabaseSignUp($email, $password, $userData = []) {
    $data = [
        'email' => $email,
        'password' => $password,
        'data' => $userData
    ];
    
    $response = supabaseRequest('/auth/v1/signup', 'POST', $data);
    
    // Registrar la respuesta completa para debugging
    error_log("Respuesta de supabaseSignUp: " . json_encode($response));
    
    // Asegurar que el formato de la respuesta incluye el ID del usuario
    if (isset($response['user']) && isset($response['user']['id'])) {
        // Mantener la estructura como se espera
        return $response;
    } else {
        // Si hay un error, devolverlo como está
        if (isset($response['error']) || isset($response['code'])) {
            return $response;
        }
        
        // Intentar encontrar el ID del usuario en la respuesta
        $userId = null;
        
        if (isset($response['id'])) {
            $userId = $response['id'];
        } elseif (isset($response['data']) && isset($response['data']['user']) && isset($response['data']['user']['id'])) {
            $userId = $response['data']['user']['id'];
        } elseif (isset($response[0]) && isset($response[0]['id'])) {
            $userId = $response[0]['id'];
        }
        
        if ($userId) {
            // Reconstruir la respuesta en el formato esperado
            return [
                'user' => [
                    'id' => $userId,
                    'email' => $email,
                    'user_metadata' => $userData
                ]
            ];
        }
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
    
    return supabaseRequest($query);
}

// Función para insertar datos en una tabla
function supabaseInsert($table, $data) {
    // Lista de campos que deberían ser enteros (1/0) en lugar de booleanos
    $integerBooleanFields = ['destacada', 'obligatorio'];
    
    // Lista de campos que son IDs enteros (nunca deben convertirse a booleanos)
    $integerIdFields = ['id', 'empresa_id', 'reclutador_id', 'vacante_id', 'habilidad_id', 'candidato_id', 'perfil_id', 'user_id'];
    
    // Preprocesar los datos para corregir valores booleanos
    $processedData = array();
    // Lista de campos de fecha que deberían ser NULL si están vacíos
    $dateFields = ['fecha_expiracion', 'fecha_inicio', 'fecha_fin', 'fecha_nacimiento'];
    
    foreach ($data as $key => $value) {
        // Si es valor nulo, mantenerlo como null
        if ($value === null) {
            $processedData[$key] = null;
            continue;
        }
        
        // Si es un campo de fecha y está vacío, tratarlo como null
        if (in_array($key, $dateFields) && (trim($value) === '' || $value === '0000-00-00')) {
            $processedData[$key] = null;
            error_log("Campo de fecha '$key' vacío convertido a NULL");
            continue;
        }
        
        // Si el campo es un ID, asegurarse de que sea entero
        if (in_array($key, $integerIdFields)) {
            // Convertir a entero si no lo es ya y registrar para diagnóstico
            $processedData[$key] = is_numeric($value) ? (int)$value : $value;
            error_log("Campo ID '$key' procesado: " . gettype($value) . " → " . gettype($processedData[$key]) . 
                     " (valor: $value → " . $processedData[$key] . ")");
        }
        // Si el campo está en la lista de campos de tipo entero-booleano
        else if (in_array($key, $integerBooleanFields)) {
            // Convertir cualquier valor booleano a entero (1/0)
            if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
                $processedData[$key] = 1;
            } else {
                $processedData[$key] = 0;
            }
        }
        // Campos que deben ser numéricos (pero no están en la lista de IDs)
        else if (is_numeric($value) && (strpos($key, '_id') !== false || $key === 'anios_experiencia' || $key === 'salario')) {
            // Asegurarse que los campos numéricos se envíen como números
            $processedData[$key] = is_float($value + 0) ? (float)$value : (int)$value;
        }
        // Para campos booleanos regulares
        else if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
            $processedData[$key] = true;
        } 
        else if ($value === false || $value === "false" || $value === 0 || $value === "0" || $value === "") {
            $processedData[$key] = false;
        }
        // Mantener otros valores sin cambios
        else {
            $processedData[$key] = $value;
        }
    }
    
    // Registrar datos procesados para diagnóstico
    error_log("Datos a insertar en $table (después del procesamiento): " . json_encode($processedData));
    
    $response = supabaseRequest("/rest/v1/$table", 'POST', $processedData);
    
    // Registrar la respuesta para debugging
    error_log("Respuesta de supabaseInsert para tabla $table: " . json_encode($response));
    
    // Mejora del manejo de errores
    if (isset($response['code']) || isset($response['error'])) {
        $errorInfo = [
            'error' => true,
            'message' => isset($response['message']) ? $response['message'] : 'Error desconocido',
            'code' => isset($response['code']) ? $response['code'] : 'unknown',
            'details' => $response
        ];
        
        // Registrar el error para debugging
        error_log("Error en supabaseInsert para tabla $table: " . json_encode($errorInfo));
        
        return $errorInfo;
    }
    
    // Si la respuesta es null o vacía pero no hay error, considerarlo como éxito
    // Supabase a veces devuelve null en inserciones exitosas
    if ($response === null || (is_array($response) && empty($response))) {
        // Intentar obtener el registro recién insertado
        $fetchResponse = supabaseFetch($table, '*', $data);
        
        if (!isset($fetchResponse['error']) && !empty($fetchResponse)) {
            return $fetchResponse;
        }
        
        // Si no podemos obtener el registro, devolver un objeto genérico de éxito
        return ['success' => true, 'message' => 'Inserción exitosa'];
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

// Función para verificar si existe una tabla en Supabase
function supabaseCheckTableExists($table) {
    // En Supabase, podemos usar la API de PostgreSQL para obtener información del esquema
    $query = "/rest/v1/rpc/check_table_exists";
    $data = [
        'table_name' => $table
    ];
    
    return supabaseRequest($query, 'POST', $data);
}

// Función para inicializar las tablas necesarias en Supabase si no existen
function initializeSupabaseTables() {
    error_log("Verificando tablas en Supabase...");
    
    // Lista de las tablas principales a verificar
    $tables = ['perfiles', 'empresas', 'candidatos', 'reclutadores', 'habilidades'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $result = supabaseFetch($table, 'id', ['limit' => 1]);
        if (isset($result['error']) || isset($result['code'])) {
            $missingTables[] = $table;
            error_log("Parece que la tabla '$table' no existe o no es accesible: " . json_encode($result));
        } else {
            error_log("La tabla '$table' existe y es accesible");
        }
    }
    
    if (!empty($missingTables)) {
        error_log("ADVERTENCIA: Las siguientes tablas no existen o no son accesibles: " . implode(', ', $missingTables));
        return false;
    }
    
    return true;
}

// Función para verificar la estructura de una tabla en Supabase
function supabaseCheckTableStructure($table) {
    // Obtener una fila para ver su estructura (o vacía si no hay registros)
    $result = supabaseRequest("/rest/v1/$table?limit=1");
    
    // Si hay un error, probablemente la tabla no exista o no es accesible
    if (isset($result['error']) || isset($result['code'])) {
        return [
            'exists' => false,
            'error' => $result,
            'message' => "La tabla $table no existe o no es accesible"
        ];
    }
    
    // Obtener descripción de la tabla usando una función personalizada en Postgres (si está disponible)
    $describeResult = supabaseRequest("/rest/v1/rpc/describe_table", "POST", ["table_name" => $table]);
    
    return [
        'exists' => true,
        'sample' => $result,
        'description' => isset($describeResult['error']) ? null : $describeResult
    ];
}

// Función para verificar la estructura de una tabla
function supabaseDescribeTable($table) {
    // Verificar que la tabla existe
    $result = supabaseFetch($table, '*', [], 1);
    
    if (isset($result['error']) || (is_array($result) && isset($result['code']))) {
        return [
            'exists' => false,
            'error' => $result,
            'message' => "La tabla $table no existe o no es accesible"
        ];
    }
    
    // Intentar obtener descripción de la tabla directamente (aunque esto puede no estar disponible)
    // Este es un enfoque genérico. La función real podría ser diferente según cómo esté configurada la base de datos
    $describeQuery = "SELECT column_name, data_type, is_nullable 
                     FROM information_schema.columns 
                     WHERE table_name = '$table'";
                     
    $describeResult = supabaseRequest("/rest/v1/rpc/execute_sql", "POST", ["query" => $describeQuery]);
    
    // Si no funciona el enfoque directo, al menos devolvemos una muestra de datos
    return [
        'exists' => true,
        'sample' => $result,
        'description' => isset($describeResult['error']) ? null : $describeResult
    ];
}

// Función para depurar el proceso de registro (para desarrollo)
function debugRegistrationProcess($email, $password, $userData, $tipo_usuario = 'candidato') {
    $steps = [];
    
    // Paso 1: Registrar usuario
    $authResponse = supabaseSignUp($email, $password, $userData);
    $steps['auth'] = $authResponse;
    
    // Si hay error, terminar
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        return [
            'success' => false,
            'step' => 'auth',
            'steps' => $steps
        ];
    }
    
    // Paso 2: Extraer ID de usuario
    $userId = null;
    if (isset($authResponse['user']) && isset($authResponse['user']['id'])) {
        $userId = $authResponse['user']['id'];
    } elseif (isset($authResponse['id'])) {
        $userId = $authResponse['id'];
    } elseif (isset($authResponse['data']) && isset($authResponse['data']['user']) && isset($authResponse['data']['user']['id'])) {
        $userId = $authResponse['data']['user']['id'];
    }
    
    if (!$userId) {
        return [
            'success' => false,
            'step' => 'extract_id',
            'steps' => $steps
        ];
    }
    
    // Paso 3: Crear perfil
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => $tipo_usuario
    ];
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    $steps['perfil'] = $perfilResponse;
    
    // Si hay error, terminar
    if (isset($perfilResponse['error'])) {
        return [
            'success' => false,
            'step' => 'perfil',
            'steps' => $steps
        ];
    }
    
    return [
        'success' => true,
        'steps' => $steps
    ];
}

// Verificar tablas al cargar el archivo (opcional - puedes comentar esta línea si prefieres)
// initializeSupabaseTables();
?>
