
var init = false;

var idReserva = null;

var $table = $('#table-reserva');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoReservaHandler();
    initReservaEntregar();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'reserva/index_table/',
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
            name: 'idReserva',
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
            name: 'idReserva',
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
            name: 'clienteReserva',
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
            name: 'estadoPedidoProducto',
            width: '90',
            className: 'nowrap text-center align-middle',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.colorEstadoPedidoProducto + ' font-weight-bold p-4" style="width: 120px">' + data.estadoPedidoProducto + '</span>';
                }
                return data.estadoPedidoProducto;
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
            (data.entregar !== undefined ? '<a class="dropdown-item reserva-entregar" href="#" data-id="' + data.idReserva + '" data-href="' + data.entregar + '"><i class="la la-truck" style="margin-right: 5px;"></i> Entregar</a>' : '')
            +
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '" target="_blank"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '')
            +
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '" target="_blank"><i class="la la-search" style="margin-right: 5px;"></i> Ver Reserva</a>' : '')
            +
            (data.historico_estados !== undefined ? '<a class="dropdown-item link-ver-historico-reserva" href="#" data-href="' + data.historico_estados + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '')
            +
            (data.print_pdf !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf + '" target="_blank"><i class="la la-file-pdf" style="margin-right: 5px;"></i> Imprimir Reserva A4</a>' : '')
            +
            (data.print_pdf_ticket !== undefined ? '<a class="dropdown-item" href="' + data.print_pdf_ticket + '" target="_blank"><i class="la la-receipt" style="margin-right: 5px;"></i> Imprimir Reserva TICKET</a>' : '')
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
function initVerHistoricoEstadoReservaHandler() {

    $(document).off('click', '.link-ver-historico-reserva').on('click', '.link-ver-historico-reserva', function (e) {

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

function initReservaEntregar(){
    $(document).off('click', '.reserva-entregar').on('click', '.reserva-entregar', function (e) {

        e.preventDefault();

        idReserva = $(this).data('id');

        var actionUrl = $(this).data('href');

        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: {
                id: idReserva
            }
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Reserva',
                contenido: form,
                color: 'yellow',
                labelCancel: 'Cerrar',
                labelSuccess: 'Confirmar',
                closeButton: true,
                callbackCancel: function () {
                    return;
                },
                callbackSuccess: function () {
                    $.ajax({
                        type: 'POST',
                        url: __HOMEPAGE_PATH__ + "reserva/"+idReserva+"/realizar-entrega",
                        data: {
                            id: idReserva
                        }
                    }).done(function (form) {
                        window.location.reload();
                    });
                }
            });
            $('.modal-dialog').css('width', '80%');
            $('.modal-dialog').addClass('modal-xl');
            $('.modal-dialog').addClass('modal-fullscreen-xl-down');

        });

        e.stopPropagation();
    });
}
