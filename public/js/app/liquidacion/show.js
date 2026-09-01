jQuery(document).ready(function () {

    function parseMonto(valor) {
        return parseFloat((valor || '').replace(/\./g, '').replace(',', '.')) || 0;
    }

    function formatearMonto(valor) {
        return Number(valor).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getSueldoBruto() {
        var $input = $('#liquidacion_sueldoBruto');
        return $input.length ? parseMonto($input.val()) : 0;
    }

    function getDeducciones() {
        var $input = $('#liquidacion_deducciones');
        return $input.length ? parseMonto($input.val()) : 0;
    }

    function getTotalConceptosSemanas() {
        var $input = $('#liquidacion_totalConceptosSemanas');
        return $input.length ? parseMonto($input.val()) : 0;
    }

    function calcularImporteConcepto() {
        var $form = $('.row-liquidacion-concepto-form');
        var cantidad = parseMonto($form.find('#liquidacion_concepto_cantidad').val());
        var valor = parseMonto($form.find('#liquidacion_concepto_valorUnitario').val());
        var importe = cantidad * valor;
        $form.find('#liquidacion_concepto_importe').val(importe.toFixed(2).replace('.', ','));
    }

    function recalcularTotales() {
        var sueldoBruto = getSueldoBruto();
        var deducciones = getDeducciones();
        var neto = sueldoBruto - deducciones;

        var totalConceptos = 0;
        $('.tr-liquidacion-concepto').each(function () {
            var tipo = $(this).data('tipo');
            var signo = tipo === 'DESCUENTO' ? -1 : 1;
            var importe = parseFloat($(this).data('total')) || 0;
            totalConceptos += importe * signo;
        });

        var total = neto + getTotalConceptosSemanas() + totalConceptos;

        $('#resumen-neto').text('$' + formatearMonto(neto));
        $('#resumen-total').text('$' + formatearMonto(total));
    }

    function clearConceptoForm() {
        var $form = $('.row-liquidacion-concepto-form');
        $form.find('#liquidacion_concepto_tipoConceptoLiquidacion').val('').trigger('change');
        $form.find('#liquidacion_concepto_descripcion').val('');
        $form.find('#liquidacion_concepto_cantidad').val('');
        $form.find('#liquidacion_concepto_valorUnitario').val('');
        $form.find('#liquidacion_concepto_importe').val('');
    }

    function agregarConcepto(e) {
        e.preventDefault();

        var $form = $('.row-liquidacion-concepto-form');
        var tipoConceptoSelect = $form.find('#liquidacion_concepto_tipoConceptoLiquidacion');
        var tipoConceptoId = tipoConceptoSelect.val();
        var tipoConceptoText = tipoConceptoSelect.find('option:selected').text();
        var tipo = tipoConceptoSelect.find('option:selected').data('tipo');
        var codigoInterno = tipoConceptoSelect.find('option:selected').data('codigo-interno');
        var descripcion = $form.find('#liquidacion_concepto_descripcion').val();
        var cantidadRaw = $form.find('#liquidacion_concepto_cantidad').val();
        var valorRaw = $form.find('#liquidacion_concepto_valorUnitario').val();

        if (!tipoConceptoId || parseMonto(cantidadRaw) <= 0 || parseMonto(valorRaw) <= 0) {
            Swal.fire({
                title: 'Debe completar el tipo de concepto, cantidad y valor unitario (mayores a 0).',
                icon: 'warning'
            });
            return;
        }

        var cantidad = parseMonto(cantidadRaw);
        var valor = parseMonto(valorRaw);
        var importe = cantidad * valor;
        var $tbody = $('.tbody-liquidacion-concepto');
        var index = parseInt($tbody.data('index')) || 0;

        var removeLink = '<a href="#" class="btn btn-sm delete-link-inline link-delete-liquidacion-concepto tooltips" data-placement="top" data-original-title="Eliminar"><i class="fa fa-trash text-danger"></i></a>';
        var clasePrestamo = (codigoInterno == 7) ? 'table-danger' : '';

        var item = '<tr class="tr-liquidacion-concepto ' + clasePrestamo + '" data-total="' + importe.toFixed(2) + '" data-tipo="' + tipo + '">' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][id]" value=""></td>' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][tipoConceptoLiquidacion]" value="' + tipoConceptoId + '"></td>' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][descripcion]" value="' + descripcion.replace(/"/g, '&quot;') + '"></td>' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][cantidad]" value="' + cantidadRaw + '"></td>' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][valorUnitario]" value="' + valorRaw + '"></td>' +
            '<td class="hidden"><input type="hidden" name="liquidacion[conceptos][' + index + '][importe]" value="' + importe.toFixed(2).replace('.', ',') + '"></td>' +
            '<td class="v-middle text-center">' + tipoConceptoText + '</td>' +
            '<td class="v-middle text-center">' + (descripcion || '-') + '</td>' +
            '<td class="v-middle text-center">' + formatearMonto(cantidad) + '</td>' +
            '<td class="v-middle text-center">$ ' + formatearMonto(valor) + '</td>' +
            '<td class="v-middle text-center">$ ' + formatearMonto(importe) + '</td>' +
            '<td class="v-middle text-center">' + tipo + '</td>' +
            '<td class="text-center v-middle">' + removeLink + '</td>' +
            '</tr>';

        $tbody.append(item);
        $tbody.data('index', index + 1);

        $tbody.find('tr.tr-liquidacion-concepto:last').hide();
        $tbody.find('tr.tr-liquidacion-concepto').fadeIn('slow');

        $('.row-liquidacion-conceptos').show('slow');

        clearConceptoForm();
        recalcularTotales();
    }

    function eliminarConcepto(e) {
        e.preventDefault();
        var $tr = $(this).closest('.tr-liquidacion-concepto');
        $tr.hide('slow', function () {
            $tr.remove();
            if ($('.tr-liquidacion-concepto').length === 0) {
                $('.row-liquidacion-conceptos').hide('slow');
            }
            recalcularTotales();
        });
    }

    // Inicializar Select2 del concepto
    var $tipoConceptoSelect = $('#liquidacion_concepto_tipoConceptoLiquidacion');
    if ($tipoConceptoSelect.length && !$tipoConceptoSelect.data('select2')) {
        $tipoConceptoSelect.select2();
    }

    // Eventos
    $('.row-liquidacion-concepto-form').on('input', '#liquidacion_concepto_cantidad, #liquidacion_concepto_valorUnitario', calcularImporteConcepto);
    $(document).on('click', '.link-add-liquidacion-concepto', agregarConcepto);
    $(document).on('click', '.link-delete-liquidacion-concepto', eliminarConcepto);
    $('#liquidacion_sueldoBruto, #liquidacion_deducciones').on('input', recalcularTotales);

    // Modal de edición de semana
    var $modal = $('#modalSemana');

    $(document).on('click', '.abrir-modal-semana', function (e) {
        e.preventDefault();

        var $link = $(this);
        var semana = $link.data('semana');
        var apellido = $link.data('apellido');
        var nombre = $link.data('nombre');

        $modal.find('#modalSemanaLabel').text('Liquidación Semana ' + semana + ' de ' + apellido + ', ' + nombre);
        $modal.find('#formGuardarSemana').attr('action', $link.data('action'));
        $modal.find('#semana_sueldoBruto').val($link.data('sueldo-bruto'));
        $modal.find('#semana_deducciones').val($link.data('deducciones'));
        $modal.find('#semana_token').val($link.data('token'));
        $modal.find('#semana_return').val($link.data('return'));

        $modal.modal('show');
    });

    // Modal de agregar concepto a una semana
    var $modalConcepto = $('#modalConceptoSemana');
    var $conceptoTipoSelect = $('#concepto_semana_tipoConceptoLiquidacion');

    function calcularImporteConceptoSemana() {
        var cantidad = parseMonto($('#concepto_semana_cantidad').val());
        var valor = parseMonto($('#concepto_semana_valorUnitario').val());
        $('#concepto_semana_importe').val(formatearMonto(cantidad * valor));
    }

    $(document).on('input', '#concepto_semana_cantidad, #concepto_semana_valorUnitario', calcularImporteConceptoSemana);

    $(document).on('click', '.abrir-modal-concepto-semana', function (e) {
        e.preventDefault();

        var $link = $(this);
        var semana = $link.data('semana');
        var apellido = $link.data('apellido');
        var nombre = $link.data('nombre');

        if (!$conceptoTipoSelect.data('select2')) {
            $conceptoTipoSelect.select2({
                dropdownParent: $modalConcepto,
                allowClear: true,
                theme: 'default',
                width: '100%'
            });
        }

        $modalConcepto.find('#modalConceptoSemanaLabel').text('Agregar concepto - Semana ' + semana + ' de ' + apellido + ', ' + nombre);
        $modalConcepto.find('#formAgregarConceptoSemana').attr('action', $link.data('action'));
        $modalConcepto.find('#concepto_semana_token').val($link.data('token'));
        $conceptoTipoSelect.val('').trigger('change');
        $('#concepto_semana_descripcion').val('');
        $('#concepto_semana_cantidad').val('');
        $('#concepto_semana_valorUnitario').val('');
        $('#concepto_semana_importe').val('');

        $modalConcepto.modal('show');
    });

    // Confirmación de anulación
    $('#form-anular').on('submit', function (e) {
        e.preventDefault();
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea anular esta liquidación?',
            callbackOK: function () {
                $('#form-anular').off('submit').submit();
            }
        });
    });

    // Confirmación de reversión de pago
    $(document).on('click', '.btn-revertir-confirm', function (e) {
        e.preventDefault();
        var $this = $(this);
        var actionUrl = $this.data('action');
        var token = $this.data('token');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea revertir el pago de esta liquidación? La liquidación y sus semanas volverán a estado borrador y se anularán los pagos registrados.',
            callbackOK: function () {
                var $form = $('<form>', {action: actionUrl, method: 'post', style: 'display:none;'});
                $form.append($('<input>', {type: 'hidden', name: '_token', value: token}));
                $('body').append($form);
                $form.submit();
            }
        });
    });

    // Confirmación de reversión de aprobación
    $(document).on('click', '.btn-revertir-aprobacion-confirm', function (e) {
        e.preventDefault();
        var $this = $(this);
        var actionUrl = $this.data('action');
        var token = $this.data('token');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea revertir la aprobación de esta liquidación? La liquidación y sus semanas volverán a estado borrador.',
            callbackOK: function () {
                var $form = $('<form>', {action: actionUrl, method: 'post', style: 'display:none;'});
                $form.append($('<input>', {type: 'hidden', name: '_token', value: token}));
                $('body').append($form);
                $form.submit();
            }
        });
    });

    $(document).on('click', '.btn-revertir-aprobacion-semana-confirm', function (e) {
        e.preventDefault();
        var $form = $(this).closest('form');
        show_confirm({
            title: 'Confirmación',
            type: 'warning',
            msg: '¿Desea revertir la aprobación de esta semana? Volverá a estado borrador.',
            callbackOK: function () {
                $form.submit();
            }
        });
    });

    // Confirmación de eliminación de concepto desde el detalle de semana
    $(document).on('click', '.btn-eliminar-concepto-semana', function (e) {
        e.preventDefault();
        var $form = $(this).closest('form');
        show_confirm({
            title: 'Confirmar',
            type: 'warning',
            msg: '¿Desea eliminar este concepto?',
            callbackOK: function () {
                $form.submit();
            }
        });
    });

    recalcularTotales();
});
