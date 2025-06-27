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

// Obtener las habilidades de la base de datos
$habilidadesManager = new Habilidades();
$habilidades = [];

try {
    $habilidades = $habilidadesManager->obtenerHabilidadesPorCategoria();

    if (empty($habilidades) || count($habilidades) == 0) {
        $resultado = supabaseFetch('habilidades', '*');

        if (is_array($resultado) && !empty($resultado)) {
            $porCategoria = [];

            foreach ($resultado as $habilidad) {
                $categoria = $habilidad['categoria'] ?? 'otras_habilidades';
                if (!isset($porCategoria[$categoria])) {
                    $porCategoria[$categoria] = [];
                }
                $porCategoria[$categoria][] = (object)$habilidad;
            }

            $habilidades = $porCategoria;
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener habilidades: " . $e->getMessage());
}

// Si no hay habilidades en la base de datos, usar habilidades predefinidas
if (empty($habilidades)) {
    $habilidades = [
        'protocolos_red' => [
            'TCP/IP', 'UDP', 'HTTP/HTTPS', 'DNS', 'DHCP', 'FTP', 'SMTP', 'POP3/IMAP', 'SSH', 'RDP'
        ],
        'dispositivos_red' => [
            'Routers', 'Switches', 'Firewalls', 'Access Points', 'Load Balancers', 'Modems', 'Repeaters', 'VPN Concentrators', 'Network Bridges', 'Gateways'
        ],
        'seguridad_redes' => [
            'VPN', 'Encrypting', 'Firewalls Hardware', 'Firewalls Software', 'IDS/IPS', 'Network Monitoring', 'Penetration Testing', 'SSL/TLS', 'Authentication Systems', 'Network Security Policies'
        ],
        'software' => [
            'Cisco IOS', 'Wireshark', 'Nmap', 'PuTTY', 'GNS3', 'SolarWinds', 'Nagios', 'OpenVPN', 'VMware', 'Packet Tracer'
        ],
        'certificaciones' => [
            'CCNA', 'CCNP', 'CompTIA Network+', 'CompTIA Security+', 'CISSP', 'CISM', 'JNCIA', 'AWS Certified Networking', 'Microsoft Certified: Azure Network Engineer', 'Fortinet NSE'
        ]
    ];
}

// Variables para mensajes y datos
$successMessage = '';
$errorMessage = '';
$editingVacante = null;
$requisitosVacante = [];

// Obtener vacantes de la empresa
$vacantesEmpresa = supabaseFetch('vacantes', '*', ['empresa_id' => $empresaData[0]['id']]);

if (isset($vacantesEmpresa['error'])) {
    $vacantesEmpresa = [];
}

// Verificar si se está editando una vacante
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $vacanteId = (int)$_GET['edit'];

    // Buscar la vacante en las vacantes de la empresa
    foreach ($vacantesEmpresa as $vacante) {
        if ($vacante['id'] == $vacanteId) {
            $editingVacante = $vacante;
            break;
        }
    }

    if ($editingVacante) {
        // Obtener requisitos de la vacante
        $requisitosVacante = supabaseFetch('requisitos_vacante', '*', ['vacante_id' => $vacanteId]);
        if (isset($requisitosVacante['error'])) {
            $requisitosVacante = [];
        }
    }
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vacante'])) {
    $vacanteId = filter_input(INPUT_POST, 'vacante_id', FILTER_VALIDATE_INT);

    if ($vacanteId) {
        // Obtener datos del formulario
        $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
        $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
        $responsabilidades = filter_input(INPUT_POST, 'responsabilidades', FILTER_SANITIZE_STRING);
        $requisitos = filter_input(INPUT_POST, 'requisitos', FILTER_SANITIZE_STRING);
        $salario = filter_input(INPUT_POST, 'salario', FILTER_VALIDATE_FLOAT);
        $modalidad = filter_input(INPUT_POST, 'modalidad', FILTER_SANITIZE_STRING);
        $ubicacion = filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_STRING);
        $anios_experiencia = filter_input(INPUT_POST, 'anios_experiencia', FILTER_VALIDATE_INT);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);

        // Validar datos
        if (empty($titulo) || empty($descripcion) || empty($responsabilidades) || empty($requisitos) || 
            $salario === false || empty($modalidad) || empty($ubicacion) || $anios_experiencia === false) {
            $errorMessage = 'Todos los campos obligatorios deben ser completados correctamente';
        } else {
            // Preparar datos de actualización
            $updateData = [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'responsabilidades' => $responsabilidades,
                'requisitos' => $requisitos,
                'salario' => $salario,
                'modalidad' => $modalidad,
                'ubicacion' => $ubicacion,
                'anios_experiencia_requeridos' => $anios_experiencia,
                'estado' => $estado
            ];

            // Actualizar la vacante
            $updateResponse = supabaseUpdate('vacantes', $updateData, ['id' => $vacanteId]);

            if (isset($updateResponse['error'])) {
                $errorMessage = 'Error al actualizar la vacante';
            } else {
                // Eliminar requisitos existentes
                supabaseDelete('requisitos_vacante', ['vacante_id' => $vacanteId]);

                // Procesar nuevos requisitos de tecnologías
                $tecnologiasRequeridas = 0;

                foreach ($habilidades as $categoria => $tecnologias) {
                    foreach ($tecnologias as $tecnologia) {
                        // Manejar tanto objetos como strings
                        if (is_object($tecnologia)) {
                            $tecnologiaNombre = $tecnologia->nombre ?? null;
                            $tecnologiaId = $tecnologia->id ?? null;
                        } elseif (is_array($tecnologia)) {
                            $tecnologiaNombre = $tecnologia['nombre'] ?? $tecnologia[0] ?? null;
                            $tecnologiaId = $tecnologia['id'] ?? null;
                        } else {
                            $tecnologiaNombre = $tecnologia;
                            $tecnologiaId = null;
                        }

                        if (!$tecnologiaNombre) continue;

                        $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologiaNombre));

                        // Si se requiere esta tecnología
                        if (isset($_POST['req_' . $tecnologiaKey]) && $_POST['req_' . $tecnologiaKey] == 'on') {
                            $nivel = isset($_POST['nivel_' . $tecnologiaKey]) ? filter_input(INPUT_POST, 'nivel_' . $tecnologiaKey, FILTER_SANITIZE_STRING) : 'intermedio';

                            if (!in_array($nivel, ['principiante', 'intermedio', 'avanzado', 'experto'])) {
                                $nivel = 'intermedio';
                            }

                            $requisitoData = [
                                'vacante_id' => $vacanteId,
                                'tecnologia' => $tecnologiaNombre,
                                'nivel_requerido' => $nivel
                            ];

                            if ($tecnologiaId !== null) {
                                $requisitoData['habilidad_id'] = $tecnologiaId;
                            }

                            $requisitoResponse = supabaseInsert('requisitos_vacante', $requisitoData);

                            if (!isset($requisitoResponse['error'])) {
                                $tecnologiasRequeridas++;
                            }
                        }
                    }
                }

                $successMessage = 'Vacante actualizada exitosamente con ' . $tecnologiasRequeridas . ' requisitos de tecnologías.';

                // Recargar datos
                $vacantesEmpresa = supabaseFetch('vacantes', '*', ['empresa_id' => $empresaData[0]['id']]);
                $editingVacante = null;

                // Redirigir después de un breve retraso
                header('Refresh: 2; URL=mis_vacantes.php');
            }
        }
    }
}

// Procesar eliminación de vacante
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $vacanteId = (int)$_GET['delete'];

    // Verificar que la vacante pertenece a la empresa
    $vacanteToDelete = null;
    foreach ($vacantesEmpresa as $vacante) {
        if ($vacante['id'] == $vacanteId) {
            $vacanteToDelete = $vacante;
            break;
        }
    }

    if ($vacanteToDelete) {
        // Eliminar requisitos primero
        supabaseDelete('requisitos_vacante', ['vacante_id' => $vacanteId]);

        // Eliminar vacante
        $deleteResponse = supabaseDelete('vacantes', ['id' => $vacanteId]);

        if (isset($deleteResponse['error'])) {
            $errorMessage = 'Error al eliminar la vacante';
        } else {
            $successMessage = 'Vacante eliminada exitosamente';
            // Recargar datos
            $vacantesEmpresa = supabaseFetch('vacantes', '*', ['empresa_id' => $empresaData[0]['id']]);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Vacantes - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
    <link rel="stylesheet" href="../../estilo/vacantes_fix.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .skills-category {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }

        .skills-category h4 {
            background-color: #f9f9f9;
            padding: 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            text-transform: capitalize;
        }

        .skill-name {
            position: relative;
            cursor: help;
            display: flex;
            align-items: center;
        }

        .info-icon {
            font-size: 0.8em;
            color: #007bff;
            margin-left: 5px;
            vertical-align: super;
        }

        .vacantes-list {
            margin-bottom: 30px;
        }

        .vacante-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .vacante-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .vacante-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .vacante-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-activa {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pausada {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cerrada {
            background-color: #f8d7da;
            color: #721c24;
        }

        .vacante-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9em;
        }

        .info-value {
            color: #333;
            margin-top: 2px;
        }

        .vacante-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
        }

        .btn-edit {
            background-color: #007bff;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .edit-form {
            background-color: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .no-vacantes {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        /* Mejoras para mobile */
        @media (max-width: 768px) {
            .vacante-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .vacante-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .skill-item {
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }

            .skill-level {
                display: flex;
                flex-wrap: wrap;
                margin-top: 5px;
            }

            .skill-level label {
                margin-right: 10px;
                margin-bottom: 5px;
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
                <p><?php 
                    echo htmlspecialchars($userProfile[0]['nombre'] . ' ' . $userProfile[0]['apellido']);
                ?></p>
            </div>

            <ul class="nav-menu">
                <li><a href="home_empresa.php">Inicio</a></li>
                <li><a href="nueva_vacante.php">Publicar Vacante</a></li>
                <li><a href="#" class="active">Mis Vacantes</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
            </ul>

            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>

        <div class="content">
            <h2>Mis Vacantes</h2>

            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <?php if ($editingVacante): ?>
                <div class="edit-form">
                    <h3>Editar Vacante: <?php echo htmlspecialchars($editingVacante['titulo']); ?></h3>

                    <form action="" method="POST">
                        <input type="hidden" name="vacante_id" value="<?php echo $editingVacante['id']; ?>">

                        <div class="section">
                            <h4>Información de la Vacante</h4>

                            <div class="form-group">
                                <label for="titulo">Título de la vacante*</label>
                                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($editingVacante['titulo']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción general de la vacante*</label>
                                <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($editingVacante['descripcion']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="responsabilidades">Responsabilidades*</label>
                                <textarea id="responsabilidades" name="responsabilidades" required><?php echo htmlspecialchars($editingVacante['responsabilidades']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="requisitos">Requisitos generales*</label>
                                <textarea id="requisitos" name="requisitos" required><?php echo htmlspecialchars($editingVacante['requisitos']); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="salario">Salario mensual (MXN)*</label>
                                    <input type="number" id="salario" name="salario" min="0" step="1000" value="<?php echo $editingVacante['salario']; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="anios_experiencia">Años de experiencia requeridos*</label>
                                    <input type="number" id="anios_experiencia" name="anios_experiencia" min="0" value="<?php echo $editingVacante['anios_experiencia_requeridos']; ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="modalidad">Modalidad de trabajo*</label>
                                    <select id="modalidad" name="modalidad" required>
                                        <option value="presencial" <?php echo $editingVacante['modalidad'] === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                                        <option value="remoto" <?php echo $editingVacante['modalidad'] === 'remoto' ? 'selected' : ''; ?>>Remoto</option>
                                        <option value="híbrido" <?php echo $editingVacante['modalidad'] === 'híbrido' ? 'selected' : ''; ?>>Híbrido</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="ubicacion">Ubicación*</label>
                                    <input type="text" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($editingVacante['ubicacion']); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado de la vacante*</label>
                                <select id="estado" name="estado" required>
                                    <option value="activa" <?php echo $editingVacante['estado'] === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                    <option value="pausada" <?php echo $editingVacante['estado'] === 'pausada' ? 'selected' : ''; ?>>Pausada</option>
                                    <option value="cerrada" <?php echo $editingVacante['estado'] === 'cerrada' ? 'selected' : ''; ?>>Cerrada</option>
                                </select>
                            </div>
                        </div>

                        <div class="section">
                            <h4>Conocimientos y Tecnologías Requeridas</h4>
                            <p>Selecciona las tecnologías que requiere la vacante y el nivel mínimo necesario:</p>

                            <?php 
                            // Crear array de requisitos actuales para fácil acceso
                            $requisitosActuales = [];
                            foreach ($requisitosVacante as $req) {
                                $key = str_replace(['/', ' ', '-', '.'], '_', strtolower($req['tecnologia']));
                                $requisitosActuales[$key] = $req['nivel_requerido'];
                            }

                            ksort($habilidades);

                            foreach ($habilidades as $categoria => $tecnologias): 
                                if (empty($tecnologias)) continue;
                            ?>
                                <div class="skills-category">
                                    <h4><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h4>

                                    <div class="skills-grid">
                                        <?php 
                                        usort($tecnologias, function($a, $b) {
                                            $nombreA = is_object($a) ? ($a->nombre ?? '') : (is_array($a) ? ($a['nombre'] ?? '') : $a);
                                            $nombreB = is_object($b) ? ($b->nombre ?? '') : (is_array($b) ? ($b['nombre'] ?? '') : $b);
                                            return strcmp($nombreA, $nombreB);
                                        });

                                        foreach ($tecnologias as $tecnologia): 
                                            if (is_object($tecnologia)) {
                                                $tecnologiaNombre = $tecnologia->nombre ?? null;
                                                $descripcion = $tecnologia->descripcion ?? '';
                                            } elseif (is_array($tecnologia)) {
                                                $tecnologiaNombre = $tecnologia['nombre'] ?? $tecnologia[0] ?? null;
                                                $descripcion = $tecnologia['descripcion'] ?? '';
                                            } else {
                                                $tecnologiaNombre = $tecnologia;
                                                $descripcion = '';
                                            }

                                            if (!$tecnologiaNombre) continue;

                                            $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologiaNombre));
                                            $isRequired = isset($requisitosActuales[$tecnologiaKey]);
                                            $currentLevel = $isRequired ? $requisitosActuales[$tecnologiaKey] : 'principiante';
                                        ?>
                                            <div class="skill-item">
                                                <div class="skill-name" <?php if (!empty($descripcion)): ?>title="<?php echo htmlspecialchars($descripcion); ?>"<?php endif; ?>>
                                                    <input type="checkbox" id="req_<?php echo $tecnologiaKey; ?>" name="req_<?php echo $tecnologiaKey; ?>" <?php echo $isRequired ? 'checked' : ''; ?>>
                                                    <label for="req_<?php echo $tecnologiaKey; ?>"><?php echo htmlspecialchars($tecnologiaNombre); ?></label>
                                                    <?php if (!empty($descripcion)): ?>
                                                        <span class="info-icon">ℹ</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="skill-level">
                                                    <label>
                                                        <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="principiante" <?php echo $currentLevel === 'principiante' ? 'checked' : ''; ?>> 
                                                        Básico
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="intermedio" <?php echo $currentLevel === 'intermedio' ? 'checked' : ''; ?>> 
                                                        Intermedio
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="avanzado" <?php echo $currentLevel === 'avanzado' ? 'checked' : ''; ?>> 
                                                        Avanzado
                                                    </label>
                                                    <label>
                                                        <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="experto" <?php echo $currentLevel === 'experto' ? 'checked' : ''; ?>> 
                                                        Experto
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="submit" name="update_vacante">Guardar Cambios</button>
                        <a href="mis_vacantes.php" class="btn btn-cancel">Cancelar</a>
                    </form>
                </div>
            <?php endif; ?>

            <div class="vacantes-list">
                <?php if (empty($vacantesEmpresa)): ?>
                    <div class="no-vacantes">
                        <h3>No tienes vacantes publicadas</h3>
                        <p>¡Comienza publicando tu primera vacante!</p>
                        <a href="nueva_vacante.php" class="btn btn-edit">Publicar Vacante</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($vacantesEmpresa as $vacante): ?>
                        <div class="vacante-card">
                            <div class="vacante-header">
                                <h3 class="vacante-title"><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                                <span class="vacante-status status-<?php echo $vacante['estado']; ?>">
                                    <?php echo ucfirst($vacante['estado']); ?>
                                </span>
                            </div>

                            <div class="vacante-info">
                                <div class="info-item">
                                    <span class="info-label">Salario:</span>
                                    <span class="info-value">$<?php echo number_format($vacante['salario'], 0, '.', ','); ?> MXN</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Modalidad:</span>
                                    <span class="info-value"><?php echo ucfirst($vacante['modalidad']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Ubicación:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Experiencia:</span>
                                    <span class="info-value"><?php echo $vacante['anios_experiencia_requeridos']; ?> años</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Fecha de publicación:</span>
                                    <span class="info-value"><?php echo date('d/m/Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                                </div>
                            </div>

                            <div class="vacante-actions">
                                <a href="?edit=<?php echo $vacante['id']; ?>" class="btn btn-edit">Editar</a>
                                <a href="?delete=<?php echo $vacante['id']; ?>" class="btn btn-delete" 
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar esta vacante?')">Eliminar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

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
        });
    </script>
</body>
</html>