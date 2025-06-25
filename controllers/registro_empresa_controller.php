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
    
    // Depuración de la respuesta de autenticación
    error_log("Respuesta completa de Auth: " . json_encode($authResponse));
    
    // Obtener el ID del usuario recién registrado
    // La estructura puede variar, así que verificamos posibles ubicaciones del ID
    $userId = null;
    
    if (isset($authResponse['user']) && isset($authResponse['user']['id'])) {
        // Estructura esperada
        $userId = $authResponse['user']['id'];
    } elseif (isset($authResponse['id'])) {
        // Estructura alternativa
        $userId = $authResponse['id'];
    } elseif (isset($authResponse['data']) && isset($authResponse['data']['user']) && isset($authResponse['data']['user']['id'])) {
        // Otra posible estructura
        $userId = $authResponse['data']['user']['id'];
    }
    
    // Verificar que tenemos un ID válido
    if (empty($userId)) {
        error_log("ERROR: No se pudo extraer el ID del usuario de la respuesta de Supabase Auth: " . json_encode($authResponse));
        header('Location: ../paginas/registro_empresa.php?error=Error al obtener el identificador del usuario');
        exit;
    }
    
    error_log("Usuario reclutador creado en Supabase Auth con ID: $userId");
    
    // Crear perfil de reclutador
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => 'reclutador'
    ];
    
    error_log("Intentando crear perfil de reclutador con datos: " . json_encode($perfilData));
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Comprobar si hay errores al crear el perfil
    if (isset($perfilResponse['error'])) {
        error_log("Error al crear perfil de reclutador: " . json_encode($perfilResponse));
        header('Location: ../paginas/registro_empresa.php?error=' . urlencode('Error al crear el perfil de usuario: ' . $perfilResponse['message']));
        exit;
    }
    
    // Verificar que recibimos una respuesta correcta con el nuevo perfil
    // Supabase puede devolver diferentes formatos de respuesta exitosa
    error_log("Respuesta al crear perfil de reclutador: " . json_encode($perfilResponse));
    
    // Intentar obtener el ID del perfil recién creado de diferentes maneras
    $perfilId = null;
    
    // Caso 1: Respuesta como array con objetos
    if (is_array($perfilResponse) && !empty($perfilResponse) && isset($perfilResponse[0]['id'])) {
        $perfilId = $perfilResponse[0]['id'];
    }
    // Caso 2: Respuesta directa con id
    elseif (isset($perfilResponse['id'])) {
        $perfilId = $perfilResponse['id'];
    }
    // Caso 3: Buscar el perfil recién creado por user_id
    else {
        // Si no podemos obtener el ID directamente, buscar el perfil por user_id
        $perfilesResult = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        
        if (is_array($perfilesResult) && !empty($perfilesResult) && isset($perfilesResult[0]['id'])) {
            $perfilId = $perfilesResult[0]['id'];
        }
    }
    
    // Verificar si se encontró un ID
    if (empty($perfilId)) {
        error_log("No se pudo obtener el ID del perfil de reclutador creado. Respuesta: " . json_encode($perfilResponse));
        header('Location: ../paginas/registro_empresa.php?error=Error: No se pudo identificar el perfil creado');
        exit;
    }
    
    error_log("Perfil de reclutador creado con ID: $perfilId");
    
    // Crear datos de la empresa
    $empresaData = [
        'nombre' => $nombre_empresa,
        'rfc' => $rfc,
        'industria' => $industria,
        'direccion' => $direccion_empresa,
        'telefono' => $telefono_empresa,
        'sitio_web' => $sitio_web ?: null // Si está vacío, guardar null
    ];
    
    error_log("Intentando crear empresa con datos: " . json_encode($empresaData));
    $empresaResponse = supabaseInsert('empresas', $empresaData);
    
    // Comprobar si hay errores al crear la empresa
    if (isset($empresaResponse['error'])) {
        error_log("Error al crear empresa: " . json_encode($empresaResponse));
        header('Location: ../paginas/registro_empresa.php?error=' . urlencode('Error al guardar los datos de la empresa: ' . $empresaResponse['message']));
        exit;
    }
    
    // Verificar la respuesta al crear empresa
    error_log("Respuesta al crear empresa: " . json_encode($empresaResponse));
    
    // Intentar obtener el ID de la empresa recién creada de diferentes maneras
    $empresaId = null;
    
    // Caso 1: Respuesta como array con objetos
    if (is_array($empresaResponse) && !empty($empresaResponse) && isset($empresaResponse[0]['id'])) {
        $empresaId = $empresaResponse[0]['id'];
    }
    // Caso 2: Respuesta directa con id
    elseif (isset($empresaResponse['id'])) {
        $empresaId = $empresaResponse['id'];
    }
    // Caso 3: Buscar la empresa recién creada por nombre y rfc
    else {
        // Si no podemos obtener el ID directamente, buscar la empresa por nombre y rfc
        $empresasResult = supabaseFetch('empresas', '*', ['nombre' => $nombre_empresa, 'rfc' => $rfc]);
        
        if (is_array($empresasResult) && !empty($empresasResult) && isset($empresasResult[0]['id'])) {
            $empresaId = $empresasResult[0]['id'];
        }
    }
    
    // Verificar si se encontró un ID
    if (empty($empresaId)) {
        error_log("No se pudo obtener el ID de la empresa creada. Respuesta: " . json_encode($empresaResponse));
        header('Location: ../paginas/registro_empresa.php?error=Error: No se pudo identificar la empresa creada');
        exit;
    }
    
    error_log("Empresa creada con ID: $empresaId");
    
    // Crear datos del reclutador
    $reclutadorData = [
        'perfil_id' => $perfilId,
        'empresa_id' => $empresaId,
        'nombre' => $nombre_reclutador,
        'apellidos' => $apellidos_reclutador,
        'email' => $email,
        'cargo' => $cargo
    ];
    
    error_log("Intentando crear reclutador con datos: " . json_encode($reclutadorData));
    $reclutadorResponse = supabaseInsert('reclutadores', $reclutadorData);
    
    // Comprobar si hay errores al crear los datos del reclutador
    if (isset($reclutadorResponse['error'])) {
        error_log("Error al crear reclutador: " . json_encode($reclutadorResponse));
        header('Location: ../paginas/registro_empresa.php?error=' . urlencode('Error al guardar los datos del reclutador: ' . $reclutadorResponse['message']));
        exit;
    }
    
    // Verificar la respuesta al crear reclutador
    error_log("Respuesta al crear reclutador: " . json_encode($reclutadorResponse));
    
    // Intentar obtener el ID del reclutador recién creado de diferentes maneras
    $reclutadorId = null;
    
    // Caso 1: Respuesta como array con objetos
    if (is_array($reclutadorResponse) && !empty($reclutadorResponse) && isset($reclutadorResponse[0]['id'])) {
        $reclutadorId = $reclutadorResponse[0]['id'];
    }
    // Caso 2: Respuesta directa con id
    elseif (isset($reclutadorResponse['id'])) {
        $reclutadorId = $reclutadorResponse['id'];
    }
    // Caso 3: Buscar el reclutador recién creado por email y perfil_id
    else {
        // Si no podemos obtener el ID directamente, buscar el reclutador por email
        $reclutadoresResult = supabaseFetch('reclutadores', '*', ['email' => $email]);
        
        if (is_array($reclutadoresResult) && !empty($reclutadoresResult) && isset($reclutadoresResult[0]['id'])) {
            $reclutadorId = $reclutadoresResult[0]['id'];
        }
    }
    
    // Verificar si se encontró un ID, pero no salir con error si no se encuentra
    // porque en este punto, el registro ya está prácticamente completo
    if (empty($reclutadorId)) {
        error_log("No se pudo obtener el ID del reclutador creado. Respuesta: " . json_encode($reclutadorResponse));
    } else {
        error_log("Reclutador creado exitosamente con ID: " . $reclutadorId);
    }
    
    // Registro exitoso, redirigir a la página de inicio de sesión (index.php) con mensaje de éxito
    header('Location: ../index.php?success=Registro exitoso. Ahora puedes iniciar sesión.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/registro_empresa.php');
    exit;
}
?>
