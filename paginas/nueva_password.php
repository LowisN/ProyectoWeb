<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>      
    <link rel="stylesheet" href="../estilo/nueva_password.css">
</head>
<body>
    
    <div class="form-container">
    <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo">
        </div>
        <h2>Restablecer tu contraseña</h2>
        <p class="subtitulo">Ingresa tu nueva contraseña para continuar</p>

        <form id="reset-form">
            <input type="password" id="nuevaClave" placeholder="Nueva contraseña" required>
            <input type="password" id="confirmarClave" placeholder="Confirmar contraseña" required>
            <button type="submit">Guardar nueva contraseña</button>
        </form>
        <p id="mensaje" class="mensaje"></p>
        <button id="btn-volver" onclick="window.location.href='../index.php'">Ir al inicio ahora</button>
    </div>
    <script src="../scripts/cambiar_password.js"></script>
</body>
</html>