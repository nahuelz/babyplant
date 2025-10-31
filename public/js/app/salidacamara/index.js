var fv;
var EN_INVERNACULO = 5;
var KTCalendarListView = function() {
    return {
        //main function to initiate the module
        init: function() {
            var todayDate = moment().startOf('day');
            var YM = todayDate.format('YYYY-MM');
            var YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
            var TODAY = todayDate.format('YYYY-MM-DD');
            var TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');
            let actualizarContadores;
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
                moreLinkText: "más",
                height: 800,
                contentHeight: 2750,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio

                views: {
                    dayGridMonth: { buttonText: 'mes' },
                    dayGridWeek: { buttonText: 'semana' },
                    dayGridDay: { buttonText: 'dia' }
                },
                eventLimitText: 'MÁS',
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
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i><a target="_blank" href="'+__HOMEPAGE_PATH__+'pedido/'+idPedido+'/#'+idProducto+'"> Salida de camara Pedido N° '+idPedido+' Orden N° '+orden,
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
                                $('form[name="salida_camara"]').submit();
                            }
                        });
                        $('.bs-popover-top').hide();
                        $('.modal-dialog').css('width', '80%');
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                        initObservacionInput();
                        initObservacionCamaraInput();
                        if (__ID_ESTADO__ !== EN_INVERNACULO) {
                            initmesadaForm();
                            $('#salida_camara_mesadaUno_tipoMesada').select2();
                            //initMesadaChainedSelect();
                            $('#salida_camara_mesadaUno_tipoProducto').val('').select2().change();
                            if ($('#ultimaMesada').val() != '') {
                                $('#salida_camara_mesadaUno_tipoMesada').val($('#ultimaMesada').val()).select2();
                            }
                            initValidations();
                            $('.remove-mesada').hide();
                        }else{
                            $('.salida_camara_submit').hide();
                        }
                    });
                },
                eventRender: function(info) {
                    var element = $(info.el);
                    element.find('.fc-title').html(info.event.title);
                    element.attr('data-id', info.event.id);
                    element.attr('data-idpedido', info.event.extendedProps.idPedido);
                    element.attr('data-toggle', 'modal');
                    element.attr('data-target', '#productoModal');
                    element.attr('data-href', info.event.extendedProps.href);
                    element.css('min-height', '40px');
                    element.find('.fc-title').css('font-size', '1rem');
                    element.find('.tipo-bandeja').attr('style', 'color: ' + info.event.extendedProps.colorBandeja + ' !important;');
                    info.el.style.borderColor = 'black';

                    //COLOR FONDO
                    if (info.event.extendedProps.colorProducto) {
                        info.el.style.backgroundColor = info.event.extendedProps.colorProducto;
                    }
                },
                // Reemplaza el viewSkeletonRender con este código
                viewSkeletonRender: function(info) {
                    if (info.view.type === 'dayGridWeek') {
                        const calendar = this;

                        actualizarContadores = () => {
                            try {
                                const events = calendar.getEvents();
                                if (events.length === 0) {
                                    return;
                                }

                                // Contar bandejas por día
                                const bandejasPorDia = events.reduce((acumulador, event) => {
                                    if (event.start) {
                                        const fecha = event.start.toISOString().split('T')[0];
                                        const cantidad = parseFloat(event.extendedProps?.cantidadBandejas || 0, 10);
                                        acumulador[fecha] = (acumulador[fecha] || 0) + cantidad;
                                    }
                                    return acumulador;
                                }, {});

                                // Actualizar la UI
                                document.querySelectorAll('.fc-day-header[data-date]').forEach(header => {
                                    const fecha = header.getAttribute('data-date');
                                    const contador = bandejasPorDia[fecha] || 0;

                                    // Limpiar contador anterior
                                    const contadorAnterior = header.querySelector('.day-bandejas-counter');
                                    if (contadorAnterior) {
                                        contadorAnterior.remove();
                                    }

                                    // Agregar nuevo contador si hay bandejas
                                    if (contador > 0) {
                                        const nuevoContador = document.createElement('div');
                                        nuevoContador.className = 'day-bandejas-counter';
                                        nuevoContador.textContent = `${contador} bandejas`;
                                        header.appendChild(nuevoContador);
                                    }
                                });

                            } catch (error) {
                                console.error('Error al actualizar contadores:', error);
                            }
                        };

                        // Programar actualizaciones
                        [100, 1000, 3000].forEach(delay => {
                            setTimeout(actualizarContadores, delay);
                        });

                        // Actualizar cuando cambien los eventos
                        calendar.on('eventChange', actualizarContadores);
                        calendar.on('eventAdd', actualizarContadores);
                        calendar.on('eventRemove', actualizarContadores);
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

            document.addEventListener('click', function(e) {
                const target = e.target.closest('.fc-prev-button, .fc-next-button, .fc-today-button');
                if (target) {
                    console.log('Botón de navegación clickeado');
                    setTimeout(() => {
                        if (calendar.view.type === 'dayGridWeek') {
                            console.log('Actualizando contadores...');
                            if (typeof actualizarContadores === 'function') {
                                actualizarContadores();
                            }
                        }
                    }, 100); // Pequeño retraso para asegurar que el calendario se actualice
                }
            });
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
    $('#salida_camara_mesadaUno_cantidadBandejas').on('keyup', function(){
        if ($(this).val() > parseFloat($('#cantidadBandejasReales').val())){
            $(this).val($('#cantidadBandejasReales').val());
        }
    });
}

jQuery(document).ready(function() {
    KTCalendarListView.init();

});

function cantidadDeBandejasValida(){
    let cantidadBandejasMesadaUno = $('#salida_camara_mesadaUno_cantidadBandejas').val() ? parseFloat($('#salida_camara_mesadaUno_cantidadBandejas').val()) : 0;
    let cantidadBandejasMesadaDos = $('#salida_camara_mesadaDos_cantidadBandejas').val() ? parseFloat($('#salida_camara_mesadaDos_cantidadBandejas').val()) : 0;
    let cantidadBandejasReales = parseFloat($('#cantidadBandejasReales').val());
    let cantidadTotalBandejas = cantidadBandejasMesadaUno + cantidadBandejasMesadaDos;

    return (cantidadTotalBandejas === cantidadBandejasReales);
}

/**
 *
 * @returns {undefined}
 */
function initPreValidation() {

    $(".salida_camara_submit").off('click').on('click', function (e) {
        e.preventDefault();
        if (cantidadDeBandejasValida()) {
            if ($('#salida_camara_mesadaUno_tipoMesada').val() != '') {
                if ($('#salida_camara_mesadaUno_cantidadBandejas').val() > 0) {
                    if ($('#salida_camara_mesadaDos_cantidadBandejas').val() < 1) {
                        $("#salida_camara_mesadaDos_cantidadBandejas").attr('disabled', 'disabled');
                        $("#salida_camara_mesadaDos_tipoMesada").attr('disabled', 'disabled');
                    }
                    $('form[name="salida_camara"]').submit();
                }else{
                    Swal.fire({
                        title: 'Debe ingresar al menos 1 bandeja en la mesada.',
                        icon: "error"
                    });
                    return false;
                }
            } else {
                Swal.fire({
                    title: 'Debe compeltar todos los datos.',
                    icon: "error"
                });
                return false;
            }
        } else {
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
function initmesadaForm() {
    $('#salida_camara_mesadaUno_tipoMesada').select2();
    $('#salida_camara_mesadaDos_tipoMesada').select2();
    $('.row-mesada-empty').hide();
    $('.mesada-dos').hide();
    $('#salida_camara_mesadaUno_cantidadBandejas').val($('#cantidadBandejasReales').val())
    $(document).on('click', '.add-mesada', function (e) {
        e.preventDefault();
        disableMesadaDosOptionHandler();
        removeMesadaHandler();
        $('#salida_camara_mesadaDos_cantidadBandejas').on('keyup', function(){
            if ($(this).val() > parseFloat($('#cantidadBandejasReales').val())){
                $(this).val($('#cantidadBandejasReales').val());
            }
        });
        $('.add-mesada').hide();
        $('.mesada-dos').show();
        $("#salida_camara_mesadaDos_tipoMesada>option[value="+$('#salida_camara_mesadaUno_tipoMesada').val()+"]").attr('disabled','disabled');
    });
}

function disableMesadaDosOptionHandler(){
    $('#salida_camara_mesadaUno_tipoMesada').on('change', function(){
        $('#salida_camara_mesadaDos_tipoMesada').val('').select2();
        $("#salida_camara_mesadaDos_tipoMesada>option").removeAttr('disabled');
        $("#salida_camara_mesadaDos_tipoMesada>option[value="+$('#salida_camara_mesadaUno_tipoMesada').val()+"]").attr('disabled','disabled');
    })
}

function removeMesadaHandler(){
    $('.remove-mesada').on('click', function(){
        $('.mesada-dos').hide();
        $('#salida_camara_mesadaDos_tipoMesada').val('').select2();
        $('#salida_camara_mesadaDos_cantidadBandejas').val('');
        $('.add-mesada').show();
    })
}
