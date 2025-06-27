<?php
// Depuración en tiempo real de categorías y habilidades
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Crear instancia de la clase Habilidades
$manager = new Habilidades();

echo "<h1>Depuración de Categorías y Habilidades</h1>";

// 1. Categorías únicas
echo "<h2>Categorías Únicas</h2>";
$categorias = $manager->obtenerCategoriasUnicas();
var_dump($categorias);

// 2. Habilidades por categoría
echo "<h2>Habilidades por Categoría</h2>";
$porCategoria = $manager->obtenerHabilidadesPorCategoria();

foreach ($porCategoria as $categoria => $habilidades) {
    echo "<h3>$categoria (" . count($habilidades) . ")</h3>";
    echo "<ul>";
    foreach ($habilidades as $hab) {
        $nombre = is_object($hab) ? ($hab->nombre ?? 'N/A') : ($hab['nombre'] ?? 'N/A');
        echo "<li>$nombre</li>";
    }
    echo "</ul>";
}

// 3. Datos de habilidades crudos
echo "<h2>Datos crudos de habilidades</h2>";
$todas = $manager->obtenerTodasHabilidades();
echo "Total: " . count($todas) . "<br>";
var_dump(array_slice($todas, 0, 5));
