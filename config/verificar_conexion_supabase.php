<?php
/**
 * Herramienta para probar la conexión y funcionalidad de Supabase
 * 
 * Esta utilidad permite:
 * 1. Verificar la conexión con Supabase
 * 2. Probar las credenciales API
 * 3. Verificar permisos en tablas críticas
 * 4. Diagnosticar problemas comunes de registro
 */

session_start();
require_once '../config/supabase.php';

// Variable para almacenar resultados
$results = [];
$allPassed = true;
$criticalError = false;

// Función para mostrar resultados de pruebas
function showTestResult($test, $passed, $message, $details = '') {
    global $allPassed;
    if (!$passed) {
        $allPassed = false;
    }
    $status = $passed ? 'success' : 'error';
    $icon = $passed ? '✅' : '❌';
    
    return [
        'test' => $test,
        'status' => $status,
        'icon' => $icon,
        'message' => $message,
        'details' => $details
    ];
}

// 1. Prueba de conectividad básica
$testUrl = SUPABASE_URL . '/rest/v1/';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$connectionSuccess = $httpCode > 0 && $httpCode < 500;
$results[] = showTestResult(
    'Conexión con Supabase', 
    $connectionSuccess, 
    $connectionSuccess ? 'La conexión con Supabase está activa' : 'No se pudo conectar con Supabase',
    $connectionSuccess ? "Código HTTP: $httpCode" : "Error: $error"
);

// Si hay error crítico de conexión, no continuar con más pruebas
if (!$connectionSuccess) {
    $criticalError = true;
} else {
    // 2. Prueba de autenticación de API
    $response = supabaseRequest('/rest/v1/perfiles?limit=1', 'GET');
    $authSuccess = !isset($response['error']) || (isset($response['statusCode']) && $response['statusCode'] != 401);
    
    $results[] = showTestResult(
        'Autenticación API', 
        $authSuccess, 
        $authSuccess ? 'Las credenciales API son válidas' : 'Problema con las credenciales API',
        $authSuccess ? "Respuesta correcta" : "Error: " . json_encode($response['error'] ?? 'Desconocido')
    );
    
    if (!$authSuccess) {
        $criticalError = true;
    } else {
        // 3. Verificar tablas necesarias
        $tables = ['perfiles', 'candidatos', 'reclutadores', 'empresas'];
        $tablesStatus = [];
        
        foreach ($tables as $table) {
            $tableCheck = supabaseRequest("/rest/v1/$table?limit=1", 'GET');
            $tableExists = !isset($tableCheck['error']) || (isset($tableCheck['statusCode']) && $tableCheck['statusCode'] != 404);
            
            $tablesStatus[$table] = $tableExists;
            $results[] = showTestResult(
                "Tabla '$table'", 
                $tableExists, 
                $tableExists ? "La tabla '$table' existe y es accesible" : "La tabla '$table' no existe o no es accesible",
                $tableExists ? "" : "Esto puede causar errores en el registro y funcionamiento de la aplicación"
            );
        }
        
        // 4. Probar inserción en tablas críticas (solo simulación)
        if ($tablesStatus['perfiles']) {
            $testInsert = [
                'user_id' => '00000000-0000-0000-0000-000000000000',
                'email' => 'test@example.com',
                'tipo_usuario' => 'test',
                'nombre' => 'Test',
                'apellidos' => 'Usuario',
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];
            
            // Usamos isDryRun = true para no insertar realmente
            $insertResult = supabaseRequest("/rest/v1/perfiles", 'POST', $testInsert, true);
            $insertPermission = !isset($insertResult['error']) || (isset($insertResult['statusCode']) && $insertResult['statusCode'] != 403);
            
            $results[] = showTestResult(
                "Permisos de inserción", 
                $insertPermission, 
                $insertPermission ? "Los permisos de inserción parecen correctos" : "Posible problema de permisos para insertar",
                $insertPermission ? "" : "Las políticas RLS podrían estar bloqueando la inserción"
            );
        }
        
        // 5. Probar el servicio de autenticación
        try {
            // Intento de llamada a endpoint de auth (solo para verificar disponibilidad)
            $authEndpoint = SUPABASE_URL . '/auth/v1/user';
            $ch = curl_init($authEndpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY
            ]);
            curl_exec($ch);
            $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $authServiceUp = $authHttpCode != 0 && $authHttpCode != 500 && $authHttpCode != 503;
            
            $results[] = showTestResult(
                "Servicio de autenticación", 
                $authServiceUp, 
                $authServiceUp ? "El servicio de autenticación está disponible" : "Problema con el servicio de autenticación",
                "Código HTTP: $authHttpCode"
            );
        } catch (Exception $e) {
            $results[] = showTestResult(
                "Servicio de autenticación", 
                false, 
                "Error al comprobar el servicio de autenticación",
                $e->getMessage()
            );
        }
    }
}

// Determinar el estado general
$overallStatus = $criticalError ? 'error' : ($allPassed ? 'success' : 'warning');
$overallMessage = $criticalError 
    ? 'Se encontraron errores críticos que impiden el funcionamiento de la aplicación' 
    : ($allPassed 
        ? 'Todos los sistemas funcionan correctamente' 
        : 'La aplicación puede funcionar pero se detectaron algunas advertencias');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Supabase - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            margin-top: 0;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .summary {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .test-result {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .test-result .details {
            margin-top: 5px;
            font-size: 0.9em;
            color: #555;
        }
        .actions {
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Diagnóstico de Conexión Supabase</h1>
            <p>Esta herramienta verifica la conectividad y funcionalidad de Supabase para ChambaNet</p>
        </div>
        
        <div class="summary <?php echo $overallStatus; ?>">
            <h2>Resultado General</h2>
            <p><?php echo $overallMessage; ?></p>
        </div>
        
        <h2>Resultados Detallados</h2>
        <?php foreach ($results as $result): ?>
            <div class="test-result <?php echo $result['status']; ?>">
                <strong><?php echo $result['icon']; ?> <?php echo $result['test']; ?>:</strong> <?php echo $result['message']; ?>
                <?php if (!empty($result['details'])): ?>
                    <div class="details"><?php echo $result['details']; ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="actions">
            <h2>Acciones Recomendadas</h2>
            <?php if ($criticalError): ?>
                <p>Se detectaron problemas críticos que deben resolverse antes de usar la aplicación:</p>
                <ul>
                    <?php if (!$connectionSuccess): ?>
                        <li>Verificar la conexión a Internet y que la URL de Supabase sea correcta</li>
                    <?php endif; ?>
                    <?php if (isset($authSuccess) && !$authSuccess): ?>
                        <li>Verificar que las credenciales API en config/supabase.php sean correctas</li>
                    <?php endif; ?>
                </ul>
                <a href="verificar_conexion_supabase.php" class="btn">Volver a Probar</a>
            <?php elseif (!$allPassed): ?>
                <p>La aplicación puede funcionar pero se recomienda revisar las advertencias:</p>
                <a href="../config/verificar_tablas.php" class="btn">Verificar Estructura de Tablas</a>
                <a href="../config/diagnostico_perfiles.php" class="btn">Diagnóstico de Perfiles</a>
                <a href="verificar_conexion_supabase.php" class="btn">Volver a Probar</a>
            <?php else: ?>
                <p>Todos los sistemas están funcionando correctamente. No se requieren acciones.</p>
                <a href="../paginas/interfaz_iniciar_sesion.php" class="btn">Ir a Inicio de Sesión</a>
            <?php endif; ?>
            <a href="../index.php" class="btn btn-secondary">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
