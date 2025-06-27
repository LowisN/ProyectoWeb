<?php
/**
 * Script para corregir el problema de inserción de vacantes y sus habilidades
 * Este script modifica el archivo nueva_vacante.php para arreglar la inserción en la tabla vacante_habilidades
 */

// Incluir configuración
require_once 'config/supabase.php';

// Función para leer un archivo (incluido para diagnóstico)
function readFile($filePath) {
    if (file_exists($filePath)) {
        return file_get_contents($filePath);
    }
    return null;
}

// Función para escribir un archivo (incluido para diagnóstico)
function writeFile($filePath, $content) {
    return file_put_contents($filePath, $content);
}

// Encabezado para visualización amigable
echo "<html><head><title>Corrección de Inserción de Vacantes</title>";
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
echo "<h1>Corrección de Problema de Inserción de Vacantes</h1>";

// Path al archivo a modificar
$nuevaVacantePath = 'paginas/empresa/nueva_vacante.php';
$fullPath = dirname(__FILE__) . '/' . $nuevaVacantePath;

if (!file_exists($fullPath)) {
    echo "<p class='error'>No se encontró el archivo {$nuevaVacantePath}. Verifique la estructura de directorios.</p>";
    exit;
}

echo "<h2>1. Analizando el archivo {$nuevaVacantePath}</h2>";
$contenido = readFile($fullPath);
if (!$contenido) {
    echo "<p class='error'>No se pudo leer el archivo {$nuevaVacantePath}.</p>";
    exit;
}

echo "<p>Archivo leído correctamente. Longitud: " . strlen($contenido) . " bytes.</p>";

// Buscar la sección que procesa la inserción de la vacante y obtiene su ID
$patronInsercion = '// Insertar la vacante en la base de datos
        $vacanteResponse = supabaseInsert(\'vacantes\', $vacanteData);
        
        if \(isset\($vacanteResponse\[\'error\'\]\)\) {
            \$errorMessage = \'Error al crear la vacante\';
        \} else {
            \$vacanteId = \$vacanteResponse\[0\]\[\'id\'\];';

// La corrección que aplicaremos
$correccion = '// Insertar la vacante en la base de datos
        $vacanteResponse = supabaseInsert(\'vacantes\', $vacanteData);
        
        // Debug para diagnóstico
        error_log("Respuesta de inserción de vacante: " . print_r($vacanteResponse, true));
        
        if (isset($vacanteResponse[\'error\'])) {
            $errorMessage = \'Error al crear la vacante\';
        } else {
            // Intentar obtener ID de diferentes formas según la estructura de respuesta
            $vacanteId = null;
            if (is_array($vacanteResponse) && !empty($vacanteResponse) && isset($vacanteResponse[0][\'id\'])) {
                $vacanteId = $vacanteResponse[0][\'id\'];
                error_log("ID de vacante obtenido del primer elemento: $vacanteId");
            } else if (isset($vacanteResponse[\'id\'])) {
                $vacanteId = $vacanteResponse[\'id\'];
                error_log("ID de vacante obtenido directamente: $vacanteId");
            } else {
                // Buscar la vacante recién insertada
                error_log("Estructura de respuesta desconocida, intentando recuperar la vacante por sus datos");
                $vacantesRecientes = supabaseFetch(\'vacantes\', \'*\', [
                    \'titulo\' => $titulo,
                    \'empresa_id\' => $empresaData[0][\'id\']
                ], 1, [\'fecha_creacion\' => \'desc\']);
                
                if (!empty($vacantesRecientes) && isset($vacantesRecientes[0][\'id\'])) {
                    $vacanteId = $vacantesRecientes[0][\'id\'];
                    error_log("ID de vacante recuperado mediante consulta: $vacanteId");
                } else {
                    error_log("ERROR: No se pudo obtener el ID de vacante: " . print_r($vacantesRecientes, true));
                }
            }';

// Verificar si el patrón existe
if (strpos($contenido, $patronInsercion) !== false) {
    echo "<div class='success'>Se encontró la sección de inserción de vacante para modificar.</div>";
    
    // Realizar la modificación
    $nuevoContenido = str_replace($patronInsercion, $correccion, $contenido);
    
    if ($nuevoContenido != $contenido) {
        echo "<h2>2. Aplicando la corrección</h2>";
        echo "<div class='code-block'>";
        echo "<p>La corrección mejora la obtención del ID de la vacante recién insertada de varias maneras:</p>";
        echo "<ul>";
        echo "<li>Añade logging detallado para diagnóstico</li>";
        echo "<li>Intenta obtener el ID de múltiples formas según la estructura de respuesta</li>";
        echo "<li>Como último recurso, recupera la vacante recién creada mediante una consulta</li>";
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
        if (writeFile($fullPath, $nuevoContenido)) {
            echo "<p class='success'>La corrección se aplicó exitosamente a {$nuevaVacantePath}</p>";
            
            echo "<h2>3. Verificación de la corrección</h2>";
            echo "<p>La corrección debería solucionar el problema de obtención del ID de vacante y permitir la inserción correcta de habilidades en la tabla vacante_habilidades.</p>";
            
            echo "<div class='code-block'>";
            echo "<p>Para probar la corrección:</p>";
            echo "<ol>";
            echo "<li>Vaya a la página de crear nueva vacante</li>";
            echo "<li>Complete el formulario con al menos una o dos habilidades seleccionadas</li>";
            echo "<li>Envíe el formulario</li>";
            echo "<li>Verifique los logs para ver si el ID de vacante se obtiene correctamente</li>";
            echo "<li>Verifique en la base de datos si las habilidades se insertaron correctamente</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<p class='error'>No se pudo escribir la corrección en {$nuevaVacantePath}. Intente aplicar la corrección manualmente.</p>";
        }
    } else {
        echo "<p class='warning'>No se realizó ningún cambio, el contenido es idéntico después de la sustitución.</p>";
    }
} else {
    echo "<p class='error'>No se encontró la sección esperada de inserción de vacante en el archivo. Es posible que el archivo haya sido modificado o que la estructura sea diferente.</p>";
    echo "<p>Aplique la corrección manualmente al archivo nueva_vacante.php, modificando la sección donde se obtiene el ID de la vacante después de insertarla.</p>";
}

echo "<h2>4. Conclusión</h2>";
echo "<p>La corrección aborda el problema principal: la obtención incorrecta del ID de la vacante recién creada, lo que ocasionaba que todas las inserciones en la tabla vacante_habilidades fallaran debido a que vacante_id era NULL.</p>";
echo "<p>Si el problema persiste después de aplicar esta corrección, es posible que sea necesario revisar la función supabaseInsert para asegurarse de que devuelve correctamente los datos de la inserción.</p>";

echo "</body></html>";
