-- Primero: Crear un usuario en auth.users (necesitas permisos de superusuario/administrador)
-- Nota: En Supabase, esto normalmente se hace mediante la API de autenticación o mediante la UI
-- Este comando SQL directo generalmente está restringido, pero lo incluyo como referencia:

-- MÉTODO 1: Si tienes permisos de superadmin en la base de datos (raro en Supabase hosted)
INSERT INTO auth.users (
  id,
  email,
  encrypted_password,
  email_confirmed_at,
  created_at,
  updated_at
) VALUES (
  '22bc048f-700a-4a67-93b1-81c8d584f6a0', -- UUID específico que quieres usar
  'lowis.sin@gmail.com.com',
  -- Contraseña hasheada (esta es una versión de ejemplo, NO usar en producción)
  -- En realidad, deberías usar la función de hash adecuada o dejar que Supabase la genere
  crypt('admin123', gen_salt('bf')), 
  now(),
  now(),
  now()
);

-- MÉTODO 2 (RECOMENDADO): Usar la API de Supabase para registrar el usuario
-- Esto se debe hacer desde tu aplicación usando el código de cliente de Supabase

-- Después de crear el usuario en auth, AHORA puedes insertar en tu tabla usuario:
INSERT INTO public.usuario (
  user_id,
  nombre,
  apellido_paterno,
  apellido_materno,
  correo,
  telefono,
  fecha_nacimiento
) VALUES (
  '22bc048f-700a-4a67-93b1-81c8d584f6a0', -- Mismo UUID del paso anterior
  'Administrador',
  'Sistema',
  '',
  'admin@chambanet.com',
  '0000000000',
  '2000-01-01'
);

-- Por último, define el perfil como administrador
INSERT INTO public.perfiles (
  user_id,
  tipo_perfil
) VALUES (
  '22bc048f-700a-4a67-93b1-81c8d584f6a0', 
  'administrador'
);

-- Verificación (opcional)
SELECT u.user_id, u.nombre, u.correo, p.tipo_perfil 
FROM usuario u 
JOIN perfiles p ON u.user_id = p.user_id 
WHERE p.tipo_perfil = 'administrador';
