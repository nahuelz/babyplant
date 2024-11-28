
jQuery(document).ready(function () {
    $('#tipo_mesada_nombre').attr('readonly', true);
    FormValidation.formValidation(
        $("form[name=tipo_mesada]")[0],
        {
            fields: {
                'tipo_mesada[nombre]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'tipo_mesada[estandar]': {
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