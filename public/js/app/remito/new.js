var fv;
var fvCliente;
var total = 0;
var indexEntrega = 0;

jQuery(document).ready(function () {
    calcularDescuento();
    initRemitoProductoHandler();
    $('#remito_cantidadDescuento').attr('readonly', true);
    tipoDescuentoHandler();
    cantidadDescuentoHandler();
    initEntregas();
    initClienteSelect2();
});

function initEntregas() {
    initChainedSelect($('#remito_cliente'), $('#remito_entrega_entrega'), __HOMEPAGE_PATH__ + 'remito/lista/entregas', preserve_values);
}

function initSubTotalHandler() {
    $('.precio-unitario').on('keyup', function () {
        if( $('.precio-unitario').val() !== '') {
            let cantidadBandejas = $(this).parent().siblings('.cantidad-bandejas').text();
            $(this).parent().siblings('.subtotal').text(formatCurrency((parseFloat($(this).val()) * parseFloat(cantidadBandejas))));
        }else{
            $(this).parent().siblings('.subtotal').text(formatCurrency(0));
        }
        calcularTotal();
    })
}

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
    fv = FormValidation.formValidation($("form[name=entrega]")[0], {
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

    $(document).off('click', '.link-save-remito-entrega').on('click', '.link-save-remito-entrega', function (e) {

        e.preventDefault();

        let idEntrega = parseInt($('#remito_entrega_entrega').val());

        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "entrega/get-entrega",
            data: {
                id: idEntrega,
            },
        }).done(function (entrega) {
            var productos = entrega.productos;
            $("#remito_entrega_entrega>option[value='"+idEntrega+"']").attr('disabled','disabled');
            $('.tbody-remito-producto').append('<td class="hidden"><input type="hidden" class="pedidoProductoId" name="remito[entregas][' + indexEntrega + '][entrega]" value="' + idEntrega + '"></td>');
            for(var producto in productos) {
                agregarEntregaProducto(productos[producto],indexEntrega);
            }
            $('.tbody-remito-producto').data('index', 0);
            indexEntrega++;
        });

        e.stopPropagation();
    });
}

function agregarEntregaProducto(producto, indexEntrega) {
    var idEntrega = producto['idEntrega']
    var idEntregaProducto = producto['idEntregaProducto']
    var idPedidoProducto = producto['idProducto']
    var textPedidoProducto = producto['textProducto']
    var cantidadBandejas = producto['cantidadBandejas']
    //var adelanto = producto['adelanto']
    var precioUnitario = 0;


    var index = $('.tbody-remito-producto').data('index');

    var removeLink = '\
                    <a href="#" class="btn btn-sm delete-link-inline link-delete-remito-producto remito-producto-borrar tooltips" \n\
                        data-placement="top" data-original-title="">\n\
                        <i class="fa fa-trash text-danger"></i>\n\
                    </a>';

    var item = '\
                    <tr class="tr-remito-producto">\n\
                        <td class="hidden"><input type="hidden" class="pedidoProductoId" name="remito[entregas][' + indexEntrega + '][entrega][entregasProductos][' + index + '][entregaProducto]" value="' + idEntregaProducto + '"></td>\n\
                        <td class="hidden"><input type="hidden" class="pedidoProductoId" name="remito[entregas][' + indexEntrega + '][entrega][entregasProductos][' + index + '][entrega]" value="' + idEntrega + '"></td>\n\
                        <td class="hidden"><input type="hidden" class="pedidoProductoId" name="remito[entregas][' + indexEntrega + '][entrega][entregasProductos][' + index + '][pedidoProducto]" value="' + idPedidoProducto + '"></td>\n\
                        <td class="hidden"><input type="hidden" name="remito[entregas][' + indexEntrega + '][entrega][entregasProductos][' + index + '][cantidadBandejas]" value="' + cantidadBandejas + '"></td>\n\
                        \n\
                        <td class="text-center v-middle">Entrega N° ' + idEntrega  + '</td>\n\
                        <td class="text-center v-middle">' + textPedidoProducto  + '</td>\n\
                        <td class="text-center v-middle cantidad-bandejas">' + cantidadBandejas + '</td>\n\
                        <td class="text-center v-middle"><input class="precio-unitario" type="number" name="remito[entregas][' + indexEntrega + '][entrega][entregasProductos][' + index + '][precioUnitario]" value="' + precioUnitario + '"></td>\n\
                        <td class="text-center v-middle subtotal">'+formatCurrency((parseInt(precioUnitario) * parseInt(cantidadBandejas)))+ '</td>\n\
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

    initSubTotalHandler();
}


function calcularTotal(){
    total = 0;
    $(".subtotal").each(function( index ) {
        var subtotal = $(this).text().replaceAll(',', '');
        total += parseInt(subtotal.slice(1));
    });

    if (!isNaN(total)) {
        $('.total').html(formatCurrency(total));
    }
    calcularDescuento();
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
                            var id = deletableRow.find('.pedidoProductoId').val();
                            $("#entrega_entregaProducto_pedidoProducto>option[value='"+id+"']").removeAttr('disabled');
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
    initChainedSelect($('#entrega_cliente'), $('#entrega_entregaProducto_pedidoProducto'), __HOMEPAGE_PATH__ + 'entrega/lista/productos', preserve_values);
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#remito_submit").off('click').on('click', function (e) {
        e.preventDefault();
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
                        $('.modal-dialog').css('opacity', 0);
                        //$('form[name="remito"]').submit();

                        $.post({
                            url: __HOMEPAGE_PATH__ + "remito/insertar",
                            type: 'post',
                            dataType: 'json',
                            data: $('form[name="remito"]').serialize()
                        }).done(function (result) {
                            if (result.statusText !== 'OK') {
                                Swal.fire({
                                    title: result.statusCode,
                                    text: result.statusText,
                                    icon: "warning"
                                });
                                return false;
                            } else {
                                showDialog({
                                    titulo: '<i class="fa fa-list-ul margin-right-10"></i> REMITO AGREGADO',
                                    contenido: '' +
                                        '<a href="/remito/imprimir-remito/'+result.id+'" target="_blank" class="btn btn-light-primary blue mr-10" title="Imprimir Remito">\n' +
                                        '<i class="fas fa-file-pdf text-white"></i> Imprimir A4\n' +
                                        '</a>'+
                                        '<a href="/remito/imprimir-remito-ticket/'+result.id+'" target="_blank" class="btn btn-light-primary blue mr-10" title="Imprimir Remito">\n' +
                                        '<i class="fas fa-receipt text-white"></i> Imprimir TICKET\n' +
                                        '</a>'+
                                        '<a href="/remito/new" class="btn btn-light-primary blue mr-10" title="Agregar Nuevo Remito">\n' +
                                        '<i class="fas fa-plus text-white"></i> Agregar Nuevo Remito\n' +
                                        '</a>'+
                                        '<a href="/remito/" class="btn btn-light-primary blue mr-10" title="Ver Remitos">\n' +
                                        '<i class="fas fa-search text-white"></i> Ver los Remitos\n' +
                                        '</a>'+
                                        '<a href="/situacion_cliente/'+result.idCliente+'" class="btn btn-light-primary blue mr-10" title="Ver Situacion Cliente">\n' +
                                        '<i class="fas fa-search text-white"></i> Ver Situacion Cliente\n' +
                                        '</a>',
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
                $('.bs-popover-top').hide();
                $('.modal-dialog').css('width', '80%');
                $('.modal-dialog').addClass('modal-xl');
                $('.modal-dialog').addClass('modal-fullscreen-xl-down');
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
    if (!isNaN(totalAux)) {
        $('.total').html(formatCurrency(totalAux));
    }

}

function formatCurrency(total) {
    var neg = false;
    if(total < 0) {
        neg = true;
        total = Math.abs(total);
    }
    return (neg ? "-$" : '$') + parseFloat(total).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
}