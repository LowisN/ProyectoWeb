<?php
// Script simplificado para probar Supabase
require_once 'config/supabase.php';

echo "<h1>Prueba de API Supabase</h1>";

// Función para mostrar resultados de forma legible
function displayResult($title, $data) {
    echo "<h3>$title</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
}

// Probar la conexión a Supabase
echo "<h2>Probando conexión a Supabase</h2>";
$connectionTest = supabaseRequest('/rest/v1/');
displayResult("Resultado de conexión", $connectionTest);

// Verificar si hay tablas
echo "<h2>Verificando tablas</h2>";
$tables = ['perfiles', 'empresas', 'candidatos', 'reclutadores'];

foreach ($tables as $table) {
    $tableResult = supabaseFetch($table, '*', ['limit' => 1]);
    displayResult("Tabla: $table", $tableResult);
}

// Probar inserción y respuesta
echo "<h2>Probar inserción</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'insert_profile') {
        // Generar un UUID v4 aleatorio para simular un ID de usuario
        function generateUuidV4() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Versión 4
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variante RFC 4122
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        
        $userId = $_POST['user_id'] ?: generateUuidV4();
        $tipoUsuario = $_POST['tipo_usuario'];
        
        $perfilData = [
            'user_id' => $userId,
            'tipo_usuario' => $tipoUsuario
        ];
        
        echo "<p>Intentando insertar perfil con:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($perfilData, JSON_PRETTY_PRINT)) . "</pre>";
        
        // Realizar la inserción
        $insertResult = supabaseInsert('perfiles', $perfilData);
        displayResult("Resultado de inserción", $insertResult);
        
        // Verificar si el perfil fue insertado buscándolo
        $fetchResult = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        displayResult("Verificación de inserción", $fetchResult);
    }
}

?>

<h2>Prueba de inserción en perfiles</h2>
<form method="post" action="">
    <div>
        <label for="user_id">ID de usuario (UUID):</label>
        <input type="text" id="user_id" name="user_id" placeholder="Dejar vacío para generar uno aleatorio">
        <small>Formato UUID v4</small>
    </div>
    <div>
        <label for="tipo_usuario">Tipo de usuario:</label>
        <select id="tipo_usuario" name="tipo_usuario">
            <option value="candidato">Candidato</option>
            <option value="reclutador">Reclutador</option>
        </select>
    </div>
    <div>
        <input type="hidden" name="action" value="insert_profile">
        <button type="submit">Insertar perfil</button>
    </div>
</form>

<h2>Ver estructura de la tabla</h2>

<?php
// Mostrar información sobre las tablas
$perfilesInfo = supabaseFetch('perfiles', '*', ['limit' => 5]);
if (is_array($perfilesInfo) && !empty($perfilesInfo)) {
    echo "<h3>Campos de la tabla perfiles:</h3>";
    echo "<ul>";
    foreach (array_keys($perfilesInfo[0]) as $campo) {
        echo "<li>" . htmlspecialchars($campo) . "</li>";
    }
    echo "</ul>";
}

// Mostrar total de registros
$tables = ['perfiles', 'empresas', 'candidatos', 'reclutadores'];
echo "<h3>Total de registros:</h3>";
echo "<ul>";
foreach ($tables as $table) {
    $countResult = supabaseFetch($table, '*');
    $count = is_array($countResult) ? count($countResult) : 0;
    echo "<li>$table: $count registros</li>";
}
echo "</ul>";
?>

<p><a href="index.php">Volver al inicio</a></p>
