<?php
// Incluir cualquier configuración necesaria
// require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Recuperar Contraseña</h1>
        
        <?php
        // Verificar si hay mensajes de error o éxito
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        if (isset($_GET['success'])) {
            echo '<p class="success-message">' . htmlspecialchars($_GET['success']) . '</p>';
        }
        ?>
        
        <form action="../controllers/recuperar_password_controller.php" method="POST">
            <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
            
            <label for="email">Correo electrónico*</label>
            <input type="email" id="email" name="email" placeholder="Tu correo electrónico" required>
            
            <button type="submit">Enviar correo de recuperación</button>
        </form>
        
        <a href="interfaz_iniciar_sesion.php" class="volver">Volver a Inicio de Sesión</a>
    </div>
</body>
</html>
