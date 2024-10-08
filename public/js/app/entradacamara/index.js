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

                defaultView: 'dayGridDay',
                defaultDate: TODAY,

                editable: true,
                eventLimit: true, // allow "more" link when too many events
                navLinks: true,
                eventSources: [
                    {
                        url: __HOMEPAGE_PATH__ + "entrada_camara/index_table/",
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
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar a camara pedido N° '+idProducto,
                            contenido: form,
                            color: 'yellow ',
                            labelCancel: 'Cerrar',
                            labelSuccess: 'Guardar',
                            closeButton: true,
                            class: 'codigo-sobre-submit',
                            callbackCancel: function () {
                                return;
                            },
                            callbackSuccess: function () {

                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        codSobre: $('#codSobre').val(),
                                        idPedidoProducto: info.event.id
                                    },
                                    url: __HOMEPAGE_PATH__ + "entrada_camara/guardar/",
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
                        $('.modal-dialog').css('width', '80%');
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
                                <label class="text-dark text-hover-primary mb-1 font-size-lg">Codigo de sobre</label>\n\
                                <span class="text-muted">'+info.event.extendedProps.codigoSobre+'</span>\n\
                            </div>\n\
                        </div>';
                    showDialog({
                        titulo: '<span class="font-white text-center"> Modificar fecha de entrada a camara pedido N° '+info.event.id +'</span>',
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
                                url: __HOMEPAGE_PATH__ + "entrada_camara/cambiar_fecha_entrada_camara/",
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
});

/**
 *
 * @returns {undefined}
 */
function initPreValidation() {

    $(".codigo-sobre-submit").off('click').on('click', function (e) {
        e.preventDefault();

       if ($('#codSobre').val() != ''){
           return true;
       } else {
           Swal.fire({
               title: 'Ingrese el codigo del sobre.',
               icon: "error"
           });
       }

        e.stopPropagation();
    });
}