var fv;
var collectionHoldermesada;
var idTipoProducto;
var agrego = false;
const requiredField = {
    validators: {
        notEmpty: {
            message: "Este campo es requerido"
        }
    }
};
var KTCalendarListView = function() {
    return {
        //main function to initiate the module
        init: function() {
            var todayDate = moment().startOf('day');
            var YM = todayDate.format('YYYY-MM');
            var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
            var TODAY = todayDate.format('YYYY-MM-DD');
            var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');

            var calendarEl = document.getElementById('kt_calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'list' ],
                isRTL: KTUtil.isRTL(),
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
                },
                height: 800,
                contentHeight: 750,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio

                views: {
                    dayGridMonth: { buttonText: 'mes' },
                    dayGridWeek: { buttonText: 'semana' },
                    dayGridDay: { buttonText: 'dia' },
                    listWeek: { buttonText: 'lista' }
                },

                defaultView: 'dayGridWeek',
                defaultDate: TODAY,
                editable: true,
                eventLimit: true, // allow "more" link when too many events
                navLinks: true,
                eventSources: [
                    {
                        url: __HOMEPAGE_PATH__ + "salida_camara/index_table/",
                        method: 'POST',
                        extraParams: {
                            custom_param1: 'something',
                            custom_param2: 'somethingelse'
                        },
                        failure: function() {
                            alert('there was an error while fetching events!');
                        },
                    }
                ],
                eventClick: function(info) {
                    var element = $(info.el);
                    var idProducto = element.data('id');
                    idTipoProducto = info.event.extendedProps.idProducto;
                    var actionUrl = element.data('href');
                    $.ajax({
                        type: 'POST',
                        url: actionUrl,
                        data: {
                            id: idProducto
                        }
                    }).done(function (form) {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> Salida de camara pedido N° '+idProducto,
                            contenido: form,
                            color: 'btn-light-success ',
                            labelCancel: 'Cerrar',
                            labelSuccess: 'Enviar a mesada',
                            closeButton: true,
                            class: 'salida_camara_submit',
                            callbackCancel: function () {
                                return;
                            },
                            callbackSuccess: function () {
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        form: $('form[name="salida_camara"]').serialize()
                                    },
                                    url: __HOMEPAGE_PATH__ + "salida_camara/"+idProducto,
                                    success: function (data) {
                                        if (!jQuery.isEmptyObject(data)) {
                                            $('.alert-success').hide();
                                            showFlashMessage("success", data.message);
                                            calendar.refetchEvents()
                                        }
                                    },
                                    error: function () {
                                        alert('ah ocurrido un error.');
                                    }
                                });
                            }
                        });
                        $('.bs-popover-top').hide();
                        $('.modal-dialog').css('width', '80%');
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                        if (__ID_ESTADO__ != 5) {
                            initmesadaForm();
                            $('#salida_camara_mesadas_0_mesada_tipoProducto').val(idTipoProducto).select2();
                            $('#salida_camara_mesadas_0_mesada_tipoMesada').select2();
                            initMesadaChainedSelect();
                            $('#salida_camara_mesadas_0_mesada_tipoProducto').val('').select2().change();
                            initValidations();
                            $('.remove-mesada').hide();
                            initResetModalHandler();
                        }
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.attr('data-id', info.event.id);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    if (info.event.extendedProps && info.event.extendedProps.description) {
                        if (element.hasClass('fc-day-grid-event')) {
                            element.data('content', info.event.extendedProps.description);
                            element.data('placement', 'top');
                            KTApp.initPopover(element);
                        } else if (element.hasClass('fc-time-grid-event')) {
                            element.find('.fc-title').append('<div class="fc-description">' + info.event.extendedProps.description + '</div>');
                        } else if (element.find('.fc-list-item-title').lenght !== 0) {
                            element.find('.fc-list-item-title').append('<div class="fc-description">' + info.event.extendedProps.description + '</div>');
                        }
                    }
                },
                eventDrop: function(info) {
                    dialogFinalizarForm = '\
                        <div class="row">\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                        <span class="symbol-label">\n\
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                                <i class="fas fa-calendar-alt icon-2x text-dark"></i>\n\
                                            </span>\n\
                                        </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <label class="text-dark text-hover-primary mb-1 font-size-lg"><strong>Nueva fecha salida de camara</strong></label>\n\
                                        <span class="text-muted">'+info.event.extendedProps.fechaSalidaCamara+ '<i class="fas fa-arrow-right" style="padding: 0px 10px;"></i>' +info.event.start.toISOString().substring(0, 10)+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                    <span class="symbol-label">\n\
                                        <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                            <i class="fas fa-leaf icon-2x text-dark"></i>\n\
                                        </span>\n\
                                    </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <span class="label label-inline '+info.event.extendedProps.tipoProducto+' font-weight-bold" style="padding: 20px">'+info.event.extendedProps.producto+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                        <span class="symbol-label">\n\
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                                <i class="la la-file-alt icon-2x text-dark"></i>\n\
                                            </span>\n\
                                        </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <span class="label label-inline font-weight-bold '+info.event.extendedProps.colorEstado+'" style="padding: 20px 50px;">'+info.event.extendedProps.estado+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                        <span class="symbol-label">\n\
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                                <i class="fas fa-user icon-2x text-dark"></i>\n\
                                            </span>\n\
                                        </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <label class="text-dark text-hover-primary mb-1 font-size-lg">Cliente</label>\n\
                                        <span class="text-muted">'+info.event.extendedProps.cliente+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                        <span class="symbol-label">\n\
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                                <i class="fas fa-table icon-2x text-dark"></i>\n\
                                            </span>\n\
                                        </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <label class="text-dark text-hover-primary mb-1 font-size-lg">Bandejas</label>\n\
                                        <span class="text-muted">'+info.event.extendedProps.cantidadTipoBandejabandeja+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                            <div class="col-md-4">\n\
                                <div class="d-flex align-items-center mb-10">\n\
                                    <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                        <span class="symbol-label">\n\
                                            <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                                <i class="fas fa-table icon-2x text-dark"></i>\n\
                                            </span>\n\
                                        </span>\n\
                                    </div>\n\
                                    <div class="d-flex flex-column font-weight-bold">\n\
                                        <label class="text-dark text-hover-primary mb-1 font-size-lg">Codigo de sobre</label>\n\
                                        <span class="text-muted">'+info.event.extendedProps.codigoSobre+'</span>\n\
                                    </div>\n\
                                </div>\n\
                            </div>\n\
                        </div>';
                    showDialog({
                        titulo: '<span class="font-white text-center"> Modificar fecha de salida camara pedido producto N° '+info.event.id +'</span>',
                        contenido: dialogFinalizarForm,
                        className: 'modal-dialog-small',
                        color: 'green',
                        labelSuccess: 'Aceptar',
                        closeButton: false,
                        callbackCancel: function () {
                            info.revert();
                            return;
                        },
                        callbackSuccess: function () {
                            $.ajax({
                                type: 'post',
                                dataType: 'json',
                                data: {
                                    nuevaFechaSalidaCamara: info.event.start.toISOString().substring(0, 10),
                                    idPedidoProducto: info.event.id
                                },
                                url: __HOMEPAGE_PATH__ + "salidacamara/cambiar_fecha_salida_camara/",
                                success: function (data) {
                                    if (!jQuery.isEmptyObject(data)) {
                                        $('.alert-success').hide();
                                        showFlashMessage("success", data.message);
                                    }
                                },
                                error: function() {
                                    alert('ah ocurrido un error.');
                                }
                            });
                        }
                    });
                    $('.bs-popover-top').hide();
                    $('.modal-dialog').css('width', '80%');
                    $('.modal-dialog').addClass('modal-xl');
                    $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                }
            });
            calendar.render();
        }
    };
}();


/**
 *
 * @returns {undefined}
 */
function initFormValidation() {

    fv = FormValidation.formValidation($("form[name=salida_camara]")[0], {
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
function initValidations(){
    $('#salida_camara_mesadas_0_mesada_cantidadBandejas').on('keyup', function(){
        if ($(this).val() > parseInt($('#cantBandejasReales').val())){
            $(this).val($('#cantBandejasReales').val());
        }
    });
}

jQuery(document).ready(function() {
    KTCalendarListView.init();

});

/**
 *
 * @returns {undefined}
 */
function initPreValidation() {

    $(".salida_camara_submit").off('click').on('click', function (e) {
        e.preventDefault();
        $('.fv-plugins-message-container').remove();
        initFormValidation();
        fv.revalidateField('requiredFields');

        fv.validate().then((status) => {
            if (status === "Valid") {
                let res = 0;
                $('.cantBandejas').each(function () {
                    if ($(this).val() != ''){
                        res += parseInt($(this).val());
                    }
                });
                if (res == $('#cantBandejasReales').val()) {
                    if ($('#salida_camara_mesadas_1_mesada_cantidadBandejas').val() == ''){
                        $('#salida_camara_mesadas_1_mesada_cantidadBandejas').closest('.row').remove();
                    }
                    $('form[name="salida_camara"]').submit();
                } else {
                    Swal.fire({
                        title: 'La cantidad de bandejas debe coincidir con las bandejas reales.',
                        icon: "error"
                    });
                    return false;
                }
            }
        });
        e.stopPropagation();
    });
}

function initMesadaChainedSelect() {
    if ($('#salida_camara_mesadas_0_mesada_tipoProducto').val() != '') {
        initChainedSelect($('#salida_camara_mesadas_0_mesada_tipoProducto'), $('#salida_camara_mesadas_0_mesada_tipoMesada'), __HOMEPAGE_PATH__ + 'tipo/mesada/lista/mesada/producto', true);
    }
}

function getExtraDataChainedSelect(){
    return ($('#cantBandejasReales').val());
}

function customAfterChainedSelect() {
    if ($('#ultimaMesada').val() != '') {
        $('#salida_camara_mesadas_0_mesada_tipoMesada').val($('#ultimaMesada').val()).select2();
    }
}


/**
 *
 * @returns {undefined}
 */
function initmesadaForm() {
    initCollectionHoldermesada();
    addmesadaForm(collectionHoldermesada);
    $('#salida_camara_mesadas_0_mesada_cantidadBandejas').val($('#cantBandejasReales').val())
    $(document).on('click', '.prototype-link-add-mesada', function (e) {
        e.preventDefault();
        if (!agrego) {
            addmesadaForm(collectionHoldermesada);
            agrego = true;
            $('#salida_camara_mesadas_1_mesada_tipoProducto').val(idTipoProducto).select2();
            $('#salida_camara_mesadas_1_mesada_tipoMesada').select2();
            if ($('#salida_camara_mesadas_1_mesada_tipoProducto').val() != '') {
                initChainedSelect($('#salida_camara_mesadas_1_mesada_tipoProducto'), $('#salida_camara_mesadas_1_mesada_tipoMesada'), __HOMEPAGE_PATH__ + 'tipo/mesada/lista/mesada/producto', true);
            }
            $('#salida_camara_mesadas_1_mesada_cantidadBandejas').on('keyup', function(){
                if ($(this).val() > parseInt($('#cantBandejasReales').val())){
                    $(this).val($('#cantBandejasReales').val());
                }
            });
            $('.add-mesada').hide();
        }else{
            $('.row-mesada').show();
            $('#salida_camara_mesadas_1_mesada_cantidadBandejas').attr('required', true);
            $('#salida_camara_mesadas_1_mesada_tipoMesada').attr('required', true);
            $('.add-mesada').hide();
        }

        //initSelects();
    });
}

/**
 *
 * @param {type} $collectionHolder
 * @returns {addmesadaForm}
 */
function addmesadaForm($collectionHolder) {
    var prototype = $collectionHolder.data('prototype');
    var index = $collectionHolder.data('index');
    var form = prototype.replace(/__mesada__/g, index);

    $collectionHolder.data('index', index + 1);
    // Modificación para varios datos_contacto en la misma página
    $collectionHolder.parent().find('.prototype-link-add-mesada').closest('.row').before(form);

    var $deleteLink = $(".prototype-link-remove-mesada");

    updateDeleteLinks($deleteLink);
}

/**
 *
 * @returns {undefined}
 */
function initCollectionHoldermesada() {
    collectionHoldermesada = $('div.prototype-mesada');
    collectionHoldermesada.data('index', collectionHoldermesada.find(':input').length);
}

/**
 *
 * @param {type} $sourceSelect
 * @param {type} $targetSelect
 * @param {type} ajaxURL
 * @param {type} preserve_values
 * @returns {undefined}
 */
function initChainedSelect($sourceSelect, $targetSelect, ajaxURL, preserve_values) {
    var isEdit = $('[name=_method]').length > 0;
    var targetArray = [];
    var selectedElement = null;
    if (isEdit || (typeof preserve_values !== "undefined" && (preserve_values === true || preserve_values === "true"))) {
        selectedElement = $targetSelect.val();
        $sourceSelect.each(function (index) {
            targetArray[index] = $targetSelect.val();
        });
    }
    $sourceSelect.on("change", function () {
        var data = {
            id_entity: $(this).val(),
            extra_data: getExtraDataChainedSelect()
        };
        if (customChainedSelect($sourceSelect, $targetSelect, data)) {
            resetSelect($targetSelect);
            $.ajax({
                type: 'post',
                url: ajaxURL,
                data: data,
                success: function (data) {
                    $targetSelect.attr('readonly', false);
                    for (var i = 0, total = data.length; i < total; i++) {
                        if (data[i].id != parseInt($('#salida_camara_mesadas_0_mesada_tipoMesada').val())) {
                            $targetSelect.append('<option value="' + data[i].id + '">' + data[i].denominacion + '</option>');
                        }
                        if (data.length === 1){
                            selectedElement = data[i].id;
                        }
                    }
                    if (null !== selectedElement) {
                        $targetSelect.val(selectedElement);
                    } else {
                        $targetSelect.val(null);
                    }
                    selectedElement = null;
                    $targetSelect.select2();
                    customAfterChainedSelect();
                }
            });
        }
    }).trigger('change');
}

/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinks(deleteLink, closestClassName) {
    closestClassName = typeof closestClassName !== 'undefined' ? closestClassName : '.row';
    deleteLink.each(function () {
        $(this).tooltip();
        $(this).off("click").on('click', function (e) {
            e.preventDefault();
            var deletableRow = $(this).closest(closestClassName);
            deletableRow.hide();
            $('.add-mesada').show();
            $('#salida_camara_mesadas_1_mesada_cantidadBandejas').attr('required', false);
            $('#salida_camara_mesadas_1_mesada_tipoMesada').attr('required', false);
            e.stopPropagation();

        });
    });
}

function initResetModalHandler(){
    $('.cancel, .close').on('click', function (e) {
        e.preventDefault();
        agrego=false;
    })
}