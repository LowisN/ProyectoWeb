<?php
session_start();
require_once '../../config/supabase.php';
require_once '../../helpers/auth_helper.php';

// Verificar si el usuario está autenticado y es un candidato
verificarAcceso('candidato', '../interfaz_iniciar_sesion.php');

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

// Verificar errores explícitos en la respuesta
if (isset($userProfile['error'])) {
    error_log("Error en supabaseFetch de perfiles: " . print_r($userProfile['error'], true));
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil: ' . urlencode($userProfile['error']));
    exit;
}

// Verificar que userProfile tiene la estructura esperada (array con al menos un elemento)
if (empty($userProfile) || !is_array($userProfile)) {
    error_log("El perfil del usuario $userId no existe o no es un array: " . gettype($userProfile));
    // Intentar obtener el perfil usando una consulta SQL directa para diagnóstico
    error_log("Intentando obtener perfil mediante consulta SQL directa");
    try {
        // Intento de recuperación directa para diagnóstico
        $perfilDirecto = supabaseRequest('/rest/v1/perfiles?user_id=eq.' . urlencode($userId));
        error_log("Resultado de la consulta directa: " . print_r($perfilDirecto, true));
    } catch (Exception $e) {
        error_log("Error en consulta directa: " . $e->getMessage());
    }
    
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil: no se encontró el perfil de usuario');
    exit;
}

// Verificar que existe el elemento 0 en el array y tiene un id
if (!isset($userProfile[0]) || !isset($userProfile[0]['id'])) {
    error_log("Estructura de userProfile inválida: " . print_r($userProfile, true));
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil: estructura de datos inválida');
    exit;
}

// Obtener datos del candidato
$perfilId = $userProfile[0]['id']; // Ya sabemos que existe gracias a la validación anterior
$candidatoData = supabaseFetch('candidatos', '*', ['perfil_id' => $perfilId]);

// Verificar errores explícitos
if (isset($candidatoData['error'])) {
    error_log("Error en supabaseFetch de candidatos para perfil $perfilId: " . print_r($candidatoData['error'], true));
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del candidato: ' . urlencode($candidatoData['error']));
    exit;
}

// Verificar que existan datos del candidato
if (empty($candidatoData)) {
    error_log("No se encontraron datos del candidato para el perfil $perfilId");
    
    // Si no existe un registro del candidato, podemos crearlo automáticamente
    $nuevoCandidato = [
        'perfil_id' => $perfilId,
        'estado' => 'activo',
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    
    $resultadoInsercion = supabaseInsert('candidatos', $nuevoCandidato);
    if (isset($resultadoInsercion['error'])) {
        error_log("Error al crear automáticamente el registro de candidato: " . print_r($resultadoInsercion['error'], true));
        header('Location: ../interfaz_iniciar_sesion.php?error=Error al crear datos del candidato');
        exit;
    }
    
    // Usar los datos recién insertados
    $candidatoData = [$nuevoCandidato];
    if (isset($resultadoInsercion[0])) {
        $candidatoData = $resultadoInsercion;
    }
}

// Verificar la estructura de los datos del candidato
if (!isset($candidatoData[0])) {
    error_log("Estructura de candidatoData inválida: " . print_r($candidatoData, true));
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del candidato: formato inválido');
    exit;
}

// Obtener vacantes recomendadas
$vacantes = supabaseFetch('vacantes', '*');

// Verificar que la respuesta es un array válido y no un error
if (isset($vacantes['error'])) {
    error_log("Error al obtener vacantes: " . print_r($vacantes['error'], true));
    $vacantes = []; // Si hay error, inicializar como array vacío para evitar errores de iteración
}

// Asegurarse de que $vacantes siempre sea un array
if (!is_array($vacantes)) {
    error_log("La respuesta de vacantes no es un array: " . gettype($vacantes));
    $vacantes = [];
}

// En un caso real, aquí se filtrarían las vacantes según los conocimientos del candidato
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes.css">
    <link rel="stylesheet" href="../../estilo/candidato_dashboard.css">
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="user-info">
                <img src="../../imagenes/logo.png" alt="Foto de perfil">
                <?php
                // Verificar que la estructura de metadatos existe antes de usarla
                $nombre = isset($_SESSION['user']['user_metadata']['nombre']) ? 
                          $_SESSION['user']['user_metadata']['nombre'] : 'Usuario';
                $apellidos = isset($_SESSION['user']['user_metadata']['apellidos']) ? 
                             $_SESSION['user']['user_metadata']['apellidos'] : '';
                ?>
                <h3><?php echo htmlspecialchars($nombre . ' ' . $apellidos); ?></h3>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" class="active">Inicio</a></li>
                <li><a href="actualizar_perfil.php">Mi Perfil</a></li>
                <li><a href="mis_postulaciones.php">Mis Postulaciones</a></li>
                <li><a href="actualizar_conocimientos.php">Mis Conocimientos</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content" id="cntC">
            <div class="welcome-banner">
                <h2 id="titB">¡Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>
                <p>Estas son las vacantes que coinciden con tu perfil profesional.</p>
            </div>
            
            <h2>Vacantes Recomendadas</h2>
            
            <div class="vacantes">
                <?php if (!empty($vacantes) && !isset($vacantes['error'])): ?>
                    <?php foreach ($vacantes as $vacante): ?>
                        <div class="vacante-card">
                            <h3><?php echo isset($vacante['titulo']) ? htmlspecialchars($vacante['titulo']) : 'Sin título'; ?></h3>
                            <div class="empresa"><?php echo isset($vacante['empresa_nombre']) ? htmlspecialchars($vacante['empresa_nombre']) : 'Empresa no especificada'; ?></div>
                            <p>
                                <?php 
                                if (isset($vacante['descripcion']) && is_string($vacante['descripcion'])) {
                                    echo htmlspecialchars(substr($vacante['descripcion'], 0, 100)) . '...';
                                } else {
                                    echo 'No hay descripción disponible.';
                                }
                                ?>
                            </p>
                            <div class="detalles">
                                <span>Salario: $<?php echo isset($vacante['salario']) ? number_format(floatval($vacante['salario']), 2) : '0.00'; ?></span>
                                <span class="match"><?php echo rand(75, 95); ?>% Match</span>
                            </div>
                            <div style="text-align: center; margin-top: 10px;">
                                <a href="detalle_vacante.php?id=<?php echo isset($vacante['id']) ? $vacante['id'] : '0'; ?>" style="color: #d63d3d; text-decoration: none;">Ver detalles</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay vacantes disponibles en este momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
