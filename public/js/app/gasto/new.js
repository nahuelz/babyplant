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
            // Si el valor ya está formateado, no hacer nada
            if (e.target.value === '') return;

            // Eliminar todos los caracteres que no sean números
            let value = e.target.value.replace(/[^\d]/g, '');

            // Convertir a número y dividir por 100 para manejar decimales
            const number = parseInt(value || '0', 10) / 100;

            // Formatear como moneda argentina
            e.target.value = number.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    }
}