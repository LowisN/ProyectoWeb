const token = window.location.hash.match(/access_token=([^&]*)/)?.[1];

        if (!token) {
            document.getElementById('mensaje').textContent = "Token no encontrado en el enlace.";
            document.getElementById('mensaje').className = "mensaje error";
        }

        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const nuevaClave = document.getElementById('nuevaClave').value;
            const confirmarClave = document.getElementById('confirmarClave').value;
            const mensaje = document.getElementById('mensaje');
            const btnVolver = document.getElementById('btn-volver');

            if (nuevaClave !== confirmarClave) {
                mensaje.textContent = "Las contraseñas no coinciden.";
                mensaje.className = "mensaje error";
                return;
            }

            try {
                const response = await fetch('https://wklyvlosbiylfvovakly.supabase.co/auth/v1/user', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token,
                        'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6IndrbHl2bG9zYml5bGZ2b3Zha2x5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTA4MTI2ODIsImV4cCI6MjA2NjM4ODY4Mn0.fthVr-m8UkVX4tUW_zzdwhM6mgqohRbez-Oqek4efxo'
                    },
                    body: JSON.stringify({
                        password: nuevaClave
                    })
                });

                if (response.ok) {
                    mensaje.textContent = "Contraseña actualizada con éxito. Redirigiendo...";
                    mensaje.className = "mensaje";
                    btnVolver.style.display = "block";

                    // Redirigir después de 3 segundos
                    setTimeout(() => {
                        window.location.href = "../index.php";
                    }, 3000);
                } else {
                    const data = await response.json();
                    mensaje.textContent = data.error || "Error al actualizar contraseña.";
                    mensaje.className = "mensaje error";
                }

            } catch (error) {
                mensaje.textContent = "Error de red. Intenta más tarde.";
                mensaje.className = "mensaje error";
            }
        });