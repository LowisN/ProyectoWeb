-- Políticas de Seguridad de Row Level Security (RLS) para ChambaNet en Supabase (Versión actualizada)
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
CREATE POLICY "Acceso completo para administradores en usuarios" ON usuario
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los usuarios pueden ver su propia información
CREATE POLICY "Los usuarios pueden ver su propia información" ON usuario
    FOR SELECT
    USING (user_id = auth.uid());

-- Los usuarios pueden actualizar su propia información
CREATE POLICY "Los usuarios pueden actualizar su propia información" ON usuario
    FOR UPDATE
    USING (user_id = auth.uid());

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA PERFILES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los perfiles
CREATE POLICY "Acceso completo para administradores en perfiles" ON perfiles
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los usuarios pueden ver su propio perfil
CREATE POLICY "Los usuarios pueden ver su propio perfil" ON perfiles
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM usuario u
        WHERE u.user_id = auth.uid() AND u.id = perfiles.usuario_id
    ));

-- Los usuarios pueden actualizar su propio perfil
CREATE POLICY "Los usuarios pueden actualizar su propio perfil" ON perfiles
    FOR UPDATE
    USING (EXISTS (
        SELECT 1 FROM usuario u
        WHERE u.user_id = auth.uid() AND u.id = perfiles.usuario_id
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA CANDIDATOS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los candidatos
CREATE POLICY "Acceso completo para administradores en candidatos" ON candidatos
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los candidatos pueden ver y modificar su propia información
CREATE POLICY "Candidatos pueden ver y modificar su información" ON candidatos
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        AND p.id = candidatos.perfil_id
    ));

-- Los reclutadores pueden ver información general de candidatos
CREATE POLICY "Reclutadores pueden ver información de candidatos" ON candidatos
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA EMPRESAS
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todas las empresas
CREATE POLICY "Acceso completo para administradores en empresas" ON empresas
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los reclutadores pueden ver y actualizar su propia empresa
CREATE POLICY "Reclutadores pueden ver y actualizar su empresa" ON empresas
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN reclutadores r ON p.id = r.perfil_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        AND r.empresa_id = empresas.id
    ));

-- Todos los usuarios autenticados pueden ver información básica de empresas
CREATE POLICY "Usuarios autenticados pueden ver empresas" ON empresas
    FOR SELECT
    USING (auth.role() = 'authenticated');

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA RECLUTADORES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo a todos los reclutadores
CREATE POLICY "Acceso completo para administradores en reclutadores" ON reclutadores
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los reclutadores pueden ver y modificar su propia información
CREATE POLICY "Reclutadores pueden ver y modificar su información" ON reclutadores
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        AND p.id = reclutadores.perfil_id
    ));

-- Los candidatos pueden ver información general de reclutadores
CREATE POLICY "Candidatos pueden ver información de reclutadores" ON reclutadores
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA TECNOLOGIAS
------------------------------------------------------------------------

-- Acceso de lectura para todos los usuarios autenticados
CREATE POLICY "Lectura de tecnologías para usuarios autenticados" ON tecnologias
    FOR SELECT
    USING (auth.role() = 'authenticated');

-- Solo administradores pueden modificar las tecnologías
CREATE POLICY "Solo administradores modifican tecnologías" ON tecnologias
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA CONOCIMIENTOS_CANDIDATO
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores en conocimientos" ON conocimientos_candidato
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los candidatos pueden gestionar sus propios conocimientos
CREATE POLICY "Candidatos gestionan sus conocimientos" ON conocimientos_candidato
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN candidatos c ON p.id = c.perfil_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        AND c.id = conocimientos_candidato.candidato_id
    ));

-- Los reclutadores pueden ver conocimientos de candidatos
CREATE POLICY "Reclutadores ven conocimientos de candidatos" ON conocimientos_candidato
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA VACANTES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores en vacantes" ON vacantes
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los reclutadores pueden gestionar vacantes de su empresa
CREATE POLICY "Reclutadores gestionan vacantes de su empresa" ON vacantes
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN reclutadores r ON p.id = r.perfil_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        AND r.empresa_id = vacantes.empresa_id
    ));

-- Todos los usuarios autenticados pueden ver vacantes activas
CREATE POLICY "Usuarios autenticados ven vacantes activas" ON vacantes
    FOR SELECT
    USING (auth.role() = 'authenticated' AND estado = 'activa');

-- Los candidatos pueden ver vacantes activas
CREATE POLICY "Candidatos ven vacantes activas" ON vacantes
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        AND vacantes.estado = 'activa'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA REQUISITOS_VACANTE
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores en requisitos" ON requisitos_vacante
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los reclutadores pueden gestionar requisitos de vacantes de su empresa
CREATE POLICY "Reclutadores gestionan requisitos de vacantes" ON requisitos_vacante
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN reclutadores r ON p.id = r.perfil_id
        JOIN vacantes v ON r.empresa_id = v.empresa_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        AND v.id = requisitos_vacante.vacante_id
    ));

-- Todos los usuarios autenticados pueden ver requisitos de vacantes activas
CREATE POLICY "Usuarios ven requisitos de vacantes activas" ON requisitos_vacante
    FOR SELECT
    USING (EXISTS (
        SELECT 1 FROM vacantes v
        WHERE v.id = requisitos_vacante.vacante_id
        AND v.estado = 'activa'
        AND auth.role() = 'authenticated'
    ));

------------------------------------------------------------------------
-- POLÍTICAS PARA TABLA POSTULACIONES
------------------------------------------------------------------------

-- Los administradores tienen acceso completo
CREATE POLICY "Acceso completo para administradores en postulaciones" ON postulaciones
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'admin'
    ));

-- Los candidatos pueden ver y gestionar sus propias postulaciones
CREATE POLICY "Candidatos gestionan sus postulaciones" ON postulaciones
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN candidatos c ON p.id = c.perfil_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'candidato'
        AND c.id = postulaciones.candidato_id
    ));

-- Los reclutadores pueden ver y gestionar postulaciones a vacantes de su empresa
CREATE POLICY "Reclutadores gestionan postulaciones a sus vacantes" ON postulaciones
    USING (EXISTS (
        SELECT 1 FROM usuario u
        JOIN perfiles p ON u.id = p.usuario_id
        JOIN reclutadores r ON p.id = r.perfil_id
        JOIN vacantes v ON r.empresa_id = v.empresa_id
        WHERE u.user_id = auth.uid() AND p.tipo_perfil = 'reclutador'
        AND v.id = postulaciones.vacante_id
    ));
