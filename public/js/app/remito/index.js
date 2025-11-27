
var init = false;

var $table = $('#table-remito');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoRemitoHandler();
    initClienteSelect2();
    initCancelarButton();
});

function initCancelarButton() {
    $(document).on('click', '.accion-cancelar', function (e) {
        e.preventDefault();
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Desea cancelar este remito?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}

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
            dataSrc: 1,
            endRender: function (rows, group) {
                let precioTotalConDescuento = rows.data()[0][11].precioTotalConDescuento;
                let montoPendiente = rows.data()[0][11].montoPendiente;

                const formatter = new Intl.NumberFormat('es-AR', {style: 'currency', currency: 'ARS'});

                return $('<tr/>').append(`
                    <td colspan="11" class="text-right font-weight-bold">
                        <div>Total Remito con Descuento: ${formatter.format(precioTotalConDescuento)}</div>
                        ${montoPendiente > 0
                    ? `<div class="text-warning">Monto Pendiente: ${formatter.format(montoPendiente)}</div>`
                    : `<div class="text-success">Monto Pendiente: ${formatter.format(montoPendiente)}</div>`}
                    </td>
                    <td></td>
                `);
            }
        },
        serverSide: false,
        colReorder: false,

    });

    setTimeout(function () {
        $table.DataTable().columns.adjust().draw(false);
    }, 500);

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
            type: 'num',
            visible: false
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
            name: 'nombreCliente',
            className: 'nowrap text-center margin-0 ',
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '">' + data.nombreCliente + '</a>';
                }
                return data.nombreCliente;
            }
        },
        {
            targets: index++,
            name: 'producto',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline margin-0 font-weight-bold p-6" style="width: 220px;color: #ffffff !important;background-color: ' + data.colorProducto + '">' + data.nombreProductoCompleto + '</span>';
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
            targets: index++,
            name: 'descuento',
            visible: false, // Podés ocultarla si no querés mostrarla
            render: function (data, type, full, meta) {
                return JSON.stringify(data); // guardamos como string para fácil acceso en endRender
            }
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
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '')
            +
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Remito</a>' : '')
            +
            (data.historico_estados !== undefined ? '<a class="dropdown-item link-ver-historico-remito" href="#" data-href="' + data.historico_estados + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '')
            +
            (data.print_pdf !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf + '"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Remito</a>' : '')
            +
            (data.cancelar !== undefined ? '<a class="dropdown-item accion-cancelar" href="' + data.cancelar + '"><i class="la la-remove" style="margin-right: 5px;"></i> Cancelar</a>' : '')
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
function initVerHistoricoEstadoRemitoHandler() {

    $(document).off('click', '.link-ver-historico-remito').on('click', '.link-ver-historico-remito', function (e) {

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
