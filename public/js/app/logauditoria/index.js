var usuario_table = null

jQuery(document).ready(function () {
    usuario_table = $('#table-auditoria')
    dataTablesInit(usuario_table, {
        ajax: __HOMEPAGE_PATH__ + 'logouditoria/index_table/',
        columnDefs: datatablesGetColDef()
    })

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
            name: 'idPedido',
        },
        {
            targets: index++,
            name: 'idProducto',
        },
        {
            targets: index++,
            name: 'accion',
        },
        {
            targets: index++,
            name: 'modulo',
        },
        {
            targets: index++,
            name: 'usuario',
        },
        {
            targets: index++,
            name: 'fecha'
        }
    ]
}