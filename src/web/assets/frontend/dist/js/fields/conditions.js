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
  !*** ./src/js/fields/conditions.js ***!
  \*************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieConditions": () => (/* binding */ FormieConditions)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


var FormieConditions = /*#__PURE__*/function () {
  function FormieConditions() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieConditions);

    this.$form = settings.$form;
    this.form = this.$form.form; // Best-practice for storing data keyed by DOM nodes
    // https://fitzgeraldnick.com/2014/01/13/hiding-implementation-details-with-e6-weakmaps.html

    this.conditionsStore = new WeakMap();
    this.initFieldConditions();
  }

  _createClass(FormieConditions, [{
    key: "initFieldConditions",
    value: function initFieldConditions() {
      var _this = this;

      this.$form.querySelectorAll('[data-field-conditions]').forEach(function ($field) {
        var conditionSettings = _this.parseJsonConditions($field);

        if (!conditionSettings || !conditionSettings.conditions.length) {
          return;
        } // Store the conditions against the target field object for later access/testing


        var conditions = [];
        conditionSettings.conditions.forEach(function (condition) {
          // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
          var $targets = _this.$form.querySelectorAll("[name=\"".concat(condition.field, "\"]")); // Check if we're dealing with multiple fields, like checkboxes. This overrides the above


          var $multiFields = _this.$form.querySelectorAll("[name=\"".concat(condition.field, "[]\"]"));

          if ($multiFields.length) {
            $targets = $multiFields;
          } // Special handling for Repeater/Groups that have `new1` in their name but for page reload forms
          // this will be replaced by the blockId, and will fail to match the conditions settings.


          if ((!$targets || !$targets.length) && condition.field.includes('[new1]')) {
            // Get tricky with Regex. Find the element that matches everything except `[new1]` for `[1234]`.
            // Escape special characters `[]` in the string, and swap `[new1]` with `[\d+]`.
            var regexString = condition.field.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/new1/g, '\\d+'); // Find all targets via Regex.

            $targets = _this.querySelectorAllRegex(new RegExp(regexString), 'name');
          }

          if (!$targets || !$targets.length) {
            return;
          } // Store the conditions with the target field for later access/testing


          condition.$targets = $targets;
          conditions.push(condition);
          $targets.forEach(function ($target) {
            // Get the right event for the field
            var eventType = _this.getEventType($target); // Watch for changes on the target field. When one occurs, fire off a custom event on the source field
            // We need to do this because target fields can be targetted by multiple conditions, and source
            // fields can have multiple conditions - we need to check them all for all/any logic.


            _this.form.addEventListener($target, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)(eventType), function () {
              return $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', {
                bubbles: true,
                detail: {
                  conditions: _this
                }
              }));
            });
          });
        }); // Save our condition settings and targets against the origin fields. We'll use this to evaluate conditions

        _this.conditionsStore.set($field, {
          showRule: conditionSettings.showRule,
          conditionRule: conditionSettings.conditionRule,
          isNested: conditionSettings.isNested || false,
          conditions: conditions
        }); // Add a custom event listener to fire when the field event listener fires


        _this.form.addEventListener($field, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormieEvaluateConditions'), _this.evaluateConditions.bind(_this)); // Also - trigger the event right now to evaluate immediately. Namely if we need to hide
        // field that are set to show if conditions are met. Pass in a param to let fields know if this is "init".


        $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', {
          bubbles: true,
          detail: {
            conditions: _this,
            init: true
          }
        }));
      }); // Update the form hash, so we don't get change warnings

      if (this.form.formTheme) {
        this.form.formTheme.updateFormHash();
      }
    }
  }, {
    key: "evaluateConditions",
    value: function evaluateConditions(e) {
      var _this2 = this;

      var $field = e.target;
      var isInit = e.detail ? e.detail.init : false; // Get the prepped conditions for this field

      var conditionSettings = this.conditionsStore.get($field);

      if (!conditionSettings) {
        return;
      }

      var showRule = conditionSettings.showRule,
          conditionRule = conditionSettings.conditionRule,
          conditions = conditionSettings.conditions,
          isNested = conditionSettings.isNested;
      var results = {};
      conditions.forEach(function (condition, i) {
        var logic = condition.condition,
            value = condition.value,
            $targets = condition.$targets,
            field = condition.field; // We're always dealing with a collection of targets, even if the target is a text field
        // The reason being is this normalises behaviour for some fields (checkbox/radio) that
        // have multiple fields in a group.

        $targets.forEach(function ($target) {
          var result = false;
          var testOptions = {};
          var tagName = $target.tagName.toLowerCase();
          var inputType = $target.getAttribute('type') ? $target.getAttribute('type').toLowerCase() : ''; // Create a key for this condition rule that we'll use to store (potentially multiple) results against.
          // It's not visibly needed for anything, but using the target's field name helps with debugging.

          var resultKey = "".concat(field, "_").concat(i); // Store all results as an array, and we'll normalise afterwards. Group results by their condition rule.
          // For example: { dropdown_0: [false], radio_1: [true, false] }

          if (!results[resultKey]) {
            results[resultKey] = [];
          } // Handle some special options like dates - tell our condition tester about them


          if (inputType === 'date') {
            testOptions.isDate = true;
          } // Handle agree fields, which are a single checkbox, checked/unchecked


          if ($target.getAttribute('data-fui-input-type') === 'agree') {
            // Ignore the empty, hidden checkbox
            if (inputType === 'hidden') {
              return;
            } // Convert the value to boolean to compare


            result = _this2.testCondition(logic, value == '0' ? false : true, $target.checked);
            results[resultKey].push(result);
          } else if (inputType === 'checkbox' || inputType === 'radio') {
            // Handle (multi) checkboxes and radio, which are a bit of a pain
            result = _this2.testCondition(logic, value, $target.value) && $target.checked;
            results[resultKey].push(result);
          } else if (tagName === 'select' && $target.hasAttribute('multiple')) {
            // Handle multi-selects
            Array.from($target.options).forEach(function ($option) {
              result = _this2.testCondition(logic, value, $option.value) && $option.selected;
              results[resultKey].push(result);
            });
          } else {
            result = _this2.testCondition(logic, value, $target.value, testOptions);
            results[resultKey].push(result);
          }
        });
      }); // Normalise the results before going further, as this'll be keyed as an object, so convert to an array
      // and because we can have multiple inputs, each with their own value, reduce them to a single boolean.
      // For example: { dropdown_0: [false], radio_1: [true, false] } changes to [false, true].

      var normalisedResults = [];
      Object.values(results).forEach(function (result) {
        normalisedResults.push(result.includes(true));
      });
      var finalResult = false; // Check to see how to compare the result (any or all).

      if (normalisedResults.length) {
        if (conditionRule === 'all') {
          // Are _all_ the conditions the same?
          finalResult = normalisedResults.every(function (val) {
            return val === true;
          });
        } else {
          finalResult = normalisedResults.includes(true);
        }
      } // Check if this condition is nested in a Group/Repeater field. Only proceed if the parent field
      // conditional evaluation has passed.


      var overrideResult = false; // But *do* setup conditions on the first run, when initialising all the fields

      if (isNested && !isInit) {
        var $parentField = $field.closest('[data-field-type="group"], [data-field-type="repeater"]');

        if ($parentField) {
          // Is the parent field conditionally hidden? Force the evaluation to be true (this field is
          // is conditionallu hidden), to prevent inner field conditions having a higher priority than the
          // parent Group/Repeater fields.
          if ($parentField.conditionallyHidden) {
            overrideResult = true;
          }
        }
      } // Show or hide? Also toggle the disabled state to sort out any hidden required fields


      if (overrideResult || finalResult && showRule !== 'show' || !finalResult && showRule === 'show') {
        $field.conditionallyHidden = true;
        $field.setAttribute('data-conditionally-hidden', true);
        $field.querySelectorAll('input, textarea, select').forEach(function ($input) {
          $input.setAttribute('disabled', true);
        });
      } else {
        $field.conditionallyHidden = false;
        $field.removeAttribute('data-conditionally-hidden');
        $field.querySelectorAll('input, textarea, select').forEach(function ($input) {
          $input.removeAttribute('disabled');
        });
      } // Fire an event to notify that the field's conditions have been evaluated


      $field.dispatchEvent(new CustomEvent('onAfterFormieEvaluateConditions', {
        bubbles: true,
        detail: {
          conditions: this,
          init: isInit
        }
      }));
    }
  }, {
    key: "parseJsonConditions",
    value: function parseJsonConditions($field) {
      var json = $field.getAttribute('data-field-conditions');

      if (json) {
        try {
          return JSON.parse(json);
        } catch (e) {
          console.error("Unable to parse JSON conditions: ".concat(e));
        }
      }

      return false;
    }
  }, {
    key: "getEventType",
    value: function getEventType($field) {
      var tagName = $field.tagName.toLowerCase();
      var inputType = $field.getAttribute('type') ? $field.getAttribute('type').toLowerCase() : '';

      if (tagName === 'select' || inputType === 'date') {
        return 'change';
      }

      if (inputType === 'number') {
        return 'input';
      }

      if (inputType === 'checkbox' || inputType === 'radio') {
        return 'click';
      }

      return 'keyup';
    }
  }, {
    key: "testCondition",
    value: function testCondition(logic, value, fieldValue) {
      var testOptions = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
      var result = false; // Are we dealing with dates? That's a whole other mess...

      if (testOptions.isDate) {
        value = new Date(value).valueOf();
        fieldValue = new Date(fieldValue).valueOf();
      }

      if (logic === '=') {
        result = value === fieldValue;
      } else if (logic === '!=') {
        result = value !== fieldValue;
      } else if (logic === '>') {
        result = parseFloat(fieldValue, 10) > parseFloat(value, 10);
      } else if (logic === '<') {
        result = parseFloat(fieldValue, 10) < parseFloat(value, 10);
      } else if (logic === 'contains') {
        result = fieldValue.includes(value);
      } else if (logic === 'startsWith') {
        result = fieldValue.startsWith(value);
      } else if (logic === 'endsWith') {
        result = fieldValue.endsWith(value);
      }

      return result;
    }
  }, {
    key: "querySelectorAllRegex",
    value: function querySelectorAllRegex(regex, attributeToSearch) {
      var output = [];

      var _iterator = _createForOfIteratorHelper(this.$form.querySelectorAll("[".concat(attributeToSearch, "]"))),
          _step;

      try {
        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          var element = _step.value;

          if (regex.test(element.getAttribute(attributeToSearch))) {
            output.push(element);
          }
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }

      return output;
    }
  }]);

  return FormieConditions;
}();
window.FormieConditions = FormieConditions;
})();

/******/ })()
;