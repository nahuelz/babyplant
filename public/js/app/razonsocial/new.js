let fvRazonSocial;
let status;

jQuery(document).ready(function () {
    initRazonSocialHandler();
    initAgregarRazonSocialModal();
    initAgregarRazonSocialHandler();
});

function initAgregarRazonSocialHandler() {
    $('.link-agregar-razonsocial').on('click', function(){
        $("#razon_social_cuit").inputmask("99-99999999-9");

    });
}

/**
 *
 * @returns {undefined}
 */
function initFormValidationRazonSocial() {
    fvRazonSocial = FormValidation.formValidation($("form[name=razon_social]")[0], {
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
        }
    });
}

/*
function initAgregarRazonSocialModal2() {

    $("#razon_social_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fvRazonSocial.revalidateField('requiredFields');

        fvRazonSocial.validate().then((status) => {

            if (status === "Valid") {
                $.ajax({
                    url: __HOMEPAGE_PATH__ + "razonSocial/createAjax/",
                    type: 'POST',
                    data:  $('form[name="razon_social"]').serialize()
                }).done(function (response) {
                    var decodedResponse = jQuery.parseJSON(response);
                    if (decodedResponse.statusText === 'OK') {
                        $('#razonSocialModal').modal('hide');
                        var textOption = $('#razon_social_razonSocial').val();
                        var newOption = new Option('TEST NEW', '9911', true, true);
                        $("#registration_form_razonSocial").append(newOption).trigger('change');
                        showFlashMessage("success", decodedResponse.message);
                    } //
                    else {
                        showFlashMessage("danger", decodedResponse.message);
                    }
                });
                return false;
            }
        });

        e.stopPropagation();
    });
}*/

function initAgregarRazonSocialModal() {
    $('.link-agregar-razonsocial').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "razonSocial/newAjax/",
        }).done(function (form) {
            $('.modalCliente').css('opacity', 0);
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Nueva Razon Social',
                contenido: form,
                color: 'modal-razon-social',
                labelCancel: 'Cerrar',
                labelSuccess: 'Guardar',
                closeButton: true,
                callbackCancel: function () {
                    $('.modalCliente').css('opacity', 1);
                    return true;
                },
                callbackSuccess: function () {
                    fvRazonSocial.revalidateField('requiredFields');
                    status = fvRazonSocial.validate().then((status) => {
                        if (status === "Valid") {
                            let razonSocial = $('#razon_social_razonSocial').val();
                            let cuit = $('#razon_social_cuit').val();
                            $.ajax({
                                type: 'post',
                                dataType: 'json',
                                data: {
                                    razonSocial: razonSocial,
                                    cuit: cuit
                                },
                                url: __HOMEPAGE_PATH__ + "razonSocial/createAjax/",
                                success: function (data) {
                                    if (!jQuery.isEmptyObject(data)) {
                                        let nombreInput = data.nombre + ' (' + data.cuit + ')';
                                        let newOption = new Option(nombreInput, data.id, true, true);
                                        $("#registration_form_razonSocial").append(newOption).trigger('change');
                                        $('.modalCliente').css('opacity', 1);
                                        $('.bootbox-close-button').click();
                                    }
                                },
                                error: function () {
                                    alert('ah ocurrido un error.');
                                }
                            });
                        }
                    });
                    return false;
                }
            });
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            initFormValidationRazonSocial();
        });
        e.stopPropagation();
    });
}