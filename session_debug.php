<?php
session_start();
echo "<h1>Depuración de la sesión y autenticación</h1>";

// Verificar si hay una sesión activa
echo "<h2>Estado de la sesión</h2>";

if (empty($_SESSION)) {
    echo "<p style='color: orange;'>No hay una sesión activa.</p>";
} else {
    echo "<p style='color: green;'>Sesión activa encontrada.</p>";
    
    // Mostrar datos de sesión
    echo "<h3>Datos de sesión:</h3>";
    echo "<pre>";
    
    // Filtrar datos sensibles
    $sessionData = $_SESSION;
    if (isset($sessionData['access_token'])) {
        $sessionData['access_token'] = substr($sessionData['access_token'], 0, 10) . '...';
    }
    if (isset($sessionData['refresh_token'])) {
        $sessionData['refresh_token'] = substr($sessionData['refresh_token'], 0, 10) . '...';
    }
    
    print_r($sessionData);
    echo "</pre>";
    
    // Verificar token de acceso
    if (isset($_SESSION['access_token'])) {
        echo "<p style='color: green;'>✓ Token de acceso presente</p>";
    } else {
        echo "<p style='color: red;'>✗ No hay token de acceso</p>";
    }
    
    // Verificar datos del usuario
    if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
        echo "<p style='color: green;'>✓ Datos del usuario presentes</p>";
    } else {
        echo "<p style='color: red;'>✗ No hay datos del usuario</p>";
    }
    
    // Verificar tipo de usuario
    if (isset($_SESSION['tipo_usuario'])) {
        echo "<p style='color: green;'>✓ Tipo de usuario definido: " . htmlspecialchars($_SESSION['tipo_usuario']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ No hay tipo de usuario definido</p>";
    }
}

// Verificar redirección según tipo de usuario
echo "<h2>Verificación de redirección</h2>";

echo "<p>Si el usuario inicia sesión, será redirigido a:</p>";
echo "<ul>";
if (isset($_SESSION['tipo_usuario'])) {
    switch ($_SESSION['tipo_usuario']) {
        case 'administrador':
            echo "<li>Administrador: <code>paginas/admin/dashboard.php</code></li>";
            break;
        case 'candidato':
            echo "<li>Candidato: <code>paginas/candidato/home_candidato.php</code></li>";
            break;
        case 'reclutador':
            echo "<li>Reclutador: <code>paginas/empresa/home_empresa.php</code></li>";
            break;
        default:
            echo "<li>Tipo de usuario no reconocido: " . htmlspecialchars($_SESSION['tipo_usuario']) . "</li>";
    }
} else {
    echo "<li>Sin tipo de usuario definido, no se puede determinar la redirección</li>";
}
echo "</ul>";

// Verificar existencia de páginas de destino
echo "<h2>Verificación de páginas de destino</h2>";
echo "<ul>";
$pagesMap = [
    'administrador' => 'paginas/admin/dashboard.php',
    'candidato' => 'paginas/candidato/home_candidato.php',
    'reclutador' => 'paginas/empresa/home_empresa.php'
];

foreach ($pagesMap as $type => $page) {
    $fullPath = __DIR__ . '/' . $page;
    if (file_exists($fullPath)) {
        echo "<li>" . htmlspecialchars($type) . ": <code>" . htmlspecialchars($page) . "</code> <span style='color: green;'>✓ Existe</span></li>";
    } else {
        echo "<li>" . htmlspecialchars($type) . ": <code>" . htmlspecialchars($page) . "</code> <span style='color: red;'>✗ No existe</span></li>";
    }
}
echo "</ul>";

// Opciones de acción
echo "<h2>Acciones</h2>";

// Opción para destruir la sesión
echo "<form method='post' action=''>";
echo "<input type='hidden' name='action' value='clear_session'>";
echo "<button type='submit' style='background-color: #f44336; color: white; padding: 10px; border: none; cursor: pointer;'>Cerrar sesión (destruir sesión)</button>";
echo "</form>";

// Opción para verificar la validez del token
if (isset($_SESSION['access_token'])) {
    echo "<form method='post' action=''>";
    echo "<input type='hidden' name='action' value='check_token'>";
    echo "<button type='submit' style='background-color: #2196F3; color: white; padding: 10px; border: none; cursor: pointer; margin-top: 10px;'>Verificar validez del token</button>";
    echo "</form>";
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'clear_session') {
            // Destruir la sesión
            session_unset();
            session_destroy();
            echo "<script>window.location.reload();</script>";
        } elseif ($_POST['action'] === 'check_token') {
            // Verificar la validez del token
            if (isset($_SESSION['access_token'])) {
                require_once 'config/supabase.php';
                
                echo "<h3>Verificando validez del token...</h3>";
                
                // Intentar hacer una solicitud autenticada
                $ch = curl_init(SUPABASE_URL . '/auth/v1/user');
                
                $headers = [
                    'Authorization: Bearer ' . $_SESSION['access_token'],
                    'apikey: ' . SUPABASE_KEY
                ];
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                echo "<p>Código HTTP: " . $httpCode . "</p>";
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    echo "<p style='color: green;'>El token es válido.</p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                } else {
                    echo "<p style='color: red;'>El token no es válido o ha expirado.</p>";
                    if ($error) {
                        echo "<p>Error: " . htmlspecialchars($error) . "</p>";
                    }
                    if ($response) {
                        echo "<pre>" . htmlspecialchars($response) . "</pre>";
                    }
                }
            }
        }
    }
}

// Enlaces útiles
echo "<h2>Enlaces útiles</h2>";
echo "<ul>";
echo "<li><a href='index.php'>Página de inicio de sesión</a></li>";
echo "<li><a href='test_login.php'>Diagnóstico de inicio de sesión</a></li>";
echo "<li><a href='monitor_api.php'>Monitor de API Supabase</a></li>";
echo "<li><a href='verificar_tablas.php'>Verificar tablas de Supabase</a></li>";
echo "</ul>";
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2, h3 { color: #333; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    code { background-color: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    a { color: #2196F3; text-decoration: none; }
    a:hover { text-decoration: underline; }
    ul { line-height: 1.6; }
</style>
