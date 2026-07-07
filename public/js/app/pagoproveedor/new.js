var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initMontoFormat();
    initSelect2();
    initBaseSubmitButton();
    initImputacionFacturas();
    initImputacionHandler();

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

    // Obtener valor del dólar blue automáticamente al cargar el formulario
    obtenerValorDolarBlue();

    // Manejar cambio de tipo de moneda
    $('#pago_proveedor_tipoMoneda').on('change', function() {
        const tipoMoneda = $(this).val();
        
        // Obtener valor del dólar blue automáticamente cuando es USD
        if (tipoMoneda === 'USD') {
            obtenerValorDolarBlue();
        }
    });
}

/**
 * Carga las facturas pendientes del proveedor seleccionado en el select de imputacion.
 */
function initImputacionFacturas() {
    $('#pago_proveedor_proveedor').on('change', function () {
        cargarFacturasProveedor($(this).val());
    });
    cargarFacturasProveedor($('#pago_proveedor_proveedor').val());

    // Autocompletar monto al seleccionar una factura
    $('#pago_proveedor_imputacion_factura').on('change', function () {
        const selectedOption = $(this).find('option:selected');
        const saldo = selectedOption.data('saldo');
        
        if (saldo !== undefined && saldo !== null) {
            // Formatear el saldo con separadores de miles
            const saldoFormateado = parseFloat(saldo).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            $('#pago_proveedor_imputacion_monto').val(saldoFormateado);
        } else {
            $('#pago_proveedor_imputacion_monto').val('');
        }
    });
}

function cargarFacturasProveedor(idProveedor) {
    const select = $('#pago_proveedor_imputacion_factura');
    if (select.length === 0) {
        return;
    }

    select.html('<option value="">Seleccione una factura</option>');

    if (!idProveedor) {
        return;
    }

    $.post(__HOMEPAGE_PATH__ + 'pago_proveedor/lista/facturas', { id_entity: idProveedor }, function (data) {
        data.forEach(function (f) {
            const opt = $('<option></option>')
                .val(f.id)
                .text(f.denominacion)
                .attr('data-moneda', f.moneda)
                .attr('data-saldo', f.saldo);
            select.append(opt);
        });
    }, 'json');
}

/**
 * Manejo de filas de imputacion (agregar / eliminar).
 */
function initImputacionHandler() {
    $('.tbody-imputacion').data('index', $('.tr-imputacion').length);

    $(document).off('click', '.link-save-imputacion').on('click', '.link-save-imputacion', function (e) {
        console.log('asd');
        e.preventDefault();

        const facturaSelect = $('#pago_proveedor_imputacion_factura');
        const montoInput = $('#pago_proveedor_imputacion_monto');

        const facturaId = facturaSelect.val();
        const facturaText = facturaSelect.find('option:selected').text();
        const moneda = facturaSelect.find('option:selected').data('moneda') || '';
        const saldo = parseFloat(facturaSelect.find('option:selected').data('saldo')) || 0;
        const montoRaw = montoInput.val();
        const monto = parseFloat((montoRaw || '').replace(/\./g, '').replace(',', '.')) || 0;

        if (!facturaId || monto <= 0) {
            Swal.fire({ title: 'Seleccione una factura e ingrese un monto mayor a 0.', icon: 'warning' });
            return;
        }

        if (monto > saldo + 0.01) {
            Swal.fire({ title: 'El monto imputado supera el saldo pendiente de la factura.', icon: 'warning' });
            return;
        }

        let duplicada = false;
        $('.tr-imputacion').each(function () {
            if ($(this).find('input[name*="[factura]"]').val() == facturaId) {
                duplicada = true;
            }
        });
        if (duplicada) {
            Swal.fire({ title: 'Esa factura ya fue imputada.', icon: 'warning' });
            return;
        }

        const index = $('.tbody-imputacion').data('index');
        const simbolo = moneda === 'USD' ? 'US$' : '$';
        const montoFmt = monto.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const row = `
            <tr class="tr-imputacion" data-monto="${monto}" data-moneda="${moneda}">
                <td class="hidden"><input type="hidden" name="pago_proveedor[imputaciones][${index}][factura]" value="${facturaId}"></td>
                <td class="hidden"><input type="hidden" name="pago_proveedor[imputaciones][${index}][monto]" value="${montoRaw}"></td>
                <td class="v-middle text-center">${facturaText}</td>
                <td class="v-middle text-center">${moneda}</td>
                <td class="v-middle text-center">${simbolo} ${montoFmt}</td>
                <td class="text-center v-middle">
                    <a href="#" class="btn btn-sm delete-link-inline link-delete-imputacion">
                        <i class="fa fa-trash text-danger"></i>
                    </a>
                </td>
            </tr>`;

        $('.tbody-imputacion').append(row);
        $('.tbody-imputacion').data('index', index + 1);
        $('.row-imputacion').show();
        $('.row-imputacion-empty').hide();

        facturaSelect.val('');
        montoInput.val('');
        updateDeleteImputacion();
    });

    updateDeleteImputacion();
}

function updateDeleteImputacion() {
    $('.link-delete-imputacion').off('click').on('click', function (e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        row.remove();
        if ($('.tr-imputacion').length === 0) {
            $('.row-imputacion').hide();
        }
    });
}

/**
 * Valida que la suma de imputaciones (convertida a la moneda del pago) no supere el monto del pago.
 */
function validarImputaciones() {
    if ($('.tr-imputacion').length === 0) {
        return true;
    }

    const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
    const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;
    const pagoMonto = parseFloat(($('#pago_proveedor_monto').val() || '').replace(/\./g, '').replace(',', '.')) || 0;

    let totalEnMonedaPago = 0;

    $('.tr-imputacion').each(function () {
        const moneda = $(this).data('moneda');
        const monto = parseFloat($(this).data('monto')) || 0;
        let enPago = monto;

        if (moneda !== pagoMoneda) {
            if (tipoCambio <= 0) {
                enPago = Infinity;
            } else if (pagoMoneda === 'USD') {
                enPago = monto / tipoCambio;
            } else {
                enPago = monto * tipoCambio;
            }
        }

        totalEnMonedaPago += enPago;
    });

    return totalEnMonedaPago <= pagoMonto + 0.01;
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
                if (!validarImputaciones()) {
                    Swal.fire({
                        title: 'La suma de las imputaciones supera el monto del pago.',
                        icon: 'warning'
                    });
                    return false;
                }
                limpiarMontosParaEnvio();
                $.post({
                    url: window.__PAGO_SUBMIT_URL__ || (__HOMEPAGE_PATH__ + "pagoproveedor/insertar"),
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
                            title: window.__PAGO_IS_EDIT__ ? '<strong>PAGO ACTUALIZADO!</strong>' : '<strong>PAGO AGREGADO!</strong>',
                            color: "#716add",
                            allowOutsideClick: false,
                            backdrop: false,
                            confirmButtonText: window.__PAGO_IS_EDIT__ ? 'Volver al listado' : 'Agregar Nuevo Pago',
                            html: '<div class="d-flex flex-row justify-content-center align-items-center w-100">' +
                                '<a href="/proveedor/" class="swal2-confirm swal2-styled" title="Ver Pagos">\n' +
                                '<i class="fas fa-search text-white"></i> Ver Proveedores\n' +
                                '</a>'+
                                '<a href="/factura/" class="swal2-confirm swal2-styled" title="Ver Pagos">\n' +
                                '<i class="fas fa-search text-white"></i> Ver Facturas\n' +
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