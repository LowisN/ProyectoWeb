<?php
// Incluir cualquier configuración necesaria
// require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Candidato - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Registro de Candidato</h1>
        
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        
        <form action="../../controllers/registro_candidato_controller.php" method="POST">
 <!-- Tabla educacion -->
             <h2>Datos Academicos</h2>
            
            <div class="form-group">
                <label for="institucion">Institucion Academica</label>
                <input type="text" id="titulo" name="titulo" placeholder="IPN, UNAM, etc.">
            </div>
   
            <div class="form-group">
                <label for="titulo">Titulo Profesional*</label>
                <input type="number" id="experiencia" name="experiencia" min="0" placeholder="Ej. Ingeniero en Redes" required>
            </div>

            <div class="form-group">
                <label for="area">Area de expertis*</label>
                <input type="number" id="experiencia" name="experiencia" min="0" placeholder="Tipo de area de su carrera" required>
            </div>
             <div class="form-group">
                <label for="fechaFin">Estudiando*</label>
                <input type="checkbox" id="en_curso" name="en_curso" min="0" placeholder="Se encuentra estudiando actualmente" required>
            </div>
            <div class="form-group">
                <label for="fechaIni">Fecha de Inicio*</label>
                <input type="date" id="experiencia" name="experiencia" min="0" placeholder="Inicio de carrera profesional" required>
            </div>
            <div class="form-group">
                <label for="fechaFin">Fecha de Finalizacion*</label>
                <input type="date" id="experiencia" name="experiencia" min="0" placeholder="Termino de carrera profesional" required>
            </div>
                
            
<!-- Tabla experiencia_laboral -->
            <h2>Datos profesionales</h2>
            
            <div class="form-group">
                <label for="titulo">Empresa </label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. IBM, Google, etc.">
            </div>
            
            <div class="form-group">
                <label for="experiencia">Años laborados en la empresa*</label>
                <input type="number" id="experiencia" name="experiencia" min="0" placeholder="Años de experiencia en el área" required>
            </div>
                       
            <p>* Campos obligatorios</p>
            
            <input type="buttom" value="Continuar" onclick="window.location.href='reg_hab.php'">

        </form>
        
        <a href="../registro_candidato.php" class="enlaces">Volver</a>
    </div>
</body>
</html>
