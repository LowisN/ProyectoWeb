<?php
session_start();
require_once '../../config/supabase.php';

// Verificar si hay un proceso de registro en curso y si hemos pasado por el paso 1
if (!isset($_SESSION['registro_candidato']) || $_SESSION['registro_candidato']['paso_actual'] < 2) {
    header('Location: ../../controllers/registro_candidato_unificado_controller.php');
    exit;
}

$datos_personales = $_SESSION['registro_candidato']['datos_personales'];
$datos_academicos = isset($_SESSION['registro_candidato']['datos_academicos']) ? $_SESSION['registro_candidato']['datos_academicos'] : [];
$datos_profesionales = isset($_SESSION['registro_candidato']['datos_profesionales']) ? $_SESSION['registro_candidato']['datos_profesionales'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Candidato - Datos Académicos y Profesionales - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Registro de Candidato: Paso 2</h1>
        <h2>Datos Académicos y Profesionales</h2>
        
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        
        <form id="datosForm" action="../../controllers/registro_candidato_unificado_controller.php" method="POST">
            <input type="hidden" name="paso" value="2">
            
            <h2>Datos Académicos</h2>
            
            <div class="form-group">
                <label for="institucion">Institución Académica</label>
                <input type="text" id="institucion" name="institucion" placeholder="IPN, UNAM, etc." value="<?php echo isset($datos_academicos['institucion']) ? htmlspecialchars($datos_academicos['institucion']) : ''; ?>">
            </div>
   
            <div class="form-group">
                <label for="titulo">Título Profesional*</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. Ingeniero en Redes" value="<?php echo isset($datos_academicos['titulo']) ? htmlspecialchars($datos_academicos['titulo']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="area">Área de expertis</label>
                <input type="text" id="area" name="area" placeholder="Tipo de área de su carrera" value="<?php echo isset($datos_academicos['area']) ? htmlspecialchars($datos_academicos['area']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="en_curso">Estudiando actualmente</label>
                <input type="checkbox" class="ckBoxL" id="en_curso" name="en_curso" <?php echo isset($datos_academicos['en_curso']) && $datos_academicos['en_curso'] ? 'checked' : ''; ?>>
            </div>
            
            <div id="seccionNoEnCurso" class="<?php echo isset($datos_academicos['en_curso']) && $datos_academicos['en_curso'] ? 'oculta' : 'visible'; ?>">
               <div class="form-group">
                   <label for="descripcion">Descripción de la carrera</label>
                   <input type="text" id="descripcion" name="descripcion" placeholder="Descripción de la carrera" value="<?php echo isset($datos_academicos['descripcion']) ? htmlspecialchars($datos_academicos['descripcion']) : ''; ?>">
                </div>
            </div>
            
            <div id="seccionEnCurso" class="<?php echo isset($datos_academicos['en_curso']) && $datos_academicos['en_curso'] ? 'visible' : 'oculta'; ?>">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo isset($datos_academicos['fecha_inicio']) ? htmlspecialchars($datos_academicos['fecha_inicio']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de Finalización</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo isset($datos_academicos['fecha_fin']) ? htmlspecialchars($datos_academicos['fecha_fin']) : ''; ?>">
                </div>
            </div>
            
            <h2>Datos Profesionales</h2>
            
            <div class="form-group">
                <label for="empresa">Empresa</label>
                <input type="text" id="empresa" name="empresa" placeholder="Ej. IBM, Google, etc." value="<?php echo isset($datos_profesionales['empresa']) ? htmlspecialchars($datos_profesionales['empresa']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="puesto">Puesto en el que laboró*</label>
                <input type="text" id="puesto" name="puesto" placeholder="Puesto en el que trabajó" value="<?php echo isset($datos_profesionales['puesto']) ? htmlspecialchars($datos_profesionales['puesto']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="actualL">Labora actualmente</label>
                <input type="checkbox" class="ckBoxL" id="actualL" name="actualL" <?php echo isset($datos_profesionales['actualL']) && $datos_profesionales['actualL'] ? 'checked' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label for="fecha_inicio_laboral">Fecha de Inicio en la empresa</label>
                <input type="date" id="fecha_inicio_laboral" name="fecha_inicio_laboral" value="<?php echo isset($datos_profesionales['fecha_inicio_laboral']) ? htmlspecialchars($datos_profesionales['fecha_inicio_laboral']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="fecha_fin_laboral">Fecha de Finalización en la empresa</label>
                <input type="date" id="fecha_fin_laboral" name="fecha_fin_laboral" value="<?php echo isset($datos_profesionales['fecha_fin_laboral']) ? htmlspecialchars($datos_profesionales['fecha_fin_laboral']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="anios_experiencia">Años de experiencia total*</label>
                <input type="number" id="anios_experiencia" name="anios_experiencia" min="0" value="<?php echo isset($datos_profesionales['anios_experiencia']) ? intval($datos_profesionales['anios_experiencia']) : 0; ?>" required>
            </div>
            
            <p>* Campos obligatorios</p>
            <button type="submit">Siguiente: Habilidades y Conocimientos</button>
        </form>
        
        <a href="../registro_candidato.php" class="enlaces">Volver al paso anterior</a>
    </div>

    <script>
        // Script para manejar el cambio entre mostrar campos según si está estudiando o no
        document.getElementById('en_curso').addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('seccionEnCurso').classList.remove('oculta');
                document.getElementById('seccionEnCurso').classList.add('visible');
                document.getElementById('seccionNoEnCurso').classList.add('oculta');
                document.getElementById('seccionNoEnCurso').classList.remove('visible');
            } else {
                document.getElementById('seccionNoEnCurso').classList.remove('oculta');
                document.getElementById('seccionNoEnCurso').classList.add('visible');
                document.getElementById('seccionEnCurso').classList.add('oculta');
                document.getElementById('seccionEnCurso').classList.remove('visible');
            }
        });

        // Lo mismo para el checkbox de si trabaja actualmente
        document.getElementById('actualL').addEventListener('change', function() {
            // Aquí puedes agregar lógica similar para mostrar/ocultar campos según si trabaja actualmente
        });
    </script>
    
    <style>
        .visible {
            display: block;
        }
        .oculta {
            display: none;
        }
        .ckBoxL {
            width: auto !important;
            margin-left: 10px;
        }
    </style>
</body>
</html>
    </script>

    <style>
        .visible {
            display: block;
        }
        .oculta {
            display: none;
        }
        .ckBoxL {
            width: auto !important;
            margin-left: 10px;
        }
    </style>
</body>
</html>
