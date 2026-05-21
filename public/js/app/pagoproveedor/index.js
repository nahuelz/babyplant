var pago_proveedor_table = null

jQuery(document).ready(function () {
    pago_proveedor_table = $('#table-pago-proveedor')
    dataTablesInit(pago_proveedor_table, {
        ajax: __HOMEPAGE_PATH__ + 'pago_proveedor/index_table/',
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
            className: 'dt-center',
            name: 'proveedor',
        },
        {
            targets: index++,
            className: 'dt-center',
            name: 'fechaPago',
        },
        {
            targets: index++,
            name: 'monto',
            className: 'dt-center',
            render: function (data, type, full, meta) {

                const monto = parseFloat(data.monto || 0);

                const montoFormateado = monto.toLocaleString('es-AR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                const simbolo = data.tipoMoneda === 'USD'
                    ? 'US$'
                    : '$';

                return simbolo + ' ' + montoFormateado;
            }
        },
        {
            targets: index++,
            name: 'modoPago',
            className: 'dt-center',
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
    let actions = '';
    return actions;
}

function initHabilitar() {
    $(document).on('click', '.accion-habilitar', function (e) {
        e.preventDefault();
        var msg = (parseInt($(this).attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea '+ msg +' este pago_proveedor?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}
