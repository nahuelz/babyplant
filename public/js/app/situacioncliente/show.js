let fv;
$(document).ready(function () {
    initAgregarSaldo();
});

/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=situacion_cliente]")[0], {
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

function initAgregarSaldo() {
    $('.add-saldo').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/new",
        }).done(function (form) {
            showDialog({
                titulo: '<i class="fa fa-list-ul margin-right-10"></i> Ingresar Dinero',
                contenido: form,
                color: 'modal-razon-social',
                labelCancel: 'Cerrar',
                labelSuccess: 'Guardar',
                closeButton: true,
                callbackCancel: function () {
                    return true;
                },
                callbackSuccess: function () {
                    fv.revalidateField('requiredFields');
                    status = fv.validate().then((status) => {
                        if (status === "Valid") {
                            let modoPago = $('#situacion_cliente_modoPago').val();
                            let monto = $('#situacion_cliente_monto').val();
                            let descripcion = $('#situacion_cliente_descripcion').val();
                            $.ajax({
                                type: 'POST',
                                url: __HOMEPAGE_PATH__ + "situacion_cliente/movimiento/create",
                                dataType: 'json',
                                data: {
                                    modoPago: modoPago,
                                    monto: monto,
                                    descripcion: descripcion,
                                    id: __ID_CUENTA_CORRIENTE__,
                                    idUsuario: __ID_USUARIO__,
                                },
                                success: function (data) {
                                    window.location.reload();
                                    $('.cancel').click();
                                    return true;
                                },
                                error: function () {
                                    return true;
                                }
                            });
                        } else {
                            return false;
                        }
                    });
                    return false;
                }
            });
            let modal = $('.modal-dialog');
            modal.css('width', '80%');
            modal.addClass('modal-xl');
            modal.addClass('modal-fullscreen-xl-down');
            $('#situacion_cliente_modoPago').select2();
            initFormValidation();
        });
        e.stopPropagation();
        return true;
    })
}


