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
/*!*************************************!*\
  !*** ./src/js/fields/text-limit.js ***!
  \*************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieTextLimit": () => (/* binding */ FormieTextLimit)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


var FormieTextLimit = /*#__PURE__*/function () {
  function FormieTextLimit() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieTextLimit);

    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.$text = this.$field.querySelector('[data-limit]');
    this.$input = this.$field.querySelector('input, textarea');

    if (this.$text) {
      this.initTextLimits();
    } else {
      console.error('Unable to find rich text field “[data-limit]”');
    }
  }

  _createClass(FormieTextLimit, [{
    key: "initTextLimits",
    value: function initTextLimits() {
      this.maxChars = this.$text.getAttribute('data-max-chars');
      this.maxWords = this.$text.getAttribute('data-max-words');

      if (this.maxChars) {
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('paste'), this.characterCheck.bind(this), false);
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.characterCheck.bind(this), false);
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('populate'), this.characterCheck.bind(this), false); // Fire immediately

        this.$input.dispatchEvent(new Event('keydown', {
          bubbles: true
        }));
      }

      if (this.maxWords) {
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('paste'), this.wordCheck.bind(this), false);
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.wordCheck.bind(this), false);
        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('populate'), this.wordCheck.bind(this), false); // Fire immediately

        this.$input.dispatchEvent(new Event('keydown', {
          bubbles: true
        }));
      }
    }
  }, {
    key: "characterCheck",
    value: function characterCheck(e) {
      var _this = this;

      setTimeout(function () {
        // Strip HTML tags
        var value = _this.stripTags(e.target.value);

        var charactersLeft = _this.maxChars - _this.count(value);

        var extraClasses = ['fui-limit-number'];
        var type = charactersLeft == 1 || charactersLeft == -1 ? 'character' : 'characters';

        if (charactersLeft < 0) {
          extraClasses.push('fui-limit-number-error');
        }

        _this.$text.innerHTML = (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)("{startTag}{num}{endTag} ".concat(type, " left"), {
          num: String(charactersLeft),
          startTag: "<span class=\"".concat(extraClasses.join(' '), "\">"),
          endTag: '</span>'
        });
      }, 1);
    }
  }, {
    key: "wordCheck",
    value: function wordCheck(e) {
      var _this2 = this;

      setTimeout(function () {
        // Strip HTML tags
        var value = _this2.stripTags(e.target.value);

        var wordCount = value.split(/\S+/).length - 1;
        var wordsLeft = _this2.maxWords - wordCount;
        var extraClasses = ['fui-limit-number'];
        var type = wordsLeft == 1 || wordsLeft == -1 ? 'word' : 'words';

        if (wordsLeft < 0) {
          extraClasses.push('fui-limit-number-error');
        }

        _this2.$text.innerHTML = (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)("{startTag}{num}{endTag} ".concat(type, " left"), {
          num: String(wordsLeft),
          startTag: "<span class=\"".concat(extraClasses.join(' '), "\">"),
          endTag: '</span>'
        });
      }, 1);
    }
  }, {
    key: "count",
    value: function count(value) {
      // Convert any multibyte characters to their HTML entity equivalent to match server-side processing
      // https://dev.to/nikkimk/converting-utf-including-emoji-to-html-x1f92f-4951
      return _toConsumableArray(value).map(function (_char) {
        // Check for space characters to exclude
        return _char.codePointAt() > 127 && !/\s/.test(_char) ? "&#".concat(_char.codePointAt(), ";") : _char;
      }).join('').length;
    }
  }, {
    key: "stripTags",
    value: function stripTags(string) {
      var doc = new DOMParser().parseFromString(string, 'text/html');
      return doc.body.textContent || '';
    }
  }]);

  return FormieTextLimit;
}();
window.FormieTextLimit = FormieTextLimit;
})();

/******/ })()
;