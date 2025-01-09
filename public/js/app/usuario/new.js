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

    $("#usuario_cuit").inputmask("99-99999999-9");
    $("#usuario_razonSocial_cuit").inputmask("99-99999999-9");
});