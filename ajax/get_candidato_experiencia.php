<?php
// Archivo para obtener la experiencia laboral de un candidato
session_start();
require_once '../config/supabase.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener ID del candidato
$candidatoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($candidatoId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de candidato no válido']);
    exit;
}

// Obtener experiencia laboral del candidato
$query = "/rest/v1/experiencia_laboral?select=id,empresa,puesto,fecha_inicio,fecha_fin,actual,descripcion&candidato_id=eq.$candidatoId&order=fecha_inicio.desc";
$experienciaLaboral = supabaseRequest($query);

if (isset($experienciaLaboral['error'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al obtener experiencia laboral']);
    exit;
}

// Devolver los datos
header('Content-Type: application/json');
echo json_encode(['experiencia' => $experienciaLaboral]);
