<?php
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Publicar Vacante - ChambaNet</title>
    <!-- Solo cargamos los CSS necesarios, NO interfaz_iniciar_usuario.css -->
    <link rel="stylesheet" href="../../estilo/formularios.css">
    <link rel="stylesheet" href="../../estilo/conocimientos.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    * {
        box-sizing: border-box;
    }
    
    html, body {
        height: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        background: transparent;
        font-family: Arial, sans-serif;
    }
    
    body {
        display: block; /* NO flex */
        padding: 0;
        margin: 0;
    }
    
    .formulario-vacante {
        width: 100%;
        margin: 0;
        background: #fff;
        padding: 20px;
        box-sizing: border-box;
        min-height: 100vh;
    }
    
    .section {
        margin-bottom: 20px;
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .section h3 {
        color: #d63d3d;
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .form-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .form-row .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .skills-category {
        margin-bottom: 25px;
    }
    
    .skills-category h4 {
        color: #d63d3d;
        margin-bottom: 15px;
        text-transform: capitalize;
    }
    
    .skills-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
    
    .skill-item {
        background-color: #fff;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .skill-name {
        font-weight: bold;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }
    
    .skill-name input[type="checkbox"] {
        margin-right: 10px;
    }
    
    .skill-level {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }
    
    .skill-level label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-size: 12px;
    }
    
    .skill-level input[type="radio"] {
        margin-right: 5px;
    }
    
    .form-actions {
        text-align: center;
        margin-top: 30px;
        padding: 20px 0;
    }
    
    .btn-submit {
        background: linear-gradient(to right, #d63d3d, #e44c4c);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 25px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn-submit:hover {
        background: linear-gradient(to right, #c02c2c, #cc4444);
    }
    
    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }
    
    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }
    
    @media (max-width: 768px) {
        .formulario-vacante {
            padding: 15px;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .skills-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>

    <div class="formulario-vacante">
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
                    <textarea id="descripcion" name="descripcion" placeholder="Describe brevemente la vacante..."
                        required></textarea>
                </div>

                <div class="form-group">
                    <label for="responsabilidades">Responsabilidades*</label>
                    <textarea id="responsabilidades" name="responsabilidades"
                        placeholder="Lista las responsabilidades del puesto..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="requisitos">Requisitos generales*</label>
                    <textarea id="requisitos" name="requisitos" placeholder="Describe los requisitos generales..."
                        required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="salario">Salario mensual (MXN)*</label>
                        <input type="number" id="salario" name="salario" min="0" step="1000" placeholder="Ej. 25000"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="anios_experiencia">Años de experiencia requeridos*</label>
                        <input type="number" id="anios_experiencia" name="anios_experiencia" min="0" placeholder="Ej. 2"
                            required>
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
                                        <input type="checkbox" id="req_<?php echo $tecnologiaKey; ?>"
                                            name="req_<?php echo $tecnologiaKey; ?>">
                                        <label
                                            for="req_<?php echo $tecnologiaKey; ?>"><?php echo htmlspecialchars($tecnologia); ?></label>
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

            <div class="form-actions">
                <button type="submit" class="btn-submit">Publicar Vacante</button>
            </div>
        </form>
    </div>
</body>

</html>