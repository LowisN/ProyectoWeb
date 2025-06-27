<?php
// Diagnóstico del cálculo de porcentaje de match entre candidatos y vacantes
session_start();
require_once 'config/supabase.php';

// Función para calcular el porcentaje de match (copia de la implementada en candidatos.php)
function calcularMatchPorcentaje($candidatoId, $habilidadesVacante) {
    if (empty($habilidadesVacante)) {
        return ['porcentaje' => 0, 'detalles' => 'No hay habilidades requeridas'];
    }
    
    // Obtener habilidades del candidato
    $habilidadesCandidato = supabaseFetch('candidato_habilidades', '*', ['candidato_id' => $candidatoId]);
    
    if (isset($habilidadesCandidato['error'])) {
        return ['porcentaje' => 0, 'detalles' => 'Error al obtener habilidades: ' . json_encode($habilidadesCandidato['error'])];
    }
    
    if (!is_array($habilidadesCandidato) || empty($habilidadesCandidato)) {
        return ['porcentaje' => 0, 'detalles' => 'El candidato no tiene habilidades registradas'];
    }
    
    // Contador de coincidencias
    $coincidenciasObligatorias = 0;
    $totalObligatorios = 0;
    $coincidenciasOpcionales = 0;
    $totalOpcionales = 0;
    
    // Mapa de nivel a valor numérico para comparaciones
    $nivelValor = [
        'principiante' => 1,
        'intermedio' => 2,
        'avanzado' => 3,
        'experto' => 4
    ];
    
    // Crear un mapa de habilidades del candidato para búsqueda rápida
    $mapaCandidato = [];
    $detallesCandidato = [];
    foreach ($habilidadesCandidato as $habilidad) {
        $mapaCandidato[$habilidad['habilidad_id']] = [
            'nivel' => $habilidad['nivel'],
            'anios_experiencia' => $habilidad['anios_experiencia']
        ];
        
        // Obtener nombre de la habilidad para mostrar en el diagnóstico
        $habilidadInfo = supabaseFetch('habilidades', 'nombre', ['id' => $habilidad['habilidad_id']]);
        $nombreHabilidad = !empty($habilidadInfo) && isset($habilidadInfo[0]['nombre']) ? 
                          $habilidadInfo[0]['nombre'] : "Habilidad #" . $habilidad['habilidad_id'];
        
        $detallesCandidato[] = [
            'id' => $habilidad['habilidad_id'],
            'nombre' => $nombreHabilidad,
            'nivel' => $habilidad['nivel'],
            'valor_nivel' => $nivelValor[$habilidad['nivel']],
            'anios' => $habilidad['anios_experiencia']
        ];
    }
    
    // Evaluar cada habilidad requerida
    $detallesVacante = [];
    $coincidenciasDetalle = [];
    
    foreach ($habilidadesVacante as $habilidadRequerida) {
        $esObligatorio = (bool)($habilidadRequerida['obligatorio'] ?? true);
        $habilidadId = (int)($habilidadRequerida['habilidad_id'] ?? 0);
        $nivelRequerido = $habilidadRequerida['nivel_requerido'] ?? 'principiante';
        
        // Obtener nombre de la habilidad para mostrar en el diagnóstico
        $habilidadInfo = supabaseFetch('habilidades', 'nombre', ['id' => $habilidadId]);
        $nombreHabilidad = !empty($habilidadInfo) && isset($habilidadInfo[0]['nombre']) ? 
                          $habilidadInfo[0]['nombre'] : "Habilidad #" . $habilidadId;
        
        // Incrementar contadores de totales
        if ($esObligatorio) {
            $totalObligatorios++;
        } else {
            $totalOpcionales++;
        }
        
        $detallesVacante[] = [
            'id' => $habilidadId,
            'nombre' => $nombreHabilidad,
            'nivel' => $nivelRequerido,
            'valor_nivel' => $nivelValor[$nivelRequerido],
            'obligatorio' => $esObligatorio
        ];
        
        // Verificar si el candidato tiene la habilidad
        $coincide = false;
        $nivelCoincide = false;
        $detalle = '';
        
        if (isset($mapaCandidato[$habilidadId])) {
            $coincide = true;
            $nivelCandidato = $mapaCandidato[$habilidadId]['nivel'];
            
            // Verificar si el nivel del candidato cumple con el requerido
            if ($nivelValor[$nivelCandidato] >= $nivelValor[$nivelRequerido]) {
                $nivelCoincide = true;
                if ($esObligatorio) {
                    $coincidenciasObligatorias++;
                    $detalle = "Obligatoria: CUMPLE. Nivel requerido: $nivelRequerido, Nivel candidato: $nivelCandidato";
                } else {
                    $coincidenciasOpcionales++;
                    $detalle = "Opcional: CUMPLE. Nivel requerido: $nivelRequerido, Nivel candidato: $nivelCandidato";
                }
            } else {
                $detalle = "NO CUMPLE NIVEL. Requerido: $nivelRequerido (${nivelValor[$nivelRequerido]}), Candidato: $nivelCandidato (${nivelValor[$nivelCandidato]})";
            }
        } else {
            $detalle = $esObligatorio ? 
                     "Obligatoria: NO TIENE la habilidad" : 
                     "Opcional: NO TIENE la habilidad";
        }
        
        $coincidenciasDetalle[] = [
            'habilidad_id' => $habilidadId,
            'nombre' => $nombreHabilidad,
            'obligatorio' => $esObligatorio,
            'tiene_habilidad' => $coincide,
            'nivel_coincide' => $nivelCoincide,
            'detalle' => $detalle
        ];
    }
    
    // Calcular porcentaje final de match
    $porcentajeObligatorios = $totalObligatorios > 0 ? ($coincidenciasObligatorias / $totalObligatorios) * 70 : 0;
    $porcentajeOpcionales = $totalOpcionales > 0 ? ($coincidenciasOpcionales / $totalOpcionales) * 30 : 0;
    
    // Si no hay opcionales, el 100% depende de los obligatorios
    if ($totalOpcionales == 0) {
        $porcentajeObligatorios = $totalObligatorios > 0 ? ($coincidenciasObligatorias / $totalObligatorios) * 100 : 0;
    }
    
    $porcentajeFinal = round($porcentajeObligatorios + $porcentajeOpcionales);
    
    // Devolver resultados detallados para diagnóstico
    return [
        'porcentaje' => $porcentajeFinal,
        'obligatorios_total' => $totalObligatorios,
        'obligatorios_match' => $coincidenciasObligatorias,
        'porcentaje_obligatorios' => round($porcentajeObligatorios, 2),
        'opcionales_total' => $totalOpcionales,
        'opcionales_match' => $coincidenciasOpcionales,
        'porcentaje_opcionales' => round($porcentajeOpcionales, 2),
        'habilidades_candidato' => $detallesCandidato,
        'habilidades_vacante' => $detallesVacante,
        'coincidencias_detalle' => $coincidenciasDetalle
    ];
}

// Verificar parámetros
$candidatoId = isset($_GET['candidato_id']) ? (int)$_GET['candidato_id'] : 0;
$vacanteId = isset($_GET['vacante_id']) ? (int)$_GET['vacante_id'] : 0;

// Verificar si es una petición AJAX
$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

// Resultados
$resultados = [
    'candidato_id' => $candidatoId,
    'vacante_id' => $vacanteId,
    'match' => null,
    'error' => null
];

// Validar parámetros
if ($candidatoId <= 0 || $vacanteId <= 0) {
    $resultados['error'] = 'Se requieren IDs válidos para candidato y vacante';
} else {
    // Obtener habilidades de la vacante
    $query = "/rest/v1/vacante_habilidades?select=id,habilidad_id,nivel_requerido,obligatorio&vacante_id=eq.$vacanteId";
    $habilidadesVacante = supabaseRequest($query);
    
    if (isset($habilidadesVacante['error']) || !is_array($habilidadesVacante)) {
        $resultados['error'] = 'Error al obtener habilidades de la vacante: ' . json_encode($habilidadesVacante);
    } else if (empty($habilidadesVacante)) {
        $resultados['error'] = 'La vacante no tiene habilidades registradas';
    } else {
        // Calcular match
        $resultados['match'] = calcularMatchPorcentaje($candidatoId, $habilidadesVacante);
    }
}

// Si es una petición AJAX, devolver los resultados en formato JSON
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode($resultados);
    exit;
}

// Obtener lista de candidatos y vacantes para selección
$candidatos = supabaseFetch('candidatos', 'id,perfil_id,titulo');
$vacantes = supabaseFetch('vacantes', 'id,titulo');

// Convertir a array si no lo es
if (!is_array($candidatos) || isset($candidatos['error'])) {
    $candidatos = [];
}
if (!is_array($vacantes) || isset($vacantes['error'])) {
    $vacantes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Match de Candidatos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #0066cc;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select {
            width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #0066cc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #004c99;
        }
        .results {
            margin-top: 30px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .match-percentage {
            font-size: 2em;
            font-weight: bold;
            color: #0066cc;
            margin: 15px 0;
        }
        .high-match {
            color: #28a745;
        }
        .medium-match {
            color: #ffc107;
        }
        .low-match {
            color: #dc3545;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #0066cc;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .detail-section {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .success {
            color: #28a745;
        }
        .warning {
            color: #ffc107;
        }
        .danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Match entre Candidatos y Vacantes</h1>
        <p>Esta herramienta permite diagnosticar cómo se calcula el porcentaje de match entre un candidato y una vacante.</p>
        
        <form method="GET" action="">
            <div class="form-group">
                <label for="candidato_id">Seleccionar Candidato:</label>
                <select name="candidato_id" id="candidato_id" required>
                    <option value="">-- Seleccione un candidato --</option>
                    <?php foreach ($candidatos as $candidato): ?>
                        <?php 
                            // Obtener nombre del candidato
                            $perfilId = $candidato['perfil_id'] ?? 0;
                            $perfil = $perfilId > 0 ? supabaseFetch('perfiles', 'user_id', ['id' => $perfilId]) : null;
                            $userId = !empty($perfil) && isset($perfil[0]['user_id']) ? $perfil[0]['user_id'] : '';
                            
                            $nombreCandidato = "Candidato #" . $candidato['id'];
                            if ($userId) {
                                $userAuthData = supabaseRequest("/auth/v1/user/" . $userId);
                                if (isset($userAuthData['user_metadata']) && isset($userAuthData['user_metadata']['nombre'])) {
                                    $nombreCandidato = $userAuthData['user_metadata']['nombre'] . ' ' . 
                                        ($userAuthData['user_metadata']['apellidos'] ?? '');
                                } else if (isset($userAuthData['email'])) {
                                    $nombreCandidato = $userAuthData['email'];
                                }
                            }
                        ?>
                        <option value="<?= $candidato['id'] ?>" <?= $candidatoId == $candidato['id'] ? 'selected' : '' ?>><?= htmlspecialchars($nombreCandidato . ' - ' . ($candidato['titulo'] ?? 'Sin título')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vacante_id">Seleccionar Vacante:</label>
                <select name="vacante_id" id="vacante_id" required>
                    <option value="">-- Seleccione una vacante --</option>
                    <?php foreach ($vacantes as $vacante): ?>
                        <option value="<?= $vacante['id'] ?>" <?= $vacanteId == $vacante['id'] ? 'selected' : '' ?>><?= htmlspecialchars($vacante['titulo'] ?? 'Vacante sin título') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit">Calcular Match</button>
        </form>
        
        <?php if (!empty($resultados['error'])): ?>
            <div class="results">
                <h2 class="danger">Error</h2>
                <p><?= htmlspecialchars($resultados['error']) ?></p>
            </div>
        <?php elseif (!empty($resultados['match'])): ?>
            <div class="results">
                <h2>Resultados del Match</h2>
                
                <?php 
                    $matchClass = '';
                    $porcentaje = $resultados['match']['porcentaje'];
                    if ($porcentaje >= 80) {
                        $matchClass = 'high-match';
                    } elseif ($porcentaje >= 50) {
                        $matchClass = 'medium-match';
                    } else {
                        $matchClass = 'low-match';
                    }
                ?>
                
                <div class="match-percentage <?= $matchClass ?>">
                    <?= $porcentaje ?>% de Match
                </div>
                
                <h3>Resumen</h3>
                <ul>
                    <li><strong>Habilidades obligatorias:</strong> <?= $resultados['match']['obligatorios_match'] ?> de <?= $resultados['match']['obligatorios_total'] ?> (<?= $resultados['match']['porcentaje_obligatorios'] ?>%)</li>
                    <li><strong>Habilidades opcionales:</strong> <?= $resultados['match']['opcionales_match'] ?> de <?= $resultados['match']['opcionales_total'] ?> (<?= $resultados['match']['porcentaje_opcionales'] ?>%)</li>
                </ul>
                
                <div class="detail-section">
                    <h3>Habilidades del Candidato</h3>
                    <?php if (empty($resultados['match']['habilidades_candidato'])): ?>
                        <p class="warning">El candidato no tiene habilidades registradas.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Habilidad</th>
                                    <th>Nivel</th>
                                    <th>Valor Nivel</th>
                                    <th>Años de Experiencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados['match']['habilidades_candidato'] as $hab): ?>
                                    <tr>
                                        <td><?= $hab['id'] ?></td>
                                        <td><?= htmlspecialchars($hab['nombre']) ?></td>
                                        <td><?= htmlspecialchars($hab['nivel']) ?></td>
                                        <td><?= $hab['valor_nivel'] ?></td>
                                        <td><?= $hab['anios'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="detail-section">
                    <h3>Habilidades Requeridas por la Vacante</h3>
                    <?php if (empty($resultados['match']['habilidades_vacante'])): ?>
                        <p class="warning">La vacante no tiene habilidades registradas.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Habilidad</th>
                                    <th>Nivel Requerido</th>
                                    <th>Valor Nivel</th>
                                    <th>Obligatorio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados['match']['habilidades_vacante'] as $hab): ?>
                                    <tr>
                                        <td><?= $hab['id'] ?></td>
                                        <td><?= htmlspecialchars($hab['nombre']) ?></td>
                                        <td><?= htmlspecialchars($hab['nivel']) ?></td>
                                        <td><?= $hab['valor_nivel'] ?></td>
                                        <td><?= $hab['obligatorio'] ? 'Sí' : 'No' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="detail-section">
                    <h3>Detalle de Coincidencias</h3>
                    <?php if (empty($resultados['match']['coincidencias_detalle'])): ?>
                        <p class="warning">No hay detalles de coincidencias disponibles.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Habilidad</th>
                                    <th>Obligatorio</th>
                                    <th>Tiene Habilidad</th>
                                    <th>Nivel Suficiente</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados['match']['coincidencias_detalle'] as $coincidencia): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coincidencia['nombre']) ?></td>
                                        <td><?= $coincidencia['obligatorio'] ? 'Sí' : 'No' ?></td>
                                        <td class="<?= $coincidencia['tiene_habilidad'] ? 'success' : 'danger' ?>"><?= $coincidencia['tiene_habilidad'] ? 'Sí' : 'No' ?></td>
                                        <td class="<?= $coincidencia['nivel_coincide'] ? 'success' : ($coincidencia['tiene_habilidad'] ? 'warning' : 'danger') ?>"><?= $coincidencia['nivel_coincide'] ? 'Sí' : 'No' ?></td>
                                        <td><?= htmlspecialchars($coincidencia['detalle']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
