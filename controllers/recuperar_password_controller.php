<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validar que se ha proporcionado un correo electrónico
    if (empty($email)) {
        header('Location: ../paginas/recuperar_password.php?error=El campo de correo electrónico es obligatorio');
        exit;
    }
    
    // Solicitar restablecimiento de contraseña a Supabase
    $data = [
        'email' => $email
    ];
    
    $response = supabaseRequest('/auth/v1/recover', 'POST', $data);
    
    // Comprobar si hay errores
    if (isset($response['error']) || isset($response['code'])) {
        $errorMessage = isset($response['error_description']) ? $response['error_description'] : 'Error al enviar el correo de recuperación. Inténtalo de nuevo.';
        header('Location: ../paginas/recuperar_password.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Éxito, redirigir con mensaje
    header('Location: ../paginas/recuperar_password.php?success=Se ha enviado un correo electrónico con instrucciones para restablecer tu contraseña.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/recuperar_password.php');
    exit;
}
?>
