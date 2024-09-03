$(document).ready(function () {

    $("#btn-info-retroactivo").click(function () {

        let fechaCobro = $("#form_fechaCobroPago").val();

        if (fechaCobro) {
            $.ajax({
                url: __HOMEPAGE_PATH__ + "utilidad/info_retroactivo",
                type: 'POST',
                dataType: 'json',
                data: {
                    fecha_cobro: fechaCobro
                },
                success: function (response) {

                    var retroactivo = response.retroactivo;

                    $('#form_cantidadCuotas').val(retroactivo.cuotas.length);
                    $('#form_monto').val(retroactivo.monto);
                    $('#form_cuotasIncluidas').val(retroactivo.cuotas_txt);
                }
            });
        } else {
            Swal.fire('Atención', 'Debe indicar la fecha de cobro.', 'alert');
            return;
        }
    });
});
