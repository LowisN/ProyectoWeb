<?php
// Script para verificar la estructura de la tabla empresas
require_once 'config/supabase.php';

echo "<h1>Verificación de la tabla 'empresas'</h1>";

// Verificar conexión
echo "<h2>Verificando conexión con Supabase...</h2>";
$testConnection = supabaseRequest('/rest/v1/');

if (isset($testConnection['error'])) {
    echo "<p style='color: red;'>Error de conexión: " . htmlspecialchars(json_encode($testConnection)) . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>Conexión exitosa con Supabase</p>";
}

// Intentar obtener información sobre la estructura de la tabla empresas
echo "<h2>Verificando estructura de la tabla 'empresas'</h2>";

// Enfoque 1: Intentar obtener metadatos
$query = "/rest/v1/empresas?select=id,nombre,rfc,industria,direccion,telefono,sitio_web&limit=0";
$schemaResult = supabaseRequest($query);

if (isset($schemaResult['error'])) {
    echo "<p style='color: red;'>Error al obtener estructura: " . htmlspecialchars(json_encode($schemaResult)) . "</p>";
} else {
    echo "<p style='color: green;'>Campos disponibles en la tabla:</p>";
    echo "<ul>";
    foreach (array_keys($schemaResult) as $field) {
        echo "<li>" . htmlspecialchars($field) . "</li>";
    }
    echo "</ul>";
}

// Enfoque 2: Obtener una empresa existente para analizar los datos
echo "<h2>Empresas existentes en la tabla</h2>";
$empresas = supabaseFetch('empresas', '*', []);

if (isset($empresas['error'])) {
    echo "<p style='color: red;'>Error al obtener empresas: " . htmlspecialchars(json_encode($empresas)) . "</p>";
} elseif (empty($empresas)) {
    echo "<p>No hay empresas registradas aún.</p>";
} else {
    echo "<p>Se encontraron " . count($empresas) . " empresas en la tabla.</p>";
    
    echo "<h3>Estructura de los datos de empresa:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    
    // Encabezados de la tabla
    echo "<tr>";
    foreach (array_keys($empresas[0]) as $headerField) {
        echo "<th>" . htmlspecialchars($headerField) . "</th>";
    }
    echo "</tr>";
    
    // Datos de cada empresa
    foreach ($empresas as $empresa) {
        echo "<tr>";
        foreach ($empresa as $key => $value) {
            echo "<td>";
            if (is_array($value)) {
                echo htmlspecialchars(json_encode($value));
            } else {
                echo htmlspecialchars($value);
                // Mostrar longitud para campos string
                if (is_string($value)) {
                    echo " <small>(" . strlen($value) . " caracteres)</small>";
                }
            }
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Obtener estructura de la tabla desde información del sistema
echo "<h2>Prueba de inserción con diferentes longitudes de RFC</h2>";

// Formulario para probar con diferentes longitudes de RFC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_rfc'])) {
    $test_rfc = $_POST['test_rfc'];
    $test_nombre = "Empresa Test " . time();
    
    echo "<h3>Intentando insertar empresa con RFC: " . htmlspecialchars($test_rfc) . " (longitud: " . strlen($test_rfc) . ")</h3>";
    
    $testData = [
        'nombre' => $test_nombre,
        'rfc' => $test_rfc,
        'industria' => 'Test',
        'direccion' => 'Dirección de prueba',
        'telefono' => '1234567890'
    ];
    
    $insertResult = supabaseInsert('empresas', $testData);
    
    if (isset($insertResult['error'])) {
        echo "<p style='color: red;'>Error al insertar: " . htmlspecialchars(json_encode($insertResult)) . "</p>";
        
        // Información adicional para el error específico
        if (strpos(json_encode($insertResult), 'varying(13)') !== false) {
            echo "<p style='color: red;'>Se confirma que el campo RFC está limitado a 13 caracteres.</p>";
        }
    } else {
        echo "<p style='color: green;'>Inserción exitosa: " . htmlspecialchars(json_encode($insertResult)) . "</p>";
        
        // Eliminar el registro de prueba
        if (isset($insertResult[0]['id'])) {
            $id = $insertResult[0]['id'];
            echo "<p>Eliminando registro de prueba con ID {$id}...</p>";
            $deleteResult = supabaseDelete('empresas', ['id' => $id]);
            
            if (isset($deleteResult['error'])) {
                echo "<p style='color: red;'>Error al eliminar: " . htmlspecialchars(json_encode($deleteResult)) . "</p>";
            } else {
                echo "<p style='color: green;'>Eliminación exitosa</p>";
            }
        }
    }
}

// Mostrar formulario para pruebas
?>
<form method="post" action="">
    <p>Introduce un RFC para probar la inserción:</p>
    <input type="text" name="test_rfc" value="ABC123456789" required>
    <small>Longitud actual: <span id="rfcLength">12</span> caracteres</small>
    <br><br>
    <button type="submit">Probar inserción</button>
</form>

<script>
document.querySelector('input[name="test_rfc"]').addEventListener('input', function() {
    document.getElementById('rfcLength').textContent = this.value.length;
});
</script>

<h2>Conclusión</h2>
<p>Basado en el error reportado ("value too long for type character varying(13)"), el campo RFC en la tabla empresas está limitado a 13 caracteres. Los RFC de empresas en México pueden ser de 12 caracteres, y los de personas físicas pueden ser de 13 caracteres.</p>
<p>La solución más sencilla es limitar la entrada del campo RFC en el formulario a máximo 13 caracteres.</p>

<p><a href="paginas/registro_empresa.php">Volver al formulario de registro de empresa</a></p>

<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #f2f2f2; }
    td, th { padding: 8px; text-align: left; border: 1px solid #ddd; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    input[type="text"] { padding: 8px; width: 300px; }
    button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    a { color: #2196F3; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
