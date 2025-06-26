        // Función principal que controla la visibilidad de las secciones
        function toggleFormSections() {
            const checkbox = document.getElementById('en_curso');
            const seccionNoEnCurso = document.getElementById('secccionNoEnCurso');
            const seccionEnCurso = document.getElementById('seccionEnCurso');
            
            if (checkbox.checked) {
                // Si está marcado, mostrar sección empresa y ocultar persona
                seccionPersona.classList.remove('visible');
                seccionPersona.classList.add('oculta');
                
                seccionEmpresa.classList.remove('oculta');
                seccionEmpresa.classList.add('visible');
                
                // Opcional: limpiar campos de persona
                //limpiarCamposPersona();
                
            } else {
                // Si no está marcado, mostrar sección persona y ocultar empresa
                seccionEmpresa.classList.remove('visible');
                seccionEmpresa.classList.add('oculta');
                
                seccionPersona.classList.remove('oculta');
                seccionPersona.classList.add('visible');
                
                // Opcional: limpiar campos de empresa
                limpiarCamposEmpresa();
            }
        }
        
        // Función para limpiar campos de persona
      /*  function limpiarCamposPersona() {
            document.getElementById('edad').value = '';
            document.getElementById('telefono').value = '';
            document.getElementById('intereses').value = '';
        }
        
        // Función para limpiar campos de empresa
        function limpiarCamposEmpresa() {
            document.getElementById('nombreEmpresa').value = '';
            document.getElementById('rfc').value = '';
            document.getElementById('sector').value = '';
            document.getElementById('empleados').value = '';
        }
        
        // Manejo del envío del formulario
        document.getElementById('dCForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEmpresa = document.getElementById('esEmpresa').checked;
            
            console.log('Tipo de registro:', isEmpresa ? 'Empresa' : 'Persona');
            console.log('Datos del formulario:');
            
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    console.log(`${key}: ${value}`);
                }
            }
            
            alert('¡Formulario enviado! Revisa la consola para ver los datos.');
        });*/