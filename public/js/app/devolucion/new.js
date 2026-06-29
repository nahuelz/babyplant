var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initProductos();
    initClienteSelect2();
    initSubmitButton();
    initCalculoValorTotal();
});

function initProductos() {
    initChainedSelect($('#devolucion_cliente'), $('#devolucion_pedidoProducto'), __HOMEPAGE_PATH__ + 'devolucion/lista/productos', preserve_values);
}

function initFormValidation() {
    fv = FormValidation.formValidation($("form[name=devolucion]")[0], {
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
    $("#devolucion_submit").off('click').on('click', function (e) {
        e.preventDefault();

        if (parseFloat($('#devolucion_cantidadBandejas').val()) < 0.5) {
            Swal.fire({
                title: 'Error',
                text: "La cantidad de bandejas no puede ser menor a 0.5.",
                icon: "warning"
            });
            return false;
        }

        fv.validate().then(function (status) {
            if (status === "Valid") {
                $('form[name="devolucion"]')[0].submit();
            }
        });

        e.stopPropagation();
    });
}

function initCalculoValorTotal() {
    function calcularValorTotal() {
        var cantidad = parseFloat($('#devolucion_cantidadBandejas').val()) || 0;
        var precio = parseFloat($('#devolucion_precioUnitario').val()) || 0;
        var total = cantidad * precio;
        $('#devolucion_valor_total').val('$ ' + total.toFixed(2).replace('.', ','));
    }

    $('#devolucion_cantidadBandejas').on('input', calcularValorTotal);
    $('#devolucion_precioUnitario').on('input', calcularValorTotal);

    // Calcular al cargar si hay valores preexistentes (edición)
    calcularValorTotal();
}
