<?php
// Archivo para obtener las habilidades de un candidato
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

// Función de utilidad para obtener un valor de un array de forma segura
function getValue($array, $key, $default = '') {
    if (!is_array($array)) {
        return $default;
    }
    return isset($array[$key]) ? $array[$key] : $default;
}

// Obtener habilidades del candidato
$query = "/rest/v1/candidato_habilidades?select=id,habilidad_id,nivel,anios_experiencia,certificado_url&candidato_id=eq.$candidatoId";
$candidatoHabilidades = supabaseRequest($query);

if (isset($candidatoHabilidades['error'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al obtener habilidades: ' . json_encode($candidatoHabilidades['error'])]);
    exit;
}

// Procesar los resultados
$habilidadesDetalladas = [];

if (is_array($candidatoHabilidades) && !empty($candidatoHabilidades)) {
    foreach ($candidatoHabilidades as $habilidad) {
        $habilidadId = (int)getValue($habilidad, 'habilidad_id', 0);
        
        // Obtener detalles de la habilidad
        $habilidadDetalle = supabaseFetch('habilidades', '*', ['id' => $habilidadId]);
        
        if (is_array($habilidadDetalle) && !empty($habilidadDetalle)) {
            $nombreHabilidad = getValue($habilidadDetalle[0], 'nombre', 'Habilidad #' . $habilidadId);
            $categoriaHabilidad = getValue($habilidadDetalle[0], 'categoria', 'otros');
            
            // Crear objeto con detalles completos
            $habilidadesDetalladas[] = [
                'id' => $habilidadId,
                'nombre' => $nombreHabilidad,
                'categoria' => $categoriaHabilidad,
                'nivel' => getValue($habilidad, 'nivel', 'principiante'),
                'anios_experiencia' => (int)getValue($habilidad, 'anios_experiencia', 0),
                'certificado_url' => getValue($habilidad, 'certificado_url', null)
            ];
        }
    }
}

// Devolver los datos
header('Content-Type: application/json');
echo json_encode(['habilidades' => $habilidadesDetalladas]);
