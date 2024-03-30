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
  !*** ./src/js/fields/file-upload.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieFileUpload": () => (/* binding */ FormieFileUpload)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


var FormieFileUpload = /*#__PURE__*/function () {
  function FormieFileUpload() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieFileUpload);

    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('registerFormieValidation'), this.registerValidation.bind(this));
    this.form.addEventListener(this.$form, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onAfterFormieSubmit'), this.onAfterSubmit.bind(this));
  }

  _createClass(FormieFileUpload, [{
    key: "registerValidation",
    value: function registerValidation(e) {
      // Add our custom validations logic and methods
      e.detail.validatorSettings.customValidations = _objectSpread(_objectSpread(_objectSpread(_objectSpread({}, e.detail.validatorSettings.customValidations), this.getFileSizeMinLimitRule()), this.getFileSizeMaxLimitRule()), this.getFileLimitRule()); // Add our custom messages

      e.detail.validatorSettings.messages = _objectSpread(_objectSpread(_objectSpread(_objectSpread({}, e.detail.validatorSettings.messages), this.getFileSizeMinLimitMessage()), this.getFileSizeMaxLimitMessage()), this.getFileLimitMessage());
    }
  }, {
    key: "getFileSizeMinLimitRule",
    value: function getFileSizeMinLimitRule() {
      return {
        fileSizeMinLimit: function fileSizeMinLimit(field) {
          var type = field.getAttribute('type');
          var sizeLimit = field.getAttribute('data-size-min-limit');
          var sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

          if (type !== 'file' || !sizeBytes) {
            return;
          }

          var _iterator = _createForOfIteratorHelper(field.files),
              _step;

          try {
            for (_iterator.s(); !(_step = _iterator.n()).done;) {
              var file = _step.value;

              if (file.size < sizeBytes) {
                return true;
              }
            }
          } catch (err) {
            _iterator.e(err);
          } finally {
            _iterator.f();
          }
        }
      };
    }
  }, {
    key: "getFileSizeMinLimitMessage",
    value: function getFileSizeMinLimitMessage() {
      return {
        fileSizeMinLimit: function fileSizeMinLimit(field) {
          return (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('File must be larger than {filesize} MB.', {
            filesize: field.getAttribute('data-size-min-limit')
          });
        }
      };
    }
  }, {
    key: "getFileSizeMaxLimitRule",
    value: function getFileSizeMaxLimitRule() {
      return {
        fileSizeMaxLimit: function fileSizeMaxLimit(field) {
          var type = field.getAttribute('type');
          var sizeLimit = field.getAttribute('data-size-max-limit');
          var sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

          if (type !== 'file' || !sizeBytes) {
            return;
          }

          var _iterator2 = _createForOfIteratorHelper(field.files),
              _step2;

          try {
            for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
              var file = _step2.value;

              if (file.size > sizeBytes) {
                return true;
              }
            }
          } catch (err) {
            _iterator2.e(err);
          } finally {
            _iterator2.f();
          }
        }
      };
    }
  }, {
    key: "getFileSizeMaxLimitMessage",
    value: function getFileSizeMaxLimitMessage() {
      return {
        fileSizeMaxLimit: function fileSizeMaxLimit(field) {
          return (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('File must be smaller than {filesize} MB.', {
            filesize: field.getAttribute('data-size-max-limit')
          });
        }
      };
    }
  }, {
    key: "getFileLimitRule",
    value: function getFileLimitRule() {
      return {
        fileLimit: function fileLimit(field) {
          var type = field.getAttribute('type');
          var fileLimit = parseInt(field.getAttribute('data-file-limit'));

          if (type !== 'file' || !fileLimit) {
            return;
          }

          if (field.files.length > fileLimit) {
            return true;
          }
        }
      };
    }
  }, {
    key: "getFileLimitMessage",
    value: function getFileLimitMessage() {
      return {
        fileLimit: function fileLimit(field) {
          return (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Choose up to {files} files.', {
            files: field.getAttribute('data-file-limit')
          });
        }
      };
    }
  }, {
    key: "onAfterSubmit",
    value: function onAfterSubmit() {
      // For multi-page Ajax forms, we don't want to submit the file uploads multiple times, so clear the content after success
      this.$field.querySelector('[type="file"]').value = null;
    }
  }]);

  return FormieFileUpload;
}();
window.FormieFileUpload = FormieFileUpload;
})();

/******/ })()
;