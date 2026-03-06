jQuery(document).ready(function () {
    $('#gasto_modoPago').select2();
    $('#gasto_concepto').select2();
    $('#gasto_subConcepto').select2();
    initMontoFormat();

    initChainedSelect($('#gasto_concepto'), $('#gasto_subConcepto'), __HOMEPAGE_PATH__ + 'tipo/sub/concepto/lista/', 1);
});


function initMontoFormat() {
    const montoInput = document.querySelector('.monto-input');

    if (montoInput) {
        montoInput.addEventListener('input', function(e) {

            if (e.target.value === '') return;

            // Eliminar todo lo que no sea número
            let value = e.target.value.replace(/[^\d]/g, '');

            // Convertir a número
            const number = parseInt(value || '0', 10);

            // Formatear sin decimales
            e.target.value = number.toLocaleString('es-AR');
        });
    }
}