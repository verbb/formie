/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/captchas/captcha-provider.js":
/*!*********************************************!*\
  !*** ./src/js/captchas/captcha-provider.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieCaptchaProvider": () => (/* binding */ FormieCaptchaProvider)
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

var FormieCaptchaProvider = /*#__PURE__*/function () {
  function FormieCaptchaProvider() {
    _classCallCheck(this, FormieCaptchaProvider);
  }

  _createClass(FormieCaptchaProvider, [{
    key: "createInput",
    value: function createInput() {
      var $div = document.createElement('div'); // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas

      this.$placeholder.innerHTML = '';
      this.$placeholder.appendChild($div);
      return $div;
    }
  }]);

  return FormieCaptchaProvider;
}();
window.FormieCaptchaProvider = FormieCaptchaProvider;

/***/ }),

/***/ "./src/js/captchas/inc/defer.js":
/*!**************************************!*\
  !*** ./src/js/captchas/inc/defer.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var defer = function defer() {
  var state = false; // Resolved or not

  var callbacks = [];

  var resolve = function resolve(val) {
    if (state) {
      return;
    }

    state = true;

    for (var i = 0, len = callbacks.length; i < len; i++) {
      callbacks[i](val);
    }
  };

  var then = function then(cb) {
    if (!state) {
      callbacks.push(cb);
      return;
    }

    cb();
  };

  var deferred = {
    resolved: function resolved() {
      return state;
    },
    resolve: resolve,
    promise: {
      then: then
    }
  };
  return deferred;
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (defer);

/***/ }),

/***/ "./src/js/captchas/inc/turnstile.js":
/*!******************************************!*\
  !*** ./src/js/captchas/inc/turnstile.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "createTurnstile": () => (/* binding */ createTurnstile),
/* harmony export */   "turnstile": () => (/* binding */ turnstile)
/* harmony export */ });
/* harmony import */ var _defer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./defer */ "./src/js/captchas/inc/defer.js");

var ownProp = Object.prototype.hasOwnProperty;
function createTurnstile() {
  var deferred = (0,_defer__WEBPACK_IMPORTED_MODULE_0__["default"])(); // In order to handle multiple recaptchas on a page, store all renderers (promises)
  // in a central store. When reCAPTCHA is loaded, notify all promises that it's ready.

  if (!window.turnstileRenderers) {
    window.turnstileRenderers = [];
  } // Store the promise in our renderers store


  window.turnstileRenderers.push(deferred);
  return {
    notify: function notify() {
      // Be sure to notify all renderers that reCAPTCHA is ready, as soon as at least one is ready
      // As is - as soon as `window.turnstile` is available.
      for (var i = 0, len = window.turnstileRenderers.length; i < len; i++) {
        window.turnstileRenderers[i].resolve();
      }
    },
    wait: function wait() {
      return deferred.promise;
    },
    render: function render(ele, options, cb) {
      this.wait().then(function () {
        cb(window.turnstile.render(ele, options));
      });
    },
    reset: function reset(widgetId) {
      if (typeof widgetId === 'undefined') {
        return;
      }

      this.assertLoaded();
      this.wait().then(function () {
        return window.turnstile.reset(widgetId);
      });
    },
    remove: function remove(widgetId) {
      if (typeof widgetId === 'undefined') {
        return;
      }

      this.assertLoaded();
      this.wait().then(function () {
        return window.turnstile.remove(widgetId);
      });
    },
    execute: function execute(widgetId) {
      if (typeof widgetId === 'undefined') {
        return;
      }

      this.assertLoaded();
      this.wait().then(function () {
        return window.turnstile.execute(widgetId);
      });
    },
    checkCaptchaLoad: function checkCaptchaLoad() {
      if (ownProp.call(window, 'turnstile') && ownProp.call(window.turnstile, 'render')) {
        this.notify();
      }
    },
    assertLoaded: function assertLoaded() {
      if (!deferred.resolved()) {
        throw new Error('Turnstile has not been loaded');
      }
    }
  };
}
var turnstile = createTurnstile();

if (typeof window !== 'undefined') {
  window.formieTurnstileOnLoadCallback = turnstile.notify;
}

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "ensureVariable": () => (/* binding */ ensureVariable),
/* harmony export */   "eventKey": () => (/* binding */ eventKey),
/* harmony export */   "isEmpty": () => (/* binding */ isEmpty),
/* harmony export */   "t": () => (/* binding */ t),
/* harmony export */   "toBoolean": () => (/* binding */ toBoolean),
/* harmony export */   "waitForElement": () => (/* binding */ waitForElement)
/* harmony export */ });
var isEmpty = function isEmpty(obj) {
  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
};
var toBoolean = function toBoolean(val) {
  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};
var eventKey = function eventKey(eventName) {
  var namespace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

  if (!namespace) {
    namespace = Math.random().toString(36).substr(2, 5);
  }

  return "".concat(eventName, ".").concat(namespace);
};
var t = function t(string) {
  var replacements = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

  if (window.FormieTranslations) {
    string = window.FormieTranslations[string] || string;
  }

  return string.replace(/{([a-zA-Z0-9]+)}/g, function (match, p1) {
    if (replacements[p1]) {
      return replacements[p1];
    }

    return match;
  });
};
var ensureVariable = function ensureVariable(variable) {
  var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 100000;
  var start = Date.now(); // Function to allow us to wait for a global variable to be available. Useful for third-party scripts.

  var waitForVariable = function waitForVariable(resolve, reject) {
    if (window[variable]) {
      resolve(window[variable]);
    } else if (timeout && Date.now() - start >= timeout) {
      reject(new Error('timeout'));
    } else {
      setTimeout(waitForVariable.bind(this, resolve, reject), 30);
    }
  };

  return new Promise(waitForVariable);
};
var waitForElement = function waitForElement(selector, $element) {
  $element = $element || document;
  return new Promise(function (resolve) {
    if ($element.querySelector(selector)) {
      return resolve($element.querySelector(selector));
    }

    var observer = new MutationObserver(function (mutations) {
      if ($element.querySelector(selector)) {
        observer.disconnect();
        resolve($element.querySelector(selector));
      }
    });
    observer.observe($element, {
      childList: true,
      subtree: true
    });
  });
};

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**************************************!*\
  !*** ./src/js/captchas/turnstile.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieTurnstile": () => (/* binding */ FormieTurnstile)
/* harmony export */ });
/* harmony import */ var _captcha_provider__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./captcha-provider */ "./src/js/captchas/captcha-provider.js");
/* harmony import */ var _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./inc/turnstile */ "./src/js/captchas/inc/turnstile.js");
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }




var FormieTurnstile = /*#__PURE__*/function (_FormieCaptchaProvide) {
  _inherits(FormieTurnstile, _FormieCaptchaProvide);

  var _super = _createSuper(FormieTurnstile);

  function FormieTurnstile() {
    var _this;

    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieTurnstile);

    _this = _super.call(this, settings);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.siteKey = settings.siteKey;
    _this.loadingMethod = settings.loadingMethod;
    _this.turnstileScriptId = 'FORMIE_TURNSTILE_SCRIPT'; // Fetch and attach the script only once - this is in case there are multiple forms on the page.
    // They all go to a single callback which resolves its loaded state

    if (!document.getElementById(_this.turnstileScriptId)) {
      var $script = document.createElement('script');
      $script.id = _this.turnstileScriptId;
      $script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=formieTurnstileOnLoadCallback&render=explicit';

      if (_this.loadingMethod === 'async' || _this.loadingMethod === 'asyncDefer') {
        $script.async = true;
      }

      if (_this.loadingMethod === 'defer' || _this.loadingMethod === 'asyncDefer') {
        $script.defer = true;
      }

      document.body.appendChild($script);
    } // Wait for/ensure turnstile script has been loaded


    _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__.turnstile.checkCaptchaLoad(); // We can have multiple captchas per form, so store them and render only when we need

    _this.$placeholders = _this.$form.querySelectorAll('[data-turnstile-placeholder]');

    if (!_this.$placeholders) {
      console.error('Unable to find any Turnstile placeholders for [data-turnstile-placeholder]');
      return _possibleConstructorReturn(_this);
    } // Render the captcha for just this page


    _this.renderCaptcha(); // Attach a custom event listener on the form


    _this.form.addEventListener(_this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_2__.eventKey)('onFormieCaptchaValidate', 'Turnstile'), _this.onValidate.bind(_assertThisInitialized(_this)));

    _this.form.addEventListener(_this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_2__.eventKey)('onAfterFormieSubmit', 'Turnstile'), _this.onAfterSubmit.bind(_assertThisInitialized(_this)));

    return _this;
  }

  _createClass(FormieTurnstile, [{
    key: "renderCaptcha",
    value: function renderCaptcha() {
      var _this2 = this;

      this.$placeholder = null; // Get the active page

      var $currentPage = null; // Find the current page, from Formie's JS

      if (this.$form.form.formTheme) {
        // eslint-disable-next-line
        $currentPage = this.$form.form.formTheme.$currentPage;
      }

      var hasMultiplePages = this.$form.form.settings.hasMultiplePages; // Get the current page's captcha - find the first placeholder that's non-invisible

      this.$placeholders.forEach(function ($placeholder) {
        if ($currentPage && $currentPage.contains($placeholder)) {
          _this2.$placeholder = $placeholder;
        }
      }); // If a single-page form, get the first placeholder

      if (!hasMultiplePages && this.$placeholder === null) {
        // eslint-disable-next-line
        this.$placeholder = this.$placeholders[0];
      }

      if (this.$placeholder === null) {
        // This is okay in some instances - notably for multi-page forms where the captcha
        // should only be shown on the last step. But its nice to log this anyway.
        if ($currentPage === null) {
          console.log('Unable to find turnstile placeholder for [data-turnstile-placeholder]');
        }

        return;
      } // Remove any existing token input


      var $token = this.$form.querySelector('[name="cf-turnstile-response"]');

      if ($token) {
        $token.remove();
      } // Clear the submit handler (as this has been re-rendered after a successful Ajax submission)
      // as Turnstile will verify on-render and will auto-submit the form again. Because in `onVerify`
      // we have a submit handler, the form will try and submit itself, which we don't want.


      this.submitHandler = null; // Render the turnstile

      _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__.turnstile.render(this.createInput(), {
        sitekey: this.siteKey,
        callback: this.onVerify.bind(this),
        'expired-callback': this.onExpired.bind(this),
        'timeout-callback': this.onTimeout.bind(this),
        'error-callback': this.onError.bind(this),
        'close-callback': this.onClose.bind(this)
      }, function (id) {
        _this2.turnstileId = id;
      });
    }
  }, {
    key: "onValidate",
    value: function onValidate(e) {
      // When not using Formie's theme JS, there's nothing preventing the form from submitting (the theme does).
      // And when the form is submitting, we can't query DOM elements, so stop early so the normal checks work.
      if (!this.$form.form.formTheme) {
        e.preventDefault(); // Get the submit action from the form hidden input. This is normally taken care of by the theme

        this.form.submitAction = this.$form.querySelector('[name="submitAction"]').value || 'submit';
      } // Don't validate if we're not submitting (going back, saving)


      if (this.form.submitAction !== 'submit' || this.$placeholder === null) {
        return;
      } // Check if the form has an invalid flag set, don't bother going further


      if (e.detail.invalid) {
        return;
      }

      e.preventDefault(); // Save for later to trigger real submit

      this.submitHandler = e.detail.submitHandler; // Trigger turnstile

      _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__.turnstile.execute(this.turnstileId);
    }
  }, {
    key: "onVerify",
    value: function onVerify(token) {
      // Submit the form - we've hijacked it up until now
      if (this.submitHandler) {
        // Run the next submit action for the form. TODO: make this better!
        if (this.submitHandler.validatePayment()) {
          this.submitHandler.submitForm();
        }
      }
    }
  }, {
    key: "onAfterSubmit",
    value: function onAfterSubmit(e) {
      var _this3 = this;

      // For a multi-page form, we need to remove the current captcha, then render the next pages.
      // For a single-page form, reset the Turnstile, in case we want to fill out the form again
      // `renderCaptcha` will deal with both cases
      setTimeout(function () {
        _this3.renderCaptcha();
      }, 300);
    }
  }, {
    key: "onExpired",
    value: function onExpired() {
      console.log('Turnstile has expired - reloading.');
      _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__.turnstile.reset(this.turnstileId);
    }
  }, {
    key: "onTimeout",
    value: function onTimeout() {
      console.log('Turnstile has expired challenge - reloading.');
      _inc_turnstile__WEBPACK_IMPORTED_MODULE_1__.turnstile.reset(this.turnstileId);
    }
  }, {
    key: "onError",
    value: function onError(error) {
      console.error('Turnstile was unable to load');
    }
  }, {
    key: "onClose",
    value: function onClose() {
      if (this.$form.form.formTheme) {
        this.$form.form.formTheme.removeLoading();
      }
    }
  }]);

  return FormieTurnstile;
}(_captcha_provider__WEBPACK_IMPORTED_MODULE_0__.FormieCaptchaProvider);
window.FormieTurnstile = FormieTurnstile;
})();

/******/ })()
;