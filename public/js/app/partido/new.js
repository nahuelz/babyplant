
jQuery(document).ready(function () {

    var fv = FormValidation.formValidation(
            $("form[name=partido]")[0],
            {
                fields: {
                    'partido[nombre]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            }
                        }
                    },
                    'partido[provincia]': {
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
                    defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                }
            }
    );

    $('select').on('change.select2', function () {
        fv.revalidateField('partido[provincia]');
    });
});