var fv;

jQuery(document).ready(function () {
    initFormValidation();
    initProductos();
    initClienteSelect2();
    initSubmitButton();
    initCalculoValorTotal();
    initValidacionCantidadBandejas();
});

function initProductos() {
    initChainedSelect($('#devolucion_cliente'), $('#devolucion_entregaProducto'), __HOMEPAGE_PATH__ + 'devolucion/lista/productos', preserve_values);
    
    // Agregar evento change al campo entregaProducto
    $('#devolucion_entregaProducto').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var datos = selectedOption.data('datos');
        
        if (datos) {
            $('#datos-producto').show();
            $('#info_producto').val(datos.denominacion);
            $('#info_numero_orden').val(datos.numeroOrden);
            $('#info_bandejas').val(datos.bandejasEntregadas);
            $('#info_mesada').val(datos.mesada);
            $('#info_pedido').val(datos.numeroPedido);
            $('#info_precio').val('$ ' + parseFloat(datos.precioUnitario).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
            
            // También actualizar el campo oculto de precio unitario
            $('#devolucion_precioUnitario').val(datos.precioUnitario);
        } else {
            $('#datos-producto').hide();
        }
    });
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

        var cantidadDevolver = parseFloat($('#devolucion_cantidadBandejas').val()) || 0;
        var bandejasEntregadas = parseFloat($('#info_bandejas').val()) || 0;
        
        if (bandejasEntregadas > 0 && cantidadDevolver > bandejasEntregadas) {
            Swal.fire({
                title: 'Error',
                text: "La cantidad a devolver no puede superar las bandejas entregadas (" + bandejasEntregadas + ").",
                icon: "warning"
            });
            return false;
        }

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

function initValidacionCantidadBandejas() {
    $('#devolucion_cantidadBandejas').on('input', function() {
        var cantidadDevolver = parseFloat($(this).val()) || 0;
        var bandejasEntregadas = parseFloat($('#info_bandejas').val()) || 0;
        
        if (bandejasEntregadas > 0 && cantidadDevolver > bandejasEntregadas) {
            $(this).addClass('is-invalid');
            // Mostrar mensaje de error si no existe
            if (!$(this).siblings('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">La cantidad a devolver no puede superar las bandejas entregadas (' + bandejasEntregadas + ')</div>');
            } else {
                $(this).siblings('.invalid-feedback').text('La cantidad a devolver no puede superar las bandejas entregadas (' + bandejasEntregadas + ')');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
}
