
var spinner = '\
        <div id="loader-wrapper" class="old-spinner">\n\
            <div id="legend">\n\
                <div class="font-size-20">BABYPLANT</div>\n\
                <div class="font-size-16">cargando..</div>\n\
            </div>\n\
            <div id="loader"></div>\n\
            <div class="loader-section section-left"></div>\n\
            <div class="loader-section section-right"></div>\n\
        </div>';

blockPageContent();

$(document).ajaxStart(function () {
    blockPageContent();
}).ajaxStop(function () {
    unblockPageContent();
});

$(document).ready(function () {

    $('form:not(.no-spinner)').submit(function (e) {

        e.preventDefault();

        this.submit();

        blockPageContent();
    });

    $.ajaxSetup({
        'beforeSend': function () {
            if (!$('.blockUI').length) {
                blockPageContent();
            }
        },
        'complete': function () {
            unblockPageContent();
        }
    });

    if (!$.active) {
        unblockPageContent();
    }

    (function (open) {

        XMLHttpRequest.prototype.open = function (method, url, async) {

            this.addEventListener("readystatechange", function (e) {
                if (this.readyState == 4) {
                    if (this.responseText.indexOf('system-error') !== -1) {
                        $('.select2-drop').select2("close");
                        showAlert({type: 'warning', color: 'red', msg: this.responseText});
                    }
                    if (this.responseText.indexOf('login-form') !== -1) {
                        e.preventDefault();
                        location.href = __AJAX_PATH__; // location.host;
                        alert('Su sesión ha finalizado');
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                    }
                }
            }, false);

            open.call(this, method, url, async);
        };

    })(XMLHttpRequest.prototype.open);

});

/**
 *
 * @returns {undefined}
 */
function blockPageContent() {
    blockTarget($(".page-content"));
}

/**
 *
 * @returns {undefined}
 */
function unblockPageContent() {
    unblockTarget($(".page-content"));
}

/**
 *
 * @returns {undefined}
 */
function blockBody() {
    blockTarget($("body"));
}

/**
 *
 * @returns {undefined}
 */
function unblockBody() {
    unblockTarget($("body"));
}

/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function blockTarget(target) {
    $(".old-spinner").remove();
    $(".loaded").removeClass('loaded');
//  $(target)[0].insertAdjacentElement('afterbegin', htmlToElements(spinner));
    $(target)[0].insertAdjacentHTML('afterbegin', spinner);
}

/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function unblockTarget(target) {
    $(target).addClass('loaded');
}

/**
 * @param {String} html representing any number of sibling elements
 * @return {NodeList}
 */
function htmlToElements(html) {
    var template = document.createElement('div');
    $(template).addClass('old-spinner');
    template.innerHTML = html;
    return template;
}

/**
 *
 * @param {type} target
 * @param {type} messageParam
 * @returns {undefined}
 */
function disableTarget(target, messageParam) {

    var message = typeof messageParam !== "undefined" && messageParam !== null ? messageParam : '';

    target.block(
        {
            message: message,
            fadeIn: 300,
            fadeOut: 300,
            centerY: 0,
            css: {
                cursor: 'not-allowed',
                top: '30%',
                'font-size': '18px',
                border: 'none',
                padding: '15px',
                opacity: '.9',
                color: '#FFF',
                backgroundColor: 'transparent',
                'z-index': 10000
            },
            overlayCSS: {
                cursor: 'not-allowed',
                backgroundColor: '#414042',
                opacity: '.7',
                'z-index': 10000
            }
        }
    );
}

/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function unDisableTarget(target) {
    target.unblock();
}

/**
 *
 * @param {type} target
 * @param {type} messageParam
 * @returns {undefined}
 */
function disableTarget(target, messageParam) {

    var message = typeof messageParam !== "undefined" && messageParam !== null ? messageParam : '';

    target.block(
        {
            message: message,
            fadeIn: 300,
            fadeOut: 300,
            centerY: 0,
            css: {
                cursor: 'not-allowed',
                top: '30%',
                'font-size': '18px',
                border: 'none',
                padding: '15px',
                opacity: '.9',
                color: '#FFF',
                backgroundColor: 'transparent',
                'z-index': 10000
            },
            overlayCSS: {
                cursor: 'not-allowed',
                backgroundColor: '#414042',
                opacity: '.7',
                'z-index': 10000
            }
        }
    );
}

/**
 *
 * @param {type} target
 * @returns {undefined}
 */
function unDisableTarget(target) {
    target.unblock();
}