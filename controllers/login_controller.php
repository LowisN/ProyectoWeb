<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['contrasena']; // No filtrar para evitar problemas con caracteres especiales
    
    // Validar que se han proporcionado los datos necesarios
    if (empty($email) || empty($password)) {
        header('Location: ../paginas/interfaz_iniciar_sesion.php?error=Todos los campos son obligatorios');
        exit;
    }
    
    // Intentar iniciar sesión con Supabase
    $response = supabaseSignIn($email, $password);
    
    // Comprobar si hay errores
    if (isset($response['error']) || isset($response['code'])) {
        $errorMessage = isset($response['error_description']) ? $response['error_description'] : 'Error al iniciar sesión. Inténtalo de nuevo.';
        header('Location: ../paginas/interfaz_iniciar_sesion.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Almacenar el token de acceso en la sesión
    $_SESSION['access_token'] = $response['access_token'];
    $_SESSION['refresh_token'] = $response['refresh_token'];
    $_SESSION['user'] = $response['user'];
    
    // Obtener el perfil del usuario para determinar el tipo
    $userId = $response['user']['id'];
    $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
    
    if (empty($userProfile) || isset($userProfile['error'])) {
        header('Location: ../paginas/interfaz_iniciar_sesion.php?error=No se pudo obtener el perfil de usuario');
        exit;
    }
    
    // Guardar el tipo de usuario en la sesión
    $_SESSION['tipo_usuario'] = $userProfile[0]['tipo_usuario'];
    
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
            header('Location: ../paginas/interfaz_iniciar_sesion.php?error=Tipo de usuario no válido');
            break;
    }
    exit;
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/interfaz_iniciar_sesion.php');
    exit;
}
?>
