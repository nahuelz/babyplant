
var init = false;

var $table = $('#table-stock');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoHandler();
    $('#multiple').select2();
    initCancelarButton();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'stock/index_table/',
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
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']],
        rowGroup: {
            dataSrc: 1
        },
        serverSide: false,
    });

    init = true;
}

// Hide/show columns
$('input.toggle-vis').on( 'change', function (e) {
    e.preventDefault();
    // Get the column API object
    var column = table.column( $(this).attr('data-column') );
    // Toggle the visibility
    column.visible( ! column.visible() );
} );

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
            width: '5px',
            className: 'dt-center',
            orderable: false,
            render: function (data, type, full, meta) {
                return '';
            },
        },
        {
            targets: index++,
            name: 'id',
            width: '15px',
            className: 'dt-center',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + full[2].path + '" target="_blank">' + data + '</a>';
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'idProducto',
            width: '5px',
            className: 'nowrap text-center margin-0 ',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '" target="_blank">' + data.idProducto + '</a>';
                }
                return data.idProducto;
            }
        },
        {
            targets: index++,
            name: 'nombreVariedad',
            className: 'nowrap text-center margin-0 ',
            width: '150px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline margin-0 font-weight-bold p-6" style="width: 220px;color: #ffffff !important;background-color: ' + data.colorProducto + '">' + data.nombreProductoCompleto + '</span>';
                }
                return data.nombreProductoCompleto;
            }
        },
        {
            targets: index++,
            name: 'nombreCliente',
            className: 'nowrap text-center margin-0 ',
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '" target="_blank">' + data.nombreCliente + '</a>';
                }
                return data.nombreCliente;
            }
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            className: 'dt-center',
            width: '50px',
        },
        {
            targets: index++,
            name: 'fechaEntrega',
            width: '50px',
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
            name: 'ordenSiembra',
            width: '50px',
            className: 'dt-center font-weight-bold',
            searchable: true,
        },
        {
            targets: index++,
            name: 'mesada',
            className: 'dt-center',
            searchable: true,
            width: '50px',
            type: 'num'
        },
        {
            targets: -1,
            name: '',
            title: '',
            width: '50px',
            className: "text-center dt-acciones",
            orderable: false,

            render: dataTablesActionFormatter
        },
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

    if (!jQuery.isEmptyObject(data)) {

        // Generar botón de WhatsApp si el número es válido
        let botonWhatsapp = '';
        if (data.celular !== undefined) {
            let celularLimpio = data.celular.replace(/\D+/g, '');
            let celularConPrefijo = '54' + celularLimpio;

            if (esNumeroWhatsappValido('+' + celularConPrefijo)) {
                let mensaje =
                    `Hola *${full[5].nombreCliente}*,%0A` +
                    `Te informamos que tu pedido N° *${full[5].idPedido}* fue registrado correctamente.%0A` +
                    `Podés ver el detalle y estado de tu pedido en el siguiente enlace:%0A` +
                    `https://dev.babyplant.com.ar${full[14].print}%0A%0A` +
                    `¡Gracias por tu compra! 🪴🌿%0A` +
                    `- *Vivero Babyplant*`;
                botonWhatsapp =
                    '<a class="dropdown-item" href="https://api.whatsapp.com/send?phone='
                    + celularConPrefijo
                    + '&text='
                    + mensaje
                    + '" target="_blank">'
                    + '<i class="la la-whatsapp" style="margin-right: 5px; color: green;"></i> Enviar WhatsApp</a>';
            }
        }

        actions +=
            (data.show_pedido !== undefined ? '<a class="dropdown-item" href="' + data.show_pedido + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Pedido</a>' : '') +
            (data.show_producto !== undefined ? '<a class="dropdown-item" href="' + data.show_producto + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Producto</a>' : '') +
            (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '') +
            (data.historico_estado !== undefined ? '<a class="dropdown-item link-ver-historico-pedido" href="#" data-href="' + data.historico_estado + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '') +
            (data.print !== undefined ? '<a class="dropdown-item" href="' + data.print + '"><i class="la la-edit" style="margin-right: 5px;"></i> Print</a>' : '') +
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '') +
            (data.remito !== undefined ? '<a class="dropdown-item" href="' + data.remito + '"><i class="la la-edit" style="margin-right: 5px;"></i> Remito</a>' : '') +
            (data.cancelar !== undefined ? '<a class="dropdown-item accion-cancelar" href="' + data.cancelar + '"><i class="la la-remove" style="margin-right: 5px;"></i> Cancelar</a>' : '') +
            botonWhatsapp;

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
function initVerHistoricoEstadoHandler() {

    $(document).off('click', '.link-ver-historico-pedido').on('click', '.link-ver-historico-pedido', function (e) {

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
            msg: '¿Desea cancelar este producto?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}

function esNumeroWhatsappValido(numero) {
    const regex = /^\+54\d{10}$/;
    return regex.test(numero);
}

