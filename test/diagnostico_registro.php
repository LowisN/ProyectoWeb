<?php
// Este script ayudará a identificar y corregir problemas con el registro de candidatos
session_start();
require_once 'config/supabase.php';

echo "<h1>Diagnóstico de Registro de Candidato</h1>";

// Verificar tabla de habilidades
echo "<h2>Verificando tabla de habilidades</h2>";
$tablaHabilidades = 'candidato_habilidades';

echo "<p>Verificando tabla '$tablaHabilidades'...</p>";
$response = supabaseRequest("/rest/v1/$tablaHabilidades?limit=1");

if (!isset($response['error']) || !isset($response['code'])) {
    echo "<p style='color:green'>✅ La tabla '$tablaHabilidades' existe y es accesible.</p>";
    
    // Verificar estructura
    echo "<p>Estructura esperada: id, candidato_id, habilidad_id, nivel, anios_experiencia, certificado_url, fecha_creacion, ultima_actualizacion</p>";
    
    if (is_array($response) && !empty($response)) {
        $campos = array_keys((array)$response[0]);
        echo "<p>Campos encontrados: " . implode(", ", $campos) . "</p>";
        
        // Verificar campos clave
        $camposClaveEncontrados = true;
        foreach (['candidato_id', 'habilidad_id', 'nivel'] as $campoObligatorio) {
            if (!in_array($campoObligatorio, $campos)) {
                $camposClaveEncontrados = false;
                echo "<p style='color:red'>❌ Falta el campo obligatorio '$campoObligatorio'</p>";
            }
        }
        
        if ($camposClaveEncontrados) {
            echo "<p style='color:green'>✅ La estructura tiene todos los campos obligatorios</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ La tabla '$tablaHabilidades' no existe o hay un error: " . 
         (isset($response['message']) ? $response['message'] : json_encode($response)) . "</p>";
}

// Verificar tabla de educación
echo "<h2>Verificando tabla de educación</h2>";
$tablaEducacion = 'educacion';

echo "<p>Verificando tabla '$tablaEducacion'...</p>";
$response = supabaseRequest("/rest/v1/$tablaEducacion?limit=1");

if (!isset($response['error']) || !isset($response['code'])) {
    echo "<p style='color:green'>✅ La tabla '$tablaEducacion' existe y es accesible.</p>";
    
    // Verificar estructura
    echo "<p>Estructura esperada: id, candidato_id, institucion, titulo, area, fecha_inicio, fecha_fin, en_curso, descripcion, fecha_creacion, ultima_actualizacion</p>";
    
    if (is_array($response) && !empty($response)) {
        $campos = array_keys((array)$response[0]);
        echo "<p>Campos encontrados: " . implode(", ", $campos) . "</p>";
        
        // Verificar campos clave
        $camposClaveEncontrados = true;
        foreach (['candidato_id', 'institucion', 'titulo'] as $campoObligatorio) {
            if (!in_array($campoObligatorio, $campos)) {
                $camposClaveEncontrados = false;
                echo "<p style='color:red'>❌ Falta el campo obligatorio '$campoObligatorio'</p>";
            }
        }
        
        if ($camposClaveEncontrados) {
            echo "<p style='color:green'>✅ La estructura tiene todos los campos obligatorios</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ La tabla '$tablaEducacion' no existe o hay un error: " . 
         (isset($response['message']) ? $response['message'] : json_encode($response)) . "</p>";
}

// Verificar tabla de experiencia laboral
echo "<h2>Verificando tabla de experiencia laboral</h2>";
$tablaExperiencia = 'experiencia_laboral';

echo "<p>Verificando tabla '$tablaExperiencia'...</p>";
$response = supabaseRequest("/rest/v1/$tablaExperiencia?limit=1");

if (!isset($response['error']) || !isset($response['code'])) {
    echo "<p style='color:green'>✅ La tabla '$tablaExperiencia' existe y es accesible.</p>";
    
    // Verificar estructura
    echo "<p>Estructura esperada: id, candidato_id, empresa, puesto, fecha_inicio, fecha_fin, actual, descripcion, fecha_creacion, ultima_actualizacion</p>";
    
    if (is_array($response) && !empty($response)) {
        $campos = array_keys((array)$response[0]);
        echo "<p>Campos encontrados: " . implode(", ", $campos) . "</p>";
        
        // Verificar campos clave
        $camposClaveEncontrados = true;
        foreach (['candidato_id', 'empresa', 'puesto'] as $campoObligatorio) {
            if (!in_array($campoObligatorio, $campos)) {
                $camposClaveEncontrados = false;
                echo "<p style='color:red'>❌ Falta el campo obligatorio '$campoObligatorio'</p>";
            }
        }
        
        if ($camposClaveEncontrados) {
            echo "<p style='color:green'>✅ La estructura tiene todos los campos obligatorios</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ La tabla '$tablaExperiencia' no existe o hay un error: " . 
         (isset($response['message']) ? $response['message'] : json_encode($response)) . "</p>";
}

// Resultados
echo "<h2>Resultados</h2>";
echo "<ul>";
echo "<li>Tabla de habilidades: <strong style='color:green'>candidato_habilidades</strong></li>";
echo "<li>Tabla de educación: <strong style='color:green'>educacion</strong></li>";
echo "<li>Tabla de experiencia: <strong style='color:green'>experiencia_laboral</strong></li>";
echo "</ul>";

// Extraer los nombres de tablas usados en el controlador
$controller_path = 'controllers/registro_candidato_unificado_controller.php';
if (file_exists($controller_path)) {
    $controller_content = file_get_contents($controller_path);
    
    echo "<h3>Nombres de tablas utilizados en el controlador:</h3>";
    $tablas_encontradas = [];
    
    // Buscar patrones comunes de uso de tablas en el controlador
    preg_match_all("/supabaseInsert\(['\"](.*?)['\"]/", $controller_content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $tabla) {
            if (!in_array($tabla, $tablas_encontradas) && $tabla !== '$tablaCorrecta' && $tabla !== '$tablaEducacionCorrecta' && $tabla !== '$tablaExperienciaCorrecta' && $tabla !== '$tablaHabilidades' && $tabla !== '$tablaEducacion' && $tabla !== '$tablaExperiencia') {
                $tablas_encontradas[] = $tabla;
            }
        }
    }
    
    if (!empty($tablas_encontradas)) {
        echo "<ul>";
        foreach ($tablas_encontradas as $tabla) {
            $color = 'black';
            if ($tabla === 'candidato_habilidades' || $tabla === 'educacion' || $tabla === 'experiencia_laboral') {
                $color = 'green';
            } else {
                $color = 'orange';
            }
            echo "<li style='color:$color'>$tabla</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No se encontraron nombres de tablas fijas en el controlador.</p>";
        echo "<p>El controlador está usando variables para los nombres de tablas, lo cual es correcto.</p>";
    }
    
    // Verificar si también se usa el nuevo modelo de habilidades
    if (strpos($controller_content, 'models/habilidades.php') !== false && strpos($controller_content, 'insertarHabilidadesCandidato') !== false) {
        echo "<p style='color:green'>✅ El controlador está usando el modelo de habilidades personalizado</p>";
    } else {
        echo "<p style='color:orange'>⚠️ El controlador no parece estar usando el modelo de habilidades personalizado</p>";
    }
} else {
    echo "<p style='color:red'>No se pudo encontrar el archivo del controlador.</p>";
}
}

echo "<hr>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
