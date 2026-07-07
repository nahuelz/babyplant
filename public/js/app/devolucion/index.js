$(document).ready(function () {
    initVerHistoricoEstadoDevolucionHandler();
    initDescartarDevolucionHandler();
});

function initVerHistoricoEstadoDevolucionHandler() {
    $(document).off('click', '.link-ver-historico-devolucion').on('click', '.link-ver-historico-devolucion', function (e) {
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

function initDescartarDevolucionHandler() {
    $(document).off('click', '.btn-descartar-devolucion').on('click', '.btn-descartar-devolucion', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');

        Swal.fire({
            title: '¿Descartar devolución?',
            text: 'Esta acción marcará las bandejas restantes como descartadas y no podrán ser revendidas.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, descartar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
}
