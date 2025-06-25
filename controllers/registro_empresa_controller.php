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
        'email' => $email, // Guardar el email para facilitar búsquedas
        'tipo_usuario' => 'reclutador',
        'tipo_perfil' => 'reclutador', // Asegurar consistencia en ambos campos
        'nombre' => $nombre_reclutador,
        'apellidos' => $apellidos_reclutador,
        'fecha_creacion' => date('Y-m-d H:i:s')
    ];
    
    // Registrar información de diagnóstico antes de la inserción
    error_log("Intentando crear perfil para usuario reclutador: $userId con email: $email");
    error_log("Datos del perfil a insertar: " . print_r($perfilData, true));
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Si hay un error, registrar información detallada
    if (isset($perfilResponse['error'])) {
        error_log("Error al crear perfil para $email: " . print_r($perfilResponse['error'], true));
        header('Location: ../paginas/registro_empresa.php?error=Error al crear el perfil de usuario: ' . urlencode($perfilResponse['error']['message'] ?? 'Error desconocido'));
        exit;
    }
    
    // Verificar que la respuesta tiene la estructura esperada
    if (!isset($perfilResponse[0]) || !isset($perfilResponse[0]['id'])) {
        error_log("Respuesta inesperada al crear perfil de reclutador: " . print_r($perfilResponse, true));
        
        // Intentar recuperar el perfil por user_id
        $checkPerfil = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        if (!empty($checkPerfil) && isset($checkPerfil[0]['id'])) {
            $perfilId = $checkPerfil[0]['id'];
            error_log("Perfil de reclutador recuperado mediante consulta alternativa. ID: $perfilId");
        } else {
            error_log("No se pudo recuperar el perfil de reclutador. Respuesta de la consulta: " . print_r($checkPerfil, true));
            header('Location: ../paginas/registro_empresa.php?error=Error al crear el perfil de usuario: Estructura de respuesta inválida');
            exit;
        }
    } else {
        // Obtener el ID del perfil recién creado
        $perfilId = $perfilResponse[0]['id'];
        error_log("Perfil de reclutador creado correctamente con ID: $perfilId");
    }
    
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
