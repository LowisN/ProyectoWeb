<?php
/**
 * Script de diagnóstico para verificar el proceso de publicación de vacantes
 * y las relaciones entre vacantes, requisitos y habilidades
 */

session_start();
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Establecer errores y logs para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Publicación de Vacantes</h1>";

// 1. Verificar la tabla de requisitos_vacante y sus constraints
echo "<h2>1. Estructura de la tabla requisitos_vacante</h2>";

// Función para obtener estructura de tabla
function getTableStructure($table) {
    $url = SUPABASE_URL . "/rest/v1/" . $table . "?select=*&limit=1";
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
    
    if ($httpCode === 200) {
        echo "<p>Tabla $table existe y es accesible.</p>";
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data)) {
            echo "<pre>Estructura de registro: " . print_r(array_keys($data[0]), true) . "</pre>";
        } else {
            echo "<p>No hay registros en la tabla para mostrar la estructura.</p>";
        }
    } else {
        echo "<p class='error'>Error accediendo a la tabla $table: Código HTTP $httpCode</p>";
    }
}

getTableStructure('requisitos_vacante');

// 2. Verificar los valores permitidos para nivel_requerido
echo "<h2>2. Verificando valores permitidos para nivel_requerido</h2>";
echo "<p>Según el constraint de la base de datos, los valores permitidos son: 'principiante', 'intermedio', 'avanzado', 'experto'</p>";

// Verificar valores actuales en la base de datos
$url = SUPABASE_URL . "/rest/v1/requisitos_vacante?select=nivel_requerido&distinct=true";
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

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (is_array($data)) {
        echo "<p>Valores distintos en la tabla:</p>";
        echo "<ul>";
        foreach ($data as $item) {
            echo "<li>" . htmlspecialchars($item['nivel_requerido']) . "</li>";
        }
        echo "</ul>";
    }
}

// 3. Verificar la relación entre habilidades y requisitos_vacante
echo "<h2>3. Relación entre habilidades y requisitos_vacante</h2>";

// Obtener habilidades disponibles
$habilidadesManager = new Habilidades();
$todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();

echo "<p>Total de habilidades disponibles: " . count($todasHabilidades) . "</p>";

// Mostrar algunas habilidades de ejemplo
echo "<p>Primeras 5 habilidades de ejemplo:</p>";
echo "<ul>";
$count = 0;
foreach ($todasHabilidades as $habilidad) {
    if ($count >= 5) break;
    
    if (is_object($habilidad)) {
        $nombre = $habilidad->nombre ?? 'Sin nombre';
        $categoria = $habilidad->categoria ?? 'Sin categoría';
        $id = $habilidad->id ?? 'Sin ID';
        
        echo "<li>ID: $id, Nombre: $nombre, Categoría: $categoria</li>";
    } else {
        echo "<li>" . print_r($habilidad, true) . "</li>";
    }
    
    $count++;
}
echo "</ul>";

// 4. Prueba simulada de creación de vacante
echo "<h2>4. Simulación de creación de vacante</h2>";

// Crear un array con datos de ejemplo para una vacante
$vacanteEjemplo = [
    'empresa_id' => 1, // Este ID debe existir en tu base de datos
    'empresa_nombre' => 'Empresa de Prueba',
    'reclutador_id' => 1, // Este ID debe existir en tu base de datos
    'titulo' => 'Vacante de Prueba para Diagnóstico',
    'descripcion' => 'Esta es una vacante de prueba para diagnóstico',
    'responsabilidades' => 'Responsabilidades de prueba',
    'requisitos' => 'Requisitos de prueba',
    'salario' => 25000,
    'modalidad' => 'remoto',
    'ubicacion' => 'Ciudad de Prueba',
    'anios_experiencia_requeridos' => 2,
    'fecha_publicacion' => date('Y-m-d'),
    'estado' => 'prueba' // Usamos 'prueba' para identificar que es una vacante de diagnóstico
];

echo "<p>No se realizará la inserción real para evitar datos de prueba en la base. Datos que se insertarían:</p>";
echo "<pre>" . print_r($vacanteEjemplo, true) . "</pre>";

// Simular inserción de requisitos
echo "<h3>Simulación de inserción de requisitos de vacante</h3>";

// Tomamos 3 habilidades aleatorias para la simulación
$habilidadesSeleccionadas = array_slice($todasHabilidades, 0, 3);

echo "<p>Habilidades que se seleccionarían:</p>";
echo "<ul>";
foreach ($habilidadesSeleccionadas as $habilidad) {
    if (is_object($habilidad)) {
        $nombre = $habilidad->nombre ?? 'Sin nombre';
        $id = $habilidad->id ?? 'Sin ID';
        $nivel = ['principiante', 'intermedio', 'avanzado', 'experto'][array_rand(['principiante', 'intermedio', 'avanzado', 'experto'])];
        
        echo "<li>Habilidad: $nombre, ID: $id, Nivel requerido: $nivel</li>";
        
        // Mostrar cómo sería la estructura de datos a insertar
        $requisitoData = [
            'vacante_id' => '[ID de la vacante creada]',
            'tecnologia' => $nombre,
            'nivel_requerido' => $nivel
        ];
        
        if ($id) {
            $requisitoData['habilidad_id'] = $id;
        }
        
        echo "<pre>Datos a insertar: " . print_r($requisitoData, true) . "</pre>";
    }
}
echo "</ul>";

// 5. Mostrar formato esperado de los datos en el formulario
echo "<h2>5. Formato esperado de datos en el formulario</h2>";

// Mostrar algunos ejemplos de nombres de campos
echo "<p>Al publicar una vacante, los campos generados para cada habilidad siguen este formato:</p>";
echo "<ul>";
echo "<li>Checkbox para seleccionar la habilidad: req_[nombre_tecnologia]</li>";
echo "<li>Radio button para nivel: nivel_[nombre_tecnologia]</li>";
echo "</ul>";

echo "<p>Ejemplo para la habilidad 'TCP/IP':</p>";
echo "<ul>";
echo "<li>Checkbox: <code>req_tcp_ip</code></li>";
echo "<li>Radio buttons para nivel: <code>nivel_tcp_ip</code> con valores 'principiante', 'intermedio', 'avanzado', 'experto'</li>";
echo "</ul>";

// 6. Verificar si hay alguna vacante activa
echo "<h2>6. Verificación de vacantes existentes</h2>";

$url = SUPABASE_URL . "/rest/v1/vacantes?select=id,titulo,empresa_nombre&limit=5";
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

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (is_array($data) && !empty($data)) {
        echo "<p>Primeras 5 vacantes encontradas:</p>";
        echo "<ul>";
        foreach ($data as $vacante) {
            echo "<li>ID: {$vacante['id']}, Título: {$vacante['titulo']}, Empresa: {$vacante['empresa_nombre']}</li>";
        }
        echo "</ul>";
        
        // Verificar requisitos de la primera vacante
        if (isset($data[0]['id'])) {
            $vacanteId = $data[0]['id'];
            echo "<h3>Requisitos para la vacante ID: $vacanteId</h3>";
            
            $url = SUPABASE_URL . "/rest/v1/requisitos_vacante?select=*&vacante_id=eq." . $vacanteId;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $requisitos = json_decode($response, true);
                if (is_array($requisitos) && !empty($requisitos)) {
                    echo "<p>Requisitos encontrados:</p>";
                    echo "<ul>";
                    foreach ($requisitos as $requisito) {
                        echo "<li>Tecnología: {$requisito['tecnologia']}, Nivel: {$requisito['nivel_requerido']}</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No se encontraron requisitos para esta vacante.</p>";
                }
            }
        }
    } else {
        echo "<p>No se encontraron vacantes.</p>";
    }
}

echo "<hr>";
echo "<p><strong>Diagnóstico finalizado</strong></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 20px;
    color: #333;
}
h1, h2, h3 {
    color: #2c3e50;
}
h1 {
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}
h2 {
    margin-top: 30px;
    border-left: 4px solid #3498db;
    padding-left: 10px;
}
pre {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 5px;
    overflow: auto;
}
ul {
    background-color: #f8f9fa;
    padding: 15px 15px 15px 40px;
    border-radius: 5px;
}
li {
    margin-bottom: 5px;
}
.error {
    color: #e74c3c;
    font-weight: bold;
}
code {
    background-color: #f1f1f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 30px 0;
}
</style>
