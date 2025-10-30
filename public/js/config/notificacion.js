jQuery(document).ready(function () {
    loadNotificacionesUsuario();

    initHandlerVerNotificacionButton();
    initHandlerMarcarNotificacionVistaButton();

//    initHandlerVerTodasNotificacionesButton();
//    initHandlerMarcarTodasNotificacionesVistasButton();
});

/**
 * 
 * @returns {undefined}
 */
function loadNotificacionesUsuario() {
    $.ajax({
        type: 'POST',
        url: __HOMEPAGE_PATH__ + 'notificacion/ultimas_notificaciones/',
        global: false
    }).done(function (notificaciones) {
        $('#content-notificaciones').html(notificaciones);

        var cantidadNotificaciones = $('#content-notificaciones .notificacion-link').length;
        var $notificationBadge = $('.notification-badge');

        // Actualizar contador
        if (cantidadNotificaciones > 0) {
            $notificationBadge.text(cantidadNotificaciones).show();
        } else {
            $notificationBadge.hide();
        }

        $('.total-notificaciones').text(cantidadNotificaciones);

        if (cantidadNotificaciones > 0) {
            $('.notificaciones-pulse').addClass('pulse');
        } else {
            $('.notificaciones-pulse').removeClass('pulse');
        }

        if (cantidadNotificaciones > 0) {
            $('.fa-bell').addClass('faa-tada animated');
            $('.notificacion_marcar_todas_vistas').show();
        } else {
            $('.fa-bell').removeClass('faa-tada animated');
            $('.notificacion_marcar_todas_vistas').hide();
        }

        toggleNotificaciones();
    }).fail(function (jqXHR, textStatus, errorThrown) {
        var msg = "No se pudo  marcar como vista la notificación. Intente nuevamente.";
        
        console.log(msg);
    });
}

/**
 * 
 * @returns {undefined}
 */
function toggleNotificaciones() {
    if ($('.notificacion_ver').length === 0) {
        $('.notificacion_marcar_todas_vistas').hide('slow');
    } else {
        $('.notificacion_marcar_todas_vistas').show();
    }
}

/**
 * 
 * @returns {undefined}
 */
function initHandlerVerNotificacionButton() {
    $(document).on('click', '.notificacion_ver', function (e) {
        e.preventDefault();
        var notificacionLink = $(this);
        var mensaje = $(this).data('contenido-notificacion');
        var titulo = $(this).data('titulo-notificacion');
        var imagenUrl = $(this).data('imagen');

        // Crear el contenido del diálogo
        var contenido = '';

        // Agregar la imagen si existe
        if (imagenUrl) {
            contenido += `
                <div class="text-center mb-3">
                    <img src="${imagenUrl}" 
                         class="img-fluid rounded" 
                         style="max-height: 300px; width: auto;"
                         alt="Imagen de la notificación">
                </div>`;
        }

        // Agregar el mensaje
        contenido += '<div class="notificacion-contenido">' + mensaje + '</div>';

        dialog = showDialog({
            color: 'blue',
            contenido: contenido,
            titulo: '<i class="fa fa-info-circle"></i> ' + titulo,
            labelSuccess: 'Marcar como leída',
            labelCancel: 'Cerrar',
            callbackCancel: function () {
                return;
            },
            callbackSuccess: function () {
                notificacionMarcarVista(notificacionLink);
            }
        });

        // Ajustar el tamaño del diálogo si hay imagen
        if (imagenUrl) {
            $('.modal-dialog').css('max-width', '800px');
        } else {
            $('.modal-dialog').css('width', '70%');
        }

        e.stopPropagation();
    });
}


/**
 * 
 * @returns {undefined}
 */
function initHandlerMarcarNotificacionVistaButton() {
    $(document).on('click', '.notificacion_marcar_visto', function (e) {
        e.preventDefault();
        var notificacionLink = $(this);
        notificacionMarcarVista(notificacionLink);
        bootbox.hideAll();
        e.stopPropagation();
    });
}

/**
 * 
 * @returns {undefined}
 */
function initHandlerVerTodasNotificacionesButton() {
    $(document).on('click', '.notificacion_ver_todo', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + 'notificacion/todas_notificaciones/'
        }).done(function (response) {
            var dialog = showDialog({
                titulo: '<i class="fa fa-info-circle"></i> Notificaciones',
                contenido: response,
                color: 'blue',
                labelSuccess: 'Cerrar',
                closeButton: false,
                callbackCancel: function () {
                    return;
                },
                callbackSuccess: function () {
                    return;
                }
            });

            $('.modal-dialog').css('width', '70%');
            $('.modal .cancel').hide();

            dialog.on("shown.bs.modal", function () {
                App.initSlimScroll($('.modal-body'));
            });

        });

        e.stopPropagation();
    });
}

/**
 * 
 * @returns {undefined}
 */
function initHandlerMarcarTodasNotificacionesVistasButton() {
    $(document).on('click', '.notificacion_marcar_todas_vistas', function (e) {
        e.preventDefault();
        notificacionMarcarVista();
        e.stopPropagation();
    });
}

/**
 * 
 * @param {type} $ocultarLink
 * @returns {undefined}
 */
function notificacionMarcarVista($ocultarLink) {
    var ids = [];
    var todos = $ocultarLink === undefined;
    var mensajeSuccess = "Las notificaciones fueron marcadas como le&iacute;das correctamente.";

    if (todos) {
        $('.notificacion_ver').each(function () {
            ids.push($(this).data('id-notificacion'));
        });
    } else {
        ids.push($ocultarLink.data('id-notificacion'));
    }

    $.ajax({
        type: 'POST',
        url: __HOMEPAGE_PATH__ + 'notificacion/marcar_vista/',
        global: false,
        data: {
            ids: JSON.stringify(ids)
        }
    }).done(function (data) {

        try {
            var dataParsed = jQuery.parseJSON(data);
        } catch (err) {
            dataParsed = null;
        }

        if (dataParsed === null) {

            var msg = "No se pudo  marcar como vista la notificación. Intente nuevamente.";

            console.log(msg);
        } else {
            if (todos) {
                $('.notificacion_marcar_visto').each(function () {
                    ocultarNotificacion($(this));
                });
            } else {
                ocultarNotificacion($ocultarLink);
            }
        }

        loadNotificacionesUsuario();

        showFlashMessage('success', mensajeSuccess);

    }).fail(function (jqXHR, textStatus, errorThrown) {

        var msg = "No se pudo  marcar como vista la notificación. Intente nuevamente.";

        console.log(msg);
    });
}

/**
 * 
 * @param {type} $ocultarLink
 * @returns {undefined}
 */
function ocultarNotificacion($ocultarLink) {
    $ocultarLink.closest('li').hide('slow', function () {
        $(this).remove();
        toggleNotificaciones();
    });
}



/* 
 *  Se muestra un mensaje con un contenido dos acciones,
 *  una de cancelacion y otra de confirmacion
 *  
 *  Al invocar se deben enviar los siguientes parametros
 *  con un objeto json: titulo, contenido, callbackCancel y
 *  callbackSuccess.
 *  
 */
function showDialog(options) {

    var d = bootbox.dialog({
        backdrop: true,
        buttons: {
            danger: {
                label: options.labelCancel ? options.labelCancel : "Cancelar",
                className: "btn-sm btn-light-dark pull-left font-weight-bold cancel ",
                callback: function () {
                    var result = options.callbackCancel();
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
    initPreValidation();

    // Add resizable function to modal
        /*
    $(d).find(".modal-content").resizable({
        alsoResize: " .bootbox-body",
        handles: "e, s",
        minHeight: 250,
        minWidth: 350
    });*/

    return d;
}

function initPreValidation(){}
