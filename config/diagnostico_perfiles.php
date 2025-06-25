<?php
/**
 * Herramienta de diagnóstico y corrección para perfiles de usuario
 * 
 * Esta herramienta verifica y corrige problemas comunes en los perfiles de usuarios
 * que pueden causar errores de "estructura de datos inválida"
 */

// Iniciar sesión y configurar encabezados
session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once 'supabase.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    // Permitir ejecución en modo debug si se proporciona un parámetro especial
    $debugKey = "chambanetdiag2024";
    if (!isset($_GET['debug']) || $_GET['debug'] !== $debugKey) {
        die("Acceso denegado. Esta herramienta solo es accesible para administradores.");
    }
}

// Variables para almacenar resultados
$perfilesTotal = 0;
$perfilesCorregidos = 0;
$perfilesConProblemas = [];
$errores = [];
$logs = [];

// Función para registrar mensajes
function logMensaje($mensaje, $tipo = 'info') {
    global $logs;
    $logs[] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    error_log("[Diagnóstico Perfiles] - $tipo: $mensaje");
}

// 1. Comprobar conexión con Supabase
try {
    $prueba = supabaseRequest('/rest/v1/perfiles?select=count');
    if (isset($prueba['error'])) {
        logMensaje("Error de conexión con Supabase: " . $prueba['error'], 'error');
    } else {
        logMensaje("Conexión con Supabase establecida correctamente", 'success');
    }
} catch (Exception $e) {
    logMensaje("Excepción al conectar con Supabase: " . $e->getMessage(), 'error');
}

// 2. Comprobar existencia y estructura de la tabla perfiles
try {
    $perfiles = supabaseRequest('/rest/v1/perfiles?select=*&limit=100');
    
    if (isset($perfiles['error'])) {
        logMensaje("Error al obtener perfiles: " . $perfiles['error'], 'error');
    } else {
        $perfilesTotal = count($perfiles);
        logMensaje("Se encontraron $perfilesTotal perfiles", 'info');
        
        // Verificar estructura de los perfiles
        foreach ($perfiles as $i => $perfil) {
            $problemas = [];
            
            // Verificar campos requeridos
            $camposRequeridos = ['id', 'user_id'];
            foreach ($camposRequeridos as $campo) {
                if (!isset($perfil[$campo]) || empty($perfil[$campo])) {
                    $problemas[] = "Falta el campo '$campo' o está vacío";
                }
            }
            
            // Verificar tipo_usuario y tipo_perfil
            if ((!isset($perfil['tipo_usuario']) || empty($perfil['tipo_usuario'])) && 
                (!isset($perfil['tipo_perfil']) || empty($perfil['tipo_perfil']))) {
                $problemas[] = "Falta tanto 'tipo_usuario' como 'tipo_perfil'";
            }
            
            // Si tiene problemas, agregarlo a la lista
            if (!empty($problemas)) {
                $perfilesConProblemas[] = [
                    'id' => $perfil['id'] ?? 'Desconocido',
                    'user_id' => $perfil['user_id'] ?? 'Desconocido',
                    'problemas' => $problemas
                ];
            }
        }
        
        logMensaje("Se encontraron " . count($perfilesConProblemas) . " perfiles con problemas", 'warning');
    }
} catch (Exception $e) {
    logMensaje("Excepción al analizar perfiles: " . $e->getMessage(), 'error');
}

// 3. Intentar corregir los perfiles problemáticos si se ha solicitado
if (isset($_POST['corregir']) && $_POST['corregir'] === 'true') {
    logMensaje("Iniciando proceso de corrección de perfiles", 'info');
    
    foreach ($perfilesConProblemas as $perfil) {
        $idPerfil = $perfil['id'];
        if ($idPerfil === 'Desconocido') {
            logMensaje("No se puede corregir un perfil sin ID", 'error');
            continue;
        }
        
        // Determinar el tipo de usuario basado en otras tablas
        $tipoUsuario = null;
        $userId = $perfil['user_id'];
        
        // Verificar si es candidato
        $candidatoCheck = supabaseFetch('candidatos', 'id', ['perfil_id' => $idPerfil]);
        if (!empty($candidatoCheck) && !isset($candidatoCheck['error'])) {
            $tipoUsuario = 'candidato';
        } else {
            // Verificar si es reclutador/empresa
            $reclutadorCheck = supabaseFetch('reclutadores', 'id', ['perfil_id' => $idPerfil]);
            if (!empty($reclutadorCheck) && !isset($reclutadorCheck['error'])) {
                $tipoUsuario = 'reclutador';
            } else {
                // Por defecto, asumir que es candidato
                $tipoUsuario = 'candidato';
            }
        }
        
        if ($tipoUsuario) {
            // Actualizar el perfil
            $resultado = supabaseUpdate('perfiles', [
                'tipo_perfil' => $tipoUsuario,
                'tipo_usuario' => $tipoUsuario
            ], ['id' => $idPerfil]);
            
            if (isset($resultado['error'])) {
                logMensaje("Error al actualizar perfil $idPerfil: " . $resultado['error'], 'error');
            } else {
                $perfilesCorregidos++;
                logMensaje("Perfil $idPerfil corregido correctamente como $tipoUsuario", 'success');
            }
        }
    }
    
    logMensaje("Proceso de corrección completado. Se corrigieron $perfilesCorregidos perfiles.", 'success');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Perfiles - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d63d3d;
            margin-top: 0;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .log-item {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .log-info { color: #0099cc; }
        .log-success { color: #28a745; }
        .log-warning { color: #ffc107; }
        .log-error { color: #dc3545; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .button {
            background-color: #d63d3d;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #c72c2c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Perfiles - ChambaNet</h1>
        
        <div class="section">
            <h2>Resumen</h2>
            <p>Total de perfiles analizados: <?php echo $perfilesTotal; ?></p>
            <p>Perfiles con problemas: <?php echo count($perfilesConProblemas); ?></p>
            <p>Perfiles corregidos: <?php echo $perfilesCorregidos; ?></p>
        </div>
        
        <?php if (!empty($perfilesConProblemas)): ?>
        <div class="section">
            <h2>Perfiles Problemáticos</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Problemas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perfilesConProblemas as $perfil): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($perfil['id']); ?></td>
                        <td><?php echo htmlspecialchars($perfil['user_id']); ?></td>
                        <td>
                            <ul>
                                <?php foreach ($perfil['problemas'] as $problema): ?>
                                <li><?php echo htmlspecialchars($problema); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <form method="post" style="margin-top: 20px;">
                <input type="hidden" name="corregir" value="true">
                <button class="button" type="submit">Corregir Perfiles Problemáticos</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Registro de Actividades</h2>
            <div class="log-container">
                <?php foreach ($logs as $log): ?>
                <div class="log-item log-<?php echo $log['tipo']; ?>">
                    <?php echo htmlspecialchars($log['mensaje']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="../paginas/interfaz_iniciar_sesion.php" style="text-decoration: none;">
                <button class="button" type="button">Volver a Inicio de Sesión</button>
            </a>
        </div>
    </div>
</body>
</html>
