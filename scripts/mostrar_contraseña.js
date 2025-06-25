document.addEventListener("DOMContentLoaded", function () {
    const mostrarPass = document.getElementById('mostrarPass');
    const campoPass = document.getElementById('contrasena');
    const campoConfirmaPass = document.getElementById('confirmar_contrasena');

    if (mostrarPass && campoPass) {
        mostrarPass.addEventListener('change', function () {
            campoPass.type = this.checked ? 'text' : 'password';
            if (campoConfirmaPass) {
                campoConfirmaPass.type = this.checked ? 'text' : 'password';
            }
        });
    }
});
