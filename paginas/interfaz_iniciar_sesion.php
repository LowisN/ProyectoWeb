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
            
            // Si el error es sobre el tipo de usuario o estructura de datos, mostrar enlaces para corregir
            if (strpos($_GET['error'], 'tipo de usuario') !== false || 
                strpos($_GET['error'], 'tipo_usuario') !== false || 
                strpos($_GET['error'], 'tipo_perfil') !== false) {
                echo '<p class="info-message">
                    Si estás teniendo problemas con tu tipo de usuario, puedes 
                    <a href="../config/corregir_tipos_usuario.php">ejecutar la herramienta de corrección</a>.
                </p>';
            }
            
            // Si el error es sobre estructura de datos o carga de perfil
            if (strpos($_GET['error'], 'estructura de datos') !== false || 
                strpos($_GET['error'], 'Error al cargar el perfil') !== false ||
                strpos($_GET['error'], 'No se pudo obtener el perfil') !== false) {
                echo '<p class="info-message">
                    Hemos detectado un problema con tu perfil. Puedes 
                    <a href="../config/diagnostico_perfiles.php?debug=chambanetdiag2024">ejecutar el diagnóstico de perfiles</a>
                    para intentar corregirlo.
                </p>';
                echo '<p class="info-message">
                    Si es un usuario nuevo, es posible que tu perfil no haya sido creado correctamente. Un administrador puede 
                    <a href="../admin/diagnostico_auth.php?debug=chambanetdiag2024">ejecutar la herramienta de diagnóstico de autenticación</a>
                    o <a href="../config/crear_perfiles_faltantes.php?debug=chambanetfix2024">ejecutar la herramienta para crear perfiles faltantes</a>
                    para solucionar este problema.
                </p>';
            }

            // Si el error es específicamente sobre crear el perfil de usuario
            if (strpos($_GET['error'], 'Error al crear el perfil de usuario') !== false) {
                echo '<p class="info-message">
                    Hemos detectado un problema al crear tu perfil de usuario. Un administrador puede 
                    <a href="../config/verificar_tablas.php?debug=chambanetsetup2024">verificar las tablas en Supabase</a>
                    para asegurarse de que la estructura de la base de datos es correcta.
                </p>';
            }
        }
        
        // Verificar si hay mensajes de éxito
        if (isset($_GET['success'])) {
            echo '<p class="success-message">' . htmlspecialchars($_GET['success']) . '</p>';
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