<?php
/**
 * Archivo de verificación de acceso - Unifica la comprobación de tipos de usuario
 * Debe ser incluido al principio de todas las páginas protegidas
 */

// Verificar si una sesión ya está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Función para verificar el acceso de un usuario
 * 
 * @param string|array $tiposPermitidos Los tipos de usuario permitidos para la página actual
 * @param string $redirectUrl URL a la que redirigir si el acceso es denegado
 * @return bool True si el usuario tiene acceso, redirige en caso contrario
 */
function verificarAcceso($tiposPermitidos, $redirectUrl = '../interfaz_iniciar_sesion.php') {
    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['access_token']) || !isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        // El usuario no está autenticado
        header("Location: $redirectUrl?error=" . urlencode('Debes iniciar sesión para acceder a esta página'));
        exit;
    }
    
    $userId = $_SESSION['user']['id'];
    
    // Verificar si se ha establecido el tipo de usuario
    if (!isset($_SESSION['tipo_usuario'])) {
        // Si no está establecido, intentar recuperarlo de la base de datos
        require_once '../../config/supabase.php'; // Asegurarnos de que supabase.php está incluido
        
        // Buscar el perfil del usuario
        $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        
        // Si hay un error o no hay perfil, redirigir con un mensaje de error específico
        if (empty($userProfile) || isset($userProfile['error']) || !isset($userProfile[0])) {
            error_log("No se encontró perfil para el usuario $userId");
            header("Location: $redirectUrl?error=" . urlencode('No se pudo obtener el perfil de usuario'));
            exit;
        }
        
        // Obtener el tipo de usuario del perfil
        $tipoUsuario = obtenerTipoUsuarioNormalizado($userProfile[0]);
        
        if ($tipoUsuario === null) {
            error_log("No se pudo determinar el tipo de usuario para el perfil de $userId");
            header("Location: $redirectUrl?error=" . urlencode('No se ha podido determinar el tipo de usuario'));
            exit;
        }
        
        // Guardar en sesión para futuras referencias
        $_SESSION['tipo_usuario'] = $tipoUsuario;
    }
    
    // Si solo se proporciona un tipo de usuario como string, convertirlo a array
    if (!is_array($tiposPermitidos)) {
        $tiposPermitidos = [$tiposPermitidos];
    }
    
    // Verificar si el usuario tiene uno de los tipos permitidos
    if (!in_array($_SESSION['tipo_usuario'], $tiposPermitidos)) {
        // El usuario no tiene un tipo permitido
        $tipoActual = $_SESSION['tipo_usuario'];
        error_log("Intento de acceso no autorizado: Usuario $userId con tipo $tipoActual intentando acceder a sección para " . implode(", ", $tiposPermitidos));
        header("Location: $redirectUrl?error=" . urlencode('No tienes permisos para acceder a esta página'));
        exit;
    }
    
    // El usuario tiene acceso
    return true;
}

/**
 * Función para obtener el tipo de usuario correcto desde la base de datos
 * Esta función es útil durante la transición entre sistema antiguo (tipo_usuario) y nuevo (tipo_perfil)
 * 
 * @return string|false El tipo de usuario o false si no se pudo determinar
 */
function obtenerTipoPerfil() {
    // Verificar si ya existe en la sesión
    if (isset($_SESSION['tipo_usuario'])) {
        return $_SESSION['tipo_usuario'];
    }
    
    // Si no existe en la sesión, intentar obtenerlo de la base de datos
    if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        require_once '../../config/supabase.php';
        
        $userId = $_SESSION['user']['id'];
        $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        
        if (!empty($userProfile) && !isset($userProfile['error'])) {
            // Verificar si existe tipo_usuario (versión antigua) o tipo_perfil (nueva)
            if (isset($userProfile[0]['tipo_usuario'])) {
                $_SESSION['tipo_usuario'] = $userProfile[0]['tipo_usuario'];
                return $userProfile[0]['tipo_usuario'];
            } else if (isset($userProfile[0]['tipo_perfil'])) {
                $_SESSION['tipo_usuario'] = $userProfile[0]['tipo_perfil'];
                return $userProfile[0]['tipo_perfil'];
            }
        }
    }
    
    return false;
}

// Función para depurar problemas (eliminar en producción)
function depurarUsuario() {
    echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd; margin: 10px 0;'>";
    echo "Información de sesión:\n";
    echo "- Token: " . (isset($_SESSION['access_token']) ? "Presente" : "Ausente") . "\n";
    echo "- Usuario ID: " . (isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : "No disponible") . "\n";
    echo "- Tipo usuario: " . (isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : "No disponible") . "\n";
    echo "</pre>";
}

/**
 * Asegurar que existe un tipo de usuario válido
 * @param array $perfilData Datos del perfil desde la base de datos
 * @return string El tipo de usuario normalizado o null si no se encuentra
 */
function obtenerTipoUsuarioNormalizado($perfilData) {
    $tipoUsuario = null;
    
    // Priorizar tipo_perfil (nueva versión)
    if (isset($perfilData['tipo_perfil']) && !empty($perfilData['tipo_perfil'])) {
        $tipoUsuario = $perfilData['tipo_perfil'];
    }
    // Si no existe, intentar con tipo_usuario (versión anterior)
    else if (isset($perfilData['tipo_usuario']) && !empty($perfilData['tipo_usuario'])) {
        $tipoUsuario = $perfilData['tipo_usuario'];
    }
    
    // Normalizar 'admin' a 'administrador' para estandarizar
    if ($tipoUsuario == 'admin') {
        $tipoUsuario = 'administrador';
    }
    
    return $tipoUsuario;
}
?>
