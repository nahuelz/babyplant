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
                plugins: ['dayGrid'],
                isRTL: KTUtil.isRTL(),
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridDay'
                },
                hiddenDays:  [ 0 ],
                height: 800,
                contentHeight: 750,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio

                views: {
                    dayGridDay: { buttonText: 'dia' }
                },

                defaultView: 'dayGridDay',
                defaultDate: TODAY,

                editable: true,
                eventLimit: true, // allow "more" link when too many events
                navLinks: true,
                eventSources: [
                    {
                        url: __HOMEPAGE_PATH__ + "siembra/index_table/",
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
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> Orden de siembra pedido N° '+idProducto,
                            contenido: form,
                            className: 'modal-dialog-small',
                            color: 'yellow ',
                            labelCancel: 'Cerrar',
                            labelSave: 'Guardar',
                            labelSuccess: 'Guardar y Sembrar',
                            closeButton: true,
                            class: 'codigo-sobre-submit',
                            callbackCancel: function () {
                                return;
                            },
                            callbackSave: function () {
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        observacion: $('#observacion').val(),
                                        bandejas: $('#bandejas').val(),
                                        idPedidoProducto: info.event.id
                                    },
                                    url: __HOMEPAGE_PATH__ + "siembra/guardar/",
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
                            },
                            callbackSuccess: function () {
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        observacion: $('#observacion').val(),
                                        bandejas: $('#bandejas').val(),
                                        horaSiembra: $('#horaSiembra').val(),
                                        idPedidoProducto: info.event.id
                                    },
                                    url: __HOMEPAGE_PATH__ + "siembra/guardar_y_sembrar/",
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
                        $('.observacion').click(function(){
                            $('.observacion-edit').toggle();
                        });
                        $('.bandejas').click(function(){
                            $('.bandejas-edit').toggle();
                        });
                        $('.hora').click(function(){
                            $('.hora-edit').toggle();
                        });
                        let date = new Date();
                        let hour = date.getHours(),
                            min = date.getMinutes();
                        hour = (hour < 10 ? "0" : "") + hour;
                        min = (min < 10 ? "0" : "") + min;
                        let displayTime = hour + ":" + min;
                        $('#horaSiembra').val(displayTime);
                        $('.hora-siembra').text(displayTime);
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.attr('data-id', info.event.id);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    element.css('min-height', '75px');
                    element.find('.fc-title').css('font-size', '1.1rem');
                    element.find('.fc-content').css('margin-top', '1%');
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
                }
            });
            calendar.render();
        }
    };
}();

jQuery(document).ready(function() {
    KTCalendarListView.init();
});

function showDialog(options) {

    var d = bootbox.dialog({
        backdrop: true,
        buttons: {
            cancel: {
                label: options.labelCancel ? options.labelCancel : "Cancelar",
                className: "btn-sm btn-light-dark pull-left font-weight-bold cancel ",
                callback: function () {
                    var result = options.callbackCancel();
                    return result;
                }
            },
            danger: {
                label: options.labelSave ? options.labelSave : "Guardar",
                className: "btn-sm btn-submit submit-button btn-light-primary font-weight-bold success",
                callback: function () {
                    var result = options.callbackSave();
                    return result;
                }
            },
            success: {
                label: options.labelSuccess ? options.labelSuccess : "Guardar",
                className: "btn-sm btn-submit submit-button btn-light-success font-weight-bold success " + (options.color ? options.color : '') + (options.class ? options.class : ''),
                callback: function () {
                    var result = options.callbackSuccess();
                    return result;
                }
            }
        },
        className: options.className,
        message: options.contenido,
        title: options.titulo

    });

    $(d).find('.modal-header').addClass(options.color ? options.color : '');
    return d;
}
