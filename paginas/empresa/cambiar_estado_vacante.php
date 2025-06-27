<?php
session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
    header('Location: ../../index.php');
    exit;
}

// Verificar que se proporcionen los parámetros necesarios
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['estado']) || empty($_GET['estado'])) {
    header('Location: mis_vacantes.php?error=Parámetros insuficientes');
    exit;
}

$vacanteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$nuevoEstado = filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_STRING);

// Validar ID de vacante
if (!$vacanteId) {
    header('Location: mis_vacantes.php?error=ID de vacante inválido');
    exit;
}

// Validar estado
$estadosPermitidos = ['activa', 'pausada', 'cerrada'];
if (!in_array($nuevoEstado, $estadosPermitidos)) {
    header('Location: mis_vacantes.php?error=Estado de vacante inválido');
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

// Obtener datos de la vacante
$vacante = supabaseFetch('vacantes', '*', ['id' => $vacanteId]);

if (empty($vacante) || isset($vacante['error'])) {
    header('Location: mis_vacantes.php?error=Vacante no encontrada');
    exit;
}

// Verificar que la vacante pertenezca a la empresa del reclutador
if ($vacante[0]['empresa_id'] != $empresaData[0]['id']) {
    header('Location: mis_vacantes.php?error=No tienes acceso a esta vacante');
    exit;
}

// Actualizar el estado de la vacante
$updateData = [
    'estado' => $nuevoEstado,
    'ultima_actualizacion' => date('Y-m-d H:i:s')
];

$updateResult = supabaseUpdate('vacantes', $updateData, ['id' => $vacanteId]);

if (isset($updateResult['error'])) {
    header('Location: detalle_vacante.php?id=' . $vacanteId . '&error=Error al actualizar el estado de la vacante');
    exit;
}

// Determinar el mensaje según el estado
$mensajeEstado = '';
switch ($nuevoEstado) {
    case 'activa':
        $mensajeEstado = 'La vacante ha sido activada correctamente.';
        break;
    case 'pausada':
        $mensajeEstado = 'La vacante ha sido pausada correctamente.';
        break;
    case 'cerrada':
        $mensajeEstado = 'La vacante ha sido cerrada correctamente.';
        break;
}

header('Location: detalle_vacante.php?id=' . $vacanteId . '&success=' . urlencode($mensajeEstado));
exit;
?>
