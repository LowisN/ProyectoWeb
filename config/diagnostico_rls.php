<?php
/**
 * Herramienta de diagnóstico para problemas de políticas RLS en Supabase
 * 
 * Esta utilidad permite:
 * 1. Detectar políticas recursivas en tablas
 * 2. Obtener perfiles evitando políticas RLS problemáticas
 * 3. Sugerir correcciones para las políticas
 */

session_start();
require_once 'supabase.php';

$tables = ['perfiles', 'candidatos', 'reclutadores', 'empresas'];
$results = [];
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$email = isset($_GET['email']) ? $_GET['email'] : null;

// Función para mostrar resultados de pruebas
function showTestResult($test, $passed, $message, $details = '') {
    $status = $passed ? 'success' : 'error';
    $icon = $passed ? '✅' : '❌';
    
    return [
        'test' => $test,
        'status' => $status,
        'icon' => $icon,
        'message' => $message,
        'details' => $details
    ];
}

// Comprobar políticas RLS para cada tabla
foreach ($tables as $table) {
    try {
        $testResult = [];
        
        // Intento simple de acceso
        $response = supabaseRequest("/rest/v1/$table?limit=1", 'GET');
        
        if (!isset($response['error'])) {
            $testResult = showTestResult(
                "Política RLS en tabla '$table'", 
                true,
                "La política RLS para '$table' funciona correctamente",
                "Acceso exitoso a la tabla"
            );
        } else {
            $isRecursion = strpos($response['error'], 'infinite recursion') !== false || 
                           strpos($response['error'], 'recursion') !== false;
            
            if ($isRecursion) {
                $testResult = showTestResult(
                    "Política RLS en tabla '$table'",
                    false,
                    "Detectada recursión infinita en la política RLS para '$table'",
                    "Error: " . $response['error']
                );
                
                // Añadir sugerencia de corrección
                $testResult['suggestion'] = "La política RLS para la tabla '$table' contiene una recursión infinita. " .
                                           "Esto suele ocurrir cuando una política hace referencia a la misma tabla que está protegiendo, " .
                                           "o cuando hay referencias circulares entre políticas de diferentes tablas.";
                
                $testResult['sql_fix'] = "-- Revise sus políticas RLS en Supabase y simplifíquelas\n" .
                                         "-- Ejemplo de política simplificada para la tabla '$table':\n" .
                                         "ALTER TABLE $table ENABLE ROW LEVEL SECURITY;\n\n" .
                                         "-- Política para SELECT (lectura)\n" .
                                         "CREATE POLICY \"$table select policy\" ON $table FOR SELECT\n" .
                                         "  USING (auth.uid() = user_id OR auth.uid() IN (\n" .
                                         "    SELECT auth.uid() FROM auth.users WHERE auth.uid() IS NOT NULL\n" .
                                         "  ));\n\n" .
                                         "-- Política para INSERT (inserción)\n" .
                                         "CREATE POLICY \"$table insert policy\" ON $table FOR INSERT\n" .
                                         "  WITH CHECK (auth.uid() IS NOT NULL);\n\n" .
                                         "-- Política para UPDATE (actualización)\n" .
                                         "CREATE POLICY \"$table update policy\" ON $table FOR UPDATE\n" .
                                         "  USING (auth.uid() = user_id);\n\n";
            } else {
                $testResult = showTestResult(
                    "Política RLS en tabla '$table'",
                    false,
                    "Error al acceder a la tabla '$table', pero no es recursión",
                    "Error: " . $response['error']
                );
            }
        }
        
        $results[$table] = $testResult;
    } catch (Exception $e) {
        $results[$table] = showTestResult(
            "Política RLS en tabla '$table'",
            false,
            "Excepción al probar política RLS",
            $e->getMessage()
        );
    }
}

// Intentar obtener perfil de usuario específico con bypass si se proporciona email o userId
$profileBypass = null;
$profileNormal = null;
$compareResults = [];

if (!empty($userId) || !empty($email)) {
    // Intento con el método normal
    if (!empty($userId)) {
        $profileNormal = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        $targetDesc = "user_id = $userId";
    } else {
        $profileNormal = supabaseFetch('perfiles', '*', ['email' => $email]);
        $targetDesc = "email = $email";
    }
    
    // Intento con bypass
    $profileBypass = getProfileBypass($userId, $email);
    
    // Comparar resultados
    $normalSuccess = !isset($profileNormal['error']);
    $bypassSuccess = !isset($profileBypass['error']);
    
    $compareResults['method_normal'] = showTestResult(
        "Obtener perfil con método estándar",
        $normalSuccess,
        $normalSuccess ? "Se obtuvo el perfil correctamente" : "Error al obtener perfil con método estándar",
        $normalSuccess ? "Datos obtenidos para $targetDesc" : "Error: " . json_encode($profileNormal['error'] ?? 'Desconocido')
    );
    
    $compareResults['method_bypass'] = showTestResult(
        "Obtener perfil con bypass RLS",
        $bypassSuccess,
        $bypassSuccess ? "Se obtuvo el perfil evitando RLS" : "Error al obtener perfil incluso evitando RLS",
        $bypassSuccess ? "Datos obtenidos para $targetDesc" : "Error: " . json_encode($profileBypass['error'] ?? 'Desconocido')
    );
    
    // Determinar si el bypass es efectivo
    if ($bypassSuccess && !$normalSuccess) {
        $compareResults['conclusion'] = [
            'status' => 'success',
            'message' => 'El método de bypass funciona correctamente. Use esta solución temporal mientras corrige las políticas RLS.'
        ];
    } elseif (!$bypassSuccess && !$normalSuccess) {
        $compareResults['conclusion'] = [
            'status' => 'error',
            'message' => 'Ningún método funciona. Es posible que haya problemas más graves en la estructura de la base de datos o en la conexión.'
        ];
    } elseif ($normalSuccess) {
        $compareResults['conclusion'] = [
            'status' => 'info',
            'message' => 'El método estándar funciona correctamente. No es necesario usar el bypass.'
        ];
    }
}

// Función para generar SQL de corrección para políticas RLS
function generateFixSQL() {
    global $tables;
    $sql = "-- Script para corregir políticas RLS con problemas de recursión\n\n";
    
    foreach ($tables as $table) {
        $sql .= "-- Eliminar políticas existentes para la tabla '$table'\n";
        $sql .= "DROP POLICY IF EXISTS \"${table}_policy\" ON $table;\n";
        $sql .= "DROP POLICY IF EXISTS \"${table}_select_policy\" ON $table;\n";
        $sql .= "DROP POLICY IF EXISTS \"${table}_insert_policy\" ON $table;\n";
        $sql .= "DROP POLICY IF EXISTS \"${table}_update_policy\" ON $table;\n";
        $sql .= "DROP POLICY IF EXISTS \"${table}_delete_policy\" ON $table;\n\n";
        
        $sql .= "-- Habilitar RLS para la tabla '$table'\n";
        $sql .= "ALTER TABLE $table ENABLE ROW LEVEL SECURITY;\n\n";
        
        $sql .= "-- Política para SELECT (lectura)\n";
        $sql .= "CREATE POLICY \"${table}_select_policy\" ON $table FOR SELECT\n";
        
        // Políticas específicas según la tabla
        if ($table == 'perfiles') {
            $sql .= "  USING (auth.uid() = user_id OR auth.uid() IN (\n";
            $sql .= "    SELECT auth.uid() FROM auth.users WHERE auth.uid() IS NOT NULL\n";
            $sql .= "  ));\n\n";
        } else if ($table == 'candidatos') {
            $sql .= "  USING (EXISTS (\n";
            $sql .= "    SELECT 1 FROM perfiles WHERE perfiles.id = $table.perfil_id AND perfiles.user_id = auth.uid()\n";
            $sql .= "  ) OR auth.uid() IN (\n";
            $sql .= "    SELECT auth.uid() FROM auth.users WHERE auth.uid() IS NOT NULL\n";
            $sql .= "  ));\n\n";
        } else {
            $sql .= "  USING (true);\n\n";
        }
        
        $sql .= "-- Política para INSERT (inserción)\n";
        $sql .= "CREATE POLICY \"${table}_insert_policy\" ON $table FOR INSERT\n";
        $sql .= "  WITH CHECK (auth.uid() IS NOT NULL);\n\n";
        
        $sql .= "-- Política para UPDATE (actualización)\n";
        $sql .= "CREATE POLICY \"${table}_update_policy\" ON $table FOR UPDATE\n";
        
        if ($table == 'perfiles') {
            $sql .= "  USING (auth.uid() = user_id);\n\n";
        } else if ($table == 'candidatos') {
            $sql .= "  USING (EXISTS (\n";
            $sql .= "    SELECT 1 FROM perfiles WHERE perfiles.id = $table.perfil_id AND perfiles.user_id = auth.uid()\n";
            $sql .= "  ));\n\n";
        } else {
            $sql .= "  USING (true);\n\n";
        }
    }
    
    return $sql;
}

$fixSQL = generateFixSQL();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Políticas RLS - ChambaNet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .success {
            background-color: #d4edda;
            border-left: 5px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .error {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .info {
            background-color: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        .test-result {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .details {
            margin-top: 5px;
            font-size: 0.9em;
            color: #555;
        }
        pre {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow: auto;
            font-size: 14px;
            line-height: 1.4;
        }
        .form {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        input, button {
            padding: 8px;
            margin-right: 10px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #0069d9;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .solution-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .tabs {
            display: flex;
            margin-bottom: 0;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f1f1f1;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            margin-right: 2px;
        }
        .tab.active {
            background: #fff;
            border: 1px solid #ddd;
            border-bottom: none;
        }
        .tab-content {
            display: none;
            padding: 15px;
            border: 1px solid #ddd;
            border-top: none;
            margin-top: -1px;
        }
        .tab-content.active {
            display: block;
        }
        .copy-btn {
            float: right;
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
    <script>
        function copyToClipboard(elemId) {
            const elem = document.getElementById(elemId);
            const text = elem.textContent;
            navigator.clipboard.writeText(text)
                .then(() => alert('SQL copiado al portapapeles'))
                .catch(err => console.error('Error al copiar: ', err));
        }
        
        function activateTab(tabId) {
            // Ocultar todos los contenidos de tabs
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Desactivar todas las pestañas
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Activar la pestaña y contenido seleccionados
            document.getElementById(tabId).classList.add('active');
            document.getElementById(tabId + '-content').classList.add('active');
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Diagnóstico de Políticas RLS</h1>
            <p>Esta herramienta analiza y soluciona problemas con las políticas de seguridad de fila (RLS) en Supabase</p>
        </div>
        
        <div class="form">
            <h3>Probar acceso a perfil específico</h3>
            <form method="GET">
                <input type="text" name="user_id" placeholder="ID de usuario" value="<?= htmlspecialchars($userId ?? '') ?>">
                <span>o</span>
                <input type="email" name="email" placeholder="Email de usuario" value="<?= htmlspecialchars($email ?? '') ?>">
                <button type="submit">Probar</button>
            </form>
        </div>
        
        <h2>Resultados del Diagnóstico</h2>
        
        <h3>Estado de políticas RLS por tabla</h3>
        <?php foreach ($results as $table => $result): ?>
            <div class="test-result <?= $result['status'] ?>">
                <strong><?= $result['icon'] ?> <?= $result['test'] ?>:</strong> <?= $result['message'] ?>
                <?php if (!empty($result['details'])): ?>
                    <div class="details"><?= $result['details'] ?></div>
                <?php endif; ?>
                
                <?php if (!empty($result['suggestion'])): ?>
                    <div class="details"><strong>Sugerencia:</strong> <?= $result['suggestion'] ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <?php if (!empty($compareResults)): ?>
            <h3>Prueba de obtención de perfil</h3>
            
            <div class="test-result <?= $compareResults['method_normal']['status'] ?>">
                <strong><?= $compareResults['method_normal']['icon'] ?> <?= $compareResults['method_normal']['test'] ?>:</strong> 
                <?= $compareResults['method_normal']['message'] ?>
                <?php if (!empty($compareResults['method_normal']['details'])): ?>
                    <div class="details"><?= $compareResults['method_normal']['details'] ?></div>
                <?php endif; ?>
            </div>
            
            <div class="test-result <?= $compareResults['method_bypass']['status'] ?>">
                <strong><?= $compareResults['method_bypass']['icon'] ?> <?= $compareResults['method_bypass']['test'] ?>:</strong> 
                <?= $compareResults['method_bypass']['message'] ?>
                <?php if (!empty($compareResults['method_bypass']['details'])): ?>
                    <div class="details"><?= $compareResults['method_bypass']['details'] ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($compareResults['conclusion'])): ?>
                <div class="info">
                    <strong>Conclusión: </strong> <?= $compareResults['conclusion']['message'] ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($profileBypass) && !isset($profileBypass['error']) && !empty($profileBypass)): ?>
                <h4>Datos del perfil recuperados (usando bypass):</h4>
                <pre><?= htmlspecialchars(json_encode($profileBypass, JSON_PRETTY_PRINT)) ?></pre>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="solution-box">
            <h3>Soluciones Propuestas</h3>
            <p>Basado en el diagnóstico, aquí hay varias soluciones que puede implementar:</p>
            
            <div class="tabs">
                <div class="tab active" id="tab-sql" onclick="activateTab('tab-sql')">SQL para corregir políticas</div>
                <div class="tab" id="tab-bypass" onclick="activateTab('tab-bypass')">Usar función bypass</div>
                <div class="tab" id="tab-temp" onclick="activateTab('tab-temp')">Desactivar RLS temporalmente</div>
            </div>
            
            <div class="tab-content active" id="tab-sql-content">
                <button class="copy-btn" onclick="copyToClipboard('fix-sql')">Copiar SQL</button>
                <h4>SQL para corregir políticas RLS:</h4>
                <p>Ejecute este SQL en el editor SQL de Supabase para corregir las políticas problemáticas:</p>
                <pre id="fix-sql"><?= htmlspecialchars($fixSQL) ?></pre>
            </div>
            
            <div class="tab-content" id="tab-bypass-content">
                <h4>Usar la función bypass en tu código:</h4>
                <p>En lugar de usar <code>supabaseFetch</code> para obtener perfiles, usa <code>getProfileBypass</code>:</p>
                <pre>// Obtener perfil con bypass RLS
$perfil = getProfileBypass($userId, $email);

if (!isset($perfil['error'])) {
    // Procesar el perfil...
} else {
    // Manejar el error...
}</pre>
                <p><strong>Nota:</strong> Esta es una solución temporal mientras se corrigen las políticas RLS.</p>
            </div>
            
            <div class="tab-content" id="tab-temp-content">
                <button class="copy-btn" onclick="copyToClipboard('disable-rls')">Copiar SQL</button>
                <h4>Desactivar RLS temporalmente:</h4>
                <p>Como solución temporal de emergencia, puede desactivar RLS para las tablas afectadas:</p>
                <pre id="disable-rls"><?php 
                    $disableSQL = "";
                    foreach ($tables as $table) {
                        $disableSQL .= "-- Desactivar RLS para la tabla '$table'\n";
                        $disableSQL .= "ALTER TABLE $table DISABLE ROW LEVEL SECURITY;\n\n";
                    }
                    echo htmlspecialchars($disableSQL);
                ?></pre>
                <p><strong>¡Advertencia!</strong> Esto eliminará todas las restricciones de seguridad. Úselo solo en desarrollo o en caso de emergencia, y actívelo nuevamente lo antes posible.</p>
            </div>
        </div>
        
        <div class="actions">
            <h3>Acciones Adicionales</h3>
            <a href="?<?= !empty($userId) ? 'user_id='.urlencode($userId) : (!empty($email) ? 'email='.urlencode($email) : '') ?>" class="btn">Actualizar Diagnóstico</a>
            <a href="verificar_tablas.php" class="btn">Verificar Estructura de Tablas</a>
            <a href="verificar_conexion_supabase.php" class="btn">Probar Conexión</a>
            <a href="../paginas/interfaz_iniciar_sesion.php" class="btn btn-secondary">Volver a Inicio de Sesión</a>
        </div>
    </div>
</body>
</html>
