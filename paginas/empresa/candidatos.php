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
                <div class="filter-chips">
                    <button class="filter-chip active" >Todos (23)</button>
                    <button class="filter-chip">Pendientes (15)</button>
                    <button class="filter-chip">Revisados (8)</button>
                </div>
            </div>

            <div class="candidates-list">
                <!-- Candidato 1 - Juan Pérez -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <div class="candidate-avatar">JD</div>
                        <div class="candidate-details">
                            <h4>Juan Pérez</h4>
                            <span class="candidate-title">Frontend Developer</span>
                            <div class="match-score">Match: 89%</div>
                        </div>
                    </div>
                    <div class="candidate-status">
                        <span class="status-badge pending">Pendiente</span>
                    </div>
                    
                </div>

                <!-- Candidato 2 - María García -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <div class="candidate-avatar">MG</div>
                        <div class="candidate-details">
                            <h4>María García</h4>
                            <span class="candidate-title">React Developer</span>
                            <div class="match-score">Match: 92%</div>
                        </div>
                    </div>
                    <div class="candidate-status">
                        <span class="status-badge pending">Pendiente</span>
                    </div>
                   
                </div>

                <!-- Candidato 3 - Carlos López -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <div class="candidate-avatar">CL</div>
                        <div class="candidate-details">
                            <h4>Carlos López</h4>
                            <span class="candidate-title">Full Stack Developer</span>
                            <div class="match-score">Match: 85%</div>
                        </div>
                    </div>
                    <div class="candidate-status">
                        <span class="status-badge pending">Pendiente</span>
                    </div>
                    
                </div>

                <!-- Candidato 4 - Ana Martínez -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <div class="candidate-avatar">AM</div>
                        <div class="candidate-details">
                            <h4>Ana Martínez</h4>
                            <span class="candidate-title">UI/UX Developer</span>
                            <div class="match-score">Match: 78%</div>
                        </div>
                    </div>
                    <div class="candidate-status">
                        <span class="status-badge pending">Pendiente</span>
                    </div>
                    
                </div>

                <!-- Candidato 5 - Roberto Silva -->
                <div class="candidate-card">
                    <div class="candidate-info">
                        <div class="candidate-avatar">RS</div>
                        <div class="candidate-details">
                            <h4>Roberto Silva</h4>
                            <span class="candidate-title">JavaScript Developer</span>
                            <div class="match-score">Match: 81%</div>
                        </div>
                    </div>
                    <div class="candidate-status">
                        <span class="status-badge pending">Pendiente</span>
                    </div>
                    
                </div>
            </div>
        </div>
<!-- Action Buttons -->
    <div class="action-buttons">
        <button type="button" class="btn btn-outline">
            <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn btn-outline">
            <i class="fas fa-save"></i> Guardar Cambios
        </button>
        <button type="button" class="btn btn-primary">
            <i class="fas fa-check"></i> Aplicar Cambios
        </button>
    </div>
</div>





    </div>

    

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Aquí puedes agregar lógica para cambiar el contenido según la pestaña
                console.log('Tab seleccionada:', this.getAttribute('data-tab'));
            });
        });

        // Filter chips functionality
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function () {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                // Aquí puedes agregar lógica para filtrar candidatos
                console.log('Filtro seleccionado:', this.textContent);
            });
        });

        // Candidate actions
        document.querySelectorAll('.candidate-actions .btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const action = this.textContent.trim();
                const candidateName = this.closest('.candidate-card').querySelector('h4').textContent;

                if (action === 'Ver perfil') {
                    console.log('Ver perfil de:', candidateName);
                    // Aquí puedes redirigir al perfil del candidato
                } else if (action === 'Contactar') {
                    console.log('Contactar a:', candidateName);
                    // Aquí puedes abrir modal de contacto o redirigir
                }
            });
        });
    </script>
</body>

</html>