var mis_tareas_table = null
var tareas_disponibles_table = null

jQuery(document).ready(function () {
    mis_tareas_table = $('#table-mis-tareas')
    dataTablesInit(mis_tareas_table, {
        ajax: __HOMEPAGE_PATH__ + 'tarea/empleado/mis_tareas_table/',
        columnDefs: datatablesGetColDefMisTareas(),
        order: [[0, 'asc']],
        responsive: true,
        autoWidth: false,
        scrollX: false,
        initComplete: function () {
            initDoubleScroll()
            if (typeof KTApp !== 'undefined') {
                KTApp.initTooltips()
            }
            $(window).trigger('resize')
        }
    })

    $('#mis-tareas-search').on('keyup', function () {
        mis_tareas_table.DataTable().search(this.value).draw()
    })

    tareas_disponibles_table = $('#table-tareas-disponibles')
    dataTablesInit(tareas_disponibles_table, {
        ajax: __HOMEPAGE_PATH__ + 'tarea/empleado/disponibles_table/',
        columnDefs: datatablesGetColDefDisponibles(),
        order: [[0, 'asc']],
        responsive: true,
        autoWidth: false,
        scrollX: false,
        initComplete: function () {
            initDoubleScroll()
            if (typeof KTApp !== 'undefined') {
                KTApp.initTooltips()
            }
            $(window).trigger('resize')
        }
    })

    $('#tareas-disponibles-search').on('keyup', function () {
        tareas_disponibles_table.DataTable().search(this.value).draw()
    })
})

function datatablesGetColDefMisTareas() {
    let index = 0
    return [
        {
            targets: index++,
            name: 'tarea',
            width: '80%',
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<strong>' + (data.titulo || '-') + '</strong><br><small class="text-muted">' + (data.descripcion || '') + '</small>'
                }
                return (data.titulo || '') + ' ' + (data.descripcion || '')
            }
        },
        {
            targets: index++,
            name: 'estado',
            width: '20%',
            className: 'dt-center',
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + (data.colorEstado || 'label-light-primary') + ' font-weight-bold p-4" style="width: 120px">' + (data.estado || '') + '</span>'
                }
                return data.estado || ''
            }
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
            className: "text-center dt-acciones",
            orderable: false,
            width: '20%',
            render: dataTablesActionFormatter
        }
    ]
}

function datatablesGetColDefDisponibles() {
    let index = 0
    return [
        {
            targets: index++,
            name: 'tarea',
            width: '80%',
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<strong>' + (data.titulo || '-') + '</strong><br><small class="text-muted">' + (data.descripcion || '') + '</small>'
                }
                return (data.titulo || '') + ' ' + (data.descripcion || '')
            }
        },
        {
            targets: index++,
            name: 'estado',
            width: '15%',
            className: 'dt-center',
            render: function (data, type, row) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + (data.colorEstado || 'label-light-primary') + ' font-weight-bold p-4" style="width: 120px">' + (data.estado || '') + '</span>'
                }
                return data.estado || ''
            }
        },
        {
            targets: -1,
            name: 'acciones',
            title: 'Acciones',
            className: "text-center dt-acciones",
            orderable: false,
            width: '20%',
            render: dataTablesActionFormatter
        }
    ]
}

function dataTablesCustomActionFormatter(data, type, full, meta) {
    let html = ''

    if (data.finalizar != undefined) {
        html += '<a class="dropdown-item accion-finalizar" data-token="' + data.token + '" href="' + data.finalizar + '"><i class="la la-check" style="margin-right: 5px;"></i> Finalizar</a>'
    }

    if (data.tomar != undefined) {
        html += '<a class="dropdown-item accion-tomar" data-token="' + data.token + '" href="' + data.tomar + '"><i class="la la-hand-o-up" style="margin-right: 5px;"></i> Tomar</a>'
    }

    return html
}

$(document).on('click', '.accion-tomar, .accion-finalizar', function (e) {
    e.preventDefault()
    var $this = $(this)
    var msg = $this.hasClass('accion-tomar') ? 'tomar' : 'finalizar'
    show_confirm({
        title: 'Confirmación',
        type: 'warning',
        msg: '¿Desea ' + msg + ' esta tarea?',
        callbackOK: function () {
            var $form = $('<form>', {action: $this.attr('href'), method: 'post', style: 'display:none;'})
            $form.append($('<input>', {type: 'hidden', name: '_token', value: $this.data('token')}))
            $('body').append($form)
            $form.submit()
        }
    })
})
