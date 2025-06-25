<?php
// Importar configuración de Supabase
require_once '../../config/supabase.php';

// Función para crear usuario admin usando el método alternativo
// Esta función usa el panel de Supabase directamente cuando la API falla
function mostrar_instrucciones_manuales() {
    echo '
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <h3>Instrucciones para crear un administrador manualmente en Supabase</h3>
        <ol>
            <li>Accede al <a href="https://app.supabase.com" target="_blank">Panel de Control de Supabase</a> e inicia sesión</li>
            <li>Selecciona tu proyecto</li>
            <li>En el menú izquierdo, ve a "Authentication" > "Users"</li>
            <li>Haz clic en "Invite user" o "Add user" (dependiendo de la versión)</li>
            <li>Ingresa el correo electrónico y contraseña para el nuevo administrador</li>
            <li>Una vez creado el usuario, copia el UUID que Supabase generó para ese usuario</li>
            <li>Luego, ejecuta las siguientes consultas SQL en el "SQL Editor" de Supabase:</li>
        </ol>

        <div style="background-color: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin-top: 15px;">
            -- Reemplaza \'UUID_DEL_USUARIO\' con el UUID real del usuario que creaste<br>
            -- Reemplaza los demás datos con la información del administrador<br><br>
            
            -- 1. Insertar en la tabla usuario<br>
            INSERT INTO public.usuario (<br>
              user_id,<br>
              nombre,<br>
              apellido_paterno,<br>
              apellido_materno,<br>
              correo,<br>
              telefono,<br>
              fecha_nacimiento<br>
            ) VALUES (<br>
              \'UUID_DEL_USUARIO\',<br>
              \'Nombre_Admin\',<br>
              \'Apellido_Paterno\',<br>
              \'Apellido_Materno\',<br>
              \'admin@ejemplo.com\',<br>
              \'1234567890\',<br>
              \'1990-01-01\'<br>
            );<br><br>
            
            -- 2. Insertar en la tabla perfiles como administrador<br>
            INSERT INTO public.perfiles (<br>
              user_id,<br>
              tipo_perfil<br>
            ) VALUES (<br>
              \'UUID_DEL_USUARIO\',<br>
              \'administrador\'<br>
            );
        </div>
        
        <p style="margin-top: 15px;"><strong>Nota:</strong> Asegúrate de reemplazar \'UUID_DEL_USUARIO\' con el UUID real generado por Supabase y actualizar los demás datos según corresponda.</p>
    </div>
    ';
}

// Método alternativo utilizando el cliente de GoTrue de Supabase
function crear_usuario_admin_alternativo() {
    global $supabase_url, $supabase_key;
    
    echo '
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <h3>Método alternativo usando el cliente JavaScript de Supabase</h3>
        <p>Como alternativa, puedes usar el cliente JavaScript de Supabase para crear usuarios administradores. Este enfoque puede funcionar mejor que la API REST directa en PHP.</p>
        
        <div style="background-color: #f5f5f5; padding: 10px; border-radius: 4px; margin-top: 15px;">
            <form id="createAdminForm">
                <div style="margin-bottom: 10px;">
                    <label for="js_email" style="display: block; margin-bottom: 5px;">Correo Electrónico:</label>
                    <input type="email" id="js_email" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_password" style="display: block; margin-bottom: 5px;">Contraseña:</label>
                    <input type="password" id="js_password" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_nombre" style="display: block; margin-bottom: 5px;">Nombre:</label>
                    <input type="text" id="js_nombre" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_apellido_paterno" style="display: block; margin-bottom: 5px;">Apellido Paterno:</label>
                    <input type="text" id="js_apellido_paterno" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_apellido_materno" style="display: block; margin-bottom: 5px;">Apellido Materno:</label>
                    <input type="text" id="js_apellido_materno" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_telefono" style="display: block; margin-bottom: 5px;">Teléfono:</label>
                    <input type="tel" id="js_telefono" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label for="js_fecha_nacimiento" style="display: block; margin-bottom: 5px;">Fecha de Nacimiento:</label>
                    <input type="date" id="js_fecha_nacimiento" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Crear Administrador</button>
            </form>
            <div id="result" style="margin-top: 15px; padding: 10px; display: none;"></div>
        </div>
        
        <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
        <script>
            // Inicializar el cliente de Supabase
            const supabaseUrl = \'' . $supabase_url . '\';
            const supabaseKey = \'' . $supabase_key . '\';
            const supabase = supabase.createClient(supabaseUrl, supabaseKey);
            
            document.getElementById("createAdminForm").addEventListener("submit", async function(e) {
                e.preventDefault();
                
                const resultDiv = document.getElementById("result");
                resultDiv.style.display = "block";
                resultDiv.innerHTML = "Procesando solicitud...";
                resultDiv.style.backgroundColor = "#f8f9fa";
                resultDiv.style.color = "#333";
                
                try {
                    // 1. Crear usuario en Auth
                    const email = document.getElementById("js_email").value;
                    const password = document.getElementById("js_password").value;
                    
                    // Usar la API de admin para crear el usuario
                    const { data: userData, error: authError } = await supabase.auth.admin.createUser({
                        email: email,
                        password: password,
                        email_confirm: true
                    });
                    
                    if (authError) throw authError;
                    
                    // 2. Insertar en la tabla usuario
                    const userId = userData.user.id;
                    const nombre = document.getElementById("js_nombre").value;
                    const apellidoPaterno = document.getElementById("js_apellido_paterno").value;
                    const apellidoMaterno = document.getElementById("js_apellido_materno").value;
                    const telefono = document.getElementById("js_telefono").value;
                    const fechaNacimiento = document.getElementById("js_fecha_nacimiento").value;
                    
                    const { error: userError } = await supabase
                        .from("usuario")
                        .insert({
                            user_id: userId,
                            nombre: nombre,
                            apellido_paterno: apellidoPaterno,
                            apellido_materno: apellidoMaterno,
                            correo: email,
                            telefono: telefono,
                            fecha_nacimiento: fechaNacimiento
                        });
                    
                    if (userError) throw userError;
                    
                    // 3. Insertar en la tabla perfiles
                    const { error: profileError } = await supabase
                        .from("perfiles")
                        .insert({
                            user_id: userId,
                            tipo_perfil: "administrador"
                        });
                    
                    if (profileError) throw profileError;
                    
                    // Mostrar resultado exitoso
                    resultDiv.innerHTML = `
                        <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">
                            <strong>¡Éxito!</strong> Administrador creado correctamente con ID: ${userId}<br>
                            Correo: ${email}
                        </div>
                    `;
                    
                } catch (error) {
                    console.error("Error:", error);
                    
                    // Mostrar error
                    resultDiv.innerHTML = `
                        <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">
                            <strong>Error:</strong> ${error.message || error}<br>
                            <pre>${JSON.stringify(error, null, 2)}</pre>
                        </div>
                    `;
                }
            });
        </script>
    </div>
    ';
}

// Verificar si el formulario PHP ha sido enviado
$php_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    // Incluir la función original de PHP
    include_once 'crear_administrador_original.php';
    
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
        $php_result = [
            'success' => false,
            'message' => "Todos los campos obligatorios deben ser completados"
        ];
    } else {
        $result = crear_usuario_administrador(
            $email, $password, $nombre, $apellido_paterno, 
            $apellido_materno, $telefono, $fecha_nacimiento
        );
        
        $php_result = $result ?: [
            'success' => false,
            'message' => "No se pudo crear el administrador. Ver detalles arriba."
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Administrador</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f8f9fa;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background-color: #007bff;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background-color: #0069d9;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Usuario Administrador</h1>
        
        <?php if ($php_result !== null): ?>
            <div class="alert <?php echo $php_result['success'] ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $php_result['message']; ?>
                <?php if ($php_result['success'] && isset($php_result['user_id'])): ?>
                    <br>ID de usuario: <?php echo $php_result['user_id']; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="php">Método PHP</div>
            <div class="tab" data-tab="js">Método JavaScript</div>
            <div class="tab" data-tab="manual">Método Manual</div>
        </div>
        
        <div class="tab-content active" id="php-tab">
            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico:*</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:*</label>
                    <input type="password" id="password" name="password" required>
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
                    <input type="submit" name="create_admin" value="Crear Administrador" class="btn-submit">
                </div>
            </form>
        </div>
        
        <div class="tab-content" id="js-tab">
            <?php crear_usuario_admin_alternativo(); ?>
        </div>
        
        <div class="tab-content" id="manual-tab">
            <?php mostrar_instrucciones_manuales(); ?>
        </div>
    </div>
    
    <script>
        // Script para manejar las pestañas
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remover la clase active de todas las pestañas y contenidos
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Añadir la clase active a la pestaña actual
                this.classList.add('active');
                
                // Mostrar el contenido correspondiente
                const tabName = this.getAttribute('data-tab');
                document.getElementById(`${tabName}-tab`).classList.add('active');
            });
        });
    </script>
</body>
</html>
