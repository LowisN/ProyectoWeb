<?php
/**
 * Script para configuración inicial de la base de datos en Supabase
 *
 * IMPORTANTE: Este script NO PUEDE ejecutar sentencias DDL (CREATE TABLE, CREATE FUNCTION, etc.)
 * a través de la API REST de Supabase.
 *
 * Las sentencias DDL DEBEN ser ejecutadas directamente en el Editor SQL de Supabase Studio:
 * 1. Ve a https://app.supabase.com
 * 2. Selecciona tu proyecto
 * 3. Ve a la sección "SQL Editor"
 * 4. Crea un nuevo script SQL
 * 5. Copia y pega el contenido de tus archivos SQL (database.sql, functions.sql, policies.sql)
 * 6. Ejecuta el script
 *
 * Este script solo puede usarse para operaciones CRUD (crear, leer, actualizar, eliminar datos)
 * en tablas que ya existen.
 */
require_once 'supabase.php';

// URL de la API de Supabase
$restUrl = SUPABASE_URL . '/rest/v1/';

// Función para realizar operaciones en la API REST de Supabase
function supabaseRestRequest($endpoint, $method = 'GET', $data = null) {
    global $restUrl;
    $url = $restUrl . $endpoint;
    
    $ch = curl_init($url);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_KEY,
        'apikey: ' . SUPABASE_KEY,
        'Prefer: return=representation'
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        return ['error' => $errorMessage, 'status' => $httpCode];
    }
    
    curl_close($ch);
    
    if ($httpCode >= 400) {
        return ['error' => 'Error HTTP: ' . $httpCode, 'response' => $response];
    }
    
    return ['success' => true, 'response' => json_decode($response, true)];
}

// Leer los archivos SQL
$databaseSql = file_get_contents(__DIR__ . '/database.sql');
$functionsSql = file_get_contents(__DIR__ . '/functions.sql');
$policiesSql = file_get_contents(__DIR__ . '/policies.sql');

// Combinar los contenidos
$sqlFile = $databaseSql . "\n" . $functionsSql . "\n" . $policiesSql;
$sqlStatements = explode(';', $sqlFile);

$results = ['success' => [], 'errors' => []];

// Ejecutar cada sentencia SQL
foreach ($sqlStatements as $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    echo "Ejecutando: " . substr($statement, 0, 50) . "...\n";
    $result = executeSql($statement);
    
    if (isset($result['error'])) {
        $results['errors'][] = [
            'statement' => $statement,
            'error' => $result['error'],
            'response' => $result['response'] ?? ''
        ];
        echo "Error: " . $result['error'] . "\n";
    } else {
        $results['success'][] = $statement;
        echo "OK\n";
    }
}

// Mostrar resumen
echo "\n----- RESUMEN -----\n";
echo "Total de sentencias ejecutadas con éxito: " . count($results['success']) . "\n";
echo "Total de errores: " . count($results['errors']) . "\n";

if (!empty($results['errors'])) {
    echo "\n----- DETALLES DE ERRORES -----\n";
    foreach ($results['errors'] as $index => $error) {
        echo "Error #" . ($index + 1) . ":\n";
        echo "Sentencia: " . $error['statement'] . "\n";
        echo "Error: " . $error['error'] . "\n";
        echo "Respuesta: " . ($error['response'] ?? 'N/A') . "\n\n";
    }
}
