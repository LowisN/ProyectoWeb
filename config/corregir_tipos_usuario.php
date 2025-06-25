<?php
/**
 * Script para corregir los tipos de usuario en la base de datos
 * Este script ejecutará las correcciones para asegurar consistencia en los tipos de usuario
 */
require_once 'supabase.php';

// Ignorar avisos para mostrar un mensaje limpio si hay errores
error_reporting(E_ERROR | E_PARSE);

// Extraer el contenido del archivo SQL
$sqlFile = 'actualizar_usuarios_sin_tipo.sql';
$sqlContent = '';

// Intentar leer el archivo SQL si existe
if (file_exists($sqlFile)) {
    $sqlContent = file_get_contents($sqlFile);
} else {
    // Si no existe, definir las consultas manualmente
    $sqlContent = "
    -- Actualizar perfiles donde tipo_perfil es NULL pero tipo_usuario existe
    UPDATE perfiles 
    SET tipo_perfil = tipo_usuario 
    WHERE tipo_perfil IS NULL AND tipo_usuario IS NOT NULL;
    
    -- Actualizar perfiles donde tipo_usuario es NULL pero tipo_perfil existe
    UPDATE perfiles 
    SET tipo_usuario = tipo_perfil 
    WHERE tipo_usuario IS NULL AND tipo_perfil IS NOT NULL;
    
    -- Normalizar valores admin -> administrador
    UPDATE perfiles 
    SET tipo_usuario = 'administrador', tipo_perfil = 'administrador' 
    WHERE tipo_usuario = 'admin' OR tipo_perfil = 'admin';
    
    -- Actualizar perfiles sin tipo pero que están en tabla candidatos
    UPDATE perfiles p
    SET tipo_usuario = 'candidato', tipo_perfil = 'candidato'
    WHERE (tipo_usuario IS NULL OR tipo_perfil IS NULL OR tipo_usuario = '' OR tipo_perfil = '')
    AND EXISTS (SELECT 1 FROM candidatos c WHERE c.perfil_id = p.id);
    
    -- Actualizar perfiles sin tipo pero que están en tabla reclutadores
    UPDATE perfiles p
    SET tipo_usuario = 'reclutador', tipo_perfil = 'reclutador'
    WHERE (tipo_usuario IS NULL OR tipo_perfil IS NULL OR tipo_usuario = '' OR tipo_perfil = '')
    AND EXISTS (SELECT 1 FROM reclutadores r WHERE r.perfil_id = p.id);";
}

// Dividir las consultas por el delimitador ";"
$queries = array_filter(
    array_map(
        'trim', 
        explode(';', $sqlContent)
    ), 
    function($query) { 
        return !empty($query) && strpos($query, '--') !== 0; 
    }
);

$resultados = [];

// Ejecutar cada consulta y registrar el resultado
foreach ($queries as $index => $query) {
    try {
        $result = supabaseRawQuery($query);
        $resultados[] = "Consulta " . ($index + 1) . ": Éxito";
    } catch (Exception $e) {
        $resultados[] = "Consulta " . ($index + 1) . ": Error - " . $e->getMessage();
    }
}

// Verificar registros sin tipo_perfil después de las actualizaciones
$consultaVerificacion = "SELECT id, user_id, tipo_usuario, tipo_perfil FROM perfiles WHERE tipo_perfil IS NULL OR tipo_perfil NOT IN ('administrador', 'candidato', 'reclutador');";
// Intentar hacer una consulta directa con el método estándar para obtener perfiles con problemas
$perfilesSinTipo = supabaseFetch('perfiles', '*', ['tipo_perfil' => 'IS.NULL']);
$perfilesSinTipoUsuario = supabaseFetch('perfiles', '*', ['tipo_usuario' => 'IS.NULL']);

// Al no poder usar IS NULL fácilmente en la API REST de Supabase, hacemos una consulta que nos dará todos los perfiles
$todosPerfiles = supabaseFetch('perfiles', '*');

// Contar manualmente los perfiles con problemas
$conProblemas = 0;
$perfilesProblematicos = [];

if (!empty($todosPerfiles) && !isset($todosPerfiles['error'])) {
    foreach ($todosPerfiles as $perfil) {
        $tipoUsuario = $perfil['tipo_usuario'] ?? null;
        $tipoPerfil = $perfil['tipo_perfil'] ?? null;
        
        if (empty($tipoUsuario) || empty($tipoPerfil) || 
            !in_array($tipoUsuario, ['administrador', 'candidato', 'reclutador']) ||
            !in_array($tipoPerfil, ['administrador', 'candidato', 'reclutador'])) {
            $conProblemas++;
            $perfilesProblematicos[] = $perfil;
        }
    }
    
    if ($conProblemas > 0) {
        $resultados[] = "ATENCIÓN: Aún hay " . $conProblemas . " perfiles con tipos no válidos.";
    } else {
        $resultados[] = "VERIFICACIÓN: Todos los perfiles tienen tipos válidos.";
    }
} else {
    $resultados[] = "No se pudieron verificar todos los perfiles: " . 
                   (isset($todosPerfiles['error']) ? $todosPerfiles['error'] : "Error desconocido");
}

// Mostrar resultados
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Corrección de tipos de usuario</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Resultados de la corrección de tipos de usuario</h1>
    <div class='box'>";

foreach ($resultados as $resultado) {
    $class = "success";
    if (strpos($resultado, "Error") !== false) {
        $class = "error";
    } elseif (strpos($resultado, "ATENCIÓN") !== false) {
        $class = "warning";
    }
    echo "<p class='{$class}'>{$resultado}</p>";
}

echo "</div>
    <p><a href='../paginas/interfaz_iniciar_sesion.php'>Volver a la página de inicio de sesión</a></p>
</body>
</html>";

/**
 * Función para ejecutar consultas SQL directas
 */
function supabaseRawQuery($query) {
    // Esta función debería implementarse en supabase.php
    // Por ahora implementamos una versión básica aquí
    $url = getenv('SUPABASE_URL') ?: 'https://tu-proyecto.supabase.co';
    $key = getenv('SUPABASE_KEY') ?: 'tu-key-de-servicio';
    
    $ch = curl_init($url . '/rest/v1/rpc/ejecutar_sql');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sql' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Error al ejecutar consulta SQL: " . $error);
    }
    
    return json_decode($response, true);
}

/**
 * Función para ejecutar consultas SQL y obtener resultados
 */
function supabaseFetchRaw($query) {
    // Similar a la función anterior pero para consultas SELECT
    // En un entorno real, esto se implementaría de manera más robusta
    $url = getenv('SUPABASE_URL') ?: 'https://tu-proyecto.supabase.co';
    $key = getenv('SUPABASE_KEY') ?: 'tu-key-de-servicio';
    
    $ch = curl_init($url . '/rest/v1/rpc/ejecutar_consulta');
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sql' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $key,
        'Authorization: Bearer ' . $key
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Error al ejecutar consulta SQL: " . $error);
    }
    
    return json_decode($response, true);
}
?>
