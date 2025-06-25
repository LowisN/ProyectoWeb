<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y filtrar datos del formulario
    $nombre_empresa = filter_input(INPUT_POST, 'nombre_empresa', FILTER_SANITIZE_STRING);
    $rfc = filter_input(INPUT_POST, 'rfc', FILTER_SANITIZE_STRING);
    $industria = filter_input(INPUT_POST, 'industria', FILTER_SANITIZE_STRING);
    $direccion_empresa = filter_input(INPUT_POST, 'direccion_empresa', FILTER_SANITIZE_STRING);
    $telefono_empresa = filter_input(INPUT_POST, 'telefono_empresa', FILTER_SANITIZE_STRING);
    $sitio_web = filter_input(INPUT_POST, 'sitio_web', FILTER_SANITIZE_URL);
    $nombre_reclutador = filter_input(INPUT_POST, 'nombre_reclutador', FILTER_SANITIZE_STRING);
    $apellidos_reclutador = filter_input(INPUT_POST, 'apellidos_reclutador', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $cargo = filter_input(INPUT_POST, 'cargo', FILTER_SANITIZE_STRING);
    
    // Validaciones
    if (empty($nombre_empresa) || empty($rfc) || empty($industria) || empty($direccion_empresa) || 
        empty($telefono_empresa) || empty($nombre_reclutador) || empty($apellidos_reclutador) || 
        empty($email) || empty($contrasena) || empty($confirmar_contrasena) || empty($cargo)) {
        header('Location: ../paginas/registro_empresa.php?error=Todos los campos obligatorios deben ser completados');
        exit;
    }
    
    // Verificar que las contraseñas coinciden
    if ($contrasena !== $confirmar_contrasena) {
        header('Location: ../paginas/registro_empresa.php?error=Las contraseñas no coinciden');
        exit;
    }
    
    // Verificar longitud de la contraseña
    if (strlen($contrasena) < 8) {
        header('Location: ../paginas/registro_empresa.php?error=La contraseña debe tener al menos 8 caracteres');
        exit;
    }
    
    // Registrar usuario en Supabase Auth
    $userData = [
        'nombre' => $nombre_reclutador,
        'apellidos' => $apellidos_reclutador,
        'empresa' => $nombre_empresa
    ];
    
    $authResponse = supabaseSignUp($email, $contrasena, $userData);
    
    // Comprobar si hay errores en la autenticación
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        $errorMessage = isset($authResponse['error_description']) ? $authResponse['error_description'] : 'Error al registrar el usuario. Inténtalo de nuevo.';
        header('Location: ../paginas/registro_empresa.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Obtener el ID del usuario recién registrado
    $userId = $authResponse['user']['id'];
    
    // Crear perfil de reclutador
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => 'reclutador'
    ];
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Comprobar si hay errores al crear el perfil
    if (isset($perfilResponse['error'])) {
        header('Location: ../paginas/registro_empresa.php?error=Error al crear el perfil de usuario');
        exit;
    }
    
    // Obtener el ID del perfil recién creado
    $perfilId = $perfilResponse[0]['id'];
    
    // Crear datos de la empresa
    $empresaData = [
        'nombre' => $nombre_empresa,
        'rfc' => $rfc,
        'industria' => $industria,
        'direccion' => $direccion_empresa,
        'telefono' => $telefono_empresa,
        'sitio_web' => $sitio_web ?: null // Si está vacío, guardar null
    ];
    
    $empresaResponse = supabaseInsert('empresas', $empresaData);
    
    // Comprobar si hay errores al crear la empresa
    if (isset($empresaResponse['error'])) {
        header('Location: ../paginas/registro_empresa.php?error=Error al guardar los datos de la empresa');
        exit;
    }
    
    // Obtener el ID de la empresa recién creada
    $empresaId = $empresaResponse[0]['id'];
    
    // Crear datos del reclutador
    $reclutadorData = [
        'perfil_id' => $perfilId,
        'empresa_id' => $empresaId,
        'nombre' => $nombre_reclutador,
        'apellidos' => $apellidos_reclutador,
        'email' => $email,
        'cargo' => $cargo
    ];
    
    $reclutadorResponse = supabaseInsert('reclutadores', $reclutadorData);
    
    // Comprobar si hay errores al crear los datos del reclutador
    if (isset($reclutadorResponse['error'])) {
        header('Location: ../paginas/registro_empresa.php?error=Error al guardar los datos del reclutador');
        exit;
    }
    
    // Registro exitoso, redirigir a la página de inicio de sesión con mensaje de éxito
    header('Location: ../paginas/interfaz_iniciar_sesion.php?success=Registro exitoso. Ahora puedes iniciar sesión.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/registro_empresa.php');
    exit;
}
?>
