"use strict";
var _buttonSpinnerClasses = 'spinner spinner-right spinner-white pr-15';

// Class Definition
var KTLogin = function() {

    var _login;

    var loginForm = KTUtil.getById('kt_login_signin_form');

    initTogglePasswordHandler();

    // Handle forgot button
    $(document).on('click', '#kt_login_forgot', function (e) {
        e.preventDefault();
        var formContainer = KTUtil.getById('reset-form-container');
        var formSubmitUrl = $(formContainer).data('url');

        KTUtil.btnWait(loginForm, _buttonSpinnerClasses, '<span class="spinner spinner-right spinner-primary p-15"></span>');

        $(formContainer).html('');

        const xhttpReset = new XMLHttpRequest();
        xhttpReset.onload = function() {
            $(formContainer).html(this.responseText);
            KTUtil.btnRelease(loginForm);
            _showForm('forgot');
            _handleFormForgot();
        }
        xhttpReset.open("GET", formSubmitUrl);
        xhttpReset.send();
    });

    var _showForm = function(form) {
        var cls = 'login-' + form + '-on';
        var form = 'kt_login_' + form + '_form';

        _login.removeClass('login-forgot-on');
        _login.removeClass('login-signin-on');

        _login.addClass(cls);

        KTUtil.animateClass(KTUtil.getById(form), 'animate__animated animate__backInUp');
    }

    var _handleFormForgot = function() {
        var form = KTUtil.getById('kt_login_forgot_form');
        var formContainer = KTUtil.getById('reset-form-container');
        var formSubmitUrl = $(formContainer).data('url')
        var formSubmitButton = KTUtil.getById('kt_login_forgot_form_submit_button');

        if (!form) {
            return;
        }

        // Handle cancel button
        $('#kt_login_forgot_cancel').on('click', function (e) {
            e.preventDefault();

            _showForm('signin');
        });


        FormValidation
                .formValidation(
                        form,
                        {
                            fields: {
                                'reset_password_request_form[email]': {
                                    validators: {
                                        notEmpty: {
                                            message: 'El email es requerido'
                                        },
                                        emailAddress: {
                                            message: 'No es un mail válido'
                                        }
                                    }
                                }
                            },
                            plugins: {
                                trigger: new FormValidation.plugins.Trigger(),
                                submitButton: new FormValidation.plugins.SubmitButton(),
                                //defaultSubmit: new FormValidation.plugins.DefaultSubmit(), // Uncomment this line to enable normal button submit after form validation
                                bootstrap: new FormValidation.plugins.Bootstrap({
                                    //	eleInvalidClass: '', // Repace with uncomment to hide bootstrap validation icons
                                    //	eleValidClass: '',   // Repace with uncomment to hide bootstrap validation icons
                                })
                            }
                        }
                )
		    .on('core.form.valid', function() {
                    requestResetPassword(form, formSubmitUrl, 'signin');
                })
			.on('core.form.invalid', function() {
                    Swal.fire({
                        text: "Por favor complete el email correctamente",
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Aceptar",
                        customClass: {
                            confirmButton: "btn font-weight-bold btn-light-primary"
                        }
				}).then(function() {
                        KTUtil.scrollTop();
                    });
                });
    }

    // Public Functions
    return {
        init: function() {

            _login = $('#kt_login');

        },
        _showForm: function(form) {
            _showForm(form)
        }
    };
}();

// Class Initialization
jQuery(document).ready(function() {
    KTLogin.init();
    $(".btn-afip").click(function (e) {
        e.preventDefault();
        KTApp.blockPage();
        $.ajax({
            type: "POST",
            url: urlAfip,
            success: function (response) {
                window.location.href = response.url
                KTApp.unblockPage();
            }
        });
    });
});

/**
 * 
 * @param {*} form 
 * @param {*} formSubmitUrl 
 * @param {*} formName 
 */
function requestResetPassword(form, formSubmitUrl, formName){
    // Create an XMLHttpRequest object
    const xhttpReset = new XMLHttpRequest();

    // Define a callback function
    xhttpReset.onload = function() {
        try {
            var dataParsed = jQuery.parseJSON(this.responseText);
        } catch (err) {
            dataParsed = null;
        }

        KTUtil.btnRelease(form);

        if (dataParsed) {
            if (dataParsed.statusText == "OK") {
                Swal.fire({
                    title: dataParsed.message,
                    icon: "success"
                });
                form.reset();
                KTLogin._showForm(formName);
            } else {
                Swal.fire({
                    title: dataParsed.message,
                    icon: "error"
                });
                form.reset();
                KTLogin._showForm(formName);
            }
        } else {
            Swal.fire({
                title: 'Se ha producido un error. Intente nuevamente',
                icon: "error"
            });
        }
    }

    // Send a request
    xhttpReset.open("POST", formSubmitUrl, true);
    xhttpReset.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhttpReset.send($(form).serialize());

    KTUtil.btnWait(form, _buttonSpinnerClasses, '<span class="spinner spinner-right spinner-primary p-15"></span>');
}

/**
 * 
 * @returns {undefined}
 */
function initTogglePasswordHandler() {

    $('#togglePassword').on('click', function () {

        const type = $('#inputPassword').attr('type') === 'password' ? 'text' : 'password';
        $('#inputPassword').attr('type', type);
        $(this).find('i').toggleClass('la-eye-slash');
    });
}