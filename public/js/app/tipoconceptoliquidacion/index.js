var tipo_concepto_liquidacion_table = null

jQuery(document).ready(function () {
    tipo_concepto_liquidacion_table = $('#table-tipo-concepto-liquidacion')
    dataTablesInit(tipo_concepto_liquidacion_table, {
        ajax: __HOMEPAGE_PATH__ + 'tipoconcepto/liquidacion/index_table/',
        columnDefs: datatablesGetColDef(),
        order: [[1, 'asc']],
    })

    $(document).on('click', '.accion-habilitar', function (e) {
        e.preventDefault();
        var $this = $(this);
        var msg = (parseInt($this.attr('habilitar'))) ? 'habilitar' : 'deshabilitar';
        var a_href = $this.attr('href');
        var token = $this.data('token');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea '+ msg +' este concepto?',
            callbackOK: function () {
                var $form = $('<form>', {action: a_href, method: 'post', style: 'display:none;'});
                $form.append($('<input>', {type: 'hidden', name: '_token', value: token}));
                $('body').append($form);
                $form.submit();
            }
        });
        e.stopPropagation();
    });

})

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
            name: 'tipo',
        },
        {
            targets: index++,
            name: 'habilitado',
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

function dataTablesCustomActionFormatter(data, type, full, meta) {
    if(data.habilitar != undefined) {
        return '<a class="dropdown-item accion-habilitar" data-token="' + data.token + '" habilitar="1" href="' + data.habilitar + '"><i class="la la-clipboard" style="margin-right: 5px;"></i> Habilitar</a>'
    }else if(data.deshabilitar != undefined){
        return '<a class="dropdown-item accion-habilitar" data-token="' + data.token + '" habilitar="0" href="' + data.deshabilitar + '"><i class="la la-edit" style="margin-right: 5px;"></i> Deshabilitar</a>'
    }
    return ''
}
