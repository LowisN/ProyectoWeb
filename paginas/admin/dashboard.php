<?php
session_start();
require_once '../../config/supabase.php';
require_once '../../helpers/auth_helper.php';

// Verificar si el usuario está autenticado y es un administrador
verificarAcceso(['administrador', 'admin'], '../interfaz_iniciar_sesion.php');

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil');
    exit;
}

// Obtener datos del usuario
$userData = supabaseFetch('usuario', '*', ['user_id' => $userId]);

if (empty($userData) || isset($userData['error'])) {
    $nombre = "Administrador";
} else {
    $nombre = $userData[0]['nombre'] . ' ' . $userData[0]['apellido_paterno'];
}

// Obtener estadísticas generales (ejemplo)
$candidatosCount = count(supabaseFetch('candidatos', 'id'));
$empresasCount = count(supabaseFetch('empresas', 'id'));
$vacantesCount = count(supabaseFetch('vacantes', 'id'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #1a73e8;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .admin-tools {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .tool-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .tool-card h3 {
            margin-top: 0;
            color: #1a73e8;
        }
        
        .tool-card ul {
            padding-left: 20px;
        }
        
        .tool-card li {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="user-info">
                <div class="avatar">A</div>
                <div class="name"><?php echo htmlspecialchars($nombre); ?></div>
                <div class="role">Administrador</div>
            </div>
            
            <div class="menu">
                <a href="#" class="active">Dashboard</a>
                <a href="../../admin/diagnostico_auth.php">Diagnóstico de Autenticación</a>
                <a href="crear_administrador.php">Crear Administrador</a>
                <a href="#">Gestión de Empresas</a>
                <a href="#">Gestión de Vacantes</a>
                <a href="#">Gestión de Usuarios</a>
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="main-content">
            <h1>Panel de Administración</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Candidatos</h3>
                    <div class="number"><?php echo $candidatosCount; ?></div>
                    <p>Usuarios registrados</p>
                </div>
                
                <div class="stat-card">
                    <h3>Empresas</h3>
                    <div class="number"><?php echo $empresasCount; ?></div>
                    <p>Registradas en la plataforma</p>
                </div>
                
                <div class="stat-card">
                    <h3>Vacantes</h3>
                    <div class="number"><?php echo $vacantesCount; ?></div>
                    <p>Publicadas actualmente</p>
                </div>
            </div>
            
            <div class="admin-tools">
                <div class="tool-card">
                    <h3>Herramientas de Administración</h3>
                    <ul>
                        <li><a href="../../admin/diagnostico_auth.php">Diagnóstico de Autenticación</a></li>
                        <li><a href="crear_administrador.php">Crear Nuevo Administrador</a></li>
                        <li><a href="#">Gestionar Catálogos de Conocimientos</a></li>
                        <li><a href="#">Gestionar Permisos de Usuario</a></li>
                    </ul>
                </div>
                
                <div class="tool-card">
                    <h3>Reportes</h3>
                    <ul>
                        <li><a href="#">Postulaciones por Vacante</a></li>
                        <li><a href="#">Empresas más Activas</a></li>
                        <li><a href="#">Conocimientos más Demandados</a></li>
                        <li><a href="#">Usuarios Registrados por Mes</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
