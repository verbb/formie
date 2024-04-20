/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../../../../node_modules/@rynpsc/dialog/dist/module/dialog.js":
/*!*********************************************************************!*\
  !*** ../../../../node_modules/@rynpsc/dialog/dist/module/dialog.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   dialog: () => (/* binding */ m),
/* harmony export */   domapi: () => (/* binding */ u),
/* harmony export */   getInstanceById: () => (/* binding */ f),
/* harmony export */   instances: () => (/* binding */ s)
/* harmony export */ });
const e=["input","select","textarea","button","summary","a[href]","area[href]","embed","iframe","object","audio[controls]","video[controls]",'[contenteditable]:not([contenteditable="false"])'];function t(e,t=!1){"function"==typeof e.focus&&e.focus({preventScroll:!t})}function n(e,n=!1){const r=o(e);if(!r.length)return!1;t(r[0],n)}function o(t){return Array.from(t.querySelectorAll(e.join(","))).filter(t=>function(t){let n=parseInt(t.getAttribute("tabindex")||"",10);if(t.hidden||n&&n<0||t.matches(":disabled")||!t.matches(e.join(","))||r(t)&&!function(e){if(!r(e))return!1;let t=e.form||document;if(!Array.from(t.getElementsByName(e.name)).some(e=>e.checked))return!0;return e.checked}(t)||!function(e){if(0===e.getClientRects().length)return!1;return"visible"===window.getComputedStyle(e).visibility}(t))return!1;return!0}(t))}function r(e){return e instanceof HTMLInputElement&&"radio"===e.type}let i,a;function l(e){let{currentTarget:t}=e;if(!(t instanceof HTMLElement&&t.dataset.dialogOpen))return;let n=f(t.dataset.dialogOpen);n&&(n.open(),e.preventDefault())}function c(e){let{currentTarget:t}=e;if(!(t instanceof HTMLElement&&t.dataset.dialogClose))return;let n=f(t.dataset.dialogClose);n&&(n.close(),e.preventDefault())}var u=Object.freeze({__proto__:null,get openers(){return i},get closers(){return a},mount:function(){i=Array.from(document.querySelectorAll("[data-dialog-open]")),a=Array.from(document.querySelectorAll("[data-dialog-close]")),i.forEach(e=>e.addEventListener("click",l)),a.forEach(e=>e.addEventListener("click",c))},unmount:function(){i=[],a=[],i.forEach(e=>e.removeEventListener("click",l)),a.forEach(e=>e.removeEventListener("click",c))}});const d={alert:!1,description:void 0,label:"Dialog",manageFocus:!0,openClass:"is-open"},s={};function f(e){return void 0===e?null:Object.prototype.hasOwnProperty.call(s,e)?s[e]:null}function m(e,r={}){let i,a=!1,l=!1,c=document.getElementById(e);if(!c)return;if(s.hasOwnProperty(e))return s[e];const u=Object.assign({},d,r);function f(e){"Escape"===e.key&&v()}function m(e,t=!0){!a&&l&&b("open")&&(a=!0,c.classList.add(u.openClass),document.addEventListener("keydown",f,!0),i&&(void 0===e&&null!==c.querySelector("[data-dialog-autofocus]")&&(e=c.querySelector("[data-dialog-autofocus]")),i.activate(e,t)))}function v(e,t=!0){a&&l&&b("close")&&(i&&i.deactivate(e,t),a=!1,document.removeEventListener("keydown",f,!0),c.classList.remove(u.openClass))}function b(t){let n=new CustomEvent("dialog:"+t,{bubbles:!0,cancelable:!0,detail:{id:e}});return c.dispatchEvent(n)}u.manageFocus&&(i=function(e){let r=!1,i=null,a=null;function l(o){let r=o.target;if(e.contains(r))return o.stopPropagation(),void(i=r);o.preventDefault(),i?t(i,!0):n(e,!0)}function c(n){"Tab"!==n.key||n.altKey||n.ctrlKey||n.metaKey||function(e,n){let r=document.activeElement,i=o(e),a=i[0],l=i[i.length-1];n.shiftKey&&r==a?(t(l,!0),n.preventDefault()):n.shiftKey||r!=l||(t(a,!0),n.preventDefault())}(e,n)}return{activated:r,activate:function(o,i=!1){r||(a=document.activeElement,o?t(o,i):null!==o&&n(e,i),document.addEventListener("focusin",l,!1),document.addEventListener("keydown",c,!1),r=!0)},deactivate:function(e,n=!1){if(!r)return;document.removeEventListener("focusin",l,!1),document.removeEventListener("keydown",c,!1);let o=e||(null!==e?a:null);o&&t(o,n),r=!1,i=null}}}(c));const p={close:v,create:function(){if(l)return!1;let e=u.alert?"alertdialog":"dialog";if(c.setAttribute("tabindex","-1"),c.setAttribute("role",e),c.setAttribute("aria-modal","true"),u.label){let e=document.getElementById(u.label)?"aria-labelledby":"aria-label";c.setAttribute(e,u.label)}u.description&&document.getElementById(u.description)&&c.setAttribute("aria-describedby",u.description),l=!0,b("create")},destroy:function(){if(!l)return!1;v(),l=!1,["role","tabindex","aria-modal","aria-label","aria-labelledby","aria-describedby"].forEach(e=>c.removeAttribute(e)),b("destroy")},element:c,id:e,initiated:l,get isOpen(){return a},off:function(e,t){let n="dialog:"+e;c.removeEventListener(n,t)},on:function(e,t){let n="dialog:"+e;c.addEventListener(n,t)},open:m,toggle:function(e=!a){e?m():v()}};return s[e]=p}
//# sourceMappingURL=dialog.js.map


/***/ }),

/***/ "./src/js/payments/payment-provider.js":
/*!*********************************************!*\
  !*** ./src/js/payments/payment-provider.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

"use strict";
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

"use strict";
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

/***/ }),

/***/ "../../../../node_modules/globalthis/implementation.browser.js":
/*!*********************************************************************!*\
  !*** ../../../../node_modules/globalthis/implementation.browser.js ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
/* eslint no-negated-condition: 0, no-new-func: 0 */



if (typeof self !== 'undefined') {
	module.exports = self;
} else if (typeof window !== 'undefined') {
	module.exports = window;
} else {
	module.exports = Function('return this')();
}


/***/ }),

/***/ "../../../../node_modules/globalthis/polyfill.js":
/*!*******************************************************!*\
  !*** ../../../../node_modules/globalthis/polyfill.js ***!
  \*******************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


var implementation = __webpack_require__(/*! ./implementation */ "../../../../node_modules/globalthis/implementation.browser.js");

module.exports = function getPolyfill() {
	if (typeof __webpack_require__.g !== 'object' || !__webpack_require__.g || __webpack_require__.g.Math !== Math || __webpack_require__.g.Array !== Array) {
		return implementation;
	}
	return __webpack_require__.g;
};


/***/ }),

/***/ "../../../../node_modules/payment/lib/index.js":
/*!*****************************************************!*\
  !*** ../../../../node_modules/payment/lib/index.js ***!
  \*****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

// Generated by CoffeeScript 1.12.7
(function() {
  var Payment, QJ, cardFromNumber, cardFromType, cards, cursorSafeAssignValue, defaultFormat, formatBackCardNumber, formatBackExpiry, formatCardNumber, formatExpiry, formatForwardExpiry, formatForwardSlash, formatMonthExpiry, globalThis, hasTextSelected, luhnCheck, reFormatCardNumber, restrictCVC, restrictCardNumber, restrictCombinedExpiry, restrictExpiry, restrictMonthExpiry, restrictNumeric, restrictYearExpiry, setCardType,
    indexOf = [].indexOf || function(item) { for (var i = 0, l = this.length; i < l; i++) { if (i in this && this[i] === item) return i; } return -1; };

  globalThis = __webpack_require__(/*! globalthis/polyfill */ "../../../../node_modules/globalthis/polyfill.js")();

  QJ = __webpack_require__(/*! qj */ "../../../../node_modules/qj/lib/index.js");

  defaultFormat = /(\d{1,4})/g;

  cards = [
    {
      type: 'amex',
      pattern: /^3[47]/,
      format: /(\d{1,4})(\d{1,6})?(\d{1,5})?/,
      length: [15],
      cvcLength: [4],
      luhn: true
    }, {
      type: 'dankort',
      pattern: /^5019/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'dinersclub',
      pattern: /^(36|38|30[0-5])/,
      format: /(\d{1,4})(\d{1,6})?(\d{1,4})?/,
      length: [14],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'discover',
      pattern: /^(6011|65|64[4-9]|622)/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'elo',
      pattern: /^401178|^401179|^431274|^438935|^451416|^457393|^457631|^457632|^504175|^627780|^636297|^636369|^636368|^(506699|5067[0-6]\d|50677[0-8])|^(50900\d|5090[1-9]\d|509[1-9]\d{2})|^65003[1-3]|^(65003[5-9]|65004\d|65005[0-1])|^(65040[5-9]|6504[1-3]\d)|^(65048[5-9]|65049\d|6505[0-2]\d|65053[0-8])|^(65054[1-9]|6505[5-8]\d|65059[0-8])|^(65070\d|65071[0-8])|^65072[0-7]|^(65090[1-9]|65091\d|650920)|^(65165[2-9]|6516[6-7]\d)|^(65500\d|65501\d)|^(65502[1-9]|6550[3-4]\d|65505[0-8])|^(65092[1-9]|65097[0-8])/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'hipercard',
      pattern: /^(384100|384140|384160|606282|637095|637568|60(?!11))/,
      format: defaultFormat,
      length: [14, 15, 16, 17, 18, 19],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'jcb',
      pattern: /^(308[8-9]|309[0-3]|3094[0]{4}|309[6-9]|310[0-2]|311[2-9]|3120|315[8-9]|333[7-9]|334[0-9]|35)/,
      format: defaultFormat,
      length: [16, 19],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'laser',
      pattern: /^(6706|6771|6709)/,
      format: defaultFormat,
      length: [16, 17, 18, 19],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'maestro',
      pattern: /^(50|5[6-9]|6007|6220|6304|6703|6708|6759|676[1-3])/,
      format: defaultFormat,
      length: [12, 13, 14, 15, 16, 17, 18, 19],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'mastercard',
      pattern: /^(5[1-5]|677189)|^(222[1-9]|2[3-6]\d{2}|27[0-1]\d|2720)/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'mir',
      pattern: /^220[0-4][0-9][0-9]\d{10}$/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'troy',
      pattern: /^9792/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'unionpay',
      pattern: /^62/,
      format: defaultFormat,
      length: [16, 17, 18, 19],
      cvcLength: [3],
      luhn: false
    }, {
      type: 'visaelectron',
      pattern: /^4(026|17500|405|508|844|91[37])/,
      format: defaultFormat,
      length: [16],
      cvcLength: [3],
      luhn: true
    }, {
      type: 'visa',
      pattern: /^4/,
      format: defaultFormat,
      length: [13, 16],
      cvcLength: [3],
      luhn: true
    }
  ];

  cardFromNumber = function(num) {
    var card, foundCard, j, len, match;
    num = (num + '').replace(/\D/g, '');
    foundCard = void 0;
    for (j = 0, len = cards.length; j < len; j++) {
      card = cards[j];
      if (match = num.match(card.pattern)) {
        if (!foundCard || match[0].length > foundCard[1][0].length) {
          foundCard = [card, match];
        }
      }
    }
    return foundCard && foundCard[0];
  };

  cardFromType = function(type) {
    var card, j, len;
    for (j = 0, len = cards.length; j < len; j++) {
      card = cards[j];
      if (card.type === type) {
        return card;
      }
    }
  };

  luhnCheck = function(num) {
    var digit, digits, j, len, odd, sum;
    odd = true;
    sum = 0;
    digits = (num + '').split('').reverse();
    for (j = 0, len = digits.length; j < len; j++) {
      digit = digits[j];
      digit = parseInt(digit, 10);
      if ((odd = !odd)) {
        digit *= 2;
      }
      if (digit > 9) {
        digit -= 9;
      }
      sum += digit;
    }
    return sum % 10 === 0;
  };

  hasTextSelected = function(target) {
    var e, ref;
    try {
      if ((target.selectionStart != null) && target.selectionStart !== target.selectionEnd) {
        return true;
      }
      if ((typeof document !== "undefined" && document !== null ? (ref = document.selection) != null ? ref.createRange : void 0 : void 0) != null) {
        if (document.selection.createRange().text) {
          return true;
        }
      }
    } catch (error) {
      e = error;
    }
    return false;
  };

  reFormatCardNumber = function(e) {
    return setTimeout((function(_this) {
      return function() {
        var target, value;
        target = e.target;
        value = QJ.val(target);
        value = Payment.fns.formatCardNumber(value);
        cursorSafeAssignValue(target, value);
        return QJ.trigger(target, 'change');
      };
    })(this));
  };

  formatCardNumber = function(maxLength) {
    return function(e) {
      var card, digit, i, j, len, length, re, target, upperLength, upperLengths, value;
      if (e.which > 0) {
        digit = String.fromCharCode(e.which);
        value = QJ.val(e.target) + digit;
      } else {
        digit = e.data;
        value = QJ.val(e.target);
      }
      if (!/^\d+$/.test(digit)) {
        return;
      }
      target = e.target;
      card = cardFromNumber(value);
      length = (value.replace(/\D/g, '')).length;
      upperLengths = [16];
      if (card) {
        upperLengths = card.length;
      }
      if (maxLength) {
        upperLengths = upperLengths.filter(function(x) {
          return x <= maxLength;
        });
      }
      for (i = j = 0, len = upperLengths.length; j < len; i = ++j) {
        upperLength = upperLengths[i];
        if (length >= upperLength && upperLengths[i + 1]) {
          continue;
        }
        if (length >= upperLength) {
          return;
        }
      }
      if (hasTextSelected(target)) {
        return;
      }
      if (card && card.type === 'amex') {
        re = /^(\d{4}|\d{4}\s\d{6})$/;
      } else {
        re = /(?:^|\s)(\d{4})$/;
      }
      value = value.substring(0, value.length - 1);
      if (re.test(value)) {
        e.preventDefault();
        QJ.val(target, value + ' ' + digit);
        return QJ.trigger(target, 'change');
      }
    };
  };

  formatBackCardNumber = function(e) {
    var target, value;
    target = e.target;
    value = QJ.val(target);
    if (e.meta) {
      return;
    }
    if (e.which !== 8) {
      return;
    }
    if (hasTextSelected(target)) {
      return;
    }
    if (/\d\s$/.test(value)) {
      e.preventDefault();
      QJ.val(target, value.replace(/\d\s$/, ''));
      return QJ.trigger(target, 'change');
    } else if (/\s\d?$/.test(value)) {
      e.preventDefault();
      QJ.val(target, value.replace(/\s\d?$/, ''));
      return QJ.trigger(target, 'change');
    }
  };

  formatExpiry = function(e) {
    var digit, target, val;
    target = e.target;
    if (e.which > 0) {
      digit = String.fromCharCode(e.which);
      val = QJ.val(target) + digit;
    } else {
      digit = e.data;
      val = QJ.val(target);
    }
    if (!/^\d+$/.test(digit)) {
      return;
    }
    if (/^\d$/.test(val) && (val !== '0' && val !== '1')) {
      e.preventDefault();
      QJ.val(target, "0" + val + " / ");
      return QJ.trigger(target, 'change');
    } else if (/^\d\d$/.test(val)) {
      e.preventDefault();
      QJ.val(target, val + " / ");
      return QJ.trigger(target, 'change');
    }
  };

  formatMonthExpiry = function(e) {
    var digit, target, val;
    digit = String.fromCharCode(e.which);
    if (!/^\d+$/.test(digit)) {
      return;
    }
    target = e.target;
    val = QJ.val(target) + digit;
    if (/^\d$/.test(val) && (val !== '0' && val !== '1')) {
      e.preventDefault();
      QJ.val(target, "0" + val);
      return QJ.trigger(target, 'change');
    } else if (/^\d\d$/.test(val)) {
      e.preventDefault();
      QJ.val(target, "" + val);
      return QJ.trigger(target, 'change');
    }
  };

  formatForwardExpiry = function(e) {
    var digit, target, val;
    digit = String.fromCharCode(e.which);
    if (!/^\d+$/.test(digit)) {
      return;
    }
    target = e.target;
    val = QJ.val(target);
    if (/^\d\d$/.test(val)) {
      QJ.val(target, val + " / ");
      return QJ.trigger(target, 'change');
    }
  };

  formatForwardSlash = function(e) {
    var slash, target, val;
    slash = String.fromCharCode(e.which);
    if (slash !== '/') {
      return;
    }
    target = e.target;
    val = QJ.val(target);
    if (/^\d$/.test(val) && val !== '0') {
      QJ.val(target, "0" + val + " / ");
      return QJ.trigger(target, 'change');
    }
  };

  formatBackExpiry = function(e) {
    var target, value;
    if (e.metaKey) {
      return;
    }
    target = e.target;
    value = QJ.val(target);
    if (e.which !== 8) {
      return;
    }
    if (hasTextSelected(target)) {
      return;
    }
    if (/\d(\s|\/)+$/.test(value)) {
      e.preventDefault();
      QJ.val(target, value.replace(/\d(\s|\/)*$/, ''));
      return QJ.trigger(target, 'change');
    } else if (/\s\/\s?\d?$/.test(value)) {
      e.preventDefault();
      QJ.val(target, value.replace(/\s\/\s?\d?$/, ''));
      return QJ.trigger(target, 'change');
    }
  };

  restrictNumeric = function(e) {
    var input;
    if (e.metaKey || e.ctrlKey) {
      return true;
    }
    if (e.which === 32) {
      return e.preventDefault();
    }
    if (e.which === 0) {
      return true;
    }
    if (e.which < 33) {
      return true;
    }
    input = String.fromCharCode(e.which);
    if (!/[\d\s]/.test(input)) {
      return e.preventDefault();
    }
  };

  restrictCardNumber = function(maxLength) {
    return function(e) {
      var card, digit, length, target, value;
      target = e.target;
      digit = String.fromCharCode(e.which);
      if (!/^\d+$/.test(digit)) {
        return;
      }
      if (hasTextSelected(target)) {
        return;
      }
      value = (QJ.val(target) + digit).replace(/\D/g, '');
      card = cardFromNumber(value);
      length = 16;
      if (card) {
        length = card.length[card.length.length - 1];
      }
      if (maxLength) {
        length = Math.min(length, maxLength);
      }
      if (!(value.length <= length)) {
        return e.preventDefault();
      }
    };
  };

  restrictExpiry = function(e, length) {
    var digit, target, value;
    target = e.target;
    digit = String.fromCharCode(e.which);
    if (!/^\d+$/.test(digit)) {
      return;
    }
    if (hasTextSelected(target)) {
      return;
    }
    value = QJ.val(target) + digit;
    value = value.replace(/\D/g, '');
    if (value.length > length) {
      return e.preventDefault();
    }
  };

  restrictCombinedExpiry = function(e) {
    return restrictExpiry(e, 6);
  };

  restrictMonthExpiry = function(e) {
    return restrictExpiry(e, 2);
  };

  restrictYearExpiry = function(e) {
    return restrictExpiry(e, 4);
  };

  restrictCVC = function(e) {
    var digit, target, val;
    target = e.target;
    digit = String.fromCharCode(e.which);
    if (!/^\d+$/.test(digit)) {
      return;
    }
    if (hasTextSelected(target)) {
      return;
    }
    val = QJ.val(target) + digit;
    if (!(val.length <= 4)) {
      return e.preventDefault();
    }
  };

  setCardType = function(e) {
    var allTypes, card, cardType, target, val;
    target = e.target;
    val = QJ.val(target);
    cardType = Payment.fns.cardType(val) || 'unknown';
    if (!QJ.hasClass(target, cardType)) {
      allTypes = (function() {
        var j, len, results;
        results = [];
        for (j = 0, len = cards.length; j < len; j++) {
          card = cards[j];
          results.push(card.type);
        }
        return results;
      })();
      QJ.removeClass(target, 'unknown');
      QJ.removeClass(target, allTypes.join(' '));
      QJ.addClass(target, cardType);
      QJ.toggleClass(target, 'identified', cardType !== 'unknown');
      return QJ.trigger(target, 'payment.cardType', cardType);
    }
  };

  cursorSafeAssignValue = function(target, value) {
    var selectionEnd;
    selectionEnd = target.selectionEnd;
    QJ.val(target, value);
    if (selectionEnd) {
      return target.selectionEnd = selectionEnd;
    }
  };

  Payment = (function() {
    function Payment() {}

    Payment.J = QJ;

    Payment.fns = {
      cardExpiryVal: function(value) {
        var month, prefix, ref, year;
        value = value.replace(/\s/g, '');
        ref = value.split('/', 2), month = ref[0], year = ref[1];
        if ((year != null ? year.length : void 0) === 2 && /^\d+$/.test(year)) {
          prefix = (new Date).getFullYear();
          prefix = prefix.toString().slice(0, 2);
          year = prefix + year;
        }
        month = parseInt(month, 10);
        year = parseInt(year, 10);
        return {
          month: month,
          year: year
        };
      },
      validateCardNumber: function(num) {
        var card, ref;
        num = (num + '').replace(/\s+|-/g, '');
        if (!/^\d+$/.test(num)) {
          return false;
        }
        card = cardFromNumber(num);
        if (!card) {
          return false;
        }
        return (ref = num.length, indexOf.call(card.length, ref) >= 0) && (card.luhn === false || luhnCheck(num));
      },
      validateCardExpiry: function(month, year) {
        var currentTime, expiry, prefix, ref, ref1;
        if (typeof month === 'object' && 'month' in month) {
          ref = month, month = ref.month, year = ref.year;
        } else if (typeof month === 'string' && indexOf.call(month, '/') >= 0) {
          ref1 = Payment.fns.cardExpiryVal(month), month = ref1.month, year = ref1.year;
        }
        if (!(month && year)) {
          return false;
        }
        month = QJ.trim(month);
        year = QJ.trim(year);
        if (!/^\d+$/.test(month)) {
          return false;
        }
        if (!/^\d+$/.test(year)) {
          return false;
        }
        month = parseInt(month, 10);
        if (!(month && month <= 12)) {
          return false;
        }
        if (year.length === 2) {
          prefix = (new Date).getFullYear();
          prefix = prefix.toString().slice(0, 2);
          year = prefix + year;
        }
        expiry = new Date(year, month);
        currentTime = new Date;
        expiry.setMonth(expiry.getMonth() - 1);
        expiry.setMonth(expiry.getMonth() + 1, 1);
        return expiry > currentTime;
      },
      validateCardCVC: function(cvc, type) {
        var ref, ref1;
        cvc = QJ.trim(cvc);
        if (!/^\d+$/.test(cvc)) {
          return false;
        }
        if (type && cardFromType(type)) {
          return ref = cvc.length, indexOf.call((ref1 = cardFromType(type)) != null ? ref1.cvcLength : void 0, ref) >= 0;
        } else {
          return cvc.length >= 3 && cvc.length <= 4;
        }
      },
      cardType: function(num) {
        var ref;
        if (!num) {
          return null;
        }
        return ((ref = cardFromNumber(num)) != null ? ref.type : void 0) || null;
      },
      formatCardNumber: function(num) {
        var card, groups, ref, upperLength;
        card = cardFromNumber(num);
        if (!card) {
          return num;
        }
        upperLength = card.length[card.length.length - 1];
        num = num.replace(/\D/g, '');
        num = num.slice(0, upperLength);
        if (card.format.global) {
          return (ref = num.match(card.format)) != null ? ref.join(' ') : void 0;
        } else {
          groups = card.format.exec(num);
          if (groups == null) {
            return;
          }
          groups.shift();
          groups = groups.filter(function(n) {
            return n;
          });
          return groups.join(' ');
        }
      }
    };

    Payment.restrictNumeric = function(el) {
      QJ.on(el, 'keypress', restrictNumeric);
      return QJ.on(el, 'input', restrictNumeric);
    };

    Payment.cardExpiryVal = function(el) {
      return Payment.fns.cardExpiryVal(QJ.val(el));
    };

    Payment.formatCardCVC = function(el) {
      Payment.restrictNumeric(el);
      QJ.on(el, 'keypress', restrictCVC);
      QJ.on(el, 'input', restrictCVC);
      return el;
    };

    Payment.formatCardExpiry = function(el) {
      var month, year;
      Payment.restrictNumeric(el);
      if (el.length && el.length === 2) {
        month = el[0], year = el[1];
        this.formatCardExpiryMultiple(month, year);
      } else {
        QJ.on(el, 'keypress', restrictCombinedExpiry);
        QJ.on(el, 'keypress', formatExpiry);
        QJ.on(el, 'keypress', formatForwardSlash);
        QJ.on(el, 'keypress', formatForwardExpiry);
        QJ.on(el, 'keydown', formatBackExpiry);
        QJ.on(el, 'input', formatExpiry);
      }
      return el;
    };

    Payment.formatCardExpiryMultiple = function(month, year) {
      QJ.on(month, 'keypress', restrictMonthExpiry);
      QJ.on(month, 'keypress', formatMonthExpiry);
      QJ.on(month, 'input', formatMonthExpiry);
      QJ.on(year, 'keypress', restrictYearExpiry);
      return QJ.on(year, 'input', restrictYearExpiry);
    };

    Payment.formatCardNumber = function(el, maxLength) {
      Payment.restrictNumeric(el);
      QJ.on(el, 'keypress', restrictCardNumber(maxLength));
      QJ.on(el, 'keypress', formatCardNumber(maxLength));
      QJ.on(el, 'keydown', formatBackCardNumber);
      QJ.on(el, 'keyup blur', setCardType);
      QJ.on(el, 'blur', formatCardNumber(maxLength));
      QJ.on(el, 'paste', reFormatCardNumber);
      QJ.on(el, 'input', formatCardNumber(maxLength));
      return el;
    };

    Payment.getCardArray = function() {
      return cards;
    };

    Payment.setCardArray = function(cardArray) {
      cards = cardArray;
      return true;
    };

    Payment.addToCardArray = function(cardObject) {
      return cards.push(cardObject);
    };

    Payment.removeFromCardArray = function(type) {
      var key, value;
      for (key in cards) {
        value = cards[key];
        if (value.type === type) {
          cards.splice(key, 1);
        }
      }
      return true;
    };

    return Payment;

  })();

  module.exports = Payment;

  globalThis.Payment = Payment;

}).call(this);


/***/ }),

/***/ "../../../../node_modules/qj/lib/index.js":
/*!************************************************!*\
  !*** ../../../../node_modules/qj/lib/index.js ***!
  \************************************************/
/***/ (function(module) {

// Generated by CoffeeScript 1.10.0
(function() {
  var QJ, rreturn, rtrim;

  QJ = function(selector) {
    if (QJ.isDOMElement(selector)) {
      return selector;
    }
    return document.querySelectorAll(selector);
  };

  QJ.isDOMElement = function(el) {
    return el && (el.nodeName != null);
  };

  rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;

  QJ.trim = function(text) {
    if (text === null) {
      return "";
    } else {
      return (text + "").replace(rtrim, "");
    }
  };

  rreturn = /\r/g;

  QJ.val = function(el, val) {
    var ret;
    if (arguments.length > 1) {
      return el.value = val;
    } else {
      ret = el.value;
      if (typeof ret === "string") {
        return ret.replace(rreturn, "");
      } else {
        if (ret === null) {
          return "";
        } else {
          return ret;
        }
      }
    }
  };

  QJ.preventDefault = function(eventObject) {
    if (typeof eventObject.preventDefault === "function") {
      eventObject.preventDefault();
      return;
    }
    eventObject.returnValue = false;
    return false;
  };

  QJ.normalizeEvent = function(e) {
    var original;
    original = e;
    e = {
      which: original.which != null ? original.which : void 0,
      target: original.target || original.srcElement,
      preventDefault: function() {
        return QJ.preventDefault(original);
      },
      originalEvent: original,
      data: original.data || original.detail
    };
    if (e.which == null) {
      e.which = original.charCode != null ? original.charCode : original.keyCode;
    }
    return e;
  };

  QJ.on = function(element, eventName, callback) {
    var el, i, j, len, len1, multEventName, originalCallback, ref;
    if (element.length) {
      for (i = 0, len = element.length; i < len; i++) {
        el = element[i];
        QJ.on(el, eventName, callback);
      }
      return;
    }
    if (eventName.match(" ")) {
      ref = eventName.split(" ");
      for (j = 0, len1 = ref.length; j < len1; j++) {
        multEventName = ref[j];
        QJ.on(element, multEventName, callback);
      }
      return;
    }
    originalCallback = callback;
    callback = function(e) {
      e = QJ.normalizeEvent(e);
      return originalCallback(e);
    };
    if (element.addEventListener) {
      return element.addEventListener(eventName, callback, false);
    }
    if (element.attachEvent) {
      eventName = "on" + eventName;
      return element.attachEvent(eventName, callback);
    }
    element['on' + eventName] = callback;
  };

  QJ.addClass = function(el, className) {
    var e;
    if (el.length) {
      return (function() {
        var i, len, results;
        results = [];
        for (i = 0, len = el.length; i < len; i++) {
          e = el[i];
          results.push(QJ.addClass(e, className));
        }
        return results;
      })();
    }
    if (el.classList) {
      return el.classList.add(className);
    } else {
      return el.className += ' ' + className;
    }
  };

  QJ.hasClass = function(el, className) {
    var e, hasClass, i, len;
    if (el.length) {
      hasClass = true;
      for (i = 0, len = el.length; i < len; i++) {
        e = el[i];
        hasClass = hasClass && QJ.hasClass(e, className);
      }
      return hasClass;
    }
    if (el.classList) {
      return el.classList.contains(className);
    } else {
      return new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
    }
  };

  QJ.removeClass = function(el, className) {
    var cls, e, i, len, ref, results;
    if (el.length) {
      return (function() {
        var i, len, results;
        results = [];
        for (i = 0, len = el.length; i < len; i++) {
          e = el[i];
          results.push(QJ.removeClass(e, className));
        }
        return results;
      })();
    }
    if (el.classList) {
      ref = className.split(' ');
      results = [];
      for (i = 0, len = ref.length; i < len; i++) {
        cls = ref[i];
        results.push(el.classList.remove(cls));
      }
      return results;
    } else {
      return el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
    }
  };

  QJ.toggleClass = function(el, className, bool) {
    var e;
    if (el.length) {
      return (function() {
        var i, len, results;
        results = [];
        for (i = 0, len = el.length; i < len; i++) {
          e = el[i];
          results.push(QJ.toggleClass(e, className, bool));
        }
        return results;
      })();
    }
    if (bool) {
      if (!QJ.hasClass(el, className)) {
        return QJ.addClass(el, className);
      }
    } else {
      return QJ.removeClass(el, className);
    }
  };

  QJ.append = function(el, toAppend) {
    var e;
    if (el.length) {
      return (function() {
        var i, len, results;
        results = [];
        for (i = 0, len = el.length; i < len; i++) {
          e = el[i];
          results.push(QJ.append(e, toAppend));
        }
        return results;
      })();
    }
    return el.insertAdjacentHTML('beforeend', toAppend);
  };

  QJ.find = function(el, selector) {
    if (el instanceof NodeList || el instanceof Array) {
      el = el[0];
    }
    return el.querySelectorAll(selector);
  };

  QJ.trigger = function(el, name, data) {
    var e, error, ev;
    try {
      ev = new CustomEvent(name, {
        detail: data
      });
    } catch (error) {
      e = error;
      ev = document.createEvent('CustomEvent');
      if (ev.initCustomEvent) {
        ev.initCustomEvent(name, true, true, data);
      } else {
        ev.initEvent(name, true, true, data);
      }
    }
    return el.dispatchEvent(ev);
  };

  module.exports = QJ;

}).call(this);


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
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
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
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!**********************************!*\
  !*** ./src/js/payments/opayo.js ***!
  \**********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieOpayo: () => (/* binding */ FormieOpayo)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _payment_provider__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./payment-provider */ "./src/js/payments/payment-provider.js");
/* harmony import */ var _rynpsc_dialog__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @rynpsc/dialog */ "../../../../node_modules/@rynpsc/dialog/dist/module/dialog.js");
/* harmony import */ var payment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! payment */ "../../../../node_modules/payment/lib/index.js");
/* harmony import */ var payment__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(payment__WEBPACK_IMPORTED_MODULE_3__);
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




var FormieOpayo = /*#__PURE__*/function (_FormiePaymentProvide) {
  _inherits(FormieOpayo, _FormiePaymentProvide);
  function FormieOpayo() {
    var _this;
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieOpayo);
    _this = _callSuper(this, FormieOpayo, [settings]);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.$field = settings.$field;
    _this.boundEvents = false;
    _this.useSandbox = settings.useSandbox;
    _this.integrationHandle = settings.handle;
    _this.currency = settings.currency;
    _this.amountType = settings.amountType;
    _this.amountFixed = settings.amountFixed;
    _this.amountVariable = settings.amountVariable;
    _this.opayoScriptId = 'FORMIE_OPAYO_SCRIPT';

    // We can start listening for the field to become visible to initialize it
    _this.initialized = true;
    return _this;
  }
  _createClass(FormieOpayo, [{
    key: "onShow",
    value: function onShow() {
      // Initialize the field only when it's visible
      this.initField();
    }
  }, {
    key: "onHide",
    value: function onHide() {
      this.boundEvents = false;

      // Field is hidden, so reset everything
      this.onAfterSubmit();

      // Remove unique event listeners
      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormiePaymentValidate', 'opayo'));
      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'opayo'));
      this.form.removeEventListener((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('FormiePaymentOpayo3DS', 'opayo'));
    }
  }, {
    key: "initField",
    value: function initField() {
      // Fetch and attach the script only once - this is in case there are multiple forms on the page.
      // They all go to a single callback which resolves its loaded state
      if (!document.getElementById(this.opayoScriptId)) {
        var $script = document.createElement('script');
        $script.id = this.opayoScriptId;
        if (this.useSandbox) {
          $script.src = 'https://pi-test.sagepay.com/api/v1/js/sagepay.js';
        } else {
          $script.src = 'https://pi-live.sagepay.com/api/v1/js/sagepay.js';
        }
        $script.async = true;
        $script.defer = true;
        document.body.appendChild($script);
      }

      // Attach custom event listeners on the form
      // Prevent binding multiple times. This can cause multiple payments!
      if (!this.boundEvents) {
        this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormiePaymentValidate', 'opayo'), this.onValidate.bind(this));
        this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit', 'opayo'), this.onAfterSubmit.bind(this));
        this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('FormiePaymentOpayo3DS', 'opayo'), this.onValidate3DS.bind(this));
        this.boundEvents = true;
      }

      // Listen to events sent from the iframe to complete 3DS challenge
      window.addEventListener('message', this.onMessage.bind(this), false);

      // Add input masking and validation for some fields
      payment__WEBPACK_IMPORTED_MODULE_3___default().formatCardNumber(this.$field.querySelector('[data-opayo-card="card-number"]'));
      payment__WEBPACK_IMPORTED_MODULE_3___default().formatCardExpiry(this.$field.querySelector('[data-opayo-card="expiry-date"]'));
      payment__WEBPACK_IMPORTED_MODULE_3___default().formatCardCVC(this.$field.querySelector('[data-opayo-card="security-code"]'));
    }
  }, {
    key: "onValidate",
    value: function onValidate(e) {
      var _this2 = this;
      // Don't validate if we're not submitting (going back, saving)
      // Check if the form has an invalid flag set, don't bother going further
      if (this.form.submitAction !== 'submit' || e.detail.invalid) {
        return;
      }
      e.preventDefault();

      // Save for later to trigger real submit
      this.submitHandler = e.detail.submitHandler;
      this.removeError();
      try {
        // Fetch/generate the merchant ID first via an Ajax request
        var action = this.$form.getAttribute('action');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', action ? action : window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('Cache-Control', 'no-cache');
        xhr.ontimeout = function () {
          self.addError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request timed out.'));
        };
        xhr.onerror = function (e) {
          self.addError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request encountered a network error. Please try again.'));
        };
        xhr.onload = function () {
          if (xhr.status >= 200 && xhr.status < 300) {
            try {
              var response = JSON.parse(xhr.responseText);

              // Get the data from the client
              var cardDetails = {
                cardholderName: _this2.$field.querySelector('[data-opayo-card="cardholder-name"]').value,
                cardNumber: _this2.$field.querySelector('[data-opayo-card="card-number"]').value,
                expiryDate: _this2.$field.querySelector('[data-opayo-card="expiry-date"]').value,
                securityCode: _this2.$field.querySelector('[data-opayo-card="security-code"]').value
              };

              // Remove formatting
              cardDetails.cardNumber = cardDetails.cardNumber.replace(/[\s/]/g, '');
              cardDetails.expiryDate = cardDetails.expiryDate.replace(/[\s/]/g, '');

              // With the `merchantSessionKey`, now tokenize the credit card form and trigger submit
              sagepayOwnForm({
                merchantSessionKey: response.merchantSessionKey
              }).tokeniseCardDetails({
                cardDetails: cardDetails,
                onTokenised: function onTokenised(result) {
                  if (result.success) {
                    // Append an input so it's not namespaced with Twig
                    _this2.updateInputs('opayoTokenId', result.cardIdentifier);
                    _this2.updateInputs('opayoSessionKey', response.merchantSessionKey);
                    _this2.submitHandler.submitForm();
                  } else {
                    console.error(result);
                    _this2.addError(result.errors[0].message);
                  }
                }
              });
            } catch (e) {
              _this2.addError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Unable to parse response `{e}`.', {
                e: e
              }));
            }
          } else {
            _this2.addError("".concat(xhr.status, ": ").concat(xhr.statusText));
          }
        };
        var data = new FormData();
        data.append('action', 'formie/payment-webhooks/process-callback');
        data.append('merchantSessionKey', true);
        data.append('handle', this.integrationHandle);
        xhr.send(data);
      } catch (ex) {
        console.error(ex);
        this.addError(ex);
      }
    }
  }, {
    key: "addLoading",
    value: function addLoading() {
      if (this.form.formTheme) {
        this.form.formTheme.addLoading();
      }
    }
  }, {
    key: "removeLoading",
    value: function removeLoading() {
      if (this.form.formTheme) {
        this.form.formTheme.removeLoading();
      }
    }
  }, {
    key: "onMessage",
    value: function onMessage(e) {
      // Check this is the correct message
      if (e.data.message !== 'FormiePaymentOpayo3DSResponse') {
        return;
      }
      if (this.dialog) {
        this.dialog.close();
      }
      this.removeError();
      if (e.data.value.error) {
        this.removeLoading();
        return this.addError(e.data.value.error.message);
      }

      // Add a flag for server-side to check and finalise
      this.updateInputs('opayo3DSComplete', e.data.value.transactionId);
      this.submitHandler.submitForm();
    }
  }, {
    key: "onValidate3DS",
    value: function onValidate3DS(e) {
      try {
        var data = e.detail.data;

        // Keep the spinner going for 3DS
        this.addLoading();
        var dialogId = "fui-opayo-dialog-".concat((Math.random() + 1).toString(36).substring(7));
        var $dialog = document.createElement('div');
        $dialog.setAttribute('class', 'fui-modal');
        $dialog.setAttribute('id', dialogId);
        var $dialogBackdrop = document.createElement('div');
        $dialogBackdrop.setAttribute('class', 'fui-modal-backdrop');
        $dialogBackdrop.setAttribute('data-dialog-close', 'dialog');
        $dialog.appendChild($dialogBackdrop);
        var $dialogContent = document.createElement('div');
        $dialogContent.setAttribute('class', 'fui-modal-content');
        $dialog.appendChild($dialogContent);
        var $dialogLoading = document.createElement('div');
        $dialogLoading.setAttribute('class', 'fui-loading fui-loading-large');
        $dialogLoading.setAttribute('style', '--fui-loading-width: 3rem; --fui-loading-height: 3rem; --fui-loading-border-width: 4px; top: 50%; margin-top: -1.5rem;');
        $dialogContent.appendChild($dialogLoading);
        var $iframe = document.createElement('iframe');
        $iframe.setAttribute('width', '100%');
        $iframe.setAttribute('height', '100%');
        $iframe.setAttribute('style', 'width: 100%; height: 100%; position: relative; z-index: 1;');
        var html = "<form action=\"".concat(data.acsUrl, "\" method=\"post\">\n                <input type=\"hidden\" name=\"creq\" value=\"").concat(data.creq, "\" />\n                <input type=\"hidden\" name=\"threeDSSessionData\" value=\"").concat(data.threeDSSessionData, "\" />\n                <input type=\"hidden\" name=\"MD\" value=\"").concat(this.merchantSessionKey, "\" />\n                <input type=\"hidden\" name=\"TermUrl\" value=\"").concat(data.redirectUrl, "\" />\n                <input type=\"hidden\" name=\"ThreeDSNotificationURL\" value=\"").concat(data.redirectUrl, "\" />\n            </form>\n            <script>document.forms[0].submit();</script>");
        $dialogContent.appendChild($iframe);
        document.body.appendChild($dialog);
        $iframe.contentWindow.document.open();
        $iframe.contentWindow.document.write(html);
        $iframe.contentWindow.document.close();
        this.dialog = (0,_rynpsc_dialog__WEBPACK_IMPORTED_MODULE_2__.dialog)(dialogId);
        if (this.dialog) {
          this.dialog.create();
          this.dialog.open();
        }
      } catch (ex) {
        console.error(ex);
        self.addError(ex);
      }
    }
  }, {
    key: "onAfterSubmit",
    value: function onAfterSubmit(e) {
      // Reset all hidden inputs
      this.updateInputs('opayoTokenId', '');
      this.updateInputs('opayoSessionKey', '');
      this.updateInputs('opayo3DSComplete', '');
    }
  }]);
  return FormieOpayo;
}(_payment_provider__WEBPACK_IMPORTED_MODULE_1__.FormiePaymentProvider);
window.FormieOpayo = FormieOpayo;
})();

/******/ })()
;