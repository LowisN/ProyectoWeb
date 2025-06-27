<?php
// Incluir cualquier configuración necesaria
// require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Candidato - ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../estilo/formularios.css">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Registro de Candidato</h1>
        
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        
        <form action="../controllers/registro_candidato_controller.php" method="POST">
            <h2>Datos de la cuenta</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre*</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="apellidos">Apellidos*</label>
                    <input type="text" id="apellidos" name="apellidos" placeholder="Tus apellidos" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico*</label>
                <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contrasena">Contraseña*</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 8 caracteres" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar contraseña*</label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="Repite tu contraseña" required>
                </div>
            </div>
            
            <h2>Datos personales</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Teléfono*</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="Tu número de teléfono" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de nacimiento*</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección*</label>
                <input type="text" id="direccion" name="direccion" placeholder="Tu dirección completa" required>
            </div>
            
            <input type="buttom" value="Continuar" onclick="window.location.href='./candidato/datosEyP_candidato.php'">
            <p>* Campos obligatorios</p>
            
            
        </form>
        
        <a href="elegir_registro.php" class="enlaces">Volver</a>
    </div>
</body>
</html>
