<?php
session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
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

// Obtener datos del reclutador
$reclutadorData = supabaseFetch('reclutadores', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($reclutadorData) || isset($reclutadorData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del reclutador');
    exit;
}

// Obtener datos de la empresa
$empresaData = supabaseFetch('empresas', '*', ['id' => $reclutadorData[0]['empresa_id']]);

if (empty($empresaData) || isset($empresaData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos de la empresa');
    exit;
}



?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal de Empresa - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes.css">
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="company-info">
                <img src="../../imagenes/logo.png" alt="Logo de la empresa">
                <h3><?php echo htmlspecialchars($empresaData[0]['nombre']); ?></h3>
                <p><?php echo htmlspecialchars($reclutadorData[0]['nombre'] . ' ' . $reclutadorData[0]['apellidos']); ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" class="active">Inicio</a></li>
                <li><a href="nueva_vacante.php">Publicar Vacante</a></li>
                <li><a href="mis_vacantes.php">Mis Vacantes</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content">
            <div class="welcome-banner">
                <div>
                    <h2 id="titulo">¡Bienvenido al Portal de Empresa!</h2>
                    <p>Gestiona tus vacantes y candidatos desde aquí.</p>
                </div>
                <a href="nueva_vacante.php" class="btn-nueva-vacante">Nueva Vacante</a>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?php echo !empty($vacantes) && !isset($vacantes['error']) ? count($vacantes) : 0; ?></div>
                    <p>Vacantes Activas</p>
                </div>
                <div class="stat-card">
                    <div class="number">0</div>
                    <p>Candidatos Pendientes</p>
                </div>
                <div class="stat-card">
                    <div class="number">0</div>
                    <p>Contrataciones</p>
                </div>
            </div>
            
            <h2 class="vacantes_r">Vacantes Recientes</h2>
            
            <div class="vacantes">
                <?php if (!empty($vacantes) && !isset($vacantes['error'])): ?>
                    <?php foreach ($vacantes as $vacante): ?>
                        <div class="vacante-card">
                            <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($vacante['descripcion'], 0, 100)) . '...'; ?></p>
                            <div class="detalles">
                                <span>Salario: $<?php echo number_format($vacante['salario'], 2); ?></span>
                                <span>Candidatos: <?php echo $vacante['candidatos_count'] ?? 0; ?></span>
                            </div>
                            <div class="acciones">
                                <a href="ver_candidatos.php?vacante_id=<?php echo $vacante['id']; ?>">Ver Candidatos</a>
                                <a href="editar_vacante.php?id=<?php echo $vacante['id']; ?>">Editar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No has publicado vacantes aún. ¡Comienza creando tu primera vacante!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
