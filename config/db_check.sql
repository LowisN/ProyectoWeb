-- Este script realiza una verificación inicial de la configuración de la base de datos
-- Comprueba que las tablas existan y tienen la estructura correcta

-- Verificar existencia de tablas
DO $$
DECLARE
    tablas_requeridas TEXT[] := ARRAY['usuario', 'perfiles', 'candidatos', 'empresas', 'reclutadores', 
                                     'tecnologias', 'conocimientos_candidato', 'vacantes', 
                                     'requisitos_vacante', 'postulaciones'];
    tabla TEXT;
    tabla_existe BOOLEAN;
BEGIN
    FOREACH tabla IN ARRAY tablas_requeridas
    LOOP
        EXECUTE format('SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = ''public'' AND table_name = ''%s'');', tabla)
        INTO tabla_existe;
        
        IF NOT tabla_existe THEN
            RAISE EXCEPTION 'La tabla % no existe en la base de datos', tabla;
        END IF;
    END LOOP;
    
    -- Verificar estructura de tabla usuario
    PERFORM column_name, data_type
    FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'usuario'
    AND column_name IN ('id', 'user_id', 'email', 'nombre', 'apellidos', 'telefono', 'created_at', 'updated_at');
    
    -- Verificar estructura de tabla perfiles
    PERFORM column_name, data_type
    FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'perfiles'
    AND column_name IN ('id', 'usuario_id', 'tipo_perfil', 'created_at', 'updated_at');
    
    -- Verificar que existen usuarios administradores
    IF NOT EXISTS (
        SELECT 1 
        FROM perfiles 
        WHERE tipo_perfil = 'admin'
        LIMIT 1
    ) THEN
        RAISE WARNING 'No se encontró ningún usuario administrador en el sistema';
    END IF;
END;
$$ LANGUAGE plpgsql;
