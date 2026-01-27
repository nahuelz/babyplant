let fv;
let fvPago;
$(document).ready(function () {
    initAgregarPago();
    initVerHistoricoEstadoRemitoHandler();
    initAdjudicarAdelanto();
    initAdjudicarAdelantoReserva();
    initReservaEntregar();
    initVerHistoricoEstadoReservaHandler();
    initCancelarButton();
    initVerHistoricoEstadoEntregaHandler();
    initMovimientos();
    initAdjudicarCC();
});



function initMovimientos(){
    initMovimiento({
        triggerSelector: '.add-adelanto-pedido',
        urlNew: 'situacion_cliente/adelanto_pedido/new',
        urlCreate: 'situacion_cliente/adelanto_pedido/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Adelanto Pedido'
    });

    initMovimiento({
        triggerSelector: '.add-ajuste-pedido',
        urlNew: 'situacion_cliente/adelanto_pedido/new',
        urlCreate: 'situacion_cliente/adelanto_pedido/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Ajuste Pedido'
    });

    initMovimiento({
        triggerSelector: '.add-adelanto-cc',
        urlNew: 'situacion_cliente/adelanto_cc/new',
        urlCreate: 'situacion_cliente/adelanto_cc/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Adelanto Cuenta Corriente'
    });

    initMovimiento({
        triggerSelector: '.add-ajuste-cc',
        urlNew: 'situacion_cliente/ajuste_cc/new',
        urlCreate: 'situacion_cliente/ajuste_cc/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Ajuste Cuenta Corriente'
    });

    initMovimiento({
        triggerSelector: '.add-adelanto-reserva',
        urlNew: 'situacion_cliente/adelanto_reserva/new',
        urlCreate: 'situacion_cliente/adelanto_reserva/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Adelanto Reserva'
    });

    initMovimiento({
        triggerSelector: '.add-ajuste-reserva',
        urlNew: 'situacion_cliente/ajuste_reserva/new',
        urlCreate: 'situacion_cliente/ajuste_reserva/create',
        tituloModal: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Ajuste Reserva'
    });
}

function initReservaEntregar(){
    $(document).off('click', '.reserva-entregar').on('click', '.reserva-entregar', function (e) {

        e.preventDefault();

        idReserva = $(this).data('id');

        var actionUrl = $(this).data('href');

        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: {
                id: idReserva
            }
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Reserva',
                contenido: form,
                color: 'yellow',
                labelCancel: 'Cerrar',
                labelSuccess: 'Confirmar',
                closeButton: true,
                callbackCancel: function () {
                    return;
                },
                callbackSuccess: function () {
                    $.ajax({
                        type: 'POST',
                        url: __HOMEPAGE_PATH__ + "reserva/"+idReserva+"/realizar-entrega",
                        data: {
                            id: idReserva
                        }
                    }).done(function (form) {
                        window.location.reload();
                    });
                }
            });
            $('.modal-dialog').css('width', '80%');
            $('.modal-dialog').addClass('modal-xl');
            $('.modal-dialog').addClass('modal-fullscreen-xl-down');

        });

        e.stopPropagation();
    });
}

/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=movimiento]")[0], {
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

function initAdjudicarAdelanto() {
    $('.add-pago-adelanto').on('click', function (e) {
        e.preventDefault();

        let row = $(this).closest("tr");

        let adelantoTexto = row.find(".monto-adelanto").text();

        // Limpiar el texto para obtener solo el número
        let adelanto = adelantoTexto.replace(/[^0-9,-]+/g, "").replace(",", ".");
        adelanto = parseFloat(adelanto);

        if (adelanto === 0) {
            Swal.fire({
                title: 'El pedido no posee adelanto.',
                icon: "warning",
            });
            return false;
        } else {

            let remito = $(this).attr("data-remito");
            let monto = $(this).attr("data-monto");
            $.ajax({
                type: 'POST',
                url: __HOMEPAGE_PATH__ + "pago/new",
                data: {
                    monto: monto,
                    idRemito: remito,
                    idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
                },
            }).done(function (form) {
                showDialog({
                    titulo: '<i class="fa fa-list-ul margin-right-10"></i>ADJUDICAR ADELANTO - REMITO N°' + remito,
                    contenido: form,
                    labelCancel: 'Cancelar',
                    labelSuccess: 'Adjudicar Pago',
                    closeButton: true,
                    callbackCancel: function () {
                        return true;
                    },
                    callbackSuccess: function () {
                        $.ajax({
                            type: 'POST',
                            url: __HOMEPAGE_PATH__ + "pago/adjudicar",
                            data: {
                                idRemito: remito,
                                modoPago: 'ADELANTO'
                            },
                        }).done(function (form) {
                            Swal.fire({
                                title: 'Se adjudicó el adelanto.',
                                icon: "success",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        });
                    }
                });
                let modal = $('.modal-dialog');
                $('.modal-body').addClass('pt-1 pb-1');
                modal.css('width', '80%');
                modal.addClass('modal-xl');
                modal.addClass('modal-fullscreen-xl-down');
            });
        }
    });
}

function initAdjudicarAdelantoReserva() {
    $('.add-pago-adelanto-reserva').on('click', function (e) {
        e.preventDefault();

        let row = $(this).closest("tr");

        let adelantoTexto = row.find(".monto-adelanto-reserva").text();

        // Limpiar el texto para obtener solo el número
        let adelanto = adelantoTexto.replace(/[^0-9,-]+/g, "").replace(",", ".");
        adelanto = parseFloat(adelanto);

        if (adelanto === 0) {
            Swal.fire({
                title: 'El pedido no posee adelanto reserva.',
                icon: "warning",
            });
            return false;
        } else {

            let remito = $(this).attr("data-remito");
            let monto = $(this).attr("data-monto");
            $.ajax({
                type: 'POST',
                url: __HOMEPAGE_PATH__ + "pago/new",
                data: {
                    monto: monto,
                    idRemito: remito,
                    idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
                },
            }).done(function (form) {
                showDialog({
                    titulo: '<i class="fa fa-list-ul margin-right-10"></i>ADJUDICAR ADELANTO RESERVA - REMITO N°' + remito,
                    contenido: form,
                    labelCancel: 'Cancelar',
                    labelSuccess: 'Adjudicar Pago',
                    closeButton: true,
                    callbackCancel: function () {
                        return true;
                    },
                    callbackSuccess: function () {
                        $.ajax({
                            type: 'POST',
                            url: __HOMEPAGE_PATH__ + "pago/adjudicar",
                            data: {
                                idRemito: remito,
                                modoPago: 'ADELANTO_RESERVA'
                            },
                        }).done(function (form) {
                            Swal.fire({
                                title: 'Se adjudicó el adelanto.',
                                icon: "success",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        });
                    }
                });
                let modal = $('.modal-dialog');
                $('.modal-body').addClass('pt-1 pb-1');
                modal.css('width', '80%');
                modal.addClass('modal-xl');
                modal.addClass('modal-fullscreen-xl-down');
            });
        }
    });
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
                idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
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
                            let adelanto = $('#adelanto').val();
                            if ((modoPago === '4') && (parseInt(saldo) < parseInt(monto))){ // 4 = CUENTA CORRIENTE
                                Swal.fire({
                                    title: 'No hay saldo suficiente en la cuenta corriente.',
                                    icon: "warning",
                                });
                                return false;
                            }
                            if ((modoPago === '5') && (parseInt(adelanto) < parseInt(monto))){ // 5 = ADELANTO
                                Swal.fire({
                                    title: 'Adelanto insuficiente.',
                                    icon: "warning",
                                });
                                return false;
                            }
                            $.ajax({
                                type: 'POST',
                                url: __HOMEPAGE_PATH__ + "pago/create",
                                dataType: 'json',
                                data: {
                                    modoPago: modoPago,
                                    monto: monto,
                                    idRemito: remito,
                                    idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
                                },
                                success: function (data) {
                                    toastr.success(data.message);
                                    $('.modal').modal('hide'); // <- cierra el primer modal
                                    showDialog({
                                        titulo: 'Imprimir Comprobante Pago',
                                        contenido: '' +
                                            '<a href="/pago/imprimir-comprobante-pago/'+data.id+'" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                            '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                            '</a>'+
                                            '<a href="/pago/imprimir-comprobante-pago-ticket/'+data.id+'" class="btn btn-light-primary blue" style=" float: right; " title="Imprimir comprobante">\n' +
                                            '<i class="fa fa-receipt text-white"></i> Imprimir TICKET\n' +
                                            '</a>',
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
            let modal = $('.modal-dialog');
            $('.modal-body').addClass('pt-1 pb-1');
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

function initMontoFormat() {
    const montoInput = document.querySelector('.monto-input');

    if (montoInput) {
        montoInput.addEventListener('input', function(e) {
            // Si el valor ya está formateado, no hacer nada
            if (e.target.value === '') return;

            // Eliminar todos los caracteres que no sean números
            let value = e.target.value.replace(/[^\d]/g, '');

            // Convertir a número y dividir por 100 para manejar decimales
            const number = parseInt(value || '0', 10) / 100;

            // Formatear como moneda argentina
            e.target.value = number.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    }
}

function initMovimiento(options) {
    const {
        triggerSelector,
        urlNew,
        urlCreate,
        tituloModal
    } = options;

    $(document).on('click', triggerSelector, function (e) {
        e.preventDefault();
        e.stopPropagation();

        const idReserva = $(this).data('reserva');
        const idCuentaCorrienteUsuario = $(this).data('cuentacorrienteusuario');
        const idCliente = $(this).data('idcliente');

        $.ajax({
            type: 'GET',
            url: __HOMEPAGE_PATH__ + urlNew,
            data: { idReserva, idCuentaCorrienteUsuario, idCliente }
        }).done(function (form) {

            showDialog({
                titulo: tituloModal,
                contenido: form,
                labelCancel: 'Cerrar',
                labelSuccess: 'Guardar',
                closeButton: true,
                callbackCancel: () => true,
                callbackSuccess: function () {

                    fv.revalidateField('requiredFields');

                    fv.validate().then((status) => {
                        if (status !== "Valid") {
                            return false;
                        }

                        const formEl = document.querySelector('form[name="movimiento"]');
                        const formData = new FormData(formEl);
                        formData.append('idReserva', $('#idReserva').val());
                        formData.append('idCuentaCorrienteUsuario', $('#idCuentaCorrienteUsuario').val());

                        fetch(__HOMEPAGE_PATH__ + urlCreate, {
                            method: 'POST',
                            body: formData
                        })
                            .then(r => {
                                if (!r.ok) throw new Error();
                                return r.json();
                            })
                            .then(data => {
                                toastr.success(data.message);
                                $('.modal').modal('hide');

                                mostrarComprobanteMovimiento(data.id);
                            })
                            .catch(() => {
                                toastr.error('El monto ingresado supera el saldo disponible.');
                            });

                        return false;
                    });

                    return false;
                }
            });

            prepararModalMovimiento();
        });
    });
}

function prepararModalMovimiento() {
    $("#movimiento_modoPago option[value='4']").prop('disabled', true);
    $("#movimiento_modoPago option[value='5']").prop('disabled', true);
    $("#movimiento_modoPago option[value='6']").prop('disabled', true);
    $("#movimiento_modoPago option[value='7']").prop('disabled', true);

    const modal = $('.modal-dialog');
    $('.modal-body').addClass('pt-1 pb-1');

    modal
        .css('width', '80%')
        .addClass('modal-xl modal-fullscreen-xl-down');

    $('#movimiento_modoPago').select2();
    $('#movimiento_pedido').select2();

    initFormValidation();
    initMontoFormat();
}

function mostrarComprobanteMovimiento(idMovimiento) {
    showDialog({
        titulo: 'Imprimir Comprobante Pago',
        contenido: `
            <a href="/situacion_cliente/imprimir-comprobante-movimiento/${idMovimiento}"
               class="btn btn-light-primary blue">
                <i class="fa fa-file-pdf text-white"></i> Imprimir A4
            </a>
            <a href="/situacion_cliente/imprimir-comprobante-movimiento-ticket/${idMovimiento}"
               class="btn btn-light-primary blue float-end">
                <i class="fa fa-file-pdf text-white"></i> Imprimir TICKET
            </a>
        `,
        labelCancel: 'Cerrar',
        closeButton: true,
        callbackCancel: () => {
            window.location.reload();
            return true;
        }
    });

    $('.submit-button, .bootbox-close-button').hide();
}


/**
 *
 * @returns {undefined}
 */
function initVerHistoricoEstadoReservaHandler() {

    $(document).off('click', '.link-ver-historico-reserva').on('click', '.link-ver-historico-reserva', function (e) {

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

function initCancelarButton() {
    $(document).on('click', '.accion-cancelar', function (e) {
        e.preventDefault();
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Seguro que desea cancelar?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}

function initVerHistoricoEstadoEntregaHandler() {

    $(document).off('click', '.link-ver-historico-entrega').on('click', '.link-ver-historico-entrega', function (e) {

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


function parseMonto(texto) {
    if (!texto) return 0;

    return parseFloat(
        texto.replace(/[^0-9,-]+/g, "").replace(",", ".")
    ) || 0;
}

function validarSaldosPrevios() {
    const adelanto = parseMonto($(".monto-adelanto").text());
    const adelantoReserva = parseMonto($(".monto-adelanto-reserva").text());

    if (adelanto > 0) {
        Swal.fire({ title: 'Primero debe adjudicar el adelanto.', icon: 'warning' });
        return false;
    }

    if (adelantoReserva > 0) {
        Swal.fire({ title: 'Primero debe adjudicar el adelanto reserva.', icon: 'warning' });
        return false;
    }

    return true;
}

function validarSaldoCC() {
    const saldo = parseMonto($(".saldo-cc").text());

    if (saldo === 0) {
        Swal.fire({
            title: 'El cliente no posee saldo en la Cuenta Corriente.',
            icon: 'warning'
        });
        return false;
    }

    return true;
}

function initAdjudicarCC() {
    $('.add-pago-cc').on('click', function (e) {
        e.preventDefault();

        let row = $(this).closest("tr");

        let adelantoTexto = row.find(".monto-adelanto").text();
        let adelantoReservaTexto = row.find(".monto-adelanto-reserva").text();

        // Limpiar el texto para obtener solo el número
        let adelanto = adelantoTexto.replace(/[^0-9,-]+/g, "").replace(",", ".");
        adelanto = parseFloat(adelanto);

        // Limpiar el texto para obtener solo el número
        let adelantoReserva = adelantoReservaTexto.replace(/[^0-9,-]+/g, "").replace(",", ".");
        adelantoReserva = parseFloat(adelantoReserva);

        if (adelanto > 0) {
            Swal.fire({
                title: 'Primero debe adjudicar el adelanto.',
                icon: "warning",
            });
            return false;
        }

        if (adelantoReserva > 0) {
            Swal.fire({
                title: 'Primero debe adjudicar el adelanto reserva.',
                icon: "warning",
            });
            return false;
        }

        let ccTexto = $(".saldo-cc").text();

        // Limpiar el texto para obtener solo el número
        let cc = ccTexto.replace(/[^0-9,-]+/g, "").replace(",", ".");
        cc = parseFloat(cc);

        if (cc === 0) {
            Swal.fire({
                title: 'El cliente no posee saldo en la Cuenta Corriente.',
                icon: "warning",
            });
            return false;
        } else {
            let remito = $(this).attr("data-remito");
            let monto = $(this).attr("data-monto");
            $.ajax({
                type: 'POST',
                url: __HOMEPAGE_PATH__ + "pago/new",
                data: {
                    monto: monto,
                    idRemito: remito,
                    modoPago: 'CC',
                    idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
                },
            }).done(function (form) {
                showDialog({
                    titulo: '<i class="fa fa-list-ul margin-right-10"></i>ADJUDICAR CUENTA CORRIENTE - REMITO N°' + remito,
                    contenido: form,
                    labelCancel: 'Cancelar',
                    labelSuccess: 'Adjudicar Pago',
                    closeButton: true,
                    callbackCancel: function () {
                        return true;
                    },
                    callbackSuccess: function () {
                        $.ajax({
                            type: 'POST',
                            url: __HOMEPAGE_PATH__ + "pago/adjudicar",
                            data: {
                                idRemito: remito,
                                modoPago: 'CC'
                            },
                        }).done(function (form) {
                            Swal.fire({
                                title: 'Se adjudicó el pago.',
                                icon: "success",
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        });
                    }
                });
                let modal = $('.modal-dialog');
                $('.modal-body').addClass('pt-1 pb-1');
                modal.css('width', '80%');
                modal.addClass('modal-xl');
                modal.addClass('modal-fullscreen-xl-down');
            });
        }
    });
}


