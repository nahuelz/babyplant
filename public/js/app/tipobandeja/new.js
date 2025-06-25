
jQuery(document).ready(function () {

    const picker = document.querySelector('input[type="color"]');
    const hexInput = document.querySelector('input[name$="[color]"]');

    // Si elige un color en la paleta, lo pone en el input de texto
    picker.addEventListener('input', e => {
        hexInput.value = e.target.value;
    });

    // Si escribe el hex manualmente, lo aplica a la paleta (si es válido)
    hexInput.addEventListener('input', e => {
        const val = e.target.value;
        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
            picker.value = val;
        }
    });

    if (hexInput.value && /^#[0-9a-fA-F]{6}$/.test(hexInput.value)) {
        picker.value = hexInput.value;
    }

    FormValidation.formValidation(
        $("form[name=tipo_bandeja]")[0],
        {
            fields: {
                'tipo_bandeja[nombre]': {
                    validators: {
                        notEmpty: {
                            message: 'Este campo es requerido'
                        }
                    }
                },
                'tipo_bandeja[estandar]': {
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