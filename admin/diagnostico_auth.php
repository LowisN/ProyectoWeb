<?php
/**
 * Herramienta de diagnóstico para problemas de autenticación
 * Esta página muestra información detallada sobre el estado actual de la sesión
 * y permite solucionar problemas relacionados con los tipos de usuario
 */
session_start();
require_once '../config/supabase.php';

// Variables para diagnóstico de usuarios sin perfil
$usuarios = [];
$perfiles = [];
$usuariosSinPerfil = [];
$perfilesCreados = 0;
$logsMensaje = [];

// Función para registrar mensajes de log
function logMensaje($mensaje, $tipo = 'info') {
    global $logsMensaje;
    $logsMensaje[] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    error_log("[Diagnóstico Auth] - $tipo: $mensaje");
}

// Procesar formularios si son enviados
$resultado = null;
$resultadoPerfil = null;

// Formulario para verificar usuarios sin perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['detectar_sin_perfil'])) {
    try {
        // Intentar obtener todos los usuarios (requiere permisos de administrador)
        $usuarios = supabaseFetchRaw("SELECT id, email, user_metadata FROM auth.users;");
        
        if (isset($usuarios['error'])) {
            $resultado = "Error al obtener usuarios: " . print_r($usuarios['error'], true);
        } else {
            // Obtener todos los perfiles
            $perfiles = supabaseFetch('perfiles', '*');
            
            if (isset($perfiles['error'])) {
                $resultado = "Error al obtener perfiles: " . print_r($perfiles['error'], true);
            } else {
                // Crear un mapa de user_id => perfil para búsqueda rápida
                $mapaPerfiles = [];
                foreach ($perfiles as $perfil) {
                    if (isset($perfil['user_id'])) {
                        $mapaPerfiles[$perfil['user_id']] = $perfil;
                    }
                }
                
                // Verificar qué usuarios no tienen perfil
                foreach ($usuarios as $usuario) {
                    $userId = $usuario['id'];
                    
                    if (!isset($mapaPerfiles[$userId])) {
                        // Este usuario no tiene perfil
                        $usuariosSinPerfil[] = $usuario;
                        logMensaje("Usuario sin perfil encontrado: " . $usuario['email'], 'warning');
                    }
                }
                
                $resultado = "Se encontraron " . count($usuariosSinPerfil) . " usuarios sin perfil de un total de " . count($usuarios) . " usuarios.";
            }
        }
    } catch (Exception $e) {
        $resultado = "Error: " . $e->getMessage();
    }
}

// Formulario para crear perfiles faltantes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_perfiles'])) {
    $usuariosJson = $_POST['usuarios_json'] ?? '';
    $usuariosList = json_decode($usuariosJson, true);
    
    if (empty($usuariosList)) {
        $resultado = "No hay usuarios para procesar";
    } else {
        $perfilesCreados = 0;
        $errores = [];
        
        foreach ($usuariosList as $usuario) {
            try {
                $userId = $usuario['id'];
                $email = $usuario['email'];
                $userMetadata = $usuario['user_metadata'] ?? [];
                
                // Determinar el tipo de usuario basado en metadatos
                $tipoUsuario = 'candidato'; // Tipo por defecto
                
                // Si hay metadatos que indican que es de una empresa, marcarlo como reclutador
                if (isset($userMetadata['empresa']) && !empty($userMetadata['empresa'])) {
                    $tipoUsuario = 'reclutador';
                }
                
                // Crear el perfil
                $perfilData = [
                    'user_id' => $userId,
                    'tipo_usuario' => $tipoUsuario,
                    'tipo_perfil' => $tipoUsuario,
                    'email' => $email
                ];
                
                $resultadoInsert = supabaseInsert('perfiles', $perfilData);
                
                if (isset($resultadoInsert['error'])) {
                    $errores[] = "Error al crear perfil para $email: " . $resultadoInsert['error'];
                } else {
                    $perfilesCreados++;
                    logMensaje("Perfil creado para $email como $tipoUsuario", 'success');
                    
                    // Si es candidato, crear registro en tabla candidatos
                    if ($tipoUsuario === 'candidato' && isset($resultadoInsert[0]['id'])) {
                        $perfilId = $resultadoInsert[0]['id'];
                        $candidatoData = [
                            'perfil_id' => $perfilId,
                            'estado' => 'activo',
                            'fecha_registro' => date('Y-m-d H:i:s')
                        ];
                        
                        $resCandidato = supabaseInsert('candidatos', $candidatoData);
                        if (!isset($resCandidato['error'])) {
                            logMensaje("Registro de candidato creado para $email", 'success');
                        } else {
                            $errores[] = "Error al crear registro de candidato para $email: " . print_r($resCandidato['error'], true);
                        }
                    }
                }
            } catch (Exception $e) {
                $errores[] = "Excepción al crear perfil para " . $usuario['email'] . ": " . $e->getMessage();
            }
        }
        
        $resultado = "Se crearon $perfilesCreados perfiles correctamente. ";
        if (!empty($errores)) {
            $resultado .= "Errores encontrados: " . implode("; ", $errores);
        }
    }
}

// Formulario para verificar un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificar_usuario'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!empty($email)) {
        // Buscar el usuario por email
        $usuarioQuery = "SELECT * FROM perfiles WHERE email = '$email'";
        $resultado = "Búsqueda de usuario con email: $email";
        
        try {
            $userProfiles = supabaseFetch('perfiles', '*', ['email' => $email]);
            
            if (empty($userProfiles) || isset($userProfiles['error'])) {
                $resultado .= "\nNo se encontró ningún perfil con ese email.";
                if (isset($userProfiles['error'])) {
                    $resultado .= "\nError: " . print_r($userProfiles['error'], true);
                }
            } else {
                $resultado .= "\nSe encontraron " . count($userProfiles) . " perfiles:";
                foreach ($userProfiles as $profile) {
                    $resultado .= "\n\nID: " . $profile['id'];
                    $resultado .= "\nUser ID: " . $profile['user_id'];
                    
                    if (isset($profile['tipo_usuario'])) {
                        $resultado .= "\nTipo de usuario (campo antiguo): " . $profile['tipo_usuario'];
                    }
                    
                    if (isset($profile['tipo_perfil'])) {
                        $resultado .= "\nTipo de perfil (campo nuevo): " . $profile['tipo_perfil'];
                    }
                    
                    if (!isset($profile['tipo_usuario']) && !isset($profile['tipo_perfil'])) {
                        $resultado .= "\nADVERTENCIA: No se encontró ningún campo de tipo de usuario/perfil.";
                    }
                    
                    $resultado .= "\nCreado: " . $profile['created_at'];
                }
            }
        } catch (Exception $e) {
            $resultado .= "\nError al ejecutar la consulta: " . $e->getMessage();
        }
    }
}

// Formulario para actualizar el tipo de perfil de un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_STRING);
    $tipo_perfil = filter_input(INPUT_POST, 'tipo_perfil', FILTER_SANITIZE_STRING);
    
    if (!empty($user_id) && !empty($tipo_perfil)) {
        $resultadoPerfil = "Actualizando tipo de perfil para el usuario con ID: $user_id";
        
        // Verificar si el usuario existe antes de actualizar
        $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $user_id]);
        
        if (empty($userProfile) || isset($userProfile['error'])) {
            $resultadoPerfil .= "\nNo se encontró ningún perfil con ese ID de usuario.";
        } else {
            // Crear actualización basada en los campos existentes
            $updateData = [];
            
            if (isset($userProfile[0]['tipo_usuario'])) {
                $updateData['tipo_usuario'] = $tipo_perfil;
            }
            
            if (isset($userProfile[0]['tipo_perfil'])) {
                $updateData['tipo_perfil'] = $tipo_perfil;
            }
            
            if (empty($updateData)) {
                $updateData['tipo_perfil'] = $tipo_perfil; // Añadir campo nuevo si no existe ninguno
            }
            
            // Actualizar el perfil
            $updateResponse = supabaseUpdate('perfiles', $updateData, ['user_id' => $user_id]);
            
            if (isset($updateResponse['error'])) {
                $resultadoPerfil .= "\nError al actualizar el perfil: " . print_r($updateResponse['error'], true);
            } else {
                $resultadoPerfil .= "\n¡Perfil actualizado correctamente!";
                $resultadoPerfil .= "\nCampos actualizados: " . json_encode($updateData);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Autenticación - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: #1a73e8;
            margin-bottom: 30px;
        }
        h2 {
            margin-top: 30px;
            color: #1a73e8;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #1a73e8;
            padding: 15px;
            margin: 15px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .error-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        .log-info { color: #1a73e8; }
        .log-success { color: #28a745; }
        .log-warning { color: #ffc107; }
        .log-error { color: #dc3545; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .data-table th, .data-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f2f2f2;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0d47a1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Autenticación - ChambaNet</h1>
        
        <div class="card">
            <h2>Información de Sesión Actual</h2>
            <?php if (isset($_SESSION['access_token'])): ?>
                <div class="success-box">
                    <strong>Usuario autenticado</strong>
                </div>
                
                <h3>Detalles del Usuario</h3>
                <table>
                    <tr>
                        <th>ID de Usuario</th>
                        <td><?php echo isset($_SESSION['user']['id']) ? htmlspecialchars($_SESSION['user']['id']) : 'No disponible'; ?></td>
                    </tr>
                    <tr>
                        <th>Correo</th>
                        <td><?php echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : 'No disponible'; ?></td>
                    </tr>
                    <tr>
                        <th>Tipo de Usuario</th>
                        <td><?php echo isset($_SESSION['tipo_usuario']) ? htmlspecialchars($_SESSION['tipo_usuario']) : 'No establecido'; ?></td>
                    </tr>
                </table>
                
                <?php if (!isset($_SESSION['tipo_usuario'])): ?>
                    <div class="error-box">
                        <strong>Error:</strong> No se ha establecido el tipo de usuario en la sesión.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="warning-box">
                    <strong>Usuario no autenticado</strong> - No hay una sesión activa.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Verificar Usuario por Email</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="verificar_usuario">Verificar Usuario</button>
            </form>
            
            <?php if ($resultado): ?>
                <div class="info-box">
                    <h3>Resultado:</h3>
                    <pre><?php echo htmlspecialchars($resultado); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Actualizar Tipo de Perfil</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="user_id">ID de Usuario:</label>
                    <input type="text" id="user_id" name="user_id" required>
                </div>
                <div class="form-group">
                    <label for="tipo_perfil">Tipo de Perfil:</label>
                    <select id="tipo_perfil" name="tipo_perfil" required>
                        <option value="administrador">Administrador</option>
                        <option value="candidato">Candidato</option>
                        <option value="reclutador">Reclutador</option>
                    </select>
                </div>
                <button type="submit" name="actualizar_perfil">Actualizar Perfil</button>
            </form>
            
            <?php if ($resultadoPerfil): ?>
                <div class="info-box">
                    <h3>Resultado:</h3>
                    <pre><?php echo htmlspecialchars($resultadoPerfil); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Detectar Usuarios Sin Perfil</h2>
            <p>Esta herramienta detecta usuarios que existen en el sistema de autenticación pero no tienen un perfil correspondiente en la tabla 'perfiles'.</p>
            
            <form method="POST">
                <button type="submit" name="detectar_sin_perfil">Detectar Usuarios Sin Perfil</button>
            </form>
            
            <?php if (!empty($usuariosSinPerfil)): ?>
                <div class="warning-box">
                    <h3>Usuarios sin perfil encontrados: <?php echo count($usuariosSinPerfil); ?></h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Metadatos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosSinPerfil as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <pre><?php echo htmlspecialchars(json_encode($usuario['user_metadata'] ?? [], JSON_PRETTY_PRINT)); ?></pre>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="usuarios_json" value="<?php echo htmlspecialchars(json_encode($usuariosSinPerfil)); ?>">
                        <button type="submit" name="crear_perfiles">Crear Perfiles Faltantes</button>
                    </form>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['detectar_sin_perfil'])): ?>
                <div class="success-box">
                    <h3>Resultado:</h3>
                    <p><?php echo htmlspecialchars($resultado); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($logsMensaje)): ?>
                <div class="info-box">
                    <h3>Registro de actividad:</h3>
                    <ul>
                        <?php foreach ($logsMensaje as $log): ?>
                            <li class="log-<?php echo $log['tipo']; ?>">
                                <?php echo htmlspecialchars($log['mensaje']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Estructura de la Base de Datos</h2>
            <div class="info-box">
                <h3>Campos de tipo de usuario:</h3>
                <ul>
                    <li><strong>tipo_usuario</strong>: Campo antiguo (en migraciones)</li>
                    <li><strong>tipo_perfil</strong>: Campo nuevo (en la estructura actual)</li>
                </ul>
            </div>
            
            <h3>Valores esperados:</h3>
            <table>
                <tr>
                    <th>Valor</th>
                    <th>Descripción</th>
                    <th>Página de redirección</th>
                </tr>
                <tr>
                    <td>administrador</td>
                    <td>Usuario administrador</td>
                    <td>../paginas/admin/dashboard.php</td>
                </tr>
                <tr>
                    <td>candidato</td>
                    <td>Usuario candidato</td>
                    <td>../paginas/candidato/home_candidato.php</td>
                </tr>
                <tr>
                    <td>reclutador</td>
                    <td>Usuario reclutador</td>
                    <td>../paginas/empresa/home_empresa.php</td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>Enlaces Útiles</h2>
            <ul>
                <li><a href="../index.php">Volver a la página de inicio</a></li>
                <li><a href="../paginas/interfaz_iniciar_sesion.php">Página de inicio de sesión</a></li>
                <li><a href="../controllers/logout_controller.php">Cerrar sesión actual</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
