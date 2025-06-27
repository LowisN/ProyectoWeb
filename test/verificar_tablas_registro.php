<?php
session_start();
require_once 'config/supabase.php';

echo "<h1>Verificación de Tablas para Registro Unificado</h1>";

// Definir las tablas necesarias para el flujo de registro de candidato
$tablas = [
    'perfiles',
    'candidatos', 
    'conocimientos_candidato',
    'educacion',
    'experiencia_laboral'
];

// Estructura esperada de las tablas
$estructuras = [
    'perfiles' => ['id', 'user_id', 'tipo_usuario'],
    'candidatos' => ['id', 'perfil_id', 'telefono', 'fecha_nacimiento', 'direccion', 'titulo', 'anios_experiencia', 'acerca_de', 'cv_url'],
    'conocimientos_candidato' => ['id', 'candidato_id', 'tecnologia', 'nivel'],
    'educacion' => ['id', 'candidato_id', 'institucion', 'titulo', 'area', 'en_curso', 'descripcion', 'fecha_inicio', 'fecha_fin'],
    'experiencia_laboral' => ['id', 'candidato_id', 'empresa', 'puesto', 'actual', 'descripcion', 'fecha_inicio', 'fecha_fin']
];

// Verificar cada tabla
foreach ($tablas as $tabla) {
    echo "<h2>Verificando tabla '$tabla'</h2>";
    
    try {
        // Intentar hacer una consulta simple para ver si la tabla existe
        $response = supabaseFetch($tabla, '*', [], 1);
        
        if (isset($response['error']) && strpos(strtolower($response['error']), 'does not exist') !== false) {
            echo "<p style='color: red;'>❌ La tabla '$tabla' no existe.</p>";
            
            // Proporcionar SQL para crear la tabla si no existe
            echo "<details>";
            echo "<summary>Mostrar SQL para crear la tabla</summary>";
            echo "<pre>";
            
            switch ($tabla) {
                case 'perfiles':
                    echo "CREATE TABLE perfiles (\n";
                    echo "    id SERIAL PRIMARY KEY,\n";
                    echo "    user_id UUID NOT NULL,\n";
                    echo "    tipo_usuario VARCHAR(20) NOT NULL,\n";
                    echo "    created_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    updated_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    CONSTRAINT unique_user_id UNIQUE (user_id)\n";
                    echo ");";
                    break;
                    
                case 'candidatos':
                    echo "CREATE TABLE candidatos (\n";
                    echo "    id SERIAL PRIMARY KEY,\n";
                    echo "    perfil_id INTEGER NOT NULL,\n";
                    echo "    telefono VARCHAR(20) NOT NULL,\n";
                    echo "    fecha_nacimiento DATE NOT NULL,\n";
                    echo "    direccion TEXT NOT NULL,\n";
                    echo "    titulo VARCHAR(100),\n";
                    echo "    anios_experiencia INTEGER DEFAULT 0,\n";
                    echo "    acerca_de TEXT,\n";
                    echo "    cv_url TEXT,\n";
                    echo "    created_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    updated_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    CONSTRAINT fk_perfil FOREIGN KEY (perfil_id) REFERENCES perfiles(id) ON DELETE CASCADE\n";
                    echo ");";
                    break;
                    
                case 'conocimientos_candidato':
                    echo "CREATE TABLE conocimientos_candidato (\n";
                    echo "    id SERIAL PRIMARY KEY,\n";
                    echo "    candidato_id INTEGER NOT NULL,\n";
                    echo "    tecnologia VARCHAR(100) NOT NULL,\n";
                    echo "    nivel VARCHAR(20) NOT NULL,\n";
                    echo "    created_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    updated_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    CONSTRAINT fk_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE,\n";
                    echo "    CONSTRAINT unique_candidato_tecnologia UNIQUE (candidato_id, tecnologia)\n";
                    echo ");";
                    break;
                    
                case 'educacion':
                    echo "CREATE TABLE educacion (\n";
                    echo "    id SERIAL PRIMARY KEY,\n";
                    echo "    candidato_id INTEGER NOT NULL,\n";
                    echo "    institucion VARCHAR(100) NOT NULL,\n";
                    echo "    titulo VARCHAR(100) NOT NULL,\n";
                    echo "    area VARCHAR(100),\n";
                    echo "    en_curso BOOLEAN DEFAULT FALSE,\n";
                    echo "    descripcion TEXT,\n";
                    echo "    fecha_inicio DATE,\n";
                    echo "    fecha_fin DATE,\n";
                    echo "    created_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    updated_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    CONSTRAINT fk_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE\n";
                    echo ");";
                    break;
                    
                case 'experiencia_laboral':
                    echo "CREATE TABLE experiencia_laboral (\n";
                    echo "    id SERIAL PRIMARY KEY,\n";
                    echo "    candidato_id INTEGER NOT NULL,\n";
                    echo "    empresa VARCHAR(100) NOT NULL,\n";
                    echo "    puesto VARCHAR(100) NOT NULL,\n";
                    echo "    actual BOOLEAN DEFAULT FALSE,\n";
                    echo "    descripcion TEXT,\n";
                    echo "    fecha_inicio DATE,\n";
                    echo "    fecha_fin DATE,\n";
                    echo "    created_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    updated_at TIMESTAMPTZ DEFAULT NOW(),\n";
                    echo "    CONSTRAINT fk_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE CASCADE\n";
                    echo ");";
                    break;
                    
                default:
                    echo "-- SQL no disponible para esta tabla";
                    break;
            }
            
            echo "</pre>";
            echo "</details>";
            
            continue;
        } 
        
        if (isset($response['error'])) {
            echo "<p style='color: red;'>❌ Error al verificar la tabla '$tabla': " . htmlspecialchars(json_encode($response['error'])) . "</p>";
            continue;
        }
        
        echo "<p style='color: green;'>✅ La tabla '$tabla' existe.</p>";
        
        // Verificar estructura
        if (isset($estructuras[$tabla])) {
            // Obtener un registro para analizar estructura
            $muestra = is_array($response) && !empty($response) ? $response[0] : [];
            
            if (empty($muestra)) {
                echo "<p>No hay registros para verificar estructura.</p>";
            } else {
                $campos_esperados = $estructuras[$tabla];
                $campos_encontrados = array_keys((array)$muestra);
                $campos_faltantes = array_diff($campos_esperados, $campos_encontrados);
                
                if (!empty($campos_faltantes)) {
                    echo "<p style='color: orange;'>⚠️ Campos faltantes: " . implode(', ', $campos_faltantes) . "</p>";
                } else {
                    echo "<p style='color: green;'>✅ La estructura parece correcta.</p>";
                }
                
                echo "<details>";
                echo "<summary>Ver campos actuales</summary>";
                echo "<pre>" . htmlspecialchars(implode(', ', $campos_encontrados)) . "</pre>";
                echo "</details>";
            }
        }
        
        // Mostrar cantidad de registros
        $count = is_array($response) ? count($response) : 0;
        echo "<p>Registros encontrados: $count</p>";
        
        if ($count > 0) {
            echo "<details>";
            echo "<summary>Ver muestra de datos</summary>";
            echo "<pre>" . htmlspecialchars(json_encode($response[0], JSON_PRETTY_PRINT)) . "</pre>";
            echo "</details>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al verificar tabla '$tabla': " . $e->getMessage() . "</p>";
    }
}

// Función para verificar tablas
function supabaseFetch($table, $select = '*', $filters = [], $limit = null) {
    $query = "/rest/v1/$table?select=" . urlencode($select);
    
    if (!empty($filters)) {
        foreach ($filters as $column => $value) {
            $query .= "&$column=eq." . urlencode($value);
        }
    }
    
    if ($limit !== null) {
        $query .= "&limit=" . intval($limit);
    }
    
    return supabaseRequest($query);
}

echo "<hr>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
