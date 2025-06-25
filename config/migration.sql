-- Script de migración desde estructura antigua a nueva
-- Este script se debe ejecutar después de crear las nuevas tablas
-- para migrar los datos existentes en producción

-- Migración de datos de perfiles a usuario y perfiles nuevos
DO $$
DECLARE
    v_registro RECORD;
BEGIN
    -- Crear usuarios para cada perfil existente
    FOR v_registro IN 
        SELECT p.id as perfil_id, p.user_id, p.email, p.tipo_usuario, 
               COALESCE(c.nombre, r.nombre) as nombre, 
               COALESCE(c.apellidos, r.apellidos) as apellidos,
               COALESCE(c.telefono, r.telefono) as telefono
        FROM perfiles p
        LEFT JOIN candidatos c ON p.id = c.perfil_id
        LEFT JOIN reclutadores r ON p.id = r.perfil_id
    LOOP
        -- Insertar en la tabla usuario
        INSERT INTO usuario (user_id, email, nombre, apellidos, telefono)
        VALUES (v_registro.user_id, v_registro.email, v_registro.nombre, v_registro.apellidos, v_registro.telefono);
        
        -- Insertar en la tabla perfiles nueva
        INSERT INTO perfiles (usuario_id, tipo_perfil)
        VALUES (
            (SELECT id FROM usuario WHERE user_id = v_registro.user_id),
            v_registro.tipo_usuario
        );
    END LOOP;
    
    -- Migrar datos específicos de candidatos (excluyendo los campos que ahora están en usuario)
    FOR v_registro IN 
        SELECT p.id as perfil_id, c.fecha_nacimiento, c.direccion, c.titulo,
               c.anios_experiencia, c.acerca_de, c.cv_url
        FROM perfiles p
        JOIN candidatos c ON p.id = c.perfil_id
    LOOP
        -- Encontrar el ID del nuevo perfil basado en el user_id asociado
        UPDATE candidatos
        SET fecha_nacimiento = v_registro.fecha_nacimiento,
            direccion = v_registro.direccion,
            titulo = v_registro.titulo,
            anios_experiencia = v_registro.anios_experiencia,
            acerca_de = v_registro.acerca_de,
            cv_url = v_registro.cv_url
        WHERE perfil_id = (
            SELECT p2.id
            FROM usuario u
            JOIN perfiles p2 ON u.id = p2.usuario_id
            JOIN perfiles p_old ON p_old.user_id = u.user_id
            WHERE p_old.id = v_registro.perfil_id
        );
    END LOOP;
    
    -- Migrar datos específicos de reclutadores (excluyendo los campos que ahora están en usuario)
    FOR v_registro IN 
        SELECT p.id as perfil_id, r.empresa_id, r.puesto
        FROM perfiles p
        JOIN reclutadores r ON p.id = r.perfil_id
    LOOP
        -- Encontrar el ID del nuevo perfil basado en el user_id asociado
        UPDATE reclutadores
        SET empresa_id = v_registro.empresa_id,
            puesto = v_registro.puesto
        WHERE perfil_id = (
            SELECT p2.id
            FROM usuario u
            JOIN perfiles p2 ON u.id = p2.usuario_id
            JOIN perfiles p_old ON p_old.user_id = u.user_id
            WHERE p_old.id = v_registro.perfil_id
        );
    END LOOP;
END;
$$ LANGUAGE plpgsql;
