-- Políticas de Seguridad de Row Level Security (RLS) para ChambaNet en Supabase
-- Estas políticas controlan qué usuarios pueden leer, crear, actualizar o eliminar cada fila

------------------------------------------------------------------------
-- HABILITAR RLS EN TODAS LAS TABLAS
------------------------------------------------------------------------

-- Habilitar RLS en todas las tablas
ALTER TABLE usuario ENABLE ROW LEVEL SECURITY;
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
-- POLÍTICAS PARA TABLA USUARIO
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los usuarios
CREATE POLICY "Acceso completo para administradores a usuario" ON usuario
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los usuarios pueden ver su propia información
CREATE POLICY "Los usuarios pueden ver su propia información" ON usuario
    FOR SELECT
    USING (user_id = auth.uid());

-- Los usuarios pueden actualizar su propia información
CREATE POLICY "Los usuarios pueden actualizar su propia información" ON usuario
    FOR UPDATE
    USING (user_id = auth.uid());

-- Los reclutadores pueden ver información básica de candidatos
CREATE POLICY "Reclutadores pueden ver usuarios candidatos" ON usuario
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p 
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        ) AND
        EXISTS (
            SELECT 1 FROM perfiles p2
            WHERE p2.user_id = usuario.user_id AND p2.tipo_perfil = 'candidato'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA PERFILES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los perfiles
CREATE POLICY "Acceso completo para administradores a perfiles" ON perfiles
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los usuarios pueden ver su propio perfil
CREATE POLICY "Los usuarios pueden ver su propio perfil" ON perfiles
    FOR SELECT
    USING (user_id = auth.uid());

-- Los usuarios pueden actualizar su propio perfil
CREATE POLICY "Los usuarios pueden actualizar su propio perfil" ON perfiles
    FOR UPDATE
    USING (user_id = auth.uid());

-- Los reclutadores pueden ver los perfiles de candidatos
CREATE POLICY "Reclutadores pueden ver perfiles de candidatos" ON perfiles
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        ) AND
        tipo_perfil = 'candidato'
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA CANDIDATOS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los candidatos
CREATE POLICY "Acceso completo para administradores a candidatos" ON candidatos
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los candidatos pueden ver y editar su propia información
CREATE POLICY "Candidatos pueden ver y editar su propia información" ON candidatos
    USING (user_id = auth.uid());

-- Los reclutadores pueden ver información de candidatos
CREATE POLICY "Reclutadores pueden ver información de candidatos" ON candidatos
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA EMPRESAS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todas las empresas
CREATE POLICY "Acceso completo para administradores a empresas" ON empresas
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los reclutadores pueden ver y editar información de su propia empresa
CREATE POLICY "Reclutadores pueden ver y editar su empresa" ON empresas
    USING (
        EXISTS (
            SELECT 1 FROM reclutadores r
            WHERE r.user_id = auth.uid() AND r.empresa_id = empresas.id
        )
    );

-- Todos los usuarios autenticados pueden ver información básica de empresas
CREATE POLICY "Usuarios pueden ver información básica de empresas" ON empresas
    FOR SELECT
    USING (auth.uid() IS NOT NULL);

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA RECLUTADORES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los reclutadores
CREATE POLICY "Acceso completo para administradores a reclutadores" ON reclutadores
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los reclutadores pueden ver y editar su propia información
CREATE POLICY "Reclutadores pueden ver y editar su información" ON reclutadores
    USING (user_id = auth.uid());

-- Los candidatos pueden ver información básica de reclutadores
CREATE POLICY "Candidatos pueden ver información básica de reclutadores" ON reclutadores
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA TECNOLOGÍAS (CATÁLOGO COMPARTIDO)
------------------------------------------------------------------------

-- Todos los usuarios autenticados pueden ver tecnologías
CREATE POLICY "Usuarios autenticados pueden ver tecnologías" ON tecnologias
    FOR SELECT
    USING (auth.uid() IS NOT NULL);

-- Solo administradores pueden modificar el catálogo de tecnologías
CREATE POLICY "Solo administradores pueden modificar tecnologías" ON tecnologias
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA CONOCIMIENTOS_CANDIDATO
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores a conocimientos" ON conocimientos_candidato
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los candidatos pueden ver y editar sus propios conocimientos
CREATE POLICY "Candidatos pueden gestionar sus conocimientos" ON conocimientos_candidato
    USING (
        EXISTS (
            SELECT 1 FROM candidatos c
            WHERE c.user_id = auth.uid() AND c.user_id = conocimientos_candidato.candidato_id
        )
    );

-- Los reclutadores pueden ver conocimientos de candidatos
CREATE POLICY "Reclutadores pueden ver conocimientos de candidatos" ON conocimientos_candidato
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        )
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA VACANTES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores a vacantes" ON vacantes
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los reclutadores pueden gestionar vacantes de su empresa
CREATE POLICY "Reclutadores pueden gestionar vacantes de su empresa" ON vacantes
    USING (
        EXISTS (
            SELECT 1 FROM reclutadores r
            WHERE r.user_id = auth.uid() AND r.empresa_id = vacantes.empresa_id
        )
    );

-- Candidatos pueden ver vacantes activas
CREATE POLICY "Candidatos pueden ver vacantes activas" ON vacantes
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM perfiles p
            WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        ) AND
        estado = 'activa'
    );

------------------------------------------------------------------------
-- POLÍTICAS PARA REQUISITOS_VACANTE
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores a requisitos" ON requisitos_vacante
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los reclutadores pueden gestionar requisitos de vacantes de su empresa
CREATE POLICY "Reclutadores pueden gestionar requisitos" ON requisitos_vacante
    USING (
        EXISTS (
            SELECT 1 FROM reclutadores r
            JOIN vacantes v ON v.empresa_id = r.empresa_id
            WHERE r.user_id = auth.uid() AND v.id = requisitos_vacante.vacante_id
        )
    );

-- Todos los usuarios autenticados pueden ver requisitos de vacantes
CREATE POLICY "Usuarios pueden ver requisitos de vacantes" ON requisitos_vacante
    FOR SELECT
    USING (auth.uid() IS NOT NULL);

------------------------------------------------------------------------
-- POLÍTICAS PARA POSTULACIONES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores a postulaciones" ON postulaciones
    USING (EXISTS (
        SELECT 1 FROM perfiles p
        WHERE p.user_id = auth.uid() AND p.tipo_perfil = 'administrador'
    ));

-- Los candidatos pueden ver y gestionar sus propias postulaciones
CREATE POLICY "Candidatos pueden gestionar sus postulaciones" ON postulaciones
    USING (
        EXISTS (
            SELECT 1 FROM candidatos c
            WHERE c.user_id = auth.uid() AND c.user_id = postulaciones.candidato_id
        )
    );

-- Los reclutadores pueden ver postulaciones a vacantes de su empresa
CREATE POLICY "Reclutadores pueden ver postulaciones a sus vacantes" ON postulaciones
    USING (
        EXISTS (
            SELECT 1 FROM reclutadores r
            JOIN vacantes v ON v.empresa_id = r.empresa_id
            WHERE r.user_id = auth.uid() AND v.id = postulaciones.vacante_id
        )
    );
