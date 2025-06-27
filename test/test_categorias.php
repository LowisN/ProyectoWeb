<?php
// Script para probar específicamente la obtención de categorías
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
    <title>Test de Categorías de Habilidades</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Test de Categorías de Habilidades</h1>";

// Crear instancia de la clase Habilidades
try {
    $habilidadesManager = new Habilidades();
    
    // Obtener categorías únicas
    echo "<h2>1. Obtener categorías únicas</h2>";
    $categorias = $habilidadesManager->obtenerCategoriasUnicas();
    
    if (!empty($categorias)) {
        echo "<p class='success'>Se encontraron " . count($categorias) . " categorías únicas:</p>";
        echo "<ul>";
        foreach ($categorias as $categoria) {
            echo "<li>" . htmlspecialchars($categoria) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No se encontraron categorías.</p>";
    }
    
    // Obtener habilidades por categoría
    echo "<h2>2. Habilidades agrupadas por categoría</h2>";
    $habilidadesPorCategoria = $habilidadesManager->obtenerHabilidadesPorCategoria();
    
    if (!empty($habilidadesPorCategoria)) {
        echo "<p class='success'>Se encontraron " . count($habilidadesPorCategoria) . " categorías con habilidades:</p>";
        
        echo "<table>";
        echo "<tr><th>Categoría</th><th>Cantidad</th><th>Habilidades</th></tr>";
        
        foreach ($habilidadesPorCategoria as $categoria => $habilidades) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($categoria) . "</td>";
            echo "<td>" . count($habilidades) . "</td>";
            
            echo "<td>";
            $nombres = [];
            foreach ($habilidades as $habilidad) {
                if (is_object($habilidad)) {
                    if (isset($habilidad->nombre)) {
                        $nombres[] = htmlspecialchars($habilidad->nombre);
                    }
                } elseif (is_array($habilidad)) {
                    if (isset($habilidad['nombre'])) {
                        $nombres[] = htmlspecialchars($habilidad['nombre']);
                    }
                } else {
                    $nombres[] = htmlspecialchars((string)$habilidad);
                }
            }
            
            // Limitar la cantidad mostrada
            if (count($nombres) > 5) {
                echo implode(", ", array_slice($nombres, 0, 5)) . ", ... (y " . (count($nombres) - 5) . " más)";
            } else {
                echo implode(", ", $nombres);
            }
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='error'>No se encontraron habilidades por categoría.</p>";
    }
    
    // Obtener todas las habilidades (sin agrupar)
    echo "<h2>3. Lista completa de habilidades con categoría</h2>";
    $todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();
    
    if (!empty($todasHabilidades)) {
        echo "<p class='success'>Se encontraron " . count($todasHabilidades) . " habilidades en total:</p>";
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Categoría</th><th>Descripción</th></tr>";
        
        // Ordenar habilidades por categoría y luego por nombre
        usort($todasHabilidades, function($a, $b) {
            $catA = is_object($a) ? ($a->categoria ?? '') : ($a['categoria'] ?? '');
            $catB = is_object($b) ? ($b->categoria ?? '') : ($b['categoria'] ?? '');
            
            if ($catA === $catB) {
                $nameA = is_object($a) ? ($a->nombre ?? '') : ($a['nombre'] ?? '');
                $nameB = is_object($b) ? ($b->nombre ?? '') : ($b['nombre'] ?? '');
                return strcmp($nameA, $nameB);
            }
            
            return strcmp($catA, $catB);
        });
        
        // Mostrar solo las primeras 30 habilidades para no sobrecargar la página
        $limit = min(30, count($todasHabilidades));
        for ($i = 0; $i < $limit; $i++) {
            $habilidad = $todasHabilidades[$i];
            
            $id = is_object($habilidad) ? ($habilidad->id ?? 'N/A') : ($habilidad['id'] ?? 'N/A');
            $nombre = is_object($habilidad) ? ($habilidad->nombre ?? 'N/A') : ($habilidad['nombre'] ?? 'N/A');
            $categoria = is_object($habilidad) ? ($habilidad->categoria ?? 'N/A') : ($habilidad['categoria'] ?? 'N/A');
            $descripcion = is_object($habilidad) ? ($habilidad->descripcion ?? '') : ($habilidad['descripcion'] ?? '');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($id) . "</td>";
            echo "<td>" . htmlspecialchars($nombre) . "</td>";
            echo "<td>" . htmlspecialchars($categoria) . "</td>";
            echo "<td>" . htmlspecialchars(substr($descripcion, 0, 100)) . (strlen($descripcion) > 100 ? '...' : '') . "</td>";
            echo "</tr>";
        }
        
        if (count($todasHabilidades) > $limit) {
            echo "<tr><td colspan='4'>... y " . (count($todasHabilidades) - $limit) . " más</td></tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='error'>No se encontraron habilidades.</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "</body></html>";
?>
