-- Políticas de Seguridad de Row Level Security (RLS) para ChambaNet en Supabase
-- Estas políticas controlan qué usuarios pueden leer, crear, actualizar o eliminar cada fila

------------------------------------------------------------------------
-- HABILITAR RLS EN TODAS LAS TABLAS
------------------------------------------------------------------------

-- Habilitar RLS en todas las tablas
ALTER TABLE perfiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE candidatos ENABLE ROW LEVEL SECURITY;
ALTER TABLE empresas ENABLE ROW LEVEL SECURITY;
ALTER TABLE reclutadores ENABLE ROW LEVEL SECURITY;
ALTER TABLE tecnologias ENABLE ROW LEVEL SECURITY;
ALTER TABLE conocimientos_candidato ENABLE ROW LEVEL SECURITY;
ALTER TABLE vacantes ENABLE ROW LEVEL SECURITY;
ALTER TABLE requisitos_vacante ENABLE ROW LEVEL SECURITY;
ALTER TABLE postulaciones ENABLE ROW LEVEL SECURITY;

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA PERFILES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los perfiles
CREATE POLICY "Acceso completo para administradores" ON perfiles
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los usuarios pueden ver su propio perfil
CREATE POLICY "Los usuarios pueden ver su propio perfil" ON perfiles
    FOR SELECT
    USING (user_id = auth.uid());

-- Los usuarios pueden actualizar su propio perfil
CREATE POLICY "Los usuarios pueden actualizar su propio perfil" ON perfiles
    FOR UPDATE
    USING (user_id = auth.uid());

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA CANDIDATOS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los candidatos
CREATE POLICY "Acceso completo para administradores" ON candidatos
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los candidatos pueden ver y actualizar su propia información
CREATE POLICY "Candidatos ven y actualizan su propia información" ON candidatos
    USING (
        perfil_id IN (
            SELECT id FROM perfiles
            WHERE user_id = auth.uid()
        )
    );

-- Los reclutadores pueden ver todos los candidatos
CREATE POLICY "Reclutadores pueden ver candidatos" ON candidatos
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'reclutador'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA EMPRESAS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todas las empresas
CREATE POLICY "Acceso completo para administradores" ON empresas
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los reclutadores pueden ver y editar su propia empresa
CREATE POLICY "Reclutadores editan su propia empresa" ON empresas
    USING (
        id IN (
            SELECT empresa_id FROM reclutadores r
            JOIN perfiles p ON r.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );

-- Todos los usuarios autenticados pueden ver empresas
CREATE POLICY "Todos los usuarios pueden ver empresas" ON empresas
    FOR SELECT
    USING (auth.uid() IS NOT NULL);

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA RECLUTADORES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los reclutadores
CREATE POLICY "Acceso completo para administradores" ON reclutadores
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los reclutadores pueden ver y actualizar su propia información
CREATE POLICY "Reclutadores ven y actualizan su propia información" ON reclutadores
    USING (
        perfil_id IN (
            SELECT id FROM perfiles
            WHERE user_id = auth.uid()
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA TECNOLOGIAS
------------------------------------------------------------------------

-- Todos los usuarios autenticados pueden ver tecnologías
CREATE POLICY "Todos los usuarios pueden ver tecnologías" ON tecnologias
    FOR SELECT
    USING (auth.uid() IS NOT NULL);

-- Solo administradores pueden crear, actualizar o eliminar tecnologías
CREATE POLICY "Solo administradores gestionan tecnologías" ON tecnologias
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA CONOCIMIENTOS_CANDIDATO
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los conocimientos
CREATE POLICY "Acceso completo para administradores" ON conocimientos_candidato
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los candidatos pueden gestionar sus propios conocimientos
CREATE POLICY "Candidatos gestionan sus conocimientos" ON conocimientos_candidato
    USING (
        candidato_id IN (
            SELECT c.id FROM candidatos c
            JOIN perfiles p ON c.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );

-- Los reclutadores pueden ver los conocimientos de candidatos
CREATE POLICY "Reclutadores pueden ver conocimientos" ON conocimientos_candidato
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'reclutador'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA VACANTES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todas las vacantes
CREATE POLICY "Acceso completo para administradores" ON vacantes
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los reclutadores pueden gestionar sus propias vacantes
CREATE POLICY "Reclutadores gestionan sus vacantes" ON vacantes
    USING (
        reclutador_id IN (
            SELECT r.id FROM reclutadores r
            JOIN perfiles p ON r.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );

-- Todos los usuarios autenticados pueden ver vacantes activas
CREATE POLICY "Todos los usuarios ven vacantes activas" ON vacantes
    FOR SELECT
    USING (auth.uid() IS NOT NULL AND estado = 'activa');

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA REQUISITOS_VACANTE
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los requisitos
CREATE POLICY "Acceso completo para administradores" ON requisitos_vacante
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los reclutadores pueden gestionar requisitos de sus vacantes
CREATE POLICY "Reclutadores gestionan requisitos de sus vacantes" ON requisitos_vacante
    USING (
        vacante_id IN (
            SELECT v.id FROM vacantes v
            JOIN reclutadores r ON v.reclutador_id = r.id
            JOIN perfiles p ON r.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );

-- Todos los usuarios autenticados pueden ver requisitos de vacantes activas
CREATE POLICY "Todos los usuarios ven requisitos de vacantes activas" ON requisitos_vacante
    FOR SELECT
    USING (
        auth.uid() IS NOT NULL AND 
        vacante_id IN (
            SELECT id FROM vacantes
            WHERE estado = 'activa'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA POSTULACIONES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todas las postulaciones
CREATE POLICY "Acceso completo para administradores" ON postulaciones
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_usuario = 'admin'
    ));

-- Los candidatos pueden ver y gestionar sus propias postulaciones
CREATE POLICY "Candidatos gestionan sus postulaciones" ON postulaciones
    USING (
        candidato_id IN (
            SELECT c.id FROM candidatos c
            JOIN perfiles p ON c.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );

-- Los reclutadores pueden ver y gestionar postulaciones a sus vacantes
CREATE POLICY "Reclutadores gestionan postulaciones de sus vacantes" ON postulaciones
    USING (
        vacante_id IN (
            SELECT v.id FROM vacantes v
            JOIN reclutadores r ON v.reclutador_id = r.id
            JOIN perfiles p ON r.perfil_id = p.id
            WHERE p.user_id = auth.uid()
        )
    );
