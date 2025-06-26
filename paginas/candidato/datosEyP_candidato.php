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
    <link rel="stylesheet" href="../../formatoEsp.css">
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
        
        <form action="../../controllers/registro_candidato_controller.php" method="POST" id="dCForm">
            <h2>Datos Personales</h2>
            
            <div class="form-group
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
                <label for="area">Area de expertis</label>
                <input type="number" id="experiencia" name="experiencia" min="0" placeholder="Tipo de area de su carrera">
            </div>
             <div class="form-group">
                <label for="estudiando">Estudiando*</label>
                <input type="checkbox" class ="ckBoxL" id="en_curso" name="en_curso" min="0" placeholder="Se encuentra estudiando actualmente" required>
            </div>
             <div id="seccionNoEnCurso" class="visible">
               <div class="form-group">
                     <label for="descripcion">Descripcion de la carrera</label>
                     <input type="text" id="descripcion" name="descripcion" placeholder="Descripcion de la carrrea">
                </div>
            </div>
            <div id="seccionEnCurso" class="oculta">
                <div class="form-group">
                    <label for="fechaIni">Fecha de Inicio*</label>
                    <input type="date" id="experiencia" name="experiencia" min="0" placeholder="Inicio de carrera profesional" required>
                </div>
                <div class="form-group">
                    <label for="fechaFin">Fecha de Finalizacion*</label>
                    <input type="date" id="experiencia" name="experiencia" min="0" placeholder="Termino de carrera profesional" required>
                </div>
            </div>
            
<!-- Tabla experiencia_laboral -->
            <h2>Datos profesionales</h2>
            
            <div class="form-group">
                <label for="titulo">Empresa </label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. IBM, Google, etc.">
            </div>
            
            <div class="form-group">
                <label for="puesto">Puesto en el que laboró*</label>
                <input type="text" id="puesto" name="puesto" min="0" placeholder="Puesto en el que trabajó" required>
            </div>
            <div class="form-group">
                <label for="trabajoA">Labora Actualmente*</label>
                <input type="checkbox" class="ckBoxL" id="actualL" name="actualL" min="0" placeholder="Se encuentra laborando actualmente" required>
            </div>
            <div class="form-group">
                    <label for="fechaIni">Fecha de Inicio en la empresa*</label>
                    <input type="date" id="fechaIni" name="fechaIni" min="0" placeholder="Comienzo a laborar" required>
                </div>
                <div class="form-group">
                    <label for="fechaFin">Fecha de Finalizacion en la empresa*</label>
                    <input type="date" id="fechaFin" name="FechaFin" min="0" placeholder="Termino de labores" required>
                </div>           
            <p>* Campos obligatorios</p>
             <script src="../../scripts/ocultar_contenidoF.js"></script>
            <button type="button" onclick="window.location.href='reg_hab.php'">Continuar</button>
       
        </form>
        
        <a href="elegir_registro.php" class="enlaces">Volver</a>
    </div>
</body>
</html>
