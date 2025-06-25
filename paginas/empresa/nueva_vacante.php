<?php
session_start();
require_once '../../config/supabase.php';

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

// Definir las tecnologías y conocimientos para seleccionar
$habilidades = [
    'protocolos_red' => [
        'TCP/IP',
        'UDP',
        'HTTP/HTTPS',
        'DNS',
        'DHCP',
        'FTP',
        'SMTP',
        'POP3/IMAP',
        'SSH',
        'RDP'
    ],
    'dispositivos_red' => [
        'Routers',
        'Switches',
        'Firewalls',
        'Access Points',
        'Load Balancers',
        'Modems',
        'Repeaters',
        'VPN Concentrators',
        'Network Bridges',
        'Gateways'
    ],
    'seguridad_redes' => [
        'VPN',
        'Encrypting',
        'Firewalls Hardware',
        'Firewalls Software',
        'IDS/IPS',
        'Network Monitoring',
        'Penetration Testing',
        'SSL/TLS',
        'Authentication Systems',
        'Network Security Policies'
    ],
    'software' => [
        'Cisco IOS',
        'Wireshark',
        'Nmap',
        'PuTTY',
        'GNS3',
        'SolarWinds',
        'Nagios',
        'OpenVPN',
        'VMware',
        'Packet Tracer'
    ],
    'certificaciones' => [
        'CCNA',
        'CCNP',
        'CompTIA Network+',
        'CompTIA Security+',
        'CISSP',
        'CISM',
        'JNCIA',
        'AWS Certified Networking',
        'Microsoft Certified: Azure Network Engineer',
        'Fortinet NSE'
    ]
];

// Procesar el formulario si se ha enviado
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING);
    $responsabilidades = filter_input(INPUT_POST, 'responsabilidades', FILTER_SANITIZE_STRING);
    $requisitos = filter_input(INPUT_POST, 'requisitos', FILTER_SANITIZE_STRING);
    $salario = filter_input(INPUT_POST, 'salario', FILTER_VALIDATE_FLOAT);
    $modalidad = filter_input(INPUT_POST, 'modalidad', FILTER_SANITIZE_STRING);
    $ubicacion = filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_STRING);
    $anios_experiencia = filter_input(INPUT_POST, 'anios_experiencia', FILTER_VALIDATE_INT);
    
    // Validar datos
    if (empty($titulo) || empty($descripcion) || empty($responsabilidades) || empty($requisitos) || 
        $salario === false || empty($modalidad) || empty($ubicacion) || $anios_experiencia === false) {
        $errorMessage = 'Todos los campos obligatorios deben ser completados correctamente';
    } else {
        // Preparar datos de la vacante
        $vacanteData = [
            'empresa_id' => $empresaData[0]['id'],
            'empresa_nombre' => $empresaData[0]['nombre'],
            'reclutador_id' => $reclutadorData[0]['id'],
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'responsabilidades' => $responsabilidades,
            'requisitos' => $requisitos,
            'salario' => $salario,
            'modalidad' => $modalidad,
            'ubicacion' => $ubicacion,
            'anios_experiencia_requeridos' => $anios_experiencia,
            'fecha_publicacion' => date('Y-m-d'),
            'estado' => 'activa'
        ];
        
        // Insertar la vacante en la base de datos
        $vacanteResponse = supabaseInsert('vacantes', $vacanteData);
        
        if (isset($vacanteResponse['error'])) {
            $errorMessage = 'Error al crear la vacante';
        } else {
            $vacanteId = $vacanteResponse[0]['id'];
            
            // Procesar requisitos de tecnologías
            $tecnologiasRequeridas = 0;
            
            foreach ($habilidades as $categoria => $tecnologias) {
                foreach ($tecnologias as $tecnologia) {
                    $tecnologiaKey = str_replace(['/', ' ', '-'], '_', strtolower($tecnologia));
                    
                    // Si se requiere esta tecnología
                    if (isset($_POST['req_' . $tecnologiaKey]) && $_POST['req_' . $tecnologiaKey] == 'on') {
                        $nivel = isset($_POST['nivel_' . $tecnologiaKey]) ? filter_input(INPUT_POST, 'nivel_' . $tecnologiaKey, FILTER_SANITIZE_STRING) : 'regular';
                        
                        // Validar nivel
                        if (!in_array($nivel, ['malo', 'regular', 'bueno'])) {
                            $nivel = 'regular';
                        }
                        
                        // Preparar datos para insertar
                        $requisitoData = [
                            'vacante_id' => $vacanteId,
                            'tecnologia' => $tecnologia,
                            'nivel_requerido' => $nivel
                        ];
                        
                        // Insertar requisito
                        $requisitoResponse = supabaseInsert('requisitos_vacante', $requisitoData);
                        
                        if (!isset($requisitoResponse['error'])) {
                            $tecnologiasRequeridas++;
                        }
                    }
                }
            }
            
            $successMessage = 'Vacante creada exitosamente con ' . $tecnologiasRequeridas . ' requisitos de tecnologías.';
            
            // Redirigir después de un breve retraso
            header('Refresh: 2; URL=home_empresa.php');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Publicar Vacante - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/interfaz_iniciar_usuario.css">
    <link rel="stylesheet" href="../../estilo/dashboard.css">
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/empresa_dashboard.css">
</head>

<body>
    <div class="contenedor dashboard">
        <div class="sidebar">
            <div class="company-info">
                <img src="../../imagenes/company-default.png" alt="Logo de la empresa">
                <h3><?php echo htmlspecialchars($empresaData[0]['nombre']); ?></h3>
                <p><?php echo htmlspecialchars($reclutadorData[0]['nombre'] . ' ' . $reclutadorData[0]['apellidos']); ?></p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="home_empresa.php">Inicio</a></li>
                <li><a href="#" class="active">Publicar Vacante</a></li>
                <li><a href="mis_vacantes.php">Mis Vacantes</a></li>
                <li><a href="candidatos.php">Candidatos</a></li>
                <li><a href="perfil_empresa.php">Perfil de Empresa</a></li>
            </ul>
            
            <div class="logout">
                <a href="../../controllers/logout_controller.php">Cerrar Sesión</a>
            </div>
        </div>
        
        <div class="content">
            <h2>Publicar Nueva Vacante</h2>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="section">
                    <h3>Información de la Vacante</h3>
                    
                    <div class="form-group">
                        <label for="titulo">Título de la vacante*</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ej. Ingeniero de Redes Sr." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción general de la vacante*</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Describe brevemente la vacante..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="responsabilidades">Responsabilidades*</label>
                        <textarea id="responsabilidades" name="responsabilidades" placeholder="Lista las responsabilidades del puesto..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requisitos">Requisitos generales*</label>
                        <textarea id="requisitos" name="requisitos" placeholder="Describe los requisitos generales..." required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="salario">Salario mensual (MXN)*</label>
                            <input type="number" id="salario" name="salario" min="0" step="1000" placeholder="Ej. 25000" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="anios_experiencia">Años de experiencia requeridos*</label>
                            <input type="number" id="anios_experiencia" name="anios_experiencia" min="0" placeholder="Ej. 2" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modalidad">Modalidad de trabajo*</label>
                            <select id="modalidad" name="modalidad" required>
                                <option value="">Selecciona una opción</option>
                                <option value="presencial">Presencial</option>
                                <option value="remoto">Remoto</option>
                                <option value="híbrido">Híbrido</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ubicacion">Ubicación*</label>
                            <input type="text" id="ubicacion" name="ubicacion" placeholder="Ej. Ciudad de México" required>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h3>Conocimientos y Tecnologías Requeridas</h3>
                    <p>Selecciona las tecnologías que requiere la vacante y el nivel mínimo necesario:</p>
                    
                    <?php foreach ($habilidades as $categoria => $tecnologias): ?>
                        <div class="skills-category">
                            <h4><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h4>
                            
                            <div class="skills-grid">
                                <?php foreach ($tecnologias as $tecnologia): ?>
                                    <?php $tecnologiaKey = str_replace(['/', ' ', '-'], '_', strtolower($tecnologia)); ?>
                                    <div class="skill-item">
                                        <div class="skill-name">
                                            <input type="checkbox" id="req_<?php echo $tecnologiaKey; ?>" name="req_<?php echo $tecnologiaKey; ?>">
                                            <label for="req_<?php echo $tecnologiaKey; ?>"><?php echo htmlspecialchars($tecnologia); ?></label>
                                        </div>
                                        
                                        <div class="skill-level">
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="malo" checked> 
                                                Básico
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="regular"> 
                                                Intermedio
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="bueno"> 
                                                Avanzado
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit">Publicar Vacante</button>
            </form>
        </div>
    </div>
</body>
</html>
