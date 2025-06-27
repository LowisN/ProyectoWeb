<?php
// Script mejorado para diagnosticar y corregir problemas de inserción de habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

// Configurar visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para log adicional al archivo y pantalla
function log_info($message, $type = 'info') {
    $class = 'info';
    switch($type) {
        case 'error':
            $class = 'error';
            error_log($message);
            break;
        case 'success':
            $class = 'success';
            break;
        case 'warning':
            $class = 'warning';
            break;
    }
    echo "<div class='{$class}'>{$message}</div>";
}

// Obtener ID de candidato (del parámetro GET o usar uno predeterminado para pruebas)
$candidatoId = isset($_GET['candidato_id']) ? intval($_GET['candidato_id']) : 1; // Usar 1 como valor predeterminado
$forceInsert = isset($_GET['force']) && $_GET['force'] === '1';

// Output HTML head
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Mejorado - Habilidades de Candidato</title>
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
        .bold { font-weight: bold; }
        .centered { text-align: center; }
    </style>
</head>
<body>
    <h1>Diagnóstico Mejorado - Habilidades de Candidato</h1>
    <div class='info'>
        <p><strong>ID de Candidato para pruebas:</strong> {$candidatoId}</p>
        <form method='GET'>
            <label for='candidato_id'>Cambiar ID de Candidato:</label>
            <input type='number' id='candidato_id' name='candidato_id' min='1' value='{$candidatoId}'>
            <label for='force'>
                <input type='checkbox' id='force' name='force' value='1' " . ($forceInsert ? "checked" : "") . "> Forzar inserción (incluso si ya existen registros)
            </label>
            <button type='submit'>Actualizar</button>
        </form>
    </div>";

// 1. Verificar la existencia y estructura de la tabla candidato_habilidades
echo "<div class='section'>
    <h2>1. Verificación de la tabla candidato_habilidades</h2>";

try {
    // Usar primero el cliente Supabase
    $client = getSupabaseClient();
    $response = $client->request("/rest/v1/candidato_habilidades?limit=1");
    
    $tableExists = true;
    if (isset($response->error)) {
        log_info("Error al acceder a la tabla con cliente Supabase: " . json_encode($response->error), 'error');
        $tableExists = false;
    } else {
        log_info("La tabla candidato_habilidades es accesible con cliente Supabase.", 'success');
    }
    
    // Verificar también con curl directo para mayor seguridad
    $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?limit=1";
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
    
    log_info("Verificación directa con curl - HTTP Code: $httpCode", $httpCode === 200 ? 'success' : 'warning');
    
    // Si la tabla parece no existir, intentar verificar las tablas disponibles
    if (!$tableExists || $httpCode !== 200) {
        log_info("La tabla candidato_habilidades parece no existir o no ser accesible. Verificando tablas disponibles...", 'warning');
        
        // Obtener lista de tablas
        $urlTables = SUPABASE_URL . "/rest/v1/";
        $ch = curl_init($urlTables);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $tables = json_decode($response, true);
            log_info("Tablas disponibles: " . implode(", ", array_keys($tables)), 'info');
            
            // Sugerir nombres alternativos si parece que la tabla tiene otro nombre
            $possibleMatches = array_filter(array_keys($tables), function($tableName) {
                return strpos($tableName, 'candidato') !== false && strpos($tableName, 'hab') !== false;
            });
            
            if (!empty($possibleMatches)) {
                log_info("Posibles coincidencias para la tabla candidato_habilidades: " . implode(", ", $possibleMatches), 'warning');
            }
        } else {
            log_info("No se pudo obtener la lista de tablas: HTTP Code $httpCode", 'error');
        }
    }
    
    // Verificar registros existentes para el candidato específico
    echo "<h3>Registros existentes para el candidato ID {$candidatoId}</h3>";
    $urlCandidato = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";
    $ch = curl_init($urlCandidato);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $registros = json_decode($response, true);
        if (!empty($registros)) {
            log_info("Se encontraron " . count($registros) . " habilidades asociadas al candidato ID {$candidatoId}", 'success');
            
            // Mostrar registros existentes
            echo "<table>
                <tr>
                    <th>Habilidad ID</th>
                    <th>Nivel</th>
                    <th>Años Experiencia</th>
                </tr>";
            
            foreach ($registros as $registro) {
                echo "<tr>
                    <td>{$registro['habilidad_id']}</td>
                    <td>{$registro['nivel']}</td>
                    <td>" . ($registro['anios_experiencia'] ?? 'N/A') . "</td>
                </tr>";
            }
            
            echo "</table>";
            
            // Intentar obtener los nombres de las habilidades
            echo "<h4>Detalles de las habilidades:</h4>";
            $habilidadesManager = new Habilidades();
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                </tr>";
            
            foreach ($registros as $registro) {
                $habilidadId = $registro['habilidad_id'];
                $nombreHabilidad = '(Desconocido)';
                $categoria = '(Desconocida)';
                
                // Intentar obtener datos de la habilidad
                $urlHabilidad = SUPABASE_URL . "/rest/v1/habilidades?id=eq.$habilidadId";
                $ch = curl_init($urlHabilidad);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $responseHab = curl_exec($ch);
                curl_close($ch);
                
                $habilidadData = json_decode($responseHab, true);
                if (!empty($habilidadData)) {
                    $nombreHabilidad = $habilidadData[0]['nombre'] ?? '(Desconocido)';
                    $categoria = $habilidadData[0]['categoria'] ?? '(Desconocida)';
                }
                
                echo "<tr>
                    <td>{$habilidadId}</td>
                    <td>{$nombreHabilidad}</td>
                    <td>{$categoria}</td>
                </tr>";
            }
            
            echo "</table>";
            
            if (!$forceInsert) {
                log_info("Para probar la inserción de nuevas habilidades aunque ya existan, marca la casilla 'Forzar inserción'", 'info');
            }
        } else {
            log_info("No hay habilidades asociadas al candidato ID {$candidatoId}", 'warning');
        }
    } else {
        log_info("Error al verificar registros del candidato: HTTP Code $httpCode", 'error');
    }
    
} catch (Exception $e) {
    log_info("Excepción al verificar la tabla: " . $e->getMessage(), 'error');
}

echo "</div>";

// 2. Probar la creación y recuperación de habilidades
echo "<div class='section'>
    <h2>2. Prueba de creación y recuperación de habilidades</h2>";

$habilidadesPrueba = [
    'TCP/IP' => 'bueno',
    'Firewalls' => 'regular',
    'Python' => 'malo'
];

log_info("Habilidades de prueba: " . implode(", ", array_keys($habilidadesPrueba)), 'info');

try {
    $habilidadesManager = new Habilidades();
    
    // Verificar cada habilidad
    foreach ($habilidadesPrueba as $nombreHabilidad => $nivel) {
        echo "<h3>Verificación de la habilidad: {$nombreHabilidad}</h3>";
        
        // Obtener ID de la habilidad
        $habilidadId = $habilidadesManager->obtenerIdPorNombre($nombreHabilidad);
        
        if ($habilidadId) {
            log_info("Habilidad '{$nombreHabilidad}' encontrada con ID: {$habilidadId}", 'success');
        } else {
            log_info("Habilidad '{$nombreHabilidad}' no encontrada. Intentando crearla...", 'warning');
            
            $habilidadId = $habilidadesManager->insertarNuevaHabilidad($nombreHabilidad);
            
            if ($habilidadId) {
                log_info("Habilidad '{$nombreHabilidad}' creada exitosamente con ID: {$habilidadId}", 'success');
            } else {
                log_info("Error al crear la habilidad '{$nombreHabilidad}'", 'error');
            }
        }
    }
} catch (Exception $e) {
    log_info("Error en prueba de habilidades: " . $e->getMessage(), 'error');
}

echo "</div>";

// 3. Probar la inserción de habilidades para el candidato
if ($forceInsert || empty($registros)) {
    echo "<div class='section'>
        <h2>3. Prueba de inserción de habilidades</h2>";
    
    try {
        $habilidadesManager = new Habilidades();
        
        // Verificar que el ID de candidato sea válido
        if ($candidatoId <= 0) {
            log_info("ID de candidato inválido: {$candidatoId}. Por favor, proporciona un ID de candidato válido.", 'error');
        } else {
            // Verificar primero si el candidato existe
            $urlVerificarCandidato = SUPABASE_URL . "/rest/v1/candidatos?id=eq.$candidatoId";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY
            ];
            
            $ch = curl_init($urlVerificarCandidato);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $candidatoExiste = false;
            if ($httpCode === 200) {
                $candidatoData = json_decode($response, true);
                if (!empty($candidatoData)) {
                    log_info("Candidato ID {$candidatoId} encontrado en la base de datos.", 'success');
                    $candidatoExiste = true;
                } else {
                    log_info("Advertencia: No se encontró un candidato con ID {$candidatoId} en la base de datos. La inserción puede fallar debido a restricciones de clave foránea.", 'warning');
                }
            } else {
                log_info("Error al verificar la existencia del candidato: HTTP Code $httpCode", 'error');
            }
            
            // Continuar con la inserción si se fuerza o si el candidato existe
            if ($forceInsert || $candidatoExiste) {
                // Intentar insertar cada habilidad por separado primero
                foreach ($habilidadesPrueba as $nombreHabilidad => $nivel) {
                    echo "<h3>Inserción individual para '{$nombreHabilidad}'</h3>";
                    
                    // Obtener el ID de la habilidad
                    $habilidadId = $habilidadesManager->obtenerIdPorNombre($nombreHabilidad);
                    
                    if (!$habilidadId) {
                        log_info("No se pudo obtener ID para '{$nombreHabilidad}'. Creando habilidad...", 'warning');
                        $habilidadId = $habilidadesManager->insertarNuevaHabilidad($nombreHabilidad);
                        
                        if (!$habilidadId) {
                            log_info("Error al crear habilidad '{$nombreHabilidad}'. Omitiendo.", 'error');
                            continue;
                        }
                        
                        log_info("Habilidad '{$nombreHabilidad}' creada con ID: {$habilidadId}", 'success');
                    }
                    
                    // Preparar datos para inserción
                    $habilidadData = [
                        'candidato_id' => intval($candidatoId),
                        'habilidad_id' => intval($habilidadId),
                        'nivel' => $nivel,
                        'anios_experiencia' => 1
                    ];
                    
                    echo "<h4>Método 1: Inserción directa con supabaseInsert</h4>";
                    try {
                        $resultado = supabaseInsert('candidato_habilidades', $habilidadData);
                        
                        if (isset($resultado['error'])) {
                            log_info("Error con supabaseInsert: " . json_encode($resultado['error']), 'error');
                        } else {
                            log_info("Inserción exitosa con supabaseInsert", 'success');
                        }
                    } catch (Exception $e) {
                        log_info("Excepción en supabaseInsert: " . $e->getMessage(), 'error');
                    }
                    
                    echo "<h4>Método 2: Inserción con cliente Supabase</h4>";
                    try {
                        $response = $client->from('candidato_habilidades')->upsert($habilidadData);
                        
                        if (isset($response->error)) {
                            log_info("Error con cliente Supabase: " . json_encode($response->error), 'error');
                        } else {
                            log_info("Inserción exitosa con cliente Supabase", 'success');
                        }
                    } catch (Exception $e) {
                        log_info("Excepción con cliente Supabase: " . $e->getMessage(), 'error');
                    }
                    
                    echo "<h4>Método 3: Inserción directa con curl</h4>";
                    try {
                        $url = SUPABASE_URL . "/rest/v1/candidato_habilidades";
                        $headers = [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . SUPABASE_KEY,
                            'apikey: ' . SUPABASE_KEY,
                            'Prefer: return=minimal'
                        ];
                        
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($habilidadData));
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode >= 200 && $httpCode < 300) {
                            log_info("Inserción exitosa con curl: HTTP Code $httpCode", 'success');
                        } else {
                            log_info("Error con curl: HTTP Code $httpCode, Respuesta: $response", 'error');
                        }
                    } catch (Exception $e) {
                        log_info("Excepción con curl: " . $e->getMessage(), 'error');
                    }
                    
                    echo "<h4>Método 4: Usando método de la clase Habilidades</h4>";
                    try {
                        $resultado = $habilidadesManager->guardarHabilidadCandidato($candidatoId, $nombreHabilidad, $nivel);
                        
                        if ($resultado) {
                            log_info("Inserción exitosa con método de la clase Habilidades", 'success');
                        } else {
                            log_info("Error con método de la clase Habilidades", 'error');
                        }
                    } catch (Exception $e) {
                        log_info("Excepción con método de clase: " . $e->getMessage(), 'error');
                    }
                }
                
                // Usar el método para insertar múltiples habilidades
                echo "<h3>Inserción múltiple</h3>";
                try {
                    $resultado = $habilidadesManager->insertarHabilidadesCandidato($candidatoId, $habilidadesPrueba);
                    
                    echo "<pre>Resultado: " . print_r($resultado, true) . "</pre>";
                    
                    if ($resultado['exitos'] > 0) {
                        log_info("Se insertaron " . $resultado['exitos'] . " habilidades correctamente.", 'success');
                    }
                    if ($resultado['errores'] > 0) {
                        log_info("Se encontraron " . $resultado['errores'] . " errores al insertar habilidades.", 'error');
                    }
                } catch (Exception $e) {
                    log_info("Excepción en inserción múltiple: " . $e->getMessage(), 'error');
                }
            }
        }
    } catch (Exception $e) {
        log_info("Error general: " . $e->getMessage(), 'error');
    }
    
    echo "</div>";
}

// 4. Verificación final
echo "<div class='section'>
    <h2>4. Verificación final de habilidades del candidato</h2>";

try {
    // Verificar registros existentes para el candidato específico después de la inserción
    $urlCandidato = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId";
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_KEY,
        'apikey: ' . SUPABASE_KEY
    ];
    
    $ch = curl_init($urlCandidato);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $registros = json_decode($response, true);
        if (!empty($registros)) {
            log_info("Verificación final: Se encontraron " . count($registros) . " habilidades asociadas al candidato ID {$candidatoId}", 'success');
            
            // Mostrar registros existentes
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Habilidad ID</th>
                    <th>Candidato ID</th>
                    <th>Nivel</th>
                    <th>Años Experiencia</th>
                </tr>";
            
            foreach ($registros as $registro) {
                echo "<tr>
                    <td>" . ($registro['id'] ?? 'N/A') . "</td>
                    <td>{$registro['habilidad_id']}</td>
                    <td>{$registro['candidato_id']}</td>
                    <td>{$registro['nivel']}</td>
                    <td>" . ($registro['anios_experiencia'] ?? 'N/A') . "</td>
                </tr>";
            }
            
            echo "</table>";
            
            // Intentar obtener los nombres de las habilidades
            echo "<h4>Detalles de las habilidades:</h4>";
            $habilidadesManager = new Habilidades();
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Nivel</th>
                </tr>";
            
            foreach ($registros as $registro) {
                $habilidadId = $registro['habilidad_id'];
                $nombreHabilidad = '(Desconocido)';
                $categoria = '(Desconocida)';
                $nivel = $registro['nivel'];
                
                // Intentar obtener datos de la habilidad
                $urlHabilidad = SUPABASE_URL . "/rest/v1/habilidades?id=eq.$habilidadId";
                $ch = curl_init($urlHabilidad);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $responseHab = curl_exec($ch);
                curl_close($ch);
                
                $habilidadData = json_decode($responseHab, true);
                if (!empty($habilidadData)) {
                    $nombreHabilidad = $habilidadData[0]['nombre'] ?? '(Desconocido)';
                    $categoria = $habilidadData[0]['categoria'] ?? '(Desconocida)';
                }
                
                echo "<tr>
                    <td>{$habilidadId}</td>
                    <td>{$nombreHabilidad}</td>
                    <td>{$categoria}</td>
                    <td>{$nivel}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            log_info("No hay habilidades asociadas al candidato ID {$candidatoId} después de los intentos de inserción.", 'warning');
        }
    } else {
        log_info("Error al verificar registros finales del candidato: HTTP Code $httpCode", 'error');
    }
} catch (Exception $e) {
    log_info("Error en verificación final: " . $e->getMessage(), 'error');
}

echo "</div>";

// 5. Recomendaciones y conclusiones
echo "<div class='section'>
    <h2>5. Recomendaciones y diagnóstico</h2>";

if (empty($registros)) {
    echo "<div class='error bold'>
        <p>No se encontraron habilidades asociadas al candidato ID {$candidatoId}.</p>
        <p>Posibles causas:</p>
        <ul>
            <li>La tabla candidato_habilidades no existe o tiene un nombre diferente</li>
            <li>El ID del candidato ({$candidatoId}) no es válido en la tabla candidatos</li>
            <li>Hay restricciones de clave foránea que impiden la inserción</li>
            <li>Los tipos de datos de los IDs no coinciden (entero vs string)</li>
            <li>Problemas de permisos en la tabla</li>
        </ul>
        <p>Verificar en la base de datos:</p>
        <ul>
            <li>Estructura exacta de la tabla candidato_habilidades</li>
            <li>Existencia del candidato con ID {$candidatoId}</li>
            <li>Permisos de la aplicación para insertar en esta tabla</li>
        </ul>
    </div>";
} else {
    echo "<div class='success bold'>
        <p>Se encontraron " . count($registros) . " habilidades asociadas al candidato ID {$candidatoId}.</p>
        <p>El sistema parece estar funcionando correctamente para guardar las habilidades del candidato.</p>
    </div>";
}

echo "</div>";

// Enlaces útiles
echo "<div class='section centered'>
    <a href='diagnostico_habilidades_candidato.php?candidato_id={$candidatoId}'>Ver diagnóstico original</a> | 
    <a href='index.php'>Volver al inicio</a> | 
    <a href='?candidato_id={$candidatoId}&force=1'>Forzar nueva inserción</a>
</div>";

echo "</body></html>";
?>
