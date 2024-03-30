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
  !*** ./src/js/captchas/duplicate.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieDuplicateCaptcha": () => (/* binding */ FormieDuplicateCaptcha)
/* harmony export */ });
/* harmony import */ var _captcha_provider__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./captcha-provider */ "./src/js/captchas/captcha-provider.js");
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
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



var FormieDuplicateCaptcha = /*#__PURE__*/function (_FormieCaptchaProvide) {
  _inherits(FormieDuplicateCaptcha, _FormieCaptchaProvide);

  var _super = _createSuper(FormieDuplicateCaptcha);

  function FormieDuplicateCaptcha() {
    var _this;

    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieDuplicateCaptcha);

    _this = _super.call(this, settings);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.sessionKey = settings.sessionKey;
    _this.value = settings.value;
    _this.$placeholder = _this.$form.querySelector('[data-duplicate-captcha-placeholder]');

    if (!_this.$placeholder) {
      console.error('Unable to find Duplicate Captcha placeholder for [data-duplicate-captcha-placeholder]');
      return _possibleConstructorReturn(_this);
    }

    _this.createInput();

    return _this;
  }

  _createClass(FormieDuplicateCaptcha, [{
    key: "createInput",
    value: function createInput() {
      // We need to handle re-initializing, so always empty the placeholder to start fresh to prevent duplicate captchas
      this.$placeholder.innerHTML = '';
      var $input = document.createElement('input');
      $input.setAttribute('type', 'hidden');
      $input.setAttribute('name', this.sessionKey);
      $input.value = this.value;
      this.$placeholder.appendChild($input);
    }
  }]);

  return FormieDuplicateCaptcha;
}(_captcha_provider__WEBPACK_IMPORTED_MODULE_0__.FormieCaptchaProvider);
window.FormieDuplicateCaptcha = FormieDuplicateCaptcha;
})();

/******/ })()
;