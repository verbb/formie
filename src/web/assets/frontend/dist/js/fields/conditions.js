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
/*!*************************************!*\
  !*** ./src/js/fields/conditions.js ***!
  \*************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieConditions: () => (/* binding */ FormieConditions)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var FormieConditions = /*#__PURE__*/function () {
  function FormieConditions() {
    var _this = this;
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieConditions);
    this.$form = settings.$form;
    this.form = this.$form.form;

    // Best-practice for storing data keyed by DOM nodes
    // https://fitzgeraldnick.com/2014/01/13/hiding-implementation-details-with-e6-weakmaps.html
    this.conditionsStore = new WeakMap();
    this.initFieldConditions(this.$form);

    // Handle dynamic fields like Repeater, which should be evaluated when added
    this.form.registerEvent('repeater:initRow', function (e) {
      _this.initFieldConditions(e.$row);
    });
  }
  _createClass(FormieConditions, [{
    key: "initFieldConditions",
    value: function initFieldConditions($container) {
      var _this2 = this;
      $container.querySelectorAll('[data-field-conditions]').forEach(function ($field) {
        // Save our condition settings and targets against the origin fields. We'll use this to evaluate conditions
        var fieldConditions = _this2.getFieldConditions($field);

        // Check if this is a Repeater or Group field, and load in any of the child conditions so they can be triggered
        var fieldType = $field.getAttribute('data-field-type');
        var isNested = fieldType === 'group' || fieldType === 'repeater';
        if (isNested) {
          fieldConditions.nestedFieldConditions = $field.querySelectorAll('[data-field-conditions]');
        }
        _this2.conditionsStore.set($field, fieldConditions);

        // Add a custom event listener to fire when the field event listener fires
        _this2.form.addEventListener($field, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('onFormieEvaluateConditions'), _this2.evaluateConditions.bind(_this2));

        // Also - trigger the event right now to evaluate immediately. Namely if we need to hide
        // field that are set to show if conditions are met. Pass in a param to let fields know if this is "init".
        $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', {
          bubbles: true,
          detail: {
            conditions: _this2,
            init: true
          }
        }));
      });

      // Update the form hash, so we don't get change warnings
      if (this.form.formTheme) {
        this.form.formTheme.updateFormHash();
      }
    }
  }, {
    key: "getFieldConditions",
    value: function getFieldConditions($field) {
      var _this3 = this;
      var conditionSettings = this.parseJsonConditions($field);
      if (!conditionSettings || !conditionSettings.conditions.length) {
        return;
      }

      // Store the conditions against the target field object for later access/testing
      var conditions = [];
      conditionSettings.conditions.forEach(function (condition) {
        // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
        var $targets = _this3.$form.querySelectorAll("[name=\"".concat(condition.field, "\"]"));

        // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
        var $multiFields = _this3.$form.querySelectorAll("[name=\"".concat(condition.field, "[]\"]"));
        if ($multiFields.length) {
          $targets = $multiFields;
        }
        if (!$targets || !$targets.length) {
          return;
        }

        // Store the conditions with the target field for later access/testing
        condition.$targets = $targets;
        conditions.push(condition);
        $targets.forEach(function ($target) {
          // Get the right event for the field
          var eventType = _this3.getEventType($target);

          // Watch for changes on the target field. When one occurs, fire off a custom event on the source field
          // We need to do this because target fields can be targetted by multiple conditions, and source
          // fields can have multiple conditions - we need to check them all for all/any logic.
          _this3.form.addEventListener($target, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)(eventType), function () {
            return $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', {
              bubbles: true,
              detail: {
                conditions: _this3
              }
            }));
          });
        });
      });
      return {
        showRule: conditionSettings.showRule,
        conditionRule: conditionSettings.conditionRule,
        isNested: conditionSettings.isNested || false,
        conditions: conditions
      };
    }
  }, {
    key: "evaluateConditions",
    value: function evaluateConditions(e) {
      var _this4 = this;
      var $field = e.target;
      var isInit = e.detail ? e.detail.init : false;

      // Get the prepped conditions for this field
      var conditionSettings = this.conditionsStore.get($field);
      if (!conditionSettings) {
        return;
      }
      var showRule = conditionSettings.showRule,
        conditionRule = conditionSettings.conditionRule,
        conditions = conditionSettings.conditions,
        isNested = conditionSettings.isNested,
        nestedFieldConditions = conditionSettings.nestedFieldConditions;
      var results = {};

      // Check if this condition is nested in a Group/Repeater field. Only proceed if the parent field
      // conditional evaluation has passed. But we don't want this to run on page load, as that'll setup initial state
      if (isNested && !isInit) {
        var $parentField = $field.closest('[data-field-type="group"], [data-field-type="repeater"]');
        if ($parentField) {
          // If the parent field is conditionally hidden, don't proceed further with testing this condition
          if ($parentField.conditionallyHidden) {
            return;
          }
        }
      }
      conditions.forEach(function (condition, i) {
        var logic = condition.condition,
          value = condition.value,
          $targets = condition.$targets,
          field = condition.field;

        // We're always dealing with a collection of targets, even if the target is a text field
        // The reason being is this normalises behaviour for some fields (checkbox/radio) that
        // have multiple fields in a group.
        $targets.forEach(function ($target) {
          var result = false;
          var testOptions = {};
          var tagName = $target.tagName.toLowerCase();
          var inputType = $target.getAttribute('type') ? $target.getAttribute('type').toLowerCase() : '';

          // Create a key for this condition rule that we'll use to store (potentially multiple) results against.
          // It's not visibly needed for anything, but using the target's field name helps with debugging.
          var resultKey = "".concat(field, "_").concat(i);

          // Store all results as an array, and we'll normalise afterwards. Group results by their condition rule.
          // For example: { dropdown_0: [false], radio_1: [true, false] }
          if (!results[resultKey]) {
            results[resultKey] = [];
          }

          // Handle some special options like dates - tell our condition tester about them
          if (inputType === 'date') {
            testOptions.isDate = true;
          }

          // Handle agree fields, which are a single checkbox, checked/unchecked
          if ($target.getAttribute('data-fui-input-type') === 'agree') {
            // Ignore the empty, hidden checkbox
            if (inputType === 'hidden') {
              return;
            }

            // Convert the value to boolean to compare
            result = _this4.testCondition(logic, value == '0' ? false : true, $target.checked);
            results[resultKey].push(result);
          } else if (inputType === 'checkbox' || inputType === 'radio') {
            // Handle (multi) checkboxes and radio, which are a bit of a pain
            result = _this4.testCondition(logic, value, $target.value) && $target.checked;
            results[resultKey].push(result);
          } else if (tagName === 'select' && $target.hasAttribute('multiple')) {
            // Handle multi-selects
            Array.from($target.options).forEach(function ($option) {
              result = _this4.testCondition(logic, value, $option.value) && $option.selected;
              results[resultKey].push(result);
            });
          } else {
            result = _this4.testCondition(logic, value, $target.value, testOptions);
            results[resultKey].push(result);
          }
        });
      });

      // Normalise the results before going further, as this'll be keyed as an object, so convert to an array
      // and because we can have multiple inputs, each with their own value, reduce them to a single boolean.
      // For example: { dropdown_0: [false], radio_1: [true, false] } changes to [false, true].
      var normalisedResults = [];
      Object.values(results).forEach(function (result) {
        normalisedResults.push(result.includes(true));
      });
      var finalResult = false;

      // Check to see how to compare the result (any or all).
      if (normalisedResults.length) {
        if (conditionRule === 'all') {
          // Are _all_ the conditions the same?
          finalResult = normalisedResults.every(function (val) {
            return val === true;
          });
        } else {
          finalResult = normalisedResults.includes(true);
        }
      }

      // Show or hide? Also toggle the disabled state to sort out any hidden required fields
      if (finalResult && showRule !== 'show' || !finalResult && showRule === 'show') {
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
      }

      // Update the parent row to show the correct number of visible fields
      this.updateRowVisibility($field);

      // Fire an event to notify that the field's conditions have been evaluated
      $field.dispatchEvent(new CustomEvent('onAfterFormieEvaluateConditions', {
        bubbles: true,
        detail: {
          conditions: this,
          init: isInit
        }
      }));

      // When triggering Group/Repeater conditions, ensure that we trigger any child conditions, now that the
      // Group/Repeater field has had its conditions evaluated. This is because inner fields aren't evaluated when
      // their outer parent is conditionally hidden, but when that parent field is shown, the fields inside should be evaludated.
      if (nestedFieldConditions && !isInit) {
        nestedFieldConditions.forEach(function ($nestedField) {
          _this4.evaluateConditions({
            target: $nestedField
          });
        });
      }
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
    value: function getEventType($input) {
      var $field = $input.closest('[data-field-type]');
      var fieldType = $field === null || $field === void 0 ? void 0 : $field.getAttribute('data-field-type');
      var tagName = $input.tagName.toLowerCase();
      var inputType = $input.getAttribute('type') ? $input.getAttribute('type').toLowerCase() : '';
      if (tagName === 'select' || inputType === 'date') {
        return 'change';
      }
      if (inputType === 'number') {
        return 'input';
      }
      if (inputType === 'checkbox' || inputType === 'radio') {
        return 'click';
      }

      // If sourcing a value from another calculations, this'll be a `input` event
      if (fieldType && fieldType === 'calculations') {
        return 'input';
      }
      return 'keyup';
    }
  }, {
    key: "testCondition",
    value: function testCondition(logic, value, fieldValue) {
      var testOptions = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
      var result = false;

      // Are we dealing with dates? That's a whole other mess...
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
    value: function querySelectorAllRegex($container, regex, attributeToSearch) {
      var output = [];
      var _iterator = _createForOfIteratorHelper($container.querySelectorAll("[".concat(attributeToSearch, "]"))),
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
  }, {
    key: "updateRowVisibility",
    value: function updateRowVisibility($field) {
      var $parent = $field.closest('[data-fui-field-count]');
      if ($parent) {
        var $fields = $parent.querySelectorAll('[data-field-handle]:not([data-conditionally-hidden])');
        $parent.setAttribute('data-fui-field-count', $fields.length);

        // Update the class if we have classes enabled
        if ($parent.classList.contains('fui-row')) {
          if ($fields.length === 0) {
            $parent.classList.add('fui-row-empty');
          } else {
            $parent.classList.remove('fui-row-empty');
          }
        }
      }
    }
  }]);
  return FormieConditions;
}();
window.FormieConditions = FormieConditions;
})();

/******/ })()
;