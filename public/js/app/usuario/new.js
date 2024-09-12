function initTipoUsuario(){
    if($('#usuario_tipoUsuario').val() === '1'){
        $("#usuario_grupos").val('3').select2();
        $('.datos-personales').show();
        $('.email-nombre-apelldio').show();
        $('.user-password').hide();
        $('.grupo').hide();
        initRazonSocial();
    }
    if($('#usuario_tipoUsuario').val() === '2'){
        $('.datos-personales').hide();
        $('.user-password').show();
        $('.email-nombre-apelldio').show();
        $('.grupo').show();
        $('#usuario_cuit').val('');
        $('#usuario_razonSocial').val('');
    }
}
function initTipoUsuarioHandler(){
    $('#usuario_tipoUsuario').on('change', function (){
        initTipoUsuario();
    });
}

function initRazonSocialHandler(){
    $('#usuario_tieneRazonSocial').on('change', function (){
        initRazonSocial();
    });
}

function initRazonSocial(){
    if ($('#usuario_tieneRazonSocial').val() === '1'){
        $('.razonSocial').show();
    }else{
        $('.razonSocial').hide();
    }
}

jQuery(document).ready(function () {
    if ($('#usuario_tipoUsuario').val() === '') {
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

    $("#usuario_cuit").inputmask("99-99999999-9");
    $("#usuario_razonSocial_cuit").inputmask("99-99999999-9");
});