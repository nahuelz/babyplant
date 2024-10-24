$(document).ready(function () {
    initVerHistoricoEstadoHandler();

});

/**
 *
 * @returns {undefined}
 */
function initVerHistoricoEstadoHandler() {

    $(document).off('click', '.link-ver-historico-pedido').on('click', '.link-ver-historico-pedido', function (e) {

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
            $('.modal-dialog').css('width', '80%');
            $('.modal-dialog').addClass('modal-xl');
            $('.modal-dialog').addClass('modal-fullscreen-xl-down');
            $('.btn-submit').hide();
        });
    });
}