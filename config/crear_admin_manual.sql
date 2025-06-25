-- Instrucciones SQL para crear un usuario administrador manualmente
-- Ejecutar estos comandos en el SQL Editor de Supabase en el orden correcto

-- PASO 1: Crear el usuario en auth.users
-- NOTA: Esto normalmente NO se puede ejecutar directamente desde el SQL Editor de Supabase
-- En su lugar, debes usar la UI de Supabase (Authentication > Users > Add User)
-- O usar la API de Supabase Auth
-- 
-- El comando SQL equivalente sería algo como:
-- 
-- INSERT INTO auth.users (
--   id,
--   email,
--   encrypted_password,
--   email_confirmed_at
-- ) VALUES (
--   uuid_generate_v4(), -- O un UUID específico si quieres
--   'admin@chambanet.com',
--   crypt('ContraseñaSegura123', gen_salt('bf')),
--   now()
-- );

-- PASO 2: Una vez creado el usuario en auth.users, usa su UUID para los siguientes pasos.
-- Reemplaza 'ID_DEL_USUARIO' con el UUID real del usuario creado.
-- Puedes obtener este ID en la UI de Supabase en Authentication > Users

-- Insertar datos en la tabla usuario
INSERT INTO public.usuario (
  user_id,
  nombre,
  apellido_paterno,
  apellido_materno,
  correo,
  telefono,
  fecha_nacimiento
) VALUES (
  'ID_DEL_USUARIO', -- Reemplaza con el UUID real
  'Administrador',
  'Sistema',
  '',
  'admin@chambanet.com', -- Debe coincidir con el email usado en auth.users
  '1234567890',
  '2000-01-01'
);

-- PASO 3: Insertar en la tabla perfiles con el rol de administrador
INSERT INTO public.perfiles (
  user_id,
  tipo_perfil
) VALUES (
  'ID_DEL_USUARIO', -- Reemplaza con el mismo UUID
  'administrador'
);

-- PASO 4 (opcional): Verificar que el usuario se creó correctamente
SELECT 
  a.id, a.email, a.last_sign_in_at,
  u.nombre, u.apellido_paterno,
  p.tipo_perfil
FROM 
  auth.users a
JOIN 
  public.usuario u ON a.id = u.user_id
JOIN 
  public.perfiles p ON u.user_id = p.user_id
WHERE 
  a.email = 'admin@chambanet.com';

-- Nota importante: Si recibes un error como:
-- ERROR: insert or update on table "usuario" violates foreign key constraint "usuario_user_id_fkey"
-- DETAIL: Key (user_id)=(uuid) is not present in table "users".
--
-- Significa que estás intentando insertar un user_id que no existe en auth.users.
-- Asegúrate de que el usuario exista primero en auth.users antes de intentar 
-- insertar registros en las tablas usuario y perfiles.
