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
        header('Location: ../paginas/registro_candidato.php?error=Error al obtener el identificador del usuario');
        exit;
    }
    
    error_log("Usuario creado en Supabase Auth con ID: $userId");
    
    // Crear perfil de candidato
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => 'candidato'
    ];
    
    error_log("Intentando crear perfil con datos: " . json_encode($perfilData));
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Comprobar si hay errores al crear el perfil
    if (isset($perfilResponse['error'])) {
        error_log("Error al crear perfil: " . json_encode($perfilResponse));
        header('Location: ../paginas/registro_candidato.php?error=' . urlencode('Error al crear el perfil de usuario: ' . $perfilResponse['message']));
        exit;
    }
    
    // Verificar que recibimos una respuesta correcta con el nuevo perfil
    // Supabase puede devolver diferentes formatos de respuesta exitosa
    error_log("Respuesta al crear perfil: " . json_encode($perfilResponse));
    
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
        error_log("No se pudo obtener el ID del perfil creado. Respuesta: " . json_encode($perfilResponse));
        header('Location: ../paginas/registro_candidato.php?error=Error: No se pudo identificar el perfil creado');
        exit;
    }
    
    error_log("Perfil creado con ID: $perfilId");
    
    // Crear datos del candidato
    $candidatoData = [
        'perfil_id' => $perfilId,
        'telefono' => $telefono,
        'fecha_nacimiento' => $fecha_nacimiento,
        'direccion' => $direccion,
        'titulo' => $titulo ?: null, // Si está vacío, guardar null
        'anios_experiencia' => intval($experiencia)
    ];
    
    error_log("Intentando crear candidato con datos: " . json_encode($candidatoData));
    $candidatoResponse = supabaseInsert('candidatos', $candidatoData);
    
    // Comprobar si hay errores al crear los datos del candidato
    if (isset($candidatoResponse['error'])) {
        error_log("Error al crear candidato: " . json_encode($candidatoResponse));
        header('Location: ../paginas/registro_candidato.php?error=' . urlencode('Error al guardar los datos del candidato: ' . $candidatoResponse['message']));
        exit;
    }
    
    // Verificar la respuesta al crear candidato
    error_log("Respuesta al crear candidato: " . json_encode($candidatoResponse));
    
    // Intentar obtener el ID del candidato recién creado de diferentes maneras
    $candidatoId = null;
    
    // Caso 1: Respuesta como array con objetos
    if (is_array($candidatoResponse) && !empty($candidatoResponse) && isset($candidatoResponse[0]['id'])) {
        $candidatoId = $candidatoResponse[0]['id'];
    }
    // Caso 2: Respuesta directa con id
    elseif (isset($candidatoResponse['id'])) {
        $candidatoId = $candidatoResponse['id'];
    }
    // Caso 3: Buscar el candidato recién creado por perfil_id
    else {
        // Si no podemos obtener el ID directamente, buscar el candidato por perfil_id
        $candidatosResult = supabaseFetch('candidatos', '*', ['perfil_id' => $perfilId]);
        
        if (is_array($candidatosResult) && !empty($candidatosResult) && isset($candidatosResult[0]['id'])) {
            $candidatoId = $candidatosResult[0]['id'];
        }
    }
    
    // Verificar si se encontró un ID, pero no salir con error si no se encuentra
    // porque en este punto, el registro ya está prácticamente completo
    if (empty($candidatoId)) {
        error_log("No se pudo obtener el ID del candidato creado. Respuesta: " . json_encode($candidatoResponse));
    } else {
        error_log("Candidato creado exitosamente con ID: " . $candidatoId);
    }
    
    // Registro exitoso, redirigir a la página de inicio de sesión (index.php) con mensaje de éxito
    header('Location: ../index.php?success=Registro exitoso. Ahora puedes iniciar sesión.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/registro_candidato.php');
    exit;
}
?>
