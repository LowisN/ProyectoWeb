<?php
// Script para monitorear las llamadas a Supabase
require_once 'config/supabase.php';

// Sobrescribir la función supabaseRequest para registrar todas las llamadas
function supabaseRequestLogged($endpoint, $method = 'GET', $data = null) {
    // Guardar los datos de la solicitud
    $request = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'method' => $method,
        'data' => $data
    ];
    
    // Ejecutar la solicitud original
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Guardar la respuesta
    $responseData = [
        'http_code' => $httpCode,
        'error' => $error,
        'response' => $response ? json_decode($response, true) : null,
        'raw_response' => $response
    ];
    
    // Registrar la solicitud y la respuesta
    logApiCall($request, $responseData);
    
    if ($error) {
        return ['error' => $error];
    }
    
    return json_decode($response, true);
}

// Función para registrar las llamadas a la API
function logApiCall($request, $response) {
    // Crear un ID único para esta llamada
    $callId = uniqid();
    
    // Formatear los datos para registrar
    $logData = [
        'id' => $callId,
        'timestamp' => $request['timestamp'],
        'endpoint' => $request['endpoint'],
        'method' => $request['method'],
        'request_data' => $request['data'],
        'http_code' => $response['http_code'],
        'response_data' => $response['response'],
        'error' => $response['error']
    ];
    
    // Guardar en un archivo de registro
    $logFile = __DIR__ . '/logs/api_calls.log';
    
    // Asegurarse de que el directorio de logs existe
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0777, true);
    }
    
    // Añadir al archivo de registro
    file_put_contents(
        $logFile, 
        json_encode($logData, JSON_PRETTY_PRINT) . "\n---\n", 
        FILE_APPEND
    );
    
    return $callId;
}

// Activar o desactivar el registro
if (isset($_GET['enable'])) {
    // Habilitar el registro
    if (!function_exists('supabaseRequestOriginal')) {
        // Guardar la función original solo si no se ha guardado ya
        function supabaseRequestOriginal($endpoint, $method = 'GET', $data = null) {
            global $supabaseRequestOriginalFunc;
            return $supabaseRequestOriginalFunc($endpoint, $method, $data);
        }
        
        // Guardar la referencia original
        $supabaseRequestOriginalFunc = 'supabaseRequest';
        
        // Reemplazar con la función de registro
        function supabaseRequest($endpoint, $method = 'GET', $data = null) {
            return supabaseRequestLogged($endpoint, $method, $data);
        }
    }
    
    echo "<p style='color:green;'>Monitoreo de API activado. Todas las llamadas a Supabase serán registradas.</p>";
    echo "<p>Regresa a la <a href='index.php'>página de inicio de sesión</a> e intenta iniciar sesión para registrar las llamadas.</p>";
    echo "<p>Luego vuelve a <a href='monitor_api.php?view=1'>ver los registros</a>.</p>";
    
} elseif (isset($_GET['disable'])) {
    // Deshabilitar el registro (restaurar la función original)
    if (function_exists('supabaseRequestOriginal')) {
        function supabaseRequest($endpoint, $method = 'GET', $data = null) {
            return supabaseRequestOriginal($endpoint, $method, $data);
        }
    }
    
    echo "<p style='color:orange;'>Monitoreo de API desactivado.</p>";
    echo "<p>Volver a la <a href='index.php'>página de inicio de sesión</a>.</p>";
    
} elseif (isset($_GET['view'])) {
    // Ver los registros
    echo "<h1>Registros de llamadas a la API de Supabase</h1>";
    
    $logFile = __DIR__ . '/logs/api_calls.log';
    
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $logEntries = explode("\n---\n", $logContent);
        
        // Mostrar los registros en orden cronológico inverso (más recientes primero)
        $logEntries = array_reverse($logEntries);
        
        echo "<p>Se encontraron " . (count($logEntries) - 1) . " registros.</p>";
        
        echo "<div style='margin-top: 20px;'>";
        foreach ($logEntries as $entry) {
            if (empty(trim($entry))) continue;
            
            $data = json_decode($entry, true);
            
            if ($data) {
                echo "<div style='border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
                echo "<h3>" . htmlspecialchars($data['method']) . " " . htmlspecialchars($data['endpoint']) . " <span style='color: " . ($data['http_code'] >= 200 && $data['http_code'] < 300 ? 'green' : 'red') . ";'>[" . htmlspecialchars($data['http_code']) . "]</span></h3>";
                echo "<p><strong>Timestamp:</strong> " . htmlspecialchars($data['timestamp']) . "</p>";
                
                if ($data['request_data']) {
                    echo "<h4>Request Data:</h4>";
                    echo "<pre>" . htmlspecialchars(json_encode($data['request_data'], JSON_PRETTY_PRINT)) . "</pre>";
                }
                
                echo "<h4>Response:</h4>";
                
                if ($data['error']) {
                    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($data['error']) . "</p>";
                }
                
                if ($data['response_data']) {
                    echo "<pre>" . htmlspecialchars(json_encode($data['response_data'], JSON_PRETTY_PRINT)) . "</pre>";
                } else {
                    echo "<p>Sin datos de respuesta</p>";
                }
                
                echo "</div>";
            }
        }
        echo "</div>";
        
        echo "<p><a href='monitor_api.php?clear=1' onclick='return confirm(\"¿Estás seguro de que quieres borrar todos los registros?\");'>Borrar registros</a></p>";
    } else {
        echo "<p>No hay registros disponibles. <a href='monitor_api.php?enable=1'>Activa el monitoreo</a> primero y luego intenta iniciar sesión.</p>";
    }
    
} elseif (isset($_GET['clear'])) {
    // Borrar los registros
    $logFile = __DIR__ . '/logs/api_calls.log';
    
    if (file_exists($logFile)) {
        unlink($logFile);
        echo "<p>Registros borrados.</p>";
    } else {
        echo "<p>No hay registros para borrar.</p>";
    }
    
    echo "<p><a href='monitor_api.php'>Volver</a></p>";
    
} else {
    // Página principal
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Monitor de API Supabase</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .button { display: inline-block; padding: 10px 15px; background-color: #4CAF50; 
                      color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
            .button.warning { background-color: #f44336; }
        </style>
    </head>
    <body>
        <h1>Monitor de API Supabase</h1>
        
        <p>Esta herramienta te permite monitorear las llamadas a la API de Supabase para ayudar a diagnosticar problemas.</p>
        
        <div style="margin-top: 30px;">
            <a href="monitor_api.php?enable=1" class="button">Activar Monitoreo</a>
            <a href="monitor_api.php?view=1" class="button">Ver Registros</a>
            <a href="monitor_api.php?disable=1" class="button warning">Desactivar Monitoreo</a>
        </div>
        
        <h2>Instrucciones de uso</h2>
        <ol>
            <li>Haz clic en "Activar Monitoreo" para empezar a registrar todas las llamadas a la API de Supabase.</li>
            <li>Ve a la página de inicio de sesión e intenta iniciar sesión.</li>
            <li>Vuelve aquí y haz clic en "Ver Registros" para analizar las llamadas realizadas.</li>
            <li>Cuando hayas terminado, haz clic en "Desactivar Monitoreo" para detener el registro.</li>
        </ol>
        
        <p><a href="index.php">Volver a la página de inicio de sesión</a></p>
    </body>
    </html>
    <?php
}
?>
