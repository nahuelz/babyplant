
var fv;
var fvCliente;

jQuery(document).ready(function () {
    initFormValidation();
    initFormValidationCliente();
    initPedidoProductoHandler();
    initProductos();
    initCantSemillasHandler();
    initCantBandejasHandler();
    initFechaEntregaHandler();
    initDiasProduccionHandler();
    $('.row-pedido-producto-empty').hide();
    initAgregarClienteModal();
    initAgregarClienteHandler();
    initRazonSocialHandler();
    $('#pedido_pedidoProducto_cantDiasProduccion').val('20');
    initFechaSiembra();
    initDiasProduccionSelectHandler();

});

function initDiasProduccionSelectHandler(){
    $('#pedido_pedidoProducto_cantDiasProduccionSelect').on('change', function(){
        $('#pedido_pedidoProducto_cantDiasProduccion').val($('#pedido_pedidoProducto_cantDiasProduccionSelect').val()).change()
    });
}

function initAgregarClienteHandler() {
    $('.link-agregar-cliente').on('click', function(){
        initTipoUsuario();
        initRazonSocial();
    });
}

function initFechaSiembra(){
    $( "#pedido_pedidoProducto_fechaSiembraPedido" ).datepicker( "setDate", "today" );
    var fechaEntregaPedido = $('#pedido_pedidoProducto_fechaSiembraPedido').datepicker('getDate');
    fechaEntregaPedido.setDate(fechaEntregaPedido.getDate()+20);
    $('#pedido_pedidoProducto_fechaEntregaPedido').datepicker('setStartDate', fechaEntregaPedido);
    $('#pedido_pedidoProducto_fechaEntregaPedido').datepicker('setDate', fechaEntregaPedido);
}
function initFechaEntregaHandler(){
    $('#pedido_pedidoProducto_fechaEntregaPedido').change(function() {
        setFechaSiembra();
    });
}

function setFechaSiembra(){
    var dias = $('#pedido_pedidoProducto_cantDiasProduccion').val();
    var fechaSiembraPedido = $('#pedido_pedidoProducto_fechaEntregaPedido').datepicker('getDate');
    if (fechaSiembraPedido !== null) {
        fechaSiembraPedido.setDate(fechaSiembraPedido.getDate() - dias);
        if (fechaSiembraPedido.getDay() == 0) {
            fechaSiembraPedido.setDate(fechaSiembraPedido.getDate() + 1);
        }
        $('#pedido_pedidoProducto_fechaSiembraPedido').datepicker('setDate', fechaSiembraPedido);
    }
}

function initDiasProduccionHandler() {
    $('#pedido_pedidoProducto_cantDiasProduccion').on('change', function () {
        setFechaEntrega();
    });

    $('#pedido_pedidoProducto_cantDiasProduccion').on('keyup', function(){
        setFechaEntrega();
    });

    $('#pedido_pedidoProducto_cantDiasProduccion').on('keypress', function(){
        setFechaEntrega();
    });

    $('#pedido_pedidoProducto_cantDiasProduccion').on('paste', function(){
        setFechaEntrega();
    });

    $('#pedido_pedidoProducto_cantDiasProduccion').on('input', function(){
        setFechaEntrega();
    });
}

function setFechaEntrega() {
    var cant_dias  = $('#pedido_pedidoProducto_cantDiasProduccion').val();
    var fecha = new Date();
    fecha.setDate(fecha.getDate() + parseInt(cant_dias));
    $('#pedido_pedidoProducto_fechaEntregaPedido').datepicker('setStartDate',fecha);
    $("#pedido_pedidoProducto_fechaEntregaPedido").datepicker('setDate',fecha);

}

function initCantBandejasHandler(){
    $('#pedido_pedidoProducto_cantBandejasPedidas').on('input',function(e){
        calcularSemillas();
    });
}
function initCantSemillasHandler(){
    $('#pedido_pedidoProducto_tipoBandeja, #pedido_pedidoProducto_origenSemilla').on('change', function(){
        calcularSemillas();
    })
}

function calcularSemillas(){
    var tipoBandejaVal = $('#pedido_pedidoProducto_tipoBandeja').val();
    var tipoBandeja = $('#pedido_pedidoProducto_tipoBandeja :selected').text();
    var cantBandejasPedidas = $('#pedido_pedidoProducto_cantBandejasPedidas').val();

    if ( tipoBandejaVal && cantBandejasPedidas){
        $('#pedido_pedidoProducto_cantSemillas').val(tipoBandeja*cantBandejasPedidas);
    }

}
/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=pedido]")[0], {
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
//          defaultSubmit: new FormValidation.plugins.DefaultSubmit()
        }

    });
}

/**
 *
 * @returns {undefined}
 */
function initFormValidationCliente() {
    fvCliente = FormValidation.formValidation($("form[name=registration_form]")[0], {
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
//          defaultSubmit: new FormValidation.plugins.DefaultSubmit()
        }

    });
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#pedido_submit").off('click').on('click', function (e) {

        e.preventDefault();

        fv.revalidateField('requiredFields');
        if ($('.tr-pedido-producto').length === 0) {
            $('.row-pedido-producto-empty').show('slow');
            $('.row-pedido-producto').hide('slow');
            return false;
        }

        fv.validate().then((status) => {

            if (status === "Valid") {
                $('form[name="pedido"]').submit();
                return false;
            }
        });

        e.stopPropagation();
    });
}

/**
 *
 * @returns {undefined}
 */
function initPedidoProductoHandler() {

    $('.tbody-pedido-producto').data('index', $('.tr-pedido-producto').length);

    updateDeleteLinkPedidoProducto($(".link-delete-pedido-producto"), '.tr-pedido-producto');

    // Save CaracteristicaProducto handler
    $(document).off('click', '.link-save-pedido-producto').on('click', '.link-save-pedido-producto', function (e) {

        e.preventDefault();

        var tipoProductoSelect = $('#pedido_pedidoProducto_tipoProducto');
        var tipoSubProductoSelect = $('#pedido_pedidoProducto_tipoSubProducto');
        var tipoVariedadSelect = $('#pedido_pedidoProducto_tipoVariedad');
        var tipoBandejaSelect = $('#pedido_pedidoProducto_tipoBandeja');
        var origenSemillaSelect = $('#pedido_pedidoProducto_tipoOrigenSemilla');

        var tipoProducto = tipoProductoSelect.val();
        var tipoSubProducto = tipoSubProductoSelect.val();
        var tipoVariedad = tipoVariedadSelect.val();
        var tipoBandeja = tipoBandejaSelect.val();
        var cantSemillas = $('#pedido_pedidoProducto_cantSemillas').val();
        var cantBandejasPedidas = $('#pedido_pedidoProducto_cantBandejasPedidas').val();
        var origenSemilla = origenSemillaSelect.val();
        var fechaSiembraPedido = $('#pedido_pedidoProducto_fechaSiembraPedido').val();
        var cantDiasProduccion = $('#pedido_pedidoProducto_cantDiasProduccion').val();
        var fechaEntregaPedido = $('#pedido_pedidoProducto_fechaEntregaPedido').val();

        if (tipoProducto === '' || tipoSubProducto === '' || tipoVariedad === '' || tipoBandeja === '' || cantSemillas === '' || cantBandejasPedidas === '' || origenSemilla === '' || fechaSiembraPedido === '' || cantDiasProduccion === '' || fechaEntregaPedido === '') {
            Swal.fire({
                title: "Debe completar todos los datos del producto.",
                icon: "warning"
            });

        } else {

            var index = $('.tbody-pedido-producto').data('index');

            var removeLink = '\
                        <a href="#" class="btn btn-sm delete-link-inline link-delete-pedido-producto pedido-producto-borrar tooltips" \n\
                            data-placement="top" data-original-title="Eliminar">\n\
                            <i class="fa fa-trash text-danger"></i>\n\
                        </a>';

            var item = '\
                        <tr class="tr-pedido-producto">\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][tipoProducto]" value="' + tipoProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][tipoSubProducto]" value="' + tipoSubProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][tipoVariedad]" value="' + tipoVariedad + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][tipoBandeja]" value="' + tipoBandeja + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][cantSemillas]" value="' + cantSemillas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][cantBandejasPedidas]" value="' + cantBandejasPedidas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][tipoOrigenSemilla]" value="' + origenSemilla + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][fechaSiembraPedido]" value="' + fechaSiembraPedido + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][cantDiasProduccion]" value="' + cantDiasProduccion + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidosProductos][' + index + '][fechaEntregaPedido]" value="' + fechaEntregaPedido + '"></td>\n\
                            \n\
                            <td class="text-center v-middle">' + tipoProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoSubProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoVariedadSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoBandejaSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantSemillas + '</td>\n\
                            <td class="text-center v-middle">' + cantBandejasPedidas + '</td>\n\
                            <td class="text-center v-middle">' + origenSemillaSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + fechaSiembraPedido + '</td>\n\
                            <td class="text-center v-middle">' + cantDiasProduccion + '</td>\n\
                            <td class="text-center v-middle">' + fechaEntregaPedido + '</td>\n\
                            <td class="text-center v-middle">' + removeLink + '</td>\n\
                        </tr>';

            $('.tbody-pedido-producto').append(item);
            $('.tbody-pedido-producto').data('index', index + 1);

            $('.tbody-pedido-producto tr.tr-pedido-producto:last').hide();
            $('.tbody-pedido-producto tr.tr-pedido-producto').fadeIn("slow");

            updateDeleteLinkPedidoProducto($(".link-delete-pedido-producto"), '.tr-pedido-producto');

            $('.row-pedido-producto-empty').hide('slow');
            $('.row-pedido-producto').show('slow');

            //  Reset form
            $('.row-agregar-pedido-producto').show('slow');
            clearPedidoProductoForm();
            $('#pedido_pedidoProducto_cantDiasProduccion').val('20');
            initFechaSiembra();
        }

        e.stopPropagation();
    });
}

/**
 *
 * @returns {undefined}
 */
function clearPedidoProductoForm() {
    $('#pedido_pedidoProducto_tipoProducto').val('').select2();
    $('#pedido_pedidoProducto_tipoSubProducto').val('').select2();
    $('#pedido_pedidoProducto_tipoVariedad').val('').select2();
    $('#pedido_pedidoProducto_tipoBandeja').val('').select2();
    $('#pedido_pedidoProducto_cantSemillas').val('');
    $('#pedido_pedidoProducto_cantBandejasPedidas').val('');
    $('#pedido_pedidoProducto_origenSemilla').val('').select2();
    $('#pedido_pedidoProducto_fechaSiembraPedido').val('');
    $('#pedido_pedidoProducto_cantDiasProduccion').val('');
    $('#pedido_pedidoProducto_fechaEntregaPedido').val('');
}


/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinkPedidoProducto(deleteLink, closestClassName) {
    closestClassName = typeof closestClassName !== 'undefined' ? closestClassName : '.row';
    deleteLink.each(function () {
        $(this).tooltip();
        $(this).off("click").on('click', function (e) {
            e.preventDefault();
            var deletableRow = $(this).closest(closestClassName);
            if (!checkFormIsEmpty(deletableRow)) {
                show_confirm({
                    title: 'Confirmar',
                    type: 'warning',
                    msg: '¿Confirma la eliminación?',
                    callbackOK: function () {
                        deletableRow.hide('slow', function () {
                            customPreDeleteLinkOnCallbackOk(deletableRow);
                            deletableRow.remove();
                            if ($('.tr-pedido-producto').length === 0) {
                                $('.row-pedido-producto-empty').show('slow');
                                $('.row-pedido-producto').hide('slow');
                            }
                            customDeleteLinkOnCallbackOk();
                        });
                    }
                });
            } else {
                deletableRow.hide('slow', function () {
                    customPreDeleteLinkOnCallbackOk(deletableRow);
                    deletableRow.remove();
                    if ($('.tr-pedido-producto').length === 0) {
                        $('.row-pedido-producto-empty').show('slow');
                        $('.row-pedido-producto').hide('slow');
                    }
                    customDeleteLinkOnCallbackOk();
                });
            }

            e.stopPropagation();

        });
    });
}

function initProductos() {
    initChainedSelect($('#pedido_pedidoProducto_tipoSubProducto'), $('#pedido_pedidoProducto_tipoVariedad'), __HOMEPAGE_PATH__ + 'tipo/variedad/lista/variedades', preserve_values);
    initChainedSelect($('#pedido_pedidoProducto_tipoProducto'), $('#pedido_pedidoProducto_tipoSubProducto'), __HOMEPAGE_PATH__ + 'tipo/sub/producto/lista/subproductos', preserve_values);
}

/**
 *
 * @param {type} table
 * @returns {undefined}
 */
function initAgregarClienteModal(table) {

    $("#registration_form_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fvCliente.revalidateField('requiredFields');

        fvCliente.validate().then((status) => {

            if (status === "Valid") {
                $('form[name="registration_form"]').submit();
                return false;
            }
        });

        e.stopPropagation();
    });
}

function initRazonSocialHandler(){
    $('#registration_form_tieneRazonSocial').on('change', function (){
        initRazonSocial();
    });
}

function initRazonSocial(){
    if ($('#registration_form_tieneRazonSocial').val() === '1'){
        $('.razonSocial').show();
    }else{
        $('.razonSocial').hide();
    }
}

function initTipoUsuario(){
    $('#registration_form_tipoUsuario').val(1).select2();
    $("#registration_form_grupos").val('3').select2();
    $('.datos-personales').show();
    $('.email-nombre-apelldio').show();
    $('.user-password').hide();
    $('.grupo').hide();
    $('.tipo-usuario').hide();
    $("#registration_form_cuit").inputmask("99-99999999-9");
    $("#registration_form_razonSocial_cuit").inputmask("99-99999999-9");
}