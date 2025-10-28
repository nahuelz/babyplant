
var init = false;

var $table = $('#table-pedido');

$(document).ready(function () {
    initTable();
    initVerHistoricoEstadoHandler();
    initFiltrosHandler();
    initColumnsHandler();
    $('#multiple').select2();
    setSameHeight('.portlet-nivel-1');
    initCancelarButton();
    initClienteSelect2();
    initEditarMesadaHandler();
    initSubmitMesada();


    initCambiarMesadaHandler();


    var table = $table.DataTable();

});


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
    $('#multiple').on('change', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                columns: JSON.stringify(getTargets())
            },
            url: __HOMEPAGE_PATH__ + "pedido/save_columns/",
            success: function (response) {
                toastr.success(response.message);
                location.reload();
            },
            error: function () {
                alert('ah ocurrido un error.');
            }
        });
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

function initFiltrosHandler(){
    $('.actividad-reciente').hide();
    $('#filtros').hide();
    $('.mostrar-filtros').on('click', function (){
        $('#filtros').toggle();
        $("#reporte_filtro_cliente").select2();
        tableRefresh();
    });
    $('.mostrar-actividad-reciente').on('click', function (){
        $('.actividad-reciente').toggle();
        $('#reporte_filtro_cliente').select2();
        tableRefresh();
    });
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
        "sAjaxSource": __HOMEPAGE_PATH__ + 'pedido/index_table/',
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
            oSettings.jqXHR = $.ajax({
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": {
                    "fechaDesde": $('#reporte_filtro_fechaDesde').val(),
                    "fechaHasta": $('#reporte_filtro_fechaHasta').val(),
                    "idCliente": $('#reporte_filtro_cliente').val()
                },
                "success": fnCallback
            });
        },
        lengthMenu: [5, 10, 25, 50, 100, 500, 1000],
        pageLength: 25,
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
                    columns: ':not(.sorting_disabled)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'pagina',
                title: '',
                className: 'pagina',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: 'filtrados',
                title: '',
                className: 'filtrados',
                exportOptions: {
                    columns: ':not(.sorting_disabled)',
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
                    columns: ':not(.sorting_disabled)',
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
                    columns: ':not(.sorting_disabled)',
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
                    return '<a href="' + data.path + '" target="_blank">' + data.idProducto + '</a>';
                }
                return data.idProducto;
            }
        },
        {
            targets: index++,
            name: 'fechaCreacion',
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
            name: 'nombreVariedad',
            orderable: false,
            className: 'nowrap text-center margin-0 padding-0 p-0',
            width: '50px',
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    return '<span class="label label-inline margin-0 font-weight-bold p-6" style="font-size: 11px; font-weight: bold !important;width: 220px;color: black !important;background-color: ' + data.colorProducto + '">' + data.nombreProductoCompleto + '</span>';
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
                    return '<a href="' + data.path + '" target="_blank">' + data.nombreCliente + '</a>';
                }
                return data.nombreCliente;
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
            name: 'ordenSiembra p-0',
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
    let actions = '';

    if (!jQuery.isEmptyObject(data)) {

        // Generar botón de WhatsApp si el número es válido
        let botonWhatsapp = '';
        if (data.celular !== undefined) {
            let celularLimpio = data.celular.replace(/\D+/g, '');
            let celularConPrefijo = '54' + celularLimpio;

            if (esNumeroWhatsappValido('+' + celularConPrefijo)) {
                let mensaje =
                    `Hola *${full[5].nombreCliente}*,%0A` +
                    `Te informamos que tu pedido N° *${full[5].idPedido}* fue registrado correctamente.%0A` +
                    `Podés ver el detalle y estado de tu pedido en el siguiente enlace:%0A` +
                    `https://dev.babyplant.com.ar${full[14].print}%0A%0A` +
                    `¡Gracias por tu compra! 🪴🌿%0A` +
                    `- *Vivero Babyplant*`;
                botonWhatsapp =
                    '<a class="dropdown-item" href="https://api.whatsapp.com/send?phone='
                    + celularConPrefijo
                    + '&text='
                    + mensaje
                    + '" target="_blank">'
                    + '<i class="la la-whatsapp" style="margin-right: 5px; color: green;"></i> Enviar WhatsApp</a>';
            }
        }

        actions +=
            (data.show_pedido !== undefined ? '<a class="dropdown-item" href="' + data.show_pedido + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Pedido</a>' : '') +
            (data.show_producto !== undefined ? '<a class="dropdown-item" href="' + data.show_producto + '"><i class="la la-search" style="margin-right: 5px;"></i> Ver Producto</a>' : '') +
            (data.edit !== undefined ? '<a class="dropdown-item" href="' + data.edit + '"><i class="la la-edit" style="margin-right: 5px;"></i> Editar</a>' : '') +
            (data.historico_estado !== undefined ? '<a class="dropdown-item link-ver-historico-pedido" href="#" data-href="' + data.historico_estado + '"><i class="la la-file-alt" style="margin-right: 5px;" data-original-title="Hist&oacute;rico de estados"></i>Hist&oacute;rico de estados</a>' : '') +
            (data.print !== undefined ? '<a class="dropdown-item" href="' + data.print + '"><i class="la la-edit" style="margin-right: 5px;"></i> Print</a>' : '') +
            (data.situacion_cliente !== undefined ? '<a class="dropdown-item" href="' + data.situacion_cliente + '"><i class="la la-user" style="margin-right: 5px;"></i> Situacion Cliente</a>' : '') +
            (data.remito !== undefined ? '<a class="dropdown-item" href="' + data.remito + '"><i class="la la-edit" style="margin-right: 5px;"></i> Remito</a>' : '') +
            (data.cancelar !== undefined ? '<a class="dropdown-item accion-cancelar" href="' + data.cancelar + '"><i class="la la-remove" style="margin-right: 5px;"></i> Cancelar</a>' : '') +
            botonWhatsapp;

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

function esNumeroWhatsappValido(numero) {
    const regex = /^\+54\d{10}$/;
    return regex.test(numero);
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
    let cantidadBandejasMesadaUno = $('#cambiar_mesada_mesadaUno_cantidadBandejas').val() ? parseInt($('#cambiar_mesada_mesadaUno_cantidadBandejas').val()) : 0;
    let cantidadBandejasMesadaDos = $('#cambiar_mesada_mesadaDos_cantidadBandejas').val() ? parseInt($('#cambiar_mesada_mesadaDos_cantidadBandejas').val()) : 0;
    let cantidadBandejasReales = parseInt($('#cantidadBandejasReales').val());
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

