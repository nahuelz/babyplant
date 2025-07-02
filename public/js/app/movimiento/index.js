
var init = false;

var $table = $('#table-movimiento');

$(document).ready(function () {
    initTable();
    initClienteSelect2();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'movimiento/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    "idCliente": $('#reporte_filtro_cliente').val()
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
        buttons: [
            {
                extend: 'print',
                text: 'print',
                title: '',
                className: 'print',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'pagina',
                title: '',
                className: 'pagina',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'filtrados',
                title: '',
                className: 'filtrados',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    filter: 'applied',
                    page: 'all'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'todos',
                title: '',
                className: 'todos',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    page: 'all',
                    rows: {
                        search: 'none'
                    }
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'pdf',
                title: '',
                className: 'pdf',
                download: 'open',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    page: 'all',
                    filter: 'applied',
                    rows: {
                        search: 'none'

                    }
                }
            }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Función para limpiar y convertir a número
            var parseMonto = function (monto) {
                return typeof monto === 'string'
                    ? parseFloat(monto.replace(/[\$,]/g, '')) || 0
                    : typeof monto === 'number'
                        ? monto
                        : 0;
            };

            // Total general
            var total = api
                .column(4, { search: 'applied' }) // columna 3 es 'monto'
                .data()
                .reduce(function (a, b) {
                    return parseMonto(a) + parseMonto(b);
                }, 0);

            // Mostrar en el footer
            $(api.column(4).footer()).html('$ ' + total.toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        },
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
            name: 'id',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'tipoMovimiento',
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
            name: 'monto',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fecha',
            className: 'dt-center',
            type: 'date'
        },
        {
            targets: index++,
            name: 'cliente',
            className: 'dt-center'
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
            width: '30px',
            className: "text-center dt-acciones",
            orderable: false,

            render: dataTablesActionFormatter
        }
    ];
}

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
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '" target="_blank"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '')
            ;

        actions = ' <div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">' + actions + '</div>\
                    </div>';
    }

    return actions;
}
