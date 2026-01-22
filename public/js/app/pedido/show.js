$(document).ready(function () {
    initVerHistoricoEstadoHandler();
    initActualizarBandejas();
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


function initActualizarBandejas(){
    $(document).on('click', '.js-actualizar-bandejas', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const id = $btn.data('id');
        const $display = $('#disponibles-' + id);

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        var actionUrl = $(this).data('href');

        $.ajax({
            url: actionUrl,
            method: 'POST',
            success: function (response) {
                if (!response.ok) {
                    alert(response.message || 'Error inesperado');
                    return;
                }

                const disponibles = response.disponibles;
                const base = response.base;

                // actualizar número
                $display.text(disponibles + ' / ' + base);

                // actualizar colores
                $display
                    .removeClass('text-danger text-success')
                    .addClass(disponibles == 0 ? 'text-danger' : 'text-success');

                $btn.closest('.card')
                    .removeClass('border-danger border-success')
                    .addClass(disponibles == 0 ? 'border-danger' : 'border-success');
            },
            error: function () {
                alert('Error al actualizar las bandejas');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

}