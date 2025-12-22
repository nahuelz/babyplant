let fv;
let fvPago;
$(document).ready(function () {
    initAgregarSaldo();
    initAgregarPago();
    initAgregarAdelanto();
    initAgregarAdelantoReserva();
    initVerHistoricoEstadoRemitoHandler();
    initAdjudicarAdelanto();
    initAdjudicarAdelantoReserva();
    initAdjudicarCC();
    enviarCC();
    initReservaEntregar();
    initVerHistoricoEstadoReservaHandler();
    initCancelarButton();
    initVerHistoricoEstadoEntregaHandler();
});

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

function initAgregarAdelanto() {
    $('.add-adelanto').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "situacion_cliente/adelanto/new",
            data: {
                idCliente: __ID_USUARIO__,
            },
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Adelanto',
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
                            let modoPago = $('#movimiento_modoPago').val();
                            let monto = $('#movimiento_monto').val();
                            let descripcion = $('#movimiento_descripcion').val();
                            let idPedido = $('#movimiento_pedido').val();
                            $.ajax({
                                type: 'POST',
                                url: __HOMEPAGE_PATH__ + "situacion_cliente/adelanto/create",
                                dataType: 'json',
                                data: {
                                    modoPago: modoPago,
                                    monto: monto,
                                    descripcion: descripcion,
                                    idUsuario: __ID_USUARIO__,
                                    idPedido: idPedido,
                                },
                                success: function (data) {
                                    toastr.success(data.message);
                                    $('.modal').modal('hide'); // <- cierra el primer modal
                                    showDialog({
                                        titulo: 'Imprimir Comprobante Pago',
                                        contenido: '' +
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento/'+data.id+'" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                            '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                            '</a>'+
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento-ticket/'+data.id+'" class="btn btn-light-primary blue" style=" float: right; " title="Imprimir comprobante">\n' +
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
            $("#movimiento_modoPago>option[value='4']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='5']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='6']").attr('disabled','disabled');
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#movimiento_modoPago').select2();
            $('#movimiento_pedido').select2();
            initFormValidation();
            initMontoFormat();
        });
        e.stopPropagation();
        return true;
    })
}

function initAgregarSaldo() {
    $('.add-saldo').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/new",
            data: {
                idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
            },
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Cuenta Corriente',
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
                            let modoPago = $('#movimiento_modoPago').val();
                            let monto = $('#movimiento_monto').val();
                            let descripcion = $('#movimiento_descripcion').val();
                            $.ajax({
                                type: 'POST',
                                url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/create",
                                dataType: 'json',
                                data: {
                                    modoPago: modoPago,
                                    monto: monto,
                                    descripcion: descripcion,
                                    idCuentaCorrienteUsuario: __ID_CUENTA_CORRIENTE__,
                                    idUsuario: __ID_USUARIO__,
                                },
                                success: function (data) {
                                    if (data.statusCode === 200) {
                                        toastr.success(data.message);
                                        $('.modal').modal('hide'); // <- cierra el primer modal
                                        showDialog({
                                            titulo: 'Imprimir Comprobante Pago',
                                            contenido: '' +
                                                '<a href="/situacion_cliente/imprimir-comprobante-movimiento/'+data.id+'" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                                '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                                '</a>'+
                                                '<a href="/situacion_cliente/imprimir-comprobante-movimiento-ticket/'+data.id+'" class="btn btn-light-primary blue" style=" float: right; " title="Imprimir comprobante">\n' +
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
                                    }else{
                                        toastr.error(data.message);
                                        $('.bootbox-close-button').click();
                                    }
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
            $("#movimiento_modoPago>option[value='4']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='5']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='6']").attr('disabled','disabled');
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#movimiento_modoPago').select2();
            $('#movimiento_pedido').select2();
            initFormValidation();
            initMontoFormat();
        });
        e.stopPropagation();
        return true;
    })
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

function enviarCC() {
    $('.enviar-cc').on('click', function (e) {
        e.preventDefault();
        let remito = $(this).data("remito");

        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se enviará el adelanto a la cuenta corriente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: 'POST',
                    url: __HOMEPAGE_PATH__ + "pago/enviarCC",
                    data: { idRemito: remito },
                    dataType: 'json'  // <--- esto es clave
                }).done(function (result) {
                    console.log(result);
                    //{"message":"Se enviaron $2000 a la cuenta corriente","statusCode":200,"statusText":"OK"}
                    Swal.fire({
                        title: 'Envío exitoso',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(() => {
                        window.location.reload();
                    });
                });
            }
        });
    });
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

function initAgregarAdelantoReserva() {
    $('.add-adelanto-reserva').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "situacion_cliente/adelanto_reserva/new",
            data: {
                idCliente: __ID_USUARIO__,
                idReserva: $(this).attr("data-reserva"),
            },
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Adelanto Reserva',
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
                            const form = document.querySelector('form[name="movimiento"]');
                            const formData = new FormData(form);

                            // Agregar campos adicionales si es necesario
                            formData.append('idCliente', __ID_USUARIO__);
                            formData.append('idReserva', $('#idReserva').val());

                            fetch(__HOMEPAGE_PATH__ + "situacion_cliente/adelanto_reserva/create", {
                                method: 'POST',
                                body: formData
                            })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    toastr.success(data.message);
                                    $('.modal').modal('hide');

                                    showDialog({
                                        titulo: 'Imprimir Comprobante Pago',
                                        contenido: '' +
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento/'+data.id+'" class="btn btn-light-primary blue" title="Imprimir comprobante">\n' +
                                            '<i class="fa fa-file-pdf text-white"></i> Imprimir A4\n' +
                                            '</a>'+
                                            '<a href="/situacion_cliente/imprimir-comprobante-movimiento-ticket/'+data.id+'" class="btn btn-light-primary blue" style="float: right;" title="Imprimir comprobante">\n' +
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
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    toastr.error('Ocurrió un error al procesar la solicitud');
                                });
                        }
                        return false;
                    });
                    return false;
                }
            });
            $("#movimiento_modoPago>option[value='4']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='5']").attr('disabled','disabled');
            $("#movimiento_modoPago>option[value='6']").attr('disabled','disabled');
            let modal = $('.modal-dialog');
            $('.modal-body').addClass('pt-1 pb-1');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#movimiento_modoPago').select2();
            $('#movimiento_pedido').select2();
            initFormValidation();
            initMontoFormat();
        });
        e.stopPropagation();
        return true;
    })
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


