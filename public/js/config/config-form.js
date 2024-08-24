// CONFIG filestyle 
if (typeof $().filestyle !== 'undefined') {
    $.fn.filestyle.defaults = {
        'text': 'Examinar',
        'htmlIcon': '',
        'btnClass': 'btn-secondary',
        'size': 'nr',
        'input': true,
        'badge': false,
        'badgeName': 'badge-light',
        'buttonBefore': false,
        'dragdrop': true,
        'disabled': false,
        'placeholder': '',
        'onChange': function () {}
    };
}

jQuery(document).ready(function () {

    initPreventDoubleSubmission();

    initSelects();

    initCurrencies();

    initChecksYRadios();

    initDatepickers();

    initForm();

    initBaseMask();

    initBaseSubmitButton();
});


/**
 * 
 * @returns {undefined}
 */
function initPreventDoubleSubmission() {

    if (typeof Ladda !== "undefined") {
        Ladda.bind('.btn-submit, .submit-button, .confirm-button, .ladda-button', {timeout: 5000});
    } else {

        $('form').on('submit', function () {
            return checkLastSubmitTime($(this));
        });

        $(document).on('click', '.btn-submit', function () {
            return checkLastSubmitTime($(this));
        });
    }
}

/**
 *
 * @param {type} $element
 * @returns {Boolean}
 */
function checkLastSubmitTime($element) {

    var lastTime = $element.data("lastSubmitTime");

    if (lastTime) {

        var now = jQuery.now();

        if ((now - lastTime) < 3000) { // 3 segundos
            return false;
        }
    }

    checkSubmitButton();

    $element.data("lastSubmitTime", jQuery.now());

    return true;
}

/**
 *
 * @returns {undefined}
 */
function checkSubmitButton() {

    $('button[type="submit"]').each(function () {
        blockSubmitButton($(this));
    });

    $('.btn-submit').each(function () {
        blockSubmitButton($(this));
    });
}

/**
 *
 * @param {type} $element
 * @returns {undefined}
 */
function blockSubmitButton($element) {

    $element.data("label", $element.html());

    $element.prop('disabled', true);
    $element.html('Cargando...');

    window.setTimeout(function () {
        $element.html($element.data("label"));
        $element.prop('disabled', false);
    }, 3000);
}

/**
 *
 * @returns {undefined}
 */
function initSelects() {
    if (typeof $().select2 !== 'undefined') {

        $.fn.select2.defaults.set('language', 'es');

        // Init select con la clase "choice"
        $('select.choice').each(function () {

            if (!$('#s2id_' + $(this).attr('id')).length) {
                var select2_options = {
                    allowClear: typeof $(this).data('not-allow-clear') == 'undefined',
                    dropdownAutoWidth: true,
                    theme: "default"
                };

                var parent = $(this).closest('.modal');

                if (parent.length) {
                    select2_options.dropdownParent = parent;
                }

                $(this).select2(select2_options);

                if ($(this).attr('multiple')) {

                    if (!$(this).hasClass('hide-select-all')) {
                        var optionAll = new Option("-- Seleccionar todos --", "all");
                        var optionClear = new Option("-- Limpiar selección --", "clear");

                        $(this).prepend(optionClear);
                        $(this).prepend(optionAll);
                    }

                    $(this).select2({
                        escapeMarkup: function (m) {
                            return m;
                        },
                        templateResult: function (object, container, query) {
                            if (object.id == 'all' || object.id == 'clear')
                                return '<span class="toggle-highlight" style="color:#31708F;font-size:13px;font-weight:bold;"> ' + object.text + '</span>';
                            return object.text;
                        }
                    });

                    $(this).on("change", function (e) {

                        if ($.inArray('all', $(this).val()) !== -1) {
                            var selected = [];
                            $(this).find("option").each(function (i, e) {
                                if ($(e).attr("value") == 'all' || $(e).attr("value") == 'clear')
                                    return true;

                                selected[selected.length] = $(e).attr("value");
                            });
                            $(this).val(selected).trigger("change");
                        } else if ($.inArray('clear', $(this).val()) !== -1) {
                            $(this).val(null).trigger("change");
                        }
                    });
                }


                // Select2 Opening Callback
                $(this).on("select2-opening", function () {
                    closeActiveSelect2();
                });
            }
        });
    }
}

/**
 *
 * @returns {undefined}
 */
function initCurrencies() {

    $('.currency, .percentage, .number, .numberPositive, .integerPositive').each(function () {
        $(this).off('paste keypress').bind('paste keypress', function (e) {

            // '46' = keyCode de '.'
            if (e.keyCode === 46 || e.charCode === 46) {

                e.preventDefault();

                $(this).val($(this).val() + ',');
            }
        });
    });

    $('.currency').each(function () {

        var digits = 2;

        if (typeof $(this).data('digits') !== "undefined") {

            digits = $(this).data('digits');
        }

        $(this).val($(this).val().replace(/\./g, ','));

        $(this).inputmask("decimal", {radixPoint: ",", digits: digits});
    });

    $('.percentage').each(function () {
        $(this).val($(this).val().replace(/\./g, ','));
        $(this).inputmask('Regex', {regex: "^100$|^[0-9]{1,2}$|^[0-9]{1,2}\,[0-9]{1,5}$"});
    });

    $('.number').each(function () {

        var digits = 2;

        if (typeof $(this).data('digits') !== "undefined") {

            digits = $(this).data('digits');
        }

        $(this).inputmask("decimal", {allowMinus: false, allowPlus: false, digits: digits});
    });

    $('.numberPositive').each(function () {

        var digits = 2;

        if (typeof $(this).data('digits') !== "undefined") {

            digits = $(this).data('digits');
        }

        $(this).val($(this).val().replace(/\./g, ','));

        $(this).inputmask("decimal", {radixPoint: ",", allowMinus: false, allowPlus: false, digits: digits});
    });

    $('.integerPositive').each(function () {
        $(this).inputmask("integer", {allowMinus: false, allowPlus: false});
    });
}

/**
 * 
 * @returns {undefined}
 */
function closeActiveSelect2() {
    $('.select2-drop-active').hide();
    $('.select2-container-active.select2-dropdown-open').removeClass('select2-dropdown-open');
    $('.select2-container-active').removeClass('select2-dropdown-open, select2-container-active');
}

/**
 *
 * @param {type} element
 * @returns {undefined}
 */
function initDatepickers(element) {

    element = element === undefined ? document : element;

    $(element).find('input.datepicker').each(function () {

        //$(this).attr('readonly', 'readonly');
        // if (!$(this).parent().parent().hasClass('input-group')) {
        //     $(this).parent().wrap('<div class="input-group"></div>');

        //     $('<span class="input-group-addon"><i class="fa fa-calendar"></i></span>')
        //             .insertBefore($(this).parent());
        // }

    });

    $('.datepicker').each(function () {
        initDatepicker($(this));
    });

    // MONTH YEAR
    $('.monthyearpicker').each(function () {

        if (!$(this).is('[readonly]')) {

            initDatepicker($(this), {
                todayBtn: "linked",
                clearBtn: true,
                format: "mm-yyyy",
                viewMode: "months",
                minViewMode: 1,
                maxViewMode: 2
            });
        }
    });

    // MONTH
    $('.monthpicker').each(function () {

        initDatepicker($(this), {
            todayBtn: false,
            clearBtn: false,
            format: "MM",
            viewMode: "months",
            minViewMode: 1,
            maxViewMode: 1
        });
        if ($(this).val() !== '') {
            $(this).datepicker('show').datepicker('hide');
        }
        $(this).on('show', function (e) {
            $('.datepicker-dropdown .datepicker-months table thead tr th.prev').html('');
            $('.datepicker-dropdown .datepicker-months table thead tr th.prev').removeClass('prev');
            $('.datepicker-dropdown .datepicker-months table thead tr th.datepicker-switch').css('width', '145px');
            $('.datepicker-dropdown .datepicker-months table thead tr th.datepicker-switch').removeClass('datepicker-switch');
            $('.datepicker-dropdown .datepicker-months table thead tr th.next').html('');
            $('.datepicker-dropdown .datepicker-months table thead tr th.next').removeClass('next');
        });
    });

    // YEAR
    $('.yearpicker').each(function () {


        initDatepicker($(this), {
            format: "yyyy",
            viewMode: "years",
            minViewMode: "years"
        });

        if (typeof Inputmask !== 'undefined') {
            if (!$(this).hasClass('nomask')) {
                $(this).inputmask("9999");
            }
        }
    });
}

/**
 *
 * @param {type} element
 * @param {type} options
 * @returns {undefined}
 */
function initDatepicker(element, options) {
    if (options === undefined) {
        options = {};
    }

    var def_opts = {
        autoclose: true,
        clearBtn: true,
        format: 'dd/mm/yyyy',
        language: "es",
        todayHighlight: true,
    };

    $.extend(def_opts, options);

    $(element).datepicker(def_opts);

    if (!$(element).hasClass('novalidate')) {
        $(element).addClass('fecha_custom');
    }

    // if ((typeof Inputmask !== 'undefined' || $.inputmask) && !$(element).hasClass('nomask')) {
    //     $(element).inputmask('date', {placeholder: "_", yearrange: {minyear: 1900, maxyear: 2200}});
    // }
}

/**
 * 
 * @returns {undefined}
 */
function initFileInputStyle() {
    if (typeof $().filestyle !== 'undefined') {
        $(".filestyle").each(function () {
            $(this).filestyle({text: "Examinar"});
            var $nombreArchivo = $(this).attr('data-file');
            if ($nombreArchivo) {
                $(this).next('.bootstrap-filestyle').find('input').val($nombreArchivo);
            }
        });
    }
}

/**
 *
 * @returns {undefined}
 */
function initChecksYRadios() {

    var swts = $('form input[type=checkbox]').not('.not-checkbox-transform, [baseClass=bootstrap-switch]');

    if (swts.length == 0) {
        return;
    }

    swts.attr({
        'data-on-text': 'Si',
        'data-off-text': 'No',
        'data-on-color': "success",
        'data-off-color': "default",
        'baseClass': "bootstrap-switch"
    }).bootstrapSwitch();
}

/**
 * 
 * @returns {undefined}
 */
function initForm() {
    $('form.horizontal-form').attr('autocomplete', 'off');
}

/**
 * 
 * @returns {undefined}
 */
function initBaseValidation() {

    if (typeof $().validate !== 'undefined') {

        var error = $('.alert-danger');

        $('form').each(function () {
            $(this).validate({
                lang: 'es',
                errorElement: 'span',
                errorClass: 'help-block',
                focusInvalid: true,
                ignore: 'input[type=hidden]:not(.not-ignore)',
                errorPlacement: function (error, element) { // render error placement for each input type
                    // Si el input está dentro de un form-group
                    if ($(element).parents('div.form-group').length > 0) {
                        if (element.closest('.form-group').find('.help-block').length === 0) {
                            element.closest('.form-group').append(error);
                        } else {
                            if (element.closest('.form-group.custom-help-block').length !== 0) {
                                element.closest('.form-group.custom-help-block').prepend(error);
                            }
                        }
                    } else {
                        error.insertAfter(element); // for other inputs, just perform default behavior
                    }
                },
                invalidHandler: function (event, validator) { // display error alert on form submit   
                    error.show();
                    scrollTo(error, -200);
                },
                highlight: function (element) {
                    $(element).closest('.form-group').addClass('has-error');
                    $(element).closest('.labelless-input').addClass('has-error');
                },
                unhighlight: function (element) {
                    $(element).closest('.form-group').removeClass('has-error');
                    $(element).closest('.labelless-input').removeClass('has-error');
                },
                success: function (label) {
                    label.closest('.form-group').removeClass('has-error'); // set success class to the control group
                    label.closest('.labelless-input').removeClass('has-error'); // set success class to the control group
                }
            });
        });

        // CUIT
        $.validator.addMethod("cuit", function (value) {

            if (value === '') {
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
        }, "Formato de CUIT incorrecto");

        // CUIT FISICA
        $.validator.addMethod("cuit_persona_fisica", function (value) {

            if (value === '') {
                return true;
            }

            if (!/^(20|23|24|27)-/.test(value)) {
                return false;
            }

            return true;
        }, "Debe indicar un CUIT de persona física");

        // CUIT JURIDICA
        $.validator.addMethod("cuit_persona_juridica", function (value) {

            if (value === '') {
                return true;
            }

            if (!/^(30|33|34)-/.test(value)) {
                return false;
            }

            return true;
        }, "Debe indicar un CUIT de persona jurídica");


        // Min
        $.validator.methods.min = function (value, element, param) {
            return clearCurrencyValue(value) >= param;
        };

        // Custom phone
        $.validator.addMethod("custom_phone", function (value) {

            if (value === '') {
                return true;
            }

            if (!/^[\ \+\-\(\)0-9]+$/.test(value)) {
                return false;
            } else {
                return true;
            }

            return false;
        }, "Sólo puede contener números y los caracteres (, ), -, +");
    }
}

/**
 *
 * @returns {undefined}
 */
function initBaseMask() {

    if (typeof $().inputmask !== 'undefined') {

        $('.mask_cuit').each(function () {
            $(this).inputmask({
                mask: "99-99999999-9",
                placeholder: "_"
            });
        });

        $('.mask_dni').each(function () {
            $(this).inputmask({
                mask: "99999999",
                numericInput: true,
                onincomplete: function () {
                    $(this).val($(this).val().replace(/_/g, '0'));
                }
            });
        });

        $('.mask_text').each(function () {
            $(this).inputmask({
                regex: "^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ .,'-]*$",
                placeholder: ""
            });
        });
    }
}

/**
 * 
 * @returns {undefined}
 */
function initBaseSubmitButton() {

    var $form = $('form.base-form');

    if ($form) {
        $form.find('button[type="submit"]').off('click').on('click', function () {
            if ($form.valid()) {
                checkTabError();
                $form.submit();
            } else {
                checkTabError();
            }
        });
    }
}