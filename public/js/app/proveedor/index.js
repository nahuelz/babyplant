var proveedor_table = null

jQuery(document).ready(function () {
    proveedor_table = $('#table-proveedor')
    dataTablesInit(proveedor_table, {
        ajax: __HOMEPAGE_PATH__ + 'proveedor/index_table/',
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
    })

    initHabilitar();

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
            name: 'cuit',
        },
        {
            targets: index++,
            name: 'email',
        },
        {
            targets: index++,
            name: 'telefono',
        },
        {
            targets: index++,
            name: 'direccion',
        },
        {
            targets: index++,
            name: 'condicionIva',
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

function initHabilitar() {
    $(document).on('click', '.accion-habilitar', function (e) {
        e.preventDefault();
        var msg = (parseInt($(this).attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea '+ msg +' este proveedor?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}
