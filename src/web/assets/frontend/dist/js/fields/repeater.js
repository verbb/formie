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
/*!***********************************!*\
  !*** ./src/js/fields/repeater.js ***!
  \***********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieRepeater": () => (/* binding */ FormieRepeater)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


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

      var $rows = this.getRows(); // Assign this instance to the field's DOM, so it can be accessed by third parties

      this.$field.repeater = this; // Save a bunch of properties

      this.$addButton = this.$field.querySelector('[data-add-repeater-row]');
      this.minRows = parseInt(this.$addButton.getAttribute('data-min-rows'));
      this.maxRows = parseInt(this.$addButton.getAttribute('data-max-rows')); // Bind the click event to the add button

      if (this.$addButton) {
        // Add the click event, but use a namespace so we can track these dynamically-added items
        this.form.addEventListener(this.$addButton, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('click'), function (e) {
          _this.addRow(e);
        });
      } // Initialise any rendered rows


      if ($rows && $rows.length) {
        $rows.forEach(function ($row) {
          _this.initRow($row);
        });
      } // Emit an "init" event


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
      } // Initialize any new nested fields with JS


      if (isNew) {
        var fieldConfigs = Formie.parseFieldConfig($row, this.$form);
        Object.keys(fieldConfigs).forEach(function (module) {
          fieldConfigs[module].forEach(function (fieldConfig) {
            _this2.initFieldClass(module, fieldConfig);
          });
        });
      } // Increment the number of rows "in store"


      this.rowCounter++;
    }
  }, {
    key: "initFieldClass",
    value: function initFieldClass(className, params) {
      var moduleClass = window[className];

      if (moduleClass) {
        new moduleClass(params);
      }
    }
  }, {
    key: "addRow",
    value: function addRow(e) {
      var _this3 = this;

      var button = e.target;
      var handle = this.$addButton.getAttribute('data-add-repeater-row');
      var template = document.querySelector("[data-repeater-template=\"".concat(handle, "\"]"));
      var numRows = this.getNumRows();

      if (template) {
        if (numRows >= this.maxRows) {
          return;
        } // We don't want this real-time. We want to maintain a counter to ensure
        // there's no collisions of new rows overwriting or jumbling up old rows
        // when removing them (adding 2, remove 1st, add new - results in issues).


        var id = "new".concat(this.rowCounter + 1);
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
        this.$addButton.classList.add = this.disabledClass;
        this.$addButton.setAttribute('disabled', 'disabled');
      } else {
        this.$addButton.classList.remove = this.disabledClass;
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