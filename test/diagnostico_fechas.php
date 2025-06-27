<?php
// Conectar a Supabase
require_once 'config/supabase.php';

// Configuración para mostrar errores en el navegador
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Diagnóstico de Manejo de Fechas en Supabase</h2>";

// 1. Verificar cómo se manejan diferentes formatos de fechas
echo "<h3>1. Pruebas de Formatos de Fecha</h3>";

$testCases = [
    [
        'descripcion' => 'Fecha en formato ISO (YYYY-MM-DD)',
        'datos' => [
            'titulo' => 'Test fecha ISO - ' . time(),
            'empresa_id' => 1,
            'reclutador_id' => 1,
            'fecha_publicacion' => date('Y-m-d'),
            'fecha_expiracion' => '2025-12-31',
            'estado' => 'activa'
        ]
    ],
    [
        'descripcion' => 'Fecha como NULL',
        'datos' => [
            'titulo' => 'Test fecha NULL - ' . time(),
            'empresa_id' => 1,
            'reclutador_id' => 1,
            'fecha_publicacion' => date('Y-m-d'),
            'fecha_expiracion' => null,
            'estado' => 'activa'
        ]
    ],
    [
        'descripcion' => 'Fecha como cadena vacía (debería convertirse a NULL)',
        'datos' => [
            'titulo' => 'Test fecha vacía - ' . time(),
            'empresa_id' => 1,
            'reclutador_id' => 1,
            'fecha_publicacion' => date('Y-m-d'),
            'fecha_expiracion' => '',
            'estado' => 'activa'
        ]
    ]
];

foreach ($testCases as $index => $test) {
    echo "<h4>" . htmlspecialchars($test['descripcion']) . "</h4>";
    
    echo "<p>Datos originales:</p>";
    echo "<pre>" . htmlspecialchars(print_r($test['datos'], true)) . "</pre>";
    
    // Ejecutamos nuestra función de procesamiento de datos para ver cómo se transforman
    $processedData = [];
    $dateFields = ['fecha_expiracion', 'fecha_inicio', 'fecha_fin', 'fecha_nacimiento'];
    $integerBooleanFields = ['destacada', 'obligatorio'];
    $integerIdFields = ['id', 'empresa_id', 'reclutador_id', 'vacante_id', 'habilidad_id', 'candidato_id', 'perfil_id', 'user_id'];
    
    foreach ($test['datos'] as $key => $value) {
        // Si es valor nulo, mantenerlo como null
        if ($value === null) {
            $processedData[$key] = null;
            continue;
        }
        
        // Si es un campo de fecha y está vacío, tratarlo como null
        if (in_array($key, $dateFields) && (trim($value) === '' || $value === '0000-00-00')) {
            $processedData[$key] = null;
            continue;
        }
        
        // Si el campo es un ID, asegurarse de que sea entero
        if (in_array($key, $integerIdFields)) {
            $processedData[$key] = is_numeric($value) ? (int)$value : $value;
        }
        // Si el campo está en la lista de campos de tipo entero-booleano
        else if (in_array($key, $integerBooleanFields)) {
            if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
                $processedData[$key] = 1;
            } else {
                $processedData[$key] = 0;
            }
        }
        // Campos que deben ser numéricos (pero no están en la lista de IDs)
        else if (is_numeric($value) && (strpos($key, '_id') !== false || $key === 'anios_experiencia' || $key === 'salario')) {
            $processedData[$key] = is_float($value + 0) ? (float)$value : (int)$value;
        }
        // Para campos booleanos regulares
        else if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
            $processedData[$key] = true;
        }
        else if ($value === false || $value === "false" || $value === 0 || $value === "0" || $value === "") {
            $processedData[$key] = false;
        }
        // Mantener otros valores sin cambios
        else {
            $processedData[$key] = $value;
        }
    }
    
    echo "<p>Datos después del procesamiento:</p>";
    echo "<pre>" . htmlspecialchars(print_r($processedData, true)) . "</pre>";
    
    // Intentar la inserción
    $resultado = null;
    $mensajeError = null;
    
    try {
        if ($test['datos']['titulo'] !== 'NO_INSERTAR') {
            $resultado = supabaseInsert('vacantes', $test['datos']);
        } else {
            $resultado = "Inserción omitida para esta prueba";
        }
    } catch (Exception $e) {
        $mensajeError = $e->getMessage();
    }
    
    if ($mensajeError) {
        echo "<p>Error: " . htmlspecialchars($mensajeError) . "</p>";
    } else {
        echo "<p>Resultado de la inserción:</p>";
        echo "<pre>" . htmlspecialchars(print_r($resultado, true)) . "</pre>";
    }
    
    echo "<hr>";
}

echo "<h3>2. Estructura de la Tabla Vacantes</h3>";

// Obtener una muestra para inferir la estructura
$muestra = supabaseFetch('vacantes', '*', ['limit' => 1]);

if (!empty($muestra) && !isset($muestra['error'])) {
    echo "<p>Estructura inferida de la muestra:</p>";
    echo "<pre>" . htmlspecialchars(print_r($muestra[0], true)) . "</pre>";
    
    // Analizar específicamente los campos de fecha
    if (isset($muestra[0]['fecha_publicacion'])) {
        echo "<p>Formato de fecha_publicacion: " . htmlspecialchars($muestra[0]['fecha_publicacion']) . "</p>";
    }
    
    if (isset($muestra[0]['fecha_expiracion'])) {
        echo "<p>Formato de fecha_expiracion: " . htmlspecialchars($muestra[0]['fecha_expiracion'] === null ? "NULL" : $muestra[0]['fecha_expiracion']) . "</p>";
    }
} else {
    echo "<p>No se pudo obtener una muestra. Error: " . htmlspecialchars(json_encode($muestra)) . "</p>";
}

echo "<h3>Conclusión</h3>";
echo "<p>Revisa los resultados anteriores para identificar cómo deben manejarse correctamente los campos de fecha.</p>";
echo "<p>Asegúrate de que los campos de fecha vacíos se envíen como NULL y no como cadenas vacías.</p>";
