<?php
// Clase para facilitar el uso de la API de Supabase
class SupabaseClient {
    private $url;
    private $key;
    
    public function __construct($url, $key) {
        $this->url = $url;
        $this->key = $key;
    }
    
    /**
     * Realizar una solicitud a la API de Supabase
     */
    public function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->url . $endpoint;
        $ch = curl_init($url);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->key,
            'apikey: ' . $this->key
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        // Para depuración
        error_log("Supabase Request: $method $url");
        if ($data) {
            error_log("Request data: " . json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Para depuración
        error_log("Supabase Response code: $httpCode");
        error_log("Supabase Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
        
        if ($error) {
            error_log("Supabase Error: $error");
            return (object)['error' => $error];
        }
        
        // Verificar si hay errores de HTTP
        if ($httpCode >= 400) {
            error_log("HTTP Error: $httpCode - $response");
            return (object)['error' => "HTTP Error $httpCode", 'details' => json_decode($response)];
        }
        
        // Convertir respuesta a objeto para facilitar su uso
        return json_decode($response);
    }
    
    /**
     * Consultar datos de una tabla
     */
    public function from($table) {
        return new SupabaseQuery($this, $table);
    }
}

// Clase para construir consultas
class SupabaseQuery {
    private $client;
    private $table;
    private $select = '*';
    private $filters = [];
    
    public function __construct($client, $table) {
        $this->client = $client;
        $this->table = $table;
    }
    
    /**
     * Especificar los campos a seleccionar
     */
    public function select($fields) {
        $this->select = $fields;
        return $this;
    }
    
    /**
     * Filtrar por igualdad
     */
    public function eq($column, $value) {
        $this->filters[] = ["$column=eq." . urlencode($value)];
        return $this;
    }
    
    /**
     * Insertar datos
     */
    public function insert($data) {
        return $this->client->request("/rest/v1/{$this->table}", 'POST', $data);
    }
    
    /**
     * Actualizar datos
     */
    public function update($data) {
        $query = "/rest/v1/{$this->table}";
        if (!empty($this->filters)) {
            $query .= "?" . implode('&', array_merge(...$this->filters));
        }
        return $this->client->request($query, 'PATCH', $data);
    }
    
    /**
     * Eliminar datos
     */
    public function delete() {
        $query = "/rest/v1/{$this->table}";
        if (!empty($this->filters)) {
            $query .= "?" . implode('&', array_merge(...$this->filters));
        }
        return $this->client->request($query, 'DELETE');
    }
    
    /**
     * Ejecutar la consulta
     */
    public function execute() {
        $query = "/rest/v1/{$this->table}?select=" . urlencode($this->select);
        
        if (!empty($this->filters)) {
            $query .= "&" . implode('&', array_merge(...$this->filters));
        }
        
        // Agregar un límite por defecto si no se especifica
        if (strpos($query, 'limit=') === false) {
            $query .= "&limit=100"; // Límite por defecto para evitar problemas
        }
        
        // Para depuración
        error_log("Ejecutando consulta Supabase: " . $query);
        
        $response = $this->client->request($query);
        
        // Verificar la respuesta
        if (isset($response->error)) {
            error_log("Error en la consulta: " . json_encode($response->error));
        } else if (!isset($response->data) || !is_array($response->data)) {
            // Si la respuesta es directamente un array (sin propiedad data)
            if (is_array($response)) {
                error_log("Respuesta obtenida como array directo, convirtiendo formato");
                return (object)['data' => $response];
            } else {
                error_log("Formato de respuesta inesperado: " . json_encode($response));
                // Intentar adaptar la respuesta
                return (object)['data' => is_array($response) ? $response : []];
            }
        }
        
        return $response;
    }
}

/**
 * Verificar la existencia y contenido de una tabla
 */
function verifyTable($tableName) {
    $client = getSupabaseClient();
    $result = $client->request("/rest/v1/$tableName?limit=5");
    
    if (isset($result->error)) {
        error_log("Error al verificar tabla $tableName: " . json_encode($result->error));
        return [
            'exists' => false,
            'error' => $result->error,
            'message' => "No se pudo acceder a la tabla $tableName"
        ];
    }
    
    // Convertir a array asociativo para mejor comprensión
    $data = json_decode(json_encode($result), true);
    
    return [
        'exists' => true,
        'count' => count($data),
        'sample' => array_slice($data, 0, 3)
    ];
}

/**
 * Obtener una instancia del cliente de Supabase
 */
function getSupabaseClient() {
    static $client = null;
    if ($client === null) {
        $client = new SupabaseClient(SUPABASE_URL, SUPABASE_KEY);
    }
    return $client;
}
?>
