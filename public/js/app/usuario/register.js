var fvCliente;
function initTipoUsuario(){
    if($('#registration_form_tipoUsuario').val() === '1'){
        $("#registration_form_grupos").val('3').select2();
        $('.datos-personales').show();
        $('.email-nombre-apelldio').show();
        $('.usuario-password').hide();
        $('.grupo').hide();
        $('.razon-social').show();
        initRazonSocial();

        $('#registration_form_tieneRazonSocial').attr('required', 'required');
        $('label[for="registration_form_tieneRazonSocial"]').addClass('required');
        $('#registration_form_username').attr('required', false);
        $('label[for="registration_form_username"]').removeClass('required');
        $('#registration_form_plainPassword').attr('required', false);
        $('label[for="registration_form_plainPassword"]').removeClass('required');
        $('#registration_form_email').attr('required', false);
        $('label[for="registration_form_email"]').removeClass('required');
    }
    if($('#registration_form_tipoUsuario').val() === '2'){
        $("#registration_form_grupos").val('').select2();
        $('.datos-personales').hide();
        $('.usuario-password').show();
        $('.email-nombre-apelldio').show();
        $('.grupo').show();
        $('#registration_form_cuit').val('');
        $('#registration_form_razonSocial').val('');
        $('.razon-social').hide();
        $('.razonSocial').hide();
        $('#registration_form_tieneRazonSocial').val('').select2().change();


        $('#registration_form_tieneRazonSocial').attr('required', false);
        $('label[for="registration_form_tieneRazonSocial"]').removeClass('required');
        $('#registration_form_username').attr('required', 'required');
        $('label[for="registration_form_username"]').addClass('required');
        $('#registration_form_plainPassword').attr('required', 'required');
        $('label[for="registration_form_plainPassword"]').addClass('required');
        $('#registration_form_email').attr('required', 'required');
        $('label[for="registration_form_email"]').addClass('required');
    }
}
function initTipoUsuarioHandler(){
    $('#registration_form_tipoUsuario').on('change', function (){
        initTipoUsuario();
    });
}

function initRazonSocialHandler(){
    $('#registration_form_tieneRazonSocial').on('change', function (){
        initRazonSocial();
    });
}

function initRazonSocial(){
    if ($('#registration_form_tieneRazonSocial').val() === '1'){
        $('.razonSocial').show();
    }else{
        $('.razonSocial').hide();
    }
}

function initFormValidationCliente() {
    fvCliente = FormValidation.formValidation($("form[name=registration_form]")[0], {
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
//          defaultSubmit: new FormValidation.plugins.DefaultSubmit()
        }

    });
}

function initAgregarCliente() {

    $("#registration_form_submit").off('click').on('click', function (e) {
        e.preventDefault();

        initFormValidationCliente();

        fvCliente.revalidateField('requiredFields');

        fvCliente.validate().then((status) => {

            if (status === "Valid") {
                $('form[name="registration_form"]').submit();
                return false;
            }
        });

        e.stopPropagation();
    });
}

jQuery(document).ready(function () {
    if ($('#registration_form_tipoUsuario').val() === '') {
        $('.usuario-password').hide();
        $('.email-nombre-apelldio').hide();
        $('.datos-personales').hide();
        $('.grupo').hide();
        $('.razon-social').hide();
        $('.razonSocial').hide();
    }
    initTipoUsuarioHandler();
    initTipoUsuario();
    initRazonSocialHandler();
    initAgregarCliente();
    $('.link-agregar-razonsocial').css('margin-top', '10%');
    //$("#registration_form_cuit").inputmask("99-99999999-9");
    //$("#registration_form_razonSocial_cuit").inputmask("99-99999999-9");
});