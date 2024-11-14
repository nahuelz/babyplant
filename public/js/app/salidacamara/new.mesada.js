
var isEdit = $('[name=_method]').length > 0;

var collectionHoldermesada;

/**
 *
 */
jQuery(document).ready(function () {
    updateDeleteLinks($(".prototype-link-remove-mesada"));
    initmesadaForm();
    //initmesadaValidate();
});

/**
 *
 * @returns {undefined}
 */
function initmesadaForm() {
    initCollectionHoldermesada();
    addmesadaForm(collectionHoldermesada);
    $(document).on('click', '.prototype-link-add-mesada', function (e) {
        e.preventDefault();
        addmesadaForm(collectionHoldermesada);
        //initSelects();
        //initTipoContactoHandler();
        //initmesadaValidate();
    });
}

/**
 *
 * @param {type} $collectionHolder
 * @returns {addmesadaForm}
 */
function addmesadaForm($collectionHolder) {
    var prototype = $collectionHolder.data('prototype');
    var index = $collectionHolder.data('index');
    var form = prototype.replace(/__mesada__/g, index);

    $collectionHolder.data('index', index + 1);
    // Modificación para varios datos_contacto en la misma página
    $collectionHolder.parent().find('.prototype-link-add-mesada').closest('.row').before(form);

    var $deleteLink = $(".prototype-link-remove-mesada");

    updateDeleteLinks($deleteLink);
}

/**
 *
 * @returns {undefined}
 */
function initCollectionHoldermesada() {
    collectionHoldermesada = $('div.prototype-mesada');
    collectionHoldermesada.data('index', collectionHoldermesada.find(':input').length);
}

/**
 *
 * @returns {undefined}
 */
function initTipoContactoHandler() {
    $('select[id *= _mesadas][id $= _tipoContacto]').on('change', function () {
        initmesadaValidate();
    });
}

/**
 *
 * @returns {undefined}
 */
function initmesadaValidate() {
    $('.row-mesada').each(function () {
        var codigoInterno = $(this).find('select[id *= _mesadas][id $= _tipoContacto] option:selected').data('codigo-interno');

        if (typeof codigoInterno !== "undefined") {

            // Si es "Email"
            if (codigoInterno === 4) {
                $(this).find('input[id *= _mesadas][id $= _descripcion]').prop('type', 'email');
            } else {
                $(this).find('input[id *= _mesadas][id $= _descripcion]').prop('type', 'text');
            }
        }
    });
}

/**
 *
 * @param {type} prefix
 * @returns {Boolean}
 */
function validateAllFilled(prefix) {
    var mesadasAllFilled = true;
    $(prefix + ' .row-mesada input[id$=descripcion], ' + prefix + ' .row-mesada select').each(function () {
        mesadasAllFilled &= $(this).val() != '';
    });
    return mesadasAllFilled;
}