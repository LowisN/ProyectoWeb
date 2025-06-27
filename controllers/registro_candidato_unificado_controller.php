<?php
session_start();
require_once '../config/supabase.php';

// Inicializar o continuar el estado de registro
if (!isset($_SESSION['registro_candidato']) || isset($_GET['reset'])) {
    $_SESSION['registro_candidato'] = [
        'paso_actual' => 1,
        'datos_personales' => [],
        'datos_academicos' => [],
        'datos_profesionales' => [],
        'habilidades' => []
    ];
}

// Comprobar si se están guardando los datos del paso 1 (datos personales)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso']) && $_POST['paso'] == '1') {
    // Obtener y filtrar datos del formulario
    $_SESSION['registro_candidato']['datos_personales'] = [
        'nombre' => filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING),
        'apellidos' => filter_input(INPUT_POST, 'apellidos', FILTER_SANITIZE_STRING),
        'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'contrasena' => $_POST['contrasena'],
        'confirmar_contrasena' => $_POST['confirmar_contrasena'],
        'telefono' => filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING),
        'fecha_nacimiento' => filter_input(INPUT_POST, 'fecha_nacimiento'),
        'direccion' => filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING)
    ];
    
    // Validaciones de datos personales
    $datos = $_SESSION['registro_candidato']['datos_personales'];
    
    if (empty($datos['nombre']) || empty($datos['apellidos']) || empty($datos['email']) || 
        empty($datos['contrasena']) || empty($datos['confirmar_contrasena']) || 
        empty($datos['telefono']) || empty($datos['fecha_nacimiento']) || empty($datos['direccion'])) {
        header('Location: ../paginas/registro_candidato.php?error=Todos los campos obligatorios deben ser completados');
        exit;
    }
    
    // Verificar que las contraseñas coinciden
    if ($datos['contrasena'] !== $datos['confirmar_contrasena']) {
        header('Location: ../paginas/registro_candidato.php?error=Las contraseñas no coinciden');
        exit;
    }
    
    // Verificar longitud de la contraseña
    if (strlen($datos['contrasena']) < 8) {
        header('Location: ../paginas/registro_candidato.php?error=La contraseña debe tener al menos 8 caracteres');
        exit;
    }
    
    // Actualizar el paso actual
    $_SESSION['registro_candidato']['paso_actual'] = 2;
    
    // Redirigir al siguiente paso
    header('Location: ../paginas/candidato/datosEyP_candidato.php');
    exit;
}

// Comprobar si se están guardando los datos del paso 2 (datos académicos y profesionales)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso']) && $_POST['paso'] == '2') {
    // Obtener datos académicos
    $_SESSION['registro_candidato']['datos_academicos'] = [
        'institucion' => filter_input(INPUT_POST, 'institucion', FILTER_SANITIZE_STRING),
        'titulo' => filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING),
        'area' => filter_input(INPUT_POST, 'area', FILTER_SANITIZE_STRING),
        'en_curso' => isset($_POST['en_curso']) ? true : false,
        'descripcion' => filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING),
        'fecha_inicio' => filter_input(INPUT_POST, 'fecha_inicio'),
        'fecha_fin' => filter_input(INPUT_POST, 'fecha_fin')
    ];
    
    // Obtener datos profesionales
    $_SESSION['registro_candidato']['datos_profesionales'] = [
        'empresa' => filter_input(INPUT_POST, 'empresa', FILTER_SANITIZE_STRING),
        'puesto' => filter_input(INPUT_POST, 'puesto', FILTER_SANITIZE_STRING),
        'actualL' => isset($_POST['actualL']) ? true : false,
        'fecha_inicio_laboral' => filter_input(INPUT_POST, 'fecha_inicio_laboral'),
        'fecha_fin_laboral' => filter_input(INPUT_POST, 'fecha_fin_laboral'),
        'anios_experiencia' => filter_input(INPUT_POST, 'anios_experiencia', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]])
    ];
    
    // Validación básica
    if (empty($_SESSION['registro_candidato']['datos_academicos']['titulo']) || 
        empty($_SESSION['registro_candidato']['datos_profesionales']['puesto'])) {
        header('Location: ../paginas/candidato/datosEyP_candidato.php?error=Completa al menos el título y puesto laboral');
        exit;
    }
    
    // Actualizar el paso actual
    $_SESSION['registro_candidato']['paso_actual'] = 3;
    
    // Redirigir al siguiente paso
    header('Location: ../paginas/candidato/reg_hab.php');
    exit;
}

// Comprobar si se están guardando los datos del paso 3 (habilidades) y finalizando el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paso']) && $_POST['paso'] == '3') {
    // Cargar el modelo de habilidades para obtener las tecnologías de la base de datos
    require_once '../models/habilidades.php';
    require_once '../config/SupabaseClient.php';
    
    $habilidadesManager = new Habilidades();
    $todasHabilidades = $habilidadesManager->obtenerTodasHabilidades();
    $habilidadesObjetos = [];
    $habilidadesNombres = [];
    
    // Procesar las habilidades de la base de datos
    foreach ($todasHabilidades as $habilidad) {
        if (isset($habilidad->nombre)) {
            // Asegurarnos de que tenga categoría
            if (!isset($habilidad->categoria) || empty($habilidad->categoria)) {
                $habilidad->categoria = $habilidadesManager->determinarCategoria($habilidad->nombre);
            }
            $habilidadesObjetos[] = $habilidad;
            $habilidadesNombres[] = $habilidad->nombre;
        }
    }
    
    // Si no hay habilidades en la base de datos, usar las predefinidas
    if (empty($habilidadesNombres)) {
        $habilidades = [
            'protocolos_red' => [
                'TCP/IP', 'UDP', 'HTTP/HTTPS', 'DNS', 'DHCP', 'FTP', 'SMTP', 'POP3/IMAP', 'SSH', 'RDP'
            ],
            'dispositivos_red' => [
                'Routers', 'Switches', 'Firewalls', 'Access Points', 'Load Balancers', 'Modems', 
                'Repeaters', 'VPN Concentrators', 'Network Bridges', 'Gateways'
            ],
            'seguridad_redes' => [
                'VPN', 'Encrypting', 'Firewalls Hardware', 'Firewalls Software', 'IDS/IPS', 
                'Network Monitoring', 'Penetration Testing', 'SSL/TLS', 'Authentication Systems', 
                'Network Security Policies'
            ],
            'software' => [
                'Cisco IOS', 'Wireshark', 'Nmap', 'PuTTY', 'GNS3', 'SolarWinds', 'Nagios', 
                'OpenVPN', 'VMware', 'Packet Tracer'
            ],
            'certificaciones' => [
                'CCNA', 'CCNP', 'CompTIA Network+', 'CompTIA Security+', 'CISSP', 'CISM', 'JNCIA', 
                'AWS Certified Networking', 'Microsoft Certified: Azure Network Engineer', 'Fortinet NSE'
            ]
        ];
        
        // Aplanar el array de habilidades
        foreach ($habilidades as $tecnologias) {
            foreach ($tecnologias as $tecnologia) {
                $habilidadesNombres[] = $tecnologia;
            }
        }
    }
    
    // Recopilar habilidades seleccionadas
    $habilidades_guardadas = [];
    
    // Depuración para ver todas las entradas POST
    error_log("Entradas POST recibidas: " . print_r($_POST, true));
    
    // Procesar POST para todas las habilidades posibles
    foreach ($_POST as $key => $value) {
        // Verificar si la clave parece un nombre de tecnología (convertido para form)
        if (preg_match('/^[a-z0-9_]+$/', $key) && in_array($value, ['malo', 'regular', 'bueno'])) {
            // Convertir la clave de vuelta al formato de nombre de habilidad
            $tecnologiaOriginal = null;
            
            // Buscar coincidencia exacta primero
            foreach ($habilidadesNombres as $nombre) {
                $nombreKey = str_replace(['/', ' ', '-', '.'], '_', strtolower($nombre));
                
                if ($nombreKey === $key) {
                    $tecnologiaOriginal = $nombre;
                    break;
                }
            }
            
            // Si no hay coincidencia exacta, buscar similitudes parciales
            if (!$tecnologiaOriginal) {
                // Reconstruir el posible nombre original
                $posibleNombre = str_replace('_', ' ', $key);
                
                foreach ($habilidadesNombres as $nombre) {
                    // Comparar versiones normalizadas
                    $nombreNormalizado = strtolower($nombre);
                    $posibleNombreNormalizado = strtolower($posibleNombre);
                    
                    if ($nombreNormalizado === $posibleNombreNormalizado || 
                        stripos($nombreNormalizado, $posibleNombreNormalizado) !== false || 
                        stripos($posibleNombreNormalizado, $nombreNormalizado) !== false) {
                        $tecnologiaOriginal = $nombre;
                        break;
                    }
                }
                
                // Si todavía no hay coincidencia, usar el nombre reconstruido
                if (!$tecnologiaOriginal) {
                    $tecnologiaOriginal = ucwords($posibleNombre);
                }
            }
            
            // Guardar la habilidad con su nivel si no es "ninguno"
            if ($value !== 'ninguno') {
                $habilidades_guardadas[$tecnologiaOriginal] = $value;
                error_log("Habilidad identificada y guardada: $tecnologiaOriginal con nivel $value");
            }
        }
    }
    
    // Depurar habilidades
    error_log("Total de habilidades guardadas: " . count($habilidades_guardadas));
    error_log("Habilidades: " . json_encode($habilidades_guardadas));
    
    // Guardar habilidades en la sesión
    $_SESSION['registro_candidato']['habilidades'] = $habilidades_guardadas;
    
    // ----------------------
    // INICIAR EL PROCESO DE REGISTRO EN BASE DE DATOS
    // ----------------------
    
    // 1. Registrar usuario en Supabase Auth
    $datos_personales = $_SESSION['registro_candidato']['datos_personales'];
    $datos_academicos = $_SESSION['registro_candidato']['datos_academicos'];
    $datos_profesionales = $_SESSION['registro_candidato']['datos_profesionales'];
    
    $userData = [
        'nombre' => $datos_personales['nombre'],
        'apellidos' => $datos_personales['apellidos']
    ];
    
    $authResponse = supabaseSignUp($datos_personales['email'], $datos_personales['contrasena'], $userData);
    
    // Comprobar si hay errores en la autenticación
    if (isset($authResponse['error']) || isset($authResponse['code'])) {
        $errorMessage = isset($authResponse['error_description']) ? $authResponse['error_description'] : 'Error al registrar el usuario. Inténtalo de nuevo.';
        header('Location: ../paginas/candidato/reg_hab.php?error=' . urlencode($errorMessage));
        exit;
    }
    
    // Obtener el ID del usuario recién registrado
    $userId = null;
    
    if (isset($authResponse['user']) && isset($authResponse['user']['id'])) {
        $userId = $authResponse['user']['id'];
    } elseif (isset($authResponse['id'])) {
        $userId = $authResponse['id'];
    } elseif (isset($authResponse['data']) && isset($authResponse['data']['user']) && isset($authResponse['data']['user']['id'])) {
        $userId = $authResponse['data']['user']['id'];
    }
    
    if (empty($userId)) {
        error_log("ERROR: No se pudo extraer el ID del usuario de la respuesta de Supabase Auth: " . json_encode($authResponse));
        header('Location: ../paginas/candidato/reg_hab.php?error=Error al obtener el identificador del usuario');
        exit;
    }
    
    error_log("Usuario creado en Supabase Auth con ID: $userId");
    
    // 2. Crear perfil de candidato
    $perfilData = [
        'user_id' => $userId,
        'tipo_usuario' => 'candidato'
    ];
    
    error_log("Intentando crear perfil con datos: " . json_encode($perfilData));
    $perfilResponse = supabaseInsert('perfiles', $perfilData);
    
    // Comprobar si hay errores al crear el perfil
    if (isset($perfilResponse['error'])) {
        error_log("Error al crear perfil: " . json_encode($perfilResponse));
        header('Location: ../paginas/candidato/reg_hab.php?error=' . urlencode('Error al crear el perfil de usuario: ' . $perfilResponse['message']));
        exit;
    }
    
    // Obtener el ID del perfil
    $perfilId = null;
    
    if (is_array($perfilResponse) && !empty($perfilResponse) && isset($perfilResponse[0]['id'])) {
        $perfilId = $perfilResponse[0]['id'];
    } elseif (isset($perfilResponse['id'])) {
        $perfilId = $perfilResponse['id'];
    } else {
        // Buscar el perfil recién creado
        $perfilesResult = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
        
        if (is_array($perfilesResult) && !empty($perfilesResult) && isset($perfilesResult[0]['id'])) {
            $perfilId = $perfilesResult[0]['id'];
        }
    }
    
    if (empty($perfilId)) {
        error_log("No se pudo obtener el ID del perfil creado. Respuesta: " . json_encode($perfilResponse));
        header('Location: ../paginas/candidato/reg_hab.php?error=Error: No se pudo identificar el perfil creado');
        exit;
    }
    
    error_log("Perfil creado con ID: $perfilId");
    
    // 3. Crear datos del candidato
    $candidatoData = [
        'perfil_id' => $perfilId,
        'telefono' => $datos_personales['telefono'],
        'fecha_nacimiento' => $datos_personales['fecha_nacimiento'],
        'direccion' => $datos_personales['direccion'],
        'titulo' => $datos_academicos['titulo'] ?: null,
        'anios_experiencia' => intval($datos_profesionales['anios_experiencia'])
    ];
    
    error_log("Intentando crear candidato con datos: " . json_encode($candidatoData));
    $candidatoResponse = supabaseInsert('candidatos', $candidatoData);
    
    // Comprobar si hay errores al crear los datos del candidato
    if (isset($candidatoResponse['error'])) {
        error_log("Error al crear candidato: " . json_encode($candidatoResponse));
        header('Location: ../paginas/candidato/reg_hab.php?error=' . urlencode('Error al guardar los datos del candidato: ' . $candidatoResponse['message']));
        exit;
    }
    
    // Obtener el ID del candidato
    $candidatoId = null;
    
    if (is_array($candidatoResponse) && !empty($candidatoResponse) && isset($candidatoResponse[0]['id'])) {
        $candidatoId = $candidatoResponse[0]['id'];
    } elseif (isset($candidatoResponse['id'])) {
        $candidatoId = $candidatoResponse['id'];
    } else {
        // Buscar el candidato recién creado
        $candidatosResult = supabaseFetch('candidatos', '*', ['perfil_id' => $perfilId]);
        
        if (is_array($candidatosResult) && !empty($candidatosResult) && isset($candidatosResult[0]['id'])) {
            $candidatoId = $candidatosResult[0]['id'];
        }
    }
    
    if (empty($candidatoId)) {
        error_log("No se pudo obtener el ID del candidato creado. Respuesta: " . json_encode($candidatoResponse));
    } else {
        error_log("Candidato creado exitosamente con ID: " . $candidatoId);
    
        // 4. Insertar datos de educación (si existe una tabla para ello)
        if (!empty($datos_academicos['institucion'])) {
            // Usamos el nombre correcto de la tabla
            $tablaEducacion = 'educacion';
            error_log("Usando tabla de educación: $tablaEducacion");
            
            $educacionData = [
                'candidato_id' => $candidatoId,
                'institucion' => $datos_academicos['institucion'],
                'titulo' => $datos_academicos['titulo'],
                'area' => $datos_academicos['area'],
                'fecha_inicio' => $datos_academicos['fecha_inicio'],
                'fecha_fin' => $datos_academicos['fecha_fin']
            ];
            
            // Añadir el campo en_curso solo si es necesario según la estructura
            if ($datos_academicos['en_curso']) {
                $educacionData['en_curso'] = true;
            } else {
                $educacionData['en_curso'] = false;
            }
            
            // Añadir descripción solo si tiene valor
            if (!empty($datos_academicos['descripcion'])) {
                $educacionData['descripcion'] = $datos_academicos['descripcion'];
            }
            
            $educacionResponse = supabaseInsert($tablaEducacion, $educacionData);
            error_log("Respuesta al crear educación: " . json_encode($educacionResponse));
        }
        
        // 5. Insertar datos de experiencia laboral (si existe una tabla para ello)
        if (!empty($datos_profesionales['empresa'])) {
            // Usamos el nombre correcto de la tabla
            $tablaExperiencia = 'experiencia_laboral';
            error_log("Usando tabla de experiencia laboral: $tablaExperiencia");
            
            $laboralData = [
                'candidato_id' => $candidatoId,
                'empresa' => $datos_profesionales['empresa'],
                'puesto' => $datos_profesionales['puesto'],
                'fecha_inicio' => $datos_profesionales['fecha_inicio_laboral'],
                'fecha_fin' => $datos_profesionales['fecha_fin_laboral']
            ];
            
            // Añadir el campo actual (booleano)
            if ($datos_profesionales['actualL']) {
                $laboralData['actual'] = true;
            } else {
                $laboralData['actual'] = false;
            }
            
            // Verificar si la descripción está disponible
            if (!empty($datos_profesionales['descripcion'])) {
                $laboralData['descripcion'] = $datos_profesionales['descripcion'];
            }
            
            $laboralResponse = supabaseInsert($tablaExperiencia, $laboralData);
            error_log("Respuesta al crear experiencia laboral: " . json_encode($laboralResponse));
        }
        
        // 6. Insertar habilidades del candidato
        $errores_habilidades = 0;
        $habilidades_guardadas_count = 0;
        
        // Verificamos que haya habilidades guardadas
        if (!empty($_SESSION['registro_candidato']['habilidades'])) {
            // Cargar el modelo de habilidades
            require_once '../models/habilidades.php';
            require_once '../config/SupabaseClient.php';
            
            // Log de depuración
            error_log("Candidato ID para habilidades: " . $candidatoId);
            error_log("Total de habilidades a insertar: " . count($_SESSION['registro_candidato']['habilidades']));
            error_log("Lista de habilidades a insertar: " . json_encode($_SESSION['registro_candidato']['habilidades']));
            
            // Verificar que la tabla candidato_habilidades existe
            $testResponse = supabaseRequest("/rest/v1/candidato_habilidades?limit=1");
            if (isset($testResponse['error'])) {
                error_log("ADVERTENCIA: La tabla candidato_habilidades no existe o no es accesible: " . json_encode($testResponse));
            } else {
                error_log("La tabla candidato_habilidades existe y es accesible");
            }
            
            // Usar la clase de habilidades
            $habilidadesManager = new Habilidades();
            
            // Verificar cada habilidad antes de insertar
            foreach ($_SESSION['registro_candidato']['habilidades'] as $nombreHabilidad => $nivel) {
                // Verificar que la habilidad tenga un ID válido
                $habilidadId = $habilidadesManager->obtenerIdPorNombre($nombreHabilidad);
                
                if (!$habilidadId) {
                    error_log("No se pudo obtener ID para la habilidad: $nombreHabilidad. Intentando crear.");
                    $habilidadId = $habilidadesManager->insertarNuevaHabilidad($nombreHabilidad);
                    
                    if (!$habilidadId) {
                        error_log("ERROR: No se pudo crear/obtener ID para habilidad: $nombreHabilidad");
                        $errores_habilidades++;
                        continue;
                    }
                }
                
                error_log("Insertando habilidad: $nombreHabilidad (ID: $habilidadId) con nivel: $nivel");
                
                if ($habilidadesManager->guardarHabilidadCandidato($candidatoId, $nombreHabilidad, $nivel)) {
                    error_log("Habilidad guardada correctamente: $nombreHabilidad");
                    $habilidades_guardadas_count++;
                } else {
                    error_log("ERROR: No se pudo guardar la habilidad: $nombreHabilidad");
                    $errores_habilidades++;
                    
                    // Intento adicional con inserción directa
                    $datos = [
                        'candidato_id' => intval($candidatoId),
                        'habilidad_id' => intval($habilidadId),
                        'nivel' => $nivel,
                        'anios_experiencia' => 1
                    ];
                    
                    $resultado = supabaseInsert('candidato_habilidades', $datos);
                    if (!isset($resultado['error'])) {
                        error_log("Recuperación: Habilidad insertada con método alternativo: $nombreHabilidad");
                        $habilidades_guardadas_count++;
                        $errores_habilidades--;  // Corregir el conteo
                    }
                }
            }
            
            error_log("Resultado de inserción de habilidades: " . $habilidades_guardadas_count . " exitosas, " . $errores_habilidades . " errores");
        } else {
            error_log("No hay habilidades para guardar");
        }
        
        error_log("Se guardaron $habilidades_guardadas_count habilidades con $errores_habilidades errores");
    }
    
    // Crear un resumen del registro
    $resumen = [
        'user_id' => $userId,
        'perfil_id' => $perfilId,
        'candidato_id' => $candidatoId,
        'habilidades_guardadas' => $habilidades_guardadas_count,
        'educacion_guardada' => !empty($datos_academicos['institucion']),
        'experiencia_guardada' => !empty($datos_profesionales['empresa'])
    ];
    
    error_log("RESUMEN DE REGISTRO: " . json_encode($resumen));
    
    // Limpiar los datos de registro de la sesión
    unset($_SESSION['registro_candidato']);
    
    // Registro exitoso, redirigir a la página de inicio de sesión
    header('Location: ../index.php?success=Registro exitoso. Ahora puedes iniciar sesión con tus nuevas credenciales.');
    exit;
}

// Si se accede directamente al controlador sin datos POST, redirigir al paso correspondiente
$paso_actual = $_SESSION['registro_candidato']['paso_actual'];

switch ($paso_actual) {
    case 1:
        header('Location: ../paginas/registro_candidato.php');
        break;
    case 2:
        header('Location: ../paginas/candidato/datosEyP_candidato.php');
        break;
    case 3:
        header('Location: ../paginas/candidato/reg_hab.php');
        break;
    default:
        header('Location: ../paginas/registro_candidato.php?reset=1');
        break;
}
exit;
?>
