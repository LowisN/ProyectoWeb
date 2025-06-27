<?php
/**
 * Script para corregir el problema de tipos de datos en la inserción de Supabase
 * Este script mejora la función supabaseInsert para manejar correctamente los valores booleanos
 */

// Incluir configuración
require_once 'config/supabase.php';

// Encabezado para visualización amigable
echo "<html><head><title>Corrección de supabaseInsert</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #333; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .code-block { background: #f0f0f0; padding: 15px; border-left: 4px solid #007bff; margin-bottom: 20px; }
</style>";
echo "</head><body>";
echo "<h1>Corrección de Función supabaseInsert</h1>";

// Path al archivo a modificar
$supabasePath = 'config/supabase.php';
$fullPath = dirname(__FILE__) . '/' . $supabasePath;

if (!file_exists($fullPath)) {
    echo "<p class='error'>No se encontró el archivo {$supabasePath}. Verifique la estructura de directorios.</p>";
    exit;
}

echo "<h2>1. Analizando el archivo {$supabasePath}</h2>";
$contenido = file_get_contents($fullPath);
if (!$contenido) {
    echo "<p class='error'>No se pudo leer el archivo {$supabasePath}.</p>";
    exit;
}

echo "<p>Archivo leído correctamente. Longitud: " . strlen($contenido) . " bytes.</p>";

// Buscar la función supabaseInsert
$patronFuncion = '// Función para insertar datos en una tabla
function supabaseInsert($table, $data) {
    $response = supabaseRequest("/rest/v1/$table", \'POST\', $data);
    
    // Registrar la respuesta para debugging
    error_log("Respuesta de supabaseInsert para tabla $table: " . json_encode($response));';

// La corrección que aplicaremos (añadir preprocesamiento de datos)
$correccion = '// Función para insertar datos en una tabla
function supabaseInsert($table, $data) {
    // Preprocesar los datos para corregir valores booleanos
    $processedData = array();
    foreach ($data as $key => $value) {
        // Convertir strings vacíos a null cuando van a campos booleanos
        if ($value === "" && ($key === "destacada" || $key === "obligatorio")) {
            $processedData[$key] = null;
        }
        // Convertir explícitamente los booleanos para asegurar que se envían correctamente
        else if ($value === true || $value === "true" || $value === 1 || $value === "1") {
            $processedData[$key] = true;
        } 
        else if ($value === false || $value === "false" || $value === 0 || $value === "0") {
            $processedData[$key] = false;
        }
        // Mantener otros valores sin cambios
        else {
            $processedData[$key] = $value;
        }
    }
    
    // Registrar datos procesados para diagnóstico
    error_log("Datos a insertar en $table (después del procesamiento): " . json_encode($processedData));
    
    $response = supabaseRequest("/rest/v1/$table", \'POST\', $processedData);
    
    // Registrar la respuesta para debugging
    error_log("Respuesta de supabaseInsert para tabla $table: " . json_encode($response));';

// Verificar si el patrón existe
if (strpos($contenido, $patronFuncion) !== false) {
    echo "<div class='success'>Se encontró la función supabaseInsert para modificar.</div>";
    
    // Realizar la modificación
    $nuevoContenido = str_replace($patronFuncion, $correccion, $contenido);
    
    if ($nuevoContenido != $contenido) {
        echo "<h2>2. Aplicando la corrección</h2>";
        echo "<div class='code-block'>";
        echo "<p>La corrección mejora el manejo de tipos de datos booleanos de la siguiente manera:</p>";
        echo "<ul>";
        echo "<li>Añade un preprocesamiento de los datos antes de enviarlos a Supabase</li>";
        echo "<li>Convierte strings vacíos a NULL para campos booleanos</li>";
        echo "<li>Normaliza valores booleanos para asegurar que se envían con el tipo correcto</li>";
        echo "<li>Añade logging adicional para diagnóstico</li>";
        echo "</ul>";
        echo "</div>";
        
        // Crear una copia de seguridad
        $backupPath = $fullPath . '.bak.' . date('Ymd_His');
        if (copy($fullPath, $backupPath)) {
            echo "<p class='success'>Se creó una copia de seguridad en {$backupPath}</p>";
        } else {
            echo "<p class='warning'>No se pudo crear una copia de seguridad, pero continuaremos con la corrección.</p>";
        }
        
        // Escribir las modificaciones
        if (file_put_contents($fullPath, $nuevoContenido)) {
            echo "<p class='success'>La corrección se aplicó exitosamente a {$supabasePath}</p>";
            
            // Ejecutar un test para comprobar la función
            echo "<h2>3. Probando la función corregida</h2>";
            echo "<p>Realizando una prueba de inserción en la tabla 'vacantes'...</p>";
            
            try {
                // Intentar obtener información de empresa para la prueba
                $empresaTest = supabaseFetch('empresas', '*', [], 1);
                
                if (!empty($empresaTest) && isset($empresaTest[0]['id'])) {
                    $empresaId = $empresaTest[0]['id'];
                    
                    // Datos de prueba
                    $dataPrueba = [
                        'empresa_id' => $empresaId,
                        'titulo' => 'Test Corrección Supabase - ' . date('Y-m-d H:i:s'),
                        'descripcion' => 'Prueba de corrección de supabaseInsert',
                        'estado' => 'borrador',
                        'destacada' => false, // Incluimos explícitamente un booleano
                    ];
                    
                    $resultado = supabaseInsert('vacantes', $dataPrueba);
                    echo "<pre>";
                    var_dump($resultado);
                    echo "</pre>";
                    
                    if (!isset($resultado['error'])) {
                        echo "<p class='success'>¡La prueba fue exitosa! La función corregida insertó correctamente los datos.</p>";
                    } else {
                        echo "<p class='error'>La prueba falló. Error: " . $resultado['message'] . "</p>";
                    }
                } else {
                    echo "<p class='warning'>No se pudo realizar la prueba porque no hay empresas en la base de datos.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error al ejecutar la prueba: " . $e->getMessage() . "</p>";
            }
            
            echo "<h2>4. Recomendaciones adicionales</h2>";
            echo "<div class='code-block'>";
            echo "<p>Para asegurar que todos los problemas relacionados se resuelvan, también se recomienda:</p>";
            echo "<ol>";
            echo "<li>En todos los formularios que envíen datos booleanos, asegurarse de que los campos checkbox estén correctamente inicializados como <code>false</code> si no están marcados.</li>";
            echo "<li>Revisar el código de <code>nueva_vacante.php</code> para asegurar que maneja correctamente el campo <code>destacada</code> como valor booleano.</li>";
            echo "<li>Verificar que los esquemas de las tablas en Supabase tienen las restricciones correctas para los tipos de datos.</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<p class='error'>No se pudo escribir la corrección en {$supabasePath}. Intente aplicar la corrección manualmente.</p>";
        }
    } else {
        echo "<p class='warning'>No se realizó ningún cambio, el contenido es idéntico después de la sustitución.</p>";
    }
} else {
    echo "<p class='error'>No se encontró la función supabaseInsert en el archivo. Es posible que el archivo haya sido modificado o que la estructura sea diferente.</p>";
    echo "<p>Aplique la corrección manualmente al archivo supabase.php, modificando la función supabaseInsert.</p>";
}

echo "<h2>5. Conclusión</h2>";
echo "<p>La corrección aborda el problema principal: el error de tipo booleano (<i>\"invalid input syntax for type boolean: \"\"\"</i>) que ocurre cuando se envían strings vacíos a campos que esperan booleanos.</p>";
echo "<p>Esta solución garantiza que los campos booleanos se manejen correctamente y mejora la captura de errores y diagnóstico.</p>";

echo "</body></html>";
