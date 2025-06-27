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
                    
                    // Si no pudimos obtener el nombre de la tecnología, continuar con el siguiente
                    if (!$tecnologiaNombre) continue;
                    
                    $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologiaNombre));
                    
                    // Si se requiere esta tecnología
                    if (isset($_POST['req_' . $tecnologiaKey]) && $_POST['req_' . $tecnologiaKey] == 'on') {
                        $nivel = isset($_POST['nivel_' . $tecnologiaKey]) ? filter_input(INPUT_POST, 'nivel_' . $tecnologiaKey, FILTER_SANITIZE_STRING) : 'intermedio';
                        
                        // Validar nivel según el constraint de la base de datos
                        if (!in_array($nivel, ['principiante', 'intermedio', 'avanzado', 'experto'])) {
                            $nivel = 'intermedio';
                        }
                        
                        // Preparar datos para insertar
                        $requisitoData = [
                            'vacante_id' => $vacanteId,
                            'tecnologia' => $tecnologiaNombre,
                            'nivel_requerido' => $nivel
                        ];
                        
                        // Si tenemos el ID de la habilidad, guardarlo también
                        if ($tecnologiaId !== null) {
                            $requisitoData['habilidad_id'] = $tecnologiaId;
                        }
                        
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
        
        /* Mejoras para la visualización en mobile */
        @media (max-width: 768px) {
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
                    echo htmlspecialchars($nombreCompleto);
                ?></p>
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
                    
                    <?php 
                    // Ordenar categorías alfabéticamente para mejor presentación
                    ksort($habilidades);
                    
                    foreach ($habilidades as $categoria => $tecnologias): 
                        // Saltarse categorías vacías
                        if (empty($tecnologias)) continue;
                    ?>
                        <div class="skills-category">
                            <h4><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h4>
                            
                            <div class="skills-grid">
                                <?php 
                                // Ordenar alfabéticamente las habilidades dentro de cada categoría
                                usort($tecnologias, function($a, $b) {
                                    $nombreA = is_object($a) ? ($a->nombre ?? '') : (is_array($a) ? ($a['nombre'] ?? '') : $a);
                                    $nombreB = is_object($b) ? ($b->nombre ?? '') : (is_array($b) ? ($b['nombre'] ?? '') : $b);
                                    return strcmp($nombreA, $nombreB);
                                });
                                
                                foreach ($tecnologias as $tecnologia): 
                                    // Manejar diferentes formatos posibles
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
                                    
                                    // Si no pudimos obtener la tecnología, continuar con el siguiente
                                    if (!$tecnologiaNombre) continue;
                                    
                                    $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologiaNombre));
                                ?>
                                    <div class="skill-item">
                                        <div class="skill-name" <?php if (!empty($descripcion)): ?>title="<?php echo htmlspecialchars($descripcion); ?>"<?php endif; ?>>
                                            <input type="checkbox" id="req_<?php echo $tecnologiaKey; ?>" name="req_<?php echo $tecnologiaKey; ?>">
                                            <label for="req_<?php echo $tecnologiaKey; ?>"><?php echo htmlspecialchars($tecnologiaNombre); ?></label>
                                            <?php if (!empty($descripcion)): ?>
                                                <span class="info-icon">ℹ</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="skill-level">
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="principiante" checked> 
                                                Básico
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="intermedio"> 
                                                Intermedio
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="avanzado"> 
                                                Avanzado
                                            </label>
                                            <label>
                                                <input type="radio" name="nivel_<?php echo $tecnologiaKey; ?>" value="experto"> 
                                                Experto
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit">Publicar Vacante</button>
                <button type="button" onclick="window.location.href='home_empresa.php'">Volver</button>
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
        });
    </script>
</body>
</html>
