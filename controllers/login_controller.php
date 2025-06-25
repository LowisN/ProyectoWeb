<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['contrasena']; // No filtrar para evitar problemas con caracteres especiales
    
    // Validar que se han proporcionado los datos necesarios
    if (empty($email) || empty($password)) {
        header('Location: ../index.php?error=Todos los campos son obligatorios');
        exit;
    }
    
    // Intentar iniciar sesión con Supabase
    $response = supabaseSignIn($email, $password);
    
    // Comprobar si hay errores
    if (isset($response['error']) || isset($response['code'])) {
        // Mejorar el manejo de errores para mostrar mensajes más descriptivos
        $errorMessage = 'Error al iniciar sesión. Inténtalo de nuevo.';
        
        if (isset($response['error_description'])) {
            $errorMessage = $response['error_description'];
        } elseif (isset($response['msg'])) {
            $errorMessage = $response['msg'];
        } elseif (isset($response['message'])) {
            $errorMessage = $response['message'];
        } elseif (isset($response['error'])) {
            // Registrar el error completo para diagnóstico
            error_log("Error detallado de login: " . json_encode($response));
            
            // Personalizar mensajes según el tipo de error
            if ($response['error'] === 'invalid_grant') {
                $errorMessage = 'Email o contraseña incorrectos.';
            } elseif ($response['error'] === 'user_not_found') {
                $errorMessage = 'El usuario no existe.';
            } else {
                $errorMessage = 'Error: ' . $response['error'];
            }
        }
        
        // Registrar el error para depuración
        error_log("Error de inicio de sesión: " . $errorMessage . " - Datos: " . json_encode($response));
        
        header('Location: ../index.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Almacenar el token de acceso en la sesión
    $_SESSION['access_token'] = $response['access_token'];
    $_SESSION['refresh_token'] = $response['refresh_token'];
    $_SESSION['user'] = $response['user'];
    
    // Obtener el perfil del usuario para determinar el tipo
    $userId = $response['user']['id'];
    
    // Registrar información de usuario para depuración
    error_log("Usuario autenticado con ID: " . $userId);
    
    // Obtener el perfil del usuario
    $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
    
    // Registrar la respuesta de la consulta del perfil para depuración
    error_log("Respuesta de consulta de perfil: " . json_encode($userProfile));
    
    if (empty($userProfile)) {
        // No se encontró el perfil del usuario
        error_log("ERROR: No se encontró el perfil para el usuario con ID: " . $userId);
        header('Location: ../index.php?error=No se encontró el perfil de usuario. Por favor contacte al administrador.');
        exit;
    } elseif (isset($userProfile['error'])) {
        // Error al obtener el perfil
        error_log("ERROR al obtener el perfil: " . json_encode($userProfile));
        header('Location: ../index.php?error=Error al obtener el perfil de usuario: ' . urlencode($userProfile['error']));
        exit;
    } elseif (!isset($userProfile[0]) || !isset($userProfile[0]['tipo_usuario'])) {
        // El perfil existe pero no tiene el formato esperado
        error_log("ERROR: Formato de perfil incorrecto: " . json_encode($userProfile));
        header('Location: ../index.php?error=Formato de perfil incorrecto. Por favor contacte al administrador.');
        exit;
    }
    
    // Guardar el tipo de usuario en la sesión
    $_SESSION['tipo_usuario'] = $userProfile[0]['tipo_usuario'];
    $_SESSION['perfil_id'] = $userProfile[0]['id'];
    
    // Registrar el tipo de usuario para depuración
    error_log("Usuario de tipo: " . $userProfile[0]['tipo_usuario']);
    
    // Redirigir según el tipo de usuario
    switch ($userProfile[0]['tipo_usuario']) {
        case 'administrador':
            header('Location: ../paginas/admin/dashboard.php');
            break;
        case 'candidato':
            header('Location: ../paginas/candidato/home_candidato.php');
            break;
        case 'reclutador':
            header('Location: ../paginas/empresa/home_empresa.php');
            break;
        default:
            // Corregir la redirección por defecto para usar index.php en lugar de interfaz_iniciar_sesion.php
            header('Location: ../index.php?error=Tipo de usuario no válido');
            break;
    }
    exit;
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/interfaz_iniciar_sesion.php');
    exit;
}
?>
