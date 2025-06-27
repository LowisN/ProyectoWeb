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

// Obtener todas las habilidades disponibles en la base de datos
$todasHabilidades = supabaseFetch('habilidades', '*');
if (isset($todasHabilidades['error'])) {
    $errorMessage = "Error al cargar las habilidades disponibles.";
    $todasHabilidades = [];
}

// Organizar las habilidades por categorías
$habilidadesPorCategoria = [];
foreach ($todasHabilidades as $habilidad) {
    if (!isset($habilidadesPorCategoria[$habilidad['categoria']])) {
        $habilidadesPorCategoria[$habilidad['categoria']] = [];
    }
    $habilidadesPorCategoria[$habilidad['categoria']][] = $habilidad;
}

// Obtener los conocimientos actuales del candidato
$habilidadesCandidato = supabaseFetch('candidato_habilidades', '*', ['candidato_id' => $candidato['id']]);
if (isset($habilidadesCandidato['error'])) {
    $errorMessage = "Error al cargar tus habilidades actuales.";
    $habilidadesCandidato = [];
}

// Crear un array asociativo para facilitar el acceso a los niveles actuales del candidato
$nivelesActuales = [];
foreach ($habilidadesCandidato as $habilidad) {
    $nivelesActuales[$habilidad['habilidad_id']] = [
        'nivel' => $habilidad['nivel'],
        'anios_experiencia' => $habilidad['anios_experiencia']
    ];
}

// Procesar el formulario si se ha enviado
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tecnologiasGuardadas = 0;
    $errores = 0;
    
    // Recorrer cada habilidad enviada en el formulario
    foreach ($_POST as $key => $value) {
        // Si es un campo de nivel (formato: nivel_ID)
        if (strpos($key, 'nivel_') === 0) {
            $habilidadId = substr($key, 6); // Extraer el ID de la habilidad
            $nivel = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
            
            // Validar nivel
            if (!in_array($nivel, ['principiante', 'intermedio', 'avanzado', 'experto'])) {
                continue;
            }
            
            // Obtener años de experiencia
            $aniosExperiencia = filter_input(INPUT_POST, 'anios_' . $habilidadId, FILTER_VALIDATE_INT);
            $aniosExperiencia = $aniosExperiencia !== false ? $aniosExperiencia : 0;
            
            // Preparar datos para insertar/actualizar
            $habilidadData = [
                'candidato_id' => $candidato['id'],
                'habilidad_id' => $habilidadId,
                'nivel' => $nivel,
                'anios_experiencia' => $aniosExperiencia
            ];
            
            // Verificar si ya existe esta habilidad para el candidato
            $habilidadExistente = isset($nivelesActuales[$habilidadId]);
            
            if ($habilidadExistente) {
                // Actualizar
                $updateResponse = supabaseUpdate('candidato_habilidades', [
                    'nivel' => $nivel,
                    'anios_experiencia' => $aniosExperiencia,
                    'ultima_actualizacion' => 'now()'
                ], [
                    'candidato_id' => $candidato['id'],
                    'habilidad_id' => $habilidadId
                ]);
                
                if (isset($updateResponse['error'])) {
                    $errores++;
                } else {
                    $tecnologiasGuardadas++;
                    $nivelesActuales[$habilidadId] = [
                        'nivel' => $nivel,
                        'anios_experiencia' => $aniosExperiencia
                    ];
                }
            } else {
                // Insertar
                $insertResponse = supabaseInsert('candidato_habilidades', $habilidadData);
                
                if (isset($insertResponse['error'])) {
                    $errores++;
                } else {
                    $tecnologiasGuardadas++;
                    $nivelesActuales[$habilidadId] = [
                        'nivel' => $nivel,
                        'anios_experiencia' => $aniosExperiencia
                    ];
                }
            }
        }
    }
    
    if ($errores > 0) {
        $errorMessage = "Se produjeron errores al guardar algunas habilidades.";
    }
    
    if ($tecnologiasGuardadas > 0) {
        $successMessage = "Habilidades actualizadas correctamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Conocimientos - ChambaNet</title>
   <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
</head>

<body >
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="user-info">
                <img src="../../imagenes/user-default.png" alt="Foto de perfil">
                <h3><?php echo htmlspecialchars($_SESSION['user']['user_metadata']['nombre'] . ' ' . $_SESSION['user']['user_metadata']['apellidos']); ?></h3>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_candidato.php">Inicio</a></li>
                <li><a href="actualizar_perfil.php">Mi Perfil</a></li>
                <li><a href="mis_postulaciones.php">Mis Postulaciones</a></li>
                <li><a href="#" class="active">Mis Conocimientos</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="section">
            <h2>Mis Conocimientos y Habilidades</h2>
            
            <p>Evalúa tus conocimientos y habilidades. Esto nos ayudará a encontrar las mejores vacantes para tu perfil.</p>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <?php foreach ($habilidadesPorCategoria as $categoria => $habilidades): ?>
                    <div class="section skills-category">
                        <h3><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h3>
                        
                        <div class="skills-grid">
                            <?php foreach ($habilidades as $habilidad): ?>
                                <div class="skill-item">
                                    <div class="skill-name">
                                        <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                        <?php if (!empty($habilidad['descripcion'])): ?>
                                            <span class="skill-tooltip" title="<?php echo htmlspecialchars($habilidad['descripcion']); ?>">ℹ️</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="skill-level">
                                        <label>
                                            <input type="radio" name="nivel_<?php echo $habilidad['id']; ?>" value="principiante" 
                                                <?php echo (isset($nivelesActuales[$habilidad['id']]) && $nivelesActuales[$habilidad['id']]['nivel'] === 'principiante') ? 'checked' : ''; ?>> 
                                            Principiante
                                        </label>
                                        <label>
                                            <input type="radio" name="nivel_<?php echo $habilidad['id']; ?>" value="intermedio" 
                                                <?php echo (isset($nivelesActuales[$habilidad['id']]) && $nivelesActuales[$habilidad['id']]['nivel'] === 'intermedio') ? 'checked' : ''; ?>> 
                                            Intermedio
                                        </label>
                                        <label>
                                            <input type="radio" name="nivel_<?php echo $habilidad['id']; ?>" value="avanzado" 
                                                <?php echo (isset($nivelesActuales[$habilidad['id']]) && $nivelesActuales[$habilidad['id']]['nivel'] === 'avanzado') ? 'checked' : ''; ?>> 
                                            Avanzado
                                        </label>
                                        <label>
                                            <input type="radio" name="nivel_<?php echo $habilidad['id']; ?>" value="experto" 
                                                <?php echo (isset($nivelesActuales[$habilidad['id']]) && $nivelesActuales[$habilidad['id']]['nivel'] === 'experto') ? 'checked' : ''; ?>> 
                                            Experto
                                        </label>
                                    </div>
                                    <div class="years-experience">
                                        <label for="anios_<?php echo $habilidad['id']; ?>">Años de experiencia:</label>
                                        <input type="number" name="anios_<?php echo $habilidad['id']; ?>" id="anios_<?php echo $habilidad['id']; ?>" 
                                            min="0" max="30" 
                                            value="<?php echo isset($nivelesActuales[$habilidad['id']]) ? $nivelesActuales[$habilidad['id']]['anios_experiencia'] : 0; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit">Guardar Conocimientos</button>
            </form>
            <div id="scroll-to-top">↑</div> 
        </div>
    </div>
    <script>
        // Script para el botón de scroll hacia arriba
        document.addEventListener('DOMContentLoaded', function() {
            var scrollBtn = document.getElementById('scroll-to-top');
            
            // Mostrar/ocultar el botón basado en la posición del scroll
            window.addEventListener('scroll', function() {
                if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                    scrollBtn.style.display = 'block';
                } else {
                    scrollBtn.style.display = 'none';
                }
            });
            
            // Scroll hacia arriba al hacer clic en el botón
            scrollBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Tooltip para descripciones de habilidades
            var tooltips = document.querySelectorAll('.skill-tooltip');
            tooltips.forEach(function(tooltip) {
                tooltip.addEventListener('mouseenter', function() {
                    var title = this.getAttribute('title');
                    this.setAttribute('data-title', title);
                    this.removeAttribute('title');
                    
                    var tooltipDiv = document.createElement('div');
                    tooltipDiv.className = 'tooltip-box';
                    tooltipDiv.innerHTML = title;
                    document.body.appendChild(tooltipDiv);
                    
                    var rect = this.getBoundingClientRect();
                    tooltipDiv.style.left = rect.left + window.scrollX + 'px';
                    tooltipDiv.style.top = rect.bottom + window.scrollY + 5 + 'px';
                });
                
                tooltip.addEventListener('mouseleave', function() {
                    var title = this.getAttribute('data-title');
                    this.setAttribute('title', title);
                    this.removeAttribute('data-title');
                    
                    var tooltipDiv = document.querySelector('.tooltip-box');
                    if (tooltipDiv) {
                        tooltipDiv.parentNode.removeChild(tooltipDiv);
                    }
                });
            });
        });
    </script>
</body>
</html>
