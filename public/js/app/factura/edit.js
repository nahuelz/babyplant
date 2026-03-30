jQuery(document).ready(function () {
    // Inicialización de select2 en los campos
    $('#factura_modoPago').select2();
    $('#factura_concepto').select2();
    $('#factura_subConcepto').select2();

    initMontoFormat();

    // Asociar evento "submit" al formulario usando "name"
    $('#factura_submit').on('click', function (e) {
        e.preventDefault();

        // LIMPIAR EL MONTO ANTES DEL ENVÍO
        const montoInput = $('#factura_monto');
        const montoValue = montoInput.val();

        // Remover todos los puntos (separadores de miles)
        const cleanMonto = montoValue.replace(/\./g, '');

        // Establecer el valor limpio en el campo
        montoInput.val(cleanMonto);

        clearAllErrors();
        const isValid = validateForm();

        if (isValid) {
            $('form[name="factura"]')[0].submit();
        } else {
            showValidationSummary();

            // Restaurar el formato original si hay error
            montoInput.val(montoValue);
        }
    });
});

/**
 * Validación de formulario
 */
function validateForm() {
    let isValid = true;

    // Lista de campos requeridos con sus mensajes de error
    const requiredFields = [
        { selector: '#factura_numeroFactura', errorMessage: 'El número de factura es obligatorio.' },
        { selector: '#factura_fecha', errorMessage: 'La fecha es obligatoria.' },
        { selector: '#factura_concepto', errorMessage: 'Debe seleccionar un concepto.' },
        { selector: '#factura_modoPago', errorMessage: 'Debe seleccionar un modo de pago.' },
        { selector: '#factura_monto', errorMessage: 'El monto es obligatorio y debe ser mayor a 0.' }
    ];

    // Validar cada campo
    requiredFields.forEach(field => {
        const element = $(field.selector);
        const value = element.val();

        // Validar si el campo está vacío o si es un número inválido
        if (!value || value.trim() === '' || (field.selector === '#factura_monto' && parseFloat(value.replace(',', '.')) <= 0)) {
            showError(element, field.errorMessage);
            isValid = false;
        } else {
            clearError(element);
        }
    });

    return isValid;
}

/**
 * Limpiar todos los errores del formulario
 */
function clearAllErrors() {
    $('form[name="factura"] .is-invalid').removeClass('is-invalid');
    $('form[name="factura"] .invalid-feedback').remove();
}

/**
 * Mostrar resumen de errores
 */
function showValidationSummary() {
    const errorCount = $('form[name="factura"] .is-invalid').length;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: `Por favor, corrija los ${errorCount} errores antes de continuar.`,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
        });
    } else {
        alert(`Por favor, corrija los ${errorCount} errores antes de continuar.`);
    }

    // Hacer scroll al primer error
    const firstError = $('form[name="factura"] .is-invalid').first();
    if (firstError.length > 0) {
        $('html, body').animate({
            scrollTop: firstError.offset().top - 100
        }, 500);
        firstError.focus();
    }
}

/**
 * Mostrar error en un campo
 */
function showError(element, message) {
    element.addClass('is-invalid'); // Cambiar estilo del campo
    let feedback = element.next('.invalid-feedback'); // Buscar contenedor de error
    if (feedback.length === 0) {
        feedback = $('<div class="invalid-feedback"></div>'); // Crear contenedor de error si no existe
        element.after(feedback);
    }
    feedback.text(message); // Agregar mensaje de error
}

/**
 * Limpiar error de un campo
 */
function clearError(element) {
    element.removeClass('is-invalid'); // Quitar estilo de error
    const feedback = element.next('.invalid-feedback'); // Buscar mensaje de error
    if (feedback.length > 0) {
        feedback.remove(); // Eliminar mensaje de error
    }
}

/**
 * Formatear el campo "monto"
 */
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
