
jQuery(document).ready(function () {

    FormValidation.formValidation(
        $("form[name=reset_password_request_form]")[0],
        {
            fields: {
                'reset_password_request_form[email]': {
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
 });