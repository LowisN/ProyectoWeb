-- Funciones SQL personalizadas para ChambaNet
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
        -- Verificar si el candidato tiene esta tecnología
        SELECT nivel INTO v_nivel_candidato
        FROM conocimientos_candidato
        WHERE candidato_id = p_candidato_id AND tecnologia = v_registro.tecnologia;
        
        -- Si el candidato tiene la tecnología
        IF v_nivel_candidato IS NOT NULL THEN
            -- Asignar peso según nivel del candidato vs nivel requerido
            CASE 
                WHEN v_registro.nivel_requerido = 'malo' THEN
                    -- Si el requisito es bajo, cualquier nivel suma
                    v_peso := 1.0;
                    
                WHEN v_registro.nivel_requerido = 'regular' THEN
                    -- Si el requisito es medio, nivel bajo suma parcial
                    IF v_nivel_candidato = 'malo' THEN
                        v_peso := 0.5;
                    ELSE -- regular o bueno
                        v_peso := 1.0;
                    END IF;
                    
                WHEN v_registro.nivel_requerido = 'bueno' THEN
                    -- Si el requisito es alto, se evalúa por nivel
                    CASE v_nivel_candidato
                        WHEN 'malo' THEN v_peso := 0.25;
                        WHEN 'regular' THEN v_peso := 0.75;
                        WHEN 'bueno' THEN v_peso := 1.0;
                        ELSE v_peso := 0;
                    END CASE;
            END CASE;
            
            requisitos_cumplidos := requisitos_cumplidos + v_peso;
        END IF;
    END LOOP;
    
    -- Calcular porcentaje (base 85% por conocimientos + hasta 15% por experiencia)
    porcentaje_match := (requisitos_cumplidos / total_requisitos * 85) + bonus_experiencia;
    
    -- No permitir valores negativos o mayores a 100
    RETURN GREATEST(0, LEAST(porcentaje_match, 100));
END;
$$ LANGUAGE plpgsql;

-- Función para recalcular todos los matches de un candidato
CREATE OR REPLACE FUNCTION recalcular_matches_candidato(
    p_candidato_id UUID
) RETURNS INTEGER AS $$
DECLARE
    v_count INTEGER := 0;
    v_vacante RECORD;
    v_match NUMERIC;
BEGIN
    -- Para cada vacante activa
    FOR v_vacante IN 
        SELECT id FROM vacantes 
        WHERE estado = 'activa'
    LOOP
        -- Calcular el match
        v_match := calcular_match_candidato_vacante(p_candidato_id, v_vacante.id);
        
        -- Verificar si ya existe una postulación
        IF EXISTS (
            SELECT 1 FROM postulaciones 
            WHERE candidato_id = p_candidato_id AND vacante_id = v_vacante.id
        ) THEN
            -- Actualizar el porcentaje de match
            UPDATE postulaciones
            SET match_percentage = v_match
            WHERE candidato_id = p_candidato_id AND vacante_id = v_vacante.id;
        ELSE
            -- Crear una nueva postulación con estado pendiente
            INSERT INTO postulaciones (
                vacante_id, candidato_id, match_percentage, estado
            ) VALUES (
                v_vacante.id, p_candidato_id, v_match, 'pendiente'
            );
        END IF;
        
        v_count := v_count + 1;
    END LOOP;
    
    RETURN v_count;
END;
$$ LANGUAGE plpgsql;

-- Función para recalcular todos los matches para una vacante
CREATE OR REPLACE FUNCTION recalcular_matches_vacante(
    p_vacante_id UUID
) RETURNS INTEGER AS $$
DECLARE
    v_count INTEGER := 0;
    v_candidato RECORD;
    v_match NUMERIC;
BEGIN
    -- Solo proceder si la vacante está activa
    IF NOT EXISTS (
        SELECT 1 FROM vacantes 
        WHERE id = p_vacante_id AND estado = 'activa'
    ) THEN
        RETURN 0;
    END IF;
    
    -- Para cada candidato
    FOR v_candidato IN 
        SELECT id FROM candidatos
    LOOP
        -- Calcular el match
        v_match := calcular_match_candidato_vacante(v_candidato.id, p_vacante_id);
        
        -- Verificar si ya existe una postulación
        IF EXISTS (
            SELECT 1 FROM postulaciones 
            WHERE candidato_id = v_candidato.id AND vacante_id = p_vacante_id
        ) THEN
            -- Actualizar el porcentaje de match
            UPDATE postulaciones
            SET match_percentage = v_match
            WHERE candidato_id = v_candidato.id AND vacante_id = p_vacante_id;
        ELSE
            -- Crear una nueva postulación con estado pendiente
            INSERT INTO postulaciones (
                vacante_id, candidato_id, match_percentage, estado
            ) VALUES (
                p_vacante_id, v_candidato.id, v_match, 'pendiente'
            );
        END IF;
        
        v_count := v_count + 1;
    END LOOP;
    
    RETURN v_count;
END;
$$ LANGUAGE plpgsql;

-- Trigger para actualizar matches cuando se actualiza la experiencia de un candidato
CREATE OR REPLACE FUNCTION trigger_actualizar_matches_candidato()
RETURNS TRIGGER AS $$
BEGIN
    -- Si cambió la experiencia
    IF NEW.anios_experiencia <> OLD.anios_experiencia THEN
        PERFORM recalcular_matches_candidato(NEW.id);
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER candidato_update_matches
AFTER UPDATE OF anios_experiencia ON candidatos
FOR EACH ROW
EXECUTE FUNCTION trigger_actualizar_matches_candidato();

-- Trigger para actualizar matches cuando se agrega o actualiza un conocimiento de candidato
CREATE OR REPLACE FUNCTION trigger_actualizar_matches_conocimiento()
RETURNS TRIGGER AS $$
BEGIN
    PERFORM recalcular_matches_candidato(NEW.candidato_id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER conocimiento_candidato_matches
AFTER INSERT OR UPDATE ON conocimientos_candidato
FOR EACH ROW
EXECUTE FUNCTION trigger_actualizar_matches_conocimiento();

-- Trigger para actualizar matches cuando se crea una vacante
CREATE OR REPLACE FUNCTION trigger_actualizar_matches_vacante()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.estado = 'activa' THEN
        PERFORM recalcular_matches_vacante(NEW.id);
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER vacante_insert_matches
AFTER INSERT ON vacantes
FOR EACH ROW
EXECUTE FUNCTION trigger_actualizar_matches_vacante();

-- Trigger para actualizar matches cuando se agrega un requisito a una vacante
CREATE OR REPLACE FUNCTION trigger_actualizar_matches_requisito()
RETURNS TRIGGER AS $$
BEGIN
    PERFORM recalcular_matches_vacante(NEW.vacante_id);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER requisito_vacante_matches
AFTER INSERT ON requisitos_vacante
FOR EACH ROW
EXECUTE FUNCTION trigger_actualizar_matches_requisito();
