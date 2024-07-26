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
/* harmony export */   addClasses: () => (/* binding */ addClasses),
/* harmony export */   clone: () => (/* binding */ clone),
/* harmony export */   debounce: () => (/* binding */ debounce),
/* harmony export */   ensureVariable: () => (/* binding */ ensureVariable),
/* harmony export */   eventKey: () => (/* binding */ eventKey),
/* harmony export */   isEmpty: () => (/* binding */ isEmpty),
/* harmony export */   removeClasses: () => (/* binding */ removeClasses),
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
var addClasses = function addClasses(element, classes) {
  if (!element || !classes) {
    return;
  }
  if (typeof classes === 'string') {
    classes = classes.split(' ');
  }
  classes.forEach(function (className) {
    element.classList.add(className);
  });
};
var removeClasses = function removeClasses(element, classes) {
  if (!element || !classes) {
    return;
  }
  if (typeof classes === 'string') {
    classes = classes.split(' ');
  }
  classes.forEach(function (className) {
    element.classList.remove(className);
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
  !*** ./src/js/fields/repeater.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieRepeater: () => (/* binding */ FormieRepeater)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var FormieRepeater = /*#__PURE__*/function () {
  function FormieRepeater() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieRepeater);
    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.disabledClass = this.form.getClasses('disabled');
    this.rowCounter = 0;
    this.initRepeater();
  }
  _createClass(FormieRepeater, [{
    key: "initRepeater",
    value: function initRepeater() {
      var _this = this;
      var $rows = this.getRows();

      // Assign this instance to the field's DOM, so it can be accessed by third parties
      this.$field.repeater = this;

      // Save a bunch of properties
      this.$addButton = this.$field.querySelector('[data-add-repeater-row]');
      this.minRows = parseInt(this.$addButton.getAttribute('data-min-rows'));
      this.maxRows = parseInt(this.$addButton.getAttribute('data-max-rows'));

      // Bind the click event to the add button
      if (this.$addButton) {
        // Add the click event, but use a namespace so we can track these dynamically-added items
        this.form.addEventListener(this.$addButton, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('click'), function (e) {
          _this.addRow();
        });
      }

      // Initialise any rendered rows
      if ($rows && $rows.length) {
        $rows.forEach(function ($row) {
          _this.initRow($row);
        });
      }

      // Create any minRows automatically if the field is empty
      if ((!$rows || !$rows.length) && this.minRows) {
        for (var i = 0; i < this.minRows; i++) {
          this.addRow();
        }
      }

      // Emit an "init" event
      this.$field.dispatchEvent(new CustomEvent('init', {
        bubbles: true,
        detail: {
          repeater: this
        }
      }));
    }
  }, {
    key: "initRow",
    value: function initRow($row) {
      var _this2 = this;
      var isNew = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
      if (!$row) {
        console.error($row);
        return;
      }
      var $removeButton = $row.querySelector('[data-remove-repeater-row]');
      if ($removeButton) {
        // Add the click event, but use a namespace so we can track these dynamically-added items
        this.form.addEventListener($removeButton, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('click'), function (e) {
          _this2.removeRow(e);
        });
      }

      // Initialize any new nested fields with JS
      if (isNew) {
        var fieldConfigs = Formie.parseFieldConfig($row, this.$form);
        Object.keys(fieldConfigs).forEach(function (module) {
          fieldConfigs[module].forEach(function (fieldConfig) {
            Formie.initJsClass(module, fieldConfig);
          });
        });
      }

      // Increment the number of rows "in store"
      this.rowCounter++;

      // Emit an "initRow" event
      this.$field.dispatchEvent(new CustomEvent('initRow', {
        bubbles: true,
        detail: {
          repeater: this,
          $row: $row
        }
      }));

      // Trigger a lazy global event, to allow other things to pick up on an initialized row.
      // This is most useful for conditions, where already initialized rows won't be picked up due to race conditions.
      this.form.triggerEvent('repeater:initRow', {
        repeater: this,
        $row: $row
      });
    }
  }, {
    key: "addRow",
    value: function addRow() {
      var _this3 = this;
      var handle = this.$addButton.getAttribute('data-add-repeater-row');
      var template = document.querySelector("[data-repeater-template=\"".concat(handle, "\"]"));
      var numRows = this.getNumRows();
      if (template) {
        if (numRows >= this.maxRows) {
          return;
        }
        var id = this.rowCounter;
        var html = template.innerHTML.replace(/__ROW__/g, id);
        var $newRow = document.createElement('div');
        $newRow.innerHTML = html.trim();
        $newRow = $newRow.querySelector('div:first-of-type');
        this.$field.querySelector('[data-repeater-rows]').appendChild($newRow);
        setTimeout(function () {
          _this3.updateButton();
          var event = new CustomEvent('append', {
            bubbles: true,
            detail: {
              repeater: _this3,
              row: $newRow,
              form: _this3.$form
            }
          });
          _this3.$field.dispatchEvent(event);
          _this3.initRow(event.detail.row, true);
        }, 50);
      }
    }
  }, {
    key: "removeRow",
    value: function removeRow(e) {
      var button = e.target;
      var $row = button.closest('[data-repeater-row]');
      if ($row) {
        var numRows = this.getNumRows();
        if (numRows <= this.minRows) {
          return;
        }
        $row.parentNode.removeChild($row);
        var event = new CustomEvent('remove', {
          bubbles: true,
          detail: {
            repeater: this,
            row: $row,
            form: this.$form
          }
        });
        this.$field.dispatchEvent(event);
        this.updateButton();
      }
    }
  }, {
    key: "getRows",
    value: function getRows() {
      return this.$field.querySelectorAll('[data-repeater-row]');
    }
  }, {
    key: "getNumRows",
    value: function getNumRows() {
      return this.getRows().length;
    }
  }, {
    key: "updateButton",
    value: function updateButton() {
      if (this.getNumRows() >= this.maxRows) {
        (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.addClasses)(this.$addButton, this.disabledClass);
        this.$addButton.setAttribute('disabled', 'disabled');
      } else {
        (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.removeClasses)(this.$addButton, this.disabledClass);
        this.$addButton.removeAttribute('disabled');
      }
    }
  }]);
  return FormieRepeater;
}();
window.FormieRepeater = FormieRepeater;
})();

/******/ })()
;