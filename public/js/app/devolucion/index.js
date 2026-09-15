$(document).ready(function () {
    dataTablesInit($('#table-devolucion'), {
        order: [[0, 'desc']],
        serverSide: false,
        pageLength: 25,
        lengthMenu: [5, 10, 25, 50, 100],
        responsive: true,
        autoWidth: false,
        scrollX: false,
        fixedHeader: false,
        destroy: true,
        columnDefs: [
            {targets: 0, width: '1%', type: 'num'},
            {targets: 3, width: '10%'},
            {targets: 9, width: '10%'},
            {targets: 10, width: '7%'},
            {targets: 11, width: '8%'},
            {targets: 12, width: '2%', orderable: false}
        ]
    });

    initVerHistoricoEstadoDevolucionHandler();
    initDescartarDevolucionHandler();
    initCancelarDevolucionHandler();
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

function initCancelarDevolucionHandler() {
    $(document).off('click', '.btn-cancelar-devolucion').on('click', '.btn-cancelar-devolucion', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');

        Swal.fire({
            title: '¿Cancelar devolución?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'Cancelar',
            html: '<input id="swal-input-motivo" class="swal2-input" placeholder="Motivo de cancelación (opcional)">',
            preConfirm: function () {
                return document.getElementById('swal-input-motivo').value;
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                var motivo = result.value || '';
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'motivo';
                input.value = motivo;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        });
    });
}
