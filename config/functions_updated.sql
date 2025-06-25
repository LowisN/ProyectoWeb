-- Funciones SQL personalizadas para ChambaNet (Versión actualizada)
-- Estas funciones implementan lógica de negocio específica

-- Función para calcular el porcentaje de coincidencia entre un candidato y una vacante
CREATE OR REPLACE FUNCTION calcular_match_candidato_vacante(
    p_candidato_id UUID,
    p_vacante_id UUID
) RETURNS NUMERIC AS $$
DECLARE
    total_requisitos INTEGER := 0;
    requisitos_cumplidos NUMERIC := 0;
    porcentaje_match NUMERIC := 0;
    experiencia_candidato INTEGER;
    experiencia_requerida INTEGER;
    bonus_experiencia NUMERIC := 0;
    v_registro RECORD;
    v_nivel_candidato VARCHAR;
    v_peso NUMERIC;
BEGIN
    -- Obtener experiencia del candidato y experiencia requerida
    SELECT anios_experiencia INTO experiencia_candidato 
    FROM candidatos 
    WHERE id = p_candidato_id;
    
    SELECT anios_experiencia_requeridos INTO experiencia_requerida 
    FROM vacantes 
    WHERE id = p_vacante_id;
    
    -- Calcular bonus por experiencia (máximo 15%)
    IF experiencia_candidato >= experiencia_requerida THEN
        bonus_experiencia := LEAST((experiencia_candidato - experiencia_requerida) * 3, 15);
    ELSE
        bonus_experiencia := 0;
    END IF;
    
    -- Contar total de requisitos en la vacante
    SELECT COUNT(*) INTO total_requisitos 
    FROM requisitos_vacante 
    WHERE vacante_id = p_vacante_id;
    
    -- Si no hay requisitos, el match se basa solo en experiencia
    IF total_requisitos = 0 THEN
        RETURN 50 + bonus_experiencia;
    END IF;
    
    -- Para cada requisito de la vacante
    FOR v_registro IN 
        SELECT rv.tecnologia, rv.nivel_requerido 
        FROM requisitos_vacante rv
        WHERE rv.vacante_id = p_vacante_id
    LOOP
        -- Buscar si el candidato tiene la tecnología requerida
        SELECT nivel INTO v_nivel_candidato 
        FROM conocimientos_candidato 
        WHERE candidato_id = p_candidato_id 
        AND tecnologia = v_registro.tecnologia;
        
        -- Si el candidato tiene la tecnología
        IF v_nivel_candidato IS NOT NULL THEN
            -- Determinar peso según nivel
            CASE 
                WHEN v_nivel_candidato = 'bueno' AND v_registro.nivel_requerido = 'bueno' THEN
                    v_peso := 1.0; -- Coincidencia perfecta
                WHEN v_nivel_candidato = 'bueno' AND v_registro.nivel_requerido = 'regular' THEN
                    v_peso := 1.0; -- Supera lo requerido
                WHEN v_nivel_candidato = 'bueno' AND v_registro.nivel_requerido = 'malo' THEN
                    v_peso := 1.0; -- Supera lo requerido
                WHEN v_nivel_candidato = 'regular' AND v_registro.nivel_requerido = 'bueno' THEN
                    v_peso := 0.5; -- No alcanza lo requerido
                WHEN v_nivel_candidato = 'regular' AND v_registro.nivel_requerido = 'regular' THEN
                    v_peso := 1.0; -- Coincidencia perfecta
                WHEN v_nivel_candidato = 'regular' AND v_registro.nivel_requerido = 'malo' THEN
                    v_peso := 1.0; -- Supera lo requerido
                WHEN v_nivel_candidato = 'malo' AND v_registro.nivel_requerido = 'bueno' THEN
                    v_peso := 0.25; -- Muy por debajo de lo requerido
                WHEN v_nivel_candidato = 'malo' AND v_registro.nivel_requerido = 'regular' THEN
                    v_peso := 0.5; -- No alcanza lo requerido
                WHEN v_nivel_candidato = 'malo' AND v_registro.nivel_requerido = 'malo' THEN
                    v_peso := 1.0; -- Coincidencia perfecta
                ELSE
                    v_peso := 0;
            END CASE;
            
            requisitos_cumplidos := requisitos_cumplidos + v_peso;
        END IF;
    END LOOP;
    
    -- Calcular porcentaje de match (85% por requisitos + 15% por bonus de experiencia)
    IF total_requisitos > 0 THEN
        porcentaje_match := (requisitos_cumplidos / total_requisitos) * 85 + bonus_experiencia;
    ELSE
        porcentaje_match := 50 + bonus_experiencia;
    END IF;
    
    -- Garantizar que el resultado esté en el rango 0-100
    RETURN GREATEST(LEAST(porcentaje_match, 100), 0);
END;
$$ LANGUAGE plpgsql;

-- Trigger para actualizar automáticamente el porcentaje de match al crear una postulación
CREATE OR REPLACE FUNCTION actualizar_match_postulacion()
RETURNS TRIGGER AS $$
BEGIN
    NEW.match_percentage := calcular_match_candidato_vacante(NEW.candidato_id, NEW.vacante_id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_actualizar_match_postulacion
BEFORE INSERT OR UPDATE ON postulaciones
FOR EACH ROW EXECUTE FUNCTION actualizar_match_postulacion();

-- Función para actualizar match cuando se modifican los conocimientos de un candidato
CREATE OR REPLACE FUNCTION actualizar_match_conocimientos()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar match_percentage en todas las postulaciones del candidato
    UPDATE postulaciones
    SET match_percentage = calcular_match_candidato_vacante(candidato_id, vacante_id),
        updated_at = NOW()
    WHERE candidato_id = NEW.candidato_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_actualizar_match_conocimientos
AFTER INSERT OR UPDATE OR DELETE ON conocimientos_candidato
FOR EACH ROW EXECUTE FUNCTION actualizar_match_conocimientos();

-- Función para actualizar match cuando se modifican los requisitos de una vacante
CREATE OR REPLACE FUNCTION actualizar_match_requisitos()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar match_percentage en todas las postulaciones a esta vacante
    UPDATE postulaciones
    SET match_percentage = calcular_match_candidato_vacante(candidato_id, vacante_id),
        updated_at = NOW()
    WHERE vacante_id = NEW.vacante_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_actualizar_match_requisitos
AFTER INSERT OR UPDATE OR DELETE ON requisitos_vacante
FOR EACH ROW EXECUTE FUNCTION actualizar_match_requisitos();

-- Función para actualizar el match cuando cambia la experiencia del candidato
CREATE OR REPLACE FUNCTION actualizar_match_experiencia_candidato()
RETURNS TRIGGER AS $$
BEGIN
    -- Solo si cambió el campo anios_experiencia
    IF NEW.anios_experiencia <> OLD.anios_experiencia THEN
        -- Actualizar match_percentage en todas las postulaciones del candidato
        UPDATE postulaciones
        SET match_percentage = calcular_match_candidato_vacante(candidato_id, vacante_id),
            updated_at = NOW()
        WHERE candidato_id = NEW.id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_actualizar_match_experiencia_candidato
AFTER UPDATE ON candidatos
FOR EACH ROW EXECUTE FUNCTION actualizar_match_experiencia_candidato();

-- Función para actualizar el match cuando cambia la experiencia requerida de la vacante
CREATE OR REPLACE FUNCTION actualizar_match_experiencia_vacante()
RETURNS TRIGGER AS $$
BEGIN
    -- Solo si cambió el campo anios_experiencia_requeridos
    IF NEW.anios_experiencia_requeridos <> OLD.anios_experiencia_requeridos THEN
        -- Actualizar match_percentage en todas las postulaciones a esta vacante
        UPDATE postulaciones
        SET match_percentage = calcular_match_candidato_vacante(candidato_id, vacante_id),
            updated_at = NOW()
        WHERE vacante_id = NEW.id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trig_actualizar_match_experiencia_vacante
AFTER UPDATE ON vacantes
FOR EACH ROW EXECUTE FUNCTION actualizar_match_experiencia_vacante();
