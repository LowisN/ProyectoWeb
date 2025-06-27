<?php
/**
 * Script para probar la creación de vacantes con los campos correctos
 * luego de corregir el formulario de nueva_vacante.php
 */

// Incluir configuración
require_once 'config/supabase.php';

// Encabezado para visualización
header('Content-Type: text/plain');
echo "PRUEBA DE CREACIÓN DE VACANTE CON CAMPOS CORRECTOS\n";
echo "=================================================\n\n";

// Obtener empresa y reclutador para la prueba
$empresaData = supabaseFetch('empresas', '*', [], 1);
$reclutadorData = supabaseFetch('reclutadores', '*', [], 1);

if (!isset($empresaData[0]['id']) || !isset($reclutadorData[0]['id'])) {
    echo "ERROR: No se pudo obtener datos de empresa o reclutador para la simulación.\n";
    exit;
}

echo "OK: Usando empresa_id=" . $empresaData[0]['id'] . " y reclutador_id=" . $reclutadorData[0]['id'] . "\n\n";

// Datos de vacante usando solo los campos correctos
$vacanteData = [
    'empresa_id' => $empresaData[0]['id'],
    'reclutador_id' => $reclutadorData[0]['id'],
    'titulo' => 'Vacante de prueba corregida - ' . date('Y-m-d H:i:s'),
    'descripcion' => 'Esta es una vacante de prueba con campos corregidos',
    'responsabilidades' => 'Responsabilidades de prueba',
    'requisitos' => 'Requisitos de prueba',
    'salario' => 15000,
    'modalidad' => 'remoto',
    'ubicacion' => 'Ciudad de México',
    'anios_experiencia' => 2,
    'fecha_publicacion' => date('Y-m-d'),
    'estado' => 'activa',
    'destacada' => false
];

echo "DATOS DE VACANTE A INSERTAR:\n";
echo json_encode($vacanteData, JSON_PRETTY_PRINT) . "\n\n";

echo "REALIZANDO INSERCIÓN...\n";
$vacanteResponse = supabaseInsert('vacantes', $vacanteData);

if (isset($vacanteResponse['error'])) {
    echo "ERROR: No se pudo crear la vacante: " . $vacanteResponse['message'] . "\n";
    echo "Detalles: " . json_encode($vacanteResponse, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "OK: La vacante se creó exitosamente.\n";
    
    // Intentar obtener ID
    $vacanteId = null;
    
    if (is_array($vacanteResponse) && !empty($vacanteResponse) && isset($vacanteResponse[0]['id'])) {
        $vacanteId = $vacanteResponse[0]['id'];
        echo "ID de vacante obtenido: $vacanteId\n";
    } else if (isset($vacanteResponse['id'])) {
        $vacanteId = $vacanteResponse['id'];
        echo "ID de vacante obtenido: $vacanteId\n";
    } else {
        echo "No se pudo obtener ID directamente. Intentando consulta...\n";
        
        $consultaVacante = supabaseFetch('vacantes', '*', [
            'titulo' => $vacanteData['titulo'],
            'empresa_id' => $vacanteData['empresa_id']
        ]);
        
        if (is_array($consultaVacante) && !empty($consultaVacante) && isset($consultaVacante[0]['id'])) {
            $vacanteId = $consultaVacante[0]['id'];
            echo "ID de vacante recuperado por consulta: $vacanteId\n";
        }
    }
    
    if ($vacanteId) {
        echo "\nSIMULANDO INSERCIÓN DE HABILIDADES...\n";
        
        // Obtener una habilidad para la prueba
        $habilidades = supabaseFetch('habilidades', '*', [], 1);
        
        if (!empty($habilidades) && isset($habilidades[0]['id'])) {
            $habilidadId = $habilidades[0]['id'];
            
            $requisitoData = [
                'vacante_id' => $vacanteId,
                'habilidad_id' => $habilidadId,
                'nivel_requerido' => 'intermedio',
                'obligatorio' => true
            ];
            
            echo "Insertando habilidad ID=$habilidadId para vacante ID=$vacanteId\n";
            $resultado = supabaseInsert('vacante_habilidades', $requisitoData);
            
            if (!isset($resultado['error'])) {
                echo "OK: Se insertó correctamente la habilidad.\n";
            } else {
                echo "ERROR: No se pudo insertar la habilidad: " . $resultado['message'] . "\n";
            }
        } else {
            echo "ERROR: No se encontraron habilidades para la prueba.\n";
        }
    }
    
    echo "\nRESPUESTA COMPLETA DE LA INSERCIÓN:\n";
    echo json_encode($vacanteResponse, JSON_PRETTY_PRINT) . "\n";
}

echo "\n=================================================\n";
echo "PRUEBA COMPLETADA";
