<?php
// Script para diagnosticar la estructura de la base de datos para el registro de candidatos
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';

// Definir las tablas que necesitamos verificar
$tablas = [
    'perfiles',
    'candidatos',
    'educacion',
    'experiencia_laboral',
    'habilidades',
    'candidato_habilidades'
];

echo "<h1>Diagnóstico de Tablas para Registro de Candidatos</h1>";

// Verificar cada tabla
foreach ($tablas as $tabla) {
    echo "<h2>Tabla: $tabla</h2>";
    
    $resultado = supabaseFetch($tabla, '*', ['limit' => 1]);
    
    if (isset($resultado['error']) || isset($resultado['code'])) {
        echo "<p style='color: red;'><strong>ERROR:</strong> La tabla no existe o no es accesible</p>";
        echo "<pre>" . print_r($resultado, true) . "</pre>";
    } else {
        echo "<p style='color: green;'><strong>OK:</strong> La tabla existe y es accesible</p>";
        
        // Si hay resultados, mostrar la estructura
        if (!empty($resultado)) {
            echo "<p>Muestra de datos:</p>";
            echo "<pre>" . print_r($resultado[0], true) . "</pre>";
        } else {
            echo "<p>La tabla no tiene registros</p>";
        }
        
        // Supabase Client para consultar metadata
        echo "<p>Intentando obtener metadatos de la tabla con el cliente...</p>";
        try {
            $supabase = getSupabaseClient();
            $response = $supabase->request("/rest/v1/$tabla?limit=1");
            
            echo "<p style='color: green;'>Consulta exitosa con el cliente SupabaseClient</p>";
            
            // Mostrar resultado en formato legible
            echo "<pre>";
            var_dump($response);
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error con el cliente SupabaseClient: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
}

// Probar la clase de habilidades
echo "<h2>Prueba de la clase Habilidades</h2>";
try {
    require_once 'models/habilidades.php';
    
    $habilidadesManager = new Habilidades();
    $todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();
    
    echo "<p>Total de habilidades encontradas: " . count($todasHabilidades) . "</p>";
    
    if (count($todasHabilidades) > 0) {
        echo "<p>Muestra de habilidades:</p>";
        echo "<ul>";
        $contador = 0;
        foreach ($todasHabilidades as $habilidad) {
            if ($contador < 10) {
                echo "<li>ID: {$habilidad->id} - Nombre: {$habilidad->nombre} - Categoría: {$habilidad->categoria}</li>";
                $contador++;
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'><strong>AVISO:</strong> No se encontraron habilidades en la base de datos.</p>";
    }
    
    // Probar obtención por categoría
    echo "<h3>Habilidades por categoría</h3>";
    $porCategoria = $habilidadesManager->obtenerHabilidadesPorCategoria();
    foreach ($porCategoria as $categoria => $habilidadesCategoria) {
        echo "<p><strong>$categoria</strong>: " . count($habilidadesCategoria) . " habilidades</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
