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
                hiddenDays:  [ 0 ],
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
                        url: __HOMEPAGE_PATH__ + "planificacion/index_table/",
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
                    alert('Event: ' + info.event.title);
                },
                eventRender: function(info) {
                    var element = $(info.el);

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
                        <div class="d-flex align-items-center mb-10">\n\
                            <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                <span class="symbol-label">\n\
                                    <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                        <i class="fas fa-calendar-alt icon-2x text-dark"></i>\n\
                                    </span>\n\
                                </span>\n\
                            </div>\n\
                            <div class="d-flex flex-column font-weight-bold">\n\
                                <label class="text-dark text-hover-primary mb-1 font-size-lg"><strong>Nueva fecha de siembra</strong></label>\n\
                                <span class="text-muted">'+info.event.extendedProps.fechaSiembra+ '<i class="fas fa-arrow-right" style="padding: 0px 10px;"></i>' +info.event.start.toISOString().substring(0, 10)+'</span>\n\
                            </div>\n\
                        </div>\n\
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
                        <div class="d-flex align-items-center mb-10">\n\
                            <div class="symbol symbol-40 symbol-light-primary mr-5">\n\
                                <span class="symbol-label">\n\
                                    <span class="svg-icon svg-icon-xl svg-icon-primary">\n\
                                        <i class="fas fa-leaf icon-2x text-dark"></i>\n\
                                    </span>\n\
                                </span>\n\
                            </div>\n\
                            <div class="d-flex flex-column font-weight-bold">\n\
                                <label class="text-dark text-hover-primary mb-1 font-size-lg">Producto</label>\n\
                                <span class="text-muted">'+info.event.extendedProps.producto+'</span>\n\
                            </div>\n\
                        </div>\n\
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
                        </div>';
                    showDialog({
                        titulo: '<span class="font-white text-center"> Modificar fecha de siembra </span>',
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
                                    nuevaFechaSiembra: info.event.start.toISOString().substring(0, 10),
                                    idPedidoProducto: info.event.id
                                },
                                url: __HOMEPAGE_PATH__ + "planificacion/cambiar_fecha_planificacion/",
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
                }
            });
            calendar.render();
        }
    };
}();

jQuery(document).ready(function() {
    KTCalendarListView.init();
    document.getElementById('btn-fscreen').addEventListener('click', mkFull);
});

var elem = document.getElementById('fScreen');
var isFull = false;

function mkFull() {
    //fScreen DIV
    var elem = document.getElementById("fScreen");

    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    } else if (elem.mozRequestFullScreen) {
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
}

document.addEventListener('webkitfullscreenchange', function(e) {
    isFull = !isFull;

    if (isFull) {
        elem.style = 'width: 100%; height: 85%; background: green;';
    } else {
        elem.style = 'width: 100px; height: 500px; background: red;';
    }
});