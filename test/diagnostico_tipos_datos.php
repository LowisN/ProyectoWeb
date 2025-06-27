<?php
/**
 * Script para diagnosticar y corregir problemas de tipos en la inserción de vacantes
 * Este script detecta qué campos en la tabla vacantes son de tipo entero/booleano
 */

// Incluir configuración
require_once 'config/supabase.php';

// Encabezado para visualización
header('Content-Type: text/html');
echo "<html><head><title>Diagnóstico de Tipos de Datos</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #333; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .code-block { background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; }
</style>";
echo "</head><body>";
echo "<h1>Diagnóstico de Tipos de Datos en la Tabla Vacantes</h1>";

// Obtener información sobre la tabla vacantes
$vacantes = supabaseFetch('vacantes', '*', [], 1);

// Verificar si podemos determinar los tipos de datos
if (empty($vacantes) || isset($vacantes['error'])) {
    echo "<p class='error'>Error al obtener datos de la tabla vacantes: " . 
        (isset($vacantes['error']) ? print_r($vacantes['error'], true) : "No hay datos") . "</p>";
} else {
    echo "<h2>Análisis de Campos en la Tabla Vacantes</h2>";
    
    // Vamos a realizar una prueba para cada campo booleano potencial
    echo "<h3>Prueba de inserción con diferentes tipos</h3>";
    
    // Obtener datos necesarios para la prueba
    $empresaData = supabaseFetch('empresas', '*', [], 1);
    $reclutadorData = supabaseFetch('reclutadores', '*', [], 1);
    
    if (!isset($empresaData[0]['id']) || !isset($reclutadorData[0]['id'])) {
        echo "<p class='error'>No se pudo obtener datos de empresa o reclutador para la prueba.</p>";
    } else {
        // Primera prueba con destacada como booleano
        $booleanTest = [
            'empresa_id' => $empresaData[0]['id'],
            'reclutador_id' => $reclutadorData[0]['id'],
            'titulo' => 'Test booleano - ' . time(),
            'descripcion' => 'Prueba con destacada como booleano',
            'estado' => 'borrador',
            'destacada' => true
        ];
        
        echo "<div class='code-block'>";
        echo "<h4>Prueba 1: Campo 'destacada' como booleano (true)</h4>";
        echo "<pre>" . json_encode($booleanTest, JSON_PRETTY_PRINT) . "</pre>";
        
        $boolResult = supabaseInsert('vacantes', $booleanTest);
        
        if (isset($boolResult['error'])) {
            echo "<p class='error'>Error: " . $boolResult['message'] . "</p>";
            
            // Segunda prueba con destacada como entero
            $intTest = [
                'empresa_id' => $empresaData[0]['id'],
                'reclutador_id' => $reclutadorData[0]['id'],
                'titulo' => 'Test entero - ' . time(),
                'descripcion' => 'Prueba con destacada como entero',
                'estado' => 'borrador',
                'destacada' => 1
            ];
            
            echo "<h4>Prueba 2: Campo 'destacada' como entero (1)</h4>";
            echo "<pre>" . json_encode($intTest, JSON_PRETTY_PRINT) . "</pre>";
            
            $intResult = supabaseInsert('vacantes', $intTest);
            
            if (isset($intResult['error'])) {
                echo "<p class='error'>Error: " . $intResult['message'] . "</p>";
            } else {
                echo "<p class='success'>Éxito! El campo 'destacada' debe ser de tipo entero.</p>";
            }
        } else {
            echo "<p class='success'>Éxito! El campo 'destacada' acepta valores booleanos.</p>";
        }
        echo "</div>";
    }
    
    // Analizar los campos en la primera fila para inferir tipos
    echo "<h2>Inferencia de Tipos de Datos</h2>";
    echo "<p>Basados en los datos existentes:</p>";
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor de ejemplo</th><th>Tipo PHP</th><th>Tipo recomendado para Supabase</th></tr>";
    
    foreach ($vacantes[0] as $campo => $valor) {
        $tipo = gettype($valor);
        $recomendado = $tipo;
        
        // Inferir tipo recomendado para Supabase
        if ($campo === 'destacada') {
            $recomendado = "entero (0/1)";
        } elseif ($tipo === 'boolean') {
            $recomendado = "boolean";
        } elseif ($tipo === 'integer') {
            $recomendado = "integer";
        } elseif ($tipo === 'double') {
            $recomendado = "numeric";
        } elseif ($tipo === 'string' && strtotime($valor) !== false) {
            $recomendado = "date o timestamp";
        } elseif ($tipo === 'string') {
            $recomendado = "text";
        }
        
        echo "<tr>";
        echo "<td>$campo</td>";
        echo "<td>" . (is_array($valor) ? json_encode($valor) : htmlspecialchars((string)$valor)) . "</td>";
        echo "<td>$tipo</td>";
        echo "<td>$recomendado</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Solución para la corrección
echo "<h2>Solución Implementada</h2>";
echo "<p>Se ha modificado la función <code>supabaseInsert</code> para convertir automáticamente valores booleanos a enteros (0/1) para los campos:</p>";
echo "<ul>";
echo "<li><code>destacada</code></li>";
echo "<li><code>obligatorio</code></li>";
echo "</ul>";

echo "<div class='code-block'>";
echo "<pre>
// Lista de campos que deberían ser enteros (1/0) en lugar de booleanos
\$integerBooleanFields = ['destacada', 'obligatorio'];

// Procesamiento de datos
foreach (\$data as \$key => \$value) {
    // Si el campo está en la lista de campos de tipo entero-booleano
    if (in_array(\$key, \$integerBooleanFields)) {
        // Convertir cualquier valor booleano a entero (1/0)
        if (\$value === true || \$value === \"true\" || \$value === 1 || \$value === \"1\") {
            \$processedData[\$key] = 1;
        } else {
            \$processedData[\$key] = 0;
        }
    }
    // Para otros campos booleanos regulares
    else if (\$value === true || \$value === \"true\" || \$value === 1 || \$value === \"1\") {
        \$processedData[\$key] = true;
    } 
    else if (\$value === false || \$value === \"false\" || \$value === 0 || \$value === \"0\") {
        \$processedData[\$key] = false;
    }
    // Mantener otros valores sin cambios
    else {
        \$processedData[\$key] = \$value;
    }
}
</pre>";
echo "</div>";

echo "<p>Esta solución asegura que los campos se conviertan al tipo correcto para la estructura de la base de datos:</p>";
echo "<ul>";
echo "<li>Los campos booleanos estándar se envían como <code>true/false</code> en JSON.</li>";
echo "<li>Los campos booleanos que son de tipo entero en la base de datos se envían como <code>1/0</code>.</li>";
echo "</ul>";

echo "<h2>Prueba final</h2>";
echo "<p>Para verificar que todo funciona correctamente, ejecuta el script <code>prueba_vacante_corregida.php</code>.</p>";

echo "</body></html>";
