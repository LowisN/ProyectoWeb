<?php
// Incluir la configuración de Supabase
require_once 'config/supabase.php';

// Encabezado para fines de depuración
header('Content-Type: text/plain');
echo "DIAGNÓSTICO DE SOLUCIÓN PARA INSERCIONES DE VACANTES\n";
echo "=================================================\n\n";

// Verificar variables necesarias
echo "1. VERIFICANDO FUNCIONES DE SUPABASE\n";
if (function_exists('supabaseInsert')) {
    echo "OK: La función supabaseInsert está definida.\n";
} else {
    echo "ERROR: La función supabaseInsert no está definida.\n";
    exit;
}

if (function_exists('supabaseFetch')) {
    echo "OK: La función supabaseFetch está definida.\n";
} else {
    echo "ERROR: La función supabaseFetch no está definida.\n";
    exit;
}

echo "\n2. VERIFICANDO ESTRUCTURA DE TABLA VACANTES\n";
// Verificamos la estructura de la tabla vacantes usando la nueva función
$vacantesInfo = supabaseDescribeTable('vacantes');
if (isset($vacantesInfo['exists']) && $vacantesInfo['exists'] === true) {
    echo "OK: La tabla 'vacantes' existe.\n";
    echo "Muestra de datos: " . json_encode($vacantesInfo['sample']) . "\n";
    if (isset($vacantesInfo['description']) && is_array($vacantesInfo['description'])) {
        echo "Descripción de tabla disponible: " . count($vacantesInfo['description']) . " columnas encontradas.\n";
    } else {
        echo "No se pudo obtener la descripción detallada de la tabla.\n";
    }
} else {
    echo "ERROR: Problema con la tabla 'vacantes'.\n";
    if (isset($vacantesInfo['error'])) {
        echo "Error: " . json_encode($vacantesInfo['error']) . "\n";
    }
}

echo "\n3. VERIFICANDO ESTRUCTURA DE TABLA VACANTE_HABILIDADES\n";
$vhInfo = supabaseDescribeTable('vacante_habilidades');
if (isset($vhInfo['exists']) && $vhInfo['exists'] === true) {
    echo "OK: La tabla 'vacante_habilidades' existe.\n";
    echo "Muestra de datos: " . json_encode($vhInfo['sample']) . "\n";
    if (isset($vhInfo['description']) && is_array($vhInfo['description'])) {
        echo "Descripción de tabla disponible: " . count($vhInfo['description']) . " columnas encontradas.\n";
    } else {
        echo "No se pudo obtener la descripción detallada de la tabla.\n";
    }
} else {
    echo "ERROR: Problema con la tabla 'vacante_habilidades'.\n";
    if (isset($vhInfo['error'])) {
        echo "Error: " . json_encode($vhInfo['error']) . "\n";
    }
}

echo "\n4. SIMULACIÓN DE INSERCIÓN DE VACANTE Y OBTENCIÓN DE ID\n";
// Simular datos de empresa y reclutador
$empresaData = supabaseFetch('empresas', '*', [], 1);
$reclutadorData = supabaseFetch('reclutadores', '*', [], 1);

if (!isset($empresaData[0]['id']) || !isset($reclutadorData[0]['id'])) {
    echo "ERROR: No se pudo obtener datos de empresa o reclutador para la simulación.\n";
    if (isset($empresaData['error'])) {
        echo "Error empresas: " . json_encode($empresaData['error']) . "\n";
    }
    if (isset($reclutadorData['error'])) {
        echo "Error reclutadores: " . json_encode($reclutadorData['error']) . "\n";
    }
    exit;
}

echo "OK: Usando empresa_id=" . $empresaData[0]['id'] . " y reclutador_id=" . $reclutadorData[0]['id'] . "\n";

// Datos de vacante de prueba
$vacanteData = [
    'empresa_id' => $empresaData[0]['id'],
    'reclutador_id' => $reclutadorData[0]['id'],
    'titulo' => 'Vacante de prueba diagnóstico - ' . date('Y-m-d H:i:s'),
    'descripcion' => 'Esta es una vacante de prueba para el diagnóstico',
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

echo "Insertando vacante de prueba... ";
$vacanteResponse = supabaseInsert('vacantes', $vacanteData);
var_dump($vacanteResponse);

// Intentar obtener el ID de la vacante de diferentes formas
$vacanteId = null;

if (isset($vacanteResponse['error'])) {
    echo "ERROR: No se pudo crear la vacante de prueba: " . $vacanteResponse['message'] . "\n";
} else {
    echo "OK: La vacante de prueba se creó exitosamente.\n";
    
    // Intentar obtener ID de diferentes formas según la estructura de respuesta
    if (is_array($vacanteResponse) && !empty($vacanteResponse) && isset($vacanteResponse[0]['id'])) {
        $vacanteId = $vacanteResponse[0]['id'];
        echo "ID de vacante obtenido del primer elemento del array: $vacanteId\n";
    } else if (isset($vacanteResponse['id'])) {
        $vacanteId = $vacanteResponse['id'];
        echo "ID de vacante obtenido directamente: $vacanteId\n";
    } else if (isset($vacanteResponse['success']) && $vacanteResponse['success'] === true) {
        // La inserción fue exitosa pero no tenemos el ID directamente
        echo "Inserción exitosa pero sin ID. Intentando recuperar mediante consulta.\n";
        
        $filtros = [
            'titulo' => $vacanteData['titulo'],
            'empresa_id' => $vacanteData['empresa_id'],
            'fecha_publicacion' => $vacanteData['fecha_publicacion']
        ];
        
        $vacanteConsulta = supabaseFetch('vacantes', '*', $filtros);
        
        if (is_array($vacanteConsulta) && !empty($vacanteConsulta) && isset($vacanteConsulta[0]['id'])) {
            $vacanteId = $vacanteConsulta[0]['id'];
            echo "ID de vacante recuperado mediante consulta: $vacanteId\n";
        } else {
            echo "ERROR: No se pudo recuperar el ID de la vacante mediante consulta.\n";
            var_dump($vacanteConsulta);
        }
    }
}

echo "\n5. VERIFICAR OBTENCIÓN DE HABILIDADES\n";
$habilidadesResponse = supabaseFetch('habilidades', '*', [], 1);
if (isset($habilidadesResponse[0]['id'])) {
    echo "OK: Se pueden recuperar habilidades. Primera habilidad: ID=" . $habilidadesResponse[0]['id'] . ", nombre=" . $habilidadesResponse[0]['nombre'] . "\n";
    $habilidadId = $habilidadesResponse[0]['id'];
} else {
    echo "ERROR: No se pueden recuperar habilidades.\n";
    var_dump($habilidadesResponse);
    exit;
}

echo "\n6. SIMULAR INSERCIÓN EN VACANTE_HABILIDADES\n";
if ($vacanteId !== null) {
    $requisitoData = [
        'vacante_id' => $vacanteId,
        'habilidad_id' => $habilidadId,
        'nivel_requerido' => 'intermedio',
        'obligatorio' => true
    ];
    
    echo "Insertando requisito de habilidad para vacante $vacanteId y habilidad $habilidadId...\n";
    $requisitoResponse = supabaseInsert('vacante_habilidades', $requisitoData);
    
    if (!isset($requisitoResponse['error'])) {
        echo "OK: Requisito insertado exitosamente.\n";
        var_dump($requisitoResponse);
    } else {
        echo "ERROR: Fallo al insertar requisito.\n";
        var_dump($requisitoResponse);
    }
} else {
    echo "ERROR: No se puede simular la inserción en vacante_habilidades porque no se obtuvo ID de vacante.\n";
}

echo "\n7. VERIFICAR RECUPERACIÓN DE REQUISITOS DE VACANTE\n";
if ($vacanteId !== null) {
    $requisitosQuery = supabaseFetch('vacante_habilidades', '*', ['vacante_id' => $vacanteId]);
    
    if (is_array($requisitosQuery) && !empty($requisitosQuery)) {
        echo "OK: Se recuperaron " . count($requisitosQuery) . " requisitos para la vacante $vacanteId.\n";
        var_dump($requisitosQuery);
    } else {
        echo "ERROR: No se pudieron recuperar requisitos para la vacante $vacanteId.\n";
        var_dump($requisitosQuery);
    }
} else {
    echo "ERROR: No se puede verificar recuperación porque no se obtuvo ID de vacante.\n";
}

echo "\n=================================================\n";
echo "DIAGNÓSTICO COMPLETADO";
?>
