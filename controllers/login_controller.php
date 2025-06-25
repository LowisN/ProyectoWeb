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
    $email = $response['user']['email'];
    
    // Incluir el helper de RLS si existe
    if (file_exists('../helpers/rls_helper.php')) {
        require_once '../helpers/rls_helper.php';
        // Usar la función robusta que maneja errores de RLS
        $userProfile = obtenerPerfilRobusto($userId, $email);
    } else {
        // Método tradicional
        $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
    }
    
    if (empty($userProfile) || isset($userProfile['error'])) {
        $errorMsg = "No se pudo obtener el perfil de usuario";
        
        // Si es un error de RLS, añadir enlace a herramienta de diagnóstico
        if (isset($userProfile['error']) && 
            (function_exists('esErrorRLS') && esErrorRLS($userProfile) || 
             strpos($userProfile['error'], 'recursion') !== false)) {
            
            $errorMsg .= ". Se detectó un problema con las políticas de seguridad. ";
            $errorMsg .= "<a href='../config/diagnostico_rls.php?user_id=" . urlencode($userId) . "'>Haga clic aquí para diagnosticar y resolver</a>.";
        }
        
        header('Location: ../paginas/interfaz_iniciar_sesion.php?error=' . urlencode($errorMsg));
        exit;
    }
    
    // Incluir el helper de autenticación
    if (!function_exists('obtenerTipoUsuarioNormalizado')) {
        require_once '../helpers/auth_helper.php';
    }
    
    // Usar la función del helper para obtener el tipo de usuario normalizado
    $tipoUsuario = obtenerTipoUsuarioNormalizado($userProfile[0]);
    
    // Verificar si se encontró un tipo de usuario
    if ($tipoUsuario === null) {
        // Intentar determinar el tipo basado en otras tablas
        $esAdmin = false; // Por defecto no es admin, a menos que se implemente otra lógica
        $esCandidato = supabaseFetch('candidatos', 'id', ['perfil_id' => $userProfile[0]['id']]);
        $esReclutador = supabaseFetch('reclutadores', 'id', ['perfil_id' => $userProfile[0]['id']]);
        
        if (!empty($esCandidato) && !isset($esCandidato['error'])) {
            $tipoUsuario = 'candidato';
        } else if (!empty($esReclutador) && !isset($esReclutador['error'])) {
            $tipoUsuario = 'reclutador';
        } else if ($esAdmin) {
            $tipoUsuario = 'administrador';
        } else {
            header('Location: ../paginas/interfaz_iniciar_sesion.php?error=Error: No se pudo determinar el tipo de usuario');
            exit;
        }
        
        // Actualizar el registro en la base de datos para futuras ocasiones
        supabaseUpdate('perfiles', [
            'tipo_perfil' => $tipoUsuario, 
            'tipo_usuario' => $tipoUsuario
        ], ['id' => $userProfile[0]['id']]);
    }
    
    // Guardar en sesión
    $_SESSION['tipo_usuario'] = $tipoUsuario;
    
    // Solo mostrar información de depuración en desarrollo
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Tipo de usuario encontrado: " . $tipoUsuario);
        error_log("Datos del perfil: " . print_r($userProfile[0], true));
    }
    
    // Redirigir según el tipo de usuario
    // Validar que el tipo de usuario sea uno de los valores permitidos
    $tiposValidos = ['administrador', 'candidato', 'reclutador', 'admin'];
    if (!in_array($tipoUsuario, $tiposValidos)) {
        header('Location: ../paginas/interfaz_iniciar_sesion.php?error=Tipo de usuario no válido: ' . htmlspecialchars($tipoUsuario));
        exit;
    }
    
    switch ($tipoUsuario) {
        case 'administrador':
        case 'admin': // Para compatibilidad con versiones anteriores
            header('Location: ../paginas/admin/dashboard.php');
            break;
        case 'candidato':
            header('Location: ../paginas/candidato/home_candidato.php');
            break;
        case 'reclutador':
            header('Location: ../paginas/empresa/home_empresa.php');
            break;
    }
    exit;
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/interfaz_iniciar_sesion.php');
    exit;
}
?>
