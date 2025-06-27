<?php
// Script para diagnosticar la inserción de habilidades en la tabla candidato_habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Configurar visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Habilidades de Candidato</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin-top: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Inserción de Habilidades para Candidatos</h1>";

// 1. Verificar la estructura de la tabla candidato_habilidades
echo "<div class='section'>
    <h2>1. Verificar tabla candidato_habilidades</h2>";

try {
    $client = getSupabaseClient();
    $response = $client->request("/rest/v1/candidato_habilidades?limit=1");
    
    if (isset($response->error)) {
        echo "<p class='error'>Error al acceder a la tabla: " . json_encode($response->error) . "</p>";
    } else {
        echo "<p class='success'>La tabla candidato_habilidades es accesible.</p>";
        
        // Intentar determinar la estructura
        $estructura = [];
        if (isset($response->data) && is_array($response->data) && count($response->data) > 0) {
            $estructura = (array)$response->data[0];
        } elseif (is_array($response) && count($response) > 0) {
            $estructura = (array)$response[0];
        }
        
        if (!empty($estructura)) {
            echo "<p>Estructura detectada:</p>";
            echo "<table>";
            echo "<tr><th>Columna</th><th>Tipo</th></tr>";
            foreach ($estructura as $columna => $valor) {
                echo "<tr>";
                echo "<td>$columna</td>";
                echo "<td>" . gettype($valor) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>No se pudo determinar la estructura (tabla vacía).</p>";
            echo "<p>Estructura esperada: candidato_id, habilidad_id, nivel, anios_experiencia</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Crear una conexión directa para verificar más detalles
echo "<h3>Verificación directa con Curl:</h3>";
$url = SUPABASE_URL . "/rest/v1/candidato_habilidades?limit=5";
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . SUPABASE_KEY,
    'apikey: ' . SUPABASE_KEY
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Código HTTP: $httpCode</p>";
if ($httpCode == 200) {
    $data = json_decode($response);
    if (is_array($data) && count($data) > 0) {
        echo "<p class='success'>Se encontraron " . count($data) . " registros en la tabla.</p>";
        echo "<table>";
        echo "<tr><th>ID Candidato</th><th>ID Habilidad</th><th>Nivel</th><th>Años Exp.</th></tr>";
        foreach ($data as $registro) {
            echo "<tr>";
            echo "<td>" . ($registro->candidato_id ?? 'N/A') . "</td>";
            echo "<td>" . ($registro->habilidad_id ?? 'N/A') . "</td>";
            echo "<td>" . ($registro->nivel ?? 'N/A') . "</td>";
            echo "<td>" . ($registro->anios_experiencia ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>No hay registros en la tabla.</p>";
    }
} else {
    echo "<p class='error'>Error al acceder a la tabla. Respuesta: " . substr($response, 0, 200) . "</p>";
}
echo "</div>";

// 2. Probar la inserción de una habilidad de candidato
echo "<div class='section'>
    <h2>2. Prueba de inserción de habilidad</h2>";

// Usar ID de prueba (no existente) o especificar uno real
$candidatoIdPrueba = isset($_GET['candidato_id']) ? $_GET['candidato_id'] : 99999;
$habilidadNombrePrueba = 'TCP/IP'; // Una habilidad común que debería existir

echo "<p>Usando ID de candidato: $candidatoIdPrueba</p>";
echo "<p>Habilidad de prueba: $habilidadNombrePrueba</p>";

try {
    $habilidadesManager = new Habilidades();
    
    // Verificar si existe la habilidad
    echo "<h3>2.1 Verificando existencia de la habilidad</h3>";
    $habilidadId = $habilidadesManager->obtenerIdPorNombre($habilidadNombrePrueba);
    
    if ($habilidadId) {
        echo "<p class='success'>Se encontró la habilidad con ID: $habilidadId</p>";
    } else {
        echo "<p class='error'>No se pudo encontrar la habilidad. Intentando crearla...</p>";
        $habilidadId = $habilidadesManager->insertarNuevaHabilidad($habilidadNombrePrueba);
        if ($habilidadId) {
            echo "<p class='success'>Habilidad creada con ID: $habilidadId</p>";
        } else {
            echo "<p class='error'>No se pudo crear la habilidad.</p>";
        }
    }
    
    if ($habilidadId) {
        // Intentar insertar la relación candidato-habilidad
        echo "<h3>2.2 Insertando relación candidato-habilidad</h3>";
        
        // Crear datos para inserción directa
        $habilidadData = [
            'candidato_id' => $candidatoIdPrueba,
            'habilidad_id' => $habilidadId,
            'nivel' => 'regular',
            'anios_experiencia' => 1
        ];
        
        echo "<p>Datos a insertar:</p>";
        echo "<pre>" . print_r($habilidadData, true) . "</pre>";
        
        // Intentar inserción directa primero
        echo "<h4>Inserción directa con supabaseInsert:</h4>";
        try {
            $resultado = supabaseInsert('candidato_habilidades', $habilidadData);
            if (isset($resultado['error'])) {
                echo "<p class='error'>Error en inserción directa: " . json_encode($resultado['error']) . "</p>";
            } else {
                echo "<p class='success'>Inserción directa exitosa: " . json_encode($resultado) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Excepción en inserción directa: " . $e->getMessage() . "</p>";
        }
        
        // Probar con el método de la clase Habilidades
        echo "<h4>Inserción usando la clase Habilidades:</h4>";
        $resultado = $habilidadesManager->guardarHabilidadCandidato($candidatoIdPrueba, $habilidadNombrePrueba, 'bueno');
        if ($resultado) {
            echo "<p class='success'>Inserción exitosa usando el método de la clase.</p>";
        } else {
            echo "<p class='error'>Error en inserción usando el método de la clase.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Error general: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// 3. Verificar el método completo con múltiples habilidades
echo "<div class='section'>
    <h2>3. Prueba con múltiples habilidades</h2>";

$habilidadesPrueba = [
    'TCP/IP' => 'bueno',
    'DNS' => 'regular',
    'Firewalls' => 'malo'
];

echo "<p>Habilidades de prueba:</p>";
echo "<pre>" . print_r($habilidadesPrueba, true) . "</pre>";

try {
    $habilidadesManager = new Habilidades();
    $resultado = $habilidadesManager->insertarHabilidadesCandidato($candidatoIdPrueba, $habilidadesPrueba);
    
    echo "<p>Resultado de inserción múltiple:</p>";
    echo "<pre>" . print_r($resultado, true) . "</pre>";
    
    if ($resultado['exitos'] > 0) {
        echo "<p class='success'>Se insertaron " . $resultado['exitos'] . " habilidades correctamente.</p>";
    }
    if ($resultado['errores'] > 0) {
        echo "<p class='error'>Se encontraron " . $resultado['errores'] . " errores al insertar habilidades.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error en inserción múltiple: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Botón para probar con un ID de candidato específico
echo "<div class='section'>
    <h2>Probar con ID específico</h2>
    <form method='GET'>
        <label>ID de Candidato:</label>
        <input type='number' name='candidato_id' value='$candidatoIdPrueba'>
        <button type='submit'>Probar</button>
    </form>
</div>";

echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "</body></html>";
?>
