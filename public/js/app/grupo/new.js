
jQuery(document).ready(function () {

   FormValidation.formValidation(
       $("form[name=grupo]")[0],
       {
           fields: {
                                                                                                                      'grupo[nombre]': {
                               validators: {
                                   notEmpty: {
                                       message: 'Este campo es requerido'
                                   }
                               }
                           },
                                                                                                                              'grupo[roles]': {
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