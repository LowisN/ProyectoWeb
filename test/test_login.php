<?php
// Script para diagnosticar problemas con el inicio de sesión
require_once 'config/supabase.php';

echo "<h1>Diagnóstico del proceso de inicio de sesión</h1>";

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    echo "<h2>Probando inicio de sesión para el correo: " . htmlspecialchars($email) . "</h2>";
    
    // 1. Verificar que el usuario existe en Supabase Auth
    echo "<h3>Paso 1: Verificar existencia del usuario en Supabase Auth</h3>";
    
    // Intentar iniciar sesión con Supabase
    echo "<p>Intentando iniciar sesión con Supabase...</p>";
    $authResponse = supabaseSignIn($email, $password);
    
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        $errorMessage = isset($authResponse['error_description']) 
            ? $authResponse['error_description'] 
            : (isset($authResponse['message']) ? $authResponse['message'] : 'Error desconocido');
        
        echo "<p style='color: red;'>Error de autenticación: " . htmlspecialchars($errorMessage) . "</p>";
        echo "<pre>" . htmlspecialchars(json_encode($authResponse, JSON_PRETTY_PRINT)) . "</pre>";
        
        echo "<p><strong>Posibles causas:</strong></p>";
        echo "<ul>";
        echo "<li>El usuario no existe en Supabase Auth</li>";
        echo "<li>La contraseña es incorrecta</li>";
        echo "<li>El email no está verificado (si se requiere verificación)</li>";
        echo "<li>El usuario está bloqueado o deshabilitado</li>";
        echo "</ul>";
        
        echo "<p><strong>Soluciones recomendadas:</strong></p>";
        echo "<ul>";
        echo "<li>Verifica que el usuario existe en Supabase Auth</li>";
        echo "<li>Restablece la contraseña si es necesario</li>";
        echo "<li>Comprueba la configuración de Supabase Auth</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>Autenticación exitosa</p>";
        echo "<p>Datos del usuario:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($authResponse['user'], JSON_PRETTY_PRINT)) . "</pre>";
        
        // 2. Verificar que el perfil existe en la tabla perfiles
        echo "<h3>Paso 2: Verificar existencia del perfil en la tabla 'perfiles'</h3>";
        $userId = $authResponse['user']['id'];
        
        echo "<p>Buscando perfil para el user_id: " . htmlspecialchars($userId) . "</p>";
        $perfilResponse = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        
        if (empty($perfilResponse)) {
            echo "<p style='color: red;'>No se encontró un perfil asociado a este usuario</p>";
            
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>El registro no completó correctamente la creación del perfil</li>";
            echo "<li>El registro del perfil falló pero el usuario se creó en Auth</li>";
            echo "<li>El perfil fue eliminado manualmente</li>";
            echo "</ul>";
            
            echo "<p><strong>Soluciones recomendadas:</strong></p>";
            echo "<ul>";
            echo "<li>Crear manualmente el perfil del usuario</li>";
            echo "<li>Revisar el proceso de registro para asegurar que crea perfiles correctamente</li>";
            echo "</ul>";
            
            // Ofrecer crear un perfil
            echo "<h4>Crear perfil manualmente</h4>";
            echo "<form method='post' action=''>";
            echo "<input type='hidden' name='create_profile' value='1'>";
            echo "<input type='hidden' name='user_id' value='" . htmlspecialchars($userId) . "'>";
            echo "<input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>";
            echo "<label for='tipo_usuario'>Tipo de usuario:</label>";
            echo "<select id='tipo_usuario' name='tipo_usuario'>";
            echo "<option value='candidato'>Candidato</option>";
            echo "<option value='reclutador'>Reclutador</option>";
            echo "</select>";
            echo "<button type='submit'>Crear perfil</button>";
            echo "</form>";
        } else {
            echo "<p style='color: green;'>Perfil encontrado</p>";
            echo "<pre>" . htmlspecialchars(json_encode($perfilResponse[0], JSON_PRETTY_PRINT)) . "</pre>";
            
            // 3. Verificar que el tipo de usuario es correcto
            echo "<h3>Paso 3: Verificar tipo de usuario</h3>";
            $tipoUsuario = $perfilResponse[0]['tipo_usuario'];
            
            echo "<p>Tipo de usuario: " . htmlspecialchars($tipoUsuario) . "</p>";
            
            // 4. Verificar que existe el registro correspondiente en la tabla específica del tipo de usuario
            echo "<h3>Paso 4: Verificar registro en tabla específica</h3>";
            
            $perfilId = $perfilResponse[0]['id'];
            $tablaEspecifica = $tipoUsuario === 'candidato' ? 'candidatos' : 'reclutadores';
            
            echo "<p>Buscando registro en la tabla '{$tablaEspecifica}' para el perfil_id: " . htmlspecialchars($perfilId) . "</p>";
            $registroEspecifico = supabaseFetch($tablaEspecifica, '*', ['perfil_id' => $perfilId]);
            
            if (empty($registroEspecifico)) {
                echo "<p style='color: red;'>No se encontró un registro en la tabla específica</p>";
                
                echo "<p><strong>Posibles causas:</strong></p>";
                echo "<ul>";
                echo "<li>El registro no completó correctamente la creación del registro específico</li>";
                echo "<li>El registro específico fue eliminado manualmente</li>";
                echo "</ul>";
                
                echo "<p><strong>Soluciones recomendadas:</strong></p>";
                echo "<ul>";
                echo "<li>Crear manualmente el registro específico</li>";
                echo "<li>Revisar el proceso de registro para asegurar que crea los registros específicos correctamente</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: green;'>Registro específico encontrado</p>";
                echo "<pre>" . htmlspecialchars(json_encode($registroEspecifico[0], JSON_PRETTY_PRINT)) . "</pre>";
                
                // 5. Simular proceso de inicio de sesión
                echo "<h3>Paso 5: Simular proceso de inicio de sesión</h3>";
                
                echo "<p>Con la información obtenida, el inicio de sesión debería redireccionar a: ";
                
                switch ($tipoUsuario) {
                    case 'administrador':
                        echo "paginas/admin/dashboard.php";
                        break;
                    case 'candidato':
                        echo "paginas/candidato/home_candidato.php";
                        break;
                    case 'reclutador':
                        echo "paginas/empresa/home_empresa.php";
                        break;
                    default:
                        echo "Redirección desconocida para tipo: " . htmlspecialchars($tipoUsuario);
                        break;
                }
                
                echo "</p>";
                
                echo "<p style='color: green;'>El usuario parece estar configurado correctamente y debería poder iniciar sesión.</p>";
                
                // Mostrar un botón para volver a intentar iniciar sesión
                echo "<p><a href='index.php' class='button'>Volver a iniciar sesión</a></p>";
            }
        }
    }
} elseif (isset($_POST['create_profile'])) {
    // Crear perfil manualmente
    $userId = $_POST['user_id'];
    $tipoUsuario = $_POST['tipo_usuario'];
    
    echo "<h2>Creando perfil manualmente</h2>";
    echo "<p>User ID: " . htmlspecialchars($userId) . "</p>";
    echo "<p>Tipo de usuario: " . htmlspecialchars($tipoUsuario) . "</p>";
    
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => $tipoUsuario
    ];
    
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    if (isset($perfilResponse['error'])) {
        echo "<p style='color: red;'>Error al crear el perfil: " . htmlspecialchars(json_encode($perfilResponse)) . "</p>";
    } else {
        echo "<p style='color: green;'>Perfil creado exitosamente</p>";
        echo "<pre>" . htmlspecialchars(json_encode($perfilResponse, JSON_PRETTY_PRINT)) . "</pre>";
        
        // Si es un candidato o reclutador, ofrecer crear el registro específico
        if ($tipoUsuario === 'candidato' || $tipoUsuario === 'reclutador') {
            // Obtener el ID del perfil creado
            $perfilId = null;
            
            if (isset($perfilResponse[0]['id'])) {
                $perfilId = $perfilResponse[0]['id'];
            } elseif (isset($perfilResponse['id'])) {
                $perfilId = $perfilResponse['id'];
            } else {
                // Buscar el perfil recién creado
                $perfilFetch = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
                if (!empty($perfilFetch)) {
                    $perfilId = $perfilFetch[0]['id'];
                }
            }
            
            if ($perfilId) {
                echo "<h3>Crear registro específico en tabla '" . ($tipoUsuario === 'candidato' ? 'candidatos' : 'reclutadores') . "'</h3>";
                echo "<form method='post' action=''>";
                echo "<input type='hidden' name='create_specific' value='1'>";
                echo "<input type='hidden' name='perfil_id' value='" . htmlspecialchars($perfilId) . "'>";
                echo "<input type='hidden' name='tipo_usuario' value='" . htmlspecialchars($tipoUsuario) . "'>";
                echo "<input type='hidden' name='email' value='" . htmlspecialchars($_POST['email']) . "'>";
                
                if ($tipoUsuario === 'candidato') {
                    echo "<div><label>Teléfono: <input type='text' name='telefono' value='' required></label></div>";
                    echo "<div><label>Fecha de Nacimiento: <input type='date' name='fecha_nacimiento' required></label></div>";
                    echo "<div><label>Dirección: <input type='text' name='direccion' value='' required></label></div>";
                    echo "<div><label>Título: <input type='text' name='titulo' value=''></label></div>";
                    echo "<div><label>Años de Experiencia: <input type='number' name='anios_experiencia' value='0'></label></div>";
                } else {
                    echo "<div><label>Empresa ID: <input type='text' name='empresa_id' value='' required></label></div>";
                    echo "<div><label>Posición: <input type='text' name='posicion' value='' required></label></div>";
                    echo "<div><label>Teléfono: <input type='text' name='telefono' value='' required></label></div>";
                }
                
                echo "<button type='submit'>Crear registro específico</button>";
                echo "</form>";
            } else {
                echo "<p style='color: red;'>No se pudo determinar el ID del perfil creado.</p>";
            }
        }
    }
} elseif (isset($_POST['create_specific'])) {
    // Crear registro específico
    $perfilId = $_POST['perfil_id'];
    $tipoUsuario = $_POST['tipo_usuario'];
    
    echo "<h2>Creando registro específico</h2>";
    echo "<p>Perfil ID: " . htmlspecialchars($perfilId) . "</p>";
    
    if ($tipoUsuario === 'candidato') {
        $data = [
            'perfil_id' => $perfilId,
            'telefono' => $_POST['telefono'],
            'fecha_nacimiento' => $_POST['fecha_nacimiento'],
            'direccion' => $_POST['direccion'],
            'titulo' => empty($_POST['titulo']) ? null : $_POST['titulo'],
            'anios_experiencia' => intval($_POST['anios_experiencia'])
        ];
        
        $tabla = 'candidatos';
    } else {
        $data = [
            'perfil_id' => $perfilId,
            'empresa_id' => $_POST['empresa_id'],
            'posicion' => $_POST['posicion'],
            'telefono' => $_POST['telefono']
        ];
        
        $tabla = 'reclutadores';
    }
    
    echo "<p>Datos a insertar:</p>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    
    $response = supabaseInsert($tabla, $data);
    
    if (isset($response['error'])) {
        echo "<p style='color: red;'>Error al crear el registro: " . htmlspecialchars(json_encode($response)) . "</p>";
    } else {
        echo "<p style='color: green;'>Registro creado exitosamente</p>";
        echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . "</pre>";
        
        echo "<p><a href='test_login.php' class='button'>Volver al diagnóstico de inicio de sesión</a></p>";
        echo "<p><a href='index.php' class='button'>Ir a la página de inicio de sesión</a></p>";
    }
} else {
    // Mostrar formulario para probar inicio de sesión
    ?>
    <p>Este script diagnóstica problemas con el inicio de sesión.</p>
    <form method="post" action="">
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit">Diagnosticar</button>
        </div>
    </form>
    <?php
}
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    h1, h2, h3 { color: #333; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .button { display: inline-block; background-color: #4CAF50; color: white; padding: 8px 16px; 
              text-decoration: none; border-radius: 4px; margin-right: 10px; }
    input[type="text"], input[type="email"], input[type="password"], select {
        width: 100%;
        padding: 8px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    button { background-color: #4CAF50; color: white; padding: 10px 15px; 
             border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background-color: #45a049; }
</style>
