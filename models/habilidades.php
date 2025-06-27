<?php
// Este archivo tiene la lógica para gestionar habilidades en el sistema
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/SupabaseClient.php';

/**
 * Clase para gestionar habilidades en la aplicación
 */
class Habilidades {
    private $supabase;
    private $mapaHabilidades = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->supabase = getSupabaseClient();
        $this->cargarMapaHabilidades();
    }
    
    /**
     * Carga el mapa de habilidades desde la base de datos
     */
    private $habilidadesCompletas = [];
    
    /**
     * Carga el mapa de habilidades desde la base de datos
     */
    private function cargarMapaHabilidades() {
        if ($this->mapaHabilidades !== null) {
            return;
        }
        
        try {
            $this->mapaHabilidades = [];
            $this->habilidadesCompletas = [];
            
            // Intenta usar directamente el cliente de supabase
            $response = $this->supabase->request("/rest/v1/habilidades?select=*&limit=500");
            
            // Registrar para depuración
            error_log("Respuesta de carga de habilidades: " . json_encode(array_slice((array)$response, 0, 3)));
            
            // Procesar los datos de respuesta según el formato
            $datos = [];
            if (isset($response->data) && is_array($response->data)) {
                $datos = $response->data;
            } else if (is_array($response)) {
                $datos = $response;
            }
            
            foreach ($datos as $habilidad) {
                $habilidad = (object)$habilidad; // Asegurar que es un objeto
                
                if (isset($habilidad->id) && isset($habilidad->nombre)) {
                    // Asegurarnos que todas las habilidades tengan una categoría válida
                    if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                        $habilidad->categoria = $this->determinarCategoria($habilidad->nombre);
                    }
                    
                    // Guardar el ID para búsqueda rápida
                    $this->mapaHabilidades[$habilidad->nombre] = $habilidad->id;
                    
                    // Guardar el objeto completo para tener la categoría y descripción
                    $this->habilidadesCompletas[] = $habilidad;
                }
            }
            
            error_log("Mapa de habilidades cargado con " . count($this->mapaHabilidades) . " habilidades");
            error_log("Habilidades completas cargadas: " . count($this->habilidadesCompletas));
            
            if (count($this->mapaHabilidades) === 0) {
                // Verificación directa con curl para diagnóstico
                $this->verificarTablaHabilidadesDirectamente();
            }
            
        } catch (Exception $e) {
            error_log("Error al cargar habilidades: " . $e->getMessage());
        }
    }
    
    /**
     * Verificación directa de la tabla habilidades usando curl
     */
    private function verificarTablaHabilidadesDirectamente() {
        $url = SUPABASE_URL . "/rest/v1/habilidades?select=*&limit=500";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUPABASE_KEY,
            'apikey: ' . SUPABASE_KEY
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("Verificación directa de habilidades - HTTP Code: $httpCode");
        error_log("Respuesta: " . substr($response, 0, 500));
        
        if ($httpCode === 200) {
            $data = json_decode($response);
            if (is_array($data) && count($data) > 0) {
                error_log("Encontradas " . count($data) . " habilidades mediante verificación directa");
                
                // Actualizar el mapa con los datos obtenidos
                foreach ($data as $habilidad) {
                    if (isset($habilidad->id) && isset($habilidad->nombre)) {
                        // Asegurarnos que todas las habilidades tengan una categoría válida
                        if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                            $habilidad->categoria = $this->determinarCategoria($habilidad->nombre);
                        }
                        
                        // Guardar ID para búsqueda rápida
                        $this->mapaHabilidades[$habilidad->nombre] = $habilidad->id;
                        
                        // Guardar objeto completo
                        $this->habilidadesCompletas[] = $habilidad;
                    }
                }
                
                error_log("Mapa actualizado: " . count($this->mapaHabilidades) . " habilidades");
                error_log("Habilidades completas: " . count($this->habilidadesCompletas) . " objetos");
            } else {
                error_log("La verificación directa no encontró habilidades");
            }
        }
    }
    
    /**
     * Obtiene todas las habilidades de la base de datos
     */
    public function obtenerTodasHabilidades() {
        try {
            // Asegurarnos de tener el mapa cargado
            $this->cargarMapaHabilidades();
            
            // Si no tenemos datos en el mapa, intentamos una verificación directa
            if (count($this->mapaHabilidades) === 0) {
                $this->verificarTablaHabilidadesDirectamente();
                
                // Si sigue sin haber datos, hacemos una última verificación con la función supabaseFetch
                if (count($this->mapaHabilidades) === 0) {
                    error_log("Intento final usando supabaseFetch");
                    $resultado = supabaseFetch('habilidades', '*');
                    error_log("Resultado supabaseFetch: " . json_encode(array_slice($resultado, 0, 3)));
                    
                    $this->habilidadesCompletas = [];
                    foreach ($resultado as $item) {
                        $habilidad = (object)$item;
                        
                        // Asegurar que la habilidad tenga categoría
                        if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                            $habilidad->categoria = $this->determinarCategoria($habilidad->nombre);
                        }
                        
                        $this->habilidadesCompletas[] = $habilidad;
                        if (isset($habilidad->id) && isset($habilidad->nombre)) {
                            $this->mapaHabilidades[$habilidad->nombre] = $habilidad->id;
                        }
                    }
                }
            }
            
            // Si tenemos habilidades completas, usarlas directamente - verificando que cada una tenga categoría
            if (count($this->habilidadesCompletas) > 0) {
                error_log("Retornando " . count($this->habilidadesCompletas) . " objetos de habilidades completos");
                
                // Verificar que cada habilidad tenga categoría
                foreach ($this->habilidadesCompletas as &$habilidad) {
                    if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                        $habilidad->categoria = $this->determinarCategoria($habilidad->nombre);
                    }
                }
                
                return $this->habilidadesCompletas;
            }
            
            // Si solo tenemos el mapa de IDs, reconstruir los objetos
            // Este es un caso de respaldo
            $habilidades = [];
            foreach ($this->mapaHabilidades as $nombre => $id) {
                $habilidades[] = (object)[
                    'id' => $id,
                    'nombre' => $nombre,
                    'categoria' => $this->determinarCategoria($nombre),
                    'descripcion' => 'Descripción generada automáticamente'
                ];
            }
            
            error_log("Reconstruidos " . count($habilidades) . " objetos de habilidades desde el mapa");
            return $habilidades;
        } catch (Exception $e) {
            error_log("Error al obtener habilidades: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene habilidades agrupadas por categoría
     */
    /**
     * Obtiene las categorías únicas existentes en la base de datos
     */
    public function obtenerCategoriasUnicas() {
        try {
            // Realizar una consulta directa a la API de Supabase
            $response = $this->supabase->request("/rest/v1/habilidades?select=categoria&distinct=true");
            
            $categorias = [];
            if (isset($response->data) && is_array($response->data)) {
                foreach ($response->data as $item) {
                    if (isset($item->categoria) && !empty($item->categoria)) {
                        $categorias[] = $item->categoria;
                    }
                }
            } elseif (is_array($response)) {
                foreach ($response as $item) {
                    $item = (object)$item;
                    if (isset($item->categoria) && !empty($item->categoria)) {
                        $categorias[] = $item->categoria;
                    }
                }
            }
            
            // Si no se obtuvieron categorías, intentar con una consulta directa
            if (empty($categorias)) {
                error_log("Intentando obtener categorías con método alternativo");
                $url = SUPABASE_URL . "/rest/v1/habilidades?select=categoria&distinct=true";
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . SUPABASE_KEY,
                    'apikey: ' . SUPABASE_KEY
                ];
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $data = json_decode($response);
                    if (is_array($data)) {
                        foreach ($data as $item) {
                            if (isset($item->categoria) && !empty($item->categoria)) {
                                $categorias[] = $item->categoria;
                            }
                        }
                    }
                }
            }
            
            // Si aún no hay categorías, intentar con supabaseFetch
            if (empty($categorias)) {
                error_log("Intentando obtener categorías con supabaseFetch");
                $resultado = supabaseFetch('habilidades', '*');
                if (is_array($resultado)) {
                    foreach ($resultado as $item) {
                        if (isset($item['categoria']) && !empty($item['categoria']) && !in_array($item['categoria'], $categorias)) {
                            $categorias[] = $item['categoria'];
                        }
                    }
                }
            }
            
            // Si después de todos los intentos no hay categorías, usar valores predeterminados
            if (empty($categorias)) {
                error_log("No se encontraron categorías en la base de datos. Usando valores predeterminados.");
                $categorias = [
                    'lenguajes_programacion',
                    'herramientas_desarrollo',
                    'bases_datos',
                    'protocolos_red',
                    'dispositivos_red',
                    'ciberseguridad',
                    'servicios_cloud',
                    'sistemas_operativos',
                    'otras_habilidades'
                ];
            }
            
            error_log("Categorías únicas obtenidas: " . implode(", ", $categorias));
            sort($categorias); // Ordenar alfabéticamente para mejor presentación
            return $categorias;
        } catch (Exception $e) {
            error_log("Error al obtener categorías únicas: " . $e->getMessage());
            return [
                'lenguajes_programacion',
                'herramientas_desarrollo',
                'bases_datos', 
                'protocolos_red',
                'dispositivos_red',
                'ciberseguridad',
                'servicios_cloud',
                'sistemas_operativos',
                'otras_habilidades'
            ];
        }
    }
    
    /**
     * Obtiene habilidades agrupadas por categoría
     */
    public function obtenerHabilidadesPorCategoria() {
        // Obtener todas las habilidades
        $habilidades = $this->obtenerTodasHabilidades();
        error_log("obtenerHabilidadesPorCategoria: Total de habilidades: " . count($habilidades));
        
        // Si no hay habilidades, intentar cargar los datos de respaldo
        if (empty($habilidades)) {
            error_log("No hay habilidades, intentando cargar desde método alternativo");
            
            // Usar directamente la función supabaseFetch como respaldo
            $resultado = supabaseFetch('habilidades', '*');
            $habilidades = [];
            
            if (is_array($resultado) && !empty($resultado)) {
                error_log("Encontradas " . count($resultado) . " habilidades con supabaseFetch");
                foreach ($resultado as $item) {
                    // Convertir a objeto y asegurar que tenga los campos necesarios
                    $habilidad = (object)$item;
                    if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                        $habilidad->categoria = $this->determinarCategoria($habilidad->nombre);
                    }
                    $habilidades[] = $habilidad;
                }
            }
        }
        
        // Inicializar array para agrupar por categoría
        $porCategoria = [];
        
        // Primero, obtener todas las categorías únicas de la base de datos
        $categoriasUnicas = $this->obtenerCategoriasUnicas();
        
        // Inicializar cada categoría en el array
        foreach ($categoriasUnicas as $categoria) {
            $porCategoria[$categoria] = [];
        }
        
        // Distribuir las habilidades en sus categorías correspondientes
        foreach ($habilidades as $habilidad) {
            // Manejar tanto objetos como arrays
            if (is_array($habilidad)) {
                $habilidad = (object)$habilidad;
            }
            
            // Obtener categoría: primero de la base de datos, si no hay usar determinación automática
            $categoria = null;
            
            if (isset($habilidad->categoria) && !empty($habilidad->categoria)) {
                $categoria = $habilidad->categoria;
            } else {
                // Si no tiene categoría asignada, intentar determinarla
                $categoria = $this->determinarCategoria($habilidad->nombre);
            }
            
            // Asegurarnos de que la categoría existe en nuestro array
            if (!isset($porCategoria[$categoria])) {
                $porCategoria[$categoria] = [];
            }
            
            // Agregar la habilidad a su categoría
            $porCategoria[$categoria][] = $habilidad;
        }
        
        // Eliminar categorías vacías
        foreach ($porCategoria as $categoria => $items) {
            if (empty($items)) {
                unset($porCategoria[$categoria]);
            }
        }
        
        // Log para depuración
        foreach ($porCategoria as $categoria => $items) {
            error_log("Categoría '$categoria': " . count($items) . " habilidades");
        }
        
        return $porCategoria;
    }
    
    /**
     * Obtiene el ID de una habilidad por su nombre
     */
    public function obtenerIdPorNombre($nombreHabilidad) {
        // Validar el nombre de la habilidad
        if (empty($nombreHabilidad)) {
            error_log("Nombre de habilidad vacío");
            return false;
        }

        // Asegurarnos de que el mapa de habilidades esté cargado
        if ($this->mapaHabilidades === null || count($this->mapaHabilidades) === 0) {
            $this->cargarMapaHabilidades();
        }
        
        // Si la habilidad existe en el mapa, devolver su ID
        if (isset($this->mapaHabilidades[$nombreHabilidad])) {
            error_log("Habilidad encontrada directamente: $nombreHabilidad (ID: {$this->mapaHabilidades[$nombreHabilidad]})");
            return $this->mapaHabilidades[$nombreHabilidad];
        }
        
        // Si no se encuentra la habilidad por su nombre exacto, buscar coincidencias parciales
        foreach ($this->mapaHabilidades as $nombre => $id) {
            if (stripos($nombre, $nombreHabilidad) !== false || 
                stripos($nombreHabilidad, $nombre) !== false) {
                error_log("Coincidencia parcial para '$nombreHabilidad': usando '$nombre' (ID: $id)");
                return $id;
            }
        }
        
        // Si no encontramos la habilidad, intentar buscarla directamente en la base de datos
        error_log("No se encontró ID para la habilidad: $nombreHabilidad en el mapa local, buscando en la base de datos");
        
        try {
            // Consultar directamente la base de datos
            $response = $this->supabase->from('habilidades')
                ->select('id')
                ->ilike('nombre', "%$nombreHabilidad%")
                ->execute();
            
            if (isset($response->data) && is_array($response->data) && count($response->data) > 0) {
                $id = $response->data[0]->id;
                error_log("Habilidad encontrada en la base de datos: $nombreHabilidad (ID: $id)");
                
                // Actualizar el mapa local
                $this->mapaHabilidades[$nombreHabilidad] = $id;
                return $id;
            }
        } catch (Exception $e) {
            error_log("Error al buscar la habilidad en la base de datos: " . $e->getMessage());
        }
        
        // Si sigue sin encontrarse, intentar insertarla como nueva
        error_log("No se encontró ID para la habilidad: $nombreHabilidad, intentando insertarla");
        return $this->insertarNuevaHabilidad($nombreHabilidad);
    }
    
    /**
     * Inserta una nueva habilidad si no existe
     */
    public function insertarNuevaHabilidad($nombre) {
        // Validar el nombre
        if (empty($nombre)) {
            error_log("No se puede insertar una habilidad sin nombre");
            return false;
        }
        
        // Determinar la categoría más apropiada
        $categoria = $this->determinarCategoria($nombre);
        
        $habilidadData = [
            'nombre' => $nombre,
            'categoria' => $categoria,
            'descripcion' => 'Habilidad agregada automáticamente'
        ];
        
        error_log("Intentando insertar nueva habilidad: " . json_encode($habilidadData));
        
        try {
            // Método 1: Usar el cliente Supabase
            $response = $this->supabase->from('habilidades')->insert($habilidadData);
            
            if (isset($response->error)) {
                error_log("Error al insertar nueva habilidad con cliente Supabase: " . json_encode($response));
                
                // Método 2: Usar la función supabaseInsert
                $altResponse = supabaseInsert('habilidades', $habilidadData);
                
                if (isset($altResponse['error'])) {
                    error_log("Error al insertar con supabaseInsert: " . json_encode($altResponse));
                    
                    // Método 3: Inserción directa con curl
                    error_log("Intentando inserción directa con curl");
                    $insertId = $this->insertarHabilidadDirecta($habilidadData);
                    
                    if ($insertId) {
                        $this->mapaHabilidades[$nombre] = $insertId;
                        return $insertId;
                    }
                    
                    // Si todo falla, buscar si ya existe
                    error_log("Verificando si la habilidad ya existe en la base de datos");
                    $existingId = $this->buscarHabilidadPorNombre($nombre);
                    if ($existingId) {
                        $this->mapaHabilidades[$nombre] = $existingId;
                        return $existingId;
                    }
                    
                    error_log("No se pudo insertar ni encontrar la habilidad");
                    return 1; // ID por defecto como último recurso
                } else {
                    // Supabase insert tuvo éxito
                    $newId = $altResponse[0]['id'] ?? null;
                    if ($newId) {
                        $this->mapaHabilidades[$nombre] = $newId;
                        error_log("Nueva habilidad creada con supabaseInsert, ID: " . $newId);
                        return $newId;
                    }
                }
            } else {
                // Inserción exitosa con el cliente Supabase
                if (isset($response->data[0]->id)) {
                    $newId = $response->data[0]->id;
                    // Actualizar el mapa local
                    $this->mapaHabilidades[$nombre] = $newId;
                    error_log("Nueva habilidad creada con ID: " . $newId);
                    return $newId;
                }
            }
            
            // Si llegamos aquí, intentar buscar la habilidad recién creada
            return $this->buscarHabilidadPorNombre($nombre);
            
        } catch (Exception $e) {
            error_log("Excepción al insertar habilidad: " . $e->getMessage());
            
            // Intentar encontrarla como último recurso
            $existingId = $this->buscarHabilidadPorNombre($nombre);
            if ($existingId) {
                return $existingId;
            }
        }
        
        return 1; // ID por defecto si todo falla
    }
    
    /**
     * Buscar una habilidad por nombre en la base de datos
     */
    private function buscarHabilidadPorNombre($nombre) {
        try {
            // Método 1: Con el cliente Supabase
            $search = $this->supabase->from('habilidades')
                                    ->select('id')
                                    ->eq('nombre', $nombre)
                                    ->execute();
                                    
            if (isset($search->data[0]->id)) {
                $newId = $search->data[0]->id;
                // Actualizar el mapa local
                $this->mapaHabilidades[$nombre] = $newId;
                error_log("Habilidad encontrada con ID: " . $newId);
                return $newId;
            }
            
            // Método 2: Con supabaseFetch
            $result = supabaseFetch('habilidades', 'id', ['nombre' => $nombre]);
            if (is_array($result) && !empty($result) && isset($result[0]['id'])) {
                $newId = $result[0]['id'];
                $this->mapaHabilidades[$nombre] = $newId;
                error_log("Habilidad encontrada con supabaseFetch, ID: " . $newId);
                return $newId;
            }
            
            // Método 3: Búsqueda directa con curl (búsqueda parcial)
            $url = SUPABASE_URL . "/rest/v1/habilidades?select=id&nombre=ilike." . urlencode("%$nombre%");
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response);
                if (is_array($data) && !empty($data) && isset($data[0]->id)) {
                    $newId = $data[0]->id;
                    $this->mapaHabilidades[$nombre] = $newId;
                    error_log("Habilidad encontrada con búsqueda directa, ID: " . $newId);
                    return $newId;
                }
            }
        } catch (Exception $e) {
            error_log("Error al buscar habilidad: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Insertar habilidad directamente usando curl
     */
    private function insertarHabilidadDirecta($habilidadData) {
        $url = SUPABASE_URL . "/rest/v1/habilidades";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUPABASE_KEY,
            'apikey: ' . SUPABASE_KEY,
            'Prefer: return=representation'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($habilidadData));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("Inserción directa de habilidad - HTTP Code: $httpCode, Respuesta: " . substr($response, 0, 500));
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response);
            if (is_array($data) && !empty($data) && isset($data[0]->id)) {
                return $data[0]->id;
            }
        }
        
        return null;
    }
    
    /**
     * Determina la categoría de una habilidad basado en su nombre
     * (Método público para poder usar desde controladores)
     */
    public function determinarCategoria($nombre) {
        $nombre = strtolower($nombre);
        
        if (strpos($nombre, 'tcp') !== false || strpos($nombre, 'http') !== false || 
            strpos($nombre, 'dns') !== false || strpos($nombre, 'dhcp') !== false ||
            strpos($nombre, 'ftp') !== false || strpos($nombre, 'smtp') !== false ||
            strpos($nombre, 'pop3') !== false || strpos($nombre, 'imap') !== false ||
            strpos($nombre, 'ssh') !== false || strpos($nombre, 'rdp') !== false) {
            return 'protocolos_red';
        } else if (strpos($nombre, 'router') !== false || strpos($nombre, 'switch') !== false || 
                   strpos($nombre, 'firewall') !== false || strpos($nombre, 'access point') !== false ||
                   strpos($nombre, 'load balancer') !== false || strpos($nombre, 'modem') !== false ||
                   strpos($nombre, 'repeater') !== false || strpos($nombre, 'vpn') !== false ||
                   strpos($nombre, 'bridge') !== false || strpos($nombre, 'gateway') !== false) {
            return 'dispositivos_red';
        } else if (strpos($nombre, 'security') !== false || strpos($nombre, 'seguridad') !== false || 
                   strpos($nombre, 'encrypt') !== false || strpos($nombre, 'ids') !== false ||
                   strpos($nombre, 'ips') !== false || strpos($nombre, 'monitor') !== false ||
                   strpos($nombre, 'penetration') !== false || strpos($nombre, 'ssl') !== false ||
                   strpos($nombre, 'tls') !== false || strpos($nombre, 'auth') !== false) {
            return 'ciberseguridad';
        } else if (strpos($nombre, 'cisco') !== false || strpos($nombre, 'wireshark') !== false || 
                   strpos($nombre, 'nmap') !== false || strpos($nombre, 'putty') !== false ||
                   strpos($nombre, 'gns3') !== false || strpos($nombre, 'solar') !== false ||
                   strpos($nombre, 'nagios') !== false || strpos($nombre, 'openvpn') !== false ||
                   strpos($nombre, 'vmware') !== false || strpos($nombre, 'packet') !== false) {
            return 'herramientas_desarrollo';
        } else if (strpos($nombre, 'ccna') !== false || strpos($nombre, 'ccnp') !== false || 
                   strpos($nombre, 'comptia') !== false || strpos($nombre, 'cissp') !== false ||
                   strpos($nombre, 'cism') !== false || strpos($nombre, 'jncia') !== false ||
                   strpos($nombre, 'aws') !== false || strpos($nombre, 'microsoft') !== false ||
                   strpos($nombre, 'fortinet') !== false || strpos($nombre, 'certi') !== false) {
            return 'certificaciones';
        } else if (strpos($nombre, 'java') !== false || strpos($nombre, 'python') !== false || 
                   strpos($nombre, 'c++') !== false || strpos($nombre, 'c#') !== false ||
                   strpos($nombre, 'javascript') !== false || strpos($nombre, 'php') !== false ||
                   strpos($nombre, 'ruby') !== false || strpos($nombre, 'go') !== false ||
                   strpos($nombre, 'swift') !== false || strpos($nombre, 'kotlin') !== false) {
            return 'lenguajes_programacion';
        } else if (strpos($nombre, 'sql') !== false || strpos($nombre, 'mysql') !== false || 
                   strpos($nombre, 'postgresql') !== false || strpos($nombre, 'mongodb') !== false ||
                   strpos($nombre, 'oracle') !== false || strpos($nombre, 'sqlite') !== false ||
                   strpos($nombre, 'redis') !== false || strpos($nombre, 'db2') !== false ||
                   strpos($nombre, 'cassandra') !== false || strpos($nombre, 'firebase') !== false) {
            return 'bases_datos';
        } else if (strpos($nombre, 'aws') !== false || strpos($nombre, 'azure') !== false || 
                   strpos($nombre, 'gcp') !== false || strpos($nombre, 'cloud') !== false ||
                   strpos($nombre, 'docker') !== false || strpos($nombre, 'kubernetes') !== false ||
                   strpos($nombre, 'serverless') !== false || strpos($nombre, 'lambda') !== false ||
                   strpos($nombre, 's3') !== false || strpos($nombre, 'ec2') !== false) {
            return 'servicios_cloud';
        } else if (strpos($nombre, 'windows') !== false || strpos($nombre, 'linux') !== false || 
                   strpos($nombre, 'unix') !== false || strpos($nombre, 'macos') !== false ||
                   strpos($nombre, 'android') !== false || strpos($nombre, 'ios') !== false ||
                   strpos($nombre, 'ubuntu') !== false || strpos($nombre, 'debian') !== false ||
                   strpos($nombre, 'redhat') !== false || strpos($nombre, 'fedora') !== false) {
            return 'sistemas_operativos';
        }
        
        // Categoría por defecto
        return 'otras_habilidades';
    }
    
    /**
     * Guarda una habilidad de candidato en la base de datos
     */
    public function guardarHabilidadCandidato($candidatoId, $nombreHabilidad, $nivel) {
        // No guardar habilidades con nivel "ninguno"
        if ($nivel === 'ninguno') {
            return true;
        }
        
        // Mapeo de niveles frontend a valores permitidos en la BD
        // Según el constraint: nivel::text = ANY (ARRAY['principiante', 'intermedio', 'avanzado', 'experto']::text[])
        $mapeoNiveles = [
            'malo' => 'principiante',
            'regular' => 'intermedio',
            'bueno' => 'avanzado'
        ];
        
        // Convertir nivel si existe en el mapeo
        if (isset($mapeoNiveles[$nivel])) {
            $nivelOriginal = $nivel;
            $nivel = $mapeoNiveles[$nivel];
            error_log("Nivel mapeado: $nivelOriginal -> $nivel");
        }
        
        // Log detallado para depuración
        error_log("Guardando habilidad para candidato ID: $candidatoId, Habilidad: $nombreHabilidad, Nivel: $nivel");
        
        // Validar el ID del candidato
        if (!$candidatoId || !is_numeric($candidatoId) || $candidatoId <= 0) {
            error_log("ID de candidato inválido: $candidatoId");
            return false;
        }
        
        // Obtener el ID de la habilidad
        $habilidadId = $this->obtenerIdPorNombre($nombreHabilidad);
        
        if (!$habilidadId) {
            error_log("No se pudo obtener ID para la habilidad: $nombreHabilidad. Intentando crear nueva habilidad.");
            $habilidadId = $this->insertarNuevaHabilidad($nombreHabilidad);
            
            if (!$habilidadId) {
                error_log("ERROR: No se pudo crear/obtener habilidad para: $nombreHabilidad");
                return false;
            }
        }
        
        error_log("ID de la habilidad $nombreHabilidad: $habilidadId");
        
        // Verificar si ya existe esta relación para no duplicar
        $existeRelacion = false;
        try {
            $existeRelacion = $this->verificarRelacionExistente($candidatoId, $habilidadId);
            
            if ($existeRelacion) {
                error_log("La relación entre candidato $candidatoId y habilidad $habilidadId ya existe. Actualizando nivel.");
                return $this->actualizarNivelHabilidad($candidatoId, $habilidadId, $nivel);
            }
        } catch (Exception $e) {
            error_log("Error al verificar relación existente: " . $e->getMessage());
            // Continuamos con la inserción de todos modos
        }
        
        $habilidadData = [
            'candidato_id' => intval($candidatoId), // Asegurar que sea entero
            'habilidad_id' => intval($habilidadId), // Asegurar que sea entero
            'nivel' => $nivel,
            'anios_experiencia' => 1 // Valor por defecto
        ];
        
        error_log("Datos a insertar: " . json_encode($habilidadData));
        
        $exito = false;
        
        // Método 1: Usar supabaseInsert (más confiable)
        try {
            error_log("Intentando método 1: supabaseInsert");
            $resultadoInsert = supabaseInsert('candidato_habilidades', $habilidadData);
            
            if (!isset($resultadoInsert['error'])) {
                error_log("Éxito con supabaseInsert: " . json_encode(array_slice($resultadoInsert, 0, 2)));
                $exito = true;
            } else {
                error_log("Error con supabaseInsert: " . json_encode($resultadoInsert['error']));
                
                // Método 2: Inserción directa con curl
                error_log("Intentando método 2: curl directo");
                $exitoCurl = $this->insertarHabilidadCandidatoDirecto($habilidadData);
                
                if ($exitoCurl) {
                    error_log("Éxito con curl directo");
                    $exito = true;
                } else {
                    error_log("Error con curl directo");
                    
                    // Método 3: Usar la API de manera más básica
                    try {
                        error_log("Intentando método 3: inserción básica");
                        
                        // Si la relación ya existe pero falló la actualización, intentar eliminarla primero
                        if ($existeRelacion) {
                            $this->eliminarRelacion($candidatoId, $habilidadId);
                        }
                        
                        // Inserción con fetch plano
                        $insertUrl = SUPABASE_URL . "/rest/v1/candidato_habilidades";
                        $headers = [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . SUPABASE_KEY,
                            'apikey: ' . SUPABASE_KEY
                        ];
                        
                        $ch = curl_init($insertUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($habilidadData));
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode >= 200 && $httpCode < 300) {
                            error_log("Éxito con inserción básica: HTTP Code $httpCode");
                            $exito = true;
                        } else {
                            error_log("Error con inserción básica: HTTP Code $httpCode, Respuesta: $response");
                        }
                    } catch (Exception $e3) {
                        error_log("Error en método de inserción básica: " . $e3->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Excepción al guardar habilidad: " . $e->getMessage());
            
            // Último intento con curl si hubo una excepción
            try {
                error_log("Último intento con curl después de excepción");
                $exitoCurl = $this->insertarHabilidadCandidatoDirecto($habilidadData);
                if ($exitoCurl) {
                    error_log("Éxito con último intento curl");
                    $exito = true;
                }
            } catch (Exception $e2) {
                error_log("Error en último intento: " . $e2->getMessage());
            }
        }
        
        if ($exito) {
            error_log("ÉXITO: Habilidad guardada - Candidato: $candidatoId, Habilidad: $nombreHabilidad, Nivel: $nivel");
            return true;
        } else {
            error_log("ERROR FINAL: No se pudo guardar la habilidad después de intentar todos los métodos");
            return false;
        }
    }
    
    /**
     * Elimina una relación candidato-habilidad existente
     */
    private function eliminarRelacion($candidatoId, $habilidadId) {
        try {
            error_log("Intentando eliminar relación previa entre candidato $candidatoId y habilidad $habilidadId");
            
            $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
            $headers = [
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Eliminación de relación: HTTP Code $httpCode");
            return ($httpCode >= 200 && $httpCode < 300);
        } catch (Exception $e) {
            error_log("Error al eliminar relación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si ya existe una relación entre candidato y habilidad
     */
    private function verificarRelacionExistente($candidatoId, $habilidadId) {
        try {
            $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return !empty($data);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error al verificar relación existente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza el nivel de una habilidad existente
     */
    private function actualizarNivelHabilidad($candidatoId, $habilidadId, $nivel) {
        // Mapeo de niveles frontend a valores permitidos en la BD
        // Según el constraint: nivel::text = ANY (ARRAY['principiante', 'intermedio', 'avanzado', 'experto']::text[])
        $mapeoNiveles = [
            'malo' => 'principiante',
            'regular' => 'intermedio',
            'bueno' => 'avanzado'
        ];
        
        // Convertir nivel si existe en el mapeo
        if (isset($mapeoNiveles[$nivel])) {
            $nivelOriginal = $nivel;
            $nivel = $mapeoNiveles[$nivel];
            error_log("Nivel mapeado en actualización: $nivelOriginal -> $nivel");
        }
    
        try {
            $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . SUPABASE_KEY,
                'apikey: ' . SUPABASE_KEY,
                'Prefer: return=minimal'
            ];
            
            $data = [
                'nivel' => $nivel,
                'anios_experiencia' => 1
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("Nivel de habilidad actualizado exitosamente");
                return true;
            } else {
                error_log("Error al actualizar nivel: HTTP Code $httpCode, Respuesta: $response");
                return false;
            }
        } catch (Exception $e) {
            error_log("Error al actualizar nivel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método alternativo para insertar habilidad de candidato usando petición directa
     */
    private function insertarHabilidadCandidatoDirecto($habilidadData) {
        $url = SUPABASE_URL . "/rest/v1/candidato_habilidades";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUPABASE_KEY,
            'apikey: ' . SUPABASE_KEY,
            'Prefer: return=representation'
        ];
        
        // Mapeo de niveles frontend a valores permitidos en la BD
        // Según el constraint: nivel::text = ANY (ARRAY['principiante', 'intermedio', 'avanzado', 'experto']::text[])
        $mapeoNiveles = [
            'malo' => 'principiante',
            'regular' => 'intermedio',
            'bueno' => 'avanzado'
        ];
        
        // Convertir nivel si existe en el mapeo
        if (isset($habilidadData['nivel']) && isset($mapeoNiveles[$habilidadData['nivel']])) {
            $nivelOriginal = $habilidadData['nivel'];
            $habilidadData['nivel'] = $mapeoNiveles[$nivelOriginal];
            error_log("Nivel mapeado en inserción directa: $nivelOriginal -> " . $habilidadData['nivel']);
        }
        
        // Asegurar que los IDs sean enteros
        if (isset($habilidadData['candidato_id'])) {
            $habilidadData['candidato_id'] = intval($habilidadData['candidato_id']);
        }
        if (isset($habilidadData['habilidad_id'])) {
            $habilidadData['habilidad_id'] = intval($habilidadData['habilidad_id']);
        }
        
        error_log("Datos para inserción directa: " . json_encode($habilidadData));
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($habilidadData));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);
        
        error_log("Inserción directa - HTTP Code: $httpCode");
        
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Inserción directa exitosa. Respuesta: " . substr($response, 0, 500));
            return true;
        } else {
            error_log("Error en inserción directa. HTTP Code: $httpCode");
            error_log("Error curl: " . $errorCurl);
            error_log("Respuesta: " . substr($response, 0, 500));
            
            // Si hay conflicto, intentar con una resolución alternativa
            if ($httpCode === 409) { // Conflict error
                error_log("Conflicto detectado, intentando con método alternativo...");
                return $this->manejarConflictoInsercion($habilidadData);
            }
            
            return false;
        }
    }
    
    /**
     * Método para manejar conflictos en la inserción de habilidad-candidato
     */
    private function manejarConflictoInsercion($habilidadData) {
        // Primero, verificar si ya existe el registro para actualizar
        $candidatoId = $habilidadData['candidato_id'];
        $habilidadId = $habilidadData['habilidad_id'];
        
        $url = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SUPABASE_KEY,
            'apikey: ' . SUPABASE_KEY
        ];
        
        // Verificar si existe
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            // Si existe, actualizarlo
            if (is_array($data) && !empty($data)) {
                error_log("Registro encontrado, intentando actualizar");
                
                // Añadir cabecera Prefer para actualización
                $headers['Prefer'] = 'return=representation';
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'nivel' => $habilidadData['nivel'],
                    'anios_experiencia' => $habilidadData['anios_experiencia']
                ]));
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    error_log("Actualización exitosa");
                    return true;
                } else {
                    error_log("Error al actualizar: HTTP Code $httpCode");
                    return false;
                }
            } else {
                // Si no existe, intentar con otro método de inserción
                error_log("No se encontró el registro a pesar del conflicto. Intentando otro método de inserción");
                
                // Usar DELETE + POST para forzar la inserción
                $deleteUrl = SUPABASE_URL . "/rest/v1/candidato_habilidades?candidato_id=eq.$candidatoId&habilidad_id=eq.$habilidadId";
                $ch = curl_init($deleteUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_exec($ch);
                curl_close($ch);
                
                // Ahora intentar nuevamente la inserción
                $insertUrl = SUPABASE_URL . "/rest/v1/candidato_habilidades";
                $headers['Prefer'] = 'return=representation';
                
                $ch = curl_init($insertUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($habilidadData));
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                return ($httpCode >= 200 && $httpCode < 300);
            }
        }
        
        return false;
    }
    
    /**
     * Método para insertar múltiples habilidades de un candidato
     * @param int $candidatoId ID del candidato
     * @param array $habilidades Arreglo asociativo de habilidades con formato [nombreHabilidad => nivel]
     * @return array Resultado con conteo de éxitos y errores
     */
    public function insertarHabilidadesCandidato($candidatoId, $habilidades) {
        $resultado = [
            'exitos' => 0,
            'errores' => 0,
            'detalles' => []
        ];
        
        if (empty($candidatoId) || !is_array($habilidades) || empty($habilidades)) {
            error_log("insertarHabilidadesCandidato: Parámetros inválidos");
            $resultado['detalles'][] = "Parámetros inválidos";
            return $resultado;
        }
        
        // Mapeo de niveles frontend a valores permitidos en la BD
        // Según el constraint: nivel::text = ANY (ARRAY['principiante', 'intermedio', 'avanzado', 'experto']::text[])
        $mapeoNiveles = [
            'malo' => 'principiante',
            'regular' => 'intermedio',
            'bueno' => 'avanzado',
            'ninguno' => null  // No insertaremos esta habilidad
        ];
        
        foreach ($habilidades as $nombreHabilidad => $nivelOriginal) {
            // Si el nivel es 'ninguno', saltamos esta habilidad
            if ($nivelOriginal === 'ninguno') {
                $resultado['detalles'][] = "Habilidad $nombreHabilidad: nivel 'ninguno', omitiendo";
                continue;
            }
            
            // Mapear el nivel a los valores permitidos en la BD
            $nivel = isset($mapeoNiveles[$nivelOriginal]) ? $mapeoNiveles[$nivelOriginal] : $nivelOriginal;
            
            // Verificar que la habilidad tenga un ID válido
            $habilidadId = $this->obtenerIdPorNombre($nombreHabilidad);
            
            if (!$habilidadId) {
                error_log("No se pudo obtener ID para la habilidad: $nombreHabilidad. Intentando crear.");
                $habilidadId = $this->insertarNuevaHabilidad($nombreHabilidad);
                
                if (!$habilidadId) {
                    error_log("ERROR: No se pudo crear/obtener ID para habilidad: $nombreHabilidad");
                    $resultado['errores']++;
                    $resultado['detalles'][] = "No se pudo obtener ID para: $nombreHabilidad";
                    continue;
                }
            }
            
            error_log("Insertando habilidad: $nombreHabilidad (ID: $habilidadId) con nivel: $nivel (original: $nivelOriginal)");
            
            // Datos para insertar
            $datos = [
                'candidato_id' => intval($candidatoId),
                'habilidad_id' => intval($habilidadId),
                'nivel' => $nivel,
                'anios_experiencia' => 1
            ];
            
            // Intentar insertar con el método directo
            if ($this->insertarHabilidadCandidatoDirecto($datos)) {
                error_log("Habilidad guardada correctamente: $nombreHabilidad");
                $resultado['exitos']++;
                $resultado['detalles'][] = "Insertada: $nombreHabilidad - $nivel";
            } else {
                // Intento alternativo con la función guardarHabilidadCandidato
                if ($this->guardarHabilidadCandidato($candidatoId, $nombreHabilidad, $nivel)) {
                    error_log("Habilidad guardada con método alternativo: $nombreHabilidad");
                    $resultado['exitos']++;
                    $resultado['detalles'][] = "Insertada (alt): $nombreHabilidad - $nivel";
                } else {
                    error_log("ERROR: No se pudo guardar la habilidad: $nombreHabilidad");
                    $resultado['errores']++;
                    $resultado['detalles'][] = "Error al insertar: $nombreHabilidad";
                }
            }
        }
        
        return $resultado;
    }
}

/**
 * Función de compatibilidad con el código anterior
 */
function insertarHabilidadesCandidato($candidatoId, $habilidades) {
    $manager = new Habilidades();
    return $manager->insertarHabilidadesCandidato($candidatoId, $habilidades);
}

/**
 * Función de compatibilidad con el código anterior
 */
function obtenerIdHabilidad($nombreHabilidad) {
    $manager = new Habilidades();
    return $manager->obtenerIdPorNombre($nombreHabilidad);
}
?>
