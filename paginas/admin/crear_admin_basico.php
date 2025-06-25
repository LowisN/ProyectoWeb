<?php
// Script para insertar un usuario administrador en ChambaNet directamente
// Este script debe ser ejecutado solo una vez para crear el primer administrador
// Después, puedes usar la interfaz web para crear más administradores

// Importar configuración de Supabase
require_once '../../config/supabase.php';

// Datos del administrador (ajustar según sea necesario)
$admin_email = 'admin@chambanet.com';
$admin_password = 'Admin123!';  // ¡Cambiar esta contraseña!
$admin_data = [
    'nombre' => 'Administrador',
    'apellido_paterno' => 'Sistema',
    'apellido_materno' => '',
    'telefono' => '1234567890',
    'fecha_nacimiento' => '2000-01-01'
];

// Función para comprobar si el usuario ya existe en Auth
function usuario_existe($email) {
    // Nota: Esta función es una aproximación ya que la API de Auth de Supabase 
    // no proporciona un endpoint público para verificar si un usuario existe.
    // En un entorno real, esto debería implementarse con las APIs adecuadas.
    return false;
}

// Función para crear el administrador
function crear_admin() {
    global $admin_email, $admin_password, $admin_data;
    
    // 1. Crear usuario en Supabase Auth con la API pública de sign-up
    // Nota: Esto requiere que los registros estén habilitados en Supabase
    $signup_url = SUPABASE_URL . '/auth/v1/signup';
    
    $ch = curl_init($signup_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY
    ]);
    
    $auth_data = [
        'email' => $admin_email,
        'password' => $admin_password
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auth_data));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status < 200 || $status >= 300) {
        echo "<p class='error'>Error al crear usuario en Auth: $response</p>";
        
        // Si falla, intentar iniciar sesión para ver si el usuario ya existe
        $signin_url = SUPABASE_URL . '/auth/v1/token?grant_type=password';
        $ch = curl_init($signin_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_KEY
        ]);
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => $admin_email,
            'password' => $admin_password
        ]));
        
        $signin_response = curl_exec($ch);
        $signin_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($signin_status >= 200 && $signin_status < 300) {
            $signin_data = json_decode($signin_response, true);
            if (isset($signin_data['access_token'])) {
                echo "<p class='success'>El usuario ya existe. Se ha obtenido un token de acceso.</p>";
                $access_token = $signin_data['access_token'];
                $user_id = $signin_data['user']['id'];
                
                // Continuar con la inserción en las tablas usuario y perfiles
                return completar_registro_admin($user_id);
            }
        }
        
        echo "<p class='error'>No se pudo crear ni encontrar el usuario. Por favor, inténtelo manualmente.</p>";
        return false;
    }
    
    // Si el registro fue exitoso, extraer el user_id
    $user_data = json_decode($response, true);
    if (!isset($user_data['id'])) {
        echo "<p class='error'>Error: No se pudo obtener el ID del usuario creado.</p>";
        return false;
    }
    
    $user_id = $user_data['id'];
    echo "<p class='success'>Usuario creado con éxito en Auth. ID: $user_id</p>";
    
    // Completar el registro en las tablas personalizadas
    return completar_registro_admin($user_id);
}

// Función para completar el registro en las tablas personalizadas
function completar_registro_admin($user_id) {
    global $admin_email, $admin_data;
    
    // 2. Insertar en la tabla usuario
    $insert_usuario_url = SUPABASE_URL . '/rest/v1/usuario';
    $ch = curl_init($insert_usuario_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=representation'
    ]);
    
    $userData = [
        'user_id' => $user_id,
        'nombre' => $admin_data['nombre'],
        'apellido_paterno' => $admin_data['apellido_paterno'],
        'apellido_materno' => $admin_data['apellido_materno'],
        'correo' => $admin_email,
        'telefono' => $admin_data['telefono'],
        'fecha_nacimiento' => $admin_data['fecha_nacimiento']
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status < 200 || $status >= 300) {
        echo "<p class='error'>Error al insertar en tabla usuario: $response</p>";
        return false;
    }
    
    echo "<p class='success'>Usuario insertado correctamente en la tabla usuario</p>";
    
    // 3. Insertar en la tabla perfiles
    $insert_perfil_url = SUPABASE_URL . '/rest/v1/perfiles';
    $ch = curl_init($insert_perfil_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Prefer: return=representation'
    ]);
    
    $profileData = [
        'user_id' => $user_id,
        'tipo_perfil' => 'administrador'
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileData));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status < 200 || $status >= 300) {
        echo "<p class='error'>Error al insertar en tabla perfiles: $response</p>";
        return false;
    }
    
    echo "<p class='success'>Perfil de administrador creado correctamente</p>";
    
    return [
        'success' => true,
        'user_id' => $user_id,
        'email' => $admin_email
    ];
}

// Comprobar si se envió el formulario
$result = null;
if (isset($_POST['create_admin'])) {
    $result = crear_admin();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Administrador Inicial - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fffbea;
            border-left: 6px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #ffdddd;
            border-left: 6px solid #f44336;
            padding: 15px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #ddffdd;
            border-left: 6px solid #4CAF50;
            padding: 15px;
            margin-bottom: 20px;
        }
        form {
            margin-top: 20px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .code {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Administrador Inicial</h1>
        
        <div class="warning">
            <strong>¡IMPORTANTE!</strong> Este script está diseñado para ejecutarse una sola vez para crear el primer administrador. Después de esto, deberías usar la interfaz web regular para gestionar usuarios.
        </div>
        
        <div class="info">
            <p>Este script creará un usuario administrador con los siguientes datos:</p>
            <ul>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($admin_email); ?></li>
                <li><strong>Contraseña:</strong> <?php echo htmlspecialchars($admin_password); ?> (cámbiala después del primer inicio de sesión)</li>
                <li><strong>Nombre:</strong> <?php echo htmlspecialchars($admin_data['nombre']); ?></li>
                <li><strong>Apellidos:</strong> <?php echo htmlspecialchars($admin_data['apellido_paterno'] . ' ' . $admin_data['apellido_materno']); ?></li>
            </ul>
        </div>
        
        <?php if ($result): ?>
            <?php if ($result['success']): ?>
                <div class="success">
                    <p><strong>¡Administrador creado con éxito!</strong></p>
                    <p>ID: <?php echo htmlspecialchars($result['user_id']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($result['email']); ?></p>
                    <p>Ya puedes iniciar sesión con estas credenciales.</p>
                </div>
                <div class="info">
                    <p>Próximos pasos:</p>
                    <ol>
                        <li>Inicia sesión con las credenciales del administrador</li>
                        <li>Cambia la contraseña por una más segura</li>
                        <li>Completa la configuración de tu sitio</li>
                    </ol>
                </div>
                <a href="../../paginas/interfaz_iniciar_sesion.php" style="display: block; text-align: center; margin-top: 20px; text-decoration: none;">
                    <button type="button">Ir a la página de inicio de sesión</button>
                </a>
            <?php else: ?>
                <div class="error">
                    <p><strong>Error al crear el administrador.</strong></p>
                    <p>Consulta los mensajes de error para más detalles.</p>
                </div>
                <form method="post">
                    <button type="submit" name="create_admin">Intentar de nuevo</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <form method="post">
                <button type="submit" name="create_admin">Crear Administrador</button>
            </form>
        <?php endif; ?>
        
        <div class="footer">
            <p>ChambaNet - Sistema de Gestión de Vacantes y Candidatos</p>
        </div>
    </div>
</body>
</html>
