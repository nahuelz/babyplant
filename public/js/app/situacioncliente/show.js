let fv;
let fvPago;
$(document).ready(function () {
    initAgregarSaldo();
    initAgregarPago();
    initVerHistoricoEstadoRemitoHandler();
});

/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=situacion_cliente]")[0], {
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
 *
 * @returns {undefined}
 */
function initFormValidationPago() {
    fvPago = FormValidation.formValidation($("form[name=pago]")[0], {
        fields: {
            'pago[monto]': {
                validators: {
                    greaterThan: {
                        min: 10,
                        message: 'El minto minimo es 10'
                    }
                }
            },
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

function initAgregarSaldo() {
    $('.add-saldo').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/new",
            data: {
                idCuentaCorriente: __ID_CUENTA_CORRIENTE__,
            },
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Dinero',
                contenido: form,
                labelCancel: 'Cerrar',
                labelSuccess: 'Guardar',
                closeButton: true,
                callbackCancel: function () {
                    return true;
                },
                callbackSuccess: function () {
                    fv.revalidateField('requiredFields');
                    status = fv.validate().then((status) => {
                        if (status === "Valid") {
                            let modoPago = $('#situacion_cliente_modoPago').val();
                            let monto = $('#situacion_cliente_monto').val();
                            let descripcion = $('#situacion_cliente_descripcion').val();
                            let idPedidoProducto = $('#situacion_cliente_pedidoProducto').val();
                            $.ajax({
                                type: 'POST',
                                url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/create",
                                dataType: 'json',
                                data: {
                                    modoPago: modoPago,
                                    monto: monto,
                                    descripcion: descripcion,
                                    idCuentaCorriente: __ID_CUENTA_CORRIENTE__,
                                    idUsuario: __ID_USUARIO__,
                                    idPedidoProducto: idPedidoProducto,
                                },
                                success: function (data) {
                                    toastr.success(data.message);
                                    $('.modal').modal('hide'); // <- cierra el primer modal
                                    showDialog({
                                        titulo: 'Imprimir Comprobante Pago',
                                        contenido: '' +
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento/'+data.id+'" target="_blank" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                                '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                            '</a>'+
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento-ticket/'+data.id+'" target="_blank" class="btn btn-light-primary blue" style=" float: right; " title="Imprimir comprobante">\n' +
                                                '<i class="fa fa-file-pdf text-white"></i> Imprimir TICKET\n' +
                                            '</a>',
                                        labelCancel: 'Cerrar',
                                        labelSuccess: 'Guardar',
                                        closeButton: true,
                                        callbackCancel: function () {
                                            window.location.reload();
                                            return true;
                                        }
                                    });
                                    $('.submit-button').hide();
                                    $('.bootbox-close-button').hide();

                                },
                                error: function () {
                                    return true;
                                }
                            });
                        } else {
                            return false;
                        }
                    });
                    return false;
                }
            });
            $("#situacion_cliente_modoPago>option[value='4']").attr('disabled','disabled');
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#situacion_cliente_modoPago').select2();
            initFormValidation();
        });
        e.stopPropagation();
        return true;
    })
}

function initAgregarPago() {
    $('.add-pago').on('click', function (e) {
        e.preventDefault();
        let remito = $(this).attr("data-remito");
        let monto = $(this).attr("data-monto");
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "pago/new",
            data: {
                monto: monto,
                idRemito: remito,
                idCuentaCorriente: __ID_CUENTA_CORRIENTE__,
            },
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Pago',
                contenido: form,
                labelCancel: 'Cerrar',
                labelSuccess: 'Guardar',
                closeButton: true,
                callbackCancel: function () {
                    return true;
                },
                callbackSuccess: function () {
                    fvPago.revalidateField('requiredFields');
                    status = fvPago.validate().then((status) => {
                        if (status === "Valid") {
                            let modoPago = $('#pago_modoPago').val();
                            let monto = $('#pago_monto').val();
                            let saldo = $('#saldo').val();
                            if ((modoPago === '4') && (parseInt(saldo) < parseInt(monto))){ // 4 = CUENTA CORRIENTE
                                Swal.fire({
                                    title: 'No hay saldo suficiente en la cuenta corriente',
                                    icon: "warning",
                                });
                                return false;
                            } else {
                                $.ajax({
                                    type: 'POST',
                                    url: __HOMEPAGE_PATH__ + "pago/create",
                                    dataType: 'json',
                                    data: {
                                        modoPago: modoPago,
                                        monto: monto,
                                        idRemito: remito,
                                        idCuentaCorriente: __ID_CUENTA_CORRIENTE__,
                                    },
                                    success: function (data) {
                                        toastr.success(data.message);
                                        $('.modal').modal('hide'); // <- cierra el primer modal
                                        showDialog({
                                            titulo: 'Imprimir Comprobante Pago',
                                            contenido: '' +
                                                '<a href="/pago/imprimir-comprobante-pago/'+data.id+'" target="_blank" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                                '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                                '</a>'+
                                                '<a href="/pago/imprimir-comprobante-pago-ticket/'+data.id+'" target="_blank" class="btn btn-light-primary blue" style=" float: right; " title="Imprimir comprobante">\n' +
                                                '<i class="fa fa-file-pdf text-white"></i> Imprimir TICKET\n' +
                                                '</a>',
                                            labelCancel: 'Cerrar',
                                            labelSuccess: 'Guardar',
                                            closeButton: true,
                                            callbackCancel: function () {
                                                window.location.reload();
                                                return true;
                                            }
                                        });
                                        $('.submit-button').hide();
                                        $('.bootbox-close-button').hide();

                                    },
                                    error: function () {
                                        return true;
                                    }
                                });
                            }
                        } else {
                            return false;
                        }
                    });
                    return false;
                }
            });
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#pago_modoPago').select2();
            initFormValidationPago();
        });
        e.stopPropagation();
        return true;
    })
}

/**
 *
 * @returns {undefined}
 */
function initVerHistoricoEstadoRemitoHandler() {

    $(document).off('click', '.link-ver-historico-remito').on('click', '.link-ver-historico-remito', function (e) {

        e.preventDefault();

        var idAmenaza = $(this).data('id');

        var actionUrl = $(this).data('href');

        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: {
                id: idAmenaza
            }
        }).done(function (form) {

            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Hist&oacute;rico de estados',
                contenido: form,
                color: 'yellow',
                labelCancel: 'Cerrar',
                labelSuccess: 'Cerrar',
                closeButton: true,
                callbackCancel: function () {
                    return;
                },
                callbackSuccess: function () {
                    return;
                }
            });
            $('.bs-popover-top').hide();
            $('.btn-submit').hide();
        });
    });
}


