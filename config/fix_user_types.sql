-- Script para actualizar y corregir los tipos de usuario en la base de datos

-- 1. Asegurar que la tabla tiene la columna tipo_perfil
DO $$
BEGIN
    -- Verificar si existe la columna tipo_perfil
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'perfiles' AND column_name = 'tipo_perfil'
    ) THEN
        -- Añadir la columna si no existe
        ALTER TABLE perfiles ADD COLUMN tipo_perfil TEXT;
    END IF;
END $$;

-- 2. Actualizar tipo_perfil basado en tipo_usuario para todos los registros donde tipo_perfil es NULL
UPDATE perfiles
SET tipo_perfil = tipo_usuario
WHERE tipo_perfil IS NULL AND tipo_usuario IS NOT NULL;

-- 3. Corregir valores específicos
-- Normalizar 'admin' a 'administrador'
UPDATE perfiles
SET tipo_perfil = 'administrador'
WHERE tipo_perfil = 'admin';

-- Actualizar campos NULL basado en otros datos
-- Para candidatos
UPDATE perfiles p
SET tipo_perfil = 'candidato'
WHERE tipo_perfil IS NULL
AND EXISTS (SELECT 1 FROM candidatos c WHERE c.perfil_id = p.id);

-- Para reclutadores
UPDATE perfiles p
SET tipo_perfil = 'reclutador'
WHERE tipo_perfil IS NULL
AND EXISTS (SELECT 1 FROM reclutadores r WHERE r.perfil_id = p.id);

-- 4. Verificar registros sin tipo_perfil después de las actualizaciones
-- Esta consulta nos mostrará cualquier perfil que aún no tenga tipo_perfil
SELECT id, user_id, tipo_usuario, tipo_perfil
FROM perfiles
WHERE tipo_perfil IS NULL;

-- 5. Función para mantener tipo_usuario y tipo_perfil sincronizados
-- (solo si se necesita mantener retrocompatibilidad)
CREATE OR REPLACE FUNCTION sync_tipo_usuario_perfil()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Si se inserta con tipo_perfil pero sin tipo_usuario
        IF NEW.tipo_perfil IS NOT NULL AND NEW.tipo_usuario IS NULL THEN
            NEW.tipo_usuario := NEW.tipo_perfil;
        -- Si se inserta con tipo_usuario pero sin tipo_perfil
        ELSIF NEW.tipo_usuario IS NOT NULL AND NEW.tipo_perfil IS NULL THEN
            NEW.tipo_perfil := NEW.tipo_usuario;
        END IF;
    ELSIF TG_OP = 'UPDATE' THEN
        -- Si se actualiza tipo_perfil pero no tipo_usuario
        IF NEW.tipo_perfil IS DISTINCT FROM OLD.tipo_perfil AND NEW.tipo_usuario = OLD.tipo_usuario THEN
            NEW.tipo_usuario := NEW.tipo_perfil;
        -- Si se actualiza tipo_usuario pero no tipo_perfil
        ELSIF NEW.tipo_usuario IS DISTINCT FROM OLD.tipo_usuario AND NEW.tipo_perfil = OLD.tipo_perfil THEN
            NEW.tipo_perfil := NEW.tipo_usuario;
        END IF;
    END IF;
    
    -- Normalizar 'admin' a 'administrador'
    IF NEW.tipo_perfil = 'admin' THEN
        NEW.tipo_perfil := 'administrador';
    END IF;
    
    IF NEW.tipo_usuario = 'admin' THEN
        NEW.tipo_usuario := 'administrador';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Crear o reemplazar el trigger
DROP TRIGGER IF EXISTS sync_tipo_usuario_perfil_trigger ON perfiles;
CREATE TRIGGER sync_tipo_usuario_perfil_trigger
BEFORE INSERT OR UPDATE ON perfiles
FOR EACH ROW
EXECUTE FUNCTION sync_tipo_usuario_perfil();

-- 6. Comentario final
COMMENT ON COLUMN perfiles.tipo_perfil IS 'Tipo de perfil del usuario: administrador, candidato o reclutador. Este es el campo principal que debe utilizarse.';
COMMENT ON COLUMN perfiles.tipo_usuario IS 'Campo legacy mantenido para compatibilidad. Preferir usar tipo_perfil.';
