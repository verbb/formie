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
/* harmony export */   "FormiePaymentProvider": () => (/* binding */ FormiePaymentProvider)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


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
    this.isVisible = false; // Only initialize the field if it's visible. Use `IntersectionObserver` to check when visible
    // and also when hidden (navigating to other pages) to destroy it.

    var observer = new IntersectionObserver(function (entries) {
      if (entries[0].intersectionRatio == 0) {
        _this.isVisible = false; // Only call the events if ready

        if (_this.initialized) {
          _this.onHide();
        }
      } else {
        _this.isVisible = true; // Only call the events if ready

        if (_this.initialized) {
          _this.onShow();
        }
      }
    }, {
      root: this.$form
    }); // Watch for when the input is visible/hidden, in the context of the form. But wait a little to start watching
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

      } // Emit an "modifyBillingDetails" event. This can directly modify the `billing` param


      var modifyBillingDetailsEvent = new CustomEvent('modifyBillingDetails', {
        bubbles: true,
        detail: {
          provider: this,
          billing: billing
        }
      }); // eslint-disable-next-line camelcase

      return {
        billing_details: billing
      };
    }
  }, {
    key: "getFieldValue",
    value: function getFieldValue(handle) {
      var value = '';
      handle = this.getFieldName(handle); // We'll always get back multiple inputs to normalise checkbox/radios

      var $fields = this.getFormField(handle);

      if ($fields) {
        $fields.forEach(function ($field) {
          if ($field.type === 'checkbox' || $field.type === 'radio') {
            if ($field.checked) {
              return value = $field.value;
            }
          } else {
            return value = $field.value;
          }
        });
      }

      return value;
    }
  }, {
    key: "getFormField",
    value: function getFormField(handle) {
      // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
      var $fields = this.$form.querySelectorAll("[name=\"".concat(handle, "\"]")); // Check if we're dealing with multiple fields, like checkboxes. This overrides the above

      var $multiFields = this.$form.querySelectorAll("[name=\"".concat(handle, "[]\"]"));

      if ($multiFields.length) {
        $fields = $multiFields;
      }

      return $fields;
    }
  }, {
    key: "getFieldName",
    value: function getFieldName(handle) {
      // Normalise the handle first
      handle = handle.replace('{', '').replace('}', '').replace(']', '').split('[').join('][');
      return "fields[".concat(handle, "]");
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
/*!***********************************!*\
  !*** ./src/js/payments/payway.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormiePayWay": () => (/* binding */ FormiePayWay)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _payment_provider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./payment-provider */ "./src/js/payments/payment-provider.js");
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



var FormiePayWay = /*#__PURE__*/function (_FormiePaymentProvide) {
  _inherits(FormiePayWay, _FormiePaymentProvide);

  var _super = _createSuper(FormiePayWay);

  function FormiePayWay() {
    var _this;

    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormiePayWay);

    _this = _super.call(this, settings);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.$field = settings.$field;
    _this.$input = _this.$field.querySelector('[data-fui-payway-button]');

    if (!_this.$input) {
      console.error('Unable to find PayWay placeholder for [data-fui-payway-button]');
      return _possibleConstructorReturn(_this);
    }

    _this.publishableKey = settings.publishableKey;
    _this.currency = settings.currency;
    _this.amountType = settings.amountType;
    _this.amountFixed = settings.amountFixed;
    _this.amountVariable = settings.amountVariable;
    _this.paywayScriptId = 'FORMIE_PAYWAY_SCRIPT';

    if (!_this.publishableKey) {
      console.error('Missing publishableKey for PayWay.');
      return _possibleConstructorReturn(_this);
    } // We can start listening for the field to become visible to initialize it


    _this.initialized = true;
    return _this;
  }

  _createClass(FormiePayWay, [{
    key: "onShow",
    value: function onShow() {
      // Initialize the field only when it's visible
      this.initField();
    }
  }, {
    key: "onHide",
    value: function onHide() {
      // Field is hidden, so reset everything
      this.onAfterSubmit(); // Remove unique event listeners

      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormiePaymentValidate', 'payway'));
      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'payway'));
    }
  }, {
    key: "initField",
    value: function initField() {
      var _this2 = this;

      // Fetch and attach the script only once - this is in case there are multiple forms on the page.
      // They all go to a single callback which resolves its loaded state
      if (!document.getElementById(this.paywayScriptId)) {
        var $script = document.createElement('script');
        $script.id = this.paywayScriptId;
        $script.src = 'https://api.payway.com.au/rest/v1/payway.js';
        $script.async = true;
        $script.defer = true; // Wait until PayWay.js has loaded, then initialize

        $script.onload = function () {
          _this2.mountCard();
        };

        document.body.appendChild($script);
      } else {
        // Ensure that PayWay has been loaded and ready to use
        (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.ensureVariable)('payway').then(function () {
          _this2.mountCard();
        });
      } // Attach custom event listeners on the form


      this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormiePaymentValidate', 'payway'), this.onValidate.bind(this));
      this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'payway'), this.onAfterSubmit.bind(this));
    }
  }, {
    key: "mountCard",
    value: function mountCard() {
      var _this3 = this;

      payway.createCreditCardFrame({
        layout: 'wide',
        publishableApiKey: this.publishableKey,
        tokenMode: 'callback'
      }, function (err, frame) {
        if (err) {
          console.error("Error creating frame: ".concat(err.message));
        } else {
          // Save the created frame for when we get the token
          _this3.creditCardFrame = frame;
        }
      });
    }
  }, {
    key: "onValidate",
    value: function onValidate(e) {
      var _this4 = this;

      // Don't validate if we're not submitting (going back, saving)
      // Check if the form has an invalid flag set, don't bother going further
      if (this.form.submitAction !== 'submit' || e.detail.invalid) {
        return;
      }

      e.preventDefault(); // Save for later to trigger real submit

      this.submitHandler = e.detail.submitHandler;
      this.removeError();

      if (this.creditCardFrame) {
        this.creditCardFrame.getToken(function (err, data) {
          if (err) {
            console.error("Error getting token: ".concat(err.message));

            _this4.addError(err.message);
          } else {
            // Append an input so it's not namespaced with Twig
            _this4.updateInputs('paywayTokenId', data.singleUseTokenId);

            _this4.submitHandler.submitForm();
          }
        });
      } else {
        console.error('Credit Card Frame is invalid.');
      }
    }
  }, {
    key: "onAfterSubmit",
    value: function onAfterSubmit(e) {
      // Clear the form
      if (this.creditCardFrame) {
        this.creditCardFrame.destroy();
        this.creditCardFrame = null;
      } // Reset all hidden inputs


      this.updateInputs('paywayTokenId', '');
    }
  }]);

  return FormiePayWay;
}(_payment_provider__WEBPACK_IMPORTED_MODULE_1__.FormiePaymentProvider);
window.FormiePayWay = FormiePayWay;
})();

/******/ })()
;