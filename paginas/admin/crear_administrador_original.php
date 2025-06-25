<?php
// Guarda una copia de la función original para usarla en el archivo mejorado
// Esta función debe ejecutarse solo por usuarios autorizados
// En un entorno de producción, añadir más seguridad aquí
function crear_usuario_administrador($email, $password, $nombre, $apellido_paterno, $apellido_materno, $telefono, $fecha_nacimiento) {
    global $supabase_url, $supabase_key;

    // 1. Crear usuario en Supabase Auth
    $ch = curl_init();
    
    // Usar la URL correcta para la API de administración de usuarios
    // Nota: La API puede variar según la versión de Supabase
    curl_setopt($ch, CURLOPT_URL, "$supabase_url/auth/v1/admin/users");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key
    ]);

    // Datos para crear el usuario en Auth
    $data = [
        'email' => $email,
        'password' => $password,
        'email_confirmed' => true // Confirmar el email (nota: en algunas versiones es email_confirm)
    ];

    // Mostrar la solicitud que se está enviando (Solo para depuración)
    echo "<pre style='display:none;'>Enviando solicitud a: $supabase_url/auth/v1/admin/users\nDatos: " . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Verificar si hubo error
    if ($status !== 200) {
        // Mostrar información detallada del error para depuración
        echo "<div style='color: red; margin: 10px 0; padding: 10px; border: 1px solid red; background-color: #ffeeee;'>";
        echo "<strong>Error al crear usuario en Auth</strong><br>";
        echo "Código de estado: $status<br>";
        
        if (!empty($curl_error)) {
            echo "Error de cURL: $curl_error<br>";
        }
        
        echo "Respuesta: <pre>" . htmlspecialchars($response) . "</pre>";
        
        // Intentar parsear la respuesta JSON para obtener más detalles
        $response_data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($response_data['message'])) {
            echo "Mensaje de error: " . htmlspecialchars($response_data['message']) . "<br>";
            
            if (isset($response_data['error'])) {
                echo "Error específico: " . htmlspecialchars($response_data['error']) . "<br>";
            }
        }
        
        echo "</div>";
        
        // Proporcionar una posible solución
        echo "<div style='color: #856404; margin: 10px 0; padding: 10px; border: 1px solid #ffeeba; background-color: #fff3cd;'>";
        echo "<strong>Posibles soluciones:</strong><br>";
        echo "1. Verifica que la URL y la clave API de Supabase sean correctas.<br>";
        echo "2. Asegúrate de que tu servicio de Supabase tiene habilitado el acceso a la API de admin/users.<br>";
        echo "3. Prueba registrar al usuario a través del panel de Supabase directamente.<br>";
        echo "4. Si el correo ya está registrado, intenta con otro diferente.<br>";
        echo "</div>";
        
        return false;
    }

    // Extraer el user_id del response
    $user_data = json_decode($response, true);
    $user_id = $user_data['id'];

    // 2. Insertar en la tabla usuario
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$supabase_url/rest/v1/usuario");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Prefer: return=representation'
    ]);

    // Datos para la tabla usuario
    $userData = [
        'user_id' => $user_id,
        'nombre' => $nombre,
        'apellido_paterno' => $apellido_paterno,
        'apellido_materno' => $apellido_materno,
        'correo' => $email,
        'telefono' => $telefono,
        'fecha_nacimiento' => $fecha_nacimiento
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificar si hubo error
    if ($status < 200 || $status >= 300) {
        echo "Error al insertar en tabla usuario: " . $response;
        return false;
    }

    // 3. Insertar en la tabla perfiles como administrador
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$supabase_url/rest/v1/perfiles");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Prefer: return=representation'
    ]);

    // Datos para la tabla perfiles
    $profileData = [
        'user_id' => $user_id,
        'tipo_perfil' => 'administrador'
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($profileData));
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificar si hubo error
    if ($status < 200 || $status >= 300) {
        echo "Error al insertar en tabla perfiles: " . $response;
        return false;
    }

    return [
        'success' => true,
        'user_id' => $user_id,
        'message' => 'Usuario administrador creado exitosamente'
    ];
}
