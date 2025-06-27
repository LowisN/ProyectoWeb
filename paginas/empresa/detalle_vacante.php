<?php
session_start();
require_once '../../config/supabase.php';
require_once '../../config/SupabaseClient.php';
require_once '../../models/habilidades.php';

// Verificar si el usuario está autenticado y es un reclutador
if (!isset($_SESSION['access_token']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'reclutador') {
    header('Location: ../../index.php');
    exit;
}

// Verificar que se proporcione el ID de la vacante
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Si no hay ID en el GET pero tenemos uno en la sesión, usarlo
    if (isset($_SESSION['ultima_vacante_creada']) && !empty($_SESSION['ultima_vacante_creada'])) {
        $vacanteId = $_SESSION['ultima_vacante_creada'];
        // Limpiar la sesión
        unset($_SESSION['ultima_vacante_creada']);
        // Redirigir correctamente
        header('Location: detalle_vacante.php?id=' . $vacanteId . '&success=' . urlencode('Vacante creada exitosamente'));
        exit;
    } else {
        // Si no hay ID en ningún lado, ir a la página de vacantes
        error_log("Error: No se proporcionó ID de vacante y no hay ID en sesión");
        header('Location: mis_vacantes.php?error=ID de vacante no proporcionado');
        exit;
    }
}

$vacanteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$vacanteId) {
    header('Location: mis_vacantes.php?error=ID de vacante inválido');
    exit;
}

// Obtener información del usuario actual
$userId = $_SESSION['user']['id'];
$userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);

if (empty($userProfile) || isset($userProfile['error'])) {
    header('Location: ../../index.php?error=Error al cargar el perfil');
    exit;
}

// Obtener datos del reclutador
$reclutadorData = supabaseFetch('reclutadores', '*', ['perfil_id' => $userProfile[0]['id']]);

if (empty($reclutadorData) || isset($reclutadorData['error'])) {
    header('Location: ../../index.php?error=Error al cargar datos del reclutador');
    exit;
}

// Obtener datos de la empresa
$empresaData = supabaseFetch('empresas', '*', ['id' => $reclutadorData[0]['empresa_id']]);

if (empty($empresaData) || isset($empresaData['error'])) {
    header('Location: ../../index.php?error=Error al cargar datos de la empresa');
    exit;
}

// Obtener datos de la vacante
$vacante = supabaseFetch('vacantes', '*', ['id' => $vacanteId]);

if (empty($vacante) || isset($vacante['error'])) {
    header('Location: mis_vacantes.php?error=Vacante no encontrada');
    exit;
}

// Verificar que la vacante pertenezca a la empresa del reclutador
if ($vacante[0]['empresa_id'] != $empresaData[0]['id']) {
    header('Location: mis_vacantes.php?error=No tienes acceso a esta vacante');
    exit;
}

// Obtener las habilidades requeridas por la vacante desde vacante_habilidades
$habilidadesVacante = [];
$supabase = getSupabaseClient();

try {
    error_log("Intentando obtener habilidades para la vacante ID: $vacanteId");
    
    // Obtener habilidades con join a la tabla de habilidades para obtener nombres y categorías
    $response = $supabase->request("/rest/v1/vacante_habilidades?select=*,habilidades(nombre,categoria,descripcion)&vacante_id=eq.$vacanteId");
    
    error_log("Respuesta de la consulta de habilidades: " . json_encode(array_slice((array)$response, 0, 2)));
    
    if (isset($response->data) && is_array($response->data)) {
        $habilidadesVacante = $response->data;
        error_log("Encontradas " . count($habilidadesVacante) . " habilidades para la vacante $vacanteId");
    } else {
        // Intento alternativo usando supabaseFetch
        error_log("Intentando consulta alternativa con supabaseFetch");
        $resultadoAlternativo = supabaseFetch('vacante_habilidades', '*', ['vacante_id' => $vacanteId]);
        
        if (!empty($resultadoAlternativo) && !isset($resultadoAlternativo['error'])) {
            error_log("Encontradas " . count($resultadoAlternativo) . " habilidades (método alternativo)");
            // Ahora obtener los datos de las habilidades
            foreach ($resultadoAlternativo as &$vh) {
                $habilidadDatos = supabaseFetch('habilidades', '*', ['id' => $vh['habilidad_id']]);
                if (!empty($habilidadDatos) && !isset($habilidadDatos['error'])) {
                    $vh['habilidades'] = (object)$habilidadDatos[0];
                }
            }
            $habilidadesVacante = array_map(function($item) { return (object)$item; }, $resultadoAlternativo);
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener habilidades de la vacante: " . $e->getMessage());
}

// Preparar variables para la vista
$vacanteData = $vacante[0];
$fechaPublicacion = new DateTime($vacanteData['fecha_publicacion']);
$fechaExpiracion = isset($vacanteData['fecha_expiracion']) ? new DateTime($vacanteData['fecha_expiracion']) : null;

// Determinar si la vacante ha expirado
$haExpirado = false;
if ($fechaExpiracion !== null) {
    $haExpirado = $fechaExpiracion < new DateTime();
}

// Agrupar habilidades por categoría para mostrarlas organizadas
$habilidadesPorCategoria = [];

foreach ($habilidadesVacante as $habilidad) {
    // Para registros de vacante_habilidades con join
    $categoria = isset($habilidad->habilidades) ? ($habilidad->habilidades->categoria ?? 'otros') : 'otros';
    
    if (!isset($habilidadesPorCategoria[$categoria])) {
        $habilidadesPorCategoria[$categoria] = [];
    }
    
    $habilidadesPorCategoria[$categoria][] = [
        'nombre' => isset($habilidad->habilidades) ? ($habilidad->habilidades->nombre ?? 'Sin nombre') : 'Sin nombre',
        'nivel' => $habilidad->nivel_requerido ?? 'intermedio',
        'obligatorio' => $habilidad->obligatorio ?? true,
        'descripcion' => isset($habilidad->habilidades) ? ($habilidad->habilidades->descripcion ?? '') : ''
    ];
}

// Función para traducir nivel a español para mostrar
function traducirNivel($nivel) {
    switch ($nivel) {
        case 'principiante': return 'Básico';
        case 'intermedio': return 'Intermedio';
        case 'avanzado': return 'Avanzado';
        case 'experto': return 'Experto';
        default: return $nivel;
    }
}

// Procesar mensajes de acción
$successMessage = '';
$errorMessage = '';

if (isset($_GET['success']) && !empty($_GET['success'])) {
    $successMessage = htmlspecialchars($_GET['success']);
}

if (isset($_GET['error']) && !empty($_GET['error'])) {
    $errorMessage = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Vacante - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .vacante-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        
        .vacante-title {
            flex-grow: 1;
        }
        
        .vacante-actions {
            display: flex;
            gap: 10px;
        }
        
        .vacante-section {
            margin-bottom: 25px;
        }
        
        .vacante-section h3 {
            background-color: #f5f5f5;
            padding: 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        
        .vacante-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .vacante-meta-item {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 4px;
            border-left: 3px solid #6c757d;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .habilidades-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .habilidad-item {
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            width: calc(50% - 5px);
            box-sizing: border-box;
        }
        
        .habilidad-nombre {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .habilidad-nivel {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .habilidad-obligatorio {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 3px;
        }
        
        .destacada {
            position: relative;
        }
        
        .destacada::after {
            content: "Destacada";
            position: absolute;
            top: 0;
            right: 0;
            background-color: #ffc107;
            color: #000;
            padding: 5px 10px;
            font-size: 0.85em;
            border-radius: 0 0 0 4px;
        }
        
        @media (max-width: 768px) {
            .habilidad-item {
                width: 100%;
            }
            
            .vacante-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="company-info">
                <img src="../../imagenes/logo.png" alt="Logo de la empresa">
                <h3><?php echo isset($empresaData[0]['nombre']) ? htmlspecialchars($empresaData[0]['nombre']) : 'Empresa'; ?></h3>
                <p><?php echo htmlspecialchars($reclutadorData[0]['nombre'] . ' ' . $reclutadorData[0]['apellidos']); ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_empresa.php">Inicio</a></li>
                <li><a href="nueva_vacante.php">Publicar Vacante</a></li>
                <li><a href="mis_vacantes.php" class="active">Mis Vacantes</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <div class="vacante-header <?php echo isset($vacanteData['destacada']) && $vacanteData['destacada'] ? 'destacada' : ''; ?>">
                <div class="vacante-title">
                    <h2><?php echo htmlspecialchars($vacanteData['titulo']); ?></h2>
                    <p>
                        <span class="badge badge-<?php echo $vacanteData['estado'] === 'activa' ? 'success' : ($vacanteData['estado'] === 'pausada' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($vacanteData['estado']); ?>
                        </span>
                        
                        <?php if ($fechaExpiracion !== null): ?>
                            <?php if ($haExpirado): ?>
                                <span class="badge badge-danger">Expirada</span>
                            <?php else: ?>
                                <span class="badge badge-info">Expira: <?php echo $fechaExpiracion->format('d/m/Y'); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <span class="badge badge-secondary">
                            <?php echo $vacanteData['anios_experiencia']; ?> años de experiencia
                        </span>
                    </p>
                </div>
                
                <div class="vacante-actions">
                    <a href="editar_vacante.php?id=<?php echo $vacanteData['id']; ?>" class="btn btn-primary">Editar</a>
                    
                    <?php if ($vacanteData['estado'] === 'activa'): ?>
                        <a href="cambiar_estado_vacante.php?id=<?php echo $vacanteData['id']; ?>&estado=pausada" class="btn btn-warning">Pausar</a>
                    <?php elseif ($vacanteData['estado'] === 'pausada'): ?>
                        <a href="cambiar_estado_vacante.php?id=<?php echo $vacanteData['id']; ?>&estado=activa" class="btn btn-success">Activar</a>
                    <?php endif; ?>
                    
                    <a href="cambiar_estado_vacante.php?id=<?php echo $vacanteData['id']; ?>&estado=cerrada" class="btn btn-danger" 
                       onclick="return confirm('¿Estás seguro de que deseas cerrar esta vacante? Esta acción no se puede deshacer.');">
                        Cerrar
                    </a>
                </div>
            </div>
            
            <div class="vacante-meta">
                <div class="vacante-meta-item">
                    <strong>Modalidad:</strong> <?php echo ucfirst($vacanteData['modalidad']); ?>
                </div>
                
                <div class="vacante-meta-item">
                    <strong>Ubicación:</strong> <?php echo htmlspecialchars($vacanteData['ubicacion']); ?>
                </div>
                
                <div class="vacante-meta-item">
                    <strong>Salario:</strong> $<?php echo number_format($vacanteData['salario'], 2); ?> MXN
                </div>
                
                <div class="vacante-meta-item">
                    <strong>Publicada:</strong> <?php echo $fechaPublicacion->format('d/m/Y'); ?>
                </div>
            </div>
            
            <div class="vacante-section">
                <h3>Descripción</h3>
                <div class="section-content">
                    <?php echo nl2br(htmlspecialchars($vacanteData['descripcion'])); ?>
                </div>
            </div>
            
            <div class="vacante-section">
                <h3>Responsabilidades</h3>
                <div class="section-content">
                    <?php echo nl2br(htmlspecialchars($vacanteData['responsabilidades'])); ?>
                </div>
            </div>
            
            <div class="vacante-section">
                <h3>Requisitos</h3>
                <div class="section-content">
                    <?php echo nl2br(htmlspecialchars($vacanteData['requisitos'])); ?>
                </div>
            </div>
            
            <div class="vacante-section">
                <h3>Habilidades Requeridas</h3>
                
                <?php if (empty($habilidadesPorCategoria)): ?>
                    <p>No hay habilidades específicas requeridas para esta vacante.</p>
                <?php else: ?>
                    <?php foreach ($habilidadesPorCategoria as $categoria => $habilidades): ?>
                        <h4><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h4>
                        <div class="habilidades-lista">
                            <?php foreach ($habilidades as $habilidad): ?>
                                <div class="habilidad-item">
                                    <div class="habilidad-nombre"><?php echo htmlspecialchars($habilidad['nombre']); ?></div>
                                    <div class="habilidad-nivel">
                                        Nivel: <?php echo traducirNivel($habilidad['nivel']); ?>
                                    </div>
                                    <?php if (isset($habilidad['obligatorio']) && $habilidad['obligatorio']): ?>
                                        <div class="habilidad-obligatorio">Obligatorio</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="form-buttons">
                <a href="mis_vacantes.php" class="btn btn-secondary">Volver a Mis Vacantes</a>
                <a href="candidatos.php?vacante_id=<?php echo $vacanteData['id']; ?>" class="btn btn-primary">Ver Candidaturas</a>
            </div>
        </div>
    </div>
</body>
</html>
