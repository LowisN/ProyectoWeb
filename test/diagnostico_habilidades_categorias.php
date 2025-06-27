<?php
// Script para diagnosticar problemas con las categorías de habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Habilidades y Categorías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Habilidades y Categorías</h1>";

// 1. Verificar la conexión a Supabase
echo "<h2>1. Verificando conexión a Supabase</h2>";
try {
    $client = getSupabaseClient();
    echo "<p class='success'>✓ Cliente Supabase inicializado correctamente</p>";
    
    // Probar la conexión con una consulta simple
    $response = $client->request("/rest/v1/habilidades?limit=1");
    if (isset($response->error)) {
        echo "<p class='error'>Error al consultar la tabla habilidades: " . json_encode($response->error) . "</p>";
    } else {
        echo "<p class='success'>✓ Conexión a la API de Supabase funciona correctamente</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error al inicializar cliente Supabase: " . $e->getMessage() . "</p>";
}

// 2. Verificar la tabla de habilidades
echo "<h2>2. Examinando tabla de habilidades</h2>";
try {
    // Obtener estructura de la tabla
    echo "<h3>Estructura de la tabla:</h3>";
    $tablesResponse = supabaseFetch('habilidades', '*', [], true, 1);
    if (is_array($tablesResponse) && !empty($tablesResponse)) {
        $sample = $tablesResponse[0];
        echo "<p>Muestra de un registro de la tabla:</p>";
        echo "<pre>" . json_encode($sample, JSON_PRETTY_PRINT) . "</pre>";
        
        echo "<p>Columnas detectadas:</p>";
        echo "<ul>";
        foreach ($sample as $column => $value) {
            echo "<li><strong>$column</strong>: " . gettype($value) . ($value !== null ? " (ejemplo: " . (is_array($value) ? "Array" : (is_object($value) ? "Object" : $value)) . ")" : "") . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>No se pudo obtener la estructura de la tabla (sin registros).</p>";
    }
    
    // Contar registros
    $count = count(supabaseFetch('habilidades', '*'));
    echo "<p>Total de habilidades en la base de datos: <strong>$count</strong></p>";
    
    // Listar todas las categorías únicas
    $categorias = [];
    $allHabilidades = supabaseFetch('habilidades', '*');
    foreach ($allHabilidades as $habilidad) {
        if (isset($habilidad['categoria']) && !in_array($habilidad['categoria'], $categorias)) {
            $categorias[] = $habilidad['categoria'];
        }
    }
    
    echo "<h3>Categorías encontradas en la base de datos:</h3>";
    if (!empty($categorias)) {
        echo "<ul>";
        foreach ($categorias as $cat) {
            // Contar habilidades por categoría
            $count = 0;
            foreach ($allHabilidades as $hab) {
                if (isset($hab['categoria']) && $hab['categoria'] === $cat) {
                    $count++;
                }
            }
            echo "<li><strong>$cat</strong>: $count habilidades</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='warning'>No se encontraron categorías definidas.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error al examinar la tabla de habilidades: " . $e->getMessage() . "</p>";
}

// 3. Verificar la clase Habilidades
echo "<h2>3. Pruebas de la clase Habilidades</h2>";
try {
    $habilidadesManager = new Habilidades();
    
    // Probar obtenerCategoriasUnicas
    echo "<h3>3.1 Método obtenerCategoriasUnicas()</h3>";
    $categoriasUnicas = $habilidadesManager->obtenerCategoriasUnicas();
    if (!empty($categoriasUnicas)) {
        echo "<p class='success'>✓ El método retorna " . count($categoriasUnicas) . " categorías:</p>";
        echo "<ul>";
        foreach ($categoriasUnicas as $cat) {
            echo "<li>$cat</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>El método no retornó ninguna categoría.</p>";
    }
    
    // Probar obtenerTodasHabilidades
    echo "<h3>3.2 Método obtenerTodasHabilidades()</h3>";
    $todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();
    if (!empty($todasHabilidades)) {
        echo "<p class='success'>✓ El método retorna " . count($todasHabilidades) . " habilidades.</p>";
        
        // Mostrar muestra de habilidades
        echo "<p>Muestra de las primeras 5 habilidades:</p>";
        echo "<table class='table'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Descripción</th></tr>";
        
        $counter = 0;
        foreach ($todasHabilidades as $hab) {
            if ($counter >= 5) break;
            
            $id = is_object($hab) ? ($hab->id ?? 'N/A') : ($hab['id'] ?? 'N/A');
            $nombre = is_object($hab) ? ($hab->nombre ?? 'N/A') : ($hab['nombre'] ?? 'N/A');
            $categoria = is_object($hab) ? ($hab->categoria ?? 'N/A') : ($hab['categoria'] ?? 'N/A');
            $descripcion = is_object($hab) ? ($hab->descripcion ?? '') : ($hab['descripcion'] ?? '');
            
            echo "<tr>";
            echo "<td>$id</td>";
            echo "<td>$nombre</td>";
            echo "<td>$categoria</td>";
            echo "<td>" . (empty($descripcion) ? "(sin descripción)" : $descripcion) . "</td>";
            echo "</tr>";
            
            $counter++;
        }
        echo "</table>";
    } else {
        echo "<p class='error'>El método no retornó ninguna habilidad.</p>";
    }
    
    // Probar obtenerHabilidadesPorCategoria
    echo "<h3>3.3 Método obtenerHabilidadesPorCategoria()</h3>";
    $porCategoria = $habilidadesManager->obtenerHabilidadesPorCategoria();
    if (!empty($porCategoria)) {
        echo "<p class='success'>✓ El método retorna " . count($porCategoria) . " categorías con habilidades.</p>";
        
        echo "<table class='table'>";
        echo "<tr><th>Categoría</th><th>Cantidad</th><th>Muestra de habilidades</th></tr>";
        
        foreach ($porCategoria as $categoria => $habilidades) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($categoria) . "</td>";
            echo "<td>" . count($habilidades) . "</td>";
            
            echo "<td>";
            $names = [];
            $counter = 0;
            foreach ($habilidades as $hab) {
                if ($counter >= 3) break;
                $nombre = is_object($hab) ? ($hab->nombre ?? '') : ($hab['nombre'] ?? $hab ?? '');
                if (!empty($nombre)) {
                    $names[] = htmlspecialchars($nombre);
                    $counter++;
                }
            }
            echo implode(", ", $names);
            if (count($habilidades) > 3) {
                echo ", ...";
            }
            echo "</td>";
            
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>El método no retornó ninguna categoría con habilidades.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error al probar la clase Habilidades: " . $e->getMessage() . "</p>";
}

// 4. Probar la asignación de habilidades a categorías
echo "<h2>4. Prueba de asignación de habilidades a categorías</h2>";

try {
    // Crear una simulación de cómo se mostrarían las habilidades en el formulario
    echo "<h3>4.1 Simulación de visualización en el formulario de registro</h3>";
    
    $habilidadesManager = new Habilidades();
    $habilidadesPorCategoria = $habilidadesManager->obtenerHabilidadesPorCategoria();
    
    echo "<div style='border: 1px solid #ccc; padding: 15px; background: #f9f9f9;'>";
    echo "<h3>Vista previa del formulario</h3>";
    
    // Ordenar categorías alfabéticamente
    ksort($habilidadesPorCategoria);
    
    foreach ($habilidadesPorCategoria as $categoria => $tecnologias) {
        if (empty($tecnologias)) continue;
        
        echo "<div style='margin-bottom: 20px; border-bottom: 1px solid #ddd;'>";
        echo "<h4 style='background-color: #f2f2f2; padding: 8px;'>" . ucfirst(str_replace('_', ' ', $categoria)) . "</h4>";
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px;'>";
        
        // Ordenar habilidades alfabéticamente
        usort($tecnologias, function($a, $b) {
            $nombreA = is_object($a) ? ($a->nombre ?? '') : (is_array($a) ? ($a['nombre'] ?? '') : $a);
            $nombreB = is_object($b) ? ($b->nombre ?? '') : (is_array($b) ? ($b['nombre'] ?? '') : $b);
            return strcmp($nombreA, $nombreB);
        });
        
        foreach ($tecnologias as $habilidad) {
            // Manejar diferentes formatos posibles
            if (is_object($habilidad)) {
                $tecnologia = $habilidad->nombre ?? null;
                $descripcion = $habilidad->descripcion ?? '';
            } elseif (is_array($habilidad)) {
                $tecnologia = $habilidad['nombre'] ?? $habilidad[0] ?? null;
                $descripcion = $habilidad['descripcion'] ?? '';
            } else {
                $tecnologia = $habilidad;
                $descripcion = '';
            }
            
            if (!$tecnologia) continue;
            
            echo "<div style='border: 1px solid #eee; padding: 10px; border-radius: 5px;'>";
            echo "<div><strong>" . htmlspecialchars($tecnologia) . "</strong>";
            if (!empty($descripcion)) {
                echo " <span style='font-size: 0.8em; color: blue;'>ℹ</span>";
                echo "<div style='font-size: 0.9em; color: #666; margin-top: 5px;'>" . htmlspecialchars($descripcion) . "</div>";
            }
            echo "</div>";
            echo "<div style='margin-top: 5px;'>";
            echo "<label style='margin-right: 10px;'><input type='radio' name='nivel_" . htmlspecialchars($tecnologia) . "' value='basico'> Básico</label>";
            echo "<label style='margin-right: 10px;'><input type='radio' name='nivel_" . htmlspecialchars($tecnologia) . "' value='medio'> Intermedio</label>";
            echo "<label style='margin-right: 10px;'><input type='radio' name='nivel_" . htmlspecialchars($tecnologia) . "' value='avanzado'> Avanzado</label>";
            echo "<label><input type='radio' name='nivel_" . htmlspecialchars($tecnologia) . "' value='ninguno' checked> No aplica</label>";
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>"; // Fin del grid
        echo "</div>"; // Fin de la categoría
    }
    
    echo "</div>"; // Fin de la simulación

} catch (Exception $e) {
    echo "<p class='error'>Error en la simulación del formulario: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "</body></html>";
?>
