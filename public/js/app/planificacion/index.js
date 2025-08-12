
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
                    right: 'dayGridMonth,dayGridWeek,dayGridDay'
                },
                hiddenDays:  [ 0 ],
                height: 800,
                contentHeight: 750,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio
                views: {
                    dayGridMonth: { buttonText: 'mes' },
                    dayGridWeek: { buttonText: 'semana' },
                    dayGridDay: { buttonText: 'dia' }
                },
                eventLimitText: 'MÁS',
                defaultView: 'dayGridWeek',
                defaultDate: TODAY,
                eventDurationEditable: false,
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
                            color: 'yellow ',
                            labelCancel: 'Cerrar',
                            labelSuccess: 'Guardar',
                            closeButton: true,
                            class: 'codigo-sobre-submit',
                            callbackCancel: function () {
                                return;
                            },
                            callbackSuccess: function () {
                                if (info.event.start.toISOString() < todayDate.toISOString()){
                                    Swal.fire({
                                        title: "ERROR",
                                        html: "No se puede planificar un pedido de una fecha anterior a la de hoy!",
                                    });
                                    return false;
                                }
                                $.ajax({
                                    type: 'post',
                                    dataType: 'json',
                                    data: {
                                        codSobre: $('#codSobre').val(),
                                        observacion: $('#observacion').val(),
                                        idPedidoProducto: info.event.id
                                    },
                                    url: __HOMEPAGE_PATH__ + "planificacion/guardar_orden_siembra/",
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
                        initSobreInput();
                        initObservacionInput();
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.find('.fc-title').html(info.event.title);
                    element.attr('data-id', info.event.id);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    element.find('.fc-title').css('font-size', '1rem');
                    element.find('.tipo-bandeja').attr('style', 'color: ' + info.event.extendedProps.colorBandeja + ' !important;');
                    info.el.style.borderColor = 'black';
                    if (info.event.extendedProps.colorProducto) {
                        // Fondo
                        info.el.style.backgroundColor = info.event.extendedProps.colorProducto;
                    }
                },
                eventDrop: function(info) {
                    const fechaNueva = info.event.start.toISOString().substring(0, 10);
                    const fechaActual = info.event.extendedProps.fechaSiembraPlanificacion;

                    let year, month, day;

                    [year, month, day] = fechaNueva.split('-');
                    const fechaNuevaFormateada = `${day}/${month}/${year}`;

                    [year, month, day] = fechaActual.split('-');
                    const fechaActualFormateada = `${day}/${month}/${year}`;

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
                                        <label class="text-dark text-hover-primary mb-1 font-size-lg"><strong>Nueva fecha de siembra</strong></label>\n\
                                        <span class="text-muted">'+fechaActualFormateada+ '<i class="fas fa-arrow-right" style="padding: 0px 10px;"></i>' +fechaNuevaFormateada+'</span>\n\
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
                                        <span class="text-muted">'+info.event.extendedProps.cantidadBandejas+'</span>\n\
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
                        titulo: '<span class="font-white text-center"> Modificar fecha de siembra pedido N° '+info.event.id +'</span>',
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

jQuery(document).ready(function() {
    KTCalendarListView.init();
    $.ajax({
        type: 'POST',
        url: __HOMEPAGE_PATH__ + 'planificacion/pedidos-atrasados/',
    }).done(function (result) {
        if (result.cantidad) {
            Swal.fire({
                title: "Hay pedidos de días anteriores que no fueron planificados.",
                html: result.html,
                width: 1200,
                padding: "3em"
            });
        }
    });
});

function initSobreInput(){
    if ($('#codSobre').val() != ''){
        $('.sobre-input').hide();
    }
    $('.sobre').click(function(){
        $('.sobre-input').toggle();
    });
}

function initObservacionInput(){
    $('.observacion').click(function(){
        $('.observacion-input').toggle();
    });
}
