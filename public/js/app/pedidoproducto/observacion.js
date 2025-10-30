function initObservacionInput(){
    $('.observacion').click(function(){
        $('.observacion-input').toggle();
    });

    $('#guardar-observacion').click(function() {
        guardarObservacion('observacion');
    });
}

function initObservacionCamaraInput(){
    $('.observacion-camara').click(function(){
        $('.observacion-camara-input').toggle();
    });

    $('#guardar-observacion-camara').click(function() {
        guardarObservacion('observacion_camara');
    });
}

function guardarObservacion(tipo) {
    const idPedidoProducto = $('#observacionCamara').data('pedido-id');

    if (!idPedidoProducto) {
        toastr.error('Error: No se pudo identificar el pedido');
        return;
    }

    const valor = tipo === 'observacion' ? $('#observacion').val() : $('#observacionCamara').val();
    const $button = tipo === 'observacion' ? $('#guardar-observacion') : $('#guardar-observacion-camara');
    const originalButtonText = $button.html();

    $.ajax({
        url: __HOMEPAGE_PATH__ + "pedido/producto/"+idPedidoProducto+"/actualizar-observacion",
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            tipo: tipo,
            valor: valor
        }),
        success: function(response) {
            if (response.success) {
                toastr.success('Observación guardada correctamente');
                if (tipo === 'observacion') {
                    $('.observacion-text').html(valor);
                }else{
                    $('.observacion-camara-text').html(valor);
                }
            } else {
                toastr.error('Error al guardar la observación');
                $button.prop('disabled', false).html(originalButtonText);
            }
        },
        error: function() {
            toastr.error('Error al guardar la observación');
            $button.prop('disabled', false).html(originalButtonText);
        }
    });
}