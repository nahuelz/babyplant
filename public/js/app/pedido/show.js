$(document).ready(function () {
    initVerHistoricoEstadoHandler();
    initActualizarBandejas();
    initCambiarClienteHandler();
    initSubmitCambiarCliente();
    setSameHeight('.portlet-nivel-1');
});

function initCambiarClienteHandler() {
    $(document).off('click', '.js-cambiar-cliente').on('click', '.js-cambiar-cliente', function (e) {
        e.preventDefault();
        const url = $(this).data('href');

        $.ajax({
            url: url,
            type: 'GET',
            success: function (html) {
                $('#modalCambiarCliente').remove();
                $('body').append(html);
                $('#cambiar_cliente_select').select2({
                    dropdownParent: $('#modalCambiarCliente'),
                    width: '100%'
                });
                $('#modalCambiarCliente').modal('show');
            },
            error: function () {
                Swal.fire({ title: 'Error al cargar el formulario.', icon: 'error' });
            }
        });
    });
}

function initSubmitCambiarCliente() {
    $(document).off('submit', '#formCambiarCliente').on('submit', '#formCambiarCliente', function (e) {
        e.preventDefault();
        const $form = $(this);

        if (!$('#cambiar_cliente_select').val()) {
            Swal.fire({ title: 'Debe seleccionar un cliente.', icon: 'warning' });
            return false;
        }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.ok) {
                    $('#modalCambiarCliente').modal('hide');
                    Swal.fire({
                        title: response.message,
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({ title: response.message || 'No se pudo cambiar el cliente.', icon: 'error' });
                }
            },
            error: function (xhr) {
                let msg = 'Error al cambiar el cliente.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ title: msg, icon: 'error' });
            }
        });
    });
}

/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function setSameHeight(target) {

    var maxHeight = 0;

    $(target).each(function () {
        $(this).css('min-height', '0px');
    });

    $(target).each(function () {
        if ($(this)[0].offsetHeight > maxHeight) {
            maxHeight = $(this)[0].offsetHeight;
        }
    });

    $(target).each(function () {
        $(this).css('min-height', maxHeight + 'px');
    });
}

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