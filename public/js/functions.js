jQuery(document).ready(function () {
    //Borrar action de las tablas
    initBorrarButton();
});

function initBorrarButton() {
    $(document).on('click', '.accion-borrar', function (e) {
        e.preventDefault();
        var a_href = $(this).attr('href');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Confirma la eliminación?',
            callbackOK: function () {
                location.href = a_href;
            }
        });
        e.stopPropagation();
    });
}

function show_confirm(options_in) {
    var options = $.extend({
        title: 'Confirmar',
        msg: '¿Desea continuar?',
        callbackOK: function () {
        },
        callbackCancel: function () {
        }
    }, options_in);

    Swal.fire({
        title: options.title,
        text: options.msg,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Ok',
        cancelButtonText: 'Cancelar',
    }).then(function (result) {
        if (result.value) {
            options.callbackOK();
        } else {
            options.callbackCancel();
        }
    });

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
            if (!checkFormIsEmpty(deletableRow)) {
                show_confirm({
                    title: 'Confirmar',
                    type: 'warning',
                    msg: '¿Confirma la eliminación?',
                    callbackOK: function () {
                        deletableRow.hide('slow', function () {
                            customPreDeleteLinkOnCallbackOk(deletableRow);
                            deletableRow.remove();

                            customDeleteLinkOnCallbackOk();
                        });
                    }
                });
            } else {
                deletableRow.hide('slow', function () {
                    customPreDeleteLinkOnCallbackOk(deletableRow);
                    deletableRow.remove();
                    customDeleteLinkOnCallbackOk();
                });
            }

            e.stopPropagation();

        });
    });
}

/**
 * 
 * @param {type} element
 * @returns {Boolean}
 */
function checkFormIsEmpty(element) {
    var allEmpty = true;
    var $fields = $(element).find(':input:not([readonly],[type=checkbox]), select, textarea').not('.ignore');

    $fields.each(function () {
        if ($(this).val() !== '') {
            allEmpty = false;
            return false;
        }
    });

    return allEmpty;
}

/**
 * 
 * @returns {Boolean}
 */
function customDeleteLinkOnCallbackOk() {
    return true;
}

function show_dialog(options) {
    Swal.fire({
        title: options.title,
        html: options.contenido,
        showCancelButton: true,
        confirmButtonText: 'Ok',
        cancelButtonText: 'Cancelar'
    }).then(function (result) {
        if (result.isConfirmed) {
            options.callbackOK();
        } else if (result.isDenied) {
            options.callbackCancel();
        }
    });

}

/**
 * 
 * @param {type} type
 * @param {type} message
 * @param {type} delay
 * @param {type} className
 * @returns {undefined}
 */
function showFlashMessage(type, message, delay, className) {

    delay = typeof delay !== 'undefined' ? delay : 10000;
    className = typeof className !== 'undefined' ? className : '#flash-messages-container';

    var icon = "check";

    switch (type) {
        case 'success':
            icon = "fa-check-circle";
            break;
        case 'error':
            icon = "fa-exclamation-circle";
            type = 'danger';
            break;
        case 'warning':
            icon = "fa-exclamation-triangle";
            break;
        case 'info':
            icon = "fa-info-circle";
            break;
    }

    var id = Math.floor(Math.random() * 1000);

    var alertDiv =
            `<div class="alert alert-custom alert-${type} fade show mb-5" role="alert" id="${id}">
                <div class="alert-icon"><i class="fas ${icon}"></i></div>
                <div class="alert-text">${message}</div>
                <div class="alert-close">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true"><i class="ki ki-close"></i></span>
                    </button>
                </div>
            </div>`;

    $(className).prepend(alertDiv);

    $('body,html').animate({
        scrollTop: 0
    }, 400);

    setTimeout(
            function () {
                if (delay > 0) {
                    $('#' + id).show('slow').delay(delay).hide('slow', function () {
                        $(this).remove();
                    });
                } else {
                    $('#' + id).show('slow');
                }
            }, 500);
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
    var selectedElement = null;
    var lastXHR = null;

    if (isEdit || (preserve_values === true || preserve_values === "true")) {
        selectedElement = $targetSelect.val();
    }

    $sourceSelect.on("change", function () {
        var sourceVal = $(this).val();

        // Limpio y deshabilito mientras cargo (y quito cualquier readonly que te quede de resetSelect)
        $targetSelect.empty()
            .append(new Option('Seleccione...', '', true, false))
            .prop('disabled', true)
            .removeAttr('readonly')
            .trigger('change'); // refresca UI si ya está con Select2

        var data = {
            id_entity: sourceVal,
            extra_data: (typeof getExtraDataChainedSelect === 'function') ? getExtraDataChainedSelect() : {}
        };

        if (!customChainedSelect($sourceSelect, $targetSelect, data)) {
            return;
        }

        if (!sourceVal) {
            return; // nada seleccionado en el origen
        }

        // Evitar respuestas viejas
        if (lastXHR && lastXHR.readyState !== 4) {
            try { lastXHR.abort(); } catch (e) {}
        }

        lastXHR = $.ajax({
            type: 'POST',
            url: ajaxURL,
            data: data,
            dataType: 'json',
            success: function (rows) {
                $targetSelect.empty(); // limpio de nuevo para cargar datos reales

                if (!Array.isArray(rows) || rows.length === 0) {
                    $targetSelect.prop('disabled', true).removeAttr('readonly').trigger('change');
                    return;
                }

                // Cargar opciones
                for (var i = 0; i < rows.length; i++) {
                    $targetSelect.append(new Option(rows[i].denominacion, rows[i].id));
                }

                // Habilitar y setear valor preservado si corresponde
                $targetSelect.prop('disabled', false).removeAttr('readonly');

                if (selectedElement) {
                    $targetSelect.val(selectedElement);
                } else {
                    $targetSelect.val(null);
                }
                $targetSelect.trigger('change'); // importante para refrescar el widget

                selectedElement = null;

                // Si resetSelect destruyó el Select2, lo re-inicializo.
                // (Select2 v4 marca el select con la clase "select2-hidden-accessible" cuando está activo)
                if (!$targetSelect.hasClass('select2-hidden-accessible')) {
                    $targetSelect.select2();
                }

                if (typeof customAfterChainedSelect === 'function') {
                    customAfterChainedSelect();
                }
            },
            error: function () {
                // En error, mantengo deshabilitado
                $targetSelect.prop('disabled', true).removeAttr('readonly').trigger('change');
            }
        });
    }).trigger('change');
}



/**
 * 
 * @returns {unresolved}
 */
function getExtraDataChainedSelect() {
    return null;
}

/**
 * 
 * @returns {Boolean}
 */
function customAfterChainedSelect() {
    return true;
}

/**
 * 
 * @param {type} $sourceSelect
 * @param {type} $targetSelect
 * @param {type} data
 * @returns {Boolean}
 */
function customChainedSelect($sourceSelect, $targetSelect, data) {
    return true;
}

/**
 * 
 * @param {type} $select
 * @returns {undefined}
 */
function resetSelect($select) {

    $($select).find("option[value!='']").remove();

    $($select).attr("readonly", true);

    $($select).select2("val", null);
}

/**
 * 
 * @param deletableRow 
 * @returns {Boolean}
 */
function customPreDeleteLinkOnCallbackOk(deletableRow) {}

/**
 * 
 * @returns {undefined}
 */
function checkTabError() {

    $('.tab-pane').each(function () {

        var $id = $(this).prop('id');

        var $tagA = $("a[href='#" + $id + "']");

        var $li = $tagA.parents('li').last();

        if ($(this).has(".form-control.is-invalid").length) {

            $li.addClass('is-invalid');

            if (!$tagA.has('.fa-exclamation-triangle').length) {
                $tagA.append('<i class="fa fa-exclamation-triangle"></i>');
            }

        } else {

            $li.removeClass('is-invalid');

            $tagA.find('i').remove();
        }
    });

    if ($('ul li.is-invalid:first a').length) {
        $('ul li.is-invalid:first a').click();
    }
}

function initClienteSelect2(){
    $("select[id$='_cliente'].select2-hidden-accessible").select2({
        matcher: function (params, data) {
            // Si no hay término de búsqueda, mostrar toodo
            if ($.trim(params.term) === '') {
                return data;
            }

            // El texto del cliente (opción)
            var original = data.text.toLowerCase().replace(/,/g, '').replace(/\s+/g, ' ').trim();
            // El texto de búsqueda ingresado por el usuario
            var term = params.term.toLowerCase().replace(/,/g, '').replace(/\s+/g, ' ').trim();

            // Buscar si cada palabra del término está incluida en el texto
            var termWords = term.split(' ');
            var allMatch = termWords.every(function(word) {
                return original.includes(word);
            });

            // Si todas las palabras están incluidas, devolver el resultado
            if (allMatch) {
                return data;
            }

            // Si no hay coincidencias, no mostrar
            return null;
        }
    });
}