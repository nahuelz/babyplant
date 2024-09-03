
var fv;

jQuery(document).ready(function () {

    initFormValidation();
    initPedidoProductoHandler();
});

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
function initBaseSubmitButton() {

    $("#pedido_submit").off('click').on('click', function (e) {

        e.preventDefault();

        fv.revalidateField('requiredFields');

        fv.validate().then((status) => {

            if (status === "Valid") {
                $('form[name="pago"]').submit();
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
        var origenSemillaSelect = $('#pedido_pedidoProducto_origenSemilla');

        var tipoProducto = tipoProductoSelect.val();
        var tipoSubProducto = tipoSubProductoSelect.val();
        var tipoVariedad = tipoVariedadSelect.val();
        var tipoBandeja = tipoBandejaSelect.val();
        var cantSemillas = $('#pedido_pedidoProducto_cantSemillas').val();
        var cantcantBandejas = $('#pedido_pedidoProducto_cantBandejas').val();
        var origenSemilla = origenSemillaSelect.val();
        var fechaSiembra = $('#pedido_pedidoProducto_fechaSiembra').val();
        var cantDiasProduccion = $('#pedido_pedidoProducto_cantDiasProduccion').val();
        var fechaEntrega = $('#pedido_pedidoProducto_fechaEntrega').val();

        if (tipoProducto === '' || tipoSubProducto === '' || tipoVariedad === '' || tipoBandeja === '' || cantSemillas === '' || cantcantBandejas === '' || origenSemilla === '' || fechaSiembra === '' || cantDiasProduccion === '' || fechaEntrega === '') {
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
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][tipoProducto]" value="' + tipoProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][tipoSubProducto]" value="' + tipoSubProducto + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][tipoVariedad]" value="' + tipoVariedad + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][tipoBandeja]" value="' + tipoBandeja + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][cantSemillas]" value="' + cantSemillas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][cantcantBandejas]" value="' + cantcantBandejas + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][origenSemilla]" value="' + origenSemilla + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][fechaSiembra]" value="' + fechaSiembra + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][cantDiasProduccion]" value="' + cantDiasProduccion + '"></td>\n\
                            <td class="hidden"><input type="hidden" name="pedido[pedidoProducto][' + index + '][fechaEntrega]" value="' + fechaEntrega + '"></td>\n\
                            \n\
                            <td class="text-center v-middle">' + tipoProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoSubProductoSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoVariedadSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + tipoBandejaSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantSemillas + '</td>\n\
                            <td class="text-center v-middle">' + cantcantBandejas + '</td>\n\
                            <td class="text-center v-middle">' + origenSemillaSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + fechaSiembra + '</td>\n\
                            <td class="text-center v-middle">' + cantDiasProduccion + '</td>\n\
                            <td class="text-center v-middle">' + fechaEntrega + '</td>\n\
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
    $('#pedido_pedidoProducto_cantBandejas').val('');
    $('#pedido_pedidoProducto_origenSemilla').val('').select2();
    $('#pedido_pedidoProducto_fechaSiembra').val('');
    $('#pedido_pedidoProducto_cantDiasProduccion').val('');
    $('#pedido_pedidoProducto_fechaEntrega').val('');
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

/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinkPedidoProducto2(deleteLink, closestClassName) {

    deleteLink.each(function () {

        $(this).tooltip();

        $(this).off("click").on('click', function (e) {

            e.preventDefault();

            var deletableRow = $(this).closest(closestClassName);

            showConfirm({
                msg: '¿Desea eliminar el producto?',
                className: 'modal-dialog-small',
                color: 'red',
                callbackOK: function () {
                    deletableRow.hide('slow', function () {

                        deletableRow.remove();

                        if ($('.tr-pedido-producto').length === 0) {
                            $('.row-pedido-producto-empty').show('slow');
                            $('.row-pedido-producto').hide('slow');
                        }

                        return true;
                    });
                }
            });

            e.stopPropagation();
        });
    });
}