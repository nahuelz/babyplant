
var init = false;

var $table = $('#table-pago');

$(document).ready(function () {
    initTable();
});

/**
 *
 * @returns {undefined}
 */
function initTable() {

    initDataTable();

    $('#kt_search').on('click', function () {
        $('.div-sin-filtrar').hide();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'pedido/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    //"idTipoObligacion": $('#reporte_filtro_tipoObligacion').val(),
                    //"nombreArchivo": $('#reporte_filtro_archivo').val() ? $('#reporte_filtro_archivo option:selected').text().trim() : null,
                    //"idProveedorPago": $('#reporte_filtro_proveedorPago').val(),
                    "idCliente": $('#reporte_filtro_cliente').val()
                },
                "success": fnCallback
            });
        },
        buttons: [
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
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    page: 'all',
                    rows: {
                        search: 'none'
                    }
                }
            }
        ],
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
        serverSide: false
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
            width: '30px',
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
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaCreacion',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'producto',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'cliente',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'tipoBandeja',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaSiembra',
            className: 'dt-center',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY').format('YYYYMMDD');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'fechaEntrega',
            className: 'dt-center',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY').format('YYYYMMDD');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'estado',
            className: 'nowrap text-center align-middle',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.colorEstado + ' font-weight-bold p-4 ml-15 mr-15" style="width: 85px">' + data.estado + '</span>';
                }
                return data.estado;
            }
        },
        {
            targets: index++,
            name: 'ordenSiembra',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'mesada',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
            className: "text-center dt-acciones",
            orderable: false,
            width: '90px',
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
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver</a>' : '')
            +
            (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '')
            +
            (data.estados !== undefined ? '<a class="dropdown-item" href="' + data.estados + '"><i class="la la-refresh" style="margin-right: 5px;"></i> Administrar estados</a>' : '')
            +
            (data.estados !== undefined ? '<a class="dropdown-item" href="' + data.matriculado_historico + '"><i class="la la-address-card" style="margin-right: 5px;"></i> Administrar matrícula</a>' : '')
            +
            (data.estado_cuenta !== undefined ? '<a class="dropdown-item" href="' + data.estado_cuenta + '"><i class="la la-file-alt" style="margin-right: 5px;"></i> Estado de cuenta</a>' : '')
            +
            (data.delete !== undefined ? '<a class="dropdown-item accion-borrar" href="' + data.delete + '"><i class="la la-remove" style="margin-right: 5px;"></i> Borrar</a>' : '')
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
