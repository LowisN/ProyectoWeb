<?php
/**
 * Script para probar la creación de una vacante usando la estructura correcta de la base de datos
 */

session_start();
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Establecer errores y logs para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Creación de Vacante</h1>";

// Este script solo es para pruebas, no ejecutará inserciones reales
$ejecutarInserciones = false; // Cambiar a true para realizar inserciones reales

// Verificar autenticación
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
    echo "<p class='error'>Este script debe ejecutarse con una sesión de reclutador activa.</p>";
    echo "<p><a href='index.php'>Iniciar sesión</a></p>";
    exit;
}

// 1. Verificar la estructura de las tablas
echo "<h2>1. Verificando estructura de tablas</h2>";

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
            echo "<pre>Estructura: " . print_r(array_keys($data[0]), true) . "</pre>";
        } else {
            echo "<p>No hay registros en la tabla para mostrar la estructura.</p>";
        }
        return true;
    } else {
        echo "<p class='error'>Error accediendo a la tabla $table: Código HTTP $httpCode</p>";
        return false;
    }
}

$tablasValidas = true;
$tablasValidas &= getTableStructure('vacantes');
$tablasValidas &= getTableStructure('vacante_habilidades');
$tablasValidas &= getTableStructure('habilidades');

if (!$tablasValidas) {
    echo "<p class='error'>Algunas tablas no están disponibles. No se puede continuar.</p>";
    exit;
}

// 2. Obtener datos del reclutador y empresa
echo "<h2>2. Obteniendo datos del reclutador y empresa</h2>";

$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    echo "<p class='error'>Error al cargar el perfil del usuario.</p>";
    exit;
}

$reclutadorData = supabaseFetch('reclutadores', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($reclutadorData) || isset($reclutadorData['error'])) {
    echo "<p class='error'>Error al cargar datos del reclutador.</p>";
    exit;
}

$empresaData = supabaseFetch('empresas', '*', ['id' => $reclutadorData[0]['empresa_id']]);

if (empty($empresaData) || isset($empresaData['error'])) {
    echo "<p class='error'>Error al cargar datos de la empresa.</p>";
    exit;
}

echo "<p>Reclutador: {$reclutadorData[0]['nombre']} {$reclutadorData[0]['apellidos']} (ID: {$reclutadorData[0]['id']})</p>";
echo "<p>Empresa: {$empresaData[0]['nombre']} (ID: {$empresaData[0]['id']})</p>";

// 3. Preparar datos de prueba para una vacante
echo "<h2>3. Preparando datos de prueba para vacante</h2>";

$vacanteData = [
    'empresa_id' => $empresaData[0]['id'],
    // Eliminar 'empresa_nombre' que no existe en la tabla vacantes
    'reclutador_id' => $reclutadorData[0]['id'],
    'titulo' => 'Vacante de Prueba ' . date('YmdHis'),
    'descripcion' => 'Esta es una descripción de prueba para la vacante.',
    'responsabilidades' => 'Responsabilidades de prueba para la vacante.',
    'requisitos' => 'Requisitos de prueba para la vacante.',
    'salario' => 25000,
    'modalidad' => 'remoto',
    'ubicacion' => 'Ciudad de Prueba',
    'anios_experiencia' => 2,
    'fecha_publicacion' => date('Y-m-d'),
    'estado' => 'activa',
    'destacada' => true,
    'fecha_expiracion' => date('Y-m-d', strtotime('+30 days'))
];

echo "<pre>Datos de vacante: " . print_r($vacanteData, true) . "</pre>";

// 4. Obtener habilidades disponibles
echo "<h2>4. Obteniendo habilidades disponibles</h2>";

$habilidadesManager = new Habilidades();
$todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();

echo "<p>Total de habilidades disponibles: " . count($todasHabilidades) . "</p>";

// Seleccionar algunas habilidades para la prueba
$habilidadesSeleccionadas = array_slice($todasHabilidades, 0, 3);

echo "<p>Habilidades seleccionadas para la prueba:</p>";
echo "<ul>";
foreach ($habilidadesSeleccionadas as $habilidad) {
    $id = is_object($habilidad) ? ($habilidad->id ?? 'N/A') : (is_array($habilidad) ? ($habilidad['id'] ?? 'N/A') : 'N/A');
    $nombre = is_object($habilidad) ? ($habilidad->nombre ?? 'Sin nombre') : (is_array($habilidad) ? ($habilidad['nombre'] ?? 'Sin nombre') : $habilidad);
    $categoria = is_object($habilidad) ? ($habilidad->categoria ?? 'N/A') : (is_array($habilidad) ? ($habilidad['categoria'] ?? 'N/A') : 'N/A');
    
    echo "<li>ID: $id, Nombre: $nombre, Categoría: $categoria</li>";
}
echo "</ul>";

// 5. Simular la creación de la vacante
echo "<h2>5. Simulando creación de vacante</h2>";

if ($ejecutarInserciones) {
    echo "<p>Creando vacante...</p>";
    $vacanteResponse = supabaseInsert('vacantes', $vacanteData);
    
    if (isset($vacanteResponse['error'])) {
        echo "<p class='error'>Error al crear la vacante: " . print_r($vacanteResponse['error'], true) . "</p>";
    } else {
        $vacanteId = $vacanteResponse[0]['id'];
        echo "<p class='success'>Vacante creada con ID: $vacanteId</p>";
        
        // Insertar habilidades seleccionadas
        $habilidadesInsertadas = 0;
        
        foreach ($habilidadesSeleccionadas as $habilidad) {
            $habilidadId = is_object($habilidad) ? ($habilidad->id ?? null) : (is_array($habilidad) ? ($habilidad['id'] ?? null) : null);
            $nombreHabilidad = is_object($habilidad) ? ($habilidad->nombre ?? 'Sin nombre') : (is_array($habilidad) ? ($habilidad['nombre'] ?? 'Sin nombre') : $habilidad);
            
            if ($habilidadId) {
                $nivel = ['principiante', 'intermedio', 'avanzado', 'experto'][array_rand(['principiante', 'intermedio', 'avanzado', 'experto'])];
                
                $requisitoData = [
                    'vacante_id' => $vacanteId,
                    'habilidad_id' => $habilidadId,
                    'nivel_requerido' => $nivel,
                    'obligatorio' => true
                ];
                
                $requisitoResponse = supabaseInsert('vacante_habilidades', $requisitoData);
                
                if (!isset($requisitoResponse['error'])) {
                    $habilidadesInsertadas++;
                    echo "<p>Insertada habilidad '$nombreHabilidad' con nivel $nivel</p>";
                } else {
                    echo "<p class='error'>Error al insertar habilidad '$nombreHabilidad': " . print_r($requisitoResponse['error'], true) . "</p>";
                }
            }
        }
        
        echo "<p>Total de habilidades insertadas: $habilidadesInsertadas</p>";
        
        // Proporcionar enlace para ver la vacante
        echo "<p><a href='paginas/empresa/detalle_vacante.php?id=$vacanteId'>Ver detalle de la vacante</a></p>";
    }
} else {
    echo "<p class='warning'>El modo de ejecución está desactivado. No se realizarán inserciones reales.</p>";
    echo "<p>Para activar las inserciones, cambia la variable \$ejecutarInserciones a true.</p>";
}

echo "<hr>";
echo "<p><strong>Prueba finalizada</strong></p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
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
.success {
    color: #2ecc71;
    font-weight: bold;
}
.warning {
    color: #f39c12;
    font-weight: bold;
}
hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 30px 0;
}
a {
    color: #3498db;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
