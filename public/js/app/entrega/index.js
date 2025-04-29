
var init = false;

var $table = $('#table-entrega');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoEntregaHandler();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'entrega/index_table/',
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
            name: 'idEntrega',
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
            name: 'idEntrega',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'idPedidoProducto',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '" target="_blank">' + data.idPedidoProducto + '</a>';
                }
                return data.idPedidoProducto;
            }
        },
        {
            targets: index++,
            name: 'ordenSiembra',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaCreacion',
            className: 'dt-center',
            type: 'date'
        },
        {
            targets: index++,
            name: 'cliente',
            width: '250px',
            className: 'dt-center'
        },
        {
            targets: index++,
            name: 'clienteEntrega',
            width: '250px',
            className: 'dt-center'
        },
        {
            targets: index++,
            name: 'producto',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline margin-0 ' + data.nombreProducto + ' font-weight-bold p-6" style="width: 220px">' + data.nombreProductoCompleto + '</span>';
                }
                return data.nombreProductoCompleto;
            }
        },
        {
            targets: index++,
            name: 'estado',
            width: '90',
            className: 'nowrap text-center align-middle',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.colorEstado + ' font-weight-bold p-4" style="width: 120px">' + data.estado + '</span>';
                }
                return data.estado;
            }
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            className: 'dt-center',
            width: '30px',
            type: 'num'
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
            (data.generar_remito !== undefined ? '<a class="dropdown-item" href="' + data.generar_remito + '" target="_blank"><i class="la la-edit" style="margin-right: 5px;"></i> Generar Remito</a>' : '')
            +
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '" target="_blank"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '')
            +
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '" target="_blank"><i class="la la-search" style="margin-right: 5px;"></i> Ver Entrega</a>' : '')
            +
            (data.historico_estados !== undefined ? '<a class="dropdown-item link-ver-historico-entrega" href="#" data-href="' + data.historico_estados + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '')
            +
            (data.print_pdf !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf + '" target="_blank"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Entrega A4</a>' : '')
            +
            (data.print_pdf_ticket !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf_ticket + '" target="_blank"><i class="la la-receipt" style="margin-right: 5px;"></i> Imprimir Entrega TICKET</a>' : '')
            +
            (data.print_pdf_interno !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf_interno + '" target="_blank"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Entrega Interno</a>' : '')
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

/**
 *
 * @returns {undefined}
 */
function initVerHistoricoEstadoEntregaHandler() {

    $(document).off('click', '.link-ver-historico-entrega').on('click', '.link-ver-historico-entrega', function (e) {

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
            $('.bs-popover-top').hide();
            $('.btn-submit').hide();
        });
    });
}
