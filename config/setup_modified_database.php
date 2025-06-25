<?php
// Script para configurar la base de datos de ChambaNet en Supabase
// Este script ejecuta los scripts SQL para crear las tablas, funciones y políticas

// Importar configuración de Supabase
require_once 'supabase.php';

// Función para ejecutar un script SQL a través de la API REST de Supabase
function ejecutar_sql($sql, $descripcion) {
    global $supabase_url, $supabase_key;

    echo "<h3>Ejecutando: $descripcion</h3>";
    echo "<pre>$sql</pre>";

    // Para scripts SQL complejos que incluyen DDL (CREATE TABLE, etc.) es mejor
    // ejecutarlos directamente en el panel de SQL de Supabase.
    // Sin embargo, para propósitos de demostración, aquí está el código para
    // intentar ejecutar consultas a través de la API RESTful:

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$supabase_url/rest/v1/rpc/ejecutar_sql");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key
    ]);

    // Los datos para la llamada RPC
    $data = ['sql' => $sql];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        echo "<div style='color: green;'>Ejecución exitosa</div>";
    } else {
        echo "<div style='color: red;'>Error al ejecutar SQL: $response (Código HTTP: $status)</div>";
        echo "<p>IMPORTANTE: Es posible que este error se deba a que estás intentando ejecutar DDL (CREATE TABLE, etc.) a través de la API REST de Supabase, lo cual no está permitido. Para ejecutar este tipo de sentencias, debes usar el panel de SQL de Supabase.</p>";
    }
}

// Función para leer el contenido de un archivo
function leer_archivo($ruta) {
    if (file_exists($ruta)) {
        return file_get_contents($ruta);
    } else {
        echo "<div style='color: red;'>Error: El archivo $ruta no existe</div>";
        return false;
    }
}

// Comprobar si hay archivos SQL disponibles
$archivos = [
    'database' => 'modified_database.sql', 
    'functions' => 'functions.sql', 
    'policies' => 'modified_policies.sql'
];

$scripts_disponibles = [];
foreach ($archivos as $tipo => $nombre) {
    if (file_exists($nombre)) {
        $scripts_disponibles[$tipo] = $nombre;
    }
}

// Si no hay archivos disponibles, mostrar mensaje
if (empty($scripts_disponibles)) {
    echo "<div style='color: red;'>Error: No se encontraron archivos SQL para ejecutar</div>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Base de Datos - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        .success {
            color: green;
            background-color: #dff0d8;
            padding: 10px;
            border-radius: 3px;
        }
        .error {
            color: #a94442;
            background-color: #f2dede;
            padding: 10px;
            border-radius: 3px;
        }
        .warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            padding: 10px;
            border-radius: 3px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Configuración de Base de Datos - ChambaNet</h1>
    
    <div class="container">
        <h2>Importante: Ejecución de Scripts SQL</h2>
        <div class="warning">
            <p><strong>Nota:</strong> Este script no puede ejecutar directamente sentencias DDL (CREATE TABLE, etc.) a través de la API REST de Supabase. Para ejecutar esos scripts, debes:</p>
            <ol>
                <li>Iniciar sesión en el <a href="https://app.supabase.com" target="_blank">panel de Supabase</a></li>
                <li>Seleccionar tu proyecto</li>
                <li>Ir a "SQL Editor"</li>
                <li>Crear una nueva consulta</li>
                <li>Pegar el contenido de los scripts SQL y ejecutarlos</li>
            </ol>
            <p>Los scripts se ejecutarán en el siguiente orden:</p>
            <ol>
                <li><strong>modified_database.sql</strong>: Crea tablas y estructura básica</li>
                <li><strong>functions.sql</strong>: Crea funciones para lógica de negocio</li>
                <li><strong>modified_policies.sql</strong>: Configura políticas RLS para seguridad</li>
            </ol>
            <p>Las instrucciones detalladas para crear un admin se encuentran en el archivo <strong>crear_admin.sql</strong>.</p>
        </div>
    </div>

    <div class="container">
        <h2>Scripts SQL disponibles</h2>
        <form method="post">
            <?php foreach ($scripts_disponibles as $tipo => $nombre): ?>
                <div style="margin: 10px 0;">
                    <label>
                        <input type="checkbox" name="scripts[]" value="<?php echo $tipo; ?>" checked>
                        <?php echo ucfirst($tipo) . ' (' . $nombre . ')'; ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit" name="ejecutar">Ejecutar Scripts Seleccionados</button>
        </form>
    </div>

    <?php
    // Si se ha enviado el formulario
    if (isset($_POST['ejecutar']) && !empty($_POST['scripts'])) {
        echo '<div class="container">';
        echo '<h2>Resultados de la Ejecución</h2>';

        foreach ($_POST['scripts'] as $tipo) {
            if (isset($scripts_disponibles[$tipo])) {
                $sql = leer_archivo($scripts_disponibles[$tipo]);
                if ($sql !== false) {
                    ejecutar_sql($sql, "Script $tipo (" . $scripts_disponibles[$tipo] . ")");
                }
            }
        }

        echo '</div>';
    }
    ?>

    <div class="container">
        <h2>Orden de Creación Manual Recomendado</h2>
        <ol>
            <li>Ejecutar <code>modified_database.sql</code> para crear las tablas</li>
            <li>Ejecutar <code>functions.sql</code> para crear las funciones</li>
            <li>Ejecutar <code>modified_policies.sql</code> para configurar las políticas de seguridad</li>
            <li>Usar el archivo <code>crear_admin.php</code> o <code>crear_admin.sql</code> para crear un usuario administrador</li>
        </ol>
    </div>

</body>
</html>
