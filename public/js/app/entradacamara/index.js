
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
                //isRTL: KTUtil.isRTL(),
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridDay'
                },
                hiddenDays:  [ 0 ],
                height: 800,
                contentHeight: 2750,
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
                    var idPedido = info.event.extendedProps.idPedido;
                    var orden = info.event.extendedProps.orden;
                    var actionUrl = element.data('href');
                    $.ajax({
                        type: 'POST',
                        url: actionUrl,
                        data: {
                            id: idProducto
                        }
                    }).done(function (form) {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i><a target="_blank" href="'+__HOMEPAGE_PATH__+'pedido/'+idPedido+'/#'+idProducto+'"> Ingresar a camara Pedido N° '+idPedido+ ' Orden N° '+orden,
                            contenido: form,
                            color: 'yellow ',
                            labelCancel: 'Cerrar',
                            labelSuccess: 'Guardar En Camara',
                            closeButton: true,
                            class: 'entrada-camara-submit',
                            callbackCancel: function () {
                                return;
                            },
                            callbackSuccess: function () {

                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        fechaEntradaCamara: $('#fecha-entrada-camara').val(),
                                        idPedidoProducto: info.event.id,
                                        observacion: $('#observacion').val(),
                                        observacionCamara: $('#observacionCamara').val(),
                                    },
                                    url: __HOMEPAGE_PATH__ + "entrada_camara/guardar/",
                                    success: function (data) {
                                        if (!jQuery.isEmptyObject(data)) {
                                            $('.alert-success').hide();
                                            showFlashMessage("success", data.message);
                                            calendar.refetchEvents();
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
                        initObservacionInput();
                        initObservacionCamaraInput();
                        initFechaEntradaInput();
                        let date = new Date();
                        let hour = date.getHours();
                        let min = date.getMinutes();
                        hour = (hour < 10 ? "0" : "") + hour;
                        min = (min < 10 ? "0" : "") + min;
                        let fechaCompleta = $('.fecha-entrada-camara-hidden').text();
                        fechaCompleta = fechaCompleta.replace(' ', 'T');
                        $('#fecha-entrada-camara').val(fechaCompleta);
                        if ($('.estado').text() == 'EN CAMARA'){
                            $('.entrada-camara-submit').hide();
                            $('.fecha-entrada-camara-edit').remove();
                        }
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.find('.fc-title').html(info.event.title);
                    element.attr('data-id', info.event.id);
                    element.attr('data-orden', info.event.extendedProps.orden);
                    element.attr('data-idpedido', info.event.extendedProps.idPedido);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    element.css('min-height', '75px');
                    element.find('.fc-title').css('font-size', '1.1rem');
                    element.find('.fc-content').css('margin-top', '1%');
                    element.find('.tipo-bandeja').attr('style', 'color: ' + info.event.extendedProps.colorBandeja + ' !important;');
                    info.el.style.borderColor = 'black';

                    //COLOR FONDO
                    if (info.event.extendedProps.colorProducto) {
                        info.el.style.backgroundColor = info.event.extendedProps.colorProducto;
                    }

                    // Mostrar ícono de advertencia si hay observación
                    if (info.event.extendedProps.observacion && info.event.extendedProps.observacion.trim() !== '-') {
                        var warningIcon = $('<i class="fas fa-exclamation-triangle fa-lg text-warning ml-2" title="Este pedido tiene una observación"></i>');
                        element.find('.fc-title').append(warningIcon);
                    }

                    // Mostrar ícono de advertencia si hay observación camara
                    if (info.event.extendedProps.observacionCamara && info.event.extendedProps.observacionCamara.trim() !== '-') {
                        var warningIcon = $('<i class="fas fa-exclamation-triangle fa-lg text-warning ml-2" title="Este pedido tiene una observación en camara"></i>');
                        element.find('.fc-title').append(warningIcon);
                    }
                }
            });
            calendar.render();
        }
    };
}();

jQuery(document).ready(function() {
    KTCalendarListView.init();
    $.ajax({
        type: 'POST',
        url: __HOMEPAGE_PATH__ + 'entrada_camara/pedidos-atrasados/',
    }).done(function (result) {
        if (result.cantidad) {
            Swal.fire({
                title: "Hay pedidos de días anteriores que no fueron ingresados a camara.",
                html: result.html,
                width: 1200,
                padding: "3em"
            });
        }
    });
});

/**
 *
 * @returns {undefined}
 */
function initPreValidation() {

    $(".entrada-camara-submit").off('click').on('click', function (e) {
        e.preventDefault();

       if ($('#fecha-entrada-camara').val() != ''){
           return true;
       } else {
           Swal.fire({
               title: 'Ingrese la fecha de entrada a camara.',
               icon: "error"
           });
       }

        e.stopPropagation();
    });
}

function initFechaEntradaInput(){
    $('.fecha-entrada').click(function(){
        $('.fecha-entrada-camara-edit').toggle();
    });
}