
var init = false;

var $table = $('#table-situacion_cliente');

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
        /*if (init) {
            $table.DataTable().ajax.reload();
        }*/
        if ($('#reporte_filtro_cliente').val() !== ''){
            const id = $('#reporte_filtro_cliente').val();
            window.location.href = __HOMEPAGE_PATH__ + "situacion_cliente/" + id;
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'situacion_cliente/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
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
            name: 'email',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'nombre',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'apellido',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'cuit',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'telefono',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: index++,
            name: 'razonSocial',
            width: '30px',
            className: 'dt-center',
            type: 'num'
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
            className: "text-center dt-acciones",
            orderable: false,

            render: dataTablesActionFormatter
        },
        {
            // hide columns by index number
            //targets: 0,
            //visible: false,
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
            (data.show_cliente !== undefined ? '<a class="btn btn-sm btn-light-primary mr-1" href="' + data.show_cliente + '" title="Ver Situación Cliente">' +
                '<i class="la la-search mr-1"></i> Ver' +
                '</a>' : '')
        ;

        /*actions = ' <div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">' + actions + '</div>\
                    </div>';*/
    }

    return actions;
}