var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initFacturaDetalleHandler();
    initMontoFormat();
    $('.row-factura-detalle-empty').hide();
    initSelect2();
    calcularTotal();
    initBaseSubmitButton();
});

function initSelect2() {
    $('#factura_modoPago').select2();
    $('#factura_detalle_concepto').select2();
    $('#factura_detalle_subConcepto').select2();
    
    // Manejar cambio de tipo de moneda
    $('#factura_tipoMoneda').on('change', function() {
        const tipoMoneda = $(this).val();
        const tipoCambioContainer = $('#tipo-cambio-container');
        
        if (tipoMoneda === 'USD') {
            tipoCambioContainer.show();
            // Obtener valor del dólar blue automáticamente
            obtenerValorDolarBlue();
        } else {
            tipoCambioContainer.hide();
            // Limpiar el campo de tipo de cambio cuando no es USD
            $('#factura_tipoCambio').val('');
        }
    });
    
    // Inicializar el estado del campo tipoCambio según el valor actual
    const currentTipoMoneda = $('#factura_tipoMoneda').val();
    if (currentTipoMoneda !== 'USD') {
        $('#tipo-cambio-container').hide();
    }
}

/**
 * Validación de formulario
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=factura]")[0], {
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
 * Manejador de detalles de factura
 */
function initFacturaDetalleHandler() {
    $('.tbody-factura-detalle').data('index', $('.tr-factura-detalle').length);

    updateDeleteLinkFacturaDetalle($(".link-delete-factura-detalle"), '.tr-factura-detalle');

    // Save FacturaDetalle handler
    $(document).off('click', '.link-save-factura-detalle').on('click', '.link-save-factura-detalle', function (e) {
        e.preventDefault();

        const conceptoSelect = $('#factura_detalle_concepto');
        const subConceptoSelect = $('#factura_detalle_subConcepto');
        const montoInput = $('#factura_detalle_monto');
        const descripcionInput = $('#factura_detalle_descripcion');

        const concepto = conceptoSelect.val();
        const subConcepto = subConceptoSelect.val();
        const monto = montoInput.val();
        const descripcion = descripcionInput.val();

        // Validación
        if (!concepto || !monto || parseFloat(monto.replace(/\./g, '').replace(',', '.')) <= 0) {
            Swal.fire({
                title: "Debe completar los datos requeridos del concepto.",
                icon: "warning"
            });
            return;
        }

        const index = $('.tbody-factura-detalle').data('index');

        const removeLink = `
            <a href="#" class="btn btn-sm delete-link-inline link-delete-factura-detalle factura-detalle-borrar tooltips" 
               data-placement="top" data-original-title="Eliminar">
                <i class="fa fa-trash text-danger"></i>
            </a>`;

        const item = `
            <tr class="tr-factura-detalle">
                <td class="hidden"><input type="hidden" name="factura[detalles][${index}][concepto]" value="${concepto}"></td>
                <td class="hidden"><input type="hidden" name="factura[detalles][${index}][subConcepto]" value="${subConcepto}"></td>
                <td class="hidden"><input type="hidden" name="factura[detalles][${index}][monto]" value="${monto}"></td>
                <td class="hidden"><input type="hidden" name="factura[detalles][${index}][descripcion]" value="${descripcion}"></td>
                
                <td class="text-center v-middle">${conceptoSelect.find('option:selected').text()}</td>
                <td class="text-center v-middle">${subConceptoSelect.find('option:selected').text()}</td>
                <td class="text-center v-middle">${descripcion}</td>
                <td class="text-center v-middle">$${parseFloat(monto.replace(/\./g, '').replace(',', '.')).toLocaleString('es-AR', {minimumFractionDigits: 2})}</td>
                <td class="text-center v-middle">${removeLink}</td>
            </tr>`;

        $('.tbody-factura-detalle').append(item);
        $('.tbody-factura-detalle').data('index', index + 1);

        $('.tbody-factura-detalle tr.tr-factura-detalle:last').hide();
        $('.tbody-factura-detalle tr.tr-factura-detalle').fadeIn("slow");

        updateDeleteLinkFacturaDetalle($(".link-delete-factura-detalle"), '.tr-factura-detalle');

        $('.row-factura-detalle-empty').hide('slow');
        $('.row-factura-detalle').show('slow');

        // Reset form
        clearDetalleForm();
        calcularTotal();
    });
}

/**
 * Limpiar formulario de detalle
 */
function clearDetalleForm() {
    $('#factura_detalle_concepto').val('').select2();
    $('#factura_detalle_subConcepto').val('').select2();
    $('#factura_detalle_monto').val('');
    $('#factura_detalle_descripcion').val('');
}

/**
 * Actualizar enlaces de eliminación
 */
function updateDeleteLinkFacturaDetalle(deleteLink, closestClassName) {
    closestClassName = typeof closestClassName !== 'undefined' ? closestClassName : '.row';
    deleteLink.each(function () {
        $(this).tooltip();
        $(this).off("click").on('click', function (e) {
            e.preventDefault();
            const deletableRow = $(this).closest(closestClassName);
            
            show_confirm({
                title: 'Confirmar',
                type: 'warning',
                msg: '¿Confirma la eliminación?',
                callbackOK: function () {
                    deletableRow.hide('slow', function () {
                        deletableRow.remove();
                        if ($('.tr-factura-detalle').length === 0) {
                            $('.row-factura-detalle-empty').show('slow');
                            $('.row-factura-detalle').hide('slow');
                        }
                        calcularTotal();
                    });
                }
            });

            e.stopPropagation();
        });
    });
}

/**
 * Calcular total de la factura
 */
function calcularTotal() {
    let total = 0;
    
    $('.tr-factura-detalle').each(function() {
        const montoText = $(this).find('td').eq(3).text(); // Columna de monto
        if (montoText && montoText !== '') {
            const cleanValue = montoText.replace('$', '').replace(/\./g, '').replace(',', '.');
            const monto = parseFloat(cleanValue) || 0;
            total += monto;
        }
    });
    
    $('#total-factura').text('$' + total.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
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
    const tipoCambioInput = $('#factura_tipoCambio');
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
    const tipoCambioInput = $('#factura_tipoCambio');
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

    $("#factura_submit").off('click').on('click', function (e) {

        e.preventDefault();

        fv.revalidateField('requiredFields');
        if ($('.tr-factura-detalle').length === 0) {
            $('.row-factura-detalle-empty').show('slow');
            $('.row-factura-detalle').hide('slow');
            return false;
        }

        fv.validate().then((status) => {

            if (status === "Valid") {
                $.post({
                    url: __HOMEPAGE_PATH__ + "factura/insertar",
                    type: 'post',
                    dataType: 'json',
                    data: $('form[name="factura"]').serialize()
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
                            title: '<strong>FACTURA AGREGADA!</strong>',
                            color: "#716add",
                            allowOutsideClick: false,
                            backdrop: false,
                            confirmButtonText: 'Agregar Nueva Factura',
                            html: '<div class="d-flex flex-row justify-content-center align-items-center w-100">' +
                                '<a href="/factura/imprimir-factura/'+result.message+'" class="swal2-confirm swal2-styled" title="Imprimir comprobante">\n' +
                                '<i class="fas fa-file-pdf text-white"></i> Imprimir A4\n' +
                                '</a>'+
                                '<a href="/factura/imprimir-factura-ticket/'+result.message+'" class="swal2-confirm swal2-styled" title="Imprimir comprobante">\n' +
                                '<i class="fas fa-receipt text-white"></i> Imprimir TICKET\n' +
                                '</a>'+
                                '<a href="/factura/" class="swal2-confirm swal2-styled" title="Ver Facturas">\n' +
                                '<i class="fas fa-search text-white"></i> Ver Facturas\n' +
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