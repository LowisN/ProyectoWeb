<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Perfil de Empresa</title>
      <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">

</head>
<body>
  <div class="perfil-empresa">
    <h1>Perfil de la Empresa</h1>

    <div class="campo">
      <strong>Nombre:</strong>
      <span class="valor">Soluciones XYZ S.A. de C.V.</span>
    </div>

    <div class="campo">
      <strong>Industria:</strong>
      <span class="valor">Tecnología</span>
    </div>

    <div class="campo">
      <strong>Dirección:</strong>
      <span class="valor">Av. Reforma 1234, CDMX, México</span>
    </div>

    <div class="campo">
      <strong>Teléfono:</strong>
      <span class="valor">+52 55 1234 5678</span>
    </div>

    <div class="campo">
      <strong>Sitio web:</strong>
      <a class="valor" href="https://www.ejemplo.com" target="_blank">www.ejemplo.com</a>
    </div>

    <h2>Contacto del Reclutador</h2>

    <div class="campo">
      <strong>Nombre:</strong>
      <span class="valor">María López</span>
    </div>

    <div class="campo">
      <strong>Cargo:</strong>
      <span class="valor">Gerente de Recursos Humanos</span>
    </div>
  </div>
</body>
</html>
<!-- 
Ajustar los campos para el css por completar en el formulario del perfil
<body class="sinMar">
    <div class="contenedor">
       <div class="logo">
            <img src="imagenes/logo.png" alt="Logo">
        </div>

        <h1>Inicia Sesión en ChambaNet!</h1>

        <form action="controllers/login_controller.php" method="POST">

            <label for="email">Correo electrónico*</label>
            <input type="email" id="email" name="email" placeholder="Ingresa tu correo" required>

            <label for="contrasena">Contraseña*</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>

            <label class="mostrar">
                <input type="checkbox" id="mostrarPass">
                Mostrar Contraseña
            </label>

            <script src="./scripts/mostrar_contraseña.js"></script>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <div class="enlaces" id="enlacesIs">
            <a href="paginas/elegir_registro.php" target="_self" class="enlaces">¿No tienes Usuario?<br>¡Regístrate Aquí!</a>
            <a href="paginas/recuperar_password.php" class="enlaces">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html> -->