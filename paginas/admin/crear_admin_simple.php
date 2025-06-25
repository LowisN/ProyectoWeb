<?php
// Importar configuración de Supabase
require_once '../../config/supabase.php';

// Verificar si se envió el formulario
$result = null;
$error_details = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    // Recibir datos del formulario
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido_paterno = $_POST['apellido_paterno'] ?? '';
    $apellido_materno = $_POST['apellido_materno'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || empty($password) || empty($nombre)) {
        $error_details = "Todos los campos marcados con * son obligatorios";
    } else {
        // Datos del usuario para crear
        $userData = [
            'nombre' => $nombre,
            'apellido_paterno' => $apellido_paterno,
            'apellido_materno' => $apellido_materno,
            'telefono' => $telefono,
            'fecha_nacimiento' => $fecha_nacimiento,
            'tipo_usuario' => 'administrador'
        ];
        
        // Usar la función de Supabase para crear administrador
        $result = createAdminUser($email, $password, $userData);
        
        // Para depuración, guardar los detalles de cualquier error
        if (!$result['success']) {
            $error_details = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Administrador - ChambaNet</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
            margin-top: 20px;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Usuario Administrador</h1>
        
        <?php if ($result !== null): ?>
            <?php if ($result['success']): ?>
                <div class="alert alert-success">
                    <strong>¡Usuario administrador creado exitosamente!</strong><br>
                    ID: <?php echo htmlspecialchars($result['user_id']); ?><br>
                    Ya puedes iniciar sesión con el correo y contraseña proporcionados.
                </div>
                <a href="../../paginas/interfaz_iniciar_sesion.php" style="display: block; text-align: center; margin-top: 20px; text-decoration: none;">
                    <button type="button" class="btn-submit">Ir a iniciar sesión</button>
                </a>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>Error al crear el administrador</strong><br>
                    Mensaje: <?php echo htmlspecialchars($result['message'] ?? 'Error desconocido'); ?><br>
                    Paso donde ocurrió el error: <?php echo htmlspecialchars($result['step'] ?? 'desconocido'); ?>
                </div>
                
                <?php if (isset($result['error'])): ?>
                    <div class="debug-info">
                        <strong>Detalles del error (para depuración):</strong><br>
                        <pre><?php print_r($result['error']); ?></pre>
                        
                        <?php if (isset($result['response'])): ?>
                            <strong>Respuesta completa:</strong><br>
                            <pre><?php print_r($result['response']); ?></pre>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404;">
                        <strong>Posibles soluciones:</strong>
                        <ul>
                            <li>Si el error indica "User already registered", prueba con otro correo electrónico.</li>
                            <li>Verifica que la contraseña cumpla con los requisitos mínimos (al menos 6 caracteres).</li>
                            <li>Si el error es sobre la tabla "usuario" o "perfiles", verifica que las tablas estén creadas correctamente.</li>
                            <li>Si persiste el problema, puedes intentar el método manual descrito a continuación.</li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif ($error_details !== null): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_details); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result === null || !$result['success']): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico:*</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:* (mínimo 6 caracteres)</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre:*</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="apellido_paterno">Apellido Paterno:*</label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" required>
                </div>
                <div class="form-group">
                    <label for="apellido_materno">Apellido Materno:</label>
                    <input type="text" id="apellido_materno" name="apellido_materno">
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono:*</label>
                    <input type="tel" id="telefono" name="telefono" required>
                </div>
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento:*</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="create_admin" class="btn-submit">Crear Administrador</button>
                </div>
            </form>
            
            <div style="margin-top: 30px; padding: 15px; background-color: #e7f3fe; border-left: 6px solid #2196F3;">
                <h3 style="margin-top: 0;">Método manual alternativo</h3>
                <p>Si el método automático no funciona, puedes seguir estos pasos:</p>
                <ol>
                    <li>Accede al <a href="https://app.supabase.com" target="_blank">Panel de Control de Supabase</a> e inicia sesión</li>
                    <li>Selecciona tu proyecto</li>
                    <li>En el menú izquierdo, ve a "Authentication" > "Users"</li>
                    <li>Haz clic en "Invite user" o "Add user"</li>
                    <li>Ingresa el correo electrónico y contraseña para el nuevo administrador</li>
                    <li>Una vez creado el usuario, copia el UUID que Supabase generó para ese usuario</li>
                    <li>En el menú izquierdo, ve a "SQL Editor"</li>
                    <li>Ejecuta las siguientes consultas SQL (reemplaza los valores según corresponda):</li>
                </ol>
                
                <pre style="background-color: #f8f9fa; padding: 10px; overflow-x: auto; font-size: 12px;">
-- Insertar en la tabla usuario
INSERT INTO public.usuario (
  user_id,
  nombre,
  apellido_paterno,
  apellido_materno,
  correo,
  telefono,
  fecha_nacimiento
) VALUES (
  'UUID_DEL_USUARIO', -- Reemplaza con el UUID real
  'Nombre_Admin',
  'Apellido_Paterno',
  'Apellido_Materno',
  'admin@ejemplo.com', -- Debe coincidir con el email usado en Auth
  '1234567890',
  '2000-01-01'
);

-- Insertar en la tabla perfiles
INSERT INTO public.perfiles (
  user_id,
  tipo_perfil
) VALUES (
  'UUID_DEL_USUARIO', -- El mismo UUID
  'administrador'
);
                </pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
