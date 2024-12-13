
var init = false;

var $table = $('#table-remito');

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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'remito/index_table/',
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
        pageLength: 5,
        destroy: true,
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']],
        rowGroup: {
            dataSrc: 1
        },
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
            name: 'idRemito',
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
            name: 'idRemito',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'idPedidoProducto',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaCreacion',
            className: 'dt-center'
        },
        {
            targets: index++,
            name: 'cliente',
            width: '250px',
            className: 'dt-center'
        },
        {
            targets: index++,
            name: 'producto',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline margin-0 ' + data.nombreProducto + ' font-weight-bold p-6 ml-15 mr-15" style="width: 220px">' + data.nombreProductoCompleto + '</span>';
                }
                return data.nombreProductoCompleto;
            }
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'precioUnitario',
            className: 'dt-center'
        },
        {
            targets: index++,
            name: 'precioSubTotal',
            className: 'dt-center'
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
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
            (data.remito !== undefined ? '<a class="dropdown-item" href="' + data.remito + '"><i class="la la-edit" style="margin-right: 5px;"></i> Remito A4</a>' : '')
            +
            (data.remitoa6 !== undefined ? '<a class="dropdown-item" href="' + data.remitoa6 + '"><i class="la la-edit" style="margin-right: 5px;"></i> Remito A6</a>' : '')
            +
            (data.remitoa6l !== undefined ? '<a class="dropdown-item" href="' + data.remitoa6l + '"><i class="la la-edit" style="margin-right: 5px;"></i> Remito A6L</a>' : '');

        actions = ' <div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">' + actions + '</div>\
                    </div>';
    }

    return actions;
}
