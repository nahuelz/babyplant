
jQuery(document).ready(function () {
    if (__ID_PRODUCTO__ != '0') {
        $('#tipo_variedad_tipoProducto').val(__ID_PRODUCTO__).select().change();
    }
    initProductos();
    FormValidation.formValidation(
        $("form[name=tipo_variedad]")[0],
        {
            fields: {
                'tipo_variedad[nombre]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'tipo_variedad[estandar]': {
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
});

function initProductos() {
    initChainedSelect($('#tipo_variedad_tipoProducto'), $('#tipo_variedad_tipoSubProducto'), __HOMEPAGE_PATH__ + 'tipo/sub/producto/lista/subproductos', true);
}