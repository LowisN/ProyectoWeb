-- Script SQL para actualizar usuarios sin tipo definido o con tipo inconsistente

-- Verificar si tenemos algún perfil sin tipo de usuario/perfil
SELECT id, user_id, tipo_usuario, tipo_perfil 
FROM perfiles 
WHERE tipo_usuario IS NULL OR tipo_perfil IS NULL
   OR tipo_usuario = '' OR tipo_perfil = '';

-- Actualizar perfiles donde tipo_perfil es NULL pero tipo_usuario existe
UPDATE perfiles 
SET tipo_perfil = tipo_usuario 
WHERE tipo_perfil IS NULL AND tipo_usuario IS NOT NULL;

-- Actualizar perfiles donde tipo_usuario es NULL pero tipo_perfil existe
UPDATE perfiles 
SET tipo_usuario = tipo_perfil 
WHERE tipo_usuario IS NULL AND tipo_perfil IS NOT NULL;

-- Normalizar valores admin -> administrador
UPDATE perfiles 
SET tipo_usuario = 'administrador', tipo_perfil = 'administrador' 
WHERE tipo_usuario = 'admin' OR tipo_perfil = 'admin';

-- Actualizar perfiles sin tipo pero que están en tabla candidatos
UPDATE perfiles p
SET tipo_usuario = 'candidato', tipo_perfil = 'candidato'
WHERE (tipo_usuario IS NULL OR tipo_perfil IS NULL OR tipo_usuario = '' OR tipo_perfil = '')
AND EXISTS (SELECT 1 FROM candidatos c WHERE c.perfil_id = p.id);

-- Actualizar perfiles sin tipo pero que están en tabla reclutadores
UPDATE perfiles p
SET tipo_usuario = 'reclutador', tipo_perfil = 'reclutador'
WHERE (tipo_usuario IS NULL OR tipo_perfil IS NULL OR tipo_usuario = '' OR tipo_perfil = '')
AND EXISTS (SELECT 1 FROM reclutadores r WHERE r.perfil_id = p.id);

-- Verificar si aún tenemos perfiles sin tipo
SELECT id, user_id, tipo_usuario, tipo_perfil 
FROM perfiles 
WHERE tipo_usuario IS NULL OR tipo_perfil IS NULL
   OR tipo_usuario = '' OR tipo_perfil = ''
   OR tipo_usuario NOT IN ('administrador', 'candidato', 'reclutador')
   OR tipo_perfil NOT IN ('administrador', 'candidato', 'reclutador');
