var tarea_table = null
var init = false

jQuery(document).ready(function () {
    initTable()
    actualizarIndicadores()
})

function initTable() {

    initDataTable()

    $('#kt_search').on('click', function () {
        if (init) {
            tarea_table.DataTable().ajax.reload()
            setTimeout(actualizarIndicadores, 100)
        }
    })

    $('#kt_reset').on('click', function (e) {
        e.preventDefault()
        $('.datatable-input').each(function () {
            $(this).val('').trigger('change')
        })
        if (init) {
            tarea_table.DataTable().ajax.reload()
            setTimeout(actualizarIndicadores, 100)
        }
    })
}

function initDataTable() {

    tarea_table = $('#table-tarea')

    dataTablesInit(tarea_table, {
        "sAjaxSource": __HOMEPAGE_PATH__ + 'tarea/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    "idEmpleado": $('#reporte_filtro_empleado').val()
                },
                "success": fnCallback
            })
        },
        lengthMenu: [5, 10, 25, 50, 100],
        pageLength: 25,
        destroy: true,
        responsive: true,
        autoWidth: false,
        scrollX: false,
        columnDefs: datatablesGetColDef(),
        order: [[0, 'asc']],
        serverSide: false,
        initComplete: function () {
            initDoubleScroll()
            if (typeof KTApp !== 'undefined') {
                KTApp.initTooltips()
            }
            $(window).trigger('resize')
        }
    })

    init = true
}

function actualizarIndicadores() {
    $.ajax({
        url: __HOMEPAGE_PATH__ + 'tarea/tiles/data/',
        type: 'POST',
        data: {
            'fechaDesde': $('#reporte_filtro_fechaDesde').val(),
            'fechaHasta': $('#reporte_filtro_fechaHasta').val(),
            'idEmpleado': $('#reporte_filtro_empleado').val()
        },
        dataType: 'json',
        success: function (response) {
            $('#tile-total-tareas').text(response.total)
            $('#tile-tareas-asignadas').text(response.asignadas)
        },
        error: function () {
            console.error('Error al actualizar indicadores')
        }
    })
}

function datatablesGetColDef() {
    let index = 0

    return [
        {
            targets: index++,
            name: 'tarea',
            width: '50%',
            render: function (data, type, row) {
                if (type === 'display') {
                    var titulo = '<strong>' + (data.titulo || '-') + '</strong>'
                    var editar = data.editUrl ? ' <a href="' + data.editUrl + '" class="btn btn-sm btn-clean btn-icon btn-editar-tarea" title="Editar"><i class="la la-edit"></i></a>' : ''
                    return titulo + editar + '<br><small class="text-muted">' + (data.descripcion || '') + '</small>'
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
            targets: index++,
            name: 'empleado',
            width: '15%',
            className: 'dt-center',
            render: function (data, type, row) {
                return data
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

    if (data.asignar != undefined) {
        html += '<a class="dropdown-item" href="' + data.asignar + '"><i class="la la-user" style="margin-right: 5px;"></i> Asignar</a>'
    }

    if (data.historico != undefined) {
        html += '<a class="dropdown-item btn-historico-tarea" href="' + data.historico + '" data-id="' + data.id + '" data-href="' + data.historico + '"><i class="la la-history" style="margin-right: 5px;"></i> Histórico</a>'
    }

    if (data.cancelar != undefined) {
        html += '<a class="dropdown-item accion-cancelar" data-token="' + data.token + '" href="' + data.cancelar + '"><i class="la la-trash" style="margin-right: 5px;"></i> Cancelar</a>'
    }

    return html
}

$(document).on('click', '.accion-cancelar', function (e) {
    e.preventDefault()
    var $this = $(this)
    show_confirm({
        title: 'Confirmación',
        type: 'warning',
        msg: '¿Desea cancelar esta tarea?',
        callbackOK: function () {
            var $form = $('<form>', {action: $this.attr('href'), method: 'post', style: 'display:none;'})
            $form.append($('<input>', {type: 'hidden', name: '_token', value: $this.data('token')}))
            $('body').append($form)
            $form.submit()
        }
    })
})

$(document).on('click', '.btn-asignar-tarea', function (e) {
    e.preventDefault()
    var url = $(this).attr('href')
    $('#modalAsignarTareaBody').html('Cargando...')
    $('#modalAsignarTareaBody').load(url, function () {
        $('#modalAsignarTarea').modal('show')
        $('#tarea_asignar_empleado').select2({
            placeholder: 'Seleccione un empleado',
            allowClear: true,
            width: '100%'
        })
    })
})

$(document).off('click', '.btn-editar-tarea').on('click', '.btn-editar-tarea', function (e) {
    e.preventDefault()
    var url = $(this).attr('href')
    $('#modalEditarTareaBody').html('Cargando...')
    $('#modalEditarTareaBody').load(url, function () {
        $('#modalEditarTarea').modal('show')
    })
})

$(document).off('click', '.btn-historico-tarea').on('click', '.btn-historico-tarea', function (e) {
    e.preventDefault()
    var idTarea = $(this).data('id')
    var actionUrl = $(this).data('href')

    $.ajax({
        type: 'POST',
        url: actionUrl,
        data: {
            id: idTarea
        }
    }).done(function (form) {
        showDialog({
            titulo: '<i class="fa fa-list-ul margin-right-10"></i> Histórico de estados',
            contenido: form,
            color: 'yellow',
            labelCancel: 'Cerrar',
            labelSuccess: 'Cerrar',
            closeButton: true,
            callbackCancel: function () {
                return
            },
            callbackSuccess: function () {
                return
            }
        })
        $('.bs-popover-top').hide()
        $('.btn-submit').hide()
    })
})

$(document).on('submit', '#form-asignar-tarea', function (e) {
    e.preventDefault()
    var $form = $(this)
    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        success: function () {
            $('#modalAsignarTarea').modal('hide')
            tarea_table.DataTable().ajax.reload()
        },
        error: function (xhr) {
            $('#modalAsignarTareaBody').html(xhr.responseText)
        }
    })
})

$(document).on('submit', '#form-editar-tarea', function (e) {
    e.preventDefault()
    var $form = $(this)
    $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        success: function () {
            $('#modalEditarTarea').modal('hide')
            tarea_table.DataTable().ajax.reload()
        },
        error: function (xhr) {
            $('#modalEditarTareaBody').html(xhr.responseText)
        }
    })
})
