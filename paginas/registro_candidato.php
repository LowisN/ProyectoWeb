<?php
session_start();
require_once '../config/supabase.php';

// Comprobar si estamos iniciando un nuevo registro o continuando uno existente
if (!isset($_SESSION['registro_candidato']) || isset($_GET['reset'])) {
    $_SESSION['registro_candidato'] = [
        'paso_actual' => 1,
        'datos_personales' => [],
        'datos_academicos' => [],
        'datos_profesionales' => [],
        'habilidades' => []
    ];
}
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
        
        <form id="registroForm" action="../controllers/registro_candidato_unificado_controller.php" method="POST">
            <input type="hidden" name="paso" value="1">
            
            <h2>Datos de la cuenta</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre*</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['nombre']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['nombre']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="apellidos">Apellidos*</label>
                    <input type="text" id="apellidos" name="apellidos" placeholder="Tus apellidos" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['apellidos']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['apellidos']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico*</label>
                <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['email']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['email']) : ''; ?>" required>
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
                    <input type="tel" id="telefono" name="telefono" placeholder="Tu número de teléfono" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['telefono']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['telefono']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de nacimiento*</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['fecha_nacimiento']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['fecha_nacimiento']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección*</label>
                <input type="text" id="direccion" name="direccion" placeholder="Tu dirección completa" value="<?php echo isset($_SESSION['registro_candidato']['datos_personales']['direccion']) ? htmlspecialchars($_SESSION['registro_candidato']['datos_personales']['direccion']) : ''; ?>" required>
            </div>
            
            <p>* Campos obligatorios</p>
            <button type="submit">Siguiente: Datos Académicos y Profesionales</button>
        </form>
        
        <a href="elegir_registro.php" class="enlaces">Volver</a>
    </div>

    <script>
        // Validaciones adicionales del lado del cliente
        document.getElementById('registroForm').addEventListener('submit', function(event) {
            const contrasena = document.getElementById('contrasena').value;
            const confirmar_contrasena = document.getElementById('confirmar_contrasena').value;
            
            if (contrasena !== confirmar_contrasena) {
                event.preventDefault();
                alert('Las contraseñas no coinciden');
            }
            
            if (contrasena.length < 8) {
                event.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres');
            }
        });
    </script>
</body>
</html>
    </script>
</body>
</html>
