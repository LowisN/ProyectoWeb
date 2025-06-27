<?php
// Script para verificar la inserción de habilidades desde el formulario
session_start();
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
    <title>Verificador de Registro de Habilidades</title>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; background-color: #e8f5e9; padding: 10px; margin: 5px 0; border-left: 4px solid green; }
        .error { color: #d32f2f; background-color: #ffebee; padding: 10px; margin: 5px 0; border-left: 4px solid #d32f2f; }
        .warning { color: #ff6f00; background-color: #fff8e1; padding: 10px; margin: 5px 0; border-left: 4px solid #ff6f00; }
        .info { color: #0288d1; background-color: #e1f5fe; padding: 10px; margin: 5px 0; border-left: 4px solid #0288d1; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin-top: 25px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h1, h2, h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        button, input, select { padding: 8px; margin: 5px; }
        form { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Verificador de Registro de Habilidades</h1>";

// Formulario para simular el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='section'>
        <h2>Procesando Datos de Formulario</h2>";
    
    $habilidades = [];
    $candidatoId = isset($_POST['candidato_id']) ? intval($_POST['candidato_id']) : 0;
    
    // Recopilar habilidades seleccionadas del formulario
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'habilidad_') === 0 && $value !== 'ninguno') {
            $habilidadNombre = substr($key, 10);  // Quitar "habilidad_" del nombre
            $habilidadNombre = str_replace('_', ' ', $habilidadNombre);
            $habilidades[$habilidadNombre] = $value;
        }
    }
    
    echo "<p>Candidato ID: " . $candidatoId . "</p>";
    echo "<p>Total de habilidades seleccionadas: " . count($habilidades) . "</p>";
    
    if (count($habilidades) > 0) {
        echo "<table>
            <tr>
                <th>Habilidad</th>
                <th>Nivel</th>
            </tr>";
        
        foreach ($habilidades as $habilidad => $nivel) {
            echo "<tr>
                <td>" . htmlspecialchars($habilidad) . "</td>
                <td>" . htmlspecialchars($nivel) . "</td>
            </tr>";
        }
        
        echo "</table>";
        
        // Intentar insertar las habilidades
        if ($candidatoId > 0) {
            echo "<h3>Intentando insertar habilidades...</h3>";
            
            $habilidadesManager = new Habilidades();
            $resultado = $habilidadesManager->insertarHabilidadesCandidato($candidatoId, $habilidades);
            
            echo "<p>Resultado de inserción:</p>";
            echo "<pre>" . print_r($resultado, true) . "</pre>";
            
            if ($resultado['exitos'] > 0) {
                echo "<div class='success'>Se insertaron " . $resultado['exitos'] . " habilidades correctamente.</div>";
            }
            if ($resultado['errores'] > 0) {
                echo "<div class='error'>Hubo " . $resultado['errores'] . " errores al insertar habilidades.</div>";
            }
            
            // Verificar las habilidades insertadas
            echo "<h3>Verificando habilidades insertadas...</h3>";
            
            $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";
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
                $registros = json_decode($response, true);
                
                if (!empty($registros)) {
                    echo "<div class='success'>Se encontraron " . count($registros) . " habilidades asociadas al candidato.</div>";
                    
                    // Mostrar detalles de cada habilidad
                    echo "<table>
                        <tr>
                            <th>ID</th>
                            <th>Habilidad ID</th>
                            <th>Nivel</th>
                            <th>Nombre Habilidad</th>
                        </tr>";
                    
                    foreach ($registros as $registro) {
                        $habilidadId = $registro['habilidad_id'];
                        $nombreHabilidad = "(Desconocido)";
                        
                        // Obtener el nombre de la habilidad
                        $urlHabilidad = SUPABASE_URL . "/rest/v1/habilidades?id=eq.$habilidadId";
                        $ch = curl_init($urlHabilidad);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        $responseHab = curl_exec($ch);
                        curl_close($ch);
                        
                        $habilidadData = json_decode($responseHab, true);
                        if (!empty($habilidadData)) {
                            $nombreHabilidad = $habilidadData[0]['nombre'] ?? "(Desconocido)";
                        }
                        
                        echo "<tr>
                            <td>" . ($registro['id'] ?? 'N/A') . "</td>
                            <td>" . $habilidadId . "</td>
                            <td>" . $registro['nivel'] . "</td>
                            <td>" . $nombreHabilidad . "</td>
                        </tr>";
                    }
                    
                    echo "</table>";
                } else {
                    echo "<div class='error'>No se encontraron habilidades asociadas al candidato después de la inserción.</div>";
                }
            } else {
                echo "<div class='error'>Error al verificar las habilidades insertadas. HTTP Code: $httpCode</div>";
            }
        } else {
            echo "<div class='error'>Se requiere un ID de candidato válido para insertar habilidades.</div>";
        }
    } else {
        echo "<div class='warning'>No se seleccionaron habilidades.</div>";
    }
    
    echo "</div>";
}

// Mostrar el formulario de simulación
echo "<div class='section'>
    <h2>Simulador de Registro de Habilidades</h2>
    
    <form method='POST'>
        <div>
            <label for='candidato_id'>ID de Candidato:</label>
            <input type='number' id='candidato_id' name='candidato_id' required min='1' value='1'>
            <small>Ingresa un ID de candidato existente en la base de datos</small>
        </div>
        
        <h3>Habilidades</h3>";

// Obtener lista de habilidades para el simulador
$habilidadesDemo = [
    'Lenguajes de Programación' => ['Python', 'Java', 'JavaScript', 'PHP', 'C++'],
    'Redes' => ['TCP/IP', 'DNS', 'DHCP', 'Firewalls', 'VPN'],
    'Bases de Datos' => ['MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'Oracle'],
    'Cloud' => ['AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes'],
    'Seguridad' => ['Cryptography', 'Network Security', 'Authentication', 'Penetration Testing']
];

foreach ($habilidadesDemo as $categoria => $habilidades) {
    echo "<fieldset>
        <legend>$categoria</legend>";
    
    foreach ($habilidades as $habilidad) {
        $id = 'habilidad_' . str_replace([' ', '/'], '_', strtolower($habilidad));
        
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #eee;'>
            <p><strong>$habilidad</strong></p>
            <label>
                <input type='radio' name='$id' value='malo'> 
                Básico
            </label>
            <label>
                <input type='radio' name='$id' value='regular'> 
                Intermedio
            </label>
            <label>
                <input type='radio' name='$id' value='bueno'> 
                Avanzado
            </label>
            <label>
                <input type='radio' name='$id' value='ninguno' checked> 
                No aplica
            </label>
        </div>";
    }
    
    echo "</fieldset>";
}

echo "        <div style='margin-top: 20px;'>
            <button type='submit' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px;'>
                Simular Registro
            </button>
        </div>
    </form>
</div>

<div>
    <p>
        <a href='diagnostico_candidato_habilidades_mejorado.php' style='display: inline-block; margin: 10px; padding: 8px 15px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Ver Diagnóstico Mejorado</a>
        <a href='index.php' style='display: inline-block; margin: 10px; padding: 8px 15px; background-color: #607D8B; color: white; text-decoration: none; border-radius: 5px;'>Volver al Inicio</a>
    </p>
</div>
</body>
</html>";
?>
