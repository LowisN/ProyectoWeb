<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicia Sesión en ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
</head>

<body>
    <div class="contenedor">
       <div class="logo">
            <img src="./imagenes/logo.png" alt="Logo">
        </div>

        <h1>Inicia Sesión en ChambaNet!</h1>
        <form action="login.php" method="POST">

            <label for="usuario">Usuario*</label>
            <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>

            <label for="contrasena">Contraseña*</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>

            <label class="mostrar">
                <input type="checkbox" id="mostrarPass">
                Mostrar Contraseña
            </label>

          <script src="./scripts/mostrar_contraseña.js"></script>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <div class="enlaces">
            <a target="_self" href="./paginas/elegir_empresa_usuario.html">No tienes Usuario?<br>¡ Regístrate Aquí !</a>
            <a href="#">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>