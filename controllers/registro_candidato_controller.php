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
    
    // Registrar información de diagnóstico antes de la autenticación
    error_log("Intentando registrar usuario con email: $email");
    
    // Añadir información completa de debug antes de la llamada a supabaseSignUp
    error_log("Iniciando registro en Supabase para $email con datos: " . json_encode($userData));
    
    $authResponse = supabaseSignUp($email, $contrasena, $userData);
    
    // Comprobar si hay errores en la autenticación
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        // Registrar información detallada del error para diagnóstico
        error_log("Error en supabaseSignUp: " . print_r($authResponse, true));
        
        // Determinar un mensaje de error más específico y amigable
        $errorMessage = 'Error al registrar el usuario. Inténtalo de nuevo.';
        
        if (isset($authResponse['error'])) {
            if (is_string($authResponse['error'])) {
                $errorType = strtolower($authResponse['error']);
                
                // Mensajes más específicos según el tipo de error
                if (strpos($errorType, 'already') !== false || strpos($errorType, 'exist') !== false) {
                    $errorMessage = "El email '$email' ya está registrado. Intenta iniciar sesión o usar otro correo.";
                } elseif (strpos($errorType, 'invalid') !== false && strpos($errorType, 'email') !== false) {
                    $errorMessage = "El email '$email' no tiene un formato válido.";
                } elseif (strpos($errorType, 'password') !== false) {
                    $errorMessage = "La contraseña no cumple con los requisitos de seguridad. Debe tener al menos 8 caracteres.";
                }
            } elseif (is_array($authResponse['error']) && isset($authResponse['error']['message'])) {
                $errorMessage = $authResponse['error']['message'];
                
                // Añadir información sobre el código HTTP si está disponible
                if (isset($authResponse['statusCode'])) {
                    error_log("Error HTTP en registro: " . $authResponse['statusCode']);
                    $errorMessage .= " (HTTP " . $authResponse['statusCode'] . ")";
                }
            }
        } elseif (isset($authResponse['error_description'])) {
            $errorMessage = $authResponse['error_description'];
        } elseif (isset($authResponse['msg'])) {
            $errorMessage = $authResponse['msg'];
        }
        
        // Verificar si el usuario ya existe para dar instrucciones claras
        if (strpos(strtolower($errorMessage), 'already') !== false || 
            strpos(strtolower($errorMessage), 'exist') !== false ||
            strpos(strtolower($errorMessage), 'registered') !== false) {
            $errorMessage .= " Puedes <a href='../paginas/interfaz_iniciar_sesion.php'>iniciar sesión aquí</a>.";
            
            // Verificar si el problema podría ser que el usuario existe pero no tiene perfil
            error_log("El usuario $email ya existe, comprobando si tiene perfil...");
            
            // Intento de inicio de sesión para verificar si las credenciales son válidas
            $loginResponse = supabaseSignIn($email, $contrasena);
            if (isset($loginResponse['access_token'])) {
                error_log("Se pudo iniciar sesión con las credenciales proporcionadas. Obteniendo ID de usuario...");
                $userId = $loginResponse['user']['id'];
                
                // Verificar si existe un perfil para este usuario
                $checkProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
                if (empty($checkProfile) || isset($checkProfile['error'])) {
                    error_log("El usuario existe pero no tiene perfil. Sugiriendo herramienta de diagnóstico.");
                    $errorMessage .= " <br>Parece que tu cuenta existe pero podría faltar tu perfil de usuario. <a href='../config/crear_perfiles_faltantes.php'>Haz clic aquí para arreglar tu perfil</a>.";
                }
            }
        }
        
        // Mostrar información más detallada para ayudar al diagnóstico
        if (stripos($errorMessage, 'desconocido') !== false) {
            error_log("Error desconocido al registrar usuario. Verificando conectividad con Supabase.");
            
            // Verificar si hay problemas de red o conectividad
            $pingResponse = supabaseRequest('/rest/v1/perfiles?limit=1', 'GET');
            if (isset($pingResponse['error']) && stripos($pingResponse['error'], 'conexión') !== false) {
                $errorMessage = "No se puede conectar con el servidor de autenticación. Verifica tu conexión a internet o inténtalo más tarde.";
            } else {
                $errorMessage .= " <br>Detalles técnicos: Revisa la consola del servidor para más información o contacta al administrador.";
            }
        }
        
        header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Obtener el ID del usuario recién registrado
    $userId = $authResponse['user']['id'];
    
    // Crear perfil de candidato
    $perfilData = [
        'user_id' => $userId,
        'email' => $email, // Guardar el email para facilitar búsquedas
        'tipo_usuario' => 'candidato',
        'tipo_perfil' => 'candidato', // Asegurar consistencia en ambos campos
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'fecha_creacion' => date('Y-m-d H:i:s')
    ];
    
    // Registrar información de diagnóstico antes de la inserción
    error_log("Intentando crear perfil para usuario: $userId con email: $email");
    error_log("Datos del perfil a insertar: " . print_r($perfilData, true));
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Si hay un error, registrar información detallada y realizar diagnóstico
    if (isset($perfilResponse['error'])) {
        error_log("Error al crear perfil para $email: " . print_r($perfilResponse['error'], true));
        
        // Diagnóstico avanzado del error
        $errorDetail = '';
        $techDetails = '';
        
        // Verificar si existe la tabla perfiles
        $checkTables = supabaseRequest('/rest/v1/perfiles?limit=1', 'GET');
        if (isset($checkTables['statusCode']) && $checkTables['statusCode'] == 404) {
            error_log("No se encontró la tabla 'perfiles'. Posible problema de estructura de la base de datos.");
            $errorDetail = "No se encontró la tabla 'perfiles' en la base de datos. ";
            $techDetails = "Es necesario verificar la estructura de la base de datos.";
        } 
        // Verificar si hay problemas de permisos
        elseif (isset($perfilResponse['error']['message']) && 
                (stripos($perfilResponse['error']['message'], 'permission') !== false || 
                 stripos($perfilResponse['error']['message'], 'not allowed') !== false)) {
            error_log("Problema de permisos al insertar en tabla 'perfiles'");
            $errorDetail = "Problema de permisos al crear el perfil de usuario. ";
            $techDetails = "Revisar las políticas RLS en Supabase para la tabla 'perfiles'.";
        } 
        // Mensaje genérico si no hay diagnóstico específico
        else {
            $errorDetail = "Error al crear el perfil de usuario: " . urlencode($perfilResponse['error']['message'] ?? 'Error desconocido');
        }
        
        $errorMessage = $errorDetail;
        if (!empty($techDetails)) {
            error_log("Detalles técnicos: $techDetails");
            // Añadir enlace a herramienta de diagnóstico
            $errorMessage .= "<br>Para solucionar este problema, utilice la <a href='../config/verificar_tablas.php'>herramienta de verificación de tablas</a>.";
        }
        
        header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Verificar que la respuesta tiene la estructura esperada
    if (!isset($perfilResponse[0]) || !isset($perfilResponse[0]['id'])) {
        error_log("Respuesta inesperada al crear perfil: " . print_r($perfilResponse, true));
        
        // Intentar recuperar el perfil por user_id
        $checkPerfil = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        if (!empty($checkPerfil) && isset($checkPerfil[0]['id'])) {
            $perfilId = $checkPerfil[0]['id'];
            error_log("Perfil recuperado mediante consulta alternativa. ID: $perfilId");
        } else {
            // Intentar un enfoque directo con REST API
            error_log("Intentando crear perfil directamente por REST API");
            
            // Usar headers más específicos para la API de Supabase
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . SUPABASE_KEY,
                'Authorization: Bearer ' . SUPABASE_KEY,
                'Prefer: return=representation'
            ];
            
            // Preparar la URL y datos
            $url = SUPABASE_URL . '/rest/v1/perfiles';
            $jsonData = json_encode($perfilData);
            
            // Configurar cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            // Ejecutar la petición
            $curlResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("Respuesta directa REST: HTTP $httpCode - " . substr($curlResponse, 0, 300));
            
            if ($httpCode >= 200 && $httpCode < 300 && !empty($curlResponse)) {
                $createdProfile = json_decode($curlResponse, true);
                if (is_array($createdProfile) && isset($createdProfile[0]['id'])) {
                    $perfilId = $createdProfile[0]['id'];
                    error_log("Perfil creado correctamente mediante REST directo: ID $perfilId");
                } else {
                    error_log("No se pudo obtener ID del perfil desde la respuesta directa: " . $curlResponse);
                    
                    // Intento final: verificar si el perfil se creó a pesar de no obtener el ID
                    $lastAttempt = supabaseFetch('perfiles', '*', ['user_id' => $userId, 'email' => $email]);
                    if (!empty($lastAttempt) && isset($lastAttempt[0]['id'])) {
                        $perfilId = $lastAttempt[0]['id'];
                        error_log("¡Recuperación final exitosa! Se encontró el perfil con ID: $perfilId");
                    } else {
                        // Crear una respuesta de error detallada
                        error_log("Todos los intentos de crear/recuperar el perfil han fallado");
                        
                        // Preparar información de diagnóstico
                        $diagnosticInfo = "
                            <strong>Información de diagnóstico:</strong><br>
                            - Se creó el usuario en Auth, pero no se pudo crear el perfil<br>
                            - ID de usuario registrado: $userId<br>
                            - Email: $email<br>
                            - Respuesta HTTP: $httpCode<br>
                            <br>
                            <a href='../config/diagnostico_perfiles.php'>Ejecutar diagnóstico completo</a> | 
                            <a href='../config/crear_perfiles_faltantes.php'>Crear perfiles faltantes</a>
                        ";
                        
                        header('Location: ../paginas/registro_candidato.php?error=Error al crear el perfil de usuario: No se pudo obtener ID. ' . urlencode($diagnosticInfo));
                        exit;
                    }
                }
            } else {
                error_log("Error al crear perfil mediante REST directo: " . ($curlError ?: $curlResponse));
                
                // Análisis del código de error HTTP para dar un mensaje más claro
                $errorDetails = "";
                switch ($httpCode) {
                    case 400:
                        $errorDetails = "Solicitud incorrecta. Posible problema con los datos enviados.";
                        break;
                    case 401:
                    case 403:
                        $errorDetails = "Problema de autorización. Revisar las claves API y políticas de seguridad.";
                        break;
                    case 404:
                        $errorDetails = "La tabla 'perfiles' no existe o no está accesible.";
                        break;
                    case 409:
                        $errorDetails = "Conflicto de datos. Posible duplicación de registros.";
                        break;
                    case 500:
                    case 502:
                    case 503:
                        $errorDetails = "Error en el servidor de base de datos. Inténtalo más tarde.";
                        break;
                    default:
                        $errorDetails = "Error inesperado (HTTP $httpCode).";
                }
                
                error_log("Detalle del error: $errorDetails");
                
                // Enlace a herramienta de diagnóstico
                $errorLink = "<br><a href='../config/verificar_tablas.php'>Verificar estructura de tablas</a>";
                
                header('Location: ../paginas/registro_candidato.php?error=Error al crear el perfil de usuario: ' . urlencode($errorDetails) . urlencode($errorLink));
                exit;
            }
        }
    } else {
        // Obtener el ID del perfil recién creado
        $perfilId = $perfilResponse[0]['id'];
        error_log("Perfil creado correctamente con ID: $perfilId");
    }
    
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
        error_log("Error al crear registro de candidato: " . print_r($candidatoResponse['error'], true));
        
        // Análisis detallado del error
        $errorMsg = $candidatoResponse['error']['message'] ?? 'Error desconocido';
        
        // Verificar si es un problema de tabla no existente
        if (isset($candidatoResponse['statusCode']) && $candidatoResponse['statusCode'] == 404) {
            $errorDetail = "La tabla 'candidatos' no existe en la base de datos. ";
            $errorLink = "<br><a href='../config/verificar_tablas.php'>Crear estructura de tablas necesaria</a>";
            header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorDetail) . urlencode($errorLink));
        } 
        // Verificar si es un problema de restricción de integridad referencial
        elseif (stripos($errorMsg, 'foreign key') !== false || stripos($errorMsg, 'reference') !== false) {
            $errorDetail = "Error de referencia: El perfil de usuario no está correctamente enlazado. ";
            $errorLink = "<br><a href='../config/diagnostico_perfiles.php'>Ejecutar diagnóstico de perfiles</a>";
            header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorDetail) . urlencode($errorLink));
        } 
        // Error genérico
        else {
            // Aunque hubo error en datos del candidato, el usuario y perfil se crearon correctamente
            // Ofrecer al usuario la opción de iniciar sesión y completar su perfil después
            $errorDetail = "Se creó tu cuenta pero hubo un problema al guardar tus datos personales. ";
            $errorLink = "Puedes <a href='../paginas/interfaz_iniciar_sesion.php'>iniciar sesión</a> y completar tu perfil después.";
            header('Location: ../paginas/registro_candidato.php?error=' . urlencode($errorDetail) . urlencode($errorLink));
        }
        exit;
    }
    
    // Agregar registro informativo para confirmar el éxito completo del registro
    error_log("REGISTRO EXITOSO: Usuario ($userId), Perfil ($perfilId) y Candidato creados correctamente para $email");
    
    // Registro exitoso, redirigir a la página de inicio de sesión con mensaje de éxito
    header('Location: ../paginas/interfaz_iniciar_sesion.php?success=Registro exitoso. Ahora puedes iniciar sesión con tu cuenta de candidato.');
    exit;
    
} else {
    // Si se intenta acceder directamente al controlador sin enviar el formulario
    header('Location: ../paginas/registro_candidato.php');
    exit;
}
?>
