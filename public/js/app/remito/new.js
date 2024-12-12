var fv;
var fvCliente;

jQuery(document).ready(function () {
    initFormValidation();
    initRemitoProductoHandler();
    initProductos();
    $('.row-remito-producto-empty').hide();
    initBaseSubmitButton();
});
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

    $("#remito_submit").off('click').on('click', function (e) {

        e.preventDefault();

        fv.revalidateField('requiredFields');
        if ($('.tr-remito-producto').length === 0) {
            $('.row-remito-producto-empty').show('slow');
            $('.row-remito-producto').hide('slow');
            return false;
        }

        fv.validate().then((status) => {

            if (status === "Valid") {
                $('form[name="remito"]').submit();
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


        if (pedidoProducto === '' || cantBandejas === '' || precioUnitario === '') {
            Swal.fire({
                title: "Debe completar todos los datos del producto.",
                icon: "warning"
            });

        } else {

            var index = $('.tbody-remito-producto').data('index');

            var removeLink = '\
                        <a href="#" class="btn btn-sm delete-link-inline link-delete-remito-producto remito-producto-borrar tooltips" \n\
                            data-placement="top" data-original-title="Eliminar">\n\
                            <i class="fa fa-trash text-danger"></i>\n\
                        </a>';

            var item = '\
                        <tr class="tr-remito-producto">\n\
                            <td class="hidden"><input type="hidden" name="remito[remitosProductos][' + index + '][pedidoProducto]" value="' + pedidoProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="remito[remitosProductos][' + index + '][cantBandejas]" value="' + cantBandejas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="remito[remitosProductos][' + index + '][precioUnitario]" value="' + precioUnitario + '"></td>\n\
                            \n\
                            <td class="text-center v-middle">' + pedidoProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantBandejas + '</td>\n\
                            <td class="text-center v-middle">$' + precioUnitario + '</td>\n\
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
        }

        e.stopPropagation();
    });
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
                                $('.row-remito-producto-empty').show('slow');
                                $('.row-remito-producto').hide('slow');
                            }
                            customDeleteLinkOnCallbackOk();
                        });
                    }
                });
            } else {
                deletableRow.hide('slow', function () {
                    customPreDeleteLinkOnCallbackOk(deletableRow);
                    deletableRow.remove();
                    if ($('.tr-remito-producto').length === 0) {
                        $('.row-remito-producto-empty').show('slow');
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