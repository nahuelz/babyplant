var variedad_table = null

jQuery(document).ready(function () {
    variedad_table = $('#table-variedad')
    dataTablesInit(variedad_table, {
        ajax: __HOMEPAGE_PATH__ + 'tipo/variedad/index_table/',
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
    })

    $(document).on('click', '.accion-habilitar', function (e) {
        e.preventDefault();
        var msg = (parseInt($(this).attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea '+ msg +' este tipo variedad?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });

})

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
        },
        {
            targets: index++,
            name: 'nombre_sub_producto',
        },
        {
            targets: index++,
            name: 'nombre_producto',
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