<?php
// candidatos.php - Página de gestión de candidatos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatos - Senior Developer Frontend</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset y Variables */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-red: #d32f2f;
            --success-green: #10B981;
            --warning-orange: #F59E0B;
            --gray-light: #fff;
            --gray-medium: #b0b0b0;
            --gray-dark: #222;
            --red-light: #ffebee;
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-dark);
            background-color: #f8f9fa;
        }

        /* Header */
        .update-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: var(--shadow-sm);
        }

        .breadcrumb {
            color: var(--gray-medium);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-sm);
            padding: var(--spacing-lg) var(--spacing-xl) 0;
        }

        .breadcrumb i {
            margin: 0 var(--spacing-xs);
            font-size: 0.75rem;
        }

        .job-status-bar {
            padding: 0 var(--spacing-xl) var(--spacing-lg);
        }

        .job-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gray-dark);
        }

        .status-indicators {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .status-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .metric {
            color: var(--gray-medium);
            font-size: 0.875rem;
        }

        /* Tabs */
        .tabs-container {
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabs-nav {
            display: flex;
            padding: 0 var(--spacing-xl);
        }

        .tab-btn {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            background: none;
            color: var(--gray-medium);
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .tab-btn.active {
            color: var(--primary-red);
            border-bottom-color: var(--primary-red);
        }

        .tab-btn:hover {
            color: var(--primary-red);
            background: var(--red-light);
        }

        /* Candidates Section */
        .candidates-section {
            background: white;
            margin: var(--spacing-xl);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            padding: var(--spacing-lg);
        }

        .candidates-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid #e2e8f0;
        }

        .filter-chips {
            display: flex;
            gap: var(--spacing-sm);
        }

        .filter-chip {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .filter-chip.active {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }

        .filter-chip:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }

        .bulk-actions .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid #e2e8f0;
            background: white;
            color: var(--gray-medium);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .bulk-actions .btn:hover {
            background: var(--red-light);
            color: var(--primary-red);
            border-color: var(--primary-red);
        }

        /* Candidate Cards */
        .candidates-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .candidate-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-md);
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            transition: all 0.2s;
        }

        .candidate-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-red);
        }

        .candidate-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .candidate-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-red);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .candidate-details h4 {
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
            color: var(--gray-dark);
        }

        .candidate-title {
            color: var(--gray-medium);
            font-size: 0.875rem;
        }

        .match-score {
            color: var(--success-green);
            font-weight: 600;
            font-size: 0.875rem;
            margin-top: var(--spacing-xs);
        }

        .candidate-status {
            display: flex;
            align-items: center;
        }

        .candidate-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            text-decoration: none;
            border: 1px solid transparent;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }

        .btn-primary:hover {
            background: #b71c1c;
            border-color: #b71c1c;
        }

        .btn-outline {
            background: white;
            color: var(--gray-medium);
            border-color: #e2e8f0;
        }

        .btn-outline:hover {
            background: var(--red-light);
            color: var(--primary-red);
            border-color: var(--primary-red);
        }

        .btn-sm {
            padding: var(--spacing-xs) var(--spacing-sm);
            font-size: 0.8125rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding: var(--spacing-xl);
            background: white;
            border-top: 1px solid #e2e8f0;
            position: sticky;
            bottom: 0;
            margin-top: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .candidates-filters {
                flex-direction: column;
                gap: var(--spacing-md);
                align-items: stretch;
            }

            .filter-chips {
                flex-wrap: wrap;
            }

            .candidate-card {
                flex-direction: column;
                gap: var(--spacing-md);
                align-items: stretch;
            }

            .candidate-info {
                justify-content: center;
                text-align: center;
            }

            .job-title {
                flex-direction: column;
                gap: var(--spacing-md);
                align-items: flex-start;
            }

            .status-indicators {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Header con contexto -->
    <header class="update-header">
        <div class="breadcrumb">
            <span>Inicio</span> <i class="fas fa-chevron-right"></i>
            <span>Vacantes</span> <i class="fas fa-chevron-right"></i>
            <span>Senior Developer</span>
        </div>
        <div class="job-status-bar">
            <div class="job-title">
                <h1>Senior Developer - Frontend</h1>
                <div class="status-indicators">
                    <span class="status-badge active">🟢 Activa</span>
                    <span class="metric">23 candidatos</span>
                    <span class="metric">Modificado: hace 2h</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Tabs de navegación -->
    <div class="tabs-container">
        <nav class="tabs-nav">
            <button class="tab-btn" data-tab="info">Información</button>
            <button class="tab-btn active" data-tab="candidates">Candidatos</button>
            <button class="tab-btn" data-tab="history">Historial</button>
            <button class="tab-btn" data-tab="config">Configuración</button>
        </nav>
    </div>

    <!-- Candidates Section -->
    <div class="candidates-section">
        <div class="candidates-filters">
            <div class="filter-chips">
                <button class="filter-chip active">Todos (23)</button>
                <button class="filter-chip">Pendientes (15)</button>
                <button class="filter-chip">Revisados (8)</button>
                <button class="filter-chip">Contratados (0)</button>
            </div>
            <div class="bulk-actions">
                <button class="btn btn-outline btn-sm">Acciones en lote</button>
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
                <div class="candidate-actions">
                    <button class="btn btn-sm btn-outline">Ver perfil</button>
                    <button class="btn btn-sm btn-primary">Contactar</button>
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
                <div class="candidate-actions">
                    <button class="btn btn-sm btn-outline">Ver perfil</button>
                    <button class="btn btn-sm btn-primary">Contactar</button>
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
                <div class="candidate-actions">
                    <button class="btn btn-sm btn-outline">Ver perfil</button>
                    <button class="btn btn-sm btn-primary">Contactar</button>
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
                <div class="candidate-actions">
                    <button class="btn btn-sm btn-outline">Ver perfil</button>
                    <button class="btn btn-sm btn-primary">Contactar</button>
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
                <div class="candidate-actions">
                    <button class="btn btn-sm btn-outline">Ver perfil</button>
                    <button class="btn btn-sm btn-primary">Contactar</button>
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

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Aquí puedes agregar lógica para cambiar el contenido según la pestaña
                console.log('Tab seleccionada:', this.getAttribute('data-tab'));
            });
        });

        // Filter chips functionality
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                // Aquí puedes agregar lógica para filtrar candidatos
                console.log('Filtro seleccionado:', this.textContent);
            });
        });

        // Candidate actions
        document.querySelectorAll('.candidate-actions .btn').forEach(btn => {
            btn.addEventListener('click', function() {
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