-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.busquedas_reclutadores (
  id integer NOT NULL DEFAULT nextval('busquedas_reclutadores_id_seq'::regclass),
  reclutador_id integer NOT NULL,
  criterios_busqueda text NOT NULL,
  fecha_busqueda timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT busquedas_reclutadores_pkey PRIMARY KEY (id),
  CONSTRAINT busquedas_reclutadores_reclutador_id_fkey FOREIGN KEY (reclutador_id) REFERENCES public.reclutadores(id)
);
CREATE TABLE public.candidato_habilidades (
  id integer NOT NULL DEFAULT nextval('candidato_habilidades_id_seq'::regclass),
  candidato_id integer NOT NULL,
  habilidad_id integer NOT NULL,
  nivel character varying NOT NULL CHECK (nivel::text = ANY (ARRAY['principiante'::character varying, 'intermedio'::character varying, 'avanzado'::character varying, 'experto'::character varying]::text[])),
  anios_experiencia integer NOT NULL DEFAULT 0,
  certificado_url character varying,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT candidato_habilidades_pkey PRIMARY KEY (id),
  CONSTRAINT candidato_habilidades_habilidad_id_fkey FOREIGN KEY (habilidad_id) REFERENCES public.habilidades(id),
  CONSTRAINT candidato_habilidades_candidato_id_fkey FOREIGN KEY (candidato_id) REFERENCES public.candidatos(id)
);
CREATE TABLE public.candidatos (
  id integer NOT NULL DEFAULT nextval('candidatos_id_seq'::regclass),
  perfil_id integer NOT NULL,
  telefono character varying NOT NULL,
  fecha_nacimiento date NOT NULL,
  direccion character varying NOT NULL,
  titulo character varying,
  anios_experiencia integer NOT NULL DEFAULT 0,
  cv_url character varying,
  foto_url character varying,
  resumen_profesional text,
  disponibilidad_viaje boolean DEFAULT false,
  disponibilidad_mudanza boolean DEFAULT false,
  modalidad_preferida character varying CHECK (modalidad_preferida::text = ANY (ARRAY['presencial'::character varying, 'remoto'::character varying, 'híbrido'::character varying]::text[])),
  expectativa_salarial numeric,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT candidatos_pkey PRIMARY KEY (id),
  CONSTRAINT candidatos_perfil_id_fkey FOREIGN KEY (perfil_id) REFERENCES public.perfiles(id)
);
CREATE TABLE public.candidatos_vistos (
  id integer NOT NULL DEFAULT nextval('candidatos_vistos_id_seq'::regclass),
  reclutador_id integer NOT NULL,
  candidato_id integer NOT NULL,
  fecha_vista timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT candidatos_vistos_pkey PRIMARY KEY (id),
  CONSTRAINT candidatos_vistos_reclutador_id_fkey FOREIGN KEY (reclutador_id) REFERENCES public.reclutadores(id),
  CONSTRAINT candidatos_vistos_candidato_id_fkey FOREIGN KEY (candidato_id) REFERENCES public.candidatos(id)
);
CREATE TABLE public.educacion (
  id integer NOT NULL DEFAULT nextval('educacion_id_seq'::regclass),
  candidato_id integer NOT NULL,
  institucion character varying NOT NULL,
  titulo character varying NOT NULL,
  area character varying NOT NULL,
  fecha_inicio date NOT NULL,
  fecha_fin date,
  en_curso boolean NOT NULL DEFAULT false,
  descripcion text,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT educacion_pkey PRIMARY KEY (id),
  CONSTRAINT educacion_candidato_id_fkey FOREIGN KEY (candidato_id) REFERENCES public.candidatos(id)
);
CREATE TABLE public.empresas (
  id integer NOT NULL DEFAULT nextval('empresas_id_seq'::regclass),
  nombre character varying NOT NULL,
  rfc character varying NOT NULL UNIQUE,
  industria character varying NOT NULL,
  direccion character varying NOT NULL,
  telefono character varying NOT NULL,
  sitio_web character varying,
  logo_url character varying,
  descripcion text,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT empresas_pkey PRIMARY KEY (id)
);
CREATE TABLE public.experiencia_laboral (
  id integer NOT NULL DEFAULT nextval('experiencia_laboral_id_seq'::regclass),
  candidato_id integer NOT NULL,
  empresa character varying NOT NULL,
  puesto character varying NOT NULL,
  fecha_inicio date NOT NULL,
  fecha_fin date,
  actual boolean NOT NULL DEFAULT false,
  descripcion text,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT experiencia_laboral_pkey PRIMARY KEY (id),
  CONSTRAINT experiencia_laboral_candidato_id_fkey FOREIGN KEY (candidato_id) REFERENCES public.candidatos(id)
);
CREATE TABLE public.habilidades (
  id integer NOT NULL DEFAULT nextval('habilidades_id_seq'::regclass),
  nombre character varying NOT NULL,
  categoria character varying NOT NULL CHECK (categoria::text = ANY (ARRAY['protocolos_red'::character varying, 'dispositivos_red'::character varying, 'sistemas_operativos'::character varying, 'servicios_cloud'::character varying, 'lenguajes_programacion'::character varying, 'herramientas_desarrollo'::character varying, 'bases_datos'::character varying, 'ciberseguridad'::character varying, 'plataformas_virtualizacion'::character varying, 'otros'::character varying]::text[])),
  descripcion text,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT habilidades_pkey PRIMARY KEY (id)
);
CREATE TABLE public.perfiles (
  id integer NOT NULL DEFAULT nextval('perfiles_id_seq'::regclass),
  user_id uuid NOT NULL UNIQUE,
  tipo_usuario character varying NOT NULL CHECK (tipo_usuario::text = ANY (ARRAY['candidato'::character varying, 'reclutador'::character varying, 'admin'::character varying]::text[])),
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT perfiles_pkey PRIMARY KEY (id)
);
CREATE TABLE public.reclutadores (
  id integer NOT NULL DEFAULT nextval('reclutadores_id_seq'::regclass),
  perfil_id integer NOT NULL,
  empresa_id integer NOT NULL,
  nombre character varying NOT NULL,
  apellidos character varying NOT NULL,
  email character varying NOT NULL UNIQUE,
  cargo character varying NOT NULL,
  telefono character varying,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT reclutadores_pkey PRIMARY KEY (id),
  CONSTRAINT reclutadores_perfil_id_fkey FOREIGN KEY (perfil_id) REFERENCES public.perfiles(id),
  CONSTRAINT reclutadores_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.empresas(id)
);
CREATE TABLE public.vacante_habilidades (
  id integer NOT NULL DEFAULT nextval('vacante_habilidades_id_seq'::regclass),
  vacante_id integer NOT NULL,
  habilidad_id integer NOT NULL,
  nivel_requerido character varying NOT NULL CHECK (nivel_requerido::text = ANY (ARRAY['principiante'::character varying, 'intermedio'::character varying, 'avanzado'::character varying, 'experto'::character varying]::text[])),
  obligatorio boolean NOT NULL DEFAULT true,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT vacante_habilidades_pkey PRIMARY KEY (id),
  CONSTRAINT vacante_habilidades_habilidad_id_fkey FOREIGN KEY (habilidad_id) REFERENCES public.habilidades(id),
  CONSTRAINT vacante_habilidades_vacante_id_fkey FOREIGN KEY (vacante_id) REFERENCES public.vacantes(id)
);
CREATE TABLE public.vacantes (
  id integer NOT NULL DEFAULT nextval('vacantes_id_seq'::regclass),
  empresa_id integer NOT NULL,
  reclutador_id integer NOT NULL,
  titulo character varying NOT NULL,
  descripcion text NOT NULL,
  responsabilidades text NOT NULL,
  requisitos text NOT NULL,
  salario numeric NOT NULL,
  anios_experiencia integer NOT NULL DEFAULT 0,
  modalidad character varying NOT NULL CHECK (modalidad::text = ANY (ARRAY['presencial'::character varying, 'remoto'::character varying, 'híbrido'::character varying]::text[])),
  ubicacion character varying NOT NULL,
  estado character varying NOT NULL DEFAULT 'activa'::character varying CHECK (estado::text = ANY (ARRAY['activa'::character varying, 'cerrada'::character varying, 'pausada'::character varying]::text[])),
  destacada boolean NOT NULL DEFAULT false,
  fecha_publicacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  fecha_expiracion date,
  fecha_creacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  ultima_actualizacion timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT vacantes_pkey PRIMARY KEY (id),
  CONSTRAINT vacantes_reclutador_id_fkey FOREIGN KEY (reclutador_id) REFERENCES public.reclutadores(id),
  CONSTRAINT vacantes_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.empresas(id)
);