<?php
// Incluir cualquier configuración necesaria
// require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Empresa - ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../estilo/formularios.css">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Registro de Empresa</h1>
        
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        
        <form action="../controllers/registro_empresa_controller.php" method="POST">
            <h2>Datos de la empresa</h2>
            
            <div class="form-group">
                <label for="nombre_empresa">Nombre de la empresa*</label>
                <input type="text" id="nombre_empresa" name="nombre_empresa" placeholder="Nombre legal de la empresa" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="rfc">RFC*</label>
                    <input type="text" id="rfc" name="rfc" placeholder="RFC de la empresa" required>
                </div>
                
                <div class="form-group">
                    <label for="industria">Industria*</label>
                    <input type="text" id="industria" name="industria" placeholder="Sector o industria" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="direccion_empresa">Dirección*</label>
                <input type="text" id="direccion_empresa" name="direccion_empresa" placeholder="Dirección completa" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono_empresa">Teléfono*</label>
                    <input type="tel" id="telefono_empresa" name="telefono_empresa" placeholder="Teléfono de contacto" required>
                </div>
                
                <div class="form-group">
                    <label for="sitio_web">Sitio web</label>
                    <input type="url" id="sitio_web" name="sitio_web" placeholder="https://www.ejemplo.com">
                </div>
            </div>
            
            <h2>Datos del reclutador</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre_reclutador">Nombre*</label>
                    <input type="text" id="nombre_reclutador" name="nombre_reclutador" placeholder="Nombre del reclutador" required>
                </div>
                
                <div class="form-group">
                    <label for="apellidos_reclutador">Apellidos*</label>
                    <input type="text" id="apellidos_reclutador" name="apellidos_reclutador" placeholder="Apellidos del reclutador" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico*</label>
                <input type="email" id="email" name="email" placeholder="correo@empresa.com" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contrasena">Contraseña*</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 8 caracteres" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar contraseña*</label>
                    <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="Repite la contraseña" required>
                </div>
            </div>
            
            <label class="mostrar">
                <input type="checkbox" id="mostrarPass">
                Mostrar Contraseña
            </label>
            
            <script src="../scripts/mostrar_contraseña.js"></script>
            
            <div class="form-group">
                <label for="cargo">Cargo o puesto*</label>
                <input type="text" id="cargo" name="cargo" placeholder="Cargo en la empresa" required>
            </div>
            
            <p>* Campos obligatorios</p>
            
            <button type="submit">Registrar Empresa</button>
        </form>
        
        <a href="elegir_registro.php" class="enlaces">Volver</a>
    </div>
</body>
</html>
