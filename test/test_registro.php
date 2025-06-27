<?php
// Script para probar el proceso de registro sin UI
require_once 'config/supabase.php';

echo "<h1>Prueba de Registro de Usuario</h1>";

// Verificar si el formulario de prueba fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $tipo_usuario = $_POST['tipo_usuario'];
    
    $userData = [
        'nombre' => 'Test',
        'apellidos' => 'Usuario'
    ];
    
    echo "<h2>Ejecutando prueba de registro...</h2>";
    
    // Ejecutar la prueba paso a paso
    $result = debugRegistrationProcess($email, $password, $userData, $tipo_usuario);
    
    echo "<h3>Resultado:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    
    if ($result['success']) {
        echo "<p style='color:green;'>¡Registro exitoso! Se completaron todos los pasos.</p>";
    } else {
        echo "<p style='color:red;'>Error en el paso: " . htmlspecialchars($result['step']) . "</p>";
    }
} else {
    // Mostrar formulario de prueba
    ?>
    <p>Este script prueba el proceso de registro sin usar los controladores completos.</p>
    <form method="post" action="">
        <div>
            <label for="email">Email de prueba:</label>
            <input type="email" id="email" name="email" value="test_<?php echo time(); ?>@ejemplo.com" required>
        </div>
        <div>
            <label for="password">Contraseña:</label>
            <input type="text" id="password" name="password" value="Password123!" required>
        </div>
        <div>
            <label for="tipo_usuario">Tipo de usuario:</label>
            <select id="tipo_usuario" name="tipo_usuario">
                <option value="candidato">Candidato</option>
                <option value="reclutador">Reclutador</option>
            </select>
        </div>
        <div>
            <button type="submit">Probar Registro</button>
        </div>
    </form>
    <?php
}

// Mostrar información sobre la estructura de la tabla perfiles
echo "<h2>Estructura de la tabla 'perfiles'</h2>";
$perfilesStructure = supabaseCheckTableStructure('perfiles');

if ($perfilesStructure['exists']) {
    echo "<p style='color:green;'>La tabla existe</p>";
    
    echo "<h3>Ejemplo de registro (si existe):</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($perfilesStructure['sample'], JSON_PRETTY_PRINT)) . "</pre>";
    
    if ($perfilesStructure['description']) {
        echo "<h3>Descripción de la tabla:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($perfilesStructure['description'], JSON_PRETTY_PRINT)) . "</pre>";
    }
} else {
    echo "<p style='color:red;'>La tabla no existe o no es accesible</p>";
    echo "<pre>" . htmlspecialchars(json_encode($perfilesStructure['error'], JSON_PRETTY_PRINT)) . "</pre>";
}

// Mostrar un formulario para probar la inserción directa en la tabla perfiles
echo "<h2>Probar inserción directa en 'perfiles'</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
    $testUserId = $_POST['user_id'];
    $testTipoUsuario = $_POST['tipo_usuario_insert'];
    
    $testPerfilData = [
        'user_id' => $testUserId,
        'tipo_usuario' => $testTipoUsuario
    ];
    
    echo "<p>Intentando insertar:</p>";
    echo "<pre>" . htmlspecialchars(json_encode($testPerfilData, JSON_PRETTY_PRINT)) . "</pre>";
    
    $insertResult = supabaseInsert('perfiles', $testPerfilData);
    
    echo "<p>Resultado:</p>";
    echo "<pre>" . htmlspecialchars(json_encode($insertResult, JSON_PRETTY_PRINT)) . "</pre>";
    
    if (isset($insertResult['error'])) {
        echo "<p style='color:red;'>Error al insertar</p>";
    } else {
        echo "<p style='color:green;'>Inserción exitosa</p>";
    }
} else {
    ?>
    <form method="post" action="">
        <div>
            <label for="user_id">ID de usuario (UUID):</label>
            <input type="text" id="user_id" name="user_id" value="<?php echo uniqid(); ?>" required>
            <small>Ingresa un UUID válido</small>
        </div>
        <div>
            <label for="tipo_usuario_insert">Tipo de usuario:</label>
            <select id="tipo_usuario_insert" name="tipo_usuario_insert">
                <option value="candidato">Candidato</option>
                <option value="reclutador">Reclutador</option>
            </select>
        </div>
        <div>
            <input type="hidden" name="test_insert" value="1">
            <button type="submit">Probar Inserción</button>
        </div>
    </form>
    <?php
}
?>

<p><a href="verificar_tablas.php">Volver al verificador de tablas</a></p>
