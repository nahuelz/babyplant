var mesada_table = null
var init = false;
var $table = $('#table-mesada');
//var mesada_table = $('#table-mesada');

jQuery(document).ready(function () {
    initDataTable();
    /*dataTablesInit(mesada_table, {
        ajax: __HOMEPAGE_PATH__ + 'tipo/mesada/index_table/',
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
    })*/

    $(document).on('click', '.accion-habilitar', function (e) {
        e.preventDefault();
        var msg = (parseInt($(this).attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea '+ msg +' este tipo mesada?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });

})

/**
 *
 * @returns {undefined}
 */
function initDataTable() {

    $table.show();

    dataTablesInit($table, {
        "sAjaxSource": __HOMEPAGE_PATH__ + 'tipo/mesada/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "success": fnCallback
            });
        },
        lengthMenu: [5, 10, 25, 50, 100, 500, 1000],
        pageLength: 10,
        destroy: true,
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
    let index = 0

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
                    </label>'
            },
        },
        {
            targets: index++,
            name: 'nombre',
            type: 'num',
        },
        {
            targets: index++,
            name: 'capacidad',
        },
        {
            targets: index++,
            name: 'ocupado',
        },
        {
            targets: index++,
            name: 'tipoMesada',
        },
        {
            targets: index++,
            name: 'habilitado',
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
    ]
}

/**
 *
 * @param {type} data
 * @param {type} type
 * @param {type} full
 * @param {type} meta
 * @returns {String}
 */
function dataTablesCustomActionFormatter(data, type, full, meta) {
    if(data.habilitar != undefined) {
        return '<a class="dropdown-item accion-habilitar" titulo="'+ full[4]+'" habilitar="1" href="' + data.habilitar + '"><i class="la la-clipboard" style="margin-right: 5px;"></i> Habilitar</a>'
    }else if(data.deshabilitar != undefined){
        return '<a class="dropdown-item accion-habilitar" titulo="'+ full[4]+'" habilitar="0" href="' + data.deshabilitar + '"><i class="la la-edit" style="margin-right: 5px;"></i> Deshabilitar</a>'
    }
    return ''
}



function show_confirm(options_in){
    var options = $.extend({
        title: 'Confirmar',
        msg: '¿Desea continuar?',
        callbackOK: function () {
        },
        callbackCancel: function () {
        }
    }, options_in);

    Swal.fire({
        title: options.title,
        text: options.msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
    }).then(function(result) {
        if (result.value) {
            options.callbackOK();
        } else {
            options.callbackCancel();
        }
    });

}