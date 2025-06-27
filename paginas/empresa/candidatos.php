<?php
session_start();
require_once '../../config/supabase.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
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

// Obtener datos del reclutador
$reclutadorData = supabaseFetch('reclutadores', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($reclutadorData) || isset($reclutadorData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos del reclutador');
    exit;
}

// Obtener datos de la empresa
$empresaData = supabaseFetch('empresas', '*', ['id' => $reclutadorData[0]['empresa_id']]);

if (empty($empresaData) || isset($empresaData['error'])) {
    header('Location: ../interfaz_iniciar_sesion.php?error=Error al cargar datos de la empresa');
    exit;
}

// Función de utilidad para obtener un valor de un array de forma segura
function getValue($array, $key, $default = '') {
    if (!is_array($array)) {
        error_log("getValue: el primer parámetro no es un array, es " . gettype($array));
        return $default;
    }
    return isset($array[$key]) ? $array[$key] : $default;
}

// Variables para filtros y selección
$selectedVacante = isset($_GET['vacante_id']) ? (int)$_GET['vacante_id'] : 0;
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

// Obtener todas las vacantes activas de la empresa
$vacantes = supabaseFetch('vacantes', '*', [
    'empresa_id' => $empresaData[0]['id'], 
    'estado' => 'activa'
]);

// Si no hay vacantes, inicializar como array vacío para evitar errores
if (isset($vacantes['error']) || !is_array($vacantes)) {
    $vacantes = [];
}

// Si se seleccionó una vacante específica, obtener información detallada
$vacanteSeleccionada = null;
$habilidadesRequeridas = [];

if ($selectedVacante > 0) {
    foreach ($vacantes as $vacante) {
        if ((int)$vacante['id'] === $selectedVacante) {
            $vacanteSeleccionada = $vacante;
            break;
        }
    }
    
    if ($vacanteSeleccionada) {
        // Obtener habilidades requeridas para la vacante seleccionada
        $query = "/rest/v1/vacante_habilidades?select=id,habilidad_id,nivel_requerido,obligatorio&vacante_id=eq.$selectedVacante";
        $habilidadesRequeridas = supabaseRequest($query);
        
        if (isset($habilidadesRequeridas['error']) || !is_array($habilidadesRequeridas)) {
            $habilidadesRequeridas = [];
        }
    }
}

// Función para calcular el porcentaje de match entre un candidato y una vacante
function calcularMatchPorcentaje($candidatoId, $habilidadesVacante) {
    if (empty($habilidadesVacante)) {
        return 0;
    }
    
    // Obtener habilidades del candidato
    $habilidadesCandidato = supabaseFetch('candidato_habilidades', '*', ['candidato_id' => $candidatoId]);
    
    if (isset($habilidadesCandidato['error']) || !is_array($habilidadesCandidato)) {
        return 0;
    }
    
    // Si no hay habilidades requeridas o el candidato no tiene habilidades
    if (empty($habilidadesVacante) || empty($habilidadesCandidato)) {
        return 0;
    }
    
    // Contador de coincidencias
    $coincidenciasObligatorias = 0;
    $totalObligatorios = 0;
    $coincidenciasOpcionales = 0;
    $totalOpcionales = 0;
    
    // Mapa de nivel a valor numérico para comparaciones
    $nivelValor = [
        'principiante' => 1,
        'intermedio' => 2,
        'avanzado' => 3,
        'experto' => 4
    ];
    
    // Crear un mapa de habilidades del candidato para búsqueda rápida
    $mapaCandidato = [];
    foreach ($habilidadesCandidato as $habilidad) {
        $mapaCandidato[$habilidad['habilidad_id']] = [
            'nivel' => $habilidad['nivel'],
            'anios_experiencia' => $habilidad['anios_experiencia']
        ];
    }
    
    // Evaluar cada habilidad requerida
    foreach ($habilidadesVacante as $habilidadRequerida) {
        $esObligatorio = (bool)getValue($habilidadRequerida, 'obligatorio', true);
        $habilidadId = (int)getValue($habilidadRequerida, 'habilidad_id', 0);
        $nivelRequerido = getValue($habilidadRequerida, 'nivel_requerido', 'principiante');
        
        // Incrementar contadores de totales
        if ($esObligatorio) {
            $totalObligatorios++;
        } else {
            $totalOpcionales++;
        }
        
        // Verificar si el candidato tiene la habilidad
        if (isset($mapaCandidato[$habilidadId])) {
            $nivelCandidato = $mapaCandidato[$habilidadId]['nivel'];
            
            // Verificar si el nivel del candidato cumple con el requerido
            if ($nivelValor[$nivelCandidato] >= $nivelValor[$nivelRequerido]) {
                if ($esObligatorio) {
                    $coincidenciasObligatorias++;
                } else {
                    $coincidenciasOpcionales++;
                }
            }
        }
    }
    
    // Calcular porcentaje final de match
    $porcentajeObligatorios = $totalObligatorios > 0 ? ($coincidenciasObligatorias / $totalObligatorios) * 70 : 0;
    $porcentajeOpcionales = $totalOpcionales > 0 ? ($coincidenciasOpcionales / $totalOpcionales) * 30 : 0;
    
    // Si no hay opcionales, el 100% depende de los obligatorios
    if ($totalOpcionales == 0) {
        $porcentajeObligatorios = $totalObligatorios > 0 ? ($coincidenciasObligatorias / $totalObligatorios) * 100 : 0;
    }
    
    return round($porcentajeObligatorios + $porcentajeOpcionales);
}

// Obtener todos los candidatos o filtrar por match con la vacante seleccionada
$candidatos = [];

// Consulta a la tabla de candidatos
$query = "/rest/v1/candidatos?select=id,perfil_id,telefono,fecha_nacimiento,titulo,anios_experiencia,resumen_profesional,disponibilidad_viaje,disponibilidad_mudanza,modalidad_preferida,cv_url,foto_url,perfiles(user_id,tipo_usuario)";
$candidatosData = supabaseRequest($query);

if (is_array($candidatosData) && !isset($candidatosData['error'])) {
    // Para cada candidato, obtener información adicional
    foreach ($candidatosData as $candidato) {
        $perfilId = getValue($candidato, 'perfil_id', 0);
        
        // Obtener información de usuario de Auth
        $userData = supabaseFetch('perfiles', '*', ['id' => $perfilId]);
        
        if (!empty($userData) && is_array($userData)) {
            // Calcular porcentaje de match si hay una vacante seleccionada
            $matchPorcentaje = $selectedVacante > 0 ? 
                calcularMatchPorcentaje($candidato['id'], $habilidadesRequeridas) : 0;
            
            // Obtener nombre del candidato usando el perfil
            $nombreCompleto = "Candidato #" . $candidato['id'];
            
            // Intentar obtener el nombre real del usuario
            $userId = getValue($userData[0], 'user_id', '');
            if ($userId) {
                $userAuthData = supabaseRequest("/auth/v1/user/" . $userId);
                if (isset($userAuthData['user_metadata']) && isset($userAuthData['user_metadata']['nombre'])) {
                    $nombreCompleto = $userAuthData['user_metadata']['nombre'] . ' ' . 
                        getValue($userAuthData['user_metadata'], 'apellidos', '');
                } else if (isset($userAuthData['email'])) {
                    $nombreCompleto = $userAuthData['email'];
                }
            }
            
            // Añadir al array de candidatos con la información completa
            $candidatos[] = [
                'id' => $candidato['id'],
                'nombre' => $nombreCompleto,
                'titulo' => getValue($candidato, 'titulo', 'Profesional'),
                'match_porcentaje' => $matchPorcentaje,
                'anios_experiencia' => getValue($candidato, 'anios_experiencia', 0),
                'resumen' => getValue($candidato, 'resumen_profesional', ''),
                'foto_url' => getValue($candidato, 'foto_url', ''),
                'cv_url' => getValue($candidato, 'cv_url', '')
            ];
        }
    }
    
    // Si hay una vacante seleccionada, ordenar por porcentaje de match
    if ($selectedVacante > 0) {
        // Ordenar candidatos por porcentaje de match (de mayor a menor)
        usort($candidatos, function($a, $b) {
            return $b['match_porcentaje'] - $a['match_porcentaje'];
        });
    }
}

// Contar totales para las pestañas
$totalCandidatos = count($candidatos);
$totalPendientes = 0;
$totalRevisados = 0;

// Aquí podríamos implementar la lógica para contar pendientes/revisados
// basándonos en datos reales de la BD
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatos</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
    <link rel="stylesheet" href="../../estilo/candidatos.css">
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
       <div class="company-info">
                <img src="../../imagenes/logo.png" alt="Logo de la empresa">
                <h3><?php echo isset($empresaData[0]['nombre']) ? htmlspecialchars($empresaData[0]['nombre']) : 'Empresa'; ?></h3>
                <p><?php 
                    echo htmlspecialchars($reclutadorData[0]['nombre'] . ' ' . $reclutadorData[0]['apellidos']);
                ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_empresa.php">Inicio</a></li>
                <li><a href="nueva_vacante.php" ">Publicar Vacante</a></li>
                <li><a href="mis_vacantes.php">Mis Vacantes</a></li>
                <li><a href="#"class="active">Candidatos</a></li>
                <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
    </div>
    <div class="main-content">
        <!-- Candidates Section -->
        <div class="candidates-section">
            <div class="candidates-filters">
                <!-- Selector de vacantes -->
                <div class="vacante-selector">
                    <h3>Seleccionar Vacante</h3>
                    <form method="GET" action="candidatos.php">
                        <select name="vacante_id" class="form-control" onchange="this.form.submit()">
                            <option value="0">Todas las vacantes</option>
                            <?php foreach ($vacantes as $vacante): ?>
                                <option value="<?php echo $vacante['id']; ?>" <?php echo $selectedVacante == $vacante['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(getValue($vacante, 'titulo', 'Vacante sin título')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- Información de la vacante seleccionada -->
                <?php if ($vacanteSeleccionada): ?>
                <div class="vacante-info">
                    <h4><?php echo htmlspecialchars(getValue($vacanteSeleccionada, 'titulo', 'Vacante')); ?></h4>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars(getValue($vacanteSeleccionada, 'ubicacion', '')); ?></p>
                    <p><strong>Modalidad:</strong> <?php echo htmlspecialchars(getValue($vacanteSeleccionada, 'modalidad', '')); ?></p>
                    <p><strong>Experiencia requerida:</strong> <?php echo htmlspecialchars(getValue($vacanteSeleccionada, 'anios_experiencia', '0')); ?> años</p>
                </div>
                <?php endif; ?>

                <!-- Filtros de estado -->
                <div class="filter-chips">
                    <a href="?estado=todos<?php echo $selectedVacante ? '&vacante_id='.$selectedVacante : ''; ?>" 
                       class="filter-chip <?php echo $filtroEstado == 'todos' ? 'active' : ''; ?>">
                        Todos (<?php echo $totalCandidatos; ?>)
                    </a>
                    <a href="?estado=pendientes<?php echo $selectedVacante ? '&vacante_id='.$selectedVacante : ''; ?>" 
                       class="filter-chip <?php echo $filtroEstado == 'pendientes' ? 'active' : ''; ?>">
                        Pendientes (<?php echo $totalPendientes; ?>)
                    </a>
                    <a href="?estado=revisados<?php echo $selectedVacante ? '&vacante_id='.$selectedVacante : ''; ?>" 
                       class="filter-chip <?php echo $filtroEstado == 'revisados' ? 'active' : ''; ?>">
                        Revisados (<?php echo $totalRevisados; ?>)
                    </a>
                </div>
            </div>

            <?php if (empty($candidatos)): ?>
            <div class="empty-state">
                <h3>No se encontraron candidatos</h3>
                <?php if ($selectedVacante > 0): ?>
                    <p>No hay candidatos que coincidan con los requisitos de esta vacante.</p>
                <?php else: ?>
                    <p>No hay candidatos registrados en el sistema.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="candidates-list">
                <?php foreach ($candidatos as $candidato): ?>
                <!-- Candidato <?php echo $candidato['id']; ?> - <?php echo htmlspecialchars($candidato['nombre']); ?> -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <?php if (!empty($candidato['foto_url'])): ?>
                            <img src="<?php echo htmlspecialchars($candidato['foto_url']); ?>" alt="Foto de <?php echo htmlspecialchars($candidato['nombre']); ?>" class="candidate-avatar">
                        <?php else: ?>
                            <div class="candidate-avatar">
                                <?php 
                                    $iniciales = '';
                                    $nombre = explode(' ', $candidato['nombre']);
                                    if (!empty($nombre[0])) $iniciales .= substr($nombre[0], 0, 1);
                                    if (!empty($nombre[1])) $iniciales .= substr($nombre[1], 0, 1);
                                    echo $iniciales;
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="candidate-details">
                            <h4><?php echo htmlspecialchars($candidato['nombre']); ?></h4>
                            <span class="candidate-title"><?php echo htmlspecialchars($candidato['titulo']); ?></span>
                            
                            <?php if ($selectedVacante > 0): ?>
                            <div class="match-score <?php echo $candidato['match_porcentaje'] >= 80 ? 'high-match' : ($candidato['match_porcentaje'] >= 50 ? 'medium-match' : 'low-match'); ?>">
                                Match: <?php echo $candidato['match_porcentaje']; ?>%
                            </div>
                            <?php endif; ?>
                            
                            <div class="candidate-summary">
                                <p><strong>Experiencia:</strong> <?php echo $candidato['anios_experiencia']; ?> años</p>
                                <?php if (!empty($candidato['resumen'])): ?>
                                <p class="summary-text"><?php echo substr(htmlspecialchars($candidato['resumen']), 0, 100); ?>...</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="candidate-actions">
                        <button class="btn btn-outline ver-perfil" data-id="<?php echo $candidato['id']; ?>" data-nombre="<?php echo htmlspecialchars($candidato['nombre']); ?>" data-titulo="<?php echo htmlspecialchars($candidato['titulo']); ?>" data-experiencia="<?php echo $candidato['anios_experiencia']; ?>" data-resumen="<?php echo htmlspecialchars($candidato['resumen']); ?>" data-foto="<?php echo htmlspecialchars($candidato['foto_url']); ?>" <?php echo $selectedVacante > 0 ? 'data-match="' . $candidato['match_porcentaje'] . '"' : ''; ?>>Ver perfil</button>
                        <button class="btn btn-primary contactar" data-id="<?php echo $candidato['id']; ?>" data-nombre="<?php echo htmlspecialchars($candidato['nombre']); ?>">Contactar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?> 
        </div>
        
        <!-- Paginación (si hay muchos candidatos) -->
        <?php if (count($candidatos) > 10): ?>
        <div class="pagination">
            <button type="button" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Anterior</button>
            <div class="page-numbers">
                <span class="current">1</span>
                <span>2</span>
                <span>3</span>
            </div>
            <button type="button" class="btn btn-outline">Siguiente <i class="fas fa-chevron-right"></i></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal para ver perfil completo -->
    <div id="perfilModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2 id="modalNombreCandidato"></h2>
                <span id="modalTituloCandidato"></span>
            </div>
            <div class="modal-body">
                <div class="profile-container">
                    <div class="profile-header">
                        <div id="modalAvatarContainer">
                            <div id="modalAvatar" class="candidate-avatar-large"></div>
                        </div>
                        <div class="profile-basic-info">
                            <div id="modalMatchContainer" class="match-container">
                                <div id="modalMatchScore" class="match-score-large"></div>
                            </div>
                            <p><strong>Experiencia:</strong> <span id="modalExperiencia"></span> años</p>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <h3>Resumen Profesional</h3>
                        <p id="modalResumen"></p>
                        
                        <h3>Habilidades</h3>
                        <div id="modalHabilidades" class="skills-container">
                            <p class="loading-text">Cargando habilidades...</p>
                        </div>
                        
                        <h3>Experiencia Laboral</h3>
                        <div id="modalExperienciaLaboral" class="experience-container">
                            <p class="loading-text">Cargando experiencia laboral...</p>
                        </div>
                        
                        <h3>Educación</h3>
                        <div id="modalEducacion" class="education-container">
                            <p class="loading-text">Cargando educación...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnContactarModal" class="btn btn-primary">Contactar a este candidato</button>
                <?php if ($selectedVacante > 0): ?>
                <button id="btnVerMatchDetalles" class="btn btn-outline">Ver detalles de compatibilidad</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para contactar al candidato -->
    <div id="contactarModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2>Contactar a <span id="contactarNombreCandidato"></span></h2>
            </div>
            <div class="modal-body">
                <div id="contactarLoading" class="loading-container">
                    <p class="loading-text">Cargando información de contacto...</p>
                </div>
                <div id="contactarInfo" class="contact-info-container" style="display: none;">
                    <div class="contact-item">
                        <span class="contact-label">Teléfono:</span>
                        <span id="contactarTelefono" class="contact-value"></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Email:</span>
                        <span id="contactarEmail" class="contact-value"></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Modalidad preferida:</span>
                        <span id="contactarModalidad" class="contact-value"></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Disponibilidad para viajar:</span>
                        <span id="contactarDisponibilidadViaje" class="contact-value"></span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-label">Disponibilidad para mudarse:</span>
                        <span id="contactarDisponibilidadMudanza" class="contact-value"></span>
                    </div>
                </div>
                <div class="contact-note">
                    <p>Nota: La plataforma no gestiona la comunicación entre reclutadores y candidatos. Por favor utilice estos datos para contactar directamente al candidato a través de los medios tradicionales.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para detalles de match -->
    <div id="matchDetallesModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header">
                <h2>Detalles de compatibilidad</h2>
                <p>Candidato: <span id="matchNombreCandidato"></span> - Vacante: <span id="matchTituloVacante"></span></p>
            </div>
            <div class="modal-body">
                <div id="matchDetallesLoading" class="loading-container">
                    <p class="loading-text">Cargando detalles de compatibilidad...</p>
                </div>
                <div id="matchDetallesInfo" class="match-details-container" style="display: none;">
                    <div class="match-percentage-container">
                        <div id="matchPorcentajeGeneral" class="match-score-large"></div>
                    </div>
                    
                    <div class="match-summary">
                        <h3>Resumen</h3>
                        <div class="match-summary-item">
                            <span>Habilidades obligatorias:</span>
                            <span id="matchObligatoriasResumen"></span>
                        </div>
                        <div class="match-summary-item">
                            <span>Habilidades opcionales:</span>
                            <span id="matchOpcionalesResumen"></span>
                        </div>
                    </div>
                    
                    <div class="match-details">
                        <h3>Detalles por habilidad</h3>
                        <div id="matchDetallesHabilidades" class="match-details-table">
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>

    

    <script>
        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            // Referencias a modales
            const perfilModal = document.getElementById('perfilModal');
            const contactarModal = document.getElementById('contactarModal');
            const matchDetallesModal = document.getElementById('matchDetallesModal');
            
            // Referencias a botones de cierre de modales
            const closeButtons = document.querySelectorAll('.close');
            
            // Cerrar modal al hacer clic en el botón de cierre o fuera del modal
            closeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    perfilModal.style.display = 'none';
                    contactarModal.style.display = 'none';
                    matchDetallesModal.style.display = 'none';
                });
            });
            
            window.addEventListener('click', function(e) {
                if (e.target === perfilModal) perfilModal.style.display = 'none';
                if (e.target === contactarModal) contactarModal.style.display = 'none';
                if (e.target === matchDetallesModal) matchDetallesModal.style.display = 'none';
            });
            
            // Manejar acciones de candidatos - Ver Perfil
            document.querySelectorAll('.ver-perfil').forEach(btn => {
                btn.addEventListener('click', function() {
                    const candidatoId = this.getAttribute('data-id');
                    const candidatoNombre = this.getAttribute('data-nombre');
                    const candidatoTitulo = this.getAttribute('data-titulo');
                    const candidatoExperiencia = this.getAttribute('data-experiencia');
                    const candidatoResumen = this.getAttribute('data-resumen');
                    const candidatoFoto = this.getAttribute('data-foto');
                    const candidatoMatch = this.hasAttribute('data-match') ? this.getAttribute('data-match') : null;
                    
                    // Llenar el modal con la información básica
                    document.getElementById('modalNombreCandidato').textContent = candidatoNombre;
                    document.getElementById('modalTituloCandidato').textContent = candidatoTitulo;
                    document.getElementById('modalExperiencia').textContent = candidatoExperiencia;
                    document.getElementById('modalResumen').textContent = candidatoResumen || 'No hay resumen profesional disponible';
                    
                    // Configurar avatar
                    const avatarContainer = document.getElementById('modalAvatar');
                    if (candidatoFoto && candidatoFoto !== 'null') {
                        avatarContainer.innerHTML = `<img src="${candidatoFoto}" alt="Foto de ${candidatoNombre}">`;
                        avatarContainer.classList.remove('candidate-avatar-large');
                    } else {
                        const iniciales = getIniciales(candidatoNombre);
                        avatarContainer.textContent = iniciales;
                        avatarContainer.classList.add('candidate-avatar-large');
                    }
                    
                    // Mostrar porcentaje de match si está disponible
                    const matchContainer = document.getElementById('modalMatchContainer');
                    const matchScore = document.getElementById('modalMatchScore');
                    
                    if (candidatoMatch !== null) {
                        matchContainer.style.display = 'block';
                        matchScore.textContent = `Match: ${candidatoMatch}%`;
                        
                        if (candidatoMatch >= 80) {
                            matchScore.className = 'match-score-large high-match';
                        } else if (candidatoMatch >= 50) {
                            matchScore.className = 'match-score-large medium-match';
                        } else {
                            matchScore.className = 'match-score-large low-match';
                        }
                    } else {
                        matchContainer.style.display = 'none';
                    }
                    
                    // Cargar datos adicionales del perfil (habilidades, experiencia, educación)
                    cargarHabilidadesCandidato(candidatoId);
                    cargarExperienciaLaboral(candidatoId);
                    cargarEducacion(candidatoId);
                    
                    // Configurar botón para contactar desde el modal
                    document.getElementById('btnContactarModal').setAttribute('data-id', candidatoId);
                    document.getElementById('btnContactarModal').setAttribute('data-nombre', candidatoNombre);
                    
                    // Configurar botón para ver detalles de match si aplica
                    const btnVerMatch = document.getElementById('btnVerMatchDetalles');
                    if (btnVerMatch) {
                        btnVerMatch.setAttribute('data-id', candidatoId);
                        btnVerMatch.setAttribute('data-nombre', candidatoNombre);
                    }
                    
                    // Mostrar el modal
                    perfilModal.style.display = 'block';
                });
            });
            
            // Manejar acciones de candidatos - Contactar
            function contactarCandidato(candidatoId, candidatoNombre) {
                // Llenar el modal con la información básica
                document.getElementById('contactarNombreCandidato').textContent = candidatoNombre;
                
                // Mostrar cargando y ocultar información
                document.getElementById('contactarLoading').style.display = 'block';
                document.getElementById('contactarInfo').style.display = 'none';
                
                // Cargar datos de contacto del candidato
                fetch(`../../ajax/get_candidato_contacto.php?id=${candidatoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert('Error al cargar información de contacto: ' + data.error);
                            return;
                        }
                        
                        // Llenar campos de contacto
                        document.getElementById('contactarTelefono').textContent = data.telefono || 'No disponible';
                        document.getElementById('contactarEmail').textContent = data.email || 'No disponible';
                        document.getElementById('contactarModalidad').textContent = data.modalidad_preferida || 'No especificada';
                        document.getElementById('contactarDisponibilidadViaje').textContent = data.disponibilidad_viaje ? 'Sí' : 'No';
                        document.getElementById('contactarDisponibilidadMudanza').textContent = data.disponibilidad_mudanza ? 'Sí' : 'No';
                        
                        // Ocultar cargando y mostrar información
                        document.getElementById('contactarLoading').style.display = 'none';
                        document.getElementById('contactarInfo').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al cargar información de contacto. Por favor intente de nuevo.');
                        document.getElementById('contactarLoading').style.display = 'none';
                    });
                
                // Mostrar el modal
                contactarModal.style.display = 'block';
            }
            
            document.querySelectorAll('.contactar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const candidatoId = this.getAttribute('data-id');
                    const candidatoNombre = this.getAttribute('data-nombre');
                    
                    contactarCandidato(candidatoId, candidatoNombre);
                });
            });
            
            // Botón para contactar desde el modal de perfil
            document.getElementById('btnContactarModal').addEventListener('click', function() {
                const candidatoId = this.getAttribute('data-id');
                const candidatoNombre = this.getAttribute('data-nombre');
                
                // Ocultar modal de perfil y mostrar modal de contacto
                perfilModal.style.display = 'none';
                contactarCandidato(candidatoId, candidatoNombre);
            });
            
            // Botón para ver detalles de match
            const btnVerMatch = document.getElementById('btnVerMatchDetalles');
            if (btnVerMatch) {
                btnVerMatch.addEventListener('click', function() {
                    const candidatoId = this.getAttribute('data-id');
                    const candidatoNombre = this.getAttribute('data-nombre');
                    const vacanteId = <?php echo $selectedVacante ?: 0; ?>;
                    const vacanteTitulo = "<?php echo $vacanteSeleccionada ? htmlspecialchars($vacanteSeleccionada['titulo']) : ''; ?>";
                    
                    // Llenar el modal con la información básica
                    document.getElementById('matchNombreCandidato').textContent = candidatoNombre;
                    document.getElementById('matchTituloVacante').textContent = vacanteTitulo;
                    
                    // Mostrar cargando y ocultar información
                    document.getElementById('matchDetallesLoading').style.display = 'block';
                    document.getElementById('matchDetallesInfo').style.display = 'none';
                    
                    // Cargar detalles del match
                    fetch(`../../diagnostico_match_candidatos.php?candidato_id=${candidatoId}&vacante_id=${vacanteId}&ajax=1`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert('Error al cargar detalles de compatibilidad: ' + data.error);
                                return;
                            }
                            
                            // Mostrar porcentaje general
                            const porcentaje = data.match.porcentaje;
                            const porcentajeElement = document.getElementById('matchPorcentajeGeneral');
                            porcentajeElement.textContent = `${porcentaje}% de Match`;
                            
                            if (porcentaje >= 80) {
                                porcentajeElement.className = 'match-score-large high-match';
                            } else if (porcentaje >= 50) {
                                porcentajeElement.className = 'match-score-large medium-match';
                            } else {
                                porcentajeElement.className = 'match-score-large low-match';
                            }
                            
                            // Mostrar resumen de obligatorias y opcionales
                            document.getElementById('matchObligatoriasResumen').textContent = 
                                `${data.match.obligatorios_match} de ${data.match.obligatorios_total} (${data.match.porcentaje_obligatorios}%)`;
                            document.getElementById('matchOpcionalesResumen').textContent = 
                                `${data.match.opcionales_match} de ${data.match.opcionales_total} (${data.match.porcentaje_opcionales}%)`;
                            
                            // Generar tabla de detalles
                            const detallesContainer = document.getElementById('matchDetallesHabilidades');
                            detallesContainer.innerHTML = '';
                            
                            if (data.match.coincidencias_detalle && data.match.coincidencias_detalle.length > 0) {
                                const table = document.createElement('table');
                                table.innerHTML = `
                                    <thead>
                                        <tr>
                                            <th>Habilidad</th>
                                            <th>Obligatoria</th>
                                            <th>Estado</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                `;
                                
                                const tbody = table.querySelector('tbody');
                                
                                data.match.coincidencias_detalle.forEach(habilidad => {
                                    const tr = document.createElement('tr');
                                    const estadoClass = habilidad.nivel_coincide ? 'success' : 
                                                       (habilidad.tiene_habilidad ? 'warning' : 'danger');
                                    
                                    tr.innerHTML = `
                                        <td>${habilidad.nombre}</td>
                                        <td>${habilidad.obligatorio ? 'Sí' : 'No'}</td>
                                        <td class="${estadoClass}">${
                                            habilidad.nivel_coincide ? 'Cumple' : 
                                            (habilidad.tiene_habilidad ? 'Nivel insuficiente' : 'No tiene')
                                        }</td>
                                        <td>${habilidad.detalle}</td>
                                    `;
                                    
                                    tbody.appendChild(tr);
                                });
                                
                                detallesContainer.appendChild(table);
                            } else {
                                detallesContainer.innerHTML = '<p>No hay detalles disponibles sobre las habilidades.</p>';
                            }
                            
                            // Ocultar cargando y mostrar información
                            document.getElementById('matchDetallesLoading').style.display = 'none';
                            document.getElementById('matchDetallesInfo').style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al cargar detalles de compatibilidad. Por favor intente de nuevo.');
                            document.getElementById('matchDetallesLoading').style.display = 'none';
                        });
                    
                    // Ocultar modal de perfil y mostrar modal de detalles de match
                    perfilModal.style.display = 'none';
                    matchDetallesModal.style.display = 'block';
                });
            }
            
            // Mostrar/ocultar resúmenes de candidatos
            document.querySelectorAll('.candidate-details h4').forEach(header => {
                header.addEventListener('click', function() {
                    const summary = this.closest('.candidate-details').querySelector('.candidate-summary');
                    if (summary) {
                        summary.classList.toggle('expanded');
                    }
                });
            });
            
            // Funciones auxiliares
            function getIniciales(nombre) {
                let iniciales = '';
                const partes = nombre.split(' ');
                if (partes.length > 0 && partes[0]) iniciales += partes[0].charAt(0);
                if (partes.length > 1 && partes[1]) iniciales += partes[1].charAt(0);
                return iniciales.toUpperCase();
            }
            
            function cargarHabilidadesCandidato(candidatoId) {
                const container = document.getElementById('modalHabilidades');
                container.innerHTML = '<p class="loading-text">Cargando habilidades...</p>';
                
                fetch(`../../ajax/get_candidato_habilidades.php?id=${candidatoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<p class="error-text">Error al cargar habilidades: ' + data.error + '</p>';
                            return;
                        }
                        
                        if (!data.habilidades || data.habilidades.length === 0) {
                            container.innerHTML = '<p>El candidato no tiene habilidades registradas.</p>';
                            return;
                        }
                        
                        const habilidadesHTML = data.habilidades.map(habilidad => `
                            <div class="skill-item">
                                <div class="skill-header">
                                    <span class="skill-name">${habilidad.nombre}</span>
                                    <span class="skill-level ${habilidad.nivel}">${habilidad.nivel}</span>
                                </div>
                                <div class="skill-detail">
                                    <span class="skill-experience">${habilidad.anios_experiencia} años de experiencia</span>
                                    ${habilidad.certificado_url ? `<a href="${habilidad.certificado_url}" target="_blank" class="skill-certificate">Ver certificado</a>` : ''}
                                </div>
                            </div>
                        `).join('');
                        
                        container.innerHTML = habilidadesHTML;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        container.innerHTML = '<p class="error-text">Error al cargar habilidades. Por favor intente de nuevo.</p>';
                    });
            }
            
            function cargarExperienciaLaboral(candidatoId) {
                const container = document.getElementById('modalExperienciaLaboral');
                container.innerHTML = '<p class="loading-text">Cargando experiencia laboral...</p>';
                
                fetch(`../../ajax/get_candidato_experiencia.php?id=${candidatoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<p class="error-text">Error al cargar experiencia laboral: ' + data.error + '</p>';
                            return;
                        }
                        
                        if (!data.experiencia || data.experiencia.length === 0) {
                            container.innerHTML = '<p>El candidato no tiene experiencia laboral registrada.</p>';
                            return;
                        }
                        
                        const experienciaHTML = data.experiencia.map(exp => `
                            <div class="experience-item">
                                <div class="experience-header">
                                    <h4>${exp.puesto}</h4>
                                    <span class="experience-company">${exp.empresa}</span>
                                </div>
                                <div class="experience-period">
                                    ${formatFecha(exp.fecha_inicio)} - ${exp.actual ? 'Actualidad' : formatFecha(exp.fecha_fin)}
                                </div>
                                ${exp.descripcion ? `<p class="experience-description">${exp.descripcion}</p>` : ''}
                            </div>
                        `).join('');
                        
                        container.innerHTML = experienciaHTML;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        container.innerHTML = '<p class="error-text">Error al cargar experiencia laboral. Por favor intente de nuevo.</p>';
                    });
            }
            
            function cargarEducacion(candidatoId) {
                const container = document.getElementById('modalEducacion');
                container.innerHTML = '<p class="loading-text">Cargando educación...</p>';
                
                fetch(`../../ajax/get_candidato_educacion.php?id=${candidatoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<p class="error-text">Error al cargar educación: ' + data.error + '</p>';
                            return;
                        }
                        
                        if (!data.educacion || data.educacion.length === 0) {
                            container.innerHTML = '<p>El candidato no tiene educación registrada.</p>';
                            return;
                        }
                        
                        const educacionHTML = data.educacion.map(edu => `
                            <div class="education-item">
                                <div class="education-header">
                                    <h4>${edu.titulo}</h4>
                                    <span class="education-institution">${edu.institucion}</span>
                                </div>
                                <div class="education-detail">
                                    <span class="education-area">${edu.area}</span>
                                    <span class="education-period">
                                        ${formatFecha(edu.fecha_inicio)} - ${edu.en_curso ? 'En curso' : formatFecha(edu.fecha_fin)}
                                    </span>
                                </div>
                                ${edu.descripcion ? `<p class="education-description">${edu.descripcion}</p>` : ''}
                            </div>
                        `).join('');
                        
                        container.innerHTML = educacionHTML;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        container.innerHTML = '<p class="error-text">Error al cargar educación. Por favor intente de nuevo.</p>';
                    });
            }
            
            function formatFecha(fechaStr) {
                if (!fechaStr) return '';
                
                const fecha = new Date(fechaStr);
                return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'short' });
            }
        });
        
        // Función para actualizar el contador en las pestañas de filtro
        function actualizarContadores(total, pendientes, revisados) {
            document.querySelector('.filter-chip:nth-child(1)').textContent = `Todos (${total})`;
            document.querySelector('.filter-chip:nth-child(2)').textContent = `Pendientes (${pendientes})`;
            document.querySelector('.filter-chip:nth-child(3)').textContent = `Revisados (${revisados})`;
        }
    </script>
    
    <!-- Estilos adicionales para la página de candidatos -->
    <style>
        .vacante-selector {
            margin-bottom: 20px;
        }
        
        .vacante-selector h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .vacante-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .match-score {
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 15px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .high-match {
            background-color: #d4edda;
            color: #155724;
        }
        
        .medium-match {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .low-match {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            color: #6c757d;
        }
        
        .candidate-summary {
            margin-top: 10px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .candidate-summary.expanded {
            max-height: 200px;
        }
        
        .summary-text {
            font-size: 0.9em;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .candidate-details h4 {
            cursor: pointer;
        }
        
        .candidate-details h4:hover {
            color: #007bff;
        }
        
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .page-numbers {
            display: flex;
            gap: 10px;
        }
        
        .page-numbers span {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .page-numbers span.current {
            background-color: #007bff;
            color: white;
        }
        
        /* Estilos para modales */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation-name: fadeIn;
            animation-duration: 0.3s;
        }
        
        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
            border-radius: 5px;
            animation-name: slideIn;
            animation-duration: 0.3s;
        }
        
        @keyframes fadeIn {
            from {opacity: 0} 
            to {opacity: 1}
        }
        
        @keyframes slideIn {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            margin-right: 20px;
        }
        
        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #333;
        }
        
        .modal-header p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            text-align: right;
        }
        
        /* Estilos para perfil modal */
        .profile-container {
            display: flex;
            flex-direction: column;
        }
        
        .profile-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .candidate-avatar-large {
            width: 100px;
            height: 100px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
        }
        
        .candidate-avatar-large img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .profile-basic-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .match-score-large {
            font-size: 18px;
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 15px;
            display: inline-block;
        }
        
        .profile-details h3 {
            margin: 20px 0 10px;
            color: #333;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        /* Estilos para skills */
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .skill-item {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            width: calc(50% - 10px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .skill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .skill-name {
            font-weight: bold;
            color: #333;
        }
        
        .skill-level {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .skill-level.principiante {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .skill-level.intermedio {
            background-color: #d4edda;
            color: #155724;
        }
        
        .skill-level.avanzado {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .skill-level.experto {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .skill-detail {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6c757d;
        }
        
        /* Estilos para experiencia y educación */
        .experience-container,
        .education-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .experience-item,
        .education-item {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .experience-header h4,
        .education-header h4 {
            margin: 0 0 5px;
            color: #333;
        }
        
        .experience-company,
        .education-institution {
            display: block;
            color: #666;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .experience-period,
        .education-period,
        .education-area {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .experience-description,
        .education-description {
            margin-top: 10px;
            font-size: 14px;
            color: #333;
        }
        
        /* Estilos para modal de contacto */
        .contact-info-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
        }
        
        .contact-label {
            font-weight: bold;
            width: 200px;
            color: #333;
        }
        
        .contact-value {
            color: #0056b3;
        }
        
        .contact-note {
            padding: 15px;
            background-color: #f0f4f7;
            border-left: 4px solid #007bff;
            margin-top: 20px;
        }
        
        /* Estilos para modal de detalles de match */
        .match-percentage-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .match-summary {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .match-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .match-details-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .match-details-table th,
        .match-details-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .match-details-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .match-details-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .match-details-table td.success {
            color: #155724;
        }
        
        .match-details-table td.warning {
            color: #856404;
        }
        
        .match-details-table td.danger {
            color: #721c24;
        }
        
        /* Estilos para mensajes de carga y error */
        .loading-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .loading-text,
        .error-text {
            color: #6c757d;
            font-style: italic;
        }
        
        .error-text {
            color: #dc3545;
        }
        
        /* Responsividad */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10px auto;
            }
            
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .skill-item {
                width: 100%;
            }
            
            .contact-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .contact-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</body>

</html>