$.validator.setDefaults({
    errorElement: 'span',
    errorClass: 'help-block',
    focusInvalid: true,
    ignore: "input[name*='_currency']",
    errorPlacement: function (error, element) {
        // render error placement for each input type
        if ($(element).parents('div.input-group').length > 0) {
            // Si el input está dentro de un input-group
//            error.insertAfter(element.parent().parent());
            element.parent().parent().parent().append(error);
        } else {
            error.insertAfter(element);
        }

        var icon = $(element).parent('.input-icon').children('i');
        //icon.removeClass('fa-check').addClass("fa-warning");  
        icon.attr("data-original-title", error.text()).tooltip({'container': 'body'});
    },
    highlight: function (element) { // hightlight error inputs
        $(element).closest('.form-group').removeClass('has-success').addClass('has-error'); // set error class to the control group 
        var icon = $(element).parent('.input-icon').children('i');
        icon.removeClass('fa-check').addClass("fa-warning");

    },
    unhighlight: function (element) { // revert the change done by hightlight        
        $(element).closest('.form-group').removeClass('has-error');//.addClass('has-success');
//
//        var icon = $(element).parent('.input-icon').children('i');
//        icon.removeClass('fa-warning').addClass('fa-check');

    },
    success: function (label, element) {
//        var icon = $(element).parent('.input-icon').children('i');
//        $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
//        icon.removeClass("fa-warning").addClass("fa-check");
    }
});

$.validator.addMethod(
        "cuit",
        function (value) {

            if (value.length == 0) {
                return true;
            }

            if (!/^\d{2}-\d{8}-\d{1}$/.test(value)) {
                return false;
            }

            var aMult = '5432765432';
            var aMult = aMult.split('');
            var sCUIT = value.replace(/-/g, "").replace(/_/g, "").replace(/ /g, "");

            if (sCUIT && sCUIT != 0 && sCUIT.length == 11) {

                var aCUIT = sCUIT.split('');
                var iResult = 0;

                for (i = 0; i <= 9; i++) {
                    iResult += aCUIT[i] * aMult[i];
                }

                iResult = (iResult % 11);
                iResult = 11 - iResult;

                if (iResult == 11) {
                    iResult = 0;
                }

                if (iResult == 10) {
                    iResult = 9;
                }

                if (iResult == aCUIT[10]) {
                    return true;
                }
            }
            return false;
        },
        "Formato de CUIT incorrecto"
        );