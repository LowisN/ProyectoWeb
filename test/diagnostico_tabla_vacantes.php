<?php
/**
 * Script de diagnóstico para verificar estructura de la tabla Vacantes
 * Este script muestra campos existentes en la tabla vacantes para facilitar
 * validación y corrección de formularios
 */

// Incluir configuración
require_once 'config/supabase.php';

// Encabezado para visualización
header('Content-Type: text/html');
echo "<html><head><title>Estructura de Tabla Vacantes</title>";
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
</style>";
echo "</head><body>";
echo "<h1>Estructura de Tabla Vacantes</h1>";

// Obtener muestra de datos de vacantes
echo "<h2>Muestra de Datos de la tabla vacantes</h2>";
$vacantesData = supabaseFetch('vacantes', '*', [], 1);

if (empty($vacantesData) || isset($vacantesData['error'])) {
    echo "<p class='error'>Error al obtener datos de la tabla vacantes: " . (isset($vacantesData['error']) ? print_r($vacantesData['error'], true) : "No hay datos") . "</p>";
} else {
    // Mostrar estructura de la tabla basada en la primera fila
    echo "<h3>Campos disponibles en la tabla vacantes:</h3>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor de ejemplo</th><th>Tipo (inferido)</th></tr>";
    
    foreach ($vacantesData[0] as $campo => $valor) {
        $tipo = gettype($valor);
        echo "<tr>";
        echo "<td>$campo</td>";
        echo "<td>" . (is_array($valor) ? json_encode($valor) : htmlspecialchars((string)$valor)) . "</td>";
        echo "<td>$tipo</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Mostrar muestra completa
    echo "<h3>Datos de ejemplo (JSON):</h3>";
    echo "<pre>" . json_encode($vacantesData, JSON_PRETTY_PRINT) . "</pre>";
}

// Verificar si hay campos incorrectos en el formulario
echo "<h2>Verificación de Campos en el Formulario</h2>";

$camposFormulario = [
    'empresa_id',
    'reclutador_id',
    'titulo',
    'descripcion',
    'responsabilidades',
    'requisitos',
    'salario',
    'modalidad',
    'ubicacion',
    'anios_experiencia',
    'fecha_publicacion',
    'fecha_expiracion',
    'estado',
    'destacada'
];

echo "<h3>Campos que deberían estar en el formulario:</h3>";
echo "<ul>";
foreach ($camposFormulario as $campo) {
    if (isset($vacantesData[0][$campo])) {
        echo "<li class='success'>$campo ✓</li>";
    } else {
        echo "<li class='warning'>$campo (no encontrado en los datos de muestra)</li>";
    }
}
echo "</ul>";

// Verificar campos que existen en los datos pero no están considerados en el formulario
if (!empty($vacantesData) && !isset($vacantesData['error'])) {
    $camposNoConsiderados = [];
    
    foreach (array_keys($vacantesData[0]) as $campo) {
        if (!in_array($campo, $camposFormulario) && 
            $campo != 'id' && 
            $campo != 'fecha_creacion' && 
            $campo != 'ultima_actualizacion') {
            $camposNoConsiderados[] = $campo;
        }
    }
    
    if (!empty($camposNoConsiderados)) {
        echo "<h3>Campos adicionales encontrados en la tabla (no considerados en el formulario):</h3>";
        echo "<ul class='warning'>";
        foreach ($camposNoConsiderados as $campo) {
            echo "<li>$campo</li>";
        }
        echo "</ul>";
    }
}

echo "<h2>Conclusión</h2>";
echo "<p>La tabla vacantes tiene los siguientes campos fundamentales:</p>";
echo "<ul>";
echo "<li><strong>empresa_id</strong>: ID de la empresa que publica la vacante</li>";
echo "<li><strong>reclutador_id</strong>: ID del reclutador que publica la vacante</li>";
echo "<li><strong>titulo</strong>: Título de la vacante</li>";
echo "<li><strong>descripcion</strong>: Descripción general de la vacante</li>";
echo "<li><strong>responsabilidades</strong>: Responsabilidades del puesto</li>";
echo "<li><strong>requisitos</strong>: Requisitos generales del puesto</li>";
echo "<li><strong>salario</strong>: Salario ofrecido (valor numérico)</li>";
echo "<li><strong>modalidad</strong>: Modalidad de trabajo (presencial, remoto, híbrido)</li>";
echo "<li><strong>ubicacion</strong>: Ubicación física del puesto</li>";
echo "<li><strong>anios_experiencia</strong>: Años de experiencia requeridos</li>";
echo "<li><strong>fecha_publicacion</strong>: Fecha de publicación de la vacante</li>";
echo "<li><strong>fecha_expiracion</strong>: Fecha de expiración (opcional)</li>";
echo "<li><strong>estado</strong>: Estado de la vacante (activa, pausada, cerrada)</li>";
echo "<li><strong>destacada</strong>: Si la vacante es destacada o no (booleano)</li>";
echo "</ul>";

echo "<p class='warning'>Los campos <strong>moneda</strong>, <strong>periodo_pago</strong>, <strong>salario_min</strong> y <strong>salario_max</strong> NO existen en la tabla.</p>";

echo "</body></html>";
