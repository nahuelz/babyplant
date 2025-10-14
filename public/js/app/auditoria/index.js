var auditoria_table = null;
// Remover la variable draw ya que DataTables la maneja automáticamente
// var draw = 1;  // <-- Eliminar esta línea

jQuery(document).ready(function () {
    initAuditoriaTable();
    initSearch();
    initMarcarCorregidaHandler();
    initMarcarSeleccionadasCorregidaHandler();
});

/**
 * Inicializa la tabla de auditoría con chequeos de seguridad
 */
function initAuditoriaTable() {
    auditoria_table = $('#table-auditoria');

    // Chequeo adicional: Verificar si DataTable está disponible
    if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTable no está disponible. Verifica la carga de scripts.');
        return;
    }

    // Remover el manejo manual de draw, ya que DataTables lo maneja automáticamente
    auditoria_table.on('xhr.dt', function (e, settings, json, xhr) {
        KTApp.unblockPage();
        // No necesitamos establecer draw manualmente
    });

    // Inicializar DataTable sin manipulación manual de draw
    auditoria_table.DataTable({
        ajax: {
            url: __HOMEPAGE_PATH__ + 'auditoria_interna/index_table/',
            data: function (d) {
                return $.extend({}, d, {
                    fechaDesde: $('#fechaDesde').val(),
                    fechaHasta: $('#fechaHasta').val()
                    // Remover 'draw: draw' ya que DataTables lo maneja
                });
            }
        },
        serverSide: false,
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']]
    });
}

/**
 * Función de búsqueda corregida
 */
function initSearch() {
    $('#filter-range-button').click(function (e) {
        e.preventDefault();

        if ($('#fechaDesde').datepicker('getDate') > $('#fechaHasta').datepicker('getDate')) {
            show_alert({type: 'warning', color: 'red', msg: 'La fecha desde debe ser inferior o igual a la fecha hasta'});
            return;
        }

        // Agregar chequeo antes de recargar
        if (auditoria_table && auditoria_table.DataTable()) {
            auditoria_table.DataTable().ajax.reload();
        } else {
            console.error('La tabla de auditoría no está inicializada.');
        }

        e.stopPropagation();
    });
}

/**
 * Manejadores de acciones corregidos con chequeos
 */
function initMarcarCorregidaHandler() {
    $(document).on('click', '.toggle-fix-link', function (event) {
        event.preventDefault();

        var ajaxUrl = $(this).attr('href');

        show_confirm({
            title: '¿Desea cambiar el estado de la incidencia?',
            callbackOK: function () {
                KTApp.blockPage();
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST'
                }).done(function (response) {
                    var decodedResponse = jQuery.parseJSON(response);

                    if (decodedResponse.statusText === 'OK') {
                        showFlashMessage("success", decodedResponse.message);
                    } else {
                        showFlashMessage("danger", decodedResponse.message);
                    }

                    // Chequeo antes de recargar
                    if (auditoria_table && auditoria_table.DataTable()) {
                        auditoria_table.DataTable().ajax.reload();
                    }
                });
            }
        });
    });
}

function initMarcarSeleccionadasCorregidaHandler() {
    $(document).on('click', '.corregir-seleccionadas-link', function (event) {
        event.preventDefault();

        // Chequeo antes de acceder a DataTable
        if (!auditoria_table || !auditoria_table.DataTable()) {
            console.error('La tabla de auditoría no está inicializada.');
            return;
        }

        var rows = auditoria_table.DataTable().rows('tr.active').data();

        if (rows.length == 0) {
            Swal.fire('Atención', 'Debe seleccionar al menos una incidencia.', 'warning');
            return;
        }

        var dataArray = [];
        $.each(rows, function (key, value) {
            var id = value[0];
            var timestamp = value[1];
            var numero = value[2];
            dataArray.push({'id': id, 'timestamp': timestamp, 'numero': numero});
        });

        show_confirm({
            title: '¿Desea cambiar el estado de las incidencias seleccionadas?',
            callbackOK: function () {
                KTApp.blockPage();
                $.ajax({
                    type: 'POST',
                    data: { 'ids': JSON.stringify(dataArray) },
                    url: __HOMEPAGE_PATH__ + 'auditoria_interna/toggle-selected/'
                }).done(function (response) {
                    var decodedResponse = jQuery.parseJSON(response);

                    if (decodedResponse.statusText === 'OK') {
                        showFlashMessage("success", decodedResponse.message);
                    } else {
                        showFlashMessage("danger", decodedResponse.message);
                    }

                    // Chequeo antes de recargar
                    if (auditoria_table && auditoria_table.DataTable()) {
                        auditoria_table.DataTable().ajax.reload();
                    }
                });
            }
        });
    });
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
            width: '30px',
            className: 'dt-center',
            orderable: false,
            render: function (data, type, full, meta) {
                return '\
                    <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">\
                        <input type="checkbox" value="" class="kt-checkable">\
                        <span></span>\
                    </label>';
            }
        },
        {
            targets: index++,
            name: 'timestamp',
            visible: false
        },
        {
            targets: index++,
            name: 'numero',
            className: 'dt-center',
        },
        {
            targets: index++,
            name: 'fecha',
            className: 'dt-center',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY HH:mm:ss').format('YYYYMMDDHHmmss');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'usuario'
        },
        {
            targets: index++,
            name: 'mensaje'
        },
        {
            targets: index++,
            name: 'corregido',
            className: 'dt-center'
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

        actions += `
        ${data.show !== undefined ? `<a href="${data.show}" class="btn btn-xs btn-icon btn-primary" alt="Ver detalle" title="Ver detalle"><i class="fas fa-search"></i></a>` : ''}
        ${data.toggle_fix !== undefined ? `<a href="${data.toggle_fix}" class="btn btn-xs btn-icon btn-success toggle-fix-link" alt="Marcar como corregida" title="Marcar como corregida"><i class="fas fa-check"></i></a>` : ''}
        ${data.toggle_no_fix !== undefined ? `<a href="${data.toggle_no_fix}" class="btn btn-xs btn-icon btn-danger toggle-fix-link" alt="Marcar como no corregida" title="Marcar como no corregida"><i class="fas fa-times"></i></a>` : ''}`;

    }

    return actions;

}