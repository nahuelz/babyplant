
jQuery(document).ready(function () {

    FormValidation.formValidation(
        $("form[name=usuario]")[0],
        {
            fields: {
                'usuario[email]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'usuario[roles]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'usuario[password]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'usuario[grupos][]': {
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

   $("#usuario_cuit").inputmask("99-99999999-9");
});