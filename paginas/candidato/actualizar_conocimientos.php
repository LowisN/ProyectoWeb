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

// Obtener conocimientos de tecnologías de red
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

// Obtener los conocimientos actuales del candidato
$conocimientos = supabaseFetch('conocimientos_candidato', '*', ['candidato_id' => $candidato['id']]);

// Crear un array asociativo para facilitar el acceso

// Procesar el formulario si se ha enviado
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tecnologiasGuardadas = 0;
    $errores = 0;
    
    // Recorrer cada categoría y sus tecnologías
    foreach ($habilidades as $categoria => $tecnologias) {
        foreach ($tecnologias as $tecnologia) {
            $tecnologiaKey = str_replace(['/', ' ', '-'], '_', strtolower($tecnologia));
            
            // Si la tecnología fue evaluada por el usuario
            if (isset($_POST[$tecnologiaKey])) {
                $nivel = filter_input(INPUT_POST, $tecnologiaKey, FILTER_SANITIZE_STRING);
                
                // Validar nivel
                if (!in_array($nivel, ['malo', 'regular', 'bueno'])) {
                    continue;
                }
                
                // Preparar datos para insertar/actualizar
                $conocimientoData = [
                    'candidato_id' => $candidato['id'],
                    'tecnologia' => $tecnologia,
                    'nivel' => $nivel
                ];
                
                // Verificar si ya existe este conocimiento
                $conocimientoExistente = isset($conocimientosCandidato[$tecnologia]);
                
                if ($conocimientoExistente) {
                    // Actualizar
                    $updateResponse = supabaseUpdate('conocimientos_candidato', ['nivel' => $nivel], [
                        'candidato_id' => $candidato['id'],
                        'tecnologia' => $tecnologia
                    ]);
                    
                    if (isset($updateResponse['error'])) {
                        $errores++;
                    } else {
                        $tecnologiasGuardadas++;
                        $conocimientosCandidato[$tecnologia] = $nivel;
                    }
                } else {
                    // Insertar
                    $insertResponse = supabaseInsert('conocimientos_candidato', $conocimientoData);
                    
                    if (isset($insertResponse['error'])) {
                        $errores++;
                    } else {
                        $tecnologiasGuardadas++;
                        $conocimientosCandidato[$tecnologia] = $nivel;
                    }
                }
            }
        }
    }
    
    if ($errores > 0) {
        $errorMessage = "Se produjeron errores al guardar algunos conocimientos.";
    }
    
    if ($tecnologiasGuardadas > 0) {
        $successMessage = "Conocimientos actualizados correctamente.";
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
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/candidato_dashboard.css">
</head>

<body class="dashboard">
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
        
        <div class="content">
            <h2>Mis Conocimientos y Habilidades</h2>
            
            <p>Evalúa tus conocimientos y habilidades en tecnologías de redes. Esto nos ayudará a encontrar las mejores vacantes para tu perfil.</p>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <?php foreach ($habilidades as $categoria => $tecnologias): ?>
                    <div class="section skills-category">
                        <h3><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h3>
                        
                        <div class="skills-grid">
                            <?php foreach ($tecnologias as $tecnologia): ?>
                                <?php $tecnologiaKey = str_replace(['/', ' ', '-'], '_', strtolower($tecnologia)); ?>
                                <div class="skill-item">
                                    <div class="skill-name"><?php echo htmlspecialchars($tecnologia); ?></div>
                                    <div class="skill-rating">
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="malo" <?php echo (isset($conocimientosCandidato[$tecnologia]) && $conocimientosCandidato[$tecnologia] === 'malo') ? 'checked' : ''; ?>> 
                                            Malo
                                        </label>
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="regular" <?php echo (isset($conocimientosCandidato[$tecnologia]) && $conocimientosCandidato[$tecnologia] === 'regular') ? 'checked' : ''; ?>> 
                                            Regular
                                        </label>
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="bueno" <?php echo (isset($conocimientosCandidato[$tecnologia]) && $conocimientosCandidato[$tecnologia] === 'bueno') ? 'checked' : ''; ?>> 
                                            Bueno
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit">Guardar Conocimientos</button>
            </form>
        </div>
    </div>
</body>
</html>
