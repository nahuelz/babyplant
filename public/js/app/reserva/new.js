var fv;
var total = 0;

jQuery(document).ready(function () {
    initFormValidation();
    initProductos();
    initBaseSubmitButton();
    initPreValidation();

    $('#reserva_submit').html('Reservar');

});

function customAfterChainedSelect(){
    $('#reserva_pedidoProducto').select2({
        templateResult: function(data) {
            if (!data.id) {
                return data.text;
            }


            var palabraResaltar = "DISPONIBLES: "; // Palabras a resaltar
            var regex = new RegExp('(' + palabraResaltar + '.{0,2})', 'gi'); // Encuentra la palabra + 5 caracteres
            var highlightedText = data.text.replace(regex, '<span class="highlight">$1</span>');

            return $('<span>').html(highlightedText);
        }
    });
}
/**
 *
 * @returns {undefined}
 */
function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=reserva]")[0], {
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

function initProductos() {
    initChainedSelect($('#reserva_clienteOrigen'), $('#reserva_pedidoProducto'), __HOMEPAGE_PATH__ + 'reserva/lista/productos', preserve_values);
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#reserva_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fv.revalidateField('requiredFields');

        if (parseInt($('#reserva_cantidadBandejas').val()) < 1){
            Swal.fire({
                title: 'Error',
                text: "La cantidad de bandejas no puede ser menor a 1.",
                icon: "warning"
            });
            return false;
        }

        fv.validate().then((status) => {
            if (status === "Valid") {
                $.post({
                    url: __HOMEPAGE_PATH__ + "reserva/confirmar-reserva",
                    type: 'post',
                    dataType: 'json',
                    data: $('form[name="reserva"]').serialize()
                }).done(function (result) {
                    if (result.error) {
                        Swal.fire({
                            title: result.tipo,
                            text: "La cantidad de bandejas a reservar no puede superar a la cantidad de bandejas disponibles.",
                            icon: "warning"
                        });

                        return false;
                    } else {
                        showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> ENTREGA',
                            contenido: result.html,
                            color: 'btn-light-success ',
                            labelCancel: 'Cancelar',
                            labelSuccess: 'Confirmar Reserva',
                            closeButton: true,
                            callbackCancel: function () {

                            },
                            callbackSuccess: function () {
                                $('form[name="reserva"]').submit();
                            }
                        });
                        $('.bs-popover-top').hide();
                        $('.modal-dialog').css('width', '80%');
                        $('.modal-dialog').addClass('modal-xl');
                        $('.modal-dialog').addClass('modal-fullscreen-xl-down');
                    }
                });
            }
        });

        e.stopPropagation();
    });
}