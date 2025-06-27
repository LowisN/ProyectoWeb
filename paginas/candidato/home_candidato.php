<?php

session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un candidato
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'candidato') {
    header('Location: ../interfaz_iniciar_sesion.php');
    exit;
}

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil');
    exit;
}

// Obtener datos del candidato
$candidatoData = supabaseFetch('candidatos', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($candidatoData) || isset($candidatoData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del candidato');
    exit;
}

// Obtener vacantes recomendadas
$vacantes = supabaseFetch('vacantes', '*');
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

<body class="sinMar">
    <div class="contenedor dashboard" id="iniCand">
        <div class="sidebar">
            <div class="user-info">
                <img src="../../imagenes/logo.png" alt="Foto de perfil">
                <h3><?php echo htmlspecialchars($_SESSION['user']['user_metadata']['nombre'] . ' ' . $_SESSION['user']['user_metadata']['apellidos']); ?></h3>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" class="active">Inicio</a></li>
                <li><a href="actualizar_perfil.php">Mi Perfil</a></li>
                <li><a href="actualizar_conocimientos.php">Mis Conocimientos</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content" id="cntC">
            <div class="welcome-banner">
                <h2 id="titB">¡Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['user_metadata']['nombre']); ?>!</h2>
                <p>Te deseamos mucho éxito en tu búsqueda en el ambiente laboral !</p>
            </div>
        </div>
    </div>
</body>
</html>
