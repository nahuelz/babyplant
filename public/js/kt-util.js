"use strict";

/**
 * KTUtil - Utilidades esenciales para el funcionamiento móvil de DataTables
 * Funciones críticas restauradas para detectar dispositivos móviles y manejar eventos
 */

// Polyfills necesarios
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
    }
    Element.prototype.closest = function (s) {
        var el = this;
        var ancestor = this;
        if (!document.documentElement.contains(el)) return null;
        do {
            if (ancestor.matches(s)) return ancestor;
            ancestor = ancestor.parentElement;
        } while (ancestor !== null);
        return null;
    };
}

// Variables globales necesarias
window.KTUtilElementDataStore = {};
window.KTUtilElementDataStoreID = 0;
window.KTUtilDelegatedEventHandlers = {};

var KTUtil = function() {
    var resizeHandlers = [];

    /** @type {object} breakpoints The device width breakpoints **/
    var breakpoints = {
        sm: 544, // Small screen / phone
        md: 768, // Medium screen / tablet
        lg: 992, // Large screen / desktop
        xl: 1200 // Extra large screen / wide desktop
    };

    /**
     * Handle window resize event with some delay to attach event handlers upon resize complete
     */
    var _windowResizeHandler = function() {
        var _runResizeHandlers = function() {
            for (var i = 0; i < resizeHandlers.length; i++) {
                var each = resizeHandlers[i];
                each.call();
            }
        };

        var timer;
        window.addEventListener('resize', function() {
            KTUtil.throttle(timer, function() {
                _runResizeHandlers();
            }, 200);
        });
    };

    return {
        /**
         * Class main initializer.
         */
        init: function(settings) {
            if (settings && settings.breakpoints) {
                breakpoints = settings.breakpoints;
            }
            _windowResizeHandler();
        },

        /**
         * Adds window resize event handler.
         */
        addResizeHandler: function(callback) {
            resizeHandlers.push(callback);
        },

        /**
         * Removes window resize event handler.
         */
        removeResizeHandler: function(callback) {
            for (var i = 0; i < resizeHandlers.length; i++) {
                if (callback === resizeHandlers[i]) {
                    delete resizeHandlers[i];
                }
            }
        },

        /**
         * Trigger window resize handlers.
         */
        runResizeHandlers: function() {
            _runResizeHandlers();
        },

        resize: function() {
            if (typeof(Event) === 'function') {
                window.dispatchEvent(new Event('resize'));
            } else {
                var evt = window.document.createEvent('UIEvents');
                evt.initUIEvent('resize', true, false, window, 0);
                window.dispatchEvent(evt);
            }
        },

        /**
         * Checks whether current device is mobile touch.
         * @returns {boolean}
         */
        isMobileDevice: function() {
            var test = (this.getViewPort().width < this.getBreakpoint('lg') ? true : false);

            if (test === false) {
                // For use within normal web clients
                test = navigator.userAgent.match(/iPad|iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i) != null;
            }

            return test;
        },

        /**
         * Checks whether current page is in RTL mode.
         * @returns {boolean}
         */
        /*isRTL: function() {
            return (document.body.getAttribute('dir') === 'rtl' || document.documentElement.getAttribute('dir') === 'rtl');
        },*/

        /**
         * Gets browser window viewport size.
         * @returns {object}
         */
        getViewPort: function() {
            var e = window,
                a = 'inner';
            if (!('innerWidth' in window)) {
                a = 'client';
                e = document.documentElement || document.body;
            }

            return {
                width: e[a + 'Width'],
                height: e[a + 'Height']
            };
        },

        /**
         * Checks whether given device mode is currently activated.
         * @param {string} mode Responsive mode name(e.g: desktop, desktop-and-tablet, tablet, tablet-and-mobile, mobile)
         * @returns {boolean}
         */
        isBreakpointUp: function(mode) {
            var width = this.getViewPort().width;
            var breakpoint = this.getBreakpoint(mode);
            return (width >= breakpoint);
        },

        isBreakpointDown: function(mode) {
            var width = this.getViewPort().width;
            var breakpoint = this.getBreakpoint(mode);
            return (width < breakpoint);
        },

        /**
         * Gets window width for give breakpoint mode.
         * @param {string} mode Responsive mode name(e.g: xl, lg, md, sm)
         * @returns {number}
         */
        getBreakpoint: function(mode) {
            return breakpoints[mode];
        },

        /**
         * Simulates delay
         */
        sleep: function(milliseconds) {
            var start = new Date().getTime();
            for (var i = 0; i < 1e7; i++) {
                if ((new Date().getTime() - start) > milliseconds) {
                    break;
                }
            }
        },

        /**
         * Initialize custom scrollbar
         * @param {string|object} element - Element selector or DOM element
         * @param {object} options - Scroll options
         */
        scrollInit: function(element, options) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }

            if (!element) {
                return;
            }

            // Default options
            var defaultOptions = {
                height: 200,
                mobileNativeScroll: true,
                desktopNativeScroll: false,
                resetHeightOnDestroy: false,
                handleWindowResize: false,
                rememberPosition: false
            };

            options = KTUtil.deepExtend(defaultOptions, options);

            // For mobile devices, use native scroll
            if (KTUtil.isMobileDevice() && options.mobileNativeScroll) {
                return;
            }

            // For desktop devices, use native scroll if specified
            if (!KTUtil.isMobileDevice() && options.desktopNativeScroll) {
                return;
            }

            // Apply custom scrollbar styling if needed
            KTUtil.addClass(element, 'kt-scroll');

            return element;
        },

        /**
         * Update custom scrollbar
         * @param {string|object} element - Element selector or DOM element
         */
        scrollUpdate: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }

            if (!element) {
                return;
            }

            // Update scroll position if needed
            return element;
        },

        /**
         * Destroy custom scrollbar
         * @param {string|object} element - Element selector or DOM element
         */
        scrollDestroy: function(element, resetHeight) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }

            if (!element) {
                return;
            }

            // Remove custom scrollbar classes
            KTUtil.removeClass(element, 'kt-scroll');

            if (resetHeight) {
                element.style.height = '';
                element.style.maxHeight = '';
            }

            return element;
        },


        /**
         * Set scroll top position
         * @param {string|object} element - Element selector or DOM element
         * @param {number} position - Scroll position
         */
        setScrollTop: function(element, position) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }

            if (element) {
                element.scrollTop = position;
            }
        },

        // Deep extend:  $.extend(true, {}, objA, objB);
        deepExtend: function(out) {
            out = out || {};

            for (var i = 1; i < arguments.length; i++) {
                var obj = arguments[i];

                if (!obj)
                    continue;

                for (var key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        if (typeof obj[key] === 'object')
                            out[key] = KTUtil.deepExtend(out[key], obj[key]);
                        else
                            out[key] = obj[key];
                    }
                }
            }

            return out;
        },

        // extend:  $.extend({}, objA, objB);
        extend: function(out) {
            out = out || {};

            for (var i = 1; i < arguments.length; i++) {
                if (!arguments[i])
                    continue;

                for (var key in arguments[i]) {
                    if (arguments[i].hasOwnProperty(key))
                        out[key] = arguments[i][key];
                }
            }

            return out;
        },

        getById: function(el) {
            if (typeof el === 'string') {
                return document.getElementById(el);
            } else {
                return el;
            }
        },

        getByTag: function(query) {
            return document.getElementsByTagName(query);
        },

        getByTagName: function(query) {
            return document.getElementsByTagName(query);
        },

        getByClass: function(query) {
            return document.getElementsByClassName(query);
        },

        getBody: function() {
            return document.getElementsByTagName('body')[0];
        },

        /**
         * Checks whether the element has given classes
         */
        hasClasses: function(el, classes) {
            if (!el) {
                return;
            }

            var classesArr = classes.split(" ");

            for (var i = 0; i < classesArr.length; i++) {
                if (KTUtil.hasClass(el, KTUtil.trim(classesArr[i])) == false) {
                    return false;
                }
            }

            return true;
        },

        hasClass: function(el, className) {
            if (!el) {
                return;
            }

            return el.classList ? el.classList.contains(className) : new RegExp('\\b' + className + '\\b').test(el.className);
        },

        addClass: function(el, className) {
            if (!el || typeof className === 'undefined') {
                return;
            }

            var classNames = className.split(' ');

            if (el.classList) {
                for (var i = 0; i < classNames.length; i++) {
                    if (classNames[i] && classNames[i].length > 0) {
                        el.classList.add(KTUtil.trim(classNames[i]));
                    }
                }
            } else if (!KTUtil.hasClass(el, className)) {
                for (var x = 0; x < classNames.length; x++) {
                    el.className += ' ' + KTUtil.trim(classNames[x]);
                }
            }
        },

        removeClass: function(el, className) {
          if (!el || typeof className === 'undefined') {
                return;
            }

            var classNames = className.split(' ');

            if (el.classList) {
                for (var i = 0; i < classNames.length; i++) {
                    el.classList.remove(KTUtil.trim(classNames[i]));
                }
            } else if (KTUtil.hasClass(el, className)) {
                for (var x = 0; x < classNames.length; x++) {
                    el.className = el.className.replace(new RegExp('\\b' + KTUtil.trim(classNames[x]) + '\\b', 'g'), '');
                }
            }
        },

        triggerCustomEvent: function(el, eventName, data) {
            var event;
            if (window.CustomEvent) {
                event = new CustomEvent(eventName, {
                    detail: data
                });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent(eventName, true, true, data);
            }

            el.dispatchEvent(event);
        },

        triggerEvent: function(node, eventName) {
            var doc;
            if (node.ownerDocument) {
                doc = node.ownerDocument;
            } else if (node.nodeType == 9) {
                doc = node;
            } else {
                throw new Error("Invalid node passed to fireEvent: " + node.id);
            }

            if (node.dispatchEvent) {
                var eventClass = "";

                switch (eventName) {
                    case "click":
                    case "mousedown":
                    case "mouseup":
                        eventClass = "MouseEvents";
                        break;
                    case "focus":
                    case "change":
                    case "blur":
                    case "select":
                        eventClass = "HTMLEvents";
                        break;
                    default:
                        eventClass = "Event";
                        break;
                }

                var event = doc.createEvent(eventClass);
                var bubbles = eventName == "change" ? false : true;
                event.initEvent(eventName, bubbles, true);
                event.synthetic = true;
                node.dispatchEvent(event, true);
            } else if (node.fireEvent) {
                var event = doc.createEventObject();
                event.synthetic = true;
                node.fireEvent("on" + eventName, event);
            }
        },

        index: function(elm) {
            elm = KTUtil.get(elm);
            var c = elm.parentNode.children, i = 0;
            for (; i < c.length; i++)
                if (c[i] == elm) return i;
        },

        trim: function(string) {
            return string.trim();
        },

        eventTriggered: function(e) {
            if (e.currentTarget.dataset.triggered) {
                return true;
            } else {
                e.currentTarget.dataset.triggered = true;
                return false;
            }
        },

        remove: function(el) {
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        },

        find: function(parent, query) {
            parent = KTUtil.getById(parent);
            if (parent) {
                return parent.querySelector(query);
            }
        },

        findAll: function(parent, query) {
            return parent.querySelectorAll(query);
        },

        insertAfter: function(el, referenceNode) {
            return referenceNode.parentNode.insertBefore(el, referenceNode.nextSibling);
        },

        parents: function(elem, selector) {
            var parents = [];

            for ( ; elem && elem !== document; elem = elem.parentNode ) {
                if (selector) {
                    if (elem.matches(selector)) {
                        parents.push(elem);
                    }
                    continue;
                }
                parents.push(elem);
            }

            return parents;
        },

        children: function(el, selector, log) {
            if (!el || !el.childNodes) {
                return null;
            }

            var result = [], i = 0, l = el.childNodes.length;

            for (var i; i < l; ++i) {
                var child = el.childNodes[i];
                child = child.nodeType ? el.childNodes[i] : null;

                if (child) {
                    if (!selector || (selector && child.matches(selector))) {
                        result.push(child);
                    }
                }
            }

            return result;
        },

        child: function(el, selector, index) {
            var children = KTUtil.children(el, selector);
            return children ? children[index] : null;
        },

        matches: function(el, selector) {
            var p = Element.prototype;
            var f = p.matches || p.webkitMatchesSelector || p.mozMatchesSelector || p.msMatchesSelector || function(s) {
                return [].indexOf.call(document.querySelectorAll(s), this) !== -1;
            };

            return f.call(el, selector);
        },

        data: function(el) {
            return {
                set: function(name, data) {
                    if (!el) {
                        return;
                    }

                    if (el.customDataTag === undefined) {
                        window.KTUtilElementDataStoreID++;
                        el.customDataTag = window.KTUtilElementDataStoreID;
                    }

                    if (window.KTUtilElementDataStore[el.customDataTag] === undefined) {
                        window.KTUtilElementDataStore[el.customDataTag] = {};
                    }

                    window.KTUtilElementDataStore[el.customDataTag][name] = data;
                },

                get: function(name) {
                    if (!el) {
                        return;
                    }

                    if (el.customDataTag === undefined) {
                        return null;
                    }

                    return this.has(name) ? window.KTUtilElementDataStore[el.customDataTag][name] : null;
                },

                has: function(name) {
                    if (!el) {
                        return false;
                    }

                    if (el.customDataTag === undefined) {
                        return false;
                    }

                    return (window.KTUtilElementDataStore[el.customDataTag] && window.KTUtilElementDataStore[el.customDataTag][name]) ? true : false;
                },

                remove: function(name) {
                    if (el && window.KTUtilElementDataStore[el.customDataTag]) {
                        delete window.KTUtilElementDataStore[el.customDataTag][name];
                    }
                }
            }
        },

        outerWidth: function(el, margin) {
            var width;

            if (margin === true) {
                width = parseFloat(el.offsetWidth) + parseFloat(el.style.marginLeft) + parseFloat(el.style.marginRight);
            } else {
                width = parseFloat(el.offsetWidth);
            }

            return width;
        },

        outerHeight: function(el, margin) {
            var height;

            if (margin === true) {
                height = parseFloat(el.offsetHeight) + parseFloat(el.style.marginTop) + parseFloat(el.style.marginBottom);
            } else {
                height = parseFloat(el.offsetHeight);
            }

            return height;
        },

        scrollTop: function(val) {
            if (val !== undefined) {
                document.documentElement.scrollTop = val;
                document.body.scrollTop = val;
            } else {
                return (document.documentElement.scrollTop || document.body.scrollTop || 0);
            }
        },

        scrollLeft: function(val) {
            if (val !== undefined) {
                document.documentElement.scrollLeft = val;
                document.body.scrollLeft = val;
            } else {
                return (document.documentElement.scrollLeft || document.body.scrollLeft || 0);
            }
        },

        // Throttle function: Source: http://underscorejs.org/
        throttle: function(func, wait, options) {
            var context, args, result;
            var timeout = null;
            var previous = 0;
            if (!options) options = {};
            var later = function() {
                previous = options.leading === false ? 0 : Date.now();
                timeout = null;
                result = func.apply(context, args);
                if (!timeout) context = args = null;
            };
            return function() {
                var now = Date.now();
                if (!previous && options.leading === false) previous = now;
                var remaining = wait - (now - previous);
                context = this;
                args = arguments;
                if (remaining <= 0 || remaining > wait) {
                    if (timeout) {
                        clearTimeout(timeout);
                        timeout = null;
                    }
                    previous = now;
                    result = func.apply(context, args);
                    if (!timeout) context = args = null;
                } else if (!timeout && options.trailing !== false) {
                    timeout = setTimeout(later, remaining);
                }
                return result;
            };
        },

        isInViewport: function(el) {
            var rect = el.getBoundingClientRect();

            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        isOffscreen: function(el, direction, threshold) {
            threshold = threshold || 0;

            var rect = el.getBoundingClientRect();
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight;

            if (direction == 'left') {
                return (rect.left + threshold < 0);
            } else if (direction == 'right') {
                return (rect.right - threshold > viewportWidth);
            } else if (direction == 'top') {
                return (rect.top + threshold < 0);
            } else if (direction == 'bottom') {
                return (rect.bottom - threshold > viewportHeight);
            }

            return false;
        },

        fadeIn: function(el, speed, callback) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            if (el.style.display !== 'none') {
                return;
            }

            KTUtil.animate(0, 1, speed, function(p) {
                el.style.opacity = p;
            }, function() {
                el.style.display = 'block';

                if (callback) {
                    callback();
                }
            });
        },

        fadeOut: function(el, speed, callback) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            KTUtil.animate(1, 0, speed, function(p) {
                el.style.opacity = p;
            }, function() {
                el.style.display = 'none';

                if (callback) {
                    callback();
                }
            });
        },

        slide: function(el, dir, speed, callback) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            var from, to;
            if (dir == 'up') {
                from = 0;
                to = -el.offsetHeight;
            } else if (dir == 'down') {
                from = -el.offsetHeight;
                to = 0;
            } else if (dir == 'left') {
                from = 0;
                to = -el.offsetWidth;
            } else if (dir == 'right') {
                from = -el.offsetWidth;
                to = 0;
            }

            KTUtil.animate(from, to, speed, function(p) {
                if (dir == 'up' || dir == 'down') {
                    el.style.top = p + 'px';
                } else {
                    el.style.left = p + 'px';
                }
            }, function() {
                if (callback) {
                    callback();
                }
            });
        },

        slideUp: function(el, speed, callback) {
            KTUtil.slide(el, 'up', speed, callback);
        },

        slideDown: function(el, speed, callback) {
            KTUtil.slide(el, 'down', speed, callback);
        },

        slideLeft: function(el, speed, callback) {
            KTUtil.slide(el, 'left', speed, callback);
        },

        slideRight: function(el, speed, callback) {
            KTUtil.slide(el, 'right', speed, callback);
        },

        animate: function(from, to, speed, step, complete) {
            var current = from;
            var increment = (to - from) / (speed / 10);
            var timer = setInterval(function() {
                current += increment;

                if ((increment > 0 && current >= to) || (increment < 0 && current <= to)) {
                    current = to;
                    clearInterval(timer);

                    if (complete) {
                        complete();
                    }
                    return;
                }

                if (step) {
                    step(current);
                }
            }, 10);
        },

        css: function(el, styleProp, value) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            if (value !== undefined) {
                el.style[styleProp] = value;
            } else {
                var defaultView = (el.ownerDocument || document).defaultView;

                if (defaultView && defaultView.getComputedStyle) {
                    return window.getComputedStyle(el, null).getPropertyValue(styleProp);
                } else if (el.currentStyle) {
                    return el.currentStyle[styleProp];
                }
            }
        },

        attr: function(el, name, value) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            if (value !== undefined) {
                el.setAttribute(name, value);
            } else {
                return el.getAttribute(name);
            }
        },

        hasAttr: function(el, name) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            return el.hasAttribute(name);
        },

        removeAttr: function(el, name) {
            if (typeof el !== 'object') {
                el = document.getElementById(el);
            }

            el.removeAttribute(name);
        },

        animateClass: function(el, animationName, callback) {
            var animation;
            var animations = {
                shake: {
                    'transform-origin': 'center bottom'
                },
                'slide-in-up': {
                    transform: 'translateY(0)'
                },
                'slide-in-down': {
                    transform: 'translateY(0)'
                }
            };

            if (animations[animationName]) {
                KTUtil.animate(0, 1, 200, function(p) {
                    for (var key in animations[animationName]) {
                        KTUtil.css(el, key, animations[animationName][key]);
                    }
                }, function() {
                    if (callback) {
                        callback();
                    }
                });
            }
        },

        transition: function(el, classes, duration) {
            var cssClasses = classes.split(' ');
            var reflow = el.offsetHeight; // force reflow

            el.className = el.className + ' ' + cssClasses.join(' ');

            setTimeout(function() {
                el.className = el.className.replace(new RegExp(cssClasses.join('|'), 'g'), '');
            }, duration);
        },

        /**
         * Checks whether object has property matchs given key path.
         * @param {object} obj Object contains values paired with given key path
         * @param {string} keys Keys path seperated with dots
         * @returns {object}
         */
        isset: function(obj, keys) {
            var stone;

            keys = keys || '';

            if (keys.indexOf('[') !== -1) {
                throw new Error('Unsupported object path notation.');
            }

            keys = keys.split('.');

            do {
                if (obj === undefined) {
                    return false;
                }

                stone = keys.shift();

                if (!obj.hasOwnProperty(stone)) {
                    return false;
                }

                obj = obj[stone];

            } while (keys.length);

            return true;
        },

        on: function(element, selector, event, handler) {
            if (!selector) {
                return;
            }

            var eventId = KTUtil.getUniqueID('event');

            window.KTUtilDelegatedEventHandlers[eventId] = function(e) {
                var targets = element.querySelectorAll(selector);
                var target = e.target;

                while (target && target !== element) {
                    for (var i = 0, j = targets.length; i < j; i++) {
                        if (target === targets[i]) {
                            handler.call(target, e);
                        }
                    }

                    target = target.parentNode;
                }
            }

            KTUtil.addEvent(element, event, window.KTUtilDelegatedEventHandlers[eventId]);

            return eventId;
        },

        off: function(element, type, handler) {
            if (typeof element === 'string') {
                element = document.getElementById(element);
            }

            if (element) {
                element.removeEventListener(type, handler);
            }
        },

        one: function onetime(el, types, fn) {
            var typesArray = types.split(' ');

            typesArray.forEach(function(type) {
                KTUtil.on(el, type, function callee(e) {
                    // remove event
                    if (e.target && e.target.removeEventListener) {
                        e.target.removeEventListener(e.type, callee);
                    }

                    // call handler
                    return fn.call(this, e);
                });
            });
        },

        ready: function(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
            } else {
                callback();
            }
        },

        isArray: function(obj) {
            return obj && Array.isArray(obj);
        },

        each: function(arrayLike, fn) {
            if (KTUtil.isArray(arrayLike)) {
                return arrayLike.forEach(fn);
            }

            for (var i = 0; i < arrayLike.length; i++) {
                fn(arrayLike[i], i, arrayLike);
            }
        },

        get: function(selector) {
            if (typeof selector === 'string') {
                return document.querySelector(selector);
            }

            return selector;
        },

        getAll: function(selector) {
            if (typeof selector === 'string') {
                return document.querySelectorAll(selector);
            }

            return selector;
        },

        newId: function() {
            return Date.now().toString();
        },

        getUniqueID: function(prefix) {
            return prefix + Math.floor(Math.random() * (new Date()).getTime());
        },

        addEvent: function(el, type, handler, one) {
            if (typeof el !== 'undefined' && el !== null) {
                el.addEventListener(type, handler);
            }
        },
    };
}();

// Inicializar KTUtil
KTUtil.init();
