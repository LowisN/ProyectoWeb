<?php
session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
    header('Location: ../../index.php');
    exit;
}

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    header('Location: ../../index.php?error=Error al cargar el perfil');
    exit;
}

// Obtener datos del reclutador
$reclutadorData = supabaseFetch('reclutadores', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($reclutadorData) || isset($reclutadorData['error'])) {
    header('Location: ../../index.php?error=Error al cargar datos del reclutador');
    exit;
}

// Obtener datos de la empresa
$empresaData = supabaseFetch('empresas', '*', ['id' => $reclutadorData[0]['empresa_id']]);

if (empty($empresaData) || isset($empresaData['error'])) {
    header('Location: ../../index.php?error=Error al cargar datos de la empresa');
    exit;
}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Empresa</title>
  <link rel="stylesheet" href="../estilo/perfil_empresa.css">
  <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
</head>
<body>
  <div class="contenedor">

    <img src="<?= htmlspecialchars($empresa['logo_url']) ?>" class="logo" alt="Logo de la empresa">

    <h1>Perfil de la Empresa</h1>

    <div class="campo">
      <strong>Nombre:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['nombre']) ?></span>
    </div>

    <div class="campo">
      <strong>RFC:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['rfc']) ?></span>
    </div>

    <div class="campo">
      <strong>Industria:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['industria']) ?></span>
    </div>

    <div class="campo">
      <strong>Dirección:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['direccion']) ?></span>
    </div>

    <div class="campo">
      <strong>Teléfono:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['telefono']) ?></span>
    </div>

    <div class="campo">
      <strong>Sitio web:</strong>
      <a class="valor" href="<?= htmlspecialchars($empresa['sitio_web']) ?>" target="_blank">
        <?= htmlspecialchars($empresa['sitio_web']) ?>
      </a>
    </div>

    <div class="campo">
      <strong>Descripción:</strong>
      <span class="valor"><?= htmlspecialchars($empresa['descripcion']) ?></span>
    </div>

    <div class="campo">
      <strong>Fecha de creación:</strong>
      <span class="valor"><?= date('d/m/Y', strtotime($empresa['fecha_creacion'])) ?></span>
    </div>

    <div class="campo">
      <strong>Última actualización:</strong>
      <span class="valor"><?= date('d/m/Y H:i', strtotime($empresa['ultima_actualizacion'])) ?></span>
    </div>
  </div>
</body>
</html>



<!-- <!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Empresa</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
  <link rel="stylesheet" href="../../estilo/perfil_empresa.css">
</head>
<body>
  <div class="contenedor">
    <h1>Perfil de la Empresa</h1>
        <div class="logo">
        <img src="./../../imagenes/logo.png" alt="Logo de la Empresa">
        </div>
     

                <div class="campo">
                <strong>Nombre:</strong>
                <span class="valor">Soluciones XYZ S.A. de C.V.</span>
                </div>

                <div class="campo">
                <strong>Dirección:</strong>
                <span class="valor">Av. Reforma 1234, CDMX, México</span>
                </div>

                <div class="campo">
                <strong>Teléfono:</strong>
                <span class="valor">+52 55 1234 5678</span>
                </div>

                <div class="campo">
                <strong>Sitio web:</strong>
                <a class="valor" href="https://www.ejemplo.com" target="_blank">www.ejemplo.com</a>
                </div>

                <h2>Contacto del Reclutador</h2>

                <div class="campo">
                <strong>Nombre:</strong>
                <span class="valor">María López</span>
                </div>

                <div class="campo">
                <strong>Cargo:</strong>
                <span class="valor">Gerente de Recursos Humanos</span>
                </div>
    <button onclick="window.location.href='./../../index.php'">Volver al Inicio</button>
  </div>
</body>
</html> -->
