<?php
// Incluir cualquier configuración necesaria
// require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Elige tipo de registro - ChambaNet</title>
    <link rel="stylesheet" href="../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../estilo/formularios.css">
</head>

<body>
    <div class="contenedor">
        <div class="logo">
            <img src="../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Elige tipo de registro</h1>
        
        <div class="opciones-registro">
            <div class="opcion" onclick="location.href='registro_candidato.php';">
                <h3>Candidato</h3>
                <p>¿Buscas empleo en el área de redes de computadoras?</p>
                <button>Registrarse como Candidato</button>
            </div>
            
            <div class="opcion" onclick="location.href='registro_empresa.php';">
                <h3>Empresa</h3>
                <p>¿Buscas profesionales en redes de computadoras?</p>
                <button>Registrarse como Empresa</button>
            </div>
        </div>
        
        <a href="interfaz_iniciar_sesion.php" class="volver">Volver a Inicio de Sesión</a>
    </div>
</body>
</html>
