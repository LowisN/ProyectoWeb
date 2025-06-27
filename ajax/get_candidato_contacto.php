<?php
// Archivo para obtener los datos de contacto de un candidato
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

// Obtener datos básicos del candidato
$candidatoData = supabaseFetch('candidatos', '*', ['id' => $candidatoId]);

if (empty($candidatoData) || isset($candidatoData['error'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se encontró el candidato']);
    exit;
}

// Extraer datos relevantes
$candidato = $candidatoData[0];

// Obtener información del perfil para conseguir el email
$perfilId = $candidato['perfil_id'] ?? 0;
$perfil = supabaseFetch('perfiles', '*', ['id' => $perfilId]);

$email = '';
if (!empty($perfil) && !isset($perfil['error'])) {
    $userId = $perfil[0]['user_id'] ?? '';
    if ($userId) {
        $userData = supabaseRequest("/auth/v1/user/" . $userId);
        if (isset($userData['email'])) {
            $email = $userData['email'];
        }
    }
}

// Construir respuesta
$respuesta = [
    'telefono' => $candidato['telefono'] ?? '',
    'email' => $email,
    'modalidad_preferida' => $candidato['modalidad_preferida'] ?? 'No especificada',
    'disponibilidad_viaje' => $candidato['disponibilidad_viaje'] ?? false,
    'disponibilidad_mudanza' => $candidato['disponibilidad_mudanza'] ?? false,
];

// Devolver los datos
header('Content-Type: application/json');
echo json_encode($respuesta);
