<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y filtrar datos del formulario
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $apellidos = filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $fecha_nacimiento = filter_input(INPUT_POST, 'fecha_nacimiento');
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $experiencia = filter_input(INPUT_POST, 'experiencia', FILTER_VALIDATE_INT);
    
    // Validaciones
    if (empty($nombre) || empty($apellidos) || empty($email) || empty($contrasena) || 
        empty($confirmar_contrasena) || empty($telefono) || empty($fecha_nacimiento) || 
        empty($direccion) || $experiencia === false) {
        header('Location: ../paginas/registro_candidato.php?error=Todos los campos obligatorios deben ser completados correctamente');
        exit;
    }
    
    // Verificar que las contraseñas coinciden
    if ($contrasena !== $confirmar_contrasena) {
        header('Location: ../paginas/registro_candidato.php?error=Las contraseñas no coinciden');
        exit;
    }
    
    // Verificar longitud de la contraseña
    if (strlen($contrasena) < 8) {
        header('Location: ../paginas/registro_candidato.php?error=La contraseña debe tener al menos 8 caracteres');
        exit;
    }
    
    // Registrar usuario en Supabase Auth
    $userData = [
        'nombre' => $nombre,
        'apellidos' => $apellidos
    ];
    
    $authResponse = supabaseSignUp($email, $contrasena, $userData);
    
    // Comprobar si hay errores en la autenticación
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        $errorMessage = isset($authResponse['error_description']) ? $authResponse['error_description'] : 'Error al registrar el usuario. Inténtalo de nuevo.';
        header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Obtener el ID del usuario recién registrado
    $userId = $authResponse['user']['id'];
    
    // Crear perfil de candidato
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => 'candidato'
    ];
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Comprobar si hay errores al crear el perfil
    if (isset($perfilResponse['error'])) {
        header('Location: ../paginas/registro_candidato.php?error=Error al crear el perfil de usuario');
        exit;
    }
    
    // Obtener el ID del perfil recién creado
    $perfilId = $perfilResponse[0]['id'];
    
    // Crear datos del candidato
    $candidatoData = [
        'perfil_id' => $perfilId,
        'telefono' => $telefono,
        'fecha_nacimiento' => $fecha_nacimiento,
        'direccion' => $direccion,
        'titulo' => $titulo ?: null, // Si está vacío, guardar null
        'anios_experiencia' => intval($experiencia)
    ];
    
    $candidatoResponse = supabaseInsert('candidatos', $candidatoData);
    
    // Comprobar si hay errores al crear los datos del candidato
    if (isset($candidatoResponse['error'])) {
        header('Location: ../paginas/registro_candidato.php?error=Error al guardar los datos del candidato');
        exit;
    }
    
    // Registro exitoso, redirigir a la página de inicio de sesión con mensaje de éxito
    header('Location: ../paginas/interfaz_iniciar_sesion.php?success=Registro exitoso. Ahora puedes iniciar sesión.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/registro_candidato.php');
    exit;
}
?>
