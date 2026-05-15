// ============================================================
// TIENDA GAMER - Validaciones y utilidades JS
// INTEGRANTE 2: Subir carpeta assets/ completa
// ============================================================

// Auto-cerrar alertas después de 5 segundos
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert.fade.show').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});

// Validación genérica Bootstrap para formularios con id="formValidar"
(function () {
    'use strict';
    var forms = document.querySelectorAll('form.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
