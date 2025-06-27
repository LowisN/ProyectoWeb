<?php
// Script para diagnosticar las categorías de habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

echo "<h1>Diagnóstico de Categorías de Habilidades</h1>";

// Verificar categorías en la tabla
echo "<h2>1. Categorías disponibles en la tabla</h2>";
try {
    $client = getSupabaseClient();
    $response = $client->request("/rest/v1/habilidades?select=categoria&distinct=true");
    
    echo "<h3>Usando SupabaseClient:</h3>";
    if (isset($response->error)) {
        echo "<p style='color: red;'>Error: " . json_encode($response->error) . "</p>";
    } else {
        $categorias = [];
        if (isset($response->data) && is_array($response->data)) {
            foreach ($response->data as $item) {
                $categorias[] = $item->categoria;
            }
        } else if (is_array($response)) {
            foreach ($response as $item) {
                $item = (object)$item;
                $categorias[] = $item->categoria;
            }
        }
        
        if (empty($categorias)) {
            echo "<p style='color: orange;'>No se encontraron categorías usando este método.</p>";
        } else {
            echo "<p style='color: green;'>Categorías encontradas: " . count($categorias) . "</p>";
            echo "<ul>";
            foreach ($categorias as $cat) {
                echo "<li>$cat</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Intentar con SQL directo
echo "<h3>Usando SQL directo:</h3>";
try {
    $sql = "SELECT DISTINCT categoria FROM habilidades";
    $response = supabaseRequest("/rest/v1/rpc/execute_sql", "POST", [
        "query" => $sql
    ]);
    
    if (isset($response['error'])) {
        echo "<p style='color: orange;'>El método RPC execute_sql no está disponible: " . $response['error'] . "</p>";
    } else {
        echo "<p style='color: green;'>Resultado de SQL:</p>";
        echo "<pre>" . print_r($response, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Probar con supabaseFetch
echo "<h3>Usando supabaseFetch:</h3>";
$fetch_result = supabaseFetch('habilidades', 'categoria', [], true);
$categorias = [];

if (is_array($fetch_result)) {
    foreach ($fetch_result as $item) {
        if (isset($item['categoria']) && !in_array($item['categoria'], $categorias)) {
            $categorias[] = $item['categoria'];
        }
    }
    
    echo "<p style='color: green;'>Categorías únicas encontradas: " . count($categorias) . "</p>";
    echo "<ul>";
    foreach ($categorias as $cat) {
        echo "<li>$cat</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Error o resultado vacío</p>";
}

// Probar la clase Habilidades
echo "<h2>2. Prueba de la clase Habilidades</h2>";
try {
    $manager = new Habilidades();
    
    echo "<h3>Categorías únicas:</h3>";
    $categorias = $manager->obtenerCategoriasUnicas();
    if (empty($categorias)) {
        echo "<p style='color: orange;'>No se encontraron categorías.</p>";
    } else {
        echo "<p style='color: green;'>Categorías encontradas: " . count($categorias) . "</p>";
        echo "<ul>";
        foreach ($categorias as $cat) {
            echo "<li>$cat</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>Habilidades por categoría:</h3>";
    $porCategoria = $manager->obtenerHabilidadesPorCategoria();
    if (empty($porCategoria)) {
        echo "<p style='color: orange;'>No se encontraron habilidades por categoría.</p>";
    } else {
        echo "<p style='color: green;'>Categorías con habilidades: " . count($porCategoria) . "</p>";
        
        foreach ($porCategoria as $categoria => $habilidades) {
            echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
            echo "<h4>$categoria (" . count($habilidades) . " habilidades)</h4>";
            
            if (!empty($habilidades)) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
                
                $count = 0;
                foreach ($habilidades as $habilidad) {
                    if ($count >= 5) { // Mostrar solo 5 habilidades por categoría
                        break;
                    }
                    
                    $id = is_object($habilidad) ? ($habilidad->id ?? 'N/A') : (is_array($habilidad) ? ($habilidad['id'] ?? 'N/A') : 'N/A');
                    $nombre = is_object($habilidad) ? ($habilidad->nombre ?? 'N/A') : (is_array($habilidad) ? ($habilidad['nombre'] ?? 'N/A') : $habilidad);
                    $desc = is_object($habilidad) ? ($habilidad->descripcion ?? '') : (is_array($habilidad) ? ($habilidad['descripcion'] ?? '') : '');
                    
                    echo "<tr>";
                    echo "<td>$id</td>";
                    echo "<td>$nombre</td>";
                    echo "<td>$desc</td>";
                    echo "</tr>";
                    
                    $count++;
                }
                
                if (count($habilidades) > 5) {
                    echo "<tr><td colspan='3'>... y " . (count($habilidades) - 5) . " más</td></tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No hay habilidades en esta categoría.</p>";
            }
            
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='diagnostico_tablas_habilidades.php'>Ver diagnóstico general</a></p>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
