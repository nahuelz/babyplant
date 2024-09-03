const strongPassword = function() {
    return {
        validate: function(input) {
            const password = input.value;
            var respuesta = password.match(/^.*(?=.{8,})(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*_\-+=`|\\\(){}\[\]:;\"'<>,.?\/])(?!.*(.)\1{2}).*$/);            
            if (respuesta === null) {
                return {
                    valid: false,
                };
            }
            return {
                valid: true,
            };
        },
    };
};

const easyPassword = function() {
    return {
        validate: function(input) {
            const password = input.value;
            var respuesta = password.match(/^.*(?=.{4,}).*$/);
            if (respuesta === null) {
                return {
                    valid: false,
                };
            }
            return {
                valid: true,
            };
        },
    };
};

// Register the validator
FormValidation.validators.checkPassword = easyPassword;

jQuery(document).ready(function () {

    if ($('input[name="change_password_form[noCurrent]"]').length){
        $('#change_password_form_currentPassword').closest('.row').remove();
    } else {
        $('#change_password_form_currentPassword').closest('.row').show();
    }

    if ($('#change_password_form_currentPassword').length){
        FormValidation.formValidation(
            $("form[name=change_password_form]")[0],
            {
                fields: {
                    'change_password_form[currentPassword]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            }
                        }
                    },
                    'change_password_form[plainPassword][first]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            },
                            checkPassword: {
                                message: 'Debe tener 4 caracteres como mínimo.'
                            }
                        }
                    },
                    'change_password_form[plainPassword][second]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            },
                            identical: {
                                compare: function() {
                                    return $("form[name=change_password_form]")[0].querySelector('[name="change_password_form[plainPassword][first]"]').value;
                                },
                                message: 'Las dos contraseñas deben ser iguales'
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
    } else {
        FormValidation.formValidation(
            $("form[name=change_password_form]")[0],
            {
                fields: {
                    'change_password_form[plainPassword][first]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            },
                            checkPassword: {
                                message: 'Debe tener 4 caracteres como mínimo.'
                            }
                        }
                    },
                    'change_password_form[plainPassword][second]': {
                        validators: {
                            notEmpty: {
                                message: 'Este campo es requerido'
                            },
                            identical: {
                                compare: function() {
                                    return $("form[name=change_password_form]")[0].querySelector('[name="change_password_form[plainPassword][first]"]').value;
                                },
                                message: 'Las dos contraseñas deben ser iguales'
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
    }
 });