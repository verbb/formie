/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

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
/*!*****************************************!*\
  !*** ./src/js/fields/checkbox-radio.js ***!
  \*****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieCheckboxRadio: () => (/* binding */ FormieCheckboxRadio)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var FormieCheckboxRadio = /*#__PURE__*/function () {
  function FormieCheckboxRadio() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieCheckboxRadio);
    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    if (this.$field) {
      this.initInputs();
      this.initRequiredCheckboxes();
      this.initToggleCheckboxes();
    }
  }
  _createClass(FormieCheckboxRadio, [{
    key: "initInputs",
    value: function initInputs() {
      var _this = this;
      var $inputs = this.$field.querySelectorAll('[type=checkbox], [type=radio]');
      $inputs.forEach(function ($input) {
        _this.form.addEventListener($input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('click'), function (e) {
          if (e.target.checked) {
            if (e.target.getAttribute('type') === 'radio') {
              var inputName = e.target.getAttribute('name');
              var $radioButtons = _this.$field.querySelectorAll("[name=\"".concat(inputName, "\"] "));
              $radioButtons.forEach(function ($radioButton) {
                $radioButton.removeAttribute('checked');
              });
            }
            e.target.setAttribute('checked', '');
          } else {
            e.target.removeAttribute('checked');
          }
        }, false);
      });
    }
  }, {
    key: "initRequiredCheckboxes",
    value: function initRequiredCheckboxes() {
      var _this2 = this;
      var $checkboxInputs = this.$field.querySelectorAll('[type="checkbox"][required]');
      $checkboxInputs.forEach(function ($checkboxInput) {
        _this2.form.addEventListener($checkboxInput, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('change'), function (e) {
          _this2.onCheckboxChanged($checkboxInputs, _this2.isChecked($checkboxInputs));
        }, false);

        // For any checked fields, trigger this event now
        if ($checkboxInput.checked) {
          $checkboxInput.dispatchEvent(new Event('change', {
            bubbles: true
          }));
        }
      });
    }
  }, {
    key: "initToggleCheckboxes",
    value: function initToggleCheckboxes() {
      var _this3 = this;
      var $checkboxInputs = this.$field.querySelectorAll('[type="checkbox"]');
      var $checkboxToggles = this.$field.querySelectorAll('[type="checkbox"][data-checkbox-toggle]');
      $checkboxToggles.forEach(function ($checkboxToggle) {
        _this3.form.addEventListener($checkboxToggle, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('change'), function (e) {
          var isChecked = e.target.checked;

          // Toggle all checkboxes in this field
          $checkboxInputs.forEach(function ($checkboxInput) {
            if ($checkboxInput !== e.target) {
              $checkboxInput.checked = isChecked;
            }
          });
        }, false);
      });
    }
  }, {
    key: "isChecked",
    value: function isChecked($checkboxInputs) {
      for (var i = 0; i < $checkboxInputs.length; i++) {
        if ($checkboxInputs[i].checked) {
          return true;
        }
      }
      return false;
    }
  }, {
    key: "onCheckboxChanged",
    value: function onCheckboxChanged($checkboxInputs, checked) {
      $checkboxInputs.forEach(function ($checkboxInput) {
        if (checked) {
          $checkboxInput.removeAttribute('required');
          $checkboxInput.setAttribute('aria-required', false);
        } else {
          $checkboxInput.setAttribute('required', true);
          $checkboxInput.setAttribute('aria-required', true);
        }
      });
    }
  }]);
  return FormieCheckboxRadio;
}();
window.FormieCheckboxRadio = FormieCheckboxRadio;
})();

/******/ })()
;