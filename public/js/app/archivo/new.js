var fv;

const requiredField = {
    validators: {
        notEmpty: {
            message: "Este campo es requerido",
        }
    }
};

$(document).ready(function () {

    initFormValidation();
    initCustomValidations();

    $(".disabled").css("background-color", "#F3F6F9");

    $("#archivo_fechaProceso").datepicker({
        dateFormat: 'yy/mm/dd'
    }).datepicker("setDate", new Date());

    $("#archivo_primerVencimiento").datepicker({
        dateFormat: 'yy/mm/dd'
    });

    $("#archivo_segundoVencimiento").datepicker({
        dateFormat: 'yy/mm/dd'
    });

    $("#archivo_cuota").on('change', function () {
        $.ajax({
            type: 'post',
            dataType: 'json',
            data: {
                id: $('#archivo_cuota').val()
            },
            url: __HOMEPAGE_PATH__ + "archivo/getCuota/",
            success: function (data) {
                if (!jQuery.isEmptyObject(data)) {
                    loadCuota(data.cuota);
                    $("#archivo_cuota").selectpicker('refresh');
                }
            }
        });
    });
});

function loadCuota(cuota) {

    if (cuota.vencimiento_1 != null)
    {
        var v1 = new Date(cuota.vencimiento_1);
        var v1f = (('0' + (v1.getDate() + 1)).slice(-2) + '/' + ('0' + (v1.getMonth() + 1)).slice(-2) + '/' + v1.getFullYear());
        $("#archivo_primerVencimiento").datepicker("setDate", v1f);
    } else
    {
        $("#archivo_primerVencimiento").datepicker("setDate", '');
    }
    if (cuota.vencimiento_2 != null)
    {
        var v2 = new Date(cuota.vencimiento_2);
        var v2f = (('0' + (v2.getDate() + 1)).slice(-2) + '/' + ('0' + (v2.getMonth() + 1)).slice(-2) + '/' + v2.getFullYear());
        $("#archivo_segundoVencimiento").datepicker("setDate", v2f);
    } else
    {
        $("#archivo_segundoVencimiento").datepicker("setDate", '');
    }
    //comun
    $("#archivo_montoUno").val(Number(cuota.monto).toFixed(2));
    $("#archivo_montoDos").val((Number(cuota.monto) + Number(cuota.interes)).toFixed(2));
    $("#archivo_mensajeTicket").val("CPCIBA CUOTA " + cuota.cuota + " MATRICULA " + cuota.anio);//se arma con id deuda y cuota anual

}

/**
 *
 * @returns {undefined}
 */
function initFormValidation() {

    fv = FormValidation.formValidation($("form[name=archivo]")[0], {
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

function initCustomValidations() {
    $(".submit-button").click(function (e) {
        e.preventDefault();

        fv.revalidateField('requiredFields');

        fv.validate().then((status) => {
            if (status === "Valid") {
                $('form[name="archivo"]').submit();
            }
            checkTabError();
        });
    });
}