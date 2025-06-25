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
 * 5. Copia y pega el contenido de tus archivos SQL actualizados:
 *    - database_updated.sql: Crea la nueva estructura de tablas
 *    - functions_updated.sql: Funciones actualizadas para la nueva estructura
 *    - policies_updated.sql: Políticas de seguridad para la nueva estructura
 *    - migration.sql: Migra datos si es necesario de la estructura anterior
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
    
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'httpCode' => $httpCode
        ];
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'data' => json_decode($response, true),
        'httpCode' => $httpCode
    ];
}

function checkDatabase() {
    $tables = ['usuario', 'perfiles', 'candidatos', 'empresas', 'reclutadores', 'tecnologias', 
                'conocimientos_candidato', 'vacantes', 'requisitos_vacante', 'postulaciones'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $response = supabaseRestRequest($table . '?limit=1');
        
        if ($response['httpCode'] == 404) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "<div style='color: green;'>✓ Todas las tablas están configuradas correctamente.</div>";
    } else {
        echo "<div style='color: red;'>✗ Faltan las siguientes tablas: " . implode(', ', $missingTables) . "</div>";
        echo "<p>Debes ejecutar los scripts SQL (database_updated.sql, functions_updated.sql, policies_updated.sql) 
             en el Editor SQL de Supabase Studio.</p>";
    }
}

// Detectar si estamos en el entorno de producción o desarrollo
function getEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'development';
    }
    return 'production';
}

// Mostrar interfaz si se accede directamente
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    $env = getEnvironment();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>ChambaNet - Configuración de Base de Datos</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
            h1, h2 { color: #333; }
            pre { background: #f4f4f4; padding: 15px; border-left: 5px solid #ddd; overflow: auto; }
            .info { background: #e7f3fe; border-left: 6px solid #2196F3; padding: 10px; margin: 10px 0; }
            .warning { background: #ffffcc; border-left: 6px solid #ffeb3b; padding: 10px; margin: 10px 0; }
            .error { background: #ffdddd; border-left: 6px solid #f44336; padding: 10px; margin: 10px 0; }
            .success { background: #ddffdd; border-left: 6px solid #4CAF50; padding: 10px; margin: 10px 0; }
            button { padding: 10px; background: #4CAF50; color: white; border: none; cursor: pointer; margin-top: 10px; }
            button:hover { background: #45a049; }
        </style>
    </head>
    <body>
        <h1>ChambaNet - Configuración de Base de Datos</h1>
        <div class='info'>
            <strong>Entorno:</strong> " . ucfirst($env) . "
        </div>
        
        <div class='warning'>
            <p><strong>IMPORTANTE:</strong> Este script no puede ejecutar sentencias DDL (CREATE TABLE, etc.) a través de la API REST de Supabase.</p>
            <p>Debes ejecutar manualmente los scripts SQL en el Editor SQL de Supabase Studio:</p>
            <ol>
                <li>Ve a <a href='https://app.supabase.com' target='_blank'>https://app.supabase.com</a></li>
                <li>Selecciona tu proyecto</li>
                <li>Ve a la sección 'SQL Editor'</li>
                <li>Copia y pega el contenido de los archivos database_updated.sql, functions_updated.sql y policies_updated.sql</li>
                <li>Si estás migrando desde una versión anterior, también ejecuta migration.sql</li>
                <li>Ejecuta los scripts</li>
            </ol>
        </div>
        
        <h2>Estado de la base de datos</h2>
        <div>";
        
    checkDatabase();
    
    echo "</div>
    </body>
    </html>";
}
?>
