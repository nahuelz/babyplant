
var init = false;

var $table = $('#table-pago');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoHandler();
    initFiltrosHandler();
    initColumnsHandler();
    $('#multiple').select2();
});

function initColumnsHandler (){
    $('#multiple').on('change', function(e){
        e.preventDefault();
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                columns: JSON.stringify(getTargets())
            },
            url: __HOMEPAGE_PATH__ + "pedido/save_columns/",
            success: function (response) {
                toastr.success(response.message);
                //toggleHiddeColumn();
                location.reload();
            },
            error: function () {
                alert('ah ocurrido un error.');
            }
        });
    });
}

function toggleHiddeColumn() {
    var targets = getTargets();
    $.each(targets , function(index, val) {
        var column = $table.DataTable().columns(val);
        if (column.visible()[0]) {
            column.visible(false);
            $('.filter th:nth-child(' + val + ')').hide();
        }/* else {
            $('.filter th:nth-child(' + val + ')').show();
            column.visible(true);
        }*/
    });
    $table.DataTable().ajax.reload();
}

function getTargets(){
    let stringArray = $('#multiple').val();
    let numberArray = [];
    length = stringArray.length;
    for (let i = 0; i < length; i++) {
        numberArray.push(parseInt(stringArray[i]));
    }
    console.log(numberArray);
    return numberArray;
}

function initFiltrosHandler(){
    $('.mostrar-filtros').on('click', function (){
        $('#filtros').toggle();
        $("#reporte_filtro_cliente").select2();
    });
    $('.mostrar-actividad-reciente').on('click', function (){
        $('#actividad-reciente').toggle();
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'pedido/index_table/',
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
            name: 'id',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaCreacion',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'producto',
            width: '50px',
            className: 'nowrap text-center align-middle',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.nombreProducto + ' font-weight-bold p-4 ml-15 mr-15" style="width: 135px">' + data.producto + '</span>';
                }
                return data.producto;
            }
        },
        {
            targets: index++,
            name: 'cliente',
            width: '35px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'fechaSiembra',
            width: '30px',
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
            width: '30px',
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
            width: '30px',
            className: 'dt-center',
            searchable: true,
            type: 'num'
        },
        {
            targets: index++,
            name: 'mesada',
            width: '30px',
            className: 'dt-center',
            searchable: true,
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
        },
        {
            // hide columns by index number
            targets: getTargets(),
            visible: false,
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
    if (jQuery.isEmptyObject(data)) {
        actions = '';
    } else {

        actions +=
            (data.show !== undefined ? '<a class="dropdown-item" href="' + data.show + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver</a>' : '')
            +
            (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '')
            +
            (data.historico_estado !== undefined ? '<a class="dropdown-item link-ver-historico-pedido" href="#" data-href="' + data.historico_estado + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '')
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
