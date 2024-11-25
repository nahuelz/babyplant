function initTipoUsuario(){
    if($('#registration_form_tipoUsuario').val() === '1'){
        $("#registration_form_grupos").val('3').select2();
        $('.datos-personales').show();
        $('.email-nombre-apelldio').show();
        $('.user-password').hide();
        $('.grupo').hide();
        initRazonSocial();
    }
    if($('#registration_form_tipoUsuario').val() === '2'){
        $('.datos-personales').hide();
        $('.user-password').show();
        $('.email-nombre-apelldio').show();
        $('.grupo').show();
        $('#registration_form_cuit').val('');
        $('#registration_form_razonSocial').val('');
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

jQuery(document).ready(function () {
    if ($('#registration_form_tipoUsuario').val() === '') {
        $('.user-password').hide();
        $('.email-nombre-apelldio').hide();
        $('.datos-personales').hide();
        $('.grupo').hide();
    }
    $('.razonSocial').hide();
    initTipoUsuarioHandler();
    initTipoUsuario();
    initRazonSocialHandler();

    FormValidation.formValidation(
        $("form[name=registration_form]")[0], {
            fields: {
                'registration_form[email]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[username]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[roles]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[plainPassword]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[nombre]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[apellido]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'registration_form[grupos][]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap(),
                submitButton: new FormValidation.plugins.SubmitButton(),
                defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            }
        }
    );

    $('.link-agregar-razonsocial').css('margin-top', '10%');
    //$("#registration_form_cuit").inputmask("99-99999999-9");
    //$("#registration_form_razonSocial_cuit").inputmask("99-99999999-9");
});