<?php
/**
 * Script para verificar y crear las tablas necesarias en Supabase
 * 
 * Este script verifica que existan las tablas requeridas por la aplicación
 * y las crea si es necesario. Útil para diagnóstico de problemas de registro.
 */

session_start();
require_once 'supabase.php';

// Verificar si el usuario está autenticado como administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    // Permitir ejecución en modo debug si se proporciona un parámetro especial
    $debugKey = "chambanetsetup2024";
    if (!isset($_GET['debug']) || $_GET['debug'] !== $debugKey) {
        die("Acceso denegado. Esta herramienta solo es accesible para administradores.");
    }
}

// Variables para almacenar resultados
$tablas = [
    'perfiles' => [
        'existe' => false,
        'campos' => []
    ],
    'candidatos' => [
        'existe' => false,
        'campos' => []
    ],
    'reclutadores' => [
        'existe' => false,
        'campos' => []
    ],
    'empresas' => [
        'existe' => false,
        'campos' => []
    ],
    'vacantes' => [
        'existe' => false,
        'campos' => []
    ]
];

$mensajes = [];
$crearTablas = isset($_POST['crear_tablas']) && $_POST['crear_tablas'] === 'true';

// Función para agregar mensajes
function agregarMensaje($texto, $tipo = 'info') {
    global $mensajes;
    $mensajes[] = ['texto' => $texto, 'tipo' => $tipo];
    error_log("[Verificación Tablas] - $tipo: $texto");
}

// Función para verificar si una tabla existe
function tablaExiste($nombreTabla) {
    try {
        $response = supabaseRequest("/rest/v1/$nombreTabla?limit=0", 'GET');
        
        if (isset($response['error'])) {
            if (strpos($response['error'], '404') !== false || strpos($response['error'], 'not found') !== false) {
                return false;
            }
            
            agregarMensaje("Error al verificar tabla $nombreTabla: " . $response['error'], 'error');
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        agregarMensaje("Excepción al verificar tabla $nombreTabla: " . $e->getMessage(), 'error');
        return false;
    }
}

// Verificar las tablas
foreach ($tablas as $nombreTabla => &$infoTabla) {
    $infoTabla['existe'] = tablaExiste($nombreTabla);
    
    if ($infoTabla['existe']) {
        agregarMensaje("La tabla '$nombreTabla' existe.", 'success');
        
        // Aquí podríamos verificar los campos si fuera necesario
        // Por ejemplo, verificar que existe el campo tipo_usuario en perfiles
    } else {
        agregarMensaje("La tabla '$nombreTabla' no existe.", 'warning');
        
        // Si se solicitó crear las tablas, lo hacemos
        if ($crearTablas) {
            // Aquí iría el código para crear cada tabla según su estructura
            // Este código usaría un CREATE TABLE mediante RawQuery
            // o llamadas directas a la API de Supabase
            agregarMensaje("Se necesita crear la tabla '$nombreTabla' pero esta funcionalidad requiere SQL directo.", 'info');
        }
    }
}

// Si la tabla perfiles existe, verificar si tiene el campo tipo_usuario
if ($tablas['perfiles']['existe']) {
    // Esto sería ideal, pero requeriría acceso a información del esquema,
    // lo cual no está disponible fácilmente mediante la API REST de Supabase
    agregarMensaje("Se recomienda verificar manualmente que la tabla 'perfiles' tenga los campos 'tipo_usuario' y 'tipo_perfil'", 'info');
}

// Función para generar el SQL necesario para crear las tablas
function generarSQLCreacion() {
    $sql = "-- SQL para crear las tablas necesarias en Supabase\n\n";
    
    // Tabla perfiles
    $sql .= "CREATE TABLE IF NOT EXISTS perfiles (\n";
    $sql .= "  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),\n";
    $sql .= "  user_id UUID NOT NULL UNIQUE REFERENCES auth.users(id) ON DELETE CASCADE,\n";
    $sql .= "  email TEXT NOT NULL,\n";
    $sql .= "  tipo_usuario TEXT NOT NULL,\n";
    $sql .= "  tipo_perfil TEXT NOT NULL,\n";
    $sql .= "  nombre TEXT,\n";
    $sql .= "  apellidos TEXT,\n";
    $sql .= "  fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT now(),\n";
    $sql .= "  fecha_actualizacion TIMESTAMP WITH TIME ZONE DEFAULT now()\n";
    $sql .= ");\n\n";
    
    // Tabla candidatos
    $sql .= "CREATE TABLE IF NOT EXISTS candidatos (\n";
    $sql .= "  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),\n";
    $sql .= "  perfil_id UUID NOT NULL UNIQUE REFERENCES perfiles(id) ON DELETE CASCADE,\n";
    $sql .= "  telefono TEXT,\n";
    $sql .= "  fecha_nacimiento DATE,\n";
    $sql .= "  direccion TEXT,\n";
    $sql .= "  titulo TEXT,\n";
    $sql .= "  anios_experiencia INTEGER DEFAULT 0,\n";
    $sql .= "  estado TEXT DEFAULT 'activo',\n";
    $sql .= "  fecha_registro TIMESTAMP WITH TIME ZONE DEFAULT now()\n";
    $sql .= ");\n\n";
    
    // Tabla empresas
    $sql .= "CREATE TABLE IF NOT EXISTS empresas (\n";
    $sql .= "  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),\n";
    $sql .= "  nombre TEXT NOT NULL,\n";
    $sql .= "  rfc TEXT,\n";
    $sql .= "  industria TEXT,\n";
    $sql .= "  direccion TEXT,\n";
    $sql .= "  telefono TEXT,\n";
    $sql .= "  sitio_web TEXT,\n";
    $sql .= "  fecha_registro TIMESTAMP WITH TIME ZONE DEFAULT now()\n";
    $sql .= ");\n\n";
    
    // Tabla reclutadores
    $sql .= "CREATE TABLE IF NOT EXISTS reclutadores (\n";
    $sql .= "  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),\n";
    $sql .= "  perfil_id UUID NOT NULL UNIQUE REFERENCES perfiles(id) ON DELETE CASCADE,\n";
    $sql .= "  empresa_id UUID NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,\n";
    $sql .= "  nombre TEXT,\n";
    $sql .= "  apellidos TEXT,\n";
    $sql .= "  email TEXT NOT NULL,\n";
    $sql .= "  cargo TEXT\n";
    $sql .= ");\n\n";
    
    // Tabla vacantes
    $sql .= "CREATE TABLE IF NOT EXISTS vacantes (\n";
    $sql .= "  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),\n";
    $sql .= "  empresa_id UUID NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,\n";
    $sql .= "  reclutador_id UUID REFERENCES reclutadores(id) ON DELETE SET NULL,\n";
    $sql .= "  titulo TEXT NOT NULL,\n";
    $sql .= "  empresa_nombre TEXT,\n";
    $sql .= "  descripcion TEXT,\n";
    $sql .= "  responsabilidades TEXT,\n";
    $sql .= "  requisitos TEXT,\n";
    $sql .= "  salario NUMERIC(10,2),\n";
    $sql .= "  modalidad TEXT,\n";
    $sql .= "  ubicacion TEXT,\n";
    $sql .= "  anios_experiencia_requeridos INTEGER DEFAULT 0,\n";
    $sql .= "  fecha_publicacion DATE DEFAULT CURRENT_DATE,\n";
    $sql .= "  estado TEXT DEFAULT 'activa'\n";
    $sql .= ");\n\n";
    
    return $sql;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Tablas - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d63d3d;
            margin-top: 0;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .message.info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
        }
        .message.success {
            background-color: #d4edda;
            border-left: 6px solid #28a745;
        }
        .message.warning {
            background-color: #fff3cd;
            border-left: 6px solid #ffc107;
        }
        .message.error {
            background-color: #f8d7da;
            border-left: 6px solid #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .button {
            background-color: #d63d3d;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #c72c2c;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-family: monospace;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verificación de Tablas en Supabase - ChambaNet</h1>
        
        <div class="section">
            <h2>Resultado de la verificación</h2>
            
            <?php foreach ($mensajes as $mensaje): ?>
                <div class="message <?php echo $mensaje['tipo']; ?>">
                    <?php echo htmlspecialchars($mensaje['texto']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h2>Estado de las tablas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tabla</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tablas as $nombreTabla => $infoTabla): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nombreTabla); ?></td>
                            <td>
                                <?php if ($infoTabla['existe']): ?>
                                    <span style="color: green;">✓ Existe</span>
                                <?php else: ?>
                                    <span style="color: red;">✗ No existe</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>SQL para crear las tablas</h2>
            <p>El siguiente SQL puede utilizarse para crear las tablas necesarias en Supabase:</p>
            <pre><?php echo htmlspecialchars(generarSQLCreacion()); ?></pre>
            <p>Para ejecutar este SQL:</p>
            <ol>
                <li>Acceda al panel de control de Supabase</li>
                <li>Vaya a la sección "SQL Editor"</li>
                <li>Pegue este código en el editor</li>
                <li>Ejecute el script</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <form method="POST">
                <input type="hidden" name="crear_tablas" value="true">
                <button type="submit" class="button" disabled>Crear tablas faltantes (No disponible)</button>
            </form>
            <p><small>La creación automática de tablas requiere acceso directo al SQL en Supabase, que no está disponible a través de la API REST. Use el SQL proporcionado arriba.</small></p>
            
            <a href="../paginas/interfaz_iniciar_sesion.php" style="text-decoration: none; margin-top: 20px; display: inline-block;">
                <button class="button" type="button">Volver a Inicio de Sesión</button>
            </a>
        </div>
    </div>
</body>
</html>
