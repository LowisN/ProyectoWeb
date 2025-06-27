<?php
// Conectar a Supabase
require_once 'config/supabase.php';

// Configuración para mostrar errores en el navegador
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Diagnóstico de la Tabla vacante_habilidades</h2>";

// 1. Información Básica de la Tabla
echo "<h3>1. Estructura de la Tabla</h3>";

// Obtener la estructura de la tabla directamente usando RPC si está disponible
$estructura = supabaseRequest("/rest/v1/rpc/describe_table", "POST", ["table_name" => "vacante_habilidades"]);

if (isset($estructura['error'])) {
    echo "<p>No se pudo obtener la estructura directamente. Error: " . htmlspecialchars(json_encode($estructura)) . "</p>";
    
    // Alternativa: Intentar obtener una muestra para inferir la estructura
    echo "<p>Intentando obtener una muestra para inferir estructura...</p>";
    $muestra = supabaseFetch('vacante_habilidades', '*', ['limit' => 5]);
    
    if (!empty($muestra) && !isset($muestra['error'])) {
        echo "<p>Estructura inferida de la muestra:</p>";
        echo "<pre>" . htmlspecialchars(print_r(array_keys($muestra[0]), true)) . "</pre>";
    } else {
        echo "<p>No se pudo obtener una muestra. Error: " . htmlspecialchars(json_encode($muestra)) . "</p>";
    }
} else {
    echo "<pre>" . htmlspecialchars(json_encode($estructura, JSON_PRETTY_PRINT)) . "</pre>";
}

// 2. Datos de Prueba
echo "<h3>2. Prueba de Inserción con Diferentes Tipos de Datos</h3>";

$tests = [
    [
        'descripcion' => 'Prueba con IDs como Enteros y obligatorio como Entero (1)',
        'datos' => [
            'vacante_id' => 1,
            'habilidad_id' => 1,
            'nivel_requerido' => 'intermedio',
            'obligatorio' => 1
        ]
    ],
    [
        'descripcion' => 'Prueba con IDs como Strings y obligatorio como String "1"',
        'datos' => [
            'vacante_id' => '2',
            'habilidad_id' => '2',
            'nivel_requerido' => 'avanzado',
            'obligatorio' => '1'
        ]
    ],
    [
        'descripcion' => 'Prueba con IDs como Enteros y obligatorio como Boolean (true)',
        'datos' => [
            'vacante_id' => 3,
            'habilidad_id' => 3,
            'nivel_requerido' => 'experto',
            'obligatorio' => true
        ]
    ]
];

foreach ($tests as $index => $test) {
    echo "<h4>" . htmlspecialchars($test['descripcion']) . "</h4>";
    
    echo "<p>Datos originales:</p>";
    echo "<pre>" . htmlspecialchars(print_r($test['datos'], true)) . "</pre>";
    
    // Intentar la inserción (pero primero verificar si ya existe para evitar duplicados)
    $verificacion = supabaseFetch('vacante_habilidades', '*', [
        'vacante_id' => $test['datos']['vacante_id'],
        'habilidad_id' => $test['datos']['habilidad_id']
    ]);
    
    if (!empty($verificacion) && !isset($verificacion['error'])) {
        echo "<p>Ya existe un registro con estos IDs. Saltando inserción.</p>";
        echo "<pre>" . htmlspecialchars(print_r($verificacion[0], true)) . "</pre>";
        continue;
    }
    
    $resultado = supabaseInsert('vacante_habilidades', $test['datos']);
    
    echo "<p>Resultado de la inserción:</p>";
    echo "<pre>" . htmlspecialchars(print_r($resultado, true)) . "</pre>";
}

// 3. Recuperar registros existentes
echo "<h3>3. Registros Existentes en la Tabla</h3>";

$registros = supabaseFetch('vacante_habilidades', '*', ['order' => 'vacante_id.asc']);

if (!empty($registros) && !isset($registros['error'])) {
    echo "<p>Se encontraron " . count($registros) . " registros:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Vacante</th><th>ID Habilidad</th><th>Nivel</th><th>Obligatorio</th></tr>";
    
    foreach ($registros as $registro) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($registro['vacante_id']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['habilidad_id']) . "</td>";
        echo "<td>" . htmlspecialchars($registro['nivel_requerido']) . "</td>";
        echo "<td>" . htmlspecialchars(var_export($registro['obligatorio'], true)) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No se encontraron registros o hubo un error: " . htmlspecialchars(json_encode($registros)) . "</p>";
}

echo "<h3>Conclusión</h3>";
echo "<p>Revisa los resultados anteriores para identificar cómo debe ser la estructura correcta de los datos al insertar en la tabla vacante_habilidades.</p>";
echo "<p>Asegúrate de que los campos ID sean enteros y que el campo obligatorio tenga el formato correcto (entero 0/1 o booleano true/false).</p>";
