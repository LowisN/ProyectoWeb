<?php
// Configuración de Supabase
define('SUPABASE_URL', 'TU_URL_DE_SUPABASE');
define('SUPABASE_KEY', 'TU_API_KEY_DE_SUPABASE');

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

// Función para cerrar sesión
function supabaseSignOut($accessToken) {
    $headers = [
        'Authorization: Bearer ' . $accessToken
    ];
    
    // Aquí habría que modificar supabaseRequest para aceptar headers personalizados
    return supabaseRequest('/auth/v1/logout', 'POST');
}
?>
