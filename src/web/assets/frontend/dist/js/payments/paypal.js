/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/payments/payment-provider.js":
/*!*********************************************!*\
  !*** ./src/js/payments/payment-provider.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormiePaymentProvider: () => (/* binding */ FormiePaymentProvider)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _utils_fields__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/fields */ "./src/js/utils/fields.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }


var FormiePaymentProvider = /*#__PURE__*/function () {
  function FormiePaymentProvider() {
    var _this = this;
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormiePaymentProvider);
    this.initialized = false;
    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.successClass = this.form.getClasses('success');
    this.successMessageClass = this.form.getClasses('successMessage');
    this.errorClass = this.form.getClasses('error');
    this.errorMessageClass = this.form.getClasses('errorMessage');
    this.isVisible = false;

    // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
    // and also when hidden (navigating to other pages) to destroy it.
    var observer = new IntersectionObserver(function (entries) {
      if (entries[0].intersectionRatio == 0) {
        _this.isVisible = false;

        // Only call the events if ready
        if (_this.initialized) {
          _this.onHide();
        }
      } else {
        _this.isVisible = true;

        // Only call the events if ready
        if (_this.initialized) {
          _this.onShow();
        }
      }
    }, {
      root: this.$form
    });

    // Watch for when the input is visible/hidden, in the context of the form. But wait a little to start watching
    // to prevent double binding when still loading the form, or hidden behind conditions.
    setTimeout(function () {
      observer.observe(_this.$field);
    }, 500);
  }
  _createClass(FormiePaymentProvider, [{
    key: "removeSuccess",
    value: function removeSuccess() {
      this.$field.classList.remove(this.successClass);
      var $success = this.$field.querySelector(".".concat(this.successMessageClass));
      if ($success) {
        $success.remove();
      }
    }
  }, {
    key: "addSuccess",
    value: function addSuccess(message) {
      this.$field.classList.add(this.successClass);
      var $fieldContainer = this.$field.querySelector('[data-field-type] > div');
      if (!$fieldContainer) {
        return console.error('Unable to find `[data-field-type] > div` to add success message.');
      }
      var $success = document.createElement('div');
      $success.className = this.successMessageClass;
      $success.textContent = message;
      $fieldContainer.appendChild($success);
    }
  }, {
    key: "removeError",
    value: function removeError() {
      this.$field.classList.remove(this.errorClass);
      var $error = this.$field.querySelector(".".concat(this.errorMessageClass));
      if ($error) {
        $error.remove();
      }
    }
  }, {
    key: "addError",
    value: function addError(message) {
      this.$field.classList.add(this.errorClass);
      var $fieldContainer = this.$field.querySelector('[data-field-type] > div');
      if (!$fieldContainer) {
        return console.error('Unable to find `[data-field-type] > div` to add error message.');
      }
      var $error = document.createElement('div');
      $error.className = this.errorMessageClass;
      $error.textContent = message;
      $fieldContainer.appendChild($error);
      if (this.submitHandler) {
        this.submitHandler.formSubmitError();
      }
    }
  }, {
    key: "updateInputs",
    value: function updateInputs(name, value) {
      var $input = this.$field.querySelector("[name*=\"".concat(name, "\"]"));
      if ($input) {
        $input.value = value;
      }
    }
  }, {
    key: "getBillingData",
    value: function getBillingData() {
      if (!this.billingDetails) {
        return {};
      }
      var billing = {};
      if (this.billingDetails.billingName) {
        var billingName = this.getFieldValue(this.billingDetails.billingName);
        if (billingName) {
          billing.name = billingName;
        }
      }
      if (this.billingDetails.billingEmail) {
        var billingEmail = this.getFieldValue(this.billingDetails.billingEmail);
        if (billingEmail) {
          billing.email = billingEmail;
        }
      }
      if (this.billingDetails.billingAddress) {
        billing.address = {};
        var address1 = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[address1]"));
        var address2 = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[address2]"));
        var address3 = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[address3]"));
        var city = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[city]"));
        var zip = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[zip]"));
        var state = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[state]"));
        var country = this.getFieldValue("".concat(this.billingDetails.billingAddress, "[country]"));

        /* eslint-disable camelcase */
        if (address1) {
          billing.address.line1 = address1;
        }
        if (address2) {
          billing.address.line2 = address2;
        }
        if (address3) {
          billing.address.line3 = address3;
        }
        if (city) {
          billing.address.city = city;
        }
        if (zip) {
          billing.address.postal_code = zip;
        }
        if (state) {
          billing.address.state = state;
        }
        if (country) {
          billing.address.country = country;
        }
        /* eslint-enable camelcase */
      }

      // Emit an "modifyBillingDetails" event. This can directly modify the `billing` param
      var modifyBillingDetailsEvent = new CustomEvent('modifyBillingDetails', {
        bubbles: true,
        detail: {
          provider: this,
          billing: billing
        }
      });

      // eslint-disable-next-line camelcase
      return {
        billing_details: billing
      };
    }
  }, {
    key: "getFieldValue",
    value: function getFieldValue(handle) {
      return (0,_utils_fields__WEBPACK_IMPORTED_MODULE_1__.getFieldValue)(this.$form, handle);
    }
  }, {
    key: "getFieldLabel",
    value: function getFieldLabel(handle) {
      return (0,_utils_fields__WEBPACK_IMPORTED_MODULE_1__.getFieldLabel)(this.$form, handle);
    }
  }, {
    key: "onShow",
    value: function onShow() {}
  }, {
    key: "onHide",
    value: function onHide() {}
  }]);
  return FormiePaymentProvider;
}();
window.FormiePaymentProvider = FormiePaymentProvider;

/***/ }),

/***/ "./src/js/utils/fields.js":
/*!********************************!*\
  !*** ./src/js/utils/fields.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getFieldLabel: () => (/* binding */ getFieldLabel),
/* harmony export */   getFieldName: () => (/* binding */ getFieldName),
/* harmony export */   getFieldValue: () => (/* binding */ getFieldValue),
/* harmony export */   getFormField: () => (/* binding */ getFormField)
/* harmony export */ });
var getFormField = function getFormField($form, handle) {
  // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
  var $fields = $form.querySelectorAll("[name=\"".concat(handle, "\"]"));

  // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
  var $multiFields = $form.querySelectorAll("[name=\"".concat(handle, "[]\"]"));
  if ($multiFields.length) {
    $fields = $multiFields;
  }
  return $fields;
};
var getFieldName = function getFieldName(handle) {
  // Normalise the handle first
  handle = handle.replace('{field:', '').replace('{', '').replace('}', '').replace(']', '').split('[').join('][');
  return "fields[".concat(handle, "]");
};
var getFieldLabel = function getFieldLabel($form, handle) {
  var label = '';
  handle = getFieldName(handle);

  // We'll always get back multiple inputs to normalise checkbox/radios
  var $inputs = getFormField($form, handle);
  if ($inputs) {
    $inputs.forEach(function ($input) {
      var $field = $input.closest('[data-field-type]');
      if ($field) {
        var $label = $field.querySelector('[data-field-label]');
        if ($label) {
          var _$label$childNodes$0$, _$label$childNodes$0$2;
          label = (_$label$childNodes$0$ = (_$label$childNodes$0$2 = $label.childNodes[0].textContent) === null || _$label$childNodes$0$2 === void 0 ? void 0 : _$label$childNodes$0$2.trim()) !== null && _$label$childNodes$0$ !== void 0 ? _$label$childNodes$0$ : '';
        }
      }
    });
  }
  return label;
};
var getFieldValue = function getFieldValue($form, handle) {
  var value = '';
  handle = getFieldName(handle);

  // We'll always get back multiple inputs to normalise checkbox/radios
  var $inputs = getFormField($form, handle);
  if ($inputs) {
    $inputs.forEach(function ($input) {
      if ($input.type === 'checkbox' || $input.type === 'radio') {
        if ($input.checked) {
          return value = $input.value;
        }
      } else {
        return value = $input.value;
      }
    });
  }
  return value;
};

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   clone: () => (/* binding */ clone),
/* harmony export */   debounce: () => (/* binding */ debounce),
/* harmony export */   ensureVariable: () => (/* binding */ ensureVariable),
/* harmony export */   eventKey: () => (/* binding */ eventKey),
/* harmony export */   isEmpty: () => (/* binding */ isEmpty),
/* harmony export */   t: () => (/* binding */ t),
/* harmony export */   toBoolean: () => (/* binding */ toBoolean),
/* harmony export */   waitForElement: () => (/* binding */ waitForElement)
/* harmony export */ });
var isEmpty = function isEmpty(obj) {
  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
};
var toBoolean = function toBoolean(val) {
  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};
var clone = function clone(value) {
  if (value === undefined) {
    return undefined;
  }
  return JSON.parse(JSON.stringify(value));
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
  var start = Date.now();

  // Function to allow us to wait for a global variable to be available. Useful for third-party scripts.
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
var debounce = function debounce(func, delay) {
  var timeoutId;
  return function () {
    var _this = this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    clearTimeout(timeoutId);
    timeoutId = setTimeout(function () {
      func.apply(_this, args);
    }, delay);
  };
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
/*!***********************************!*\
  !*** ./src/js/payments/paypal.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormiePayPal: () => (/* binding */ FormiePayPal)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _payment_provider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./payment-provider */ "./src/js/payments/payment-provider.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }


var FormiePayPal = /*#__PURE__*/function (_FormiePaymentProvide) {
  _inherits(FormiePayPal, _FormiePaymentProvide);
  function FormiePayPal() {
    var _this;
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormiePayPal);
    _this = _callSuper(this, FormiePayPal, [settings]);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.$field = settings.$field;
    _this.$input = _this.$field.querySelector('[data-fui-paypal-button]');
    if (!_this.$input) {
      console.error('Unable to find PayPal placeholder for [data-fui-paypal-button]');
      return _possibleConstructorReturn(_this);
    }
    _this.clientId = settings.clientId;
    _this.useSandbox = settings.useSandbox;
    _this.currency = settings.currency;
    _this.amountType = settings.amountType;
    _this.amountFixed = settings.amountFixed;
    _this.amountVariable = settings.amountVariable;
    _this.buttonLayout = settings.buttonLayout;
    _this.buttonColor = settings.buttonColor;
    _this.buttonShape = settings.buttonShape;
    _this.buttonLabel = settings.buttonLabel;
    _this.buttonTagline = settings.buttonTagline;
    _this.buttonWidth = settings.buttonWidth;
    _this.buttonHeight = settings.buttonHeight;
    _this.paypalScriptId = 'FORMIE_PAYPAL_SCRIPT';
    if (!_this.clientId) {
      console.error('Missing clientId for PayPal.');
      return _possibleConstructorReturn(_this);
    }

    // We can start listening for the field to become visible to initialize it
    _this.initialized = true;
    return _this;
  }
  _createClass(FormiePayPal, [{
    key: "onShow",
    value: function onShow() {
      // Initialize the field only when it's visible
      this.initField();
    }
  }, {
    key: "onHide",
    value: function onHide() {
      // Field is hidden, so reset everything
      this.onAfterSubmit();

      // Remove the button so it's not rendered multiple times
      this.$input.innerHTML = '';

      // Remove unique event listeners
      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'paypal'));
    }
  }, {
    key: "getScriptUrl",
    value: function getScriptUrl() {
      var url = 'https://www.paypal.com/sdk/js';
      var params = ['intent=authorize'];
      params.push("currency=".concat(this.currency));
      params.push("client-id=".concat(this.clientId));

      // Emit an "modifyQueryParams" event. This can directly modify the `params` param
      var modifyQueryParamsEvent = new CustomEvent('modifyQueryParams', {
        bubbles: true,
        detail: {
          payPal: this,
          params: params
        }
      });
      this.$field.dispatchEvent(modifyQueryParamsEvent);
      return "".concat(url, "?").concat(params.join('&'));
    }
  }, {
    key: "initField",
    value: function initField() {
      var _this2 = this;
      // Fetch and attach the script only once - this is in case there are multiple forms on the page.
      // They all go to a single callback which resolves its loaded state
      if (!document.getElementById(this.paypalScriptId)) {
        var $script = document.createElement('script');
        $script.id = this.paypalScriptId;
        $script.src = this.getScriptUrl();
        $script.async = true;
        $script.defer = true;

        // Wait until PayPal.js has loaded, then initialize
        $script.onload = function () {
          _this2.renderButton();
        };
        document.body.appendChild($script);
      } else {
        // Ensure that PayPal has been loaded and ready to use
        (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.ensureVariable)('paypal').then(function () {
          _this2.renderButton();
        });
      }

      // Attach custom event listeners on the form
      this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'paypal'), this.onAfterSubmit.bind(this));
    }
  }, {
    key: "getStyleSettings",
    value: function getStyleSettings() {
      var settings = {
        layout: this.buttonLayout,
        color: this.buttonColor,
        shape: this.buttonShape,
        label: this.buttonLabel,
        width: this.buttonWidth,
        height: this.buttonHeight
      };
      if (this.buttonLayout === 'horizontal') {
        settings.tagline = this.buttonTagline;
      }
      return settings;
    }
  }, {
    key: "renderButton",
    value: function renderButton() {
      var _this3 = this;
      var options = {
        env: this.useSandbox ? 'sandbox' : 'production',
        style: this.getStyleSettings(),
        createOrder: function createOrder(data, actions) {
          _this3.removeError();
          var amount = 0;
          if (_this3.amountType === 'fixed') {
            amount = _this3.amountFixed;
          } else if (_this3.amountType === 'dynamic') {
            amount = _this3.getFieldValue(_this3.amountVariable);
          }

          /* eslint-disable camelcase */
          return actions.order.create({
            intent: 'AUTHORIZE',
            application_context: {
              user_action: 'CONTINUE'
            },
            purchase_units: [{
              amount: {
                currency_code: _this3.currency,
                value: amount
              }
            }]
          });
          /* eslint-enable camelcase */
        },
        onCancel: function onCancel(data, actions) {},
        onError: function onError(err) {
          _this3.addError(err);
        },
        onApprove: function onApprove(data, actions) {
          // Authorize the transaction, instead of capturing. This will be done after form submit
          actions.order.authorize().then(function (authorization) {
            try {
              var authorizationID = authorization.purchase_units[0].payments.authorizations[0].id;
              _this3.updateInputs('paypalOrderId', data.orderID);
              _this3.updateInputs('paypalAuthId', authorizationID);

              // Emit an event when approved
              var onApproveEvent = new CustomEvent('onApprove', {
                bubbles: true,
                detail: {
                  payPal: _this3,
                  data: data,
                  actions: actions,
                  authorization: authorization
                }
              });

              // Allow events to bail before showing a message (commonly to auto-submit)
              if (!_this3.$field.dispatchEvent(onApproveEvent)) {
                return;
              }
              if (!authorizationID) {
                _this3.addError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Missing Authorization ID for approval.'));
              } else {
                _this3.addSuccess((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Payment authorized. Finalize the form to complete payment.'));
              }
            } catch (error) {
              console.error(error);
              _this3.addError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Unable to authorize payment. Please try again.'));
            }
          });
        }
      };

      // Emit an "beforeInit" event. This can directly modify the `options` param
      var beforeInitEvent = new CustomEvent('beforeInit', {
        bubbles: true,
        detail: {
          payPal: this,
          options: options
        }
      });
      this.$field.dispatchEvent(beforeInitEvent);
      paypal.Buttons(options).render(this.$input);
    }
  }, {
    key: "onAfterSubmit",
    value: function onAfterSubmit(e) {
      // Reset all hidden inputs
      this.updateInputs('paypalOrderId', '');
      this.updateInputs('paypalAuthId', '');
      this.removeSuccess();
      this.removeError();
    }
  }]);
  return FormiePayPal;
}(_payment_provider__WEBPACK_IMPORTED_MODULE_1__.FormiePaymentProvider);
window.FormiePayPal = FormiePayPal;
})();

/******/ })()
;