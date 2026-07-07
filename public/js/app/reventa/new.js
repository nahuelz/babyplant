var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initDevolucionSelect();
    initSubmitButton();
    initCalculoValorTotal();
    initValidacionCantidadBandejas();
});

function initDevolucionSelect() {
    $('#reventa_devolucion').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var datos = selectedOption.data('datos');

        if (datos) {
            $('#datos-devolucion').show();
            $('#info_cliente_original').val(datos.cliente);
            $('#info_producto').val(datos.producto);
            $('#info_disponibles').val(datos.disponible);
            $('#info_precio_original').val('$ ' + parseFloat(datos.precio).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'));

            // Proponer el precio original
            $('#reventa_precioUnitario').val(datos.precio);
        } else {
            $('#datos-devolucion').hide();
        }
    });

    // Mostrar datos si ya hay devolución seleccionada (edición)
    if ($('#reventa_devolucion').val()) {
        $('#reventa_devolucion').trigger('change');
    }
}

function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=reventa]")[0], {
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
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });
}

function initSubmitButton() {
    $("#reventa_submit").off('click').on('click', function (e) {
        e.preventDefault();

        var cantidadRevender = parseFloat($('#reventa_cantidadBandejas').val()) || 0;
        var disponibles = parseFloat($('#info_disponibles').val()) || 0;

        if (disponibles > 0 && cantidadRevender > disponibles) {
            Swal.fire({
                title: 'Error',
                text: "La cantidad a revender no puede superar las bandejas disponibles (" + disponibles + ").",
                icon: "warning"
            });
            return false;
        }

        if (cantidadRevender < 0.5) {
            Swal.fire({
                title: 'Error',
                text: "La cantidad de bandejas no puede ser menor a 0.5.",
                icon: "warning"
            });
            return false;
        }

        fv.validate().then(function (status) {
            if (status === "Valid") {
                $('form[name="reventa"]')[0].submit();
            }
        });

        e.stopPropagation();
    });
}

function initCalculoValorTotal() {
    function calcularValorTotal() {
        var cantidad = parseFloat($('#reventa_cantidadBandejas').val()) || 0;
        var precio = parseFloat($('#reventa_precioUnitario').val()) || 0;
        var total = cantidad * precio;
        $('#reventa_valor_total').val('$ ' + total.toFixed(2).replace('.', ','));
    }

    $('#reventa_cantidadBandejas').on('input', calcularValorTotal);
    $('#reventa_precioUnitario').on('input', calcularValorTotal);

    calcularValorTotal();
}

function initValidacionCantidadBandejas() {
    $('#reventa_cantidadBandejas').on('input', function() {
        var cantidadRevender = parseFloat($(this).val()) || 0;
        var disponibles = parseFloat($('#info_disponibles').val()) || 0;

        if (disponibles > 0 && cantidadRevender > disponibles) {
            $(this).addClass('is-invalid');
            if (!$(this).siblings('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">La cantidad a revender no puede superar las bandejas disponibles (' + disponibles + ')</div>');
            } else {
                $(this).siblings('.invalid-feedback').text('La cantidad a revender no puede superar las bandejas disponibles (' + disponibles + ')');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
}
