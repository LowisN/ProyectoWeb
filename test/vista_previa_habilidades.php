<?php
// Este script simula la visualización del formulario de habilidades
require_once 'config/supabase.php';
require_once 'config/SupabaseClient.php';
require_once 'models/habilidades.php';

session_start();
$habilidades_guardadas = isset($_SESSION['registro_candidato']['habilidades']) ? 
    $_SESSION['registro_candidato']['habilidades'] : [];

// Obtener las habilidades
$habilidadesManager = new Habilidades();
$habilidades = $habilidadesManager->obtenerHabilidadesPorCategoria();

// Verificar que tenemos datos
if (empty($habilidades)) {
    die("No se pudieron cargar las habilidades. Comprueba la conexión a la base de datos.");
}

// Contar categorías y habilidades
$total_categorias = count($habilidades);
$total_habilidades = 0;
foreach ($habilidades as $categoria => $techs) {
    $total_habilidades += count($techs);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Visualización de Habilidades</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #333;
        }
        .stats {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .category {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .category h3 {
            background-color: #f9f9f9;
            padding: 10px;
            border-left: 4px solid #007bff;
            text-transform: capitalize;
            margin-top: 0;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .skill-item {
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .skill-name {
            font-weight: bold;
        }
        .skill-rating {
            margin-top: 8px;
        }
        .skill-rating label {
            margin-right: 10px;
            font-size: 14px;
        }
        .info-icon {
            font-size: 0.8em;
            color: #007bff;
            cursor: help;
        }
        .description {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Prueba de Visualización de Habilidades</h1>
    
    <div class="stats">
        <p><strong>Total de categorías:</strong> <?php echo $total_categorias; ?></p>
        <p><strong>Total de habilidades:</strong> <?php echo $total_habilidades; ?></p>
    </div>
    
    <h2>Previsualización del formulario</h2>
    
    <form>
        <?php 
        // Ordenar categorías alfabéticamente
        ksort($habilidades);
        
        foreach ($habilidades as $categoria => $tecnologias): 
            // Saltarse categorías vacías
            if (empty($tecnologias)) continue;
        ?>
            <div class="category">
                <h3><?php echo ucfirst(str_replace('_', ' ', $categoria)); ?></h3>
                
                <div class="skills-grid">
                    <?php 
                    // Ordenar habilidades alfabéticamente
                    usort($tecnologias, function($a, $b) {
                        $nombreA = is_object($a) ? ($a->nombre ?? '') : (is_array($a) ? ($a['nombre'] ?? '') : $a);
                        $nombreB = is_object($b) ? ($b->nombre ?? '') : (is_array($b) ? ($b['nombre'] ?? '') : $b);
                        return strcmp($nombreA, $nombreB);
                    });
                    
                    foreach ($tecnologias as $habilidad): 
                        // Extraer datos de la habilidad
                        if (is_object($habilidad)) {
                            $tecnologia = $habilidad->nombre ?? null;
                            $descripcion = $habilidad->descripcion ?? '';
                            $id = $habilidad->id ?? '';
                        } elseif (is_array($habilidad)) {
                            $tecnologia = $habilidad['nombre'] ?? $habilidad[0] ?? null;
                            $descripcion = $habilidad['descripcion'] ?? '';
                            $id = $habilidad['id'] ?? '';
                        } else {
                            $tecnologia = $habilidad;
                            $descripcion = '';
                            $id = '';
                        }
                        
                        // Si no hay nombre de tecnología, continuar
                        if (!$tecnologia) continue;
                        
                        // Crear clave única para el formulario
                        $tecnologiaKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($tecnologia));
                    ?>
                        <div class="skill-item">
                            <div class="skill-name">
                                <?php echo htmlspecialchars($tecnologia); ?>
                                <?php if (!empty($descripcion)): ?>
                                    <span class="info-icon" title="<?php echo htmlspecialchars($descripcion); ?>">ℹ</span>
                                <?php endif; ?>
                                <span style="color: #999; font-size: 12px;">(ID: <?php echo $id; ?>)</span>
                            </div>
                            
                            <?php if (!empty($descripcion)): ?>
                                <div class="description"><?php echo htmlspecialchars(substr($descripcion, 0, 100)); ?><?php echo strlen($descripcion) > 100 ? '...' : ''; ?></div>
                            <?php endif; ?>
                            
                            <div class="skill-rating">
                                <label>
                                    <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="malo"> 
                                    Básico
                                </label>
                                <label>
                                    <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="regular"> 
                                    Intermedio
                                </label>
                                <label>
                                    <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="bueno"> 
                                    Avanzado
                                </label>
                                <label>
                                    <input type="radio" name="<?php echo $tecnologiaKey; ?>" value="ninguno" checked> 
                                    No aplica
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 20px;">
            <button type="submit" disabled>Simulación - No envía datos</button>
        </div>
    </form>
    
    <p><a href="index.php">Volver al inicio</a></p>
</body>
</html>
