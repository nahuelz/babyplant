var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initMontoFormat();
    initSelect2();
    initBaseSubmitButton();

    $("#pago_proveedor_modoPago option[value='4']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='5']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='6']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='7']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='8']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='9']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='10']").prop('disabled', true);

    $('#pago_proveedor_modoPago').select2();
});

function initSelect2() {
    $('#pago_proveedor_modoPago').select2();

    // Manejar cambio de tipo de moneda
    $('#pago_proveedor_tipoMoneda').on('change', function() {
        const tipoMoneda = $(this).val();
        const tipoCambioContainer = $('#tipo-cambio-container');

        if (tipoMoneda === 'USD') {
            tipoCambioContainer.show();
            // Obtener valor del dólar blue automáticamente
            obtenerValorDolarBlue();
        } else {
            tipoCambioContainer.hide();
            // Limpiar el campo de tipo de cambio cuando no es USD
            $('#pago_proveedor_tipoCambio').val('');
        }
    });

    // Inicializar el estado del campo tipoCambio según el valor actual
    const currentTipoMoneda = $('#pago_proveedor_tipoMoneda').val();
    if (currentTipoMoneda !== 'USD') {
        $('#tipo-cambio-container').hide();
    }
}

/**
 * Validación de formulario
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=pago_proveedor]")[0], {
        fields: {
            requiredFields: {
                selector: '[required="required"]',
                validators: {
                    notEmpty: {
                        message: 'Este campo es requerido'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap: new FormValidation.plugins.Bootstrap(),
            submitButton: new FormValidation.plugins.SubmitButton(),
        }
    });
}

/**
 * Limpiar montos para envío del formulario
 */
function limpiarMontosParaEnvio() {
    $('input[name*="[monto]"]').each(function() {
        const value = $(this).val();
        if (value) {
            const cleanValue = value.replace(/\./g, '').replace(',', '.');
            $(this).val(cleanValue);
        }
    });

    // También limpiar el tipo de cambio si existe
    const tipoCambioInput = $('#pago_proveedor_tipoCambio');
    if (tipoCambioInput.length > 0 && tipoCambioInput.val()) {
        const cleanValue = tipoCambioInput.val().replace(/\./g, '').replace(',', '.');
        tipoCambioInput.val(cleanValue);
    }
}

/**
 * Obtener valor del dólar blue desde la API
 */
function obtenerValorDolarBlue() {
    // Mostrar indicador de carga
    const tipoCambioInput = $('#pago_proveedor_tipoCambio');
    tipoCambioInput.attr('placeholder', 'Cargando...');

    $.ajax({
        url: '/api/dolar-blue/precio-compra',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Formatear el valor con separadores de miles
                const valorFormateado = response.price.toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                tipoCambioInput.val(valorFormateado);

                // Mostrar notificación de éxito
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Valor actualizado',
                        text: `Tipo de cambio actualizado: ${response.formatted}`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                // Mostrar error
                tipoCambioInput.attr('placeholder', '0,00');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se pudo obtener el valor',
                        text: response.message,
                        confirmButtonColor: '#3085d6'
                    });
                }
            }
        },
        error: function() {
            tipoCambioInput.attr('placeholder', '0,00');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servicio de cotización',
                    confirmButtonColor: '#3085d6'
                });
            }
        }
    });
}

/**
 * Formatear campos de monto
 */
function initMontoFormat() {
    $('.monto-input').on('input', function(e) {
        if (e.target.value === '') return;

        // Eliminar todo lo que no sea número o coma
        let value = e.target.value.replace(/[^\d,]/g, '');

        // Asegurar que solo haya una coma
        const parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts.slice(1).join('');
        }

        // Formatear la parte entera con separadores de miles
        if (parts[0]) {
            const integerPart = parts[0].replace(/\D/g, '');
            parts[0] = parseInt(integerPart || '0', 10).toLocaleString('es-AR');
            value = parts.join(',');
        }

        e.target.value = value;
    });
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#pago_proveedor_submit").off('click').on('click', function (e) {

        e.preventDefault();

        fv.revalidateField('requiredFields');

        fv.validate().then((status) => {

            if (status === "Valid") {
                limpiarMontosParaEnvio();
                $.post({
                    url: window.__FACTURA_SUBMIT_URL__ || (__HOMEPAGE_PATH__ + "pago_proveedor/insertar"),
                    type: 'post',
                    dataType: 'json',
                    data: $('form[name="pago_proveedor"]').serialize()
                }).done(function (result) {
                    if (result.statusText !== 'OK') {
                        Swal.fire({
                            title: result.statusCode,
                            text: result.statusText,
                            icon: "warning"
                        });

                        return false;
                    } else {
                        Swal.fire({
                            width: '800px',
                            title: window.__FACTURA_IS_EDIT__ ? '<strong>PAGO ACTUALIZADO!</strong>' : '<strong>PAGO AGREGADO!</strong>',
                            color: "#716add",
                            allowOutsideClick: false,
                            backdrop: false,
                            confirmButtonText: window.__FACTURA_IS_EDIT__ ? 'Volver al listado' : 'Agregar Nuevo Pago',
                            html: '<div class="d-flex flex-row justify-content-center align-items-center w-100">' +
                                '<a href="/pago_proveedor/imprimir-pago_proveedor/'+result.message+'" class="swal2-confirm swal2-styled" title="Imprimir comprobante">\n' +
                                '<i class="fas fa-file-pdf text-white"></i> Imprimir A4\n' +
                                '</a>'+
                                '<a href="/pago_proveedor/imprimir-pago_proveedor-ticket/'+result.message+'" class="swal2-confirm swal2-styled" title="Imprimir comprobante">\n' +
                                '<i class="fas fa-receipt text-white"></i> Imprimir TICKET\n' +
                                '</a>'+
                                '<a href="/pago_proveedor/" class="swal2-confirm swal2-styled" title="Ver Pagos">\n' +
                                '<i class="fas fa-search text-white"></i> Ver Pagos\n' +
                                '</a>'+
                                '</div>',
                            icon: "success"
                        }).then((result) => {
                            window.location.reload();
                        });
                        $('.modal-dialog').css('width', '80%');
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                        $('.submit-button').hide();
                        $('.btn-light-dark').hide();
                        $('.bootbox-close-button').hide();
                    }
                });
                return false;
            }
        });

        e.stopPropagation();
    });
}