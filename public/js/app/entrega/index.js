
var init = false;

var $table = $('#table-entrega');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoEntregaHandler();
    initClienteSelect2();
    initCancelarButton();
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
        pageLength: 50,
        destroy: true,
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'filtrados',
                title: 'Reporte Entregas',
                className: 'filtrados',
                exportOptions: {
                    columns: [1, 6, 4, 7, 10, 9, 8],
                    filter: 'applied',
                    page: 'all',
                    format: {
                        header: function(data, columnIdx) {
                            var headers = {
                                1: 'N° Entrega',
                                6: 'Productor',
                                4: 'Fecha',
                                7: 'Especie',
                                10: 'Cantidad Plantines',
                                9: 'Cantidad Bandejas',
                                8: 'Condicion'
                            };
                            return headers[columnIdx] || data;
                        },
                        body: function(data, row, column) {
                            var div = document.createElement('div');
                            div.innerHTML = data;
                            data = div.textContent || div.innerText || '';

                            if (column === 4) {  // quinta columna en el array de exportación
                                return Math.round(parseFloat(data) || 0).toString();
                            }

                            if (column === 3) {  // quinta columna en el array de exportación
                                return (data || '').toString().split(' ')[0]; //solo la primer palabra  (hasta el primer espacio)
                            }

                            return data;
                        }
                    }
                },
                customizeData: function(data) {
                    /*for (var i = 0; i < data.body.length; i++) {
                        if (data.body[i][2]) {
                            //data.body[i][2] = 'PREFIJO-' + data.body[i][2];
                        }
                    }*/
                    console.log('Datos originales:', JSON.stringify(data, null, 2));
                    // Imprime la primera fila de datos para ver su estructura
                    if (data.body && data.body.length > 0) {
                        console.log('Primera fila de datos:', data.body[0]);
                    }
                }
            }
        ],
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
            width: '50px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'idPedidoProducto',
            className: 'nowrap text-center margin-0 ',
            visible: false,
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '">' + data.idPedidoProducto + '</a>';
                }
                return data.idPedidoProducto;
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
            name: 'fechaCreacion',
            className: 'dt-center',
            type: 'date',
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
            name: 'nombreClienteEntrega',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.pathEntrega + '">' + data.nombreClienteEntrega + '</a>';
                }
                return data.nombreClienteEntrega;
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
            name: 'cantidadPlantas',
            visible: false
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
            (data.generar_remito !== undefined ? '<a class="dropdown-item" href="' + data.generar_remito + '"><i class="la la-edit" style="margin-right: 5px;"></i> Generar Remito</a>' : '')
            +
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '')
            +
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Entrega</a>' : '')
            +
            (data.historico_estados !== undefined ? '<a class="dropdown-item link-ver-historico-entrega" href="#" data-href="' + data.historico_estados + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '')
            +
            (data.print_pdf !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf + '"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Entrega A4</a>' : '')
            +
            (data.print_pdf_ticket !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf_ticket + '"><i class="la la-receipt" style="margin-right: 5px;"></i> Imprimir Entrega TICKET</a>' : '')
            +
            (data.print_pdf_interno !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf_interno + '"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Entrega Interno</a>' : '')
            +
            (data.delete !== undefined ? '<a class="dropdown-item accion-borrar" href="' + data.delete + '"><i class="la la-remove" style="margin-right: 5px;"></i> Borrar</a>' : '')
            +
            (data.cancelar !== undefined ? '<a class="dropdown-item accion-cancelar" href="' + data.cancelar + '"><i class="la la-remove" style="margin-right: 5px;"></i> Cancelar </a>' : '')
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

function initCancelarButton() {
    $(document).on('click', '.accion-cancelar', function (e) {
        e.preventDefault();
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Desea cancelar esta entrega?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}
