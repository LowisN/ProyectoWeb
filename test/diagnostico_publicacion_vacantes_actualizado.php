<?php
/**
 * Script de diagnóstico para verificar la estructura y operaciones en el flujo de publicación de vacantes
 * 
 * Este script verifica:
 * 1. La existencia y estructura de las tablas necesarias
 * 2. La capacidad de insertar datos en dichas tablas
 * 3. La recuperación correcta de datos para mostrar las vacantes y sus habilidades requeridas
 */

session_start();
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Establecer errores y logs para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Flujo de Publicación de Vacantes</h1>";
echo "<p>Fecha y hora: " . date('Y-m-d H:i:s') . "</p>";

// 1. Verificar las tablas y sus constraints
echo "<h2>1. Estructura de las tablas relacionadas con vacantes</h2>";

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

echo "<h3>Tabla 'vacantes'</h3>";
getTableStructure('vacantes');

echo "<h3>Tabla 'vacante_habilidades'</h3>";
getTableStructure('vacante_habilidades');

echo "<h3>Tabla 'habilidades'</h3>";
getTableStructure('habilidades');

// Verificar también la tabla requisitos_vacante (para compatibilidad)
echo "<h3>Tabla 'requisitos_vacante' (compatibilidad)</h3>";
getTableStructure('requisitos_vacante');

// 2. Verificar los valores permitidos para nivel_requerido
echo "<h2>2. Verificando valores permitidos para nivel_requerido</h2>";
echo "<p>Según el constraint de la base de datos, los valores permitidos son: 'principiante', 'intermedio', 'avanzado', 'experto'</p>";

// Verificar valores actuales en la base de datos
$url = SUPABASE_URL . "/rest/v1/vacante_habilidades?select=nivel_requerido&distinct=true";
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
        echo "<p>Valores distintos en la tabla vacante_habilidades:</p>";
        echo "<ul>";
        foreach ($data as $item) {
            echo "<li>" . htmlspecialchars($item['nivel_requerido']) . "</li>";
        }
        echo "</ul>";
    }
}

// 3. Verificar la relación entre habilidades y vacante_habilidades
echo "<h2>3. Relación entre habilidades y vacante_habilidades</h2>";

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
    'estado' => 'activa',
    'destacada' => false,
    'fecha_expiracion' => date('Y-m-d', strtotime('+30 days'))
];

echo "<p>No se realizará la inserción real para evitar datos de prueba en la base. Datos que se insertarían:</p>";
echo "<pre>" . print_r($vacanteEjemplo, true) . "</pre>";

// Simular inserción de habilidades requeridas
echo "<h3>Simulación de inserción de habilidades requeridas</h3>";

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
        
        // Mostrar cómo sería la estructura de datos a insertar en vacante_habilidades
        $requisitoData = [
            'vacante_id' => '[ID de la vacante creada]',
            'habilidad_id' => $id,
            'nivel_requerido' => $nivel,
            'obligatorio' => true
        ];
        
        echo "<pre>Datos a insertar en vacante_habilidades: " . print_r($requisitoData, true) . "</pre>";
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

$url = SUPABASE_URL . "/rest/v1/vacantes?select=id,titulo,empresa_nombre,fecha_publicacion,fecha_expiracion,destacada&limit=5";
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
            $destacada = isset($vacante['destacada']) && $vacante['destacada'] ? '✓ Destacada' : '';
            echo "<li>ID: {$vacante['id']}, Título: {$vacante['titulo']}, Empresa: {$vacante['empresa_nombre']}, ";
            echo "Publicada: {$vacante['fecha_publicacion']}, Expira: {$vacante['fecha_expiracion']}, $destacada</li>";
        }
        echo "</ul>";
        
        // Verificar habilidades requeridas de la primera vacante
        if (isset($data[0]['id'])) {
            $vacanteId = $data[0]['id'];
            echo "<h3>Habilidades requeridas para la vacante ID: $vacanteId</h3>";
            
            // Intentar con vacante_habilidades primero
            $url = SUPABASE_URL . "/rest/v1/vacante_habilidades?select=*,habilidades(nombre)&vacante_id=eq." . $vacanteId;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $requisitos = json_decode($response, true);
                if (is_array($requisitos) && !empty($requisitos)) {
                    echo "<p>Habilidades requeridas (vacante_habilidades):</p>";
                    echo "<ul>";
                    foreach ($requisitos as $requisito) {
                        $nombreHabilidad = isset($requisito['habilidades']) && isset($requisito['habilidades']['nombre'])
                            ? $requisito['habilidades']['nombre'] 
                            : "ID: {$requisito['habilidad_id']}";
                        echo "<li>Habilidad: $nombreHabilidad, Nivel: {$requisito['nivel_requerido']}, ";
                        echo "Obligatorio: " . ($requisito['obligatorio'] ? 'Sí' : 'No') . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No se encontraron habilidades requeridas en vacante_habilidades.</p>";
                    
                    // Como fallback, probar con requisitos_vacante
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
                            echo "<p>Habilidades requeridas (requisitos_vacante - tabla alternativa):</p>";
                            echo "<ul>";
                            foreach ($requisitos as $requisito) {
                                echo "<li>Tecnología: {$requisito['tecnologia']}, Nivel: {$requisito['nivel_requerido']}</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>No se encontraron requisitos para esta vacante en ninguna tabla.</p>";
                        }
                    }
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
