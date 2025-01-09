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
            $('.btn-submit').hide();
        });
    });
}