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
    <title>Publicar Vacante - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
    <link rel="stylesheet" href="../../estilo/perfil_empresa.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="company-info">
                <img src="../../imagenes/logo.png" alt="Logo de la empresa">
                <h3><?php echo isset($empresaData[0]['nombre']) ? htmlspecialchars($empresaData[0]['nombre']) : 'Empresa'; ?></h3>
                <p><?php 
                    echo htmlspecialchars($reclutadorData[0]['nombre'] . ' ' . $reclutadorData[0]['apellidos']);
                ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_empresa.php">Inicio</a></li>
                <li><a href="nueva_vacante.php">Publicar Vacante</a></li>
                <li><a href="mis_vacantes.php">Mis Vacantes</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="#"class="active">Perfil de Empresa</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content">
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
    </div>

    <script>
        // Script para el botón de scroll hacia arriba
        document.addEventListener('DOMContentLoaded', function() {
            var scrollBtn = document.getElementById('scroll-to-top');
            
            // Mostrar/ocultar el botón basado en la posición del scroll
            window.addEventListener('scroll', function() {
                if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                    scrollBtn.style.display = 'block';
                } else {
                    scrollBtn.style.display = 'none';
                }
            });
            
            // Scroll hacia arriba al hacer clic en el botón
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
