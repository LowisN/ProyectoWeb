-- Script de creación de tablas para ChambaNet
-- Este script crea todas las tablas necesarias para la aplicación

-- Tabla de perfiles (información general de usuarios)
CREATE TABLE perfiles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL UNIQUE REFERENCES auth.users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    tipo_usuario VARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('admin', 'candidato', 'reclutador'))
);

-- Tabla de candidatos (información específica de candidatos)
CREATE TABLE candidatos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    perfil_id UUID NOT NULL UNIQUE REFERENCES perfiles(id) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    direccion TEXT NOT NULL,
    titulo VARCHAR(100),
    anios_experiencia INTEGER NOT NULL DEFAULT 0,
    acerca_de TEXT,
    cv_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de empresas
CREATE TABLE empresas (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    industria VARCHAR(100),
    sitio_web VARCHAR(255),
    logo_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de reclutadores
CREATE TABLE reclutadores (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    perfil_id UUID NOT NULL UNIQUE REFERENCES perfiles(id) ON DELETE CASCADE,
    empresa_id UUID NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    puesto VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de tecnologías/habilidades (catálogo)
CREATE TABLE tecnologias (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nombre VARCHAR(100) NOT NULL UNIQUE,
    categoria VARCHAR(50) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de conocimientos de candidato (relación muchos a muchos con nivel)
CREATE TABLE conocimientos_candidato (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    candidato_id UUID NOT NULL REFERENCES candidatos(id) ON DELETE CASCADE,
    tecnologia VARCHAR(100) NOT NULL,
    nivel VARCHAR(10) NOT NULL CHECK (nivel IN ('malo', 'regular', 'bueno')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE (candidato_id, tecnologia)
);

-- Tabla de vacantes
CREATE TABLE vacantes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    empresa_id UUID NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    empresa_nombre VARCHAR(150) NOT NULL,
    reclutador_id UUID NOT NULL REFERENCES reclutadores(id) ON DELETE CASCADE,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    responsabilidades TEXT NOT NULL,
    requisitos TEXT NOT NULL,
    salario NUMERIC(12, 2),
    modalidad VARCHAR(50) NOT NULL,
    ubicacion VARCHAR(100) NOT NULL,
    anios_experiencia_requeridos INTEGER NOT NULL DEFAULT 0,
    fecha_publicacion DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_cierre DATE,
    estado VARCHAR(20) NOT NULL DEFAULT 'activa' CHECK (estado IN ('activa', 'cerrada', 'cancelada')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Tabla de requisitos de tecnologías para vacantes
CREATE TABLE requisitos_vacante (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vacante_id UUID NOT NULL REFERENCES vacantes(id) ON DELETE CASCADE,
    tecnologia VARCHAR(100) NOT NULL,
    nivel_requerido VARCHAR(10) NOT NULL CHECK (nivel_requerido IN ('malo', 'regular', 'bueno')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE (vacante_id, tecnologia)
);

-- Tabla de postulaciones
CREATE TABLE postulaciones (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    vacante_id UUID NOT NULL REFERENCES vacantes(id) ON DELETE CASCADE,
    candidato_id UUID NOT NULL REFERENCES candidatos(id) ON DELETE CASCADE,
    match_percentage NUMERIC(5, 2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'revisada', 'entrevista', 'rechazada', 'aceptada')),
    mensaje_candidato TEXT,
    notas_reclutador TEXT,
    fecha_postulacion TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE (vacante_id, candidato_id)
);

-- Creación de índices para optimizar búsquedas comunes
CREATE INDEX idx_candidatos_experiencia ON candidatos(anios_experiencia);
CREATE INDEX idx_vacantes_estado ON vacantes(estado);
CREATE INDEX idx_vacantes_fecha ON vacantes(fecha_publicacion);
CREATE INDEX idx_vacantes_empresa ON vacantes(empresa_id);
CREATE INDEX idx_postulaciones_estado ON postulaciones(estado);
CREATE INDEX idx_postulaciones_match ON postulaciones(match_percentage DESC);

-- Inserción de datos iniciales para categorías de tecnologías
INSERT INTO tecnologias (nombre, categoria) VALUES
-- Protocolos de red
('TCP/IP', 'protocolos_red'),
('UDP', 'protocolos_red'),
('HTTP/HTTPS', 'protocolos_red'),
('DNS', 'protocolos_red'),
('DHCP', 'protocolos_red'),
('FTP', 'protocolos_red'),
('SMTP', 'protocolos_red'),
('POP3/IMAP', 'protocolos_red'),
('SSH', 'protocolos_red'),
('RDP', 'protocolos_red'),

-- Dispositivos de red
('Routers', 'dispositivos_red'),
('Switches', 'dispositivos_red'),
('Firewalls', 'dispositivos_red'),
('Access Points', 'dispositivos_red'),
('Load Balancers', 'dispositivos_red'),
('Modems', 'dispositivos_red'),
('Repeaters', 'dispositivos_red'),
('VPN Concentrators', 'dispositivos_red'),
('Network Bridges', 'dispositivos_red'),
('Gateways', 'dispositivos_red'),

-- Seguridad de redes
('VPN', 'seguridad_redes'),
('Encrypting', 'seguridad_redes'),
('Firewalls Hardware', 'seguridad_redes'),
('Firewalls Software', 'seguridad_redes'),
('IDS/IPS', 'seguridad_redes'),
('Network Monitoring', 'seguridad_redes'),
('Penetration Testing', 'seguridad_redes'),
('SSL/TLS', 'seguridad_redes'),
('Authentication Systems', 'seguridad_redes'),
('Network Security Policies', 'seguridad_redes'),

-- Software de redes
('Cisco IOS', 'software'),
('Wireshark', 'software'),
('Nmap', 'software'),
('PuTTY', 'software'),
('GNS3', 'software'),
('SolarWinds', 'software'),
('Nagios', 'software'),
('OpenVPN', 'software'),
('VMware', 'software'),
('Packet Tracer', 'software'),

-- Certificaciones
('CCNA', 'certificaciones'),
('CCNP', 'certificaciones'),
('CompTIA Network+', 'certificaciones'),
('CompTIA Security+', 'certificaciones'),
('CISSP', 'certificaciones'),
('CISM', 'certificaciones'),
('JNCIA', 'certificaciones'),
('AWS Certified Networking', 'certificaciones'),
('Microsoft Certified: Azure Network Engineer', 'certificaciones'),
('Fortinet NSE', 'certificaciones');

-- Inserción de usuario administrador (requiere modificar manualmente la contraseña o crear primero el usuario en Supabase Auth)
-- INSERT INTO perfiles (user_id, email, tipo_usuario) VALUES 
-- ('ID_DEL_USUARIO_ADMIN', 'admin@chambanet.com', 'admin');
