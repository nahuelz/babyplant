$(document).ready(function () {
    initVerHistoricoEstadoReventaHandler();
    initEntregarReventaHandler();
    initCancelarReventaHandler();
    initDistribuirSaldoReventaHandler();
});

function initVerHistoricoEstadoReventaHandler() {
    $(document).off('click', '.link-ver-historico-reventa').on('click', '.link-ver-historico-reventa', function (e) {
        e.preventDefault();
        var actionUrl = $(this).data('href');

        $.ajax({
            type: 'POST',
            url: actionUrl
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

function initEntregarReventaHandler() {
    $(document).off('click', '.btn-entregar-reventa').on('click', '.btn-entregar-reventa', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');

        Swal.fire({
            title: '¿Entregar reventa?',
            text: 'Se generará la entrega para el cliente comprador.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
}

function initCancelarReventaHandler() {
    $(document).off('click', '.btn-cancelar-reventa').on('click', '.btn-cancelar-reventa', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');

        Swal.fire({
            title: '¿Cancelar reventa?',
            text: 'Esta acción liberará las bandejas para reventa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
}

function initDistribuirSaldoReventaHandler() {
    $(document).off('click', '.btn-distribuir-saldo-reventa').on('click', '.btn-distribuir-saldo-reventa', function (e) {
        e.preventDefault();
        var actionUrl = $(this).data('href');

        $.get(actionUrl).done(function (form) {
            showDialog({
                titulo: '<i class="la la-hand-holding-usd margin-right-10"></i> Distribuir saldo de reventa',
                contenido: form,
                color: 'blue',
                labelCancel: 'Cancelar',
                labelSuccess: 'Confirmar distribución',
                closeButton: true,
                callbackSuccess: function () {
                    var $form = $('#form-distribuir-saldo-reventa');
                    $.ajax({
                        type: 'POST',
                        url: actionUrl,
                        data: $form.serialize()
                    }).done(function (response) {
                        Swal.fire('Operación realizada', response.message, 'success').then(function () {
                            window.location.reload();
                        });
                    }).fail(function (xhr) {
                        var response = xhr.responseJSON || {};
                        Swal.fire('No se pudo distribuir', response.message || 'Ocurrió un error.', 'error');
                    });

                    return false;
                }
            });

            var total = parseFloat($('#distribucion_total').val());
            $('#monto_cliente_original').on('input', function () {
                var montoCliente = parseFloat(String($(this).val()).replace(',', '.')) || 0;
                $('#monto_plantinera').val(Math.max(0, total - montoCliente).toFixed(2).replace('.', ','));
            });
        });
    });
}
