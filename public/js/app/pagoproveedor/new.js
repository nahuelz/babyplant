var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initMontoFormat();
    initSelect2();
    initBaseSubmitButton();
    initImputacionFacturas();
    initImputacionHandler();

    $("#pago_proveedor_modoPago option[value='5']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='6']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='7']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='8']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='9']").prop('disabled', true);
    $("#pago_proveedor_modoPago option[value='10']").prop('disabled', true);

    $('#pago_proveedor_modoPago').select2();
    $('#pago_proveedor_imputacion_factura').select2();
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

    // Manejar cambio de modo de pago para mostrar saldo a favor
    $('#pago_proveedor_modoPago').on('change', function() {
        const modoPago = $(this).val();
        
        if (modoPago === '4') { // CUENTA CORRIENTE
            cargarSaldoFavorProveedor();
        } else {
            $('#saldo-favor-container').hide();
        }
    });
}

/**
 * Carga las facturas pendientes del proveedor seleccionado en el select de imputacion.
 */
function initImputacionFacturas() {
    $('#pago_proveedor_proveedor').on('change', function () {
        cargarFacturasProveedor($(this).val());
        
        // Si el modo de pago es CUENTA CORRIENTE, actualizar el saldo a favor
        if ($('#pago_proveedor_modoPago').val() === '4') {
            cargarSaldoFavorProveedor();
        }
    });
    cargarFacturasProveedor($('#pago_proveedor_proveedor').val());

    // Autocompletar monto al seleccionar una factura
    $('#pago_proveedor_imputacion_factura').on('change', function () {
        const selectedOption = $(this).find('option:selected');
        const saldo = selectedOption.data('saldo');
        const facturaMoneda = selectedOption.data('moneda');
        
        if (saldo !== undefined && saldo !== null) {
            // Obtener el monto del pago y su moneda
            const pagoMontoRaw = $('#pago_proveedor_monto').val();
            const pagoMonto = parseFloat((pagoMontoRaw || '').replace(/\./g, '').replace(',', '.')) || 0;
            const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
            const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;
            
            // Convertir el monto del pago a la moneda de la factura
            let pagoMontoEnMonedaFactura = pagoMonto;
            if (pagoMoneda !== facturaMoneda) {
                if (tipoCambio > 0) {
                    if (facturaMoneda === 'USD') {
                        // Pago en ARS, factura en USD: dividir por tipo de cambio
                        pagoMontoEnMonedaFactura = pagoMonto / tipoCambio;
                    } else {
                        // Pago en USD, factura en ARS: multiplicar por tipo de cambio
                        pagoMontoEnMonedaFactura = pagoMonto * tipoCambio;
                    }
                } else {
                    pagoMontoEnMonedaFactura = 0;
                }
            }
            
            // Determinar el monto a usar: el menor entre el saldo y el monto del pago (en moneda de la factura)
            // Redondear hacia abajo a 2 decimales para evitar errores de redondeo acumulados
            const montoAUsar = Math.floor(Math.min(parseFloat(saldo), pagoMontoEnMonedaFactura) * 100) / 100;
            
            // Formatear el monto con separadores de miles
            const montoFormateado = montoAUsar.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            $('#pago_proveedor_imputacion_monto').val(montoFormateado);
        } else {
            $('#pago_proveedor_imputacion_monto').val('');
        }
        actualizarEquivalenteImputacion();
    });

    // Actualizar equivalencia al modificar el monto a imputar
    $('#pago_proveedor_imputacion_monto').on('input', function () {
        actualizarEquivalenteImputacion();
    });

    // Actualizar info del monto del pago al modificar monto, moneda o tipo de cambio
    $('#pago_proveedor_monto, #pago_proveedor_tipoCambio').on('input', function () {
        actualizarMontoPagoInfo();
    });
    $('#pago_proveedor_tipoMoneda').on('change', function () {
        actualizarMontoPagoInfo();
    });
    actualizarMontoPagoInfo();
}

/**
 * Muestra el equivalente del monto a imputar en la otra moneda.
 */
function actualizarEquivalenteImputacion() {
    const label = $('#imputacion-monto-equivalente');
    const selectedOption = $('#pago_proveedor_imputacion_factura').find('option:selected');
    const facturaMoneda = selectedOption.data('moneda');
    const montoRaw = $('#pago_proveedor_imputacion_monto').val();
    const monto = parseFloat((montoRaw || '').replace(/\./g, '').replace(',', '.')) || 0;
    const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;

    if (!facturaMoneda || monto <= 0 || tipoCambio <= 0) {
        label.text('');
        return;
    }

    let equivalente;
    let textoEquivalente;
    if (facturaMoneda === 'USD') {
        equivalente = monto * tipoCambio;
        textoEquivalente = 'Equivale a $ ' + equivalente.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (ARS)';
    } else {
        equivalente = monto / tipoCambio;
        textoEquivalente = 'Equivale a US$ ' + equivalente.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (USD)';
    }

    label.text(textoEquivalente);
}

/**
 * Muestra el monto del pago en ARS y USD.
 */
function actualizarMontoPagoInfo() {
    const pagoMontoRaw = $('#pago_proveedor_monto').val();
    const pagoMonto = parseFloat((pagoMontoRaw || '').replace(/\./g, '').replace(',', '.')) || 0;
    const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
    const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;

    if (pagoMonto <= 0) {
        $('#monto-pago-container').hide();
        return;
    }

    let montoArs = 0;
    let montoUsd = 0;

    if (pagoMoneda === 'USD') {
        montoUsd = pagoMonto;
        montoArs = tipoCambio > 0 ? pagoMonto * tipoCambio : 0;
    } else {
        montoArs = pagoMonto;
        montoUsd = tipoCambio > 0 ? pagoMonto / tipoCambio : 0;
    }

    $('#monto-pago-ars').text('$ ' + montoArs.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));

    $('#monto-pago-usd').text('US$ ' + montoUsd.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));

    $('#monto-pago-container').show();
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
                .attr('data-saldo', f.saldo)
                .attr('data-saldo-ars', f.saldoArs)
                .attr('data-saldo-usd', f.saldoUsd);
            select.append(opt);
        });
    }, 'json');
}

function cargarSaldoFavorProveedor() {
    const idProveedor = $('#pago_proveedor_proveedor').val();
    
    if (!idProveedor) {
        $('#saldo-favor-container').hide();
        return;
    }

    $.post(__HOMEPAGE_PATH__ + 'pago_proveedor/saldo/favor', { id_entity: idProveedor }, function (data) {
        const saldoArs = data.saldoArs || 0;
        const saldoUsd = data.saldoUsd || 0;
        
        $('#saldo-favor-ars').text('$' + saldoArs.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        
        $('#saldo-favor-usd').text('US$' + saldoUsd.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        
        $('#saldo-favor-container').show();
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

        // Validar que el monto a imputar no supere el monto del pago (convertido a la moneda de la factura)
        const pagoMontoRaw = $('#pago_proveedor_monto').val();
        const pagoMonto = parseFloat((pagoMontoRaw || '').replace(/\./g, '').replace(',', '.')) || 0;
        const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
        const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;

        let pagoMontoEnMonedaFactura = pagoMonto;
        if (pagoMoneda !== moneda) {
            if (tipoCambio > 0) {
                if (moneda === 'USD') {
                    pagoMontoEnMonedaFactura = pagoMonto / tipoCambio;
                } else {
                    pagoMontoEnMonedaFactura = pagoMonto * tipoCambio;
                }
            } else {
                pagoMontoEnMonedaFactura = 0;
            }
        }

        if (monto > pagoMontoEnMonedaFactura + 0.01) {
            const simboloPago = pagoMoneda === 'USD' ? 'US$' : '$';
            Swal.fire({
                title: 'El monto a imputar supera el monto del pago.',
                text: `Monto del pago: ${simboloPago} ${pagoMonto.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                icon: 'warning'
            });
            return;
        }

        // Validar que la suma de imputaciones existentes + el nuevo monto no supere el monto del pago
        let totalImputadoEnMonedaPago = 0;
        $('.tr-imputacion').each(function () {
            const imputacionMoneda = $(this).data('moneda');
            const imputacionMonto = parseFloat($(this).data('monto')) || 0;
            let enPago = imputacionMonto;

            if (imputacionMoneda !== pagoMoneda) {
                if (tipoCambio <= 0) {
                    enPago = Infinity;
                } else if (pagoMoneda === 'USD') {
                    enPago = imputacionMonto / tipoCambio;
                } else {
                    enPago = imputacionMonto * tipoCambio;
                }
            }

            totalImputadoEnMonedaPago += enPago;
        });

        // Convertir el nuevo monto a la moneda del pago
        let nuevoMontoEnPago = monto;
        if (moneda !== pagoMoneda) {
            if (tipoCambio <= 0) {
                nuevoMontoEnPago = Infinity;
            } else if (pagoMoneda === 'USD') {
                nuevoMontoEnPago = monto / tipoCambio;
            } else {
                nuevoMontoEnPago = monto * tipoCambio;
            }
        }

        if (totalImputadoEnMonedaPago + nuevoMontoEnPago > pagoMonto + 0.05) {
            const simboloPago = pagoMoneda === 'USD' ? 'US$' : '$';
            const totalDisponible = pagoMonto - totalImputadoEnMonedaPago;
            
            // Calcular el monto disponible en ambas monedas
            let disponibleArs = 0;
            let disponibleUsd = 0;
            
            if (pagoMoneda === 'USD') {
                disponibleUsd = totalDisponible;
                disponibleArs = tipoCambio > 0 ? totalDisponible * tipoCambio : 0;
            } else {
                disponibleArs = totalDisponible;
                disponibleUsd = tipoCambio > 0 ? totalDisponible / tipoCambio : 0;
            }
            
            Swal.fire({
                title: 'La suma de las imputaciones superaría el monto del pago.',
                text: `Monto disponible para imputar: $ ${disponibleArs.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (ARS) / US$ ${disponibleUsd.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (USD)`,
                icon: 'warning'
            });
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
        actualizarTotalImputado();
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
        actualizarTotalImputado();
    });
}

/**
 * Actualiza el total imputado en USD
 */
function actualizarTotalImputado() {
    const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
    const tipoCambio = parseFloat(($('#pago_proveedor_tipoCambio').val() || '').replace(/\./g, '').replace(',', '.')) || 0;
    
    let totalUsd = 0;
    
    $('.tr-imputacion').each(function () {
        const moneda = $(this).data('moneda');
        const monto = parseFloat($(this).data('monto')) || 0;
        
        if (moneda === 'USD') {
            totalUsd += monto;
        } else {
            totalUsd += tipoCambio > 0 ? monto / tipoCambio : 0;
        }
    });
    
    $('#total-imputado-usd').text('US$ ' + totalUsd.toLocaleString('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }));
    
    // Mostrar/ocultar el tfoot según haya imputaciones
    if ($('.tr-imputacion').length > 0) {
        $('.tfoot-imputacion').show();
    } else {
        $('.tfoot-imputacion').hide();
    }
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

    return totalEnMonedaPago <= pagoMonto + 0.05;
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
    // Formatear el valor inicial de los campos de monto al cargar el formulario
    $('.monto-input').each(function() {
        const value = $(this).val();
        if (value && value !== '') {
            // Convertir el valor a número y luego formatearlo
            const numericValue = parseFloat(value.replace(/\./g, '').replace(',', '.'));
            if (!isNaN(numericValue)) {
                const formattedValue = numericValue.toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                $(this).val(formattedValue);
            }
        }
    });

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
                // Validar que si hay monto a imputar escrito, se haya imputado a una factura
                const montoImputacionRaw = $('#pago_proveedor_imputacion_monto').val();
                if (montoImputacionRaw && montoImputacionRaw.trim() !== '') {
                    Swal.fire({
                        title: 'Falta imputar el pago',
                        text: 'Ha ingresado un monto a imputar pero no ha hecho clic en el botón "IMPUTAR A FACTURA". Por favor, haga clic en el botón para agregar la imputación.',
                        icon: 'warning'
                    });
                    return false;
                }

                // Validar que si es CUENTA CORRIENTE, el monto no supere el saldo a favor
                if ($('#pago_proveedor_modoPago').val() === '4') {
                    const idProveedor = $('#pago_proveedor_proveedor').val();
                    if (idProveedor) {
                        // Obtener el saldo a favor del proveedor de forma síncrona
                        let validacionPasada = true;
                        let saldoFavor = 0;
                        const pagoMoneda = $('#pago_proveedor_tipoMoneda').val();
                        const pagoMonto = parseFloat(($('#pago_proveedor_monto').val() || '').replace(/\./g, '').replace(',', '.')) || 0;
                        
                        $.ajax({
                            url: __HOMEPAGE_PATH__ + 'pago_proveedor/saldo/favor',
                            method: 'POST',
                            data: { id_entity: idProveedor },
                            async: false,
                            success: function(data) {
                                const saldoArs = data.saldoArs || 0;
                                const saldoUsd = data.saldoUsd || 0;
                                saldoFavor = pagoMoneda === 'USD' ? saldoUsd : saldoArs;
                                
                                if (pagoMonto > saldoFavor + 0.01) {
                                    validacionPasada = false;
                                    Swal.fire({
                                        title: 'El monto del pago supera el saldo a favor del proveedor',
                                        text: `Saldo a favor disponible: ${pagoMoneda === 'USD' ? 'US$' : '$'} ${saldoFavor.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                                        icon: 'warning'
                                    });
                                }
                            },
                            error: function() {
                                validacionPasada = false;
                                Swal.fire({
                                    title: 'Error al verificar saldo a favor',
                                    text: 'No se pudo verificar el saldo a favor del proveedor',
                                    icon: 'error'
                                });
                            }
                        });
                        
                        if (!validacionPasada) {
                            return false;
                        }
                    }
                }
                
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