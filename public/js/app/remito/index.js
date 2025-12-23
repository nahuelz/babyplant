
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
        pageLength: 50,
        destroy: true,
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'filtrados',
                title: 'Reporte Remitos',
                className: 'filtrados',
                exportOptions: {
                    columns: [1, 5, 4, 6, 11, 8, 9, 10, 13, 14, 7],
                    filter: 'applied',
                    page: 'all',
                    format: {
                        header: function(data, columnIdx) {
                            var headers = {
                                1: 'N° Remito',
                                5: 'Productor',
                                4: 'Fecha',
                                6: 'Especie',
                                11: 'Cantidad Plantines',
                                8: 'Cantidad Bandejas',
                                9: 'Precio Unitario',
                                10: 'Precio Total',
                                13: 'Deudores',
                                14: 'Caj Plant',
                                7: 'Condicion'
                            };
                            return headers[columnIdx] || data;
                        },
                        body: function(data, row, column, node) {
                            if (column === 8 || column === 9) {
                                return formatMoneyAR(data);
                            }
                            if (column === 4) {  // quinta columna en el array de exportación
                                return Math.round(parseFloat(data) || 0).toString();
                            }
                            var div = document.createElement('div');
                            div.innerHTML = data;
                            return div.textContent || div.innerText || '';
                        }
                    }
                }
            }
        ],
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']],
        rowGroup: {
            dataSrc: 1,
            endRender: function (rows, group) {
                let precioTotalConDescuento = rows.data()[0][15].precioTotalConDescuento;
                let montoPendiente = rows.data()[0][15].montoPendiente;

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
            className: 'dt-center',
            width: '15px',
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
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'idPedidoProducto',
            className: 'dt-center',
            type: 'num',
            visible: false
        },
        {
            targets: index++,
            name: 'ordenSiembra',
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
                    return '<span class="label label-inline margin-0 font-weight-bold p-6" style="width: 220px;color: #ffffff !important;background-color: ' + data.colorProducto + '">' + data.nombreProducto + '</span>';
                }
                return data.nombreProducto;
            }
        },
        {
            targets: index++,
            name: 'estado',
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
            name: 'cantidadPlantas',
            visible: false
        },
        {
            targets: index++,
            name: 'montoTotalConDescuentoProducto',
            visible: false
        },
        {
            targets: index++,
            name: 'montoPendienteProducto',
            visible: false
        },
        {
            targets: index++,
            name: 'montoPagoProducto',
            visible: false
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

function parseMoneyAR(value) {
    if (value === null || value === undefined) return 0;

    // Si ya es número, devolverlo
    if (typeof value === 'number') return value;

    let str = value.toString().trim();

    // Si ya es un número decimal estándar (9950.0000)
    if (/^\d+(\.\d+)?$/.test(str)) {
        return parseFloat(str);
    }

    // Formato AR: $ 9.950,00
    str = str
        .replace(/\$/g, '')
        .replace(/\s/g, '')
        .replace(/\./g, '')
        .replace(',', '.');

    return parseFloat(str) || 0;
}

function formatMoneyAR(value) {
    return parseMoneyAR(value).toLocaleString('es-AR', {
        style: 'currency',
        currency: 'ARS'
    });
}
