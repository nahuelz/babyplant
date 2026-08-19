jQuery(document).ready(function () {

    // Inicializar Select2 del filtro de empleado
    var $empleado = $('#empleado');
    if ($empleado.length && !$empleado.data('select2')) {
        $empleado.select2();
    }

    // Inicializar Select2 del filtro de modalidad
    var $modalidad = $('#modalidad');
    if ($modalidad.length && !$modalidad.data('select2')) {
        $modalidad.select2();
    }

    // Inicializar datepicker del período (vista de meses, formato yyyy-mm)
    var $periodo = $('#periodo');
    if ($periodo.length && !$periodo.data('datepicker')) {
        $periodo.datepicker({
            autoclose: true,
            clearBtn: true,
            format: 'yyyy-mm',
            viewMode: 'months',
            minViewMode: 1,
            maxViewMode: 2,
            language: 'es',
            todayHighlight: true
        });
    }

    // Auto-submit del formulario de filtros al cambiar cualquier campo
    var $formFiltros = $empleado.closest('form');
    $empleado.on('change', function () {
        $formFiltros.submit();
    });
    $modalidad.on('change', function () {
        $formFiltros.submit();
    });
    $periodo.on('changeDate', function () {
        $formFiltros.submit();
    });

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
