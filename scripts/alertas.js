// Funcionalidad para limpiar el formulario de registro
document.addEventListener('DOMContentLoaded', function() {
    var btnLimpiar = document.getElementById('btn-limpiar');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            var form = btnLimpiar.closest('form');
            if (form) {
                form.reset();
            }
        });
    }

    // Validación en tiempo real para nombre y apellido
    function soloLetras(e) {
        let valor = e.target.value;
        // Solo letras y espacios, máximo 20 caracteres
        valor = valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, "").substring(0, 20);
        e.target.value = valor;
    }
    var nombre = document.getElementById('nombre');
    var apellido = document.getElementById('apellido');
    if (nombre) nombre.addEventListener('input', soloLetras);
    if (apellido) apellido.addEventListener('input', soloLetras);
});
