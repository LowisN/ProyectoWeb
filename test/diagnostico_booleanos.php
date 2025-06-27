<?php
/**
 * Script de diagnóstico para verificar el manejo de valores booleanos en POST
 * Simula el formulario de nueva_vacante.php para identificar problemas con los valores booleanos
 */

// Incluir configuración
require_once 'config/supabase.php';

// Función para simular un POST de formulario
function simularPost() {
    // Simular valores de una solicitud POST de nueva_vacante.php
    return [
        'titulo' => 'Puesto de prueba diagnóstico boolean',
        'descripcion' => 'Descripción de prueba',
        'responsabilidades' => 'Responsabilidades de prueba',
        'requisitos' => 'Requisitos de prueba',
        'salario_min' => '15000',
        'salario_max' => '20000',
        'moneda' => 'MXN',
        'periodo_pago' => 'mensual',
        'modalidad' => 'remoto',
        'ubicacion' => 'Ciudad de México',
        'anios_experiencia' => '2',
        'fecha_publicacion' => date('Y-m-d'),
        'estado' => 'activa',
        // El siguiente campo es problemático cuando viene como string vacío
        'destacada' => '', // Este es el problema: checkbox no marcado viene como string vacío
        
        // También probamos con una tecnología seleccionada
        'req_php' => 'on',
        'nivel_php' => 'intermedio'
    ];
}

// Función para procesar el POST simulado como lo haría nueva_vacante.php
function procesarPost($post) {
    // Procesar datos de la vacante
    $vacanteData = [
        'empresa_id' => 1, // Simulado
        'reclutador_id' => 1, // Simulado
        'titulo' => $post['titulo'],
        'descripcion' => $post['descripcion'],
        'responsabilidades' => $post['responsabilidades'],
        'requisitos' => $post['requisitos'],
        'salario_min' => filter_var($post['salario_min'], FILTER_VALIDATE_FLOAT) ?: null,
        'salario_max' => filter_var($post['salario_max'], FILTER_VALIDATE_FLOAT) ?: null,
        'moneda' => $post['moneda'],
        'periodo_pago' => $post['periodo_pago'],
        'modalidad' => $post['modalidad'],
        'ubicacion' => $post['ubicacion'],
        'anios_experiencia' => filter_var($post['anios_experiencia'], FILTER_VALIDATE_INT) ?: 0,
        'fecha_publicacion' => $post['fecha_publicacion'],
        'estado' => $post['estado'] ?? 'activa',
        // Aquí está el problema: convertir el valor adecuadamente
        'destacada' => isset($post['destacada']) && ($post['destacada'] === 'on' || $post['destacada'] === '1' || $post['destacada'] === 'true' || $post['destacada'] === true)
    ];
    
    return $vacanteData;
}

// Encabezado para visualización
header('Content-Type: text/html');
echo "<html><head><title>Diagnóstico de Valores Booleanos</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #333; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .code-block { background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; }
</style>";
echo "</head><body>";
echo "<h1>Diagnóstico de Valores Booleanos en Formulario de Vacante</h1>";

// 1. Simular datos POST
echo "<h2>1. Simulando datos POST del formulario</h2>";
$datosPost = simularPost();
echo "<pre>";
print_r($datosPost);
echo "</pre>";

// 2. Procesar los datos como lo haría nueva_vacante.php
echo "<h2>2. Procesando los datos como lo haría nueva_vacante.php</h2>";
$datosVacante = procesarPost($datosPost);
echo "<pre>";
print_r($datosVacante);
echo "</pre>";

// 3. Analizar el tipo de datos de cada campo, destacando los booleanos
echo "<h2>3. Análisis de tipos de datos</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Campo</th><th>Valor</th><th>Tipo PHP</th><th>Tipo JSON</th><th>Estado</th></tr>";

foreach ($datosVacante as $campo => $valor) {
    $tipoPHP = gettype($valor);
    $tipoJSON = json_encode($valor);
    
    $estado = "OK";
    $claseEstado = "success";
    
    // Detectar posibles problemas
    if ($campo === 'destacada') {
        if ($tipoPHP !== 'boolean') {
            $estado = "PROBLEMA: Debería ser boolean";
            $claseEstado = "error";
        }
    }
    
    echo "<tr>";
    echo "<td>{$campo}</td>";
    echo "<td>" . var_export($valor, true) . "</td>";
    echo "<td>{$tipoPHP}</td>";
    echo "<td>{$tipoJSON}</td>";
    echo "<td class='{$claseEstado}'>{$estado}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Simular la inserción con Supabase (sin ejecutar realmente)
echo "<h2>4. Simulación de preparación para Supabase</h2>";
echo "<p>Así se vería la conversión a JSON para enviar a Supabase:</p>";
echo "<pre>";
echo json_encode($datosVacante, JSON_PRETTY_PRINT);
echo "</pre>";

// 5. Solución propuesta
echo "<h2>5. Solución recomendada</h2>";
echo "<div class='code-block'>";
echo "<p>La solución tiene dos partes:</p>";

echo "<h3>Parte 1: Corregir el procesamiento de formularios</h3>";
echo "<p>En <code>nueva_vacante.php</code>, asegurarse de que los campos booleanos se manejen correctamente:</p>";
echo "<pre>
// Procesar el campo destacada correctamente
\$destacada = isset(\$_POST['destacada']) && \$_POST['destacada'] === 'on';

\$vacanteData = [
    // ...otros campos...
    'destacada' => \$destacada // Esto será true o false, no string vacío
];
</pre>";

echo "<h3>Parte 2: Mejorar la función supabaseInsert</h3>";
echo "<p>Modificar la función <code>supabaseInsert</code> en <code>config/supabase.php</code> para que maneje mejor los tipos:</p>";
echo "<pre>
function supabaseInsert(\$table, \$data) {
    // Preprocesar datos para manejar correctamente tipos
    \$processedData = array();
    foreach (\$data as \$key => \$value) {
        // Manejar conversión de booleanos explícitamente
        if (\$value === true || \$value === 'true' || \$value === 1 || \$value === '1') {
            \$processedData[\$key] = true;
        } 
        else if (\$value === false || \$value === 'false' || \$value === 0 || \$value === '0' || \$value === '') {
            \$processedData[\$key] = false;
        }
        // Para otros tipos
        else {
            \$processedData[\$key] = \$value;
        }
    }
    
    \$response = supabaseRequest(\"/rest/v1/\$table\", 'POST', \$processedData);
    // ...resto del código...
}
</pre>";
echo "</div>";

// 6. Conclusión
echo "<h2>6. Conclusión</h2>";
echo "<p>El problema principal identificado es con el manejo de valores booleanos que vienen del formulario:</p>";
echo "<ul>";
echo "<li>Cuando un checkbox no está marcado, no se envía en el POST</li>";
echo "<li>Cuando se procesa incorrectamente, puede convertirse en un string vacío</li>";
echo "<li>Supabase espera un tipo boolean verdadero, no un string</li>";
echo "</ul>";
echo "<p>La solución es asegurar que los campos booleanos siempre sean <code>true</code> o <code>false</code> antes de enviarlos a Supabase, nunca strings ni valores nulos.</p>";

echo "</body></html>";
