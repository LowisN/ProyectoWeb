<?php
// Verificar las restricciones de la columna nivel en candidato_habilidades

require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';

function obtenerInformacionColumna() {
    $url = SUPABASE_URL . "/rest/v1/rpc/check_column_constraint";
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SUPABASE_KEY,
        'apikey: ' . SUPABASE_KEY
    ];
    
    $params = [
        'table_name' => 'candidato_habilidades',
        'column_name' => 'nivel'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Respuesta: <pre>" . htmlspecialchars($response) . "</pre><br>";
}

// Consultar directamente la estructura usando información del esquema
function consultarEstructuraTabla() {
    $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?limit=0";
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . SUPABASE_KEY,
        'apikey' => SUPABASE_KEY
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Consulta estructura - HTTP Code: $httpCode<br>";
    echo "Cabeceras: <pre>";
    $headers = apache_request_headers();
    foreach ($headers as $header => $value) {
        echo "$header: $value\n";
    }
    echo "</pre>";
}

// Prueba insertando distintos valores para detectar los permitidos
function probarValoresPermitidos() {
    $valoresAPrueba = [
        'principiante',
        'intermedio',
        'avanzado',
        'experto',
        'malo',
        'regular',
        'bueno',
        'ninguno',
        'basic',
        'intermediate',
        'advanced',
        'beginner'
    ];
    
    echo "<h2>Prueba de valores permitidos</h2>";
    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Valor</th>
                <th>Resultado</th>
                <th>Código HTTP</th>
                <th>Respuesta</th>
            </tr>";
    
    foreach ($valoresAPrueba as $valor) {
        // Usamos un ID de candidato y habilidad que no existan para evitar inserciones reales
        $datos = [
            'candidato_id' => 999999,
            'habilidad_id' => 999999,
            'nivel' => $valor,
            'anios_experiencia' => 1
        ];
        
        $url = SUPABASE_URL . "/rest/v1/candidato_habilidades";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUPABASE_KEY,
            'apikey: ' . SUPABASE_KEY,
            'Prefer: return=representation'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $esPermitido = ($httpCode >= 200 && $httpCode < 300) || 
                      !strpos($response, 'candidato_habilidades_nivel_check') !== false;
                      
        $resultado = $esPermitido ? 'Permitido' : 'No permitido';
        $respuestaCorta = substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '');
        
        echo "<tr>
                <td><code>$valor</code></td>
                <td style='color: " . ($esPermitido ? 'green' : 'red') . ";'>$resultado</td>
                <td>$httpCode</td>
                <td><code>" . htmlspecialchars($respuestaCorta) . "</code></td>
              </tr>";
    }
    
    echo "</table>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificar Restricción de Nivel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        
        th, td {
            text-align: left;
            padding: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        
        .error {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
    </style>
</head>
<body>
    <h1>Verificación de Restricciones de Nivel</h1>
    
    <div class="message success">
        Esta herramienta ayuda a determinar qué valores son aceptados en la columna 'nivel' de la tabla 'candidato_habilidades'.
    </div>
    
    <h2>Información de la columna (puede requerir permisos especiales)</h2>
    <?php 
        // Esta función puede fallar si no tenemos permisos adecuados
        // obtenerInformacionColumna(); 
    ?>
    
    <h2>Estructura de la tabla</h2>
    <?php 
        // consultarEstructuraTabla(); 
    ?>
    
    <?php 
        // Esta es la prueba más confiable
        probarValoresPermitidos(); 
    ?>
    
    <h2>Conclusión:</h2>
    <div class="message">
        <p>De acuerdo al constraint de la base de datos, los valores permitidos para la columna 'nivel' en la tabla 'candidato_habilidades' son:</p>
        <ul>
            <li><strong>principiante</strong></li>
            <li><strong>intermedio</strong></li>
            <li><strong>avanzado</strong></li>
            <li><strong>experto</strong></li>
        </ul>
        <p>Los valores del formulario ('malo', 'regular', 'bueno', 'ninguno') deben ser mapeados a estos valores permitidos antes de la inserción.</p>
    </div>
</body>
</html>
