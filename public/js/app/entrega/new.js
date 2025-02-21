var fv;
var fvCliente;
var total = 0;

jQuery(document).ready(function () {
    initFormValidation();
    initEntregaProductoHandler();
    initProductos();
    $('.row-entrega-producto-empty').hide();
    initBaseSubmitButton();
    $('.submit-button').html('Guardar');
    initPreValidation();
    initClienteEntregaHandler();
});

function initClienteEntregaHandler() {
    $('#entrega_cliente').on('change', function(){
        $('#entrega_clienteEntrega').val('1369').select2();
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
function initEntregaProductoHandler() {

    $('.tbody-entrega-producto').data('index', $('.tr-entrega-producto').length);

    updateDeleteLinkEntregaProducto($(".link-delete-entrega-producto"), '.tr-entrega-producto');

    // Save CaracteristicaProducto handler
    $(document).off('click', '.link-save-entrega-producto').on('click', '.link-save-entrega-producto', function (e) {

        e.preventDefault();

        var pedidoProductoSelect = $('#entrega_entregaProducto_pedidoProducto');
        var pedidoProducto = pedidoProductoSelect.val();
        var cantidadBandejas = $('#entrega_entregaProducto_cantidadBandejas').val();

        if (existeProducto(pedidoProducto)){
            Swal.fire({
                title: "Ya agregó este producto.",
                icon: "warning"
            });

            return false;
        }

        if (cantidadBandejas === '0' ) {
            Swal.fire({
                title: "La cantidad de bandejas a entregar no puede ser 0.",
                icon: "warning"
            });
            return false;
        }


        if (pedidoProducto === '' || cantidadBandejas === '') {
            Swal.fire({
                title: "Debe completar todos los datos del producto.",
                icon: "warning"
            });

        } else {

            $("#entrega_entregaProducto_pedidoProducto>option[value='"+pedidoProducto+"']").attr('disabled','disabled');

            var index = $('.tbody-entrega-producto').data('index');

            var removeLink = '\
                        <a href="#" class="btn btn-sm delete-link-inline link-delete-entrega-producto entrega-producto-borrar tooltips" \n\
                            data-placement="top" data-original-title="">\n\
                            <i class="fa fa-trash text-danger"></i>\n\
                        </a>';

            var item = '\
                        <tr class="tr-entrega-producto">\n\
                            <td class="hidden"><input type="hidden" class="pedidoProductoId" name="entrega[entregasProductos][' + index + '][pedidoProducto]" value="' + pedidoProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="entrega[entregasProductos][' + index + '][cantidadBandejas]" value="' + cantidadBandejas + '"></td>\n\
                            \n\
                            <td class="text-center v-middle">' + pedidoProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantidadBandejas + '</td>\n\
                            <td class="text-center v-middle">' + removeLink + '</td>\n\
                        </tr>';

            $('.tbody-entrega-producto').append(item);
            $('.tbody-entrega-producto').data('index', index + 1);

            $('.tbody-entrega-producto tr.tr-entrega-producto:last').hide();
            $('.tbody-entrega-producto tr.tr-entrega-producto').fadeIn("slow");

            updateDeleteLinkEntregaProducto($(".link-delete-entrega-producto"), '.tr-entrega-producto');

            $('.row-entrega-producto-empty').hide('slow');
            $('.row-entrega-producto').show('slow');

            //  Reset form
            $('.row-agregar-entrega-producto').show('slow');
            clearEntregaProductoForm();
        }

        e.stopPropagation();
    });
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
function clearEntregaProductoForm() {
    $('#entrega_entregaProducto_pedidoProducto').val('').select2();
    $('#entrega_entregaProducto_cantidadBandejas').val('');
}


/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinkEntregaProducto(deleteLink, closestClassName) {
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
                            if ($('.tr-entrega-producto').length === 0) {
                                $('.row-entrega-producto').hide('slow');
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
                    if ($('.tr-entrega-producto').length === 0) {
                        $('.row-entrega-producto').hide('slow');
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

    $("#entrega_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fv.revalidateField('requiredFields');
        if ($('.tr-entrega-producto').length === 0) {
            $('.row-entrega-producto-empty').show('slow');
            return false;
        }

        fv.validate().then((status) => {
            if (status === "Valid") {
                $.post({
                    url: __HOMEPAGE_PATH__ + "entrega/confirmar-entrega",
                    type: 'post',
                    dataType: 'json',
                    data: $('form[name="entrega"]').serialize()
                }).done(function (result) {
                    if (result.error) {
                        Swal.fire({
                            title: result.tipo,
                            text: "La cantidad de bandejas a entregar no puede superar a la cantidad de bandejas faltantes de entrega.",
                            icon: "warning"
                        });

                        return false;
                    } else {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> ENTREGA',
                            contenido: result.html,
                            color: 'btn-light-success ',
                            labelCancel: 'Cancelar',
                            labelSuccess: 'Confirmar Entrega',
                            closeButton: true,
                            callbackCancel: function () {

                            },
                            callbackSuccess: function () {
                                $('form[name="entrega"]').submit();
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