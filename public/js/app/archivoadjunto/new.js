
var isEdit = $('[name=_method]').length > 0;

var collectionHolderArchivoAdjunto;

/**
 * 
 * @returns {undefined}
 */
function initCollectionHolderArchivoAdjunto() {

    collectionHolderArchivoAdjunto = $('div.prototype-archivo-adjunto');

    collectionHolderArchivoAdjunto.data('index', collectionHolderArchivoAdjunto.find(':input').length);
}

/**
 * 
 * @returns {undefined}
 */
function initArchivoAdjuntoForm() {

    initCollectionHolderArchivoAdjunto();

    $(document).off('click', '.add-link-archivo-adjunto').on('click', '.add-link-archivo-adjunto', function (e) {
        e.preventDefault();
        addArchivoAdjuntoForm(collectionHolderArchivoAdjunto);
        initFileInputStyle();
    });

    if(isEdit){
        initFileInputStyle();
    }
}

/**
 * 
 * @param {type} $collectionHolder
 * @returns {addArchivoAdjuntoForm}
 */
function addArchivoAdjuntoForm($collectionHolder) {

    var prototype = $collectionHolder.data('prototype');

    var index = $collectionHolder.data('index');

    var form = prototype.replace(/__archivo_adjunto__/g, index);

    $collectionHolder.data('index', index + 1);

    collectionHolderArchivoAdjunto.append(form);

    var $deleteLink = $(".remove-link-archivo-adjunto");

    updateDeleteLinks($deleteLink);
}
