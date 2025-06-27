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

$empresa = $empresaData[0];
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Empresa</title>
  <link rel="stylesheet" href="../../estilo/perfil_empresa.css">
  <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
  <link rel="stylesheet" href="../../estilo/candidatos.css">

</head>
<body>
      <div class="sidebar">
        <div style="display: flex; flex-direction: column; align-items: center; margin-top: 32px;">
            <img src="../../imagenes/logo.png" alt="Logo de la empresa"
                style="width: 100px; height: 100px; margin-bottom: 32px;">
        </div>
        <ul class="nav-menu" style="margin-top: 16px;">
            <li><a href="home_empresa.php">Inicio</a></li>
            <li>
                <a href="nueva_vacante.php" class="active"
                    style="background: #e04a4a; color: #fff; border-radius: 4px;">Publicar Vacante</a>
            </li>
            <li><a href="mis_vacantes.php">Mis Vacantes</a></li>
            <li><a href="candidatos.php">Candidatos</a></li>
            <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
        </ul>
        <div style="flex: 1;"></div>
        <div style="margin: 32px 0 0 0; text-align: center;">
            <a href="../../controllers/logout_controller.php" style="color: #e04a4a; text-decoration: none;">Cerrar
                Sesión</a>
        </div>
    </div>
  <div class="contenedor_perfil">

   <div class="logo">
            <img src="../../imagenes/logo.png" alt="Logo">
    </div>
  
  <div class="recuadro_titulo">
    <h1>Perfil de la Empresa</h1>
  </div>

    <div class="campo">
      <strong>Nombre:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['nombre']) ?></span>
       <img src="<?= htmlspecialchars($empresa['logo_url']) ?>" class="logo" alt="Logo de la empresa">
      </div>
    </div>

    <div class="campo">
      <strong>RFC:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['rfc']) ?></span> 
      </div>
    </div>

    <div class="campo">
      <strong>Industria:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['industria']) ?></span>
      </div>
    </div>

    <div class="campo">
      <strong>Dirección:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['direccion']) ?></span>
      </div>
    </div>

    <div class="campo">
      <strong>Teléfono:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['telefono']) ?></span>
      </div>
    </div>

    <div class="campo">
      <strong>Sitio web:</strong>
      <div class="recuadro">
      <a class="valor" href="<?= htmlspecialchars($empresa['sitio_web']) ?>" target="_blank">
        <?= htmlspecialchars($empresa['sitio_web']) ?>
      </a>
      </div>
    </div>

    <div class="campo">
      <strong>Descripción:</strong>
      <div class="recuadro">
      <span class="valor"><?= htmlspecialchars($empresa['descripcion']) ?></span>
      </div>
    </div>

    <div class="campo">
      <strong>Fecha de creación:</strong>
      <div class="recuadro">
      <span class="valor"><?= date('d/m/Y', strtotime($empresa['fecha_creacion'])) ?></span>
      </div>
    </div>

    <div class="campo">
      <strong>Última actualización:</strong>
      <div class="recuadro">
      <span class="valor"><?= date('d/m/Y H:i', strtotime($empresa['ultima_actualizacion'])) ?></span>
      </div>
      
  </div>
</body>
</html>
