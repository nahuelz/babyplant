
var init = false;

var $table = $('#table-pedido');

$(document).ready(function () {
    // Limpiar bandera global al cargar la página
    window.isGuardingColumnas = false;
    
    // Mostrar mensaje de éxito si existe en sessionStorage
    const successMessage = sessionStorage.getItem('successMessage');
    if (successMessage) {
        toastr.success(successMessage);
        sessionStorage.removeItem('successMessage'); // Limpiar después de mostrar
    }
    
    initTable();
    initVerHistoricoEstadoHandler();
    initColumnsHandler();
    $('#multiple').select2({
    closeOnSelect: false
});
    setSameHeight('.portlet-nivel-1');
    initCancelarButton();
    initClienteSelect2();
    initEditarMesadaHandler();
    initFiltrosHandler();
    initBusquedaHandler();
    initColumnasHandler();
    initProblemasFilter();
    initProblemasSinSolucionFilter();
    initSubmitMesada();
    initMarcarSolucionHandler();
    initCambiarMesadaHandler();
    initMarcarProblemaHandler();
    initQuitarSolucionHandler();
    initEditarProblemaHandler();
    initQuitarProblemaHandler();
    initEditarSolucionHandler();
    initOkCheckeoHandler();
    initEliminarBandejasHandler();


    var table = $table.DataTable();

});

function initFiltrosHandler(){
    $('.mostrar-tiles').on('click', function (){
        $('#tiles').toggle();
    });
}

function initBusquedaHandler(){
    $('.mostrar-busqueda').on('click', function (){
        $('#busqueda').toggle();
    });
}

function initColumnasHandler(){
    $('.mostrar-columnas').on('click', function (){
        $('#columnas').toggle();
    });
}

function initProblemasFilter() {
    // Evento para el checkbox de filtro de problemas
    $('#filtro_problema').on('change', function() {
        if (init) {
            $table.DataTable().ajax.reload();
        }
    });
}

function initProblemasSinSolucionFilter() {
    // Evento para el checkbox de filtro de problemas sin solución
    $('#filtro_problema_sin_solucion').on('change', function() {
        if (init) {
            $table.DataTable().ajax.reload();
        }
    });
}


/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function setSameHeight(target) {

    var maxHeight = 0;

    $(target).each(function () {
        $(this).css('min-height', '0px');
    });

    $(target).each(function () {
        if ($(this)[0].offsetHeight > maxHeight) {
            maxHeight = $(this)[0].offsetHeight;
        }
    });

    $(target).each(function () {
        $(this).css('min-height', maxHeight + 'px');
    });
}

function initColumnsHandler () {
    // Remover el evento change automático
    $('#multiple').off('change');
    
    // Remover cualquier evento previo del botón para prevenir duplicados
    $('#guardar_columnas').off('click').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Prevenir múltiples ejecuciones con una bandera global
        if (window.isGuardingColumnas) {
            return false;
        }
        window.isGuardingColumnas = true;
        
        const $button = $(this);
        const originalText = $button.html();
        
        // Deshabilitar botón y mostrar indicador de carga
        $button.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Guardando...');
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                columns: JSON.stringify(getTargets())
            },
            url: __HOMEPAGE_PATH__ + "pedidoproblema/save_columns/",
            success: function (response) {
                // Guardar el mensaje en sessionStorage para mostrarlo después del refresco
                sessionStorage.setItem('successMessage', response.message);
                // Usar setTimeout para asegurar que el AJAX termine completamente
                setTimeout(function() {
                    location.reload();
                }, 100);
            },
            error: function (xhr, status, error) {
                console.error('Error al guardar columnas:', error);
                alert('Ocurrió un error al guardar las columnas.');
                // Restaurar botón en caso de error
                $button.prop('disabled', false).html(originalText);
                window.isGuardingColumnas = false;
            }
        });
        
        return false;
    });
}

function getTargets(){
    let stringArray = $('#multiple').val();
    let numberArray = [];
    length = stringArray.length;
    for (let i = 0; i < length; i++) {
        numberArray.push(parseInt(stringArray[i]));
    }
    return numberArray;
}

function tableRefresh(){
    $table.DataTable().ajax.reload();
    console.log('refresh');
}

/**
 *
 * @returns {undefined}
 */
function initTable() {

    initDataTable();

    $('#kt_search').on('click', function () {
        if (init) {
            $table.DataTable().ajax.reload();
        }
    });

    $('#kt_reset').on('click', function (e) {
        e.preventDefault();
        $('.datatable-input').each(function () {
            $(this).val('').trigger('change');
            $('#filtro_codigo_sobre').val('');
            $('#filtro_problema_sin_solucion').prop('checked', false);
            if (init) {
                $table.DataTable().column($(this).data('col-index')).search('', false, false);
            }
        });
        if (init) {
            $table.DataTable().ajax.reload();
        }
    });
}

/**
 *
 * @returns {undefined}
 */
function initDataTable() {

    $table.show();

    dataTablesInit($table, {
        "sAjaxSource": __HOMEPAGE_PATH__ + 'pedidoproblema/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    "idCliente": $('#reporte_filtro_cliente').val(),
                    "codigoSobre": $('#filtro_codigo_sobre').val(),
                    "tieneProblema": $('#filtro_problema').is(':checked') ? 1 : 0,
                    "problemaSinSolucion": $('#filtro_problema_sin_solucion').is(':checked') ? 1 : 0
                },
                "success": fnCallback
            });
        },
        "drawCallback": function(settings) {
            // Se ejecuta cada vez que la tabla se dibuja
            if (!window.filtroProblemaMarcado) {
                $('#filtro_problema').click();
                window.filtroProblemaMarcado = true;
            }

            // Resaltar filas con problemas
            resaltarFilasConProblemas();
        },
        lengthMenu: [5, 10, 25, 50, 100, 500, 1000],
        pageLength: 50,
        scrollX: false,
        //autoWidth: false,
        //scrollCollapse: true,
        fixedHeader: false,
        //processing: true,
        destroy: true,
        buttons: [
            {
                extend: 'print',
                text: 'print',
                title: '',
                className: 'print',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'pagina',
                title: '',
                className: 'pagina',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'filtrados',
                title: '',
                className: 'filtrados',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
                    filter: 'applied',
                    page: 'all'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'todos',
                title: '',
                className: 'todos',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
                    page: 'all',
                    rows: {
                        search: 'none'
                    }
                }
            },
            {
                extend: 'pdfHtml5',
                text: 'pdf',
                title: '',
                className: 'pdf',
                download: 'open',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16],
                    page: 'all',
                    filter: 'applied',
                    rows: {
                        search: 'none'
                    }
                }
            }
        ],
        columnDefs: datatablesGetColDef(),
        order: [],
        rowGroup: {
            dataSrc: 1
        },
        serverSide: false,
        colReorder: false,
    });

    init = true;
}

// Hide/show columns
$('input.toggle-vis').on( 'change', function (e) {
    e.preventDefault();
    // Get the column API object
    var column = table.column( $(this).attr('data-column') );
    // Toggle the visibility
    column.visible( ! column.visible() );
} );

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
            width: '5px',
            visible: false,
            className: 'nowrap text-center margin-0 padding-0 p-0',
            orderable: false,
            render: function (data, type, full, meta) {
                return '';
            },
        },
        {
            targets: index++,
            name: 'id',
            width: '50px',
            orderable: false,
            className: 'nowrap text-center margin-0 padding-0 p-0'
        },
        {
            targets: index++,
            name: 'idProducto',
            orderable: false,
            width: '50px',
            className: 'nowrap text-center margin-0 padding-0 p-0',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '">' + data.idProducto + '</a>';
                }
                return data.idProducto;
            }
        },
        {
            targets: index++,
            name: 'fechaCreacion',
            orderable: true,
            width: '50px',
            className: 'dt-center p-0',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY').format('YYYYMMDD');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'nombreVariedad',
            orderable: false,
            className: 'nowrap text-center margin-0 p-0',
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    // Reemplazar la línea del render con:
                    return '<span class="label label-inline margin-0 font-weight-bold p-2" style="font-size: 11px; font-weight: bold !important;width: 220px !important;color: black !important;background-color: ' + data.colorProducto + '; display: inline-block; min-height: auto; height: auto; white-space: normal; line-height: 1.3;">' + data.nombreProductoCompleto + '</span>';
                }
                return data.nombreProductoCompleto;
            }
        },
        {
            targets: index++,
            name: 'nombreCliente',
            orderable: false,
            className: 'nowrap text-center margin-0 p-0',
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<a href="' + data.path + '">' + data.nombreCliente + '</a>';
                }
                // Para búsqueda: nombre normal + nombre invertido, tdo en minúsculas y sin comas
                let nombreSinComas = data.nombreCliente.replace(/,/g, '').toLowerCase();
                let nombreInvertido = nombreSinComas.split(/\s+/).reverse().join(' ');
                return nombreSinComas + ' ' + nombreInvertido;
            }
        },
        {
            targets: index++,
            name: 'cantidadBandejas',
            orderable: false,
            className: 'dt-center p-0',
            width: '50px',
        },
        {
            targets: index++,
            name: 'origenSemilla',
            orderable: false,
            className: 'dt-center p-0',
            width: '50px',
        },
        {
            targets: index++,
            name: 'cantidadSemillas',
            orderable: false,
            className: 'dt-center p-0',
            width: '50px',
        },
        {
            targets: index++,
            name: 'fechaSiembra',
            orderable: false,
            width: '50px',
            className: 'dt-center p-0',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY').format('YYYYMMDD');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'fechaEntrega',
            orderable: false,
            width: '50px',
            className: 'dt-center p-0',
            render: function (data, type, full, meta) {
                if (type === 'sort') {
                    return moment(data, 'DD/MM/YYYY').format('YYYYMMDD');
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'estado',
            orderable: false,
            width: '50',
            className: 'nowrap text-center align-middle p-0',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline ' + data.colorEstado + ' font-weight-bold p-4" style="width: 120px">' + data.estado + '</span>';
                }
                return data.estado;
            }
        },
        {
            targets: index++,
            name: 'diasEnCamara',
            orderable: false,
            className: 'dt-center p-0',
            searchable: true,
            width: '50px',
            type: 'num'
        },
        {
            targets: index++,
            name: 'diasEnInvernaculo',
            orderable: false,
            className: 'dt-center p-0',
            searchable: true,
            width: '50px',
            type: 'num'
        },
        {
            targets: index++,
            name: 'ordenSiembra',
            orderable: false,
            width: '75px',
            className: 'dt-center font-weight-bold p-0',
            searchable: true,
        },
        {
            targets: index++,
            name: 'mesada',
            orderable: false,
            className: 'dt-center p-0',
            searchable: true,
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    let span = `<span>${data}</span>`;
                    let icon = '';

                    if (data !== '-') {
                        icon = `<i class="btn btn-sm fas fa-edit editar-mesada" 
                            data-bandejas="${full[5].cantidadBandejasDisponibles}" 
                            data-id="${full[2].idProducto}" 
                            data-mesada="${data}" 
                            title="Editar mesada"></i>`;
                    }

                    return span + icon;
                }
                return data;
            }
        },
        {
            targets: index++,
            name: 'codigoSobre',
            orderable: false,
            width: '75px',
            className: 'dt-center font-weight-bold p-0',
            searchable: true,
        },
        {
            targets: index++,
            name: 'checkeo',
            width: '80px',
            className: 'text-center p-0',
            orderable: false,
            render: function (data, type, full, meta) {
                const visto = full[2].visto;

                if (visto === "1") {
                    // Ya fue chequeado - mostrar botón deshabilitado
                    return '<button type="button" class="btn btn-sm btn-success" disabled title="Ya fue chequeado"><i class="la la-check" style="width: 2em;margin: auto;display: block"></i></button>';
                } else {
                    // No fue chequeado - mostrar botón activo
                    return '<button type="button" class="btn btn-sm btn-secondary btn-ok-checkeo" data-id="' + full[2].idProducto + '" title="Checkeo"><i class="la la-check" style="width: 2em;margin: auto;display: block"></i></button>';
                }
            }
        },
        {
            targets: -1,
            name: '',
            title: '',
            width: '50px',
            className: "text-center dt-acciones p-0",
            orderable: false,

            render: dataTablesActionFormatter
        },
        {
            // hide columns by index number
            targets: getTargets(),
            visible: false,
        },
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
    // Los datos de acciones están en full[18], no en el parámetro data
    const actionData = full[19];

    let actions = '';

    if (actionData && !jQuery.isEmptyObject(actionData)) {
        actions +=
            (actionData.show_pedido !== undefined ? '<a class="dropdown-item" href="' + actionData.show_pedido + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Pedido</a>' : '') +
            (actionData.pedido_producto_quitar_revision !== undefined ? '<a class="dropdown-item quitar-revision-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion=""><i class="la la-times" style="margin-right: 5px;"></i> Quitar Revisión</a>' : '') +
            (actionData.pedido_producto_marcar_revision !== undefined ? '<a class="dropdown-item marcar-revision-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion=""><i class="la la-clipboard-check" style="margin-right: 5px;"></i> Marcar Revisión</a>' : '') +
            (actionData.pedido_producto_marcar_problema !== undefined ? '<a class="dropdown-item marcar-problema-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="false" data-observacion=""><i class="la la-exclamation-triangle" style="margin-right: 5px;"></i> Marcar Problema</a>' : '') +
            (actionData.pedido_producto_quitar_problema !== undefined ? '<a class="dropdown-item quitar-problema-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion=""><i class="la la-times" style="margin-right: 5px;"></i> Quitar Problema</a>' : '') +
            (actionData.pedido_producto_editar_problema !== undefined ? '<a class="dropdown-item editar-problema-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion="' + (full[2].observacionProblema || '') + '" data-revision="' + (full[2].tipoRevision || '') + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar Problema</a>' : '') +
            (actionData.pedido_producto_marcar_solucion !== undefined ? '<a class="dropdown-item marcar-solucion-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion=""><i class="la la-check" style="margin-right: 5px;"></i> Marcar Solución</a>' : '') +
            (actionData.pedido_producto_quitar_solucion !== undefined ? '<a class="dropdown-item quitar-solucion-btn" href="#" data-id="' + full[2].idProducto + '" data-tiene-problema="true" data-observacion=""><i class="la la-times" style="margin-right: 5px;"></i> Quitar Solución</a>' : '') +
            (actionData.pedido_producto_editar_solucion !== undefined ? '<a class="dropdown-item editar-solucion-btn" href="#" data-id="' + full[2].idProducto + '" data-solucion="' + (full[2].solucion || '') + '" data-observacion="' + (full[2].observacionSolucion || '') + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar Solución</a>' : '') +
            (actionData.pedido_producto_elimina_bandeja !== undefined ? '<a class="dropdown-item elimina-bandeja-btn" href="#" data-id="' + full[2].idProducto + '" data-bandejas-reales="' + full[6] + '" data-bandejas-disponibles="' + full[5].cantidadBandejasDisponibles + '"><i class="la la-trash" style="margin-right: 5px;"></i> Eliminar Bandejas</a>' : '');

        actions = ' <div class="dropdown dropdown-inline">\
                        <button type="button" class="btn btn-light-primary btn-icon btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
                            <i class="ki ki-bold-more-hor"></i>\
                        </button>\
                        <div class="dropdown-menu">' + actions + '</div>\
                    </div>';
    }

    return actions;
}

/**
 *
 * @returns {undefined}
 */
function initVerHistoricoEstadoHandler() {

    $(document).off('click', '.link-ver-historico-pedido').on('click', '.link-ver-historico-pedido', function (e) {

        e.preventDefault();

        var idAmenaza = $(this).data('id');

        var actionUrl = $(this).data('href');

        $.ajax({
            type: 'POST',
            url: actionUrl,
            data: {
                id: idAmenaza
            }
        }).done(function (form) {

            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Hist&oacute;rico de estados',
                contenido: form,
                color: 'yellow',
                labelCancel: 'Cerrar',
                labelSuccess: 'Cerrar',
                closeButton: true,
                callbackCancel: function () {
                    return;
                },
                callbackSuccess: function () {
                    return;
                }
            });
            $('.bs-popover-top').hide();
            $('.btn-submit').hide();
        });
    });
}

function initCancelarButton() {
    $(document).on('click', '.accion-cancelar', function (e) {
        e.preventDefault();
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Desea cancelar este producto?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}

function initEditarMesadaHandler(){
    $(document).on('click', '.editar-mesada', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const modalUrl = `pedido/${id}/modal-mesada`;
        const bandejas = $(this).data('bandejas');

        $.ajax({
            url: __HOMEPAGE_PATH__ + modalUrl,
            type: 'GET',
            success: function (html) {
                // Si ya hay un modalMesada, lo eliminamos
                $('#modalMesada').remove();

                // Agregamos el nuevo modal al body
                $('body').append(html);

                // Mostramos el modal
                $('#modalMesada').modal('show');
                // Insertar el número de bandejas disponibles
                $('#bandejasDisponibles').text(`BANDEJAS DISPONIBLES: ${bandejas}`);
                initCambiarMesadaHandler();
                $('#cantidadBandejasReales').val(bandejas);
                $('.bs-popover-top').hide();
                $('.modal-dialog').css('width', '80%');
                $('.modal-dialog').addClass('modal-xl');
                $('.modal-dialog').addClass('modal-fullscreen-xl-down');
            },
            error: function () {
                alert('Error al cargar el formulario de mesadas');
            }
        });
    });
}

function initSubmitMesada(){
    $(document).on('submit', '#formMesada', function (e) {
        e.preventDefault();

        const $form = $(this);

        if (cantidadDeBandejasValida()) {
            if ($('#cambiar_mesada_mesadaUno_tipoMesada').val() != '') {
                if ($('#cambiar_mesada_mesadaDos_cantidadBandejas').val() < 1) {
                    $("#cambiar_mesada_mesadaDos_cantidadBandejas").attr('disabled', 'disabled');
                    $("#cambiar_mesada_mesadaDos_tipoMesada").attr('disabled', 'disabled');
                }
                //$('form[name="cambiar_mesada"]').submit();
                $.ajax({
                    url: $form.attr('action'),
                    type: $form.attr('method'),
                    data: $form.serialize(),
                    success: function (data) {
                        $('#modalMesada').modal('hide');
                        $('#table-pedido').DataTable().ajax.reload();
                    },
                    error: function () {
                        alert('Error al guardar las mesadas');
                    }
                });
            } else {
                Swal.fire({
                    title: 'Debe compeltar todos los datos.',
                    icon: "error"
                });
                return false;
            }
        } else {
            Swal.fire({
                title: 'La cantidad de bandejas debe coincidir con las bandejas disponibles.',
                icon: "error"
            });
            return false;
        }
    });

}

/**
 *
 * @returns {undefined}
 */
function initFormValidationCambiarMesada() {

    fv = FormValidation.formValidation($("form[name=cambiar_mesada]")[0], {
        fields: {
            requiredFields: {
                selector: '[required="required"]',
                validators: {
                    notEmpty: {
                        message: 'Este campo es requerido'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap: new FormValidation.plugins.Bootstrap(),
            submitButton: new FormValidation.plugins.SubmitButton(),
            //defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
        }
    });

}

function cantidadDeBandejasValida(){
    let cantidadBandejasMesadaUno = $('#cambiar_mesada_mesadaUno_cantidadBandejas').val() ? parseFloat($('#cambiar_mesada_mesadaUno_cantidadBandejas').val()) : 0;
    let cantidadBandejasMesadaDos = $('#cambiar_mesada_mesadaDos_cantidadBandejas').val() ? parseFloat($('#cambiar_mesada_mesadaDos_cantidadBandejas').val()) : 0;
    let cantidadBandejasReales = parseFloat($('#cantidadBandejasReales').val());
    let cantidadTotalBandejas = cantidadBandejasMesadaUno + cantidadBandejasMesadaDos;

    return (cantidadTotalBandejas === cantidadBandejasReales);
}

function initCambiarMesadaHandler(){
    $('#cambiar_mesada_mesadaUno_tipoMesada').select2();
    $('#cambiar_mesada_mesadaDos_tipoMesada').select2();
    $('.row-mesada-empty').hide();
    if ($('#cambiar_mesada_mesadaDos_cantidadBandejas').val() == '' || $('#cambiar_mesada_mesadaDos_cantidadBandejas').val() == '0') {
        $('.mesada-dos').hide();
    }else{
        $('.add-mesada').hide();
    }
    removeMesadaHandler();
    $(document).on('click', '.add-mesada', function (e) {
        e.preventDefault();
        disableMesadaDosOptionHandler();
        removeMesadaHandler();
        $('#cambiar_mesada_mesadaDos_cantidadBandejas').on('keyup', function(){
            if ($(this).val() > parseInt($('#cantidadBandejasReales').val())){
                $(this).val($('#cantidadBandejasReales').val());
            }
        });
        $('.add-mesada').hide();
        $('.mesada-dos').show();
        $("#cambiar_mesada_mesadaDos_tipoMesada>option[value="+$('#cambiar_mesada_mesadaUno_tipoMesada').val()+"]").attr('disabled','disabled');
    });
}

function disableMesadaDosOptionHandler(){
    $('#cambiar_mesada_mesadaUno_tipoMesada').on('change', function(){
        $('#cambiar_mesada_mesadaDos_tipoMesada').val('').select2();
        $("#cambiar_mesada_mesadaDos_tipoMesada>option").removeAttr('disabled');
        $("#cambiar_mesada_mesadaDos_tipoMesada>option[value="+$('#cambiar_mesada_mesadaUno_tipoMesada').val()+"]").attr('disabled','disabled');
    })
}

function removeMesadaHandler(){
    $('.remove-mesada').on('click', function(){
        $('.mesada-dos').hide();
        $('#cambiar_mesada_mesadaDos_tipoMesada').val('').select2();
        $('#cambiar_mesada_mesadaDos_cantidadBandejas').val('');
        $('.add-mesada').show();
    })
}

// Funciones para manejar problemas en pedidos
function initMarcarProblemaHandler() {
    $.ajax({
        url: __HOMEPAGE_PATH__ + 'tipo/revision/api/todos-habilitados',
        method: 'GET',
        success: function(response) {
            window.tiposRevision = response.data || [];
        },
        error: function() {
            console.error('Error al cargar tipos de revisión');
            window.tiposRevision = [];
        }
    });
    $(document).off('click', '.marcar-problema-btn').on('click', '.marcar-problema-btn', function (e) {
        e.preventDefault();
        
        const id = $(this).data('id');
        const tieneProblema = $(this).data('tiene-problema');
        const observacionActual = $(this).data('observacion') || '';
        const revisionActual = $(this).data('revision') || '';

        let opcionesRevision = '<option value="">Seleccionar tipo de problema...</option>';
        if (window.tiposRevision && window.tiposRevision.length > 0) {
            window.tiposRevision.forEach(function(tipo) {
                const selected = tipo.id == revisionActual ? 'selected' : '';
                opcionesRevision += `<option value="${tipo.id}" ${selected}>${tipo.nombre}</option>`;
            });
        }
        
        // Crear modal para marcar problema
        const modalHtml = `
            <div class="modal fade" id="modalProblema" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="la la-exclamation-triangle"></i>
                                Marcar Problema
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="formProblema">
                                <div class="form-group">
                                    <label for="tipoRevision">Tipo de Problema:</label>
                                    <select class="form-control" id="tipoRevision" name="tipoRevision" required>
                                        ${opcionesRevision}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="observacionProblema">Observación del problema:</label>
                                    <textarea class="form-control" id="observacionProblema" 
                                              name="observacionProblema" rows="4" 
                                              placeholder="Describa el problema..." required></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="guardarProblema">
                                <i class="la la-save"></i> Guardar Problema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Eliminar modal si ya existe
        $('#modalProblema').remove();
        
        // Agregar nuevo modal
        $('body').append(modalHtml);
        
        // Mostrar modal
        $('#modalProblema').modal('show');
        
        // Manejar envío del formulario
        $('#guardarProblema').off('click').on('click', function() {
            const observacion = $('#observacionProblema').val().trim();
            const tipoRevision = $('#tipoRevision').val();

            if (!tipoRevision) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar un tipo de problema',
                    icon: 'error'
                });
                return;
            }
            
            // Enviar datos via AJAX
            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/marcar-problema`,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: JSON.stringify({
                    tieneProblema: true,
                    revision: tipoRevision,
                    observacionProblema: observacion
                }),
                success: function(response) {
                    if (response.success) {
                        $('#modalProblema').modal('hide');
                        toastr.success('Problema marcado correctamente');
                        // Recargar la tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo marcar el problema',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al marcar el problema',
                        icon: 'error'
                    });
                }
            });
        });
    });
}
function initMarcarSolucionHandler() {
    $.ajax({
        url: __HOMEPAGE_PATH__ + 'tipo/solucion/api/todos-habilitados',
        method: 'GET',
        success: function(response) {
            window.tiposSolucion = response.data || [];
        },
        error: function() {
            console.error('Error al cargar tipos de solución');
            window.tiposSolucion = [];
        }
    });

    $(document).off('click', '.marcar-solucion-btn').on('click', '.marcar-solucion-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const solucionActual = $(this).data('solucion') || '';

        let opcionesSolucion = '<option value="">Seleccionar tipo de solución...</option>';
        if (window.tiposSolucion && window.tiposSolucion.length > 0) {
            window.tiposSolucion.forEach(function(tipo) {
                const selected = tipo.id == solucionActual ? 'selected' : '';
                opcionesSolucion += `<option value="${tipo.id}" ${selected}>${tipo.nombre}</option>`;
            });
        }

        // Crear modal para marcar solución
        const modalHtml = `
            <div class="modal fade" id="modalSolucion" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="la la-clipboard-check"></i>
                                Marcar Solución
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&​times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="formSolucion">
                                <div class="form-group">
                                    <label for="tipoSolucion">Tipo de Solución:</label>
                                    <select class="form-control" id="tipoSolucion" name="tipoSolucion" required>
                                        ${opcionesSolucion}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="observacionSolucion">Observación de la solución:</label>
                                    <textarea class="form-control" id="observacionSolucion" 
                                              name="observacionSolucion" rows="4" 
                                              placeholder="Describa la solución..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="guardarSolucion">
                                <i class="la la-save"></i> Guardar Solución
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal si ya existe
        $('#modalSolucion').remove();

        // Agregar nuevo modal
        $('body').append(modalHtml);

        // Mostrar modal
        $('#modalSolucion').modal('show');

        // Manejar envío del formulario
        $('#guardarSolucion').off('click').on('click', function() {
            const tipoSolucion = $('#tipoSolucion').val();
            const observacion = $('#observacionSolucion').val().trim();

            if (!tipoSolucion) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar un tipo de solución',
                    icon: 'error'
                });
                return;
            }

            // Enviar datos via AJAX
            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/marcar-solucion`,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: JSON.stringify({
                    solucion: tipoSolucion,
                    observacionSolucion: observacion
                }),
                success: function(response) {
                    if (response.success) {
                        $('#modalSolucion').modal('hide');
                        toastr.success('Solución marcada correctamente');
                        // Recargar la tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo marcar la solución',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al marcar la solución',
                        icon: 'error'
                    });
                }
            });
        });
    });
}
function initEditarSolucionHandler() {
    $(document).off('click', '.editar-solucion-btn').on('click', '.editar-solucion-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const solucionActual = $(this).data('solucion') || '';
        const observacionActual = $(this).data('observacion') || '';

        let opcionesSolucion = '<option value="">Seleccionar tipo de solución...</option>';
        if (window.tiposSolucion && window.tiposSolucion.length > 0) {
            window.tiposSolucion.forEach(function(tipo) {
                const selected = tipo.id == solucionActual ? 'selected' : '';
                opcionesSolucion += `<option value="${tipo.id}" ${selected}>${tipo.nombre}</option>`;
            });
        }

        // Crear modal para editar solución
        const modalHtml = `
            <div class="modal fade" id="modalEditarSolucion" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="la la-edit"></i>
                                Editar Solución
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&​times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="formEditarSolucion">
                                <div class="form-group">
                                    <label for="tipoSolucionEditar">Tipo de Solución:</label>
                                    <select class="form-control" id="tipoSolucionEditar" name="tipoSolucionEditar" required>
                                        ${opcionesSolucion}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="observacionSolucionEditar">Observación de la solución:</label>
                                    <textarea class="form-control" id="observacionSolucionEditar" 
                                              name="observacionSolucionEditar" rows="4" 
                                              placeholder="Describa la solución..." required>${observacionActual}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="guardarEditarSolucion">
                                <i class="la la-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal si ya existe
        $('#modalEditarSolucion').remove();

        // Agregar nuevo modal
        $('body').append(modalHtml);

        // Mostrar modal
        $('#modalEditarSolucion').modal('show');

        // Manejar envío del formulario
        $('#guardarEditarSolucion').off('click').on('click', function() {
            const observacion = $('#observacionSolucionEditar').val().trim();
            const tipoSolucion = $('#tipoSolucionEditar').val();

            if (!tipoSolucion) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar un tipo de solución',
                    icon: 'error'
                });
                return;
            }

            // Enviar datos via AJAX
            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/editar-solucion`,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: JSON.stringify({
                    solucion: tipoSolucion,
                    observacionSolucion: observacion
                }),
                success: function(response) {
                    if (response.success) {
                        $('#modalEditarSolucion').modal('hide');
                        toastr.success('Solución actualizada correctamente');
                        // Recargar la tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo actualizar la solución',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al actualizar la solución',
                        icon: 'error'
                    });
                }
            });
        });
    });
}

function initQuitarSolucionHandler() {
    $(document).off('click', '.quitar-solucion-btn').on('click', '.quitar-solucion-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará la solución del pedido',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/quitar-solucion`,
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success('Solución eliminada correctamente');

                        // recargar tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'No se pudo quitar la solución',
                            icon: 'error'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al quitar la solución',
                        icon: 'error'
                    });
                }
            });
        });
    });

}

function initQuitarProblemaHandler() {
    $(document).off('click', '.quitar-problema-btn').on('click', '.quitar-problema-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará el problema del pedido',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/quitar-problema`,
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success('Problema eliminado correctamente');

                        // recargar tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'No se pudo quitar el problema',
                            icon: 'error'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al quitar el problema',
                        icon: 'error'
                    });
                }
            });
        });
    });

}

function initEditarProblemaHandler() {
    $(document).off('click', '.editar-problema-btn').on('click', '.editar-problema-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const observacionActual = $(this).data('observacion') || '';
        const revisionActual = $(this).data('revision') || '';

        let opcionesRevision = '<option value="">Seleccionar tipo de problema...</option>';
        if (window.tiposRevision && window.tiposRevision.length > 0) {
            window.tiposRevision.forEach(function(tipo) {
                const selected = tipo.id == revisionActual ? 'selected' : '';
                opcionesRevision += `<option value="${tipo.id}" ${selected}>${tipo.nombre}</option>`;
            });
        }

        // Crear modal para editar problema
        const modalHtml = `
            <div class="modal fade" id="modalEditarProblema" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="la la-edit"></i>
                                Editar Problema
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&​times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="formEditarProblema">
                                <div class="form-group">
                                    <label for="tipoRevisionEditar">Tipo de Problema:</label>
                                    <select class="form-control" id="tipoRevisionEditar" name="tipoRevisionEditar" required>
                                        ${opcionesRevision}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="observacionProblemaEditar">Observación del problema:</label>
                                    <textarea class="form-control" id="observacionProblemaEditar" 
                                              name="observacionProblemaEditar" rows="4" 
                                              placeholder="Describa el problema..." required>${observacionActual}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="guardarEditarProblema">
                                <i class="la la-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal si ya existe
        $('#modalEditarProblema').remove();

        // Agregar nuevo modal
        $('body').append(modalHtml);

        // Mostrar modal
        $('#modalEditarProblema').modal('show');

        // Manejar envío del formulario
        $('#guardarEditarProblema').off('click').on('click', function() {
            const observacion = $('#observacionProblemaEditar').val().trim();
            const tipoRevision = $('#tipoRevisionEditar').val();

            if (!tipoRevision) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar un tipo de problema',
                    icon: 'error'
                });
                return;
            }

            // Enviar datos via AJAX
            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/editar-problema`,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: JSON.stringify({
                    tieneProblema: true,
                    revision: tipoRevision,
                    observacionProblema: observacion
                }),
                success: function(response) {
                    if (response.success) {
                        $('#modalEditarProblema').modal('hide');
                        toastr.success('Problema actualizado correctamente');
                        // Recargar la tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo actualizar el problema',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al actualizar el problema',
                        icon: 'error'
                    });
                }
            });
        });
    });
}


// Función para resaltar filas con revision
function resaltarFilasConProblemas() {
    // Obtener todas las filas de la tabla
    $('#table-pedido tbody tr').each(function() {
        const $row = $(this);
        
        // Obtener los datos de la fila usando DataTables API
        const table = $('#table-pedido').DataTable();
        const data = table.row($row).data();

        // El valor de tieneRevision está en el índice 17 del array
        if (data && data[17] === "1") {
            // Agregar clase CSS para resaltar en amarillo
            $row.addClass('fila-con-problema');
        } else {
            // Remover clase si no tiene problema
            $row.removeClass('fila-con-problema');
        }

        if (data && data[18] === "1") {
            // Agregar clase CSS para resaltar en amarillo
            $row.addClass('fila-con-solucion');
            $row.removeClass('fila-con-problema');
        } else {
            // Remover clase si no tiene problema
            $row.removeClass('fila-con-solucion');
        }
    });
}

function initOkCheckeoHandler() {
    $(document).off('click', '.btn-ok-checkeo').on('click', '.btn-ok-checkeo', function (e) {
        e.preventDefault();
        const $button = $(this);
        const id = $button.data('id');

        // Deshabilitar botón y mostrar loading
        $button.prop('disabled', true).html('<i class="la la-spinner la-spin" style="width: 2em;margin: auto;display: block"></i>');

        $.ajax({
            url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/checkeo`,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Checkeo realizado correctamente');

                    // Cambiar el botón a estado "chequeado"
                    $button.removeClass('btn-secondary').addClass('btn-success')
                        .prop('disabled', true)
                        .html('<i class="la la-check" style="width: 2em;margin: auto;display: block"></i>')
                        .attr('title', 'Ya fue chequeado');
                } else {
                    toastr.error('No se pudo realizar el checkeo');
                    $button.prop('disabled', false).html('<i class="la la-check" style="width: 2em;margin: auto;display: block"></i>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                toastr.error('Ocurrió un error al realizar el checkeo');
                $button.prop('disabled', false).html('<i class="la la-check" style="width: 2em;margin: auto;display: block"></i>');
            }
        });
    });
}

function initEliminarBandejasHandler() {
    // Cargar tipos de motivos de eliminación
    $.ajax({
        url: __HOMEPAGE_PATH__ + 'tipo/motivo/eliminacion/api/todos-habilitados',
        method: 'GET',
        success: function(response) {
            window.tiposMotivoEliminacion = response.data || [];
        },
        error: function() {
            console.error('Error al cargar tipos de motivo de eliminación');
            window.tiposMotivoEliminacion = [];
        }
    });

    $(document).off('click', '.elimina-bandeja-btn').on('click', '.elimina-bandeja-btn', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const bandejasReales = $(this).data('bandejas-reales');
        const bandejasDisponibles = $(this).data('bandejas-disponibles');

        let opcionesMotivo = '<option value="">Seleccionar motivo de eliminación...</option>';
        if (window.tiposMotivoEliminacion && window.tiposMotivoEliminacion.length > 0) {
            window.tiposMotivoEliminacion.forEach(function(tipo) {
                opcionesMotivo += `<option value="${tipo.id}">${tipo.nombre}</option>`;
            });
        }

        // Crear modal para eliminar bandejas
        const modalHtml = `
            <div class="modal fade" id="modalEliminarBandejas" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="la la-trash"></i>
                                Eliminar Bandejas
                            </h5>
                            <div>
                                <a href="${__HOMEPAGE_PATH__}tipo/motivo/eliminacion/new" target="_blank" class="btn btn-sm btn-primary mr-2">
                                    <i class="la la-plus"></i> Agregar Motivo
                                </a>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&​times;</span>
                                </button>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <strong>Información:</strong><br>
                                Bandejas Reales: ${bandejasReales}<br>
                                Bandejas Disponibles: ${bandejasDisponibles}
                            </div>
                            <form id="formEliminarBandejas">
                                <div class="form-group">
                                    <label for="motivoEliminacion">Motivo de eliminación:</label>
                                    <select class="form-control" id="motivoEliminacion" name="motivoEliminacion" required>
                                        ${opcionesMotivo}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cantidadAEliminar">Cantidad de bandejas a eliminar:</label>
                                    <input type="number" class="form-control" id="cantidadAEliminar"
                                           name="cantidadAEliminar" min="1" max="${bandejasDisponibles}" required>
                                    <small class="form-text text-muted">Máximo: ${bandejasDisponibles}</small>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="eliminarBandejas">
                                <i class="la la-trash"></i> Eliminar Bandejas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal si ya existe
        $('#modalEliminarBandejas').remove();

        // Agregar nuevo modal
        $('body').append(modalHtml);

        // Mostrar modal
        $('#modalEliminarBandejas').modal('show');

        // Manejar envío del formulario
        $('#eliminarBandejas').off('click').on('click', function() {
            const cantidadAEliminar = parseFloat($('#cantidadAEliminar').val());
            const motivoEliminacion = $('#motivoEliminacion').val();

            if (!motivoEliminacion) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe seleccionar un motivo de eliminación',
                    icon: 'error'
                });
                return;
            }

            if (!cantidadAEliminar || cantidadAEliminar <= 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debe ingresar una cantidad válida',
                    icon: 'error'
                });
                return;
            }

            if (cantidadAEliminar > bandejasDisponibles) {
                Swal.fire({
                    title: 'Error',
                    text: 'No puede eliminar más bandejas de las disponibles',
                    icon: 'error'
                });
                return;
            }

            // Enviar datos via AJAX
            $.ajax({
                url: __HOMEPAGE_PATH__ + `pedidoproblema/${id}/eliminar-bandejas`,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: JSON.stringify({
                    cantidadAEliminar: cantidadAEliminar,
                    motivoEliminacion: motivoEliminacion
                }),
                success: function(response) {
                    if (response.success) {
                        $('#modalEliminarBandejas').modal('hide');
                        toastr.success(response.message || 'Bandejas eliminadas correctamente');
                        // Recargar la tabla
                        $('#table-pedido').DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'No se pudieron eliminar las bandejas',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al eliminar las bandejas',
                        icon: 'error'
                    });
                }
            });
        });
    });
}