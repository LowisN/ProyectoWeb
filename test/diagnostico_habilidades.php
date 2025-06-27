<?php
// Script para diagnosticar específicamente la tabla habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';

echo "<h1>Diagnóstico de Tabla Habilidades</h1>";

function testSupabaseFetch() {
    echo "<h2>Método 1: supabaseFetch</h2>";
    $resultado = supabaseFetch('habilidades', '*');
    if (isset($resultado['error'])) {
        echo "<p style='color: red;'>Error: " . json_encode($resultado['error']) . "</p>";
    } else {
        echo "<p style='color: green;'>Éxito! Encontradas " . count($resultado) . " habilidades</p>";
        echo "<pre>" . print_r(array_slice($resultado, 0, 5), true) . "</pre>";
    }
}

function testSupabaseClient() {
    echo "<h2>Método 2: SupabaseClient</h2>";
    try {
        $client = getSupabaseClient();
        $response = $client->from('habilidades')->select('*')->execute();
        
        if (isset($response->error)) {
            echo "<p style='color: red;'>Error: " . json_encode($response->error) . "</p>";
        } else {
            $data = $response->data ?? [];
            echo "<p style='color: green;'>Éxito! Encontradas " . count($data) . " habilidades</p>";
            echo "<pre>" . print_r(array_slice($data, 0, 5), true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Excepción: " . $e->getMessage() . "</p>";
    }
}

function testDirectCurl() {
    echo "<h2>Método 3: Petición CURL directa</h2>";
    $url = SUPABASE_URL . "/rest/v1/habilidades?select=*&limit=10";
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
    
    echo "<p>HTTP Code: $httpCode</p>";
    if ($httpCode === 200) {
        $data = json_decode($response);
        echo "<p style='color: green;'>Éxito! Encontradas " . count($data) . " habilidades</p>";
        echo "<pre>" . print_r(array_slice($data, 0, 5), true) . "</pre>";
    } else {
        echo "<p style='color: red;'>Error: $response</p>";
    }
}

function testDatabaseStructure() {
    echo "<h2>Método 4: Verificar estructura de tabla</h2>";
    $result = verifyTable('habilidades');
    echo "<pre>" . print_r($result, true) . "</pre>";
}

function testHabilidadesClass() {
    echo "<h2>Método 5: Clase Habilidades</h2>";
    try {
        require_once 'models/habilidades.php';
        $manager = new Habilidades();
        $habilidades = $manager->obtenerTodasHabilidades();
        
        echo "<p style='color: green;'>Éxito! Encontradas " . count($habilidades) . " habilidades</p>";
        echo "<pre>" . print_r(array_slice($habilidades, 0, 5), true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Excepción: " . $e->getMessage() . "</p>";
    }
}

// Ejecutar pruebas
echo "<div style='margin: 20px; padding: 20px; border: 1px solid #ddd;'>";
echo "<h2>Información de Supabase</h2>";
echo "<p>URL: " . SUPABASE_URL . "</p>";
echo "<p>KEY: " . substr(SUPABASE_KEY, 0, 10) . "..." . substr(SUPABASE_KEY, -10) . "</p>";
echo "</div>";

testSupabaseFetch();
testSupabaseClient();
testDirectCurl();
testDatabaseStructure();
testHabilidadesClass();

echo "<hr>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
