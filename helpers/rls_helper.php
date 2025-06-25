<?php
/**
 * Esta función intenta obtener un perfil de usuario de manera robusta,
 * incluso si hay problemas con las políticas RLS
 * 
 * @param string $userId ID del usuario
 * @param string|null $email Email del usuario (opcional)
 * @return array Perfil de usuario o array con error
 */
function obtenerPerfilRobusto($userId, $email = null) {
    // Intentar primero con el método estándar
    $userProfile = supabaseFetch('perfiles', '*', ['user_id' => $userId]);
    
    // Si hay un error de recursión RLS, intentar con el bypass
    if (isset($userProfile['error']) && 
        (strpos($userProfile['error'], 'infinite recursion') !== false || 
         strpos($userProfile['error'], 'recursion') !== false)) {
        
        error_log("Detectada recursión en RLS al obtener perfil para $userId. Intentando bypass...");
        
        // Intentar con bypass
        $userProfile = getProfileBypass($userId, $email);
        
        // Si el bypass también falla, registrar el error
        if (isset($userProfile['error'])) {
            error_log("Error en bypass RLS para usuario $userId: " . json_encode($userProfile['error']));
            return [
                'error' => 'Error al cargar el perfil de usuario',
                'details' => 'Problema con las políticas de seguridad RLS',
                'diagnose_url' => '../config/diagnostico_rls.php?user_id=' . urlencode($userId)
            ];
        }
    }
    
    return $userProfile;
}

/**
 * Determina si un error está relacionado con políticas RLS
 * 
 * @param array $response Respuesta de la API con error
 * @return boolean true si es un error de RLS, false en caso contrario
 */
function esErrorRLS($response) {
    if (!isset($response['error'])) {
        return false;
    }
    
    $errorLowercase = strtolower($response['error']);
    
    // Términos comunes en errores de RLS
    $rlsErrorTerms = [
        'infinite recursion',
        'recursion',
        'policy',
        'rls',
        'row level security',
        'permission denied'
    ];
    
    foreach ($rlsErrorTerms as $term) {
        if (strpos($errorLowercase, $term) !== false) {
            return true;
        }
    }
    
    return false;
}
?>
