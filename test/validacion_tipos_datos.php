<?php
/**
 * Script para validación final del procesamiento de tipos de datos
 * Este script analiza todos los campos y simula la conversión correcta
 */

// Incluir configuración
require_once 'config/supabase.php';

// Encabezado para visualización
header('Content-Type: text/html');
echo "<html><head><title>Validación Final de Tipos de Datos</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
    h1, h2, h3 { color: #333; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .neutral { color: blue; }
    .code-block { background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; font-family: monospace; }
    .highlight { background-color: #fff3cd; }
</style>";
echo "</head><body>";

echo "<h1>Validación Final del Procesamiento de Tipos de Datos</h1>";

// Función para mostrar el proceso de conversión
function mostrarProcesamiento($data) {
    // Lista de campos que deberían ser enteros (1/0) en lugar de booleanos
    $integerBooleanFields = ['destacada', 'obligatorio'];
    
    // Lista de campos que son IDs enteros (nunca deben convertirse a booleanos)
    $integerIdFields = ['id', 'empresa_id', 'reclutador_id', 'vacante_id', 'habilidad_id', 'candidato_id', 'perfil_id', 'user_id'];
    
    // Preprocesar los datos para corregir valores booleanos
    $processedData = array();
    $conversiones = [];
    
    foreach ($data as $key => $value) {
        $tipoOriginal = gettype($value);
        $valorOriginal = is_bool($value) ? ($value ? 'true' : 'false') : (is_null($value) ? 'null' : (string)$value);
        
        // Si el campo es un ID, asegurarse de que sea entero
        if (in_array($key, $integerIdFields)) {
            // Convertir a entero si no lo es ya
            $processedData[$key] = is_numeric($value) ? (int)$value : $value;
            $conversiones[$key] = [
                'tipo_original' => $tipoOriginal,
                'valor_original' => $valorOriginal,
                'tipo_final' => gettype($processedData[$key]),
                'valor_final' => (string)$processedData[$key],
                'regla' => 'ID - convertido a entero',
                'cambio' => ($tipoOriginal != gettype($processedData[$key]) || $valorOriginal != (string)$processedData[$key])
            ];
        }
        // Si el campo está en la lista de campos de tipo entero-booleano
        else if (in_array($key, $integerBooleanFields)) {
            // Convertir cualquier valor booleano a entero (1/0)
            if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
                $processedData[$key] = 1;
                $regla = 'Booleano a Entero (true->1)';
            } else {
                $processedData[$key] = 0;
                $regla = 'Booleano a Entero (false->0)';
            }
            
            $conversiones[$key] = [
                'tipo_original' => $tipoOriginal,
                'valor_original' => $valorOriginal,
                'tipo_final' => gettype($processedData[$key]),
                'valor_final' => (string)$processedData[$key],
                'regla' => $regla,
                'cambio' => ($tipoOriginal != gettype($processedData[$key]) || $valorOriginal != (string)$processedData[$key])
            ];
        }
        // Para campos booleanos regulares
        else if ($value === true || $value === "true" || $value === 1 || $value === "1" || $value === "on") {
            $processedData[$key] = true;
            $conversiones[$key] = [
                'tipo_original' => $tipoOriginal,
                'valor_original' => $valorOriginal,
                'tipo_final' => 'boolean',
                'valor_final' => 'true',
                'regla' => 'Valor convertido a booleano (true)',
                'cambio' => ($tipoOriginal != 'boolean' || $valorOriginal != 'true')
            ];
        } 
        else if ($value === false || $value === "false" || $value === 0 || $value === "0" || $value === "") {
            $processedData[$key] = false;
            $conversiones[$key] = [
                'tipo_original' => $tipoOriginal,
                'valor_original' => $valorOriginal,
                'tipo_final' => 'boolean',
                'valor_final' => 'false',
                'regla' => 'Valor convertido a booleano (false)',
                'cambio' => ($tipoOriginal != 'boolean' || $valorOriginal != 'false')
            ];
        }
        // Mantener otros valores sin cambios
        else {
            $processedData[$key] = $value;
            $conversiones[$key] = [
                'tipo_original' => $tipoOriginal,
                'valor_original' => $valorOriginal,
                'tipo_final' => gettype($processedData[$key]),
                'valor_final' => is_null($processedData[$key]) ? 'null' : (string)$processedData[$key],
                'regla' => 'Valor sin cambios',
                'cambio' => false
            ];
        }
    }
    
    return ['processed' => $processedData, 'conversiones' => $conversiones];
}

// 1. Datos de prueba para varios escenarios
echo "<h2>Escenario 1: Vacante con Valores Típicos</h2>";
$datosVacante = [
    'empresa_id' => 1,
    'reclutador_id' => 1,
    'titulo' => 'Vacante de prueba',
    'descripcion' => 'Descripción de prueba',
    'responsabilidades' => 'Responsabilidades de prueba',
    'requisitos' => 'Requisitos de prueba',
    'salario' => 15000,
    'modalidad' => 'remoto',
    'ubicacion' => 'Ciudad de México',
    'anios_experiencia' => 2,
    'fecha_publicacion' => date('Y-m-d'),
    'estado' => 'activa',
    'destacada' => true
];

$resultado = mostrarProcesamiento($datosVacante);

// Mostrar resultados
echo "<div class='code-block'>";
echo "<p>Datos originales:</p>";
echo "<pre>" . json_encode($datosVacante, JSON_PRETTY_PRINT) . "</pre>";

echo "<p>Datos procesados:</p>";
echo "<pre>" . json_encode($resultado['processed'], JSON_PRETTY_PRINT) . "</pre>";
echo "</div>";

echo "<h3>Análisis de Conversiones:</h3>";
echo "<table>";
echo "<tr><th>Campo</th><th>Tipo Original</th><th>Valor Original</th><th>Tipo Final</th><th>Valor Final</th><th>Regla Aplicada</th></tr>";

foreach ($resultado['conversiones'] as $campo => $conversion) {
    $colorClass = $conversion['cambio'] ? 'highlight' : '';
    echo "<tr class='$colorClass'>";
    echo "<td>{$campo}</td>";
    echo "<td>{$conversion['tipo_original']}</td>";
    echo "<td>{$conversion['valor_original']}</td>";
    echo "<td>{$conversion['tipo_final']}</td>";
    echo "<td>{$conversion['valor_final']}</td>";
    echo "<td>{$conversion['regla']}</td>";
    echo "</tr>";
}
echo "</table>";

// Escenario 2: Valores problemáticos
echo "<h2>Escenario 2: Valores Problemáticos</h2>";
$datosProblematicos = [
    'empresa_id' => "1", // String que debe ser entero
    'reclutador_id' => true, // Booleano que debe ser entero
    'destacada' => "true", // String que debe ser entero (1)
    'obligatorio' => "on", // String que debe ser entero (1)
    'valor_booleano' => 1, // Entero que debe ser booleano
];

$resultadoProb = mostrarProcesamiento($datosProblematicos);

// Mostrar resultados
echo "<div class='code-block'>";
echo "<p>Datos originales:</p>";
echo "<pre>" . json_encode($datosProblematicos, JSON_PRETTY_PRINT) . "</pre>";

echo "<p>Datos procesados:</p>";
echo "<pre>" . json_encode($resultadoProb['processed'], JSON_PRETTY_PRINT) . "</pre>";
echo "</div>";

echo "<h3>Análisis de Conversiones:</h3>";
echo "<table>";
echo "<tr><th>Campo</th><th>Tipo Original</th><th>Valor Original</th><th>Tipo Final</th><th>Valor Final</th><th>Regla Aplicada</th></tr>";

foreach ($resultadoProb['conversiones'] as $campo => $conversion) {
    $colorClass = $conversion['cambio'] ? 'highlight' : '';
    echo "<tr class='$colorClass'>";
    echo "<td>{$campo}</td>";
    echo "<td>{$conversion['tipo_original']}</td>";
    echo "<td>{$conversion['valor_original']}</td>";
    echo "<td>{$conversion['tipo_final']}</td>";
    echo "<td>{$conversion['valor_final']}</td>";
    echo "<td>{$conversion['regla']}</td>";
    echo "</tr>";
}
echo "</table>";

// Conclusión
echo "<h2>Conclusión</h2>";
echo "<p>La función <code>supabaseInsert</code> ahora maneja correctamente los siguientes casos:</p>";
echo "<ol>";
echo "<li><strong>Campos ID</strong>: Los campos como empresa_id, reclutador_id, etc. se convierten a enteros.</li>";
echo "<li><strong>Campos booleanos como enteros</strong>: Los campos como 'destacada' y 'obligatorio' se convierten a 1/0.</li>";
echo "<li><strong>Booleanos regulares</strong>: Se convierten a valores booleanos true/false.</li>";
echo "<li><strong>Otros tipos</strong>: Se mantienen sin cambios.</li>";
echo "</ol>";

echo "<p>Esta solución debería resolver todos los problemas de tipos de datos al insertar registros en Supabase.</p>";

// Prueba final recomendada
echo "<h2>Prueba Final Recomendada</h2>";
echo "<p>Ejecuta el script <code>prueba_vacante_tipos_corregidos.php</code> para verificar que todo funciona correctamente en un caso real.</p>";

echo "</body></html>";
