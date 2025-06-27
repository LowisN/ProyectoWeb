<?php
session_start();
require_once '../../config/supabase.php';
require_once '../../config/SupabaseClient.php';
require_once '../../models/habilidades.php';

// Verificar si hay un proceso de registro en curso y si hemos pasado por el paso 2
if (!isset($_SESSION['registro_candidato']) || $_SESSION['registro_candidato']['paso_actual'] < 3) {
    header('Location: ../../controllers/registro_candidato_unificado_controller.php');
    exit;
}

$datos_personales = $_SESSION['registro_candidato']['datos_personales'];
$datos_academicos = $_SESSION['registro_candidato']['datos_academicos'];
$datos_profesionales = $_SESSION['registro_candidato']['datos_profesionales'];
$habilidades_guardadas = isset($_SESSION['registro_candidato']['habilidades']) ? $_SESSION['registro_candidato']['habilidades'] : [];

// Obtener las habilidades de la base de datos
$habilidadesManager = new Habilidades();
$habilidades = [];

// Intentar con ambos métodos para asegurar que obtenemos datos
try {
    // Primer intento con la clase Habilidades
    $habilidades = $habilidadesManager->obtenerHabilidadesPorCategoria();
    error_log("Habilidades obtenidas por categoría: " . count($habilidades) . " categorías");
    
    // Si no hay habilidades o están vacías, intentar con método alternativo
    if (empty($habilidades) || count($habilidades) == 0) {
        // Intentar obtener directamente usando supabaseFetch
        error_log("No se encontraron categorías. Intentando con supabaseFetch");
        $resultado = supabaseFetch('habilidades', '*');
        
        if (is_array($resultado) && !empty($resultado)) {
            error_log("supabaseFetch: Encontradas " . count($resultado) . " habilidades");
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
    error_log("No se encontraron habilidades en la base de datos. Usando habilidades predefinidas.");
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
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Candidato - Habilidades - ChambaNet</title>
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <link rel="stylesheet" href="../../estilo/reg_hab.css">
    <style>
        .section.skills-category {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        
        .section.skills-category h3 {
            background-color: #f9f9f9;
            padding: 10px;
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            text-transform: capitalize;
        }
        
        .skill-name {
            position: relative;
            cursor: help;
        }
        
        .info-icon {
            font-size: 0.8em;
            color: #007bff;
            margin-left: 5px;
            vertical-align: super;
        }
        
        /* Mejorar la visualización en mobile */
        @media (max-width: 768px) {
            .skill-item {
                padding: 10px 0;
                border-bottom: 1px solid #eee;
            }
            
            .skill-rating {
                display: flex;
                flex-wrap: wrap;
                margin-top: 5px;
            }
            
            .skill-rating label {
                margin-right: 10px;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body class="reg-hab">
    <div class="contenedor-hab">
        <div class="logo">
            <img src="../../imagenes/logo.png" alt="Logo ChambaNet">
        </div>
        
        <h1>Registro de Candidato: Paso 3</h1>
        <h2>Conocimientos y Habilidades</h2>
        
        <?php
        // Verificar si hay mensajes de error
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        
        <div class="content">
            <p>Evalúa tus conocimientos y habilidades en tecnologías de redes. Esto nos ayudará a encontrar las mejores vacantes para tu perfil.</p>
            
            <form action="../../controllers/registro_candidato_unificado_controller.php" method="POST">
                <input type="hidden" name="paso" value="3">
                
                <?php 
                // Ordenar categorías alfabéticamente para mejor presentación
                ksort($habilidades);
                
                foreach ($habilidades as $categoria => $tecnologias): 
                    // Saltarse categorías vacías
                    if (empty($tecnologias)) continue;
                ?>
                    <div class="section skills-category">
                        <h3><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h3>
                        
                        <div class="skills-grid">
                            <?php 
                            // Ordenar alfabéticamente las habilidades dentro de cada categoría
                            usort($tecnologias, function($a, $b) {
                                $nombreA = is_object($a) ? ($a->nombre ?? '') : (is_array($a) ? ($a['nombre'] ?? '') : $a);
                                $nombreB = is_object($b) ? ($b->nombre ?? '') : (is_array($b) ? ($b['nombre'] ?? '') : $b);
                                return strcmp($nombreA, $nombreB);
                            });
                            
                            foreach ($tecnologias as $habilidad): 
                                // Manejar diferentes formatos posibles
                                if (is_object($habilidad)) {
                                    $tecnologia = $habilidad->nombre ?? null;
                                    $descripcion = $habilidad->descripcion ?? '';
                                } elseif (is_array($habilidad)) {
                                    $tecnologia = $habilidad['nombre'] ?? $habilidad[0] ?? null;
                                    $descripcion = $habilidad['descripcion'] ?? '';
                                } else {
                                    $tecnologia = $habilidad;
                                    $descripcion = '';
                                }
                                
                                // Si no pudimos obtener la tecnología, continuar con el siguiente
                                if (!$tecnologia) continue;
                                
                                $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologia));
                            ?>
                                <div class="skill-item">
                                    <div class="skill-name" <?php if (!empty($descripcion)): ?>title="<?php echo htmlspecialchars($descripcion); ?>"<?php endif; ?>>
                                        <?php echo htmlspecialchars($tecnologia); ?>
                                        <?php if (!empty($descripcion)): ?>
                                            <span class="info-icon">ℹ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="skill-rating">
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="malo" <?php echo (isset($habilidades_guardadas[$tecnologia]) && $habilidades_guardadas[$tecnologia] === 'malo') ? 'checked' : ''; ?>> 
                                            Básico
                                        </label>
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="regular" <?php echo (isset($habilidades_guardadas[$tecnologia]) && $habilidades_guardadas[$tecnologia] === 'regular') ? 'checked' : ''; ?>> 
                                            Intermedio
                                        </label>
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="bueno" <?php echo (isset($habilidades_guardadas[$tecnologia]) && $habilidades_guardadas[$tecnologia] === 'bueno') ? 'checked' : ''; ?>> 
                                            Avanzado
                                        </label>
                                        <label>
                                            <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="ninguno" <?php echo (!isset($habilidades_guardadas[$tecnologia]) || $habilidades_guardadas[$tecnologia] === 'ninguno') ? 'checked' : ''; ?>> 
                                            No aplica
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="form-buttons">
                    <button type="submit">Completar Registro</button>
                    <p class="info-text">Al hacer clic en "Completar Registro" se creará tu cuenta y se guardará toda la información proporcionada.</p>
                </div>
            </form>
        </div>
        
        <a href="datosEyP_candidato.php" class="enlaces">Volver al paso anterior</a>
    </div>   
</body>
</html>
