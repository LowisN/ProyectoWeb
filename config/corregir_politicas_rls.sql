-- Script para corregir políticas RLS con problemas de recursión
-- Este script debe ser ejecutado en el editor SQL de Supabase

-- Primero, eliminar todas las políticas existentes para evitar conflictos
DROP POLICY IF EXISTS "perfiles_policy" ON perfiles;
DROP POLICY IF EXISTS "perfiles_select_policy" ON perfiles;
DROP POLICY IF EXISTS "perfiles_insert_policy" ON perfiles;
DROP POLICY IF EXISTS "perfiles_update_policy" ON perfiles;
DROP POLICY IF EXISTS "perfiles_delete_policy" ON perfiles;

DROP POLICY IF EXISTS "candidatos_policy" ON candidatos;
DROP POLICY IF EXISTS "candidatos_select_policy" ON candidatos;
DROP POLICY IF EXISTS "candidatos_insert_policy" ON candidatos;
DROP POLICY IF EXISTS "candidatos_update_policy" ON candidatos;
DROP POLICY IF EXISTS "candidatos_delete_policy" ON candidatos;

DROP POLICY IF EXISTS "reclutadores_policy" ON reclutadores;
DROP POLICY IF EXISTS "reclutadores_select_policy" ON reclutadores;
DROP POLICY IF EXISTS "reclutadores_insert_policy" ON reclutadores;
DROP POLICY IF EXISTS "reclutadores_update_policy" ON reclutadores;
DROP POLICY IF EXISTS "reclutadores_delete_policy" ON reclutadores;

DROP POLICY IF EXISTS "empresas_policy" ON empresas;
DROP POLICY IF EXISTS "empresas_select_policy" ON empresas;
DROP POLICY IF EXISTS "empresas_insert_policy" ON empresas;
DROP POLICY IF EXISTS "empresas_update_policy" ON empresas;
DROP POLICY IF EXISTS "empresas_delete_policy" ON empresas;

-- Asegurar que RLS está habilitado para todas las tablas
ALTER TABLE perfiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE candidatos ENABLE ROW LEVEL SECURITY;
ALTER TABLE reclutadores ENABLE ROW LEVEL SECURITY;
ALTER TABLE empresas ENABLE ROW LEVEL SECURITY;

-- Políticas para la tabla perfiles
-- Permitir acceso de lectura a todos los usuarios autenticados
CREATE POLICY "perfiles_select_policy" ON perfiles FOR SELECT
  USING (auth.uid() IS NOT NULL);

-- Solo permitir inserción si el usuario está autenticado y es su propio perfil
CREATE POLICY "perfiles_insert_policy" ON perfiles FOR INSERT
  WITH CHECK (auth.uid() = user_id);

-- Solo permitir actualización de su propio perfil
CREATE POLICY "perfiles_update_policy" ON perfiles FOR UPDATE
  USING (auth.uid() = user_id);

-- Políticas para la tabla candidatos
-- Permitir lectura a todos los usuarios autenticados
CREATE POLICY "candidatos_select_policy" ON candidatos FOR SELECT
  USING (auth.uid() IS NOT NULL);

-- Permitir inserción si está relacionado con su perfil
CREATE POLICY "candidatos_insert_policy" ON candidatos FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM perfiles 
      WHERE perfiles.id = candidatos.perfil_id 
      AND perfiles.user_id = auth.uid()
    )
  );

-- Permitir actualización solo de sus propios datos
CREATE POLICY "candidatos_update_policy" ON candidatos FOR UPDATE
  USING (
    EXISTS (
      SELECT 1 FROM perfiles 
      WHERE perfiles.id = candidatos.perfil_id 
      AND perfiles.user_id = auth.uid()
    )
  );

-- Políticas para la tabla reclutadores
-- Permitir lectura a todos los usuarios autenticados
CREATE POLICY "reclutadores_select_policy" ON reclutadores FOR SELECT
  USING (auth.uid() IS NOT NULL);

-- Permitir inserción si está relacionado con su perfil
CREATE POLICY "reclutadores_insert_policy" ON reclutadores FOR INSERT
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM perfiles 
      WHERE perfiles.id = reclutadores.perfil_id 
      AND perfiles.user_id = auth.uid()
    )
  );

-- Permitir actualización solo de sus propios datos
CREATE POLICY "reclutadores_update_policy" ON reclutadores FOR UPDATE
  USING (
    EXISTS (
      SELECT 1 FROM perfiles 
      WHERE perfiles.id = reclutadores.perfil_id 
      AND perfiles.user_id = auth.uid()
    )
  );

-- Políticas para la tabla empresas
-- Permitir lectura a todos los usuarios autenticados
CREATE POLICY "empresas_select_policy" ON empresas FOR SELECT
  USING (auth.uid() IS NOT NULL);

-- Permitir inserción si el usuario está autenticado
CREATE POLICY "empresas_insert_policy" ON empresas FOR INSERT
  WITH CHECK (auth.uid() IS NOT NULL);

-- Permitir actualización si está asociado a la empresa a través de un reclutador
CREATE POLICY "empresas_update_policy" ON empresas FOR UPDATE
  USING (
    EXISTS (
      SELECT 1 FROM reclutadores
      JOIN perfiles ON perfiles.id = reclutadores.perfil_id
      WHERE reclutadores.empresa_id = empresas.id
      AND perfiles.user_id = auth.uid()
    )
  );

-- Crear una función RPC para obtener perfiles sin pasar por RLS
-- Esta función debe ser ejecutada con privilegios elevados o como administrador
CREATE OR REPLACE FUNCTION public.get_profile_bypassing_rls(
  p_user_id uuid DEFAULT NULL,
  p_email text DEFAULT NULL
)
RETURNS SETOF perfiles
LANGUAGE plpgsql
SECURITY DEFINER -- Esto hace que se ejecute con los privilegios del creador
AS $$
BEGIN
  IF p_user_id IS NOT NULL THEN
    RETURN QUERY SELECT * FROM perfiles WHERE user_id = p_user_id;
  ELSIF p_email IS NOT NULL THEN
    RETURN QUERY SELECT * FROM perfiles WHERE email = p_email;
  ELSE
    RAISE EXCEPTION 'Se debe proporcionar user_id o email';
  END IF;
END;
$$;
