var KTCalendarListView = function() {
    return {
        //main function to initiate the module
        init: function() {
            var todayDate = moment().startOf('day');
            var TODAY = todayDate.format('YYYY-MM-DD');

            var calendarEl = document.getElementById('kt_calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'list' ],
                //isRTL: KTUtil.isRTL(),
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,dayGridDay'
                },
                hiddenDays:  [ 0 ],
                height: 800,
                contentHeight: 3250,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio
                views: {
                    dayGridMonth: { buttonText: 'mes' },
                    dayGridWeek: { buttonText: 'semana' },
                    dayGridDay: { buttonText: 'dia' }
                },

                defaultView: 'dayGridWeek',
                defaultDate: TODAY,
                displayEventTime: false,
                editable: true,
                eventLimit: true, // allow "more" link when too many events
                navLinks: true,
                eventSources: [
                    {
                        url: __HOMEPAGE_PATH__ + "orden_carga/index_table/",
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
                    var idEntrega = element.data('id');
                    var actionUrl = element.data('href');
                    $.ajax({
                        type: 'POST',
                        url: actionUrl,
                        data: {
                            id: idEntrega
                        }
                    }).done(function (form) {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i><a href="'+__HOMEPAGE_PATH__+'entrega/'+idEntrega+'"> Orden de carga Entrega N° ' + idEntrega + '</a>',
                            contenido: form,
                            className: 'modal-dialog-small',
                            color: 'yellow ',
                            labelCancel: 'Cerrar',
                            labelSuccess: 'ENTREGAR',
                            closeButton: true,
                            callbackCancel: function () {

                            },
                            callbackSuccess: function () {
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        fecha: TODAY,
                                        idEntrega: info.event.id
                                    },
                                    url: __HOMEPAGE_PATH__ + "orden_carga/entregar/",
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
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                        $('.yellow').attr('font-size', '17px');
                        if ($('.estado').html().includes('ENTREGADO')) {
                            $('.success').hide();
                        }

                        $(document).off('click.ordenCargaPreparada', '#cambiar-preparada')
                            .on('click.ordenCargaPreparada', '#cambiar-preparada', function () {
                                var button = $(this);

                                $.ajax({
                                    type: 'POST',
                                    dataType: 'json',
                                    url: button.data('url'),
                                    data: {
                                        _token: button.data('token')
                                    }
                                }).done(function (data) {
                                    var estado = $('#estado-preparada');
                                    var icono = button.find('i');
                                    var texto = button.find('span');

                                    estado
                                        .toggleClass('label-light-success', data.preparada)
                                        .toggleClass('label-light-warning', !data.preparada)
                                        .text(data.preparada ? 'PREPARADA' : 'NO PREPARADA');

                                    button
                                        .toggleClass('btn-light-warning', data.preparada)
                                        .toggleClass('btn-light-success', !data.preparada);

                                    icono
                                        .toggleClass('fa-undo', data.preparada)
                                        .toggleClass('fa-check', !data.preparada);

                                    texto.text(data.preparada ? 'Marcar como no preparada' : 'Marcar como preparada');
                                    showFlashMessage('success', data.message);
                                    calendar.refetchEvents();
                                }).fail(function (xhr) {
                                    var message = xhr.responseJSON && xhr.responseJSON.message
                                        ? xhr.responseJSON.message
                                        : 'No se pudo modificar el estado de preparación.';
                                    showFlashMessage('error', message);
                                });
                            });

                        $(document).off('click.cancelarEntregaOrdenCarga', '#cancelar-entrega-orden-carga')
                            .on('click.cancelarEntregaOrdenCarga', '#cancelar-entrega-orden-carga', function () {
                                var button = $(this);

                                Swal.fire({
                                    title: '¿Cancelar la entrega?',
                                    text: 'La orden de carga volverá al estado SIN REMITO.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Sí, cancelar',
                                    cancelButtonText: 'No'
                                }).then(function (result) {
                                    if (!result.isConfirmed) {
                                        return;
                                    }

                                    $.ajax({
                                        type: 'POST',
                                        dataType: 'json',
                                        url: button.data('url'),
                                        data: {
                                            _token: button.data('token')
                                        }
                                    }).done(function (data) {
                                        $('.modal').modal('hide');
                                        showFlashMessage('success', data.message);
                                        calendar.refetchEvents();
                                    }).fail(function (xhr) {
                                        var message = xhr.responseJSON && xhr.responseJSON.message
                                            ? xhr.responseJSON.message
                                            : 'No se pudo cancelar la entrega.';
                                        showFlashMessage('error', message);
                                    });
                                });
                            });
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.find('.fc-title').html(info.event.title);
                    element.attr('data-id', info.event.id);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    element.addClass(info.event.extendedProps.preparada
                        ? 'orden-carga-preparada'
                        : 'orden-carga-no-preparada');
                    element.css('min-height', '75px');
                    element.find('.fc-title').css('font-size', '1.1rem');
                    element.find('.fc-content').css('margin-top', '1%');
                    info.el.style.borderColor = 'black';

                    //COLOR FONDO
                    if (info.event.extendedProps.colorEstado) {
                        info.el.style.backgroundColor = info.event.extendedProps.colorEstado;
                    }
                },
                eventDrop: function(info) {
                    const fechaNueva = info.event.start.toISOString().substring(0, 10);

                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: __HOMEPAGE_PATH__ + "orden_carga/cambiar_fecha_orden_carga/",
                        data: {
                            fechaNueva: fechaNueva,
                            idEntrega: info.event.id
                        },
                        success: function (data) {
                            if (!jQuery.isEmptyObject(data)) {
                                showFlashMessage("success", data.message);
                            }
                        },
                        error: function () {
                            alert('Ocurrió un error al modificar la fecha.');
                            info.revert(); // 🔙 vuelve el evento a la fecha original
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

    $.ajax({
        type: 'POST',
        url: __HOMEPAGE_PATH__ + 'orden_carga/pedidos-atrasados/',
    }).done(function (result) {
        if (result.cantidad) {
            Swal.fire({
                title: "Hay pedidos de días anteriores que no fueron entregados.",
                html: result.html,
                width: 1200,
                padding: "3em"
            });
        }
    });
});
