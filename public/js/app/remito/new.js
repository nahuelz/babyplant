var fv;
var fvCliente;
var total = 0;

jQuery(document).ready(function () {
    initFormValidation();
    initRemitoProductoHandler();
    initProductos();
    $('.row-remito-producto-empty').hide();
    initBaseSubmitButton();
    $('.submit-button').html('Guardar');
    initPreValidation();
    calcularDescuento();
    $('#remito_cantidadDescuento').attr('readonly', true);
    tipoDescuentoHandler();
    cantidadDescuentoHandler();
});

function tipoDescuentoHandler() {
    $('#remito_tipoDescuento').on('change', function () {
        if ($(this).val() !== ''){
            $('#remito_cantidadDescuento').attr('readonly', false);
        }else{
            $('#remito_cantidadDescuento').val('');
            $('#remito_cantidadDescuento').attr('readonly', true);
        }
        calcularDescuento();
    })
}

function cantidadDescuentoHandler() {
    $('#remito_cantidadDescuento').on('keyup', function () {
        calcularDescuento();
    })

    $('#remito_cantidadDescuento').on('paste', function () {
        calcularDescuento();
    })
}
/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=remito]")[0], {
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
function initRemitoProductoHandler() {

    $('.tbody-remito-producto').data('index', $('.tr-remito-producto').length);

    updateDeleteLinkRemitoProducto($(".link-delete-remito-producto"), '.tr-remito-producto');

    // Save CaracteristicaProducto handler
    $(document).off('click', '.link-save-remito-producto').on('click', '.link-save-remito-producto', function (e) {

        e.preventDefault();

        var pedidoProductoSelect = $('#remito_remitoProducto_pedidoProducto');
        var pedidoProducto = pedidoProductoSelect.val();
        var cantBandejas = $('#remito_remitoProducto_cantBandejas').val();
        var precioUnitario = $('#remito_remitoProducto_precioUnitario').val();

        if (existeProducto(pedidoProducto)){
            Swal.fire({
                title: "Ya agregó este producto.",
                icon: "warning"
            });

            return false;
        }

        if (cantBandejas === '0' ) {
            Swal.fire({
                title: "La cantidad de bandejas a entregar no puede ser 0.",
                icon: "warning"
            });
            return false;
        }


        if (pedidoProducto === '' || cantBandejas === '' || precioUnitario === '') {
            Swal.fire({
                title: "Debe completar todos los datos del producto.",
                icon: "warning"
            });

        } else {

            $("#remito_remitoProducto_pedidoProducto>option[value='"+pedidoProducto+"']").attr('disabled','disabled');

            var index = $('.tbody-remito-producto').data('index');

            var removeLink = '\
                        <a href="#" class="btn btn-sm delete-link-inline link-delete-remito-producto remito-producto-borrar tooltips" \n\
                            data-placement="top" data-original-title="">\n\
                            <i class="fa fa-trash text-danger"></i>\n\
                        </a>';

            var item = '\
                        <tr class="tr-remito-producto">\n\
                            <td class="hidden"><input type="hidden" class="pedidoProductoId" name="remito[remitosProductos][' + index + '][pedidoProducto]" value="' + pedidoProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="remito[remitosProductos][' + index + '][cantBandejas]" value="' + cantBandejas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="remito[remitosProductos][' + index + '][precioUnitario]" value="' + precioUnitario + '"></td>\n\
                            \n\
                            <td class="text-center v-middle">' + pedidoProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantBandejas + '</td>\n\
                            <td class="text-center v-middle">'+formatCurrency(precioUnitario)+'</td>\n\
                            <td class="text-center v-middle subtotal">'+formatCurrency((parseInt(precioUnitario) * parseInt(cantBandejas)))+ '</td>\n\
                            <td class="text-center v-middle">' + removeLink + '</td>\n\
                        </tr>';

            $('.tbody-remito-producto').append(item);
            $('.tbody-remito-producto').data('index', index + 1);

            $('.tbody-remito-producto tr.tr-remito-producto:last').hide();
            $('.tbody-remito-producto tr.tr-remito-producto').fadeIn("slow");

            updateDeleteLinkRemitoProducto($(".link-delete-remito-producto"), '.tr-remito-producto');

            $('.row-remito-producto-empty').hide('slow');
            $('.row-remito-producto').show('slow');

            //  Reset form
            $('.row-agregar-remito-producto').show('slow');
            clearRemitoProductoForm();
            calcularTotal();
        }

        e.stopPropagation();
    });
}

function calcularTotal(){
    total = 0;
    $(".subtotal").each(function( index ) {
        var subtotal = $(this).text().replace(',', '');
        total += parseInt(subtotal.slice(1));
    });

    $('.total').html(formatCurrency(total));
    calcularDescuento();
}

function existeProducto(id){
    let existe = false;
    $('.pedidoProductoId').each(function(){
        if (id === $(this).val()){
            existe = true;
        }
    });
    return existe;
}

/**
 *
 * @returns {undefined}
 */
function clearRemitoProductoForm() {
    $('#remito_remitoProducto_pedidoProducto').val('').select2();
    $('#remito_remitoProducto_cantBandejas').val('');
    $('#remito_remitoProducto_precioUnitario').val('');
}


/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinkRemitoProducto(deleteLink, closestClassName) {
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
                            if ($('.tr-remito-producto').length === 0) {
                                $('.row-remito-producto').hide('slow');
                            }
                            customDeleteLinkOnCallbackOk();
                            calcularTotal();
                            var id = deletableRow.find('.pedidoProductoId').val();
                            $("#remito_remitoProducto_pedidoProducto>option[value='"+id+"']").removeAttr('disabled');
                        });
                    }
                });
            } else {
                deletableRow.hide('slow', function () {
                    customPreDeleteLinkOnCallbackOk(deletableRow);
                    deletableRow.remove();
                    if ($('.tr-remito-producto').length === 0) {
                        $('.row-remito-producto').hide('slow');
                    }
                    customDeleteLinkOnCallbackOk();
                });
            }

            e.stopPropagation();

        });
    });
}

function initProductos() {
    initChainedSelect($('#remito_cliente'), $('#remito_remitoProducto_pedidoProducto'), __HOMEPAGE_PATH__ + 'remito/lista/productos', preserve_values);
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#remito_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fv.revalidateField('requiredFields');
        if ($('.tr-remito-producto').length === 0) {
            $('.row-remito-producto-empty').show('slow');
            return false;
        }

        fv.validate().then((status) => {
            if (status === "Valid") {
                $.post({
                    url: __HOMEPAGE_PATH__ + "remito/confirmar-remito",
                    type: 'post',
                    dataType: 'json',
                    data: $('form[name="remito"]').serialize()
                }).done(function (result) {
                    if (result.error){
                        Swal.fire({
                            title: result.tipo,
                            text: "La cantidad de bandejas a entregar no puede superar a la cantidad de bandejas faltantes de entrega.",
                            icon: "warning"
                        });

                        return false;
                    }else {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> REMITO',
                            contenido: result.html,
                            color: 'btn-light-success ',
                            labelCancel: 'Cancelar',
                            labelSuccess: 'Confirmar Remito',
                            closeButton: true,
                            callbackCancel: function () {

                            },
                            callbackSuccess: function () {
                                $('form[name="remito"]').submit();
                            }
                        });
                        $('.bs-popover-top').hide();
                        $('.modal-dialog').css('width', '80%');
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                    }
                });
            }
        });

        e.stopPropagation();
    });
}

function calcularDescuento(objeto) {
    let DESCUENTO_PORCENTAJE = '2';
    let totalAux = total;
    const tipodescuento = $('#remito_tipoDescuento').val();
    let valordescuento = parseInt($('#remito_cantidadDescuento').val().trim());
    if (isNaN(valordescuento)){
        valordescuento = 0;
    }
    if (tipodescuento === DESCUENTO_PORCENTAJE && valordescuento < 100) {
        totalAux -= ((total * valordescuento) / 100);
    } else{
        totalAux -= valordescuento;
    }
    $('.total').html(formatCurrency(totalAux));

}

function formatCurrency(total) {
    var neg = false;
    if(total < 0) {
        neg = true;
        total = Math.abs(total);
    }
    return (neg ? "-$" : '$') + parseFloat(total).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
}