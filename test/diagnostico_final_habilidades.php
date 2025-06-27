<?php
// Script para diagnosticar la inserción de habilidades en la tabla candidato_habilidades
// Versión mejorada sin dependencias problemáticas
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
    <title>Diagnóstico Final - Habilidades de Candidato</title>
    <meta charset='UTF-8'>
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
    <h1>Diagnóstico Final - Habilidades de Candidato</h1>";

// Obtener el ID de candidato para probar
$candidatoId = isset($_GET['candidato_id']) ? intval($_GET['candidato_id']) : 1;
$forzarInsercion = isset($_GET['forzar']) && $_GET['forzar'] == '1';

echo "<div class='info'>
    <p><strong>ID de Candidato para pruebas:</strong> $candidatoId</p>";

if ($forzarInsercion) {
    echo "<p><strong>Modo:</strong> Forzar inserción (incluso si ya existen registros)</p>";
} else {
    echo "<p><strong>Modo:</strong> Normal (solo insertar si no existen)</p>";
}

echo "</div>

<form method='GET' style='background:#f9f9f9; padding:15px; border-radius:5px;'>
    <h3>Cambiar ID de Candidato:</h3>
    <div>
        <input type='number' name='candidato_id' value='$candidatoId' min='1'>
        <label><input type='checkbox' name='forzar' value='1' " . ($forzarInsercion ? 'checked' : '') . "> Forzar inserción (incluso si ya existen registros)</label>
        <button type='submit'>Cambiar</button>
    </div>
</form>";

echo "<div class='section'>
    <h2>1. Verificación de la tabla candidato_habilidades</h2>";

// Verificar acceso con cliente Supabase
try {
    $client = getSupabaseClient();
    $response = $client->request("/rest/v1/candidato_habilidades?limit=1");
    
    if (isset($response->error)) {
        echo "<p class='error'>Error al acceder a la tabla con cliente Supabase: " . json_encode($response->error) . "</p>";
    } else {
        echo "<p class='success'>La tabla candidato_habilidades es accesible con cliente Supabase.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error al verificar con cliente Supabase: " . $e->getMessage() . "</p>";
}

// Verificar con curl directo
echo "<h3>Verificación directa con curl</h3>";
$url = SUPABASE_URL . "/rest/v1/candidato_habilidades?limit=5";
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

echo "<p>Verificación directa con curl - HTTP Code: $httpCode</p>";

if ($httpCode == 200) {
    echo "<p class='success'>La tabla es accesible con curl directo.</p>";
} else {
    echo "<p class='error'>Error al acceder a la tabla con curl. Código: $httpCode</p>";
}

// Verificar registros existentes para el candidato
echo "<h3>Registros existentes para el candidato ID $candidatoId</h3>";
$url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $registros = json_decode($response, true);
    
    if (!empty($registros)) {
        echo "<p class='info'>Se encontraron " . count($registros) . " habilidades asociadas al candidato ID $candidatoId</p>";
        
        echo "<table>
            <tr>
                <th>ID</th>
                <th>Habilidad ID</th>
                <th>Nivel</th>
                <th>Años Experiencia</th>
                <th>Nombre Habilidad</th>
            </tr>";
        
        foreach ($registros as $registro) {
            // Obtener nombre de la habilidad
            $habilidadId = $registro['habilidad_id'];
            $urlHab = SUPABASE_URL . "/rest/v1/habilidades?id=eq.$habilidadId";
            
            $ch = curl_init($urlHab);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $responseHab = curl_exec($ch);
            curl_close($ch);
            
            $habilidadData = json_decode($responseHab, true);
            $nombreHabilidad = "(Desconocida)";
            
            if (!empty($habilidadData) && isset($habilidadData[0]['nombre'])) {
                $nombreHabilidad = $habilidadData[0]['nombre'];
            }
            
            echo "<tr>
                <td>" . $registro['id'] . "</td>
                <td>" . $habilidadId . "</td>
                <td>" . $registro['nivel'] . "</td>
                <td>" . ($registro['anios_experiencia'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($nombreHabilidad) . "</td>
            </tr>";
        }
        
        echo "</table>";
        
        if ($forzarInsercion) {
            echo "<p class='warning'>En modo forzado: Se eliminarán estos registros antes de las pruebas.</p>";
            
            // Eliminar registros si estamos en modo forzado
            $urlDelete = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";
            $headersDelete = $headers;
            
            $ch = curl_init($urlDelete);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headersDelete);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            $responseDelete = curl_exec($ch);
            $httpCodeDelete = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCodeDelete >= 200 && $httpCodeDelete < 300) {
                echo "<p class='success'>Registros eliminados correctamente para la prueba.</p>";
            } else {
                echo "<p class='error'>Error al eliminar registros: HTTP Code $httpCodeDelete</p>";
            }
        }
    } else {
        echo "<p class='info'>No hay habilidades asociadas al candidato ID $candidatoId</p>";
    }
} else {
    echo "<p class='error'>Error al verificar habilidades del candidato: HTTP Code $httpCode</p>";
}

echo "</div>";

// 2. Verificar si el candidato existe
echo "<div class='section'>
    <h2>2. Prueba de creación y recuperación de habilidades</h2>";

// Habilidades de prueba
$habilidadesPrueba = ['TCP/IP', 'Firewalls', 'Python'];
echo "<p>Habilidades de prueba: " . implode(", ", $habilidadesPrueba) . "</p>";

$habilidadesManager = new Habilidades();

// Verificar cada habilidad
foreach ($habilidadesPrueba as $habilidad) {
    echo "<h3>Verificación de la habilidad: $habilidad</h3>";
    $habilidadId = $habilidadesManager->obtenerIdPorNombre($habilidad);
    
    if ($habilidadId) {
        echo "<p class='success'>Habilidad '$habilidad' encontrada con ID: $habilidadId</p>";
    } else {
        echo "<p class='warning'>Habilidad '$habilidad' no encontrada. Intentando crear...</p>";
        $nuevoId = $habilidadesManager->insertarNuevaHabilidad($habilidad);
        
        if ($nuevoId) {
            echo "<p class='success'>Habilidad '$habilidad' creada con ID: $nuevoId</p>";
        } else {
            echo "<p class='error'>No se pudo crear la habilidad '$habilidad'</p>";
        }
    }
}

echo "</div>";

// 3. Prueba de inserción de habilidades
echo "<div class='section'>
    <h2>3. Prueba de inserción de habilidades</h2>";

// Verificar que el candidato exista
$urlCandidato = SUPABASE_URL . "/rest/v1/candidatos?id=eq.$candidatoId";
$ch = curl_init($urlCandidato);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$responseCandidato = curl_exec($ch);
$httpCodeCandidato = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$candidatoExiste = false;
$datosCandidato = null;

if ($httpCodeCandidato == 200) {
    $datosCandidato = json_decode($responseCandidato, true);
    if (!empty($datosCandidato)) {
        $candidatoExiste = true;
        echo "<p class='success'>Candidato ID $candidatoId encontrado en la base de datos.</p>";
    } else {
        echo "<p class='error'>No existe un candidato con ID $candidatoId en la base de datos.</p>";
        echo "<p>Para continuar con las pruebas, ingresa un ID de candidato válido.</p>";
    }
} else {
    echo "<p class='error'>Error al verificar la existencia del candidato: HTTP Code $httpCodeCandidato</p>";
}

if ($candidatoExiste) {
    // Probar la inserción de habilidades una por una
    foreach ($habilidadesPrueba as $index => $habilidad) {
        $nivel = ($index % 3 == 0) ? 'bueno' : (($index % 3 == 1) ? 'regular' : 'malo');
        
        echo "<h3>Inserción individual para '$habilidad'</h3>";
        
        // Método 1: Inserción directa con supabaseInsert
        echo "<h4>Método 1: Inserción directa con supabaseInsert</h4>";
        
        $habilidadId = $habilidadesManager->obtenerIdPorNombre($habilidad);
        
        if (!$habilidadId) {
            echo "<p class='error'>No se pudo obtener ID para la habilidad '$habilidad'</p>";
            continue;
        }
        
        $data = [
            'candidato_id' => $candidatoId,
            'habilidad_id' => $habilidadId,
            'nivel' => $nivel,
            'anios_experiencia' => 1
        ];
        
        try {
            $resultado = supabaseInsert('candidato_habilidades', $data);
            
            if (isset($resultado['error'])) {
                echo "<p class='error'>Error con supabaseInsert: " . json_encode($resultado['error']) . "</p>";
            } else {
                echo "<p class='success'>Éxito con supabaseInsert: Habilidad '$habilidad' insertada correctamente</p>";
                continue; // Pasar a la siguiente habilidad
            }
        } catch (Exception $e) {
            echo "<p class='error'>Excepción con supabaseInsert: " . $e->getMessage() . "</p>";
        }
        
        // Método 2: Inserción con curl directo
        echo "<h4>Método 2: Inserción con curl directo</h4>";
        
        try {
            // Primero eliminar si ya existe
            $urlDelete = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
            $ch = curl_init($urlDelete);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_exec($ch);
            curl_close($ch);
            
            // Ahora insertar
            $urlInsert = SUPABASE_URL . "/rest/v1/candidato_habilidades";
            $ch = curl_init($urlInsert);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $responseInsert = curl_exec($ch);
            $httpCodeInsert = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCodeInsert >= 200 && $httpCodeInsert < 300) {
                echo "<p class='success'>Éxito con curl directo: HTTP Code $httpCodeInsert</p>";
            } else {
                echo "<p class='error'>Error con curl directo: HTTP Code $httpCodeInsert, Respuesta: $responseInsert</p>";
                
                // Método 3: Usar el método de la clase Habilidades
                echo "<h4>Método 3: Usando clase Habilidades</h4>";
                
                try {
                    $resultado = $habilidadesManager->guardarHabilidadCandidato($candidatoId, $habilidad, $nivel);
                    
                    if ($resultado) {
                        echo "<p class='success'>Éxito usando la clase Habilidades</p>";
                    } else {
                        echo "<p class='error'>Error usando la clase Habilidades</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>Excepción usando la clase Habilidades: " . $e->getMessage() . "</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>Excepción con curl directo: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar las habilidades insertadas
    echo "<h3>Verificación final de habilidades insertadas</h3>";
    
    $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $registros = json_decode($response, true);
        
        if (!empty($registros)) {
            echo "<p class='success'>Se encontraron " . count($registros) . " habilidades asociadas al candidato ID $candidatoId</p>";
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Habilidad ID</th>
                    <th>Nivel</th>
                    <th>Nombre Habilidad</th>
                </tr>";
            
            foreach ($registros as $registro) {
                // Obtener nombre de la habilidad
                $habilidadId = $registro['habilidad_id'];
                $urlHab = SUPABASE_URL . "/rest/v1/habilidades?id=eq.$habilidadId";
                
                $ch = curl_init($urlHab);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $responseHab = curl_exec($ch);
                curl_close($ch);
                
                $habilidadData = json_decode($responseHab, true);
                $nombreHabilidad = "(Desconocida)";
                
                if (!empty($habilidadData) && isset($habilidadData[0]['nombre'])) {
                    $nombreHabilidad = $habilidadData[0]['nombre'];
                }
                
                echo "<tr>
                    <td>" . $registro['id'] . "</td>
                    <td>" . $habilidadId . "</td>
                    <td>" . $registro['nivel'] . "</td>
                    <td>" . htmlspecialchars($nombreHabilidad) . "</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p class='error'>No se encontraron habilidades asociadas al candidato después de las pruebas.</p>";
        }
    } else {
        echo "<p class='error'>Error al verificar las habilidades: HTTP Code $httpCode</p>";
    }
}

echo "</div>

<div>
    <p>
        <a href='index.php' style='display: inline-block; margin: 10px; padding: 8px 15px; background-color: #607D8B; color: white; text-decoration: none; border-radius: 5px;'>Volver al Inicio</a>
        <a href='simulador_registro_habilidades.php' style='display: inline-block; margin: 10px; padding: 8px 15px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Ir al Simulador de Registro</a>
    </p>
</div>
</body>
</html>";
?>
