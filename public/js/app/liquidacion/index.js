jQuery(document).ready(function () {

    $('#btn-generar').on('click', function (e) {
        e.preventDefault();
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: 'Se generarán las liquidaciones en borrador para el período seleccionado. ¿Desea continuar?',
            callbackOK: function () {
                $('#form-generar')[0].submit();
            }
        });
    });

    var $modal = $('#modalSemana');

    $('.abrir-modal-semana').on('click', function (e) {
        e.preventDefault();

        var $link = $(this);
        var semana = $link.data('semana');
        var apellido = $link.data('apellido');
        var nombre = $link.data('nombre');

        $modal.find('#modalSemanaLabel').text('Liquidación Semana ' + semana + ' de ' + apellido + ', ' + nombre);
        $modal.find('#formGuardarSemana').attr('action', $link.data('action'));
        $modal.find('#semana_sueldoBruto').val($link.data('sueldo-bruto'));
        $modal.find('#semana_deducciones').val($link.data('deducciones'));
        $modal.find('#semana_token').val($link.data('token'));

        $modal.modal('show');
    });

});
