<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicia Sesión en ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
</head>

<body class="sinMar">
    <div class="contenedor">
       <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo">
        </div>

        <h1>Inicia Sesión en ChambaNet!</h1>
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        <form action="../controllers/login_controller.php" method="POST">

            <label for="email">Correo electrónico*</label>
            <input type="email" id="email" name="email" placeholder="Ingresa tu correo" required>

            <label for="contrasena">Contraseña*</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>

            <label class="mostrar">
                <input type="checkbox" id="mostrarPass">
                Mostrar Contraseña
            </label>

            <script src="../scripts/mostrar_contraseña.js"></script>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <div class="enlaces">
            <a target="_self" href="elegir_registro.php">No tienes Usuario?<br>¡ Regístrate Aquí !</a>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>