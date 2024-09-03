
jQuery(document).ready(function () {

    var fv = FormValidation.formValidation(
            $("form[name=localidad]")[0],
            {
                fields: {
                    'localidad[nombre]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            }
                        }
                    },
                    'localidad[partido]': {
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
        fv.revalidateField('localidad[partido]');
    });
});