jQuery(document).ready(function () {

    // Inicializar Select2 del filtro de vista
    var $vista = $('#vista');
    if ($vista.length && !$vista.data('select2')) {
        $vista.select2();
    }

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

    // Inicializar datepicker del período (años para anual, meses para semanal/mensual)
    var $periodo = $('#periodo');
    if ($periodo.length && !$periodo.data('datepicker')) {
        var esAnual = $vista.val() === 'anual';
        $periodo.datepicker({
            autoclose: true,
            clearBtn: true,
            format: esAnual ? 'yyyy' : 'yyyy-mm',
            viewMode: esAnual ? 'years' : 'months',
            minViewMode: esAnual ? 2 : 1,
            maxViewMode: 2,
            language: 'es',
            todayHighlight: true
        });
    }

    // Auto-submit del formulario de filtros al cambiar cualquier campo
    var $formFiltros = $empleado.closest('form');
    $vista.on('change', function () {
        $formFiltros.submit();
    });
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
