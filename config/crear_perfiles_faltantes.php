<?php
/**
 * Script para verificar y sincronizar usuarios con perfiles faltantes
 * Este script identifica usuarios autenticados en Supabase Auth que no tienen
 * un perfil correspondiente en la tabla perfiles y los crea automáticamente
 */

session_start();
require_once '../config/supabase.php';

// Verificar si el usuario tiene permiso para ejecutar esta herramienta
// Por seguridad, se permite el acceso solo con un parámetro específico o a administradores
$debugKey = "chambanetfix2024";
if (
    (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') && 
    (!isset($_GET['debug']) || $_GET['debug'] !== $debugKey)
) {
    die("Acceso denegado. Esta herramienta solo es accesible para administradores o con el código de acceso correcto.");
}

// Variables para resultados
$usuarios = [];
$perfiles = [];
$usuariosSinPerfil = [];
$perfilesCreados = 0;
$logs = [];

// Función para registrar mensajes
function logMensaje($mensaje, $tipo = 'info') {
    global $logs;
    $logs[] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    error_log("[Creador Perfiles] - $tipo: $mensaje");
}

// Función para crear un perfil para un usuario
function crearPerfil($usuario) {
    global $perfilesCreados;
    
    // Determinar el tipo de usuario basado en metadatos
    $tipoUsuario = 'candidato'; // Por defecto asumimos que es candidato
    
    // Si tiene metadatos que indican que es de una empresa, marcarlo como reclutador
    if (isset($usuario['user_metadata']['empresa']) && !empty($usuario['user_metadata']['empresa'])) {
        $tipoUsuario = 'reclutador';
    }
    
    // Datos para el perfil
    $perfilData = [
        'user_id' => $usuario['id'],
        'email' => $usuario['email'],
        'tipo_usuario' => $tipoUsuario,
        'tipo_perfil' => $tipoUsuario
    ];
    
    // Insertar el perfil
    $resultado = supabaseInsert('perfiles', $perfilData);
    
    if (isset($resultado['error'])) {
        logMensaje("Error al crear perfil para {$usuario['email']}: " . $resultado['error'], 'error');
        return false;
    }
    
    $perfilesCreados++;
    logMensaje("Perfil creado correctamente para {$usuario['email']} como $tipoUsuario", 'success');
    
    // Si el perfil fue creado correctamente, obtener su ID
    if (!isset($resultado[0]['id'])) {
        logMensaje("No se pudo obtener el ID del perfil recién creado", 'warning');
        return true;
    }
    
    $perfilId = $resultado[0]['id'];
    
    // Si es candidato, crear entrada en tabla candidatos
    if ($tipoUsuario === 'candidato') {
        $candidatoData = [
            'perfil_id' => $perfilId,
            'estado' => 'activo',
            'fecha_registro' => date('Y-m-d H:i:s')
        ];
        
        $resultadoCandidato = supabaseInsert('candidatos', $candidatoData);
        
        if (isset($resultadoCandidato['error'])) {
            logMensaje("Error al crear candidato para perfil $perfilId: " . $resultadoCandidato['error'], 'warning');
        } else {
            logMensaje("Registro de candidato creado correctamente para perfil $perfilId", 'success');
        }
    } 
    // Si es reclutador y hay información de empresa, intentar vincular
    else if ($tipoUsuario === 'reclutador' && isset($usuario['user_metadata']['empresa'])) {
        // Buscar si existe la empresa por nombre
        $nombreEmpresa = $usuario['user_metadata']['empresa'];
        $empresas = supabaseFetch('empresas', '*', ['nombre' => $nombreEmpresa]);
        
        if (!empty($empresas) && !isset($empresas['error']) && isset($empresas[0]['id'])) {
            // Encontramos la empresa, crear el reclutador
            $reclutadorData = [
                'perfil_id' => $perfilId,
                'empresa_id' => $empresas[0]['id'],
                'nombre' => $usuario['user_metadata']['nombre'] ?? '',
                'apellidos' => $usuario['user_metadata']['apellidos'] ?? '',
                'email' => $usuario['email']
            ];
            
            $resultadoReclutador = supabaseInsert('reclutadores', $reclutadorData);
            
            if (isset($resultadoReclutador['error'])) {
                logMensaje("Error al crear reclutador para perfil $perfilId: " . $resultadoReclutador['error'], 'warning');
            } else {
                logMensaje("Registro de reclutador creado y vinculado a empresa {$empresas[0]['nombre']}", 'success');
            }
        } else {
            // Crear una empresa básica
            $empresaData = [
                'nombre' => $nombreEmpresa,
                'fecha_registro' => date('Y-m-d')
            ];
            
            $resultadoEmpresa = supabaseInsert('empresas', $empresaData);
            
            if (isset($resultadoEmpresa['error'])) {
                logMensaje("Error al crear empresa $nombreEmpresa: " . $resultadoEmpresa['error'], 'warning');
            } else {
                $empresaId = $resultadoEmpresa[0]['id'];
                logMensaje("Empresa básica creada con ID $empresaId", 'success');
                
                // Ahora crear el reclutador
                $reclutadorData = [
                    'perfil_id' => $perfilId,
                    'empresa_id' => $empresaId,
                    'nombre' => $usuario['user_metadata']['nombre'] ?? '',
                    'apellidos' => $usuario['user_metadata']['apellidos'] ?? '',
                    'email' => $usuario['email']
                ];
                
                $resultadoReclutador = supabaseInsert('reclutadores', $reclutadorData);
                
                if (isset($resultadoReclutador['error'])) {
                    logMensaje("Error al crear reclutador para perfil $perfilId: " . $resultadoReclutador['error'], 'warning');
                } else {
                    logMensaje("Registro de reclutador creado y vinculado a nueva empresa $nombreEmpresa", 'success');
                }
            }
        }
    }
    
    return true;
}

// Procesar formularios
$accion = "";
$resultadoAccion = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verificar_usuarios'])) {
        $accion = "verificar";
    } else if (isset($_POST['crear_perfiles']) && isset($_POST['usuarios_seleccionados'])) {
        $accion = "crear";
        $usuariosSeleccionados = json_decode($_POST['usuarios_seleccionados'], true);
    } else if (isset($_POST['crear_perfil_email'])) {
        $accion = "crear_individual";
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    }
}

// Ejecutar la acción seleccionada
if ($accion === "verificar" || $accion === "crear") {
    try {
        // Obtener todos los usuarios de Supabase Auth
        $queryUsers = "SELECT id, email, raw_user_meta_data FROM auth.users;";
        $usuarios = supabaseFetchRaw($queryUsers);
        
        if (isset($usuarios['error'])) {
            throw new Exception("Error al obtener usuarios: " . print_r($usuarios['error'], true));
        }
        
        // Formatear los usuarios para trabajar mejor con ellos
        $usuariosFormateados = [];
        foreach ($usuarios as $usuario) {
            $metadatos = json_decode($usuario['raw_user_meta_data'], true);
            $usuario['user_metadata'] = $metadatos;
            $usuariosFormateados[] = $usuario;
        }
        $usuarios = $usuariosFormateados;
        
        logMensaje("Se encontraron " . count($usuarios) . " usuarios registrados", 'info');
        
        // Obtener todos los perfiles
        $perfiles = supabaseFetch('perfiles', '*');
        
        if (isset($perfiles['error'])) {
            throw new Exception("Error al obtener perfiles: " . print_r($perfiles['error'], true));
        }
        
        if (empty($perfiles)) {
            $perfiles = [];
        }
        
        logMensaje("Se encontraron " . count($perfiles) . " perfiles existentes", 'info');
        
        // Crear un mapa de user_id => perfil para búsqueda rápida
        $mapaPerfiles = [];
        foreach ($perfiles as $perfil) {
            if (isset($perfil['user_id'])) {
                $mapaPerfiles[$perfil['user_id']] = $perfil;
            }
        }
        
        // Identificar usuarios sin perfil
        foreach ($usuarios as $usuario) {
            if (!isset($mapaPerfiles[$usuario['id']])) {
                $usuariosSinPerfil[] = $usuario;
            }
        }
        
        logMensaje("Se encontraron " . count($usuariosSinPerfil) . " usuarios sin perfil", 'info');
        
        // Si es acción de crear y hay usuarios sin perfil, proceder con la creación
        if ($accion === "crear" && !empty($usuariosSinPerfil) && !empty($usuariosSeleccionados)) {
            foreach ($usuariosSinPerfil as $usuario) {
                if (in_array($usuario['id'], $usuariosSeleccionados)) {
                    crearPerfil($usuario);
                }
            }
            
            $resultadoAccion = "Se crearon $perfilesCreados perfiles correctamente";
        }
        
    } catch (Exception $e) {
        $resultadoAccion = "Error: " . $e->getMessage();
        logMensaje($e->getMessage(), 'error');
    }
} 
// Crear un perfil para un usuario individual por email
else if ($accion === "crear_individual" && isset($email)) {
    try {
        // Buscar el usuario por email en auth.users
        $queryUser = "SELECT id, email, raw_user_meta_data FROM auth.users WHERE email = '$email';";
        $usuarioResult = supabaseFetchRaw($queryUser);
        
        if (isset($usuarioResult['error'])) {
            throw new Exception("Error al buscar el usuario: " . print_r($usuarioResult['error'], true));
        }
        
        if (empty($usuarioResult)) {
            throw new Exception("No se encontró ningún usuario con el email: $email");
        }
        
        // Obtener el primer resultado
        $usuario = $usuarioResult[0];
        $usuario['user_metadata'] = json_decode($usuario['raw_user_meta_data'], true);
        
        // Verificar si ya tiene perfil
        $perfilExistente = supabaseFetch('perfiles', '*', ['user_id' => $usuario['id']]);
        if (!empty($perfilExistente) && !isset($perfilExistente['error'])) {
            throw new Exception("El usuario $email ya tiene un perfil asociado");
        }
        
        // Crear el perfil
        $exito = crearPerfil($usuario);
        
        if ($exito) {
            $resultadoAccion = "Se creó correctamente el perfil para el usuario $email";
        } else {
            throw new Exception("No se pudo crear el perfil para el usuario $email");
        }
        
    } catch (Exception $e) {
        $resultadoAccion = "Error: " . $e->getMessage();
        logMensaje($e->getMessage(), 'error');
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creador de Perfiles - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #d63d3d;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        button, input[type="submit"] {
            background-color: #d63d3d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        button:hover, input[type="submit"]:hover {
            background-color: #c72c2c;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .log-container {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
            margin-top: 20px;
        }
        
        .log-item {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .log-info { color: #0099cc; }
        .log-success { color: #28a745; }
        .log-warning { color: #ffc107; }
        .log-error { color: #dc3545; }
        
        .result-message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .result-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Creador de Perfiles - ChambaNet</h1>
        
        <?php if (!empty($resultadoAccion)): ?>
            <div class="result-message <?php echo strpos($resultadoAccion, 'Error') === 0 ? 'result-error' : 'result-success'; ?>">
                <?php echo htmlspecialchars($resultadoAccion); ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Verificar usuarios sin perfil</h2>
            <p>Esta herramienta detectará usuarios registrados que no tienen un perfil asociado en la base de datos.</p>
            
            <form method="post">
                <input type="hidden" name="verificar_usuarios" value="1">
                <button type="submit">Verificar Usuarios Sin Perfil</button>
            </form>
        </div>
        
        <div class="section">
            <h2>Crear perfil para un email específico</h2>
            <p>Si conoces el correo electrónico de un usuario que necesita un perfil, puedes crearlo directamente aquí.</p>
            
            <form method="post">
                <div>
                    <label for="email">Email del usuario:</label>
                    <input type="email" id="email" name="email" required style="padding: 8px; width: 300px; margin-right: 10px;">
                    <input type="hidden" name="crear_perfil_email" value="1">
                    <button type="submit">Crear Perfil</button>
                </div>
            </form>
        </div>
        
        <?php if (!empty($usuariosSinPerfil)): ?>
            <div class="section">
                <h2>Usuarios sin perfil</h2>
                <p>Se encontraron <?php echo count($usuariosSinPerfil); ?> usuarios sin un perfil asociado.</p>
                
                <form method="post" id="createProfilesForm">
                    <input type="hidden" name="crear_perfiles" value="1">
                    <input type="hidden" name="usuarios_seleccionados" id="usuarios_seleccionados" value="">
                    
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>Empresa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosSinPerfil as $usuario): ?>
                                <tr>
                                    <td><input type="checkbox" class="user-checkbox" value="<?php echo htmlspecialchars($usuario['id']); ?>"></td>
                                    <td><?php echo htmlspecialchars(substr($usuario['id'], 0, 8) . '...'); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['user_metadata']['nombre'] ?? '') . ' ' . htmlspecialchars($usuario['user_metadata']['apellidos'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['user_metadata']['empresa'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 15px;">
                        <button type="submit" id="createProfilesButton">Crear Perfiles Seleccionados</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($logs)): ?>
            <div class="section">
                <h2>Registro de actividad</h2>
                <div class="log-container">
                    <?php foreach ($logs as $log): ?>
                        <div class="log-item log-<?php echo $log['tipo']; ?>">
                            <?php echo htmlspecialchars($log['mensaje']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="../paginas/interfaz_iniciar_sesion.php" style="text-decoration: none;">
                <button type="button">Volver a Inicio de Sesión</button>
            </a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionar/deseleccionar todos
            var selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    var checkboxes = document.querySelectorAll('.user-checkbox');
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = selectAll.checked;
                    });
                });
            }
            
            // Preparar el formulario para envío
            var createProfilesForm = document.getElementById('createProfilesForm');
            if (createProfilesForm) {
                createProfilesForm.addEventListener('submit', function(e) {
                    var checkboxes = document.querySelectorAll('.user-checkbox:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('Por favor, selecciona al menos un usuario.');
                        return false;
                    }
                    
                    var selectedUsers = [];
                    checkboxes.forEach(function(checkbox) {
                        selectedUsers.push(checkbox.value);
                    });
                    
                    document.getElementById('usuarios_seleccionados').value = JSON.stringify(selectedUsers);
                });
            }
        });
    </script>
</body>
</html>
