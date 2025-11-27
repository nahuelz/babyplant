var fv;
var total = 0;

jQuery(document).ready(function () {
    initFormValidation();
    initProductos();
    initBaseSubmitButton();
    initPreValidation();
    $('#reserva_cliente').select2();
    $('#reserva_submit').html('Reservar');
    initClienteSelect2();
    initClienteReservaHandler();

});

function initClienteReservaHandler() {
    $('#reserva_origen_cliente').on('change', function(){
        $('#reserva_cliente').val($(this).val()).select2();
    })
}

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
    initChainedSelect($('#reserva_origen_cliente'), $('#reserva_pedidoProducto'), __HOMEPAGE_PATH__ + 'reserva/lista/productos', preserve_values);
}

/**
 *
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    $("#reserva_submit").off('click').on('click', function (e) {
        e.preventDefault();

        fv.revalidateField('requiredFields');

        if (parseFloat($('#reserva_cantidadBandejas').val()) < 0.5){
            Swal.fire({
                title: 'Error',
                text: "La cantidad de bandejas no puede ser menor a 0.5.",
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
                    if (result.error === true) {
                        Swal.fire({
                            title: result.html,
                            text: "La cantidad de bandejas a reservar no puede superar a la cantidad de bandejas disponibles.",
                            icon: "error"
                        });

                        return false;
                    } else {
                        let reservaDialog = showDialog({
                            titulo: '<i class="fa fa-list-ul margin-right-10"></i> RESERVA',
                            contenido: result.html,
                            color: 'btn-light-success ',
                            labelCancel: 'Cancelar',
                            labelSuccess: 'Confirmar Reserva',
                            closeButton: true,
                            callbackCancel: function () {

                            },
                            callbackSuccess: function () {
                                $.post({
                                    url: __HOMEPAGE_PATH__ + "reserva/insertar",
                                    type: 'post',
                                    dataType: 'json',
                                    data: $('form[name="reserva"]').serialize()
                                }).done(function (result) {
                                    if (result.statusText !== 'OK') {
                                        Swal.fire({
                                            title: result.statusCode,
                                            text: result.statusText,
                                            icon: "warning"
                                        });

                                        return false;
                                    } else {
                                        document.activeElement.blur(); // quitar foco
                                        setTimeout(() => {
                                            reservaDialog.modal('hide'); // cerrar bootbox
                                        }, 50);

                                        // ✅ Mostrar Swal después
                                        setTimeout(() => {
                                            Swal.fire({
                                                width: '800px',
                                                title: '<strong>RESERVA AGREGADA!</strong>',
                                                color: "#716add",
                                                allowOutsideClick: false,
                                                backdrop: false,
                                                confirmButtonText: 'Agregar Nueva Reserva',
                                                html: '<div class="d-flex flex-row justify-content-center align-items-center w-100">' +
                                                    '<a href="/reserva/imprimir-reserva/' + result.message + '" class="swal2-confirm swal2-styled" title="Imprimir reserva">' +
                                                    '<i class="fas fa-file-pdf text-white"></i> Imprimir A4</a>' +
                                                    '<a href="/reserva/imprimir-reserva-ticket/' + result.message + '" class="swal2-confirm swal2-styled" title="Imprimir reserva">' +
                                                    '<i class="fas fa-receipt text-white"></i> Imprimir TICKET</a>' +
                                                    '<a href="/reserva/" class="swal2-confirm swal2-styled" title="Ver Reservas">' +
                                                    '<i class="fas fa-search text-white"></i> Ver Reservas</a>' +
                                                    '</div>',
                                                icon: "success"
                                            }).then(() => {
                                                window.location.reload();
                                            });
                                        }, 300); // esperamos un poco para evitar conflictos
                                    }
                                });
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