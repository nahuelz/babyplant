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
                        $('.row-mesada-empty').hide();
                        initMesadaHandler();
                        $('#salida_camara_mesada_tipoMesada').val(info.event.extendedProps.idProducto).select2();
                        $('#salida_camara_mesada_mesada').select2();
                        initMesadaChainedSelect();
                        initValidations();
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

function initValidations(){
    $('#salida_camara_mesada_cantidadBandejas').on('keyup', function(){
        if ($(this).val() > $('#cantBandejasReales').val()){
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

        let res = 0;
        $('.cantBandejas').each(function() {
            res += parseInt($(this).val());
        });

        if (res == $('#cantBandejasReales').val()){
            $('form[name="salida_camara"]').submit();
        }else {
            Swal.fire({
                title: 'La cantidad de bandejas debe coincidir con las bandejas reales.',
                icon: "error"
            });
            return false;
        }
        e.stopPropagation();
    });
}

/**
 *
 * @returns {undefined}
 */
function initMesadaHandler() {

    $('.tbody-mesada').data('index', $('.tr-mesada').length);

    updateDeleteLinkMesada($(".link-delete-mesada"), '.tr-mesada');

    // Save CaracteristicaProducto handler
    $(document).off('click', '.link-save-mesada').on('click', '.link-save-mesada', function (e) {

        e.preventDefault();


        var mesadaSelect = $('#salida_camara_mesada_mesada');
        var mesada = mesadaSelect.val();
        var cantBandejas = $('#salida_camara_mesada_cantidadBandejas').val();


        if (mesada === '' || cantBandejas === '' ) {
            Swal.fire({
                title: "Debe completar todos los datos de la mesada.",
                icon: "warning"
            });

        } else {

            var index = $('.tbody-mesada').data('index');

            var removeLink = '\
                        <a href="#" class="btn btn-sm delete-link-inline link-delete-mesada mesada-borrar tooltips" \n\
                            data-placement="top" data-original-title="Eliminar">\n\
                            <i class="fa fa-trash text-danger"></i>\n\
                        </a>';

            var item = '\
                        <tr class="tr-mesada">\n\
                            <td class="hidden"><input type="hidden" name="salida_camara[mesadas][' + index + '][mesada]" value="' + mesada + '"></td>\n\
                            <td class="hidden"><input type="hidden" class="cantBandejas" name="salida_camara[mesadas][' + index + '][cantidadBandejas]" value="' + cantBandejas + '"></td>\n\
                            <td class="text-center v-middle">' + mesadaSelect.find('option:selected').text()  + '</td>\n\
                            <td class="text-center v-middle">' + cantBandejas + '</td>\n\
                            <td class="text-center v-middle">' + removeLink + '</td>\n\
                        </tr>';

            $('.tbody-mesada').append(item);
            $('.tbody-mesada').data('index', index + 1);

            $('.tbody-mesada tr.tr-mesada:last').hide();
            $('.tbody-mesada tr.tr-mesada').fadeIn("slow");

            updateDeleteLinkMesada($(".link-delete-mesada"), '.tr-mesada');

            $('.row-mesada-empty').hide('slow');
            $('.row-mesada').show('slow');

            //  Reset form
            $('.row-agregar-mesada').show('slow');
            clearMesadaForm();
        }

        e.stopPropagation();
    });
}

/**
 *
 * @returns {undefined}
 */
function clearMesadaForm() {
    $('#salida_camara_mesada_mesada').val('').select2();
    $('#salida_camara_mesada_cantidadBandejas').val('');
}

/**
 *
 * @param {type} deleteLink
 * @param {type} closestClassName
 * @returns {undefined}
 */
function updateDeleteLinkMesada(deleteLink, closestClassName) {
    closestClassName = typeof closestClassName !== 'undefined' ? closestClassName : '.row';
    deleteLink.each(function () {
        $(this).tooltip();
        $(this).off("click").on('click', function (e) {
            e.preventDefault();
            var deletableRow = $(this).closest(closestClassName);
            if (!checkFormIsEmpty(deletableRow)) {
                show_confirm({
                    title: 'Confirmar',
                    type: 'warning',
                    msg: '¿Confirma la eliminación?',
                    callbackOK: function () {
                        deletableRow.hide('slow', function () {
                            customPreDeleteLinkOnCallbackOk(deletableRow);
                            deletableRow.remove();
                            if ($('.tr-mesada').length === 0) {
                                $('.row-mesada-empty').show('slow');
                                $('.row-mesada').hide('slow');
                            }
                            customDeleteLinkOnCallbackOk();
                        });
                    }
                });
            } else {
                deletableRow.hide('slow', function () {
                    customPreDeleteLinkOnCallbackOk(deletableRow);
                    deletableRow.remove();
                    if ($('.tr-mesada').length === 0) {
                        $('.row-mesada-empty').show('slow');
                        $('.row-mesada').hide('slow');
                    }
                    customDeleteLinkOnCallbackOk();
                });
            }
            $('.bs-tooltip-top').hide();
            e.stopPropagation();

        });
    });
}

function initMesadaChainedSelect() {
    initChainedSelect($('#salida_camara_mesada_tipoMesada'), $('#salida_camara_mesada_mesada'), __HOMEPAGE_PATH__ + 'tipo/mesada/lista/mesada/producto', true);
}

function getExtraDataChainedSelect(){
    return ($('#salida_camara_mesada_tipoMesada').select2('data')[0]['text']);
}
