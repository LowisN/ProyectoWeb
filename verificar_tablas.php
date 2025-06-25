<?php
// Script para verificar la conexión con Supabase y el estado de las tablas
require_once 'config/supabase.php';

echo "<h1>Verificación de Supabase</h1>";

// Verificar conexión
echo "<h2>Verificando conexión con Supabase...</h2>";
$testConnection = supabaseRequest('/rest/v1/');

if (isset($testConnection['error'])) {
    echo "<p style='color: red;'>Error de conexión: " . htmlspecialchars(json_encode($testConnection)) . "</p>";
} else {
    echo "<p style='color: green;'>Conexión exitosa con Supabase</p>";
}

// Verificar tablas
echo "<h2>Verificando tablas en Supabase...</h2>";
$tables = ['perfiles', 'empresas', 'candidatos', 'reclutadores', 'habilidades', 
           'candidato_habilidades', 'experiencia_laboral', 'educacion', 'vacantes', 
           'vacante_habilidades'];

echo "<ul>";
foreach ($tables as $table) {
    $result = supabaseFetch($table, 'id', ['limit' => 1]);
    
    if (isset($result['error']) || isset($result['code'])) {
        echo "<li style='color: red;'>Tabla '{$table}': No existe o no es accesible - " . 
             htmlspecialchars(json_encode($result)) . "</li>";
    } else {
        echo "<li style='color: green;'>Tabla '{$table}': Existe y es accesible</li>";
        
        // Mostrar número de registros
        $countResult = supabaseFetch($table, 'count', []);
        if (!isset($countResult['error']) && is_array($countResult)) {
            $count = count($countResult) > 0 ? $countResult[0]['count'] : '0';
            echo " - Registros: {$count}";
        }
    }
}
echo "</ul>";

// Verificar permisos en una tabla
echo "<h2>Verificando permisos de inserción...</h2>";
$testData = [
    'nombre' => 'Test ' . time(),
    'categoria' => 'otros',
    'descripcion' => 'Registro de prueba para verificar permisos'
];

echo "<p>Intentando insertar un registro de prueba en la tabla 'habilidades'...</p>";
$insertResult = supabaseInsert('habilidades', $testData);

if (isset($insertResult['error'])) {
    echo "<p style='color: red;'>Error al insertar: " . htmlspecialchars(json_encode($insertResult)) . "</p>";
} else {
    echo "<p style='color: green;'>Inserción exitosa: " . htmlspecialchars(json_encode($insertResult)) . "</p>";
    
    // Eliminar el registro de prueba
    if (isset($insertResult[0]['id'])) {
        $id = $insertResult[0]['id'];
        echo "<p>Eliminando registro de prueba con ID {$id}...</p>";
        $deleteResult = supabaseDelete('habilidades', ['id' => $id]);
        
        if (isset($deleteResult['error'])) {
            echo "<p style='color: red;'>Error al eliminar: " . htmlspecialchars(json_encode($deleteResult)) . "</p>";
        } else {
            echo "<p style='color: green;'>Eliminación exitosa</p>";
        }
    }
}

// Verificar estructura de la tabla "perfiles"
echo "<h2>Verificando estructura de la tabla 'perfiles'</h2>";
// Usando un enfoque alternativo para obtener información del esquema
$query = "/rest/v1/perfiles?select=user_id,tipo_usuario,id&limit=0";
$schemaResult = supabaseRequest($query);

if (isset($schemaResult['error'])) {
    echo "<p style='color: red;'>Error al obtener estructura: " . htmlspecialchars(json_encode($schemaResult)) . "</p>";
} else {
    echo "<p style='color: green;'>Campos disponibles: " . htmlspecialchars(implode(', ', array_keys($schemaResult))) . "</p>";
}

// Verificar respuesta de supabaseSignUp
echo "<h2>Verificando estructura de respuesta de supabaseSignUp</h2>";
// Usar un email temporal que nunca vamos a utilizar realmente
$testEmail = "test_" . time() . "@ejemplo.com";
$testPassword = "Password1234!";
$testData = [
    'nombre' => 'Usuario Test',
    'apellidos' => 'Apellido Test'
];

echo "<p>Intentando registrar un usuario de prueba (solo para ver la estructura de respuesta)...</p>";
echo "<p>Email: " . htmlspecialchars($testEmail) . "</p>";

$signUpResult = supabaseSignUp($testEmail, $testPassword, $testData);

echo "<p>Estructura de la respuesta:</p>";
echo "<pre>" . htmlspecialchars(json_encode($signUpResult, JSON_PRETTY_PRINT)) . "</pre>";

if (isset($signUpResult['user']) && isset($signUpResult['user']['id'])) {
    echo "<p style='color: green;'>ID de usuario encontrado: " . htmlspecialchars($signUpResult['user']['id']) . "</p>";
} else {
    echo "<p style='color: red;'>No se pudo encontrar el ID de usuario en la respuesta. Revisa la estructura completa arriba.</p>";
    
    // Intentar encontrar el ID de usuario en cualquier lugar de la estructura
    $jsonString = json_encode($signUpResult);
    if (preg_match('/"id"\s*:\s*"([^"]+)"/', $jsonString, $matches)) {
        echo "<p style='color: orange;'>Posible ID de usuario encontrado en otra ubicación: " . htmlspecialchars($matches[1]) . "</p>";
    }
}

echo "<h2>Conclusión</h2>";
echo "<p>Si ves errores arriba, es probable que necesites:</p>";
echo "<ol>";
echo "<li>Verificar que las tablas han sido creadas con el script SQL correcto</li>";
echo "<li>Comprobar los permisos de Supabase (RLS policies) para permitir inserciones</li>";
echo "<li>Revisar que la API key tenga los permisos adecuados</li>";
echo "</ol>";

echo "<p>Información de diagnóstico:</p>";
echo "<pre>";
echo "URL de Supabase: " . SUPABASE_URL . "\n";
echo "Primera parte de la API Key: " . substr(SUPABASE_KEY, 0, 10) . "...\n";
echo "Fecha y hora del servidor: " . date('Y-m-d H:i:s') . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "cURL habilitado: " . (function_exists('curl_version') ? 'Sí' : 'No') . "\n";
echo "</pre>";
?>
