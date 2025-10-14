var auditoria_table = null
var draw = 1;

jQuery(document).ready(function () {
    initAuditoriaTable();
    initSearch();
    initMarcarCorregidaHandler();
    initMarcarSeleccionadasCorregidaHandler();
});

/**
 *
 */
function initAuditoriaTable() {
    auditoria_table = $('#table-auditoria');
    auditoria_table.on('xhr.dt', function (e, settings, json) {
        if (json && json.draw) {
            draw = json.draw;
            console.log(draw);
        } else {
            console.error('No se recibió draw en la respuesta:', json);
        }
        KTApp.unblockPage();
    });

    dataTablesInit(auditoria_table, {
        ajax: {
            url: __HOMEPAGE_PATH__ + 'auditoria_interna/index_table/',
            data: function (d) {
                return $.extend({}, d, {
                    fechaDesde: $('#fechaDesde').val(),
                    fechaHasta: $('#fechaHasta').val(),
                    draw: draw
                });
            }
        },
        serverSide: true,
        columnDefs: datatablesGetColDef(),
        order: [[1, 'desc']]
    });
}

/**
 *
 * @returns {undefined}
 */
function initSearch() {

    $('#filter-range-button').click(function (e) {

        e.preventDefault();

        if ($('#fechaDesde').datepicker('getDate') > $('#fechaHasta').datepicker('getDate')) {
            show_alert({type: 'warning', color: 'red', msg: 'La fecha desde debe ser inferior o igual a la fecha hasta'});
            return;
        }

        auditoria_table.DataTable().ajax.reload();

        e.stopPropagation();
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

/**
 *
 * @returns {undefined}
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
                    } //
                    else {
                        showFlashMessage("danger", decodedResponse.message);
                    }

                    auditoria_table.DataTable().ajax.reload();
                });
            }
        });
    });
}


/**
 *
 * @returns {undefined}
 */
function initMarcarSeleccionadasCorregidaHandler() {

    $(document).on('click', '.corregir-seleccionadas-link', function (event) {

        event.preventDefault();

        var rows = auditoria_table.DataTable().rows('tr.active').data();

        if (rows.length == 0) {

            Swal.fire(
                'Atención',
                'Debe seleccionar al menos una incidencia.',
                'alert'
            )

            return;

        } else {

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
                        data: {
                            'ids': JSON.stringify(dataArray)
                        },
                        url: __HOMEPAGE_PATH__ + 'auditoria_interna/toggle-selected/'
                    }).done(function (response) {

                        var decodedResponse = jQuery.parseJSON(response);

                        if (decodedResponse.statusText === 'OK') {
                            showFlashMessage("success", decodedResponse.message);
                        } //
                        else {
                            showFlashMessage("danger", decodedResponse.message);
                        }

                        auditoria_table.DataTable().ajax.reload();
                    });
                }
            });
        }
    });
}