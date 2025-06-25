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
    
    return supabaseRequest('/auth/v1/signup', 'POST', $data);
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
    return supabaseRequest("/rest/v1/$table", 'POST', $data);
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

// Función para crear un usuario administrador
function createAdminUser($email, $password, $userData) {
    // 1. Registrar el usuario usando la API de autenticación
    $signUpResponse = supabaseSignUp($email, $password, $userData);
    
    // Verificar si hay error
    if (isset($signUpResponse['error'])) {
        return [
            'success' => false,
            'error' => $signUpResponse['error'],
            'message' => 'Error al crear el usuario en Auth',
            'step' => 'signup'
        ];
    }
    
    // 2. Obtener el user_id del usuario recién creado
    if (!isset($signUpResponse['id']) && !isset($signUpResponse['user']['id'])) {
        return [
            'success' => false,
            'error' => 'No se pudo obtener el ID del usuario',
            'response' => $signUpResponse,
            'step' => 'get_id'
        ];
    }
    
    $userId = isset($signUpResponse['id']) ? $signUpResponse['id'] : $signUpResponse['user']['id'];
    
    // 3. Insertar en la tabla usuario
    $insertUserData = [
        'user_id' => $userId,
        'nombre' => $userData['nombre'] ?? '',
        'apellido_paterno' => $userData['apellido_paterno'] ?? '',
        'apellido_materno' => $userData['apellido_materno'] ?? '',
        'correo' => $email,
        'telefono' => $userData['telefono'] ?? '',
        'fecha_nacimiento' => $userData['fecha_nacimiento'] ?? date('Y-m-d')
    ];
    
    $userInsertResponse = supabaseInsert('usuario', $insertUserData);
    
    if (isset($userInsertResponse['error'])) {
        return [
            'success' => false,
            'error' => $userInsertResponse['error'],
            'message' => 'Error al insertar en tabla usuario',
            'step' => 'insert_usuario'
        ];
    }
    
    // 4. Insertar en la tabla perfiles como administrador
    $insertProfileData = [
        'user_id' => $userId,
        'tipo_perfil' => 'administrador'
    ];
    
    $profileInsertResponse = supabaseInsert('perfiles', $insertProfileData);
    
    if (isset($profileInsertResponse['error'])) {
        return [
            'success' => false,
            'error' => $profileInsertResponse['error'],
            'message' => 'Error al insertar en tabla perfiles',
            'step' => 'insert_perfiles'
        ];
    }
    
    // Todo salió bien
    return [
        'success' => true,
        'user_id' => $userId,
        'message' => 'Usuario administrador creado exitosamente'
    ];
}

// Función para cerrar sesión
function supabaseSignOut($accessToken) {
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];
    
    // Aquí habría que modificar supabaseRequest para aceptar headers personalizados
    return supabaseRequest('/auth/v1/logout', 'POST');
}
?>
