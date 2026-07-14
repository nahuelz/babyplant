
var init = false;

var idgasto = null;

var $table = $('#table-gasto');

$(document).ready(function () {
    initTable();
    initModalCambiarEstado();
    actualizarIndicadores();
});

/**
 *
 * @returns {undefined}
 */
function initTable() {

    initDataTable();

    $('#kt_search').on('click', function () {
        if (init) {
            $table.DataTable().ajax.reload();
            setTimeout(actualizarIndicadores, 100);
        }
    });

    $('#kt_reset').on('click', function (e) {
        e.preventDefault();
        $('.datatable-input').each(function () {
            $(this).val('').trigger('change');
            if (init) {
                $table.DataTable().column($(this).data('col-index')).search('', false, false);
            }
        });
        if (init) {
            $table.DataTable().ajax.reload();
            setTimeout(actualizarIndicadores, 100);
        }
    });
}

/**
 *
 * @returns {undefined}
 */
function initDataTable() {

    $table.show();

    dataTablesInit($table, {
        "sAjaxSource": __HOMEPAGE_PATH__ + 'gasto/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    "idConcepto": $('#reporte_filtro_concepto').val()
                },
                "success": fnCallback
            });
        },
        lengthMenu: [5, 10, 25, 50, 100, 500, 1000],
        pageLength: 50,
        destroy: true,
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']],
        serverSide: false,
    });

    init = true;
}

/**
 *
 * @returns {Array}
 */
function datatablesGetColDef() {

    let index = 0;

    return [
        {
            targets: index++,
            name: 'id',
            width: '15px',
            className: 'dt-center',
            orderable: false,
            render: function (data, type, full, meta) {
                return '\
                    <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">\
                        <input type="checkbox" value="" class="kt-checkable">\
                        <span></span>\
                    </label>';
            },
        },
        {
            targets: index++,
            name: 'fecha',
            className: 'dt-center',
            type: 'string'
        },
        {
            targets: index++,
            name: 'concepto',
            className: 'dt-center',
            type: 'string'
        },
        {
            targets: index++,
            name: 'monto',
            className: 'dt-center',
            type: 'string'
        },
        {
            targets: index++,
            name: 'modoPago',
            className: 'dt-center',
            type: 'string'
        },
        {
            targets: index++,
            name: 'nombreEstado',
            orderable: false,
            className: 'nowrap text-center align-middle',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.colorEstado + ' font-weight-bold p-4" style="width: 120px">' + data.nombreEstado + '</span>';
                }
                return data.nombreEstado;
            }
        },
        {
            targets: -1,
            name: '',
            title: '',
            width: '100px',
            className: "text-center dt-acciones",
            orderable: false,

            render: dataTablesActionFormatter
        }
    ];


    /**
     *
     * @param {type} data
     * @param {type} type
     * @param {type} full
     * @param {type} meta
     * @returns {String}
     */
    function dataTablesActionFormatter(data, type, full, meta) {

        let actions = '';

        if (jQuery.isEmptyObject(data)) {
            actions = '';

        } else {

            actions +=
                (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver</a>' : '')
                +
                (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '')
                +
                (data.cambiar_estado !== undefined ? '<a class="dropdown-item" href="#" onclick="abrirModalCambiarEstado(\'' + data.cambiar_estado + '\'); return false;"><i class="la la-exchange-alt" style="margin-right: 5px;"></i> Cambiar Estado</a>' : '')
                +
                (data.situacion_concepto !== undefined ? '<a class="dropdown-item" href="' + data.situacion_concepto + '"><i class="la la-edit" style="margin-right: 5px;"></i> Situacion Empresa</a>' : '')
                +
                (data.delete !== undefined ? '<a class="dropdown-item accion-borrar" href="' + data.delete + '"><i class="la la-remove" style="margin-right: 5px;"></i> Borrar</a>' : '')
            ;

            //acciones adicionales
            actions += dataTablesCustomActionFormatter(data, type, full, meta);

            actions = '<div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">\
                            ' + actions +
                '</div>\
                    </div>';
        }


        return actions;
    }
}

function initModalCambiarEstado() {
    $(document).off('submit', '#formCambiarEstado').on('submit', '#formCambiarEstado', function (e) {
        e.preventDefault();
        const $form = $(this);

        if (!$('#cambiar_estado_select').val()) {
            Swal.fire({ title: 'Debe seleccionar un estado.', icon: 'warning' });
            return false;
        }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#modalCambiarEstado').modal('hide');
                    Swal.fire({
                        title: 'Estado cambiado correctamente',
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false
                    }).then(function () {
                        if ($('#table-gasto').length) {
                            $('#table-gasto').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({ title: response.message || 'No se pudo cambiar el estado.', icon: 'error' });
                }
            },
            error: function () {
                Swal.fire({ title: 'Error al cambiar el estado.', icon: 'error' });
            }
        });
    });
}

function abrirModalCambiarEstado(url) {
    $.ajax({
        url: url,
        type: 'GET',
        success: function (html) {
            $('#modalCambiarEstado').remove();
            $('body').append(html);
            $('#modalCambiarEstado').modal('show');
        },
        error: function () {
            Swal.fire({ title: 'Error al cargar el formulario.', icon: 'error' });
        }
    });
}

function actualizarIndicadores() {
    $.ajax({
        url: __HOMEPAGE_PATH__ + 'gasto/indicadores/',
        type: 'POST',
        data: {
            'fechaDesde': $('#reporte_filtro_fechaDesde').val(),
            'fechaHasta': $('#reporte_filtro_fechaHasta').val(),
            'idConcepto': $('#reporte_filtro_concepto').val()
        },
        dataType: 'json',
        success: function (response) {
            const montoTotal = parseFloat(response.montoTotal || 0);
            const cantidad = parseInt(response.cantidad || 0);

            const montoFormateado = montoTotal.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            $('#tile-monto-total').text('$' + montoFormateado);
            $('#tile-cantidad').text(cantidad);
        },
        error: function () {
            console.error('Error al actualizar indicadores');
        }
    });
}