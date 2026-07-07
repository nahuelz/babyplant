$(document).ready(function () {
    initVerHistoricoEstadoReventaHandler();
    initEntregarReventaHandler();
    initCancelarReventaHandler();
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
