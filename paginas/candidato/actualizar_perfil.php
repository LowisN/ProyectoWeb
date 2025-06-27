<?php

session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un candidato
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'candidato') {
    header('Location: ../interfaz_iniciar_sesion.php');
    exit;
}

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar el perfil');
    exit;
}

// Obtener datos del candidato
$candidatoData = supabaseFetch('candidatos', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($candidatoData) || isset($candidatoData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del candidato');
    exit;
}

$candidato = $candidatoData[0];

// Procesar el formulario si se ha enviado
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $experiencia = filter_input(INPUT_POST, 'experiencia', FILTER_VALIDATE_INT);
    $acerca_de = filter_input(INPUT_POST, 'acerca_de', FILTER_SANITIZE_STRING);
    
    // Validar los datos
    if (empty($telefono) || empty($direccion) || $experiencia === false) {
        $errorMessage = 'Todos los campos obligatorios deben ser completados correctamente';
    } else {
        // Preparar los datos para actualizar
        $updateData = [
            'telefono' => $telefono,
            'direccion' => $direccion,
            'titulo' => $titulo ?: null,
            'anios_experiencia' => $experiencia,
            'acerca_de' => $acerca_de ?: null
        ];
        
        // Actualizar los datos del candidato
        $updateResponse = supabaseUpdate('candidatos', $updateData, ['id' => $candidato['id']]);
        
        if (isset($updateResponse['error'])) {
            $errorMessage = 'Error al actualizar los datos';
        } else {
            $successMessage = 'Perfil actualizado correctamente';
            // Actualizar los datos en la variable $candidato
            $candidato = array_merge($candidato, $updateData);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Perfil - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/candidato_dashboard.css">
    </style>
</head>

<body id="masM">
    <div class="contenedor dashboard" id="canDash">
        <div class="sidebar">
            <div class="user-info">
                <img src="../../imagenes/logo.png" alt="Foto de perfil">
                <h3><?php echo htmlspecialchars($_SESSION['user']['user_metadata']['nombre'] . ' ' . $_SESSION['user']['user_metadata']['apellidos']); ?></h3>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_candidato.php">Inicio</a></li>
                <li><a href="#" class="active">Mi Perfil</a></li>
                <li><a href="actualizar_conocimientos.php">Mis Conocimientos</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content">
            <h2>Actualizar Perfil</h2>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <div class="profile-sections">
                <div class="section">
                    <h3>Datos Personales</h3>
                    
                    <form action="" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" id="nombre" value="<?php echo htmlspecialchars($_SESSION['user']['user_metadata']['nombre'] ?? ''); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellidos">Apellidos</label>
                                <input type="text" id="apellidos" value="<?php echo htmlspecialchars($_SESSION['user']['user_metadata']['apellidos'] ?? ''); ?>" disabled>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Teléfono*</label>
                                <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($candidato['telefono'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de nacimiento</label>
                                <input type="date" id="fecha_nacimiento" value="<?php echo htmlspecialchars($candidato['fecha_nacimiento'] ?? ''); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion">Dirección*</label>
                                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($candidato['direccion'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <h3>Datos Profesionales</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="titulo">Título profesional</label>
                                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($candidato['titulo'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="experiencia">Años de experiencia*</label>
                                <input type="number" id="experiencia" name="experiencia" min="0" value="<?php echo htmlspecialchars($candidato['anios_experiencia'] ?? '0'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="acerca_de">Acerca de mí</label>
                            <textarea id="acerca_de" name="acerca_de" placeholder="Escribe una breve descripción sobre ti, tu experiencia y habilidades..."><?php echo htmlspecialchars($candidato['acerca_de'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit">Guardar Cambios</button>
                    </form>
                </div>
                
                <div class="section">
                    <h3>Currículum Vitae</h3>
                    
                    <p>Sube tu CV actualizado. Formatos permitidos: PDF, DOC, DOCX (Máximo 5MB)</p>
                    
                    <form action="../../controllers/upload_cv_controller.php" method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cv_file">Archivo CV</label>
                                <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx">
                            </div>
                        </div>
                        
                        <?php if (!empty($candidato['cv_url'])): ?>
                            <p>CV actual: <a href="<?php echo htmlspecialchars($candidato['cv_url']); ?>" target="_blank">Ver CV</a></p>
                        <?php endif; ?>
                        
                        <button type="submit">Subir CV</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
