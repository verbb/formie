/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../../../../node_modules/@babel/runtime/regenerator/index.js":
/*!********************************************************************!*\
  !*** ../../../../node_modules/@babel/runtime/regenerator/index.js ***!
  \********************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__(/*! regenerator-runtime */ "../../../../node_modules/regenerator-runtime/runtime.js");


/***/ }),

/***/ "./src/js/formie-form-base.js":
/*!************************************!*\
  !*** ./src/js/formie-form-base.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieFormBase": () => (/* binding */ FormieFormBase)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _formie_form_theme__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./formie-form-theme */ "./src/js/formie-form-theme.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }



var FormieFormBase = /*#__PURE__*/function () {
  function FormieFormBase($form) {
    var _this = this;

    var config = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, FormieFormBase);

    this.$form = $form;
    this.config = config;
    this.settings = config.settings;
    this.listeners = {};

    if (!this.$form) {
      return;
    }

    this.$form.form = this;

    if (this.settings.outputJsTheme) {
      this.formTheme = new _formie_form_theme__WEBPACK_IMPORTED_MODULE_1__.FormieFormTheme(this.$form, this.config);
    } // Add helper classes to fields when their inputs are focused, have values etc.


    this.registerFieldEvents(this.$form); // Hijack the form's submit handler, in case we need to do something

    this.addEventListener(this.$form, 'submit', function (e) {
      e.preventDefault();

      var beforeSubmitEvent = _this.eventObject('onBeforeFormieSubmit', {
        submitHandler: _this
      });

      if (!_this.$form.dispatchEvent(beforeSubmitEvent)) {
        return;
      } // Add a little delay for UX


      setTimeout(function () {
        // Call the validation hooks
        if (!_this.validate() || !_this.afterValidate()) {
          return;
        } // Trigger Captchas


        if (!_this.validateCaptchas()) {
          return;
        } // Trigger Payment Integrations


        if (!_this.validatePayment()) {
          return;
        } // Proceed with submitting the form, which raises other validation events


        _this.submitForm();
      }, 300);
    }, false);
  }

  _createClass(FormieFormBase, [{
    key: "validate",
    value: function validate() {
      // Create an event for front-end validation (our own JS)
      var validateEvent = this.eventObject('onFormieValidate', {
        submitHandler: this
      });
      return this.$form.dispatchEvent(validateEvent);
    }
  }, {
    key: "afterValidate",
    value: function afterValidate() {
      // Create an event for after validation. This is mostly for third-parties.
      var afterValidateEvent = this.eventObject('onAfterFormieValidate', {
        submitHandler: this
      });
      return this.$form.dispatchEvent(afterValidateEvent);
    }
  }, {
    key: "validateCaptchas",
    value: function validateCaptchas() {
      // Create an event for captchas, separate to validation
      var validateEvent = this.eventObject('onFormieCaptchaValidate', {
        submitHandler: this
      });
      return this.$form.dispatchEvent(validateEvent);
    }
  }, {
    key: "validatePayment",
    value: function validatePayment() {
      // Create an event for payments, separate to validation
      var validateEvent = this.eventObject('onFormiePaymentValidate', {
        submitHandler: this
      });
      return this.$form.dispatchEvent(validateEvent);
    }
  }, {
    key: "submitForm",
    value: function submitForm() {
      var submitEvent = this.eventObject('onFormieSubmit', {
        submitHandler: this
      });

      if (!this.$form.dispatchEvent(submitEvent)) {
        return;
      }

      if (this.settings.submitMethod === 'ajax') {
        this.formAfterSubmit();
      } else {
        this.$form.submit();
      }
    }
  }, {
    key: "formAfterSubmit",
    value: function formAfterSubmit() {
      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      // Add redirect behaviour for iframes to control the target
      data.redirectTarget = data.redirectTarget || window;
      this.$form.dispatchEvent(new CustomEvent('onAfterFormieSubmit', {
        bubbles: true,
        detail: data
      })); // Ensure that once completed, we re-fetch the captcha value, which will have expired

      if (!data.nextPageId) {
        // Use `this.config.Formie` just in case we're not loading thie script in the global window
        // (i.e. when users import this script in their own).
        this.config.Formie.refreshFormTokens(this);
      }
    }
  }, {
    key: "formSubmitError",
    value: function formSubmitError() {
      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      this.$form.dispatchEvent(new CustomEvent('onFormieSubmitError', {
        bubbles: true,
        detail: data
      }));
    }
  }, {
    key: "formDestroy",
    value: function formDestroy() {
      var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      this.$form.dispatchEvent(new CustomEvent('onFormieDestroy', {
        bubbles: true,
        detail: data
      }));
    }
  }, {
    key: "registerFieldEvents",
    value: function registerFieldEvents($element) {
      var _this2 = this;

      var $wrappers = $element.querySelectorAll('[data-field-type]');
      $wrappers.forEach(function ($wrapper) {
        var $input = $wrapper.querySelector('input, select');

        if ($input) {
          _this2.addEventListener($input, 'input', function (event) {
            $wrapper.dispatchEvent(new CustomEvent('input', {
              bubbles: false,
              detail: {
                input: event.target
              }
            }));
          });

          _this2.addEventListener($input, 'focus', function (event) {
            $wrapper.dispatchEvent(new CustomEvent('focus', {
              bubbles: false,
              detail: {
                input: event.target
              }
            }));
          });

          _this2.addEventListener($input, 'blur', function (event) {
            $wrapper.dispatchEvent(new CustomEvent('blur', {
              bubbles: false,
              detail: {
                input: event.target
              }
            }));
          });

          $wrapper.dispatchEvent(new CustomEvent('init', {
            bubbles: false,
            detail: {
              input: $input
            }
          }));
        }
      });
    }
  }, {
    key: "addEventListener",
    value: function addEventListener(element, event, func) {
      // If the form is marked as destroyed, don't add any more event listeners.
      // This can often happen with captchas or payment integrations which are done as they appear on page.
      if (!this.destroyed) {
        this.listeners[event] = {
          element: element,
          func: func
        };
        var eventName = event.split('.')[0];
        element.addEventListener(eventName, this.listeners[event].func);
      }
    }
  }, {
    key: "removeEventListener",
    value: function removeEventListener(event) {
      var eventInfo = this.listeners[event] || {};

      if (eventInfo && eventInfo.element && eventInfo.func) {
        var eventName = event.split('.')[0];
        eventInfo.element.removeEventListener(eventName, eventInfo.func);
        delete this.listeners[event];
      }
    }
  }, {
    key: "eventObject",
    value: function eventObject(name, detail) {
      return new CustomEvent(name, {
        bubbles: true,
        cancelable: true,
        detail: detail
      });
    }
  }, {
    key: "getThemeConfigAttributes",
    value: function getThemeConfigAttributes(key) {
      var attributes = this.settings.themeConfig || {};
      return attributes[key] || {};
    }
  }, {
    key: "getClasses",
    value: function getClasses(key) {
      return this.getThemeConfigAttributes(key)["class"] || [];
    }
  }, {
    key: "applyThemeConfig",
    value: function applyThemeConfig($element, key) {
      var applyClass = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var attributes = this.getThemeConfigAttributes(key);

      if (attributes) {
        Object.entries(attributes).forEach(function (_ref) {
          var _ref2 = _slicedToArray(_ref, 2),
              attribute = _ref2[0],
              value = _ref2[1];

          if (attribute === 'class' && !applyClass) {
            return;
          }

          $element.setAttribute(attribute, value);
        });
      }
    }
  }]);

  return FormieFormBase;
}();

/***/ }),

/***/ "./src/js/formie-form-theme.js":
/*!*************************************!*\
  !*** ./src/js/formie-form-theme.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieFormTheme": () => (/* binding */ FormieFormTheme)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _utils_bouncer__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/bouncer */ "./src/js/utils/bouncer.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e2) { throw _e2; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e3) { didErr = true; err = _e3; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }



var FormieFormTheme = /*#__PURE__*/function () {
  function FormieFormTheme($form) {
    var config = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    _classCallCheck(this, FormieFormTheme);

    this.$form = $form;
    this.config = config;
    this.settings = config.settings;
    this.validationOnSubmit = !!this.settings.validationOnSubmit;
    this.validationOnFocus = !!this.settings.validationOnFocus;
    this.setCurrentPage(this.settings.currentPageId);

    if (!this.$form) {
      return;
    }

    this.$form.formTheme = this;
    this.form = this.$form.form; // Setup classes according to theme config

    this.loadingClass = this.form.getClasses('loading');
    this.tabErrorClass = this.form.getClasses('tabError');
    this.tabActiveClass = this.form.getClasses('tabActive');
    this.tabCompleteClass = this.form.getClasses('tabComplete');
    this.errorMessageClass = this.form.getClasses('errorMessage');
    this.successMessageClass = this.form.getClasses('successMessage');
    this.alertClass = this.form.getClasses('alert');
    this.alertErrorClass = this.form.getClasses('alertError');
    this.alertSuccessClass = this.form.getClasses('alertSuccess');
    this.tabClass = this.form.getClasses('tab');
    this.initValidator(); // Check if this is a success page and if we need to hide the notice
    // This is for non-ajax forms, where the page has reloaded

    this.hideSuccess(); // Hijack the form's submit handler, in case we need to do something

    this.addSubmitEventListener(); // Save the form's current state so we can tell if its changed later on

    this.updateFormHash(); // Listen to form changes if the user tries to reload

    if (this.settings.enableUnloadWarning) {
      this.addFormUnloadEventListener();
    } // Listen to tabs being clicked for ajax-enabled forms


    if (this.settings.submitMethod === 'ajax') {
      this.formTabEventListener();
    }
  }

  _createClass(FormieFormTheme, [{
    key: "initValidator",
    value: function initValidator() {
      var _this = this;

      // Kick off validation - use this even if disabling client-side validation
      // so we can use a nice API handle server-side errprs
      var validatorSettings = {
        fieldClass: 'fui-error',
        errorClass: this.form.getClasses('fieldError'),
        fieldPrefix: 'fui-field-',
        errorPrefix: 'fui-error-',
        messageAfterField: true,
        messageCustom: 'data-fui-message',
        messageTarget: 'data-fui-target',
        validateOnBlur: this.validationOnFocus,
        // Call validation on-demand
        validateOnSubmit: false,
        disableSubmit: false,
        customValidations: {},
        messages: {
          missingValue: {
            checkbox: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('This field is required.'),
            radio: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please select a value.'),
            select: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please select a value.'),
            'select-multiple': (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please select at least one value.'),
            "default": (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please fill out this field.')
          },
          patternMismatch: {
            email: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please enter a valid email address.'),
            url: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please enter a URL.'),
            number: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please enter a number'),
            color: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please match the following format: #rrggbb'),
            date: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please use the YYYY-MM-DD format'),
            time: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please use the 24-hour time format. Ex. 23:00'),
            month: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please use the YYYY-MM format'),
            "default": (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please match the requested format.')
          },
          outOfRange: {
            over: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please select a value that is no more than {max}.'),
            under: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please select a value that is no less than {min}.')
          },
          wrongLength: {
            over: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please shorten this text to no more than {maxLength} characters. You are currently using {length} characters.'),
            under: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Please lengthen this text to {minLength} characters or more. You are currently using {length} characters.')
          },
          fallback: (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('There was an error with this field.')
        }
      }; // Allow other modules to modify our validator settings (for custom rules and messages)

      var registerFormieValidation = new CustomEvent('registerFormieValidation', {
        bubbles: true,
        detail: {
          validatorSettings: validatorSettings
        }
      }); // Give a small amount of time for other JS scripts to register validations. These are lazy-loaded.
      // Maybe re-think this so we don't have to deal with event listener registration before/after dispatch?

      setTimeout(function () {
        _this.$form.dispatchEvent(registerFormieValidation);

        _this.validator = new _utils_bouncer__WEBPACK_IMPORTED_MODULE_1__.Bouncer(_this.$form, registerFormieValidation.detail.validatorSettings);
      }, 500); // After we clear any error, validate the fielset again. Mostly so we can remove global errors

      this.form.addEventListener(this.$form, 'bouncerRemoveError', function (e) {
        // Prevent an infinite loop (check behaviour with an Agree field)
        // https://github.com/verbb/formie/issues/905
        if (!_this.submitDebounce) {
          _this.validate(false);
        }
      }); // Override error messages defined in DOM - Bouncer only uses these as a last resort
      // In future updates, we can probably remove this

      this.form.addEventListener(this.$form, 'bouncerShowError', function (e) {
        var message = null;
        var $field = e.target;
        var $fieldContainer = $field.closest('[data-field-type]'); // Check if we need to move the error out of the .fui-input-container node.
        // Only the input itself should be in here.

        var $errorToMove = $field.parentNode.querySelector('[data-error-message]');

        if ($errorToMove && $errorToMove.parentNode.parentNode) {
          $errorToMove.parentNode.parentNode.appendChild($errorToMove);
        } // Only swap out any custom error message for "required" fields, so as not to override other messages


        if (e.detail && e.detail.errors && (e.detail.errors.missingValue || e.detail.errors.serverMessage)) {
          // Get the error message as defined on the input element. Use the parent to find the element
          // just to cater for some edge-cases where there might be multiple inputs (Datepicker).
          var $message = $field.parentNode.querySelector('[data-fui-message]');

          if ($message) {
            message = $message.getAttribute('data-fui-message');
          } // If there's a server error, it takes priority.


          if (e.detail.errors.serverMessage) {
            message = e.detail.errors.serverMessage;
          } // The error has been moved, find it again


          if ($fieldContainer) {
            var $error = $fieldContainer.querySelector('[data-error-message]');

            if ($error && message) {
              $error.textContent = message;
            }
          }
        }
      }, false);
    }
  }, {
    key: "addSubmitEventListener",
    value: function addSubmitEventListener() {
      var _this2 = this;

      var $submitBtns = this.$form.querySelectorAll('[type="submit"]'); // Forms can have multiple submit buttons, and its easier to assign the currently clicked one
      // than tracking it through the submit handler.

      $submitBtns.forEach(function ($submitBtn) {
        _this2.form.addEventListener($submitBtn, 'click', function (e) {
          _this2.$submitBtn = e.target; // Store for later if we're using text spinner

          _this2.originalButtonText = _this2.$submitBtn.textContent.trim();
          var submitAction = _this2.$submitBtn.getAttribute('data-submit-action') || 'submit'; // Each submit button can do different things, to store that

          _this2.updateSubmitAction(submitAction);
        });
      });
      this.form.addEventListener(this.$form, 'onBeforeFormieSubmit', this.onBeforeSubmit.bind(this));
      this.form.addEventListener(this.$form, 'onFormieValidate', this.onValidate.bind(this));
      this.form.addEventListener(this.$form, 'onFormieSubmit', this.onSubmit.bind(this));
      this.form.addEventListener(this.$form, 'onFormieSubmitError', this.onSubmitError.bind(this));
    }
  }, {
    key: "onBeforeSubmit",
    value: function onBeforeSubmit(e) {
      this.beforeSubmit(); // Save for later to trigger real submit

      this.submitHandler = e.detail.submitHandler;
    }
  }, {
    key: "onValidate",
    value: function onValidate(e) {
      // If invalid, we only want to stop if we're submitting.
      if (!this.validate()) {
        this.onFormError(); // Set a flag on the event, so other listeners can potentially do something

        e.detail.invalid = true;
        e.preventDefault();
      }
    }
  }, {
    key: "onSubmit",
    value: function onSubmit(e) {
      // Stop base behaviour of just submitting the form
      e.preventDefault(); // Either staight submit, or use Ajax

      if (this.settings.submitMethod === 'ajax') {
        this.ajaxSubmit();
      } else {
        // Before a server-side submit, refresh the saved hash immediately. Otherwise, the native submit
        // handler - which technically unloads the page - will trigger the changed alert.
        // But trigger an alert if we're going back, and back-submission data isn't set
        if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {// Don't reset the hash, trigger a warning if content has changed, because we're not submitting
        } else {
          this.updateFormHash();
        } // Triger any JS events for this page, only if submitting (not going back/saving)


        if (this.form.submitAction === 'submit') {
          this.triggerJsEvents();
        }

        this.$form.submit();
      }
    }
  }, {
    key: "onSubmitError",
    value: function onSubmitError(e) {
      this.onFormError();
    }
  }, {
    key: "addFormUnloadEventListener",
    value: function addFormUnloadEventListener() {
      var _this3 = this;

      this.form.addEventListener(window, 'beforeunload', function (e) {
        if (_this3.savedFormHash !== _this3.hashForm()) {
          e.preventDefault();
          return e.returnValue = (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Are you sure you want to leave?');
        }
      });
    }
  }, {
    key: "formTabEventListener",
    value: function formTabEventListener() {
      var _this4 = this;

      var $tabs = this.$form.querySelectorAll('[data-fui-page-tab-anchor]');
      $tabs.forEach(function ($tab) {
        _this4.form.addEventListener($tab, 'click', function (e) {
          e.preventDefault();
          var pageIndex = e.target.getAttribute('data-fui-page-index');
          var pageId = e.target.getAttribute('data-fui-page-id');

          _this4.togglePage({
            nextPageIndex: pageIndex,
            nextPageId: pageId,
            totalPages: _this4.settings.pages.length
          }); // Ensure we still update the current page server-side


          var xhr = new XMLHttpRequest();
          xhr.open('GET', e.target.getAttribute('href'), true);
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
          xhr.setRequestHeader('Accept', 'application/json');
          xhr.setRequestHeader('Cache-Control', 'no-cache');
          xhr.send();
        });
      });
    }
  }, {
    key: "hashForm",
    value: function hashForm() {
      var hash = {};
      var formData = new FormData(this.$form); // Exlcude some params from the hash, that are programatically changed
      // TODO, allow some form of registration for captchas.

      var excludedItems = ['g-recaptcha-response', 'h-captcha-response', 'CRAFT_CSRF_TOKEN', '__JSCHK', '__DUP', 'beesknees', 'cf-turnstile-response', 'frc-captcha-solution', 'submitAction'];

      var _iterator = _createForOfIteratorHelper(formData.entries()),
          _step;

      try {
        var _loop = function _loop() {
          var pair = _step.value;
          var isExcluded = excludedItems.filter(function (item) {
            return pair[0].startsWith(item);
          });

          if (!isExcluded.length) {
            // eslint-disable-next-line
            hash[pair[0]] = pair[1];
          }
        };

        for (_iterator.s(); !(_step = _iterator.n()).done;) {
          _loop();
        }
      } catch (err) {
        _iterator.e(err);
      } finally {
        _iterator.f();
      }

      return JSON.stringify(hash);
    }
  }, {
    key: "updateFormHash",
    value: function updateFormHash() {
      this.savedFormHash = this.hashForm();
    }
  }, {
    key: "validate",
    value: function validate() {
      var _this5 = this;

      var focus = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

      if (!this.validationOnSubmit) {
        return true;
      } // Only validate on submit actions


      if (this.form.submitAction !== 'submit') {
        return true;
      }

      var $fieldset = this.$form;

      if (this.$currentPage) {
        $fieldset = this.$currentPage;
      }

      var invalidFields = this.validator.validateAll($fieldset); // If there are errors, focus on the first one

      if (invalidFields.length > 0 && focus) {
        invalidFields[0].focus();
      } // Remove any global errors if none - just in case


      if (invalidFields.length === 0) {
        this.removeFormAlert();
      } // Set the debounce after a little bit, to prevent an infinite loop, as this method
      // is called on `bouncerRemoveError`.


      this.submitDebounce = true;
      setTimeout(function () {
        _this5.submitDebounce = false;
      }, 500);
      return !invalidFields.length;
    }
  }, {
    key: "hideSuccess",
    value: function hideSuccess() {
      var $successMessage = this.$form.parentNode.querySelector(".".concat(this.successMessageClass));

      if ($successMessage && this.settings.submitActionMessageTimeout) {
        var timeout = parseInt(this.settings.submitActionMessageTimeout, 10) * 1000;
        setTimeout(function () {
          $successMessage.remove();
        }, timeout);
      }
    }
  }, {
    key: "addLoading",
    value: function addLoading() {
      if (this.$submitBtn) {
        // Always disable the button
        this.$submitBtn.setAttribute('disabled', true);

        if (this.settings.loadingIndicator === 'spinner') {
          this.$submitBtn.classList.add(this.loadingClass);
        }

        if (this.settings.loadingIndicator === 'text') {
          this.$submitBtn.textContent = this.settings.loadingIndicatorText;
        }
      }
    }
  }, {
    key: "removeLoading",
    value: function removeLoading() {
      if (this.$submitBtn) {
        // Always enable the button
        this.$submitBtn.removeAttribute('disabled');

        if (this.settings.loadingIndicator === 'spinner') {
          this.$submitBtn.classList.remove(this.loadingClass);
        }

        if (this.settings.loadingIndicator === 'text') {
          this.$submitBtn.textContent = this.originalButtonText;
        }
      }
    }
  }, {
    key: "onFormError",
    value: function onFormError(errorMessage) {
      if (errorMessage) {
        this.showFormAlert(errorMessage, 'error');
      } else {
        this.showFormAlert(this.settings.errorMessage, 'error');
      }

      this.removeLoading();
    }
  }, {
    key: "showFormAlert",
    value: function showFormAlert(text, type) {
      var $alert = this.$form.parentNode.querySelector('[data-fui-alert]');

      if ($alert) {
        // We have to cater for HTML entities - quick-n-dirty
        if ($alert.innerHTML !== this.decodeHtml(text)) {
          $alert.innerHTML = "".concat($alert.innerHTML, "<br>").concat(text);
        }
      } else {
        $alert = document.createElement('div');
        $alert.className = this.alertClass;
        $alert.setAttribute('role', 'alert');
        $alert.setAttribute('data-fui-alert', 'true');
        $alert.innerHTML = text; // Set attributes on the alert according to theme config

        this.form.applyThemeConfig($alert, 'alert', false); // For error notices, we have potential special handling on position

        if (type == 'error') {
          this.form.applyThemeConfig($alert, 'alertError', false);
          $alert.className += " ".concat(this.alertErrorClass, " ").concat(this.alertClass, "-").concat(this.settings.errorMessagePosition);

          if (this.settings.errorMessagePosition == 'bottom-form') {
            this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
          } else if (this.settings.errorMessagePosition == 'top-form') {
            this.$form.parentNode.insertBefore($alert, this.$form);
          }
        } else {
          this.form.applyThemeConfig($alert, 'alertSuccess', false);
          $alert.className += " ".concat(this.alertSuccessClass, " ").concat(this.alertClass, "-").concat(this.settings.submitActionMessagePosition);

          if (this.settings.submitActionMessagePosition == 'bottom-form') {
            // An even further special case when hiding the form!
            if (this.settings.submitActionFormHide) {
              this.$form.parentNode.insertBefore($alert, this.$form);
            } else if (this.$submitBtn.parentNode) {
              // Check if there's a submit button still. Might've been removed for multi-page, ajax.
              this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
            } else {
              this.$form.parentNode.insertBefore($alert, this.$form.nextSibling);
            }
          } else if (this.settings.submitActionMessagePosition == 'top-form') {
            this.$form.parentNode.insertBefore($alert, this.$form);
          }
        }
      }
    }
  }, {
    key: "showTabErrors",
    value: function showTabErrors(errors) {
      var _this6 = this;

      Object.keys(errors).forEach(function (pageId, index) {
        var $tab = _this6.$form.parentNode.querySelector("[data-fui-page-id=\"".concat(pageId, "\"]"));

        if ($tab) {
          $tab.parentNode.classList.add(_this6.tabErrorClass);
        }
      });
    }
  }, {
    key: "decodeHtml",
    value: function decodeHtml(html) {
      var txt = document.createElement('textarea');
      txt.innerHTML = html;
      return txt.value;
    }
  }, {
    key: "removeFormAlert",
    value: function removeFormAlert() {
      var $alert = this.$form.parentNode.querySelector(".".concat(this.alertClass));

      if ($alert) {
        $alert.remove();
      }
    }
  }, {
    key: "removeTabErrors",
    value: function removeTabErrors() {
      var _this7 = this;

      var $tabs = this.$form.parentNode.querySelectorAll('[data-fui-page-tab]');
      $tabs.forEach(function ($tab) {
        $tab.classList.remove(_this7.tabErrorClass);
      });
    }
  }, {
    key: "beforeSubmit",
    value: function beforeSubmit() {
      var _this8 = this;

      // Remove all validation errors
      Array.prototype.filter.call(this.$form.querySelectorAll('input, select, textarea'), function ($field) {
        _this8.validator.removeError($field);
      });
      this.removeFormAlert();
      this.removeTabErrors(); // Don't set a loading if we're going back and the unload warning appears, because there's no way to re-enable
      // the button after the user cancels the unload event

      if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {// Do nothing
      } else {
        this.addLoading();
      }
    }
  }, {
    key: "ajaxSubmit",
    value: function ajaxSubmit() {
      var _this9 = this;

      var formData = new FormData(this.$form);
      var method = this.$form.getAttribute('method');
      var action = this.$form.getAttribute('action');
      var xhr = new XMLHttpRequest();
      xhr.open(method ? method : 'POST', action ? action : window.location.href, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('Cache-Control', 'no-cache');
      xhr.timeout = (this.settings.ajaxTimeout || 10) * 1000;
      this.beforeSubmit();

      xhr.ontimeout = function () {
        _this9.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request timed out.'));
      };

      xhr.onerror = function (e) {
        _this9.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request encountered a network error. Please try again.'));
      };

      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var response = JSON.parse(xhr.responseText);

            if (response.errors) {
              _this9.onAjaxError(response.errorMessage, response);
            } else {
              _this9.onAjaxSuccess(response);
            }
          } catch (e) {
            _this9.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Unable to parse response `{e}`.', {
              e: e
            }));
          }
        } else {
          _this9.onAjaxError("".concat(xhr.status, ": ").concat(xhr.statusText));
        }
      };

      xhr.send(formData);
    }
  }, {
    key: "afterAjaxSubmit",
    value: function afterAjaxSubmit(data) {
      var _this10 = this;

      // Reset the submit action, immediately, whether fail or success
      this.updateSubmitAction('submit');
      this.updateSubmissionInput(data); // Check if there's any events in the response back, and fire them

      if (data.events && Array.isArray(data.events) && data.events.length) {
        // An error message may be shown in some cases (for 3D secure) so remove the form-global level error notice.
        this.removeFormAlert();
        data.events.forEach(function (eventData) {
          _this10.$form.dispatchEvent(new CustomEvent(eventData.event, {
            bubbles: true,
            detail: {
              data: eventData.data
            }
          }));
        });
      }
    }
  }, {
    key: "onAjaxError",
    value: function onAjaxError(errorMessage) {
      var _this11 = this;

      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var errors = data.errors || {};
      var pageFieldErrors = data.pageFieldErrors || {}; // Show an error message at the top of the form

      this.onFormError(errorMessage); // Update the page tabs (if any) to show error state

      this.showTabErrors(pageFieldErrors); // Fire a fail event

      this.submitHandler.formSubmitError(data); // Fire cleanup methods after _any_ ajax call

      this.afterAjaxSubmit(data); // Show server-side errors for each field

      Object.keys(errors).forEach(function (handle, index) {
        var _errors$handle = _slicedToArray(errors[handle], 1),
            error = _errors$handle[0];

        var selector = handle.split('.');
        selector = selector.join('][');

        var $field = _this11.$form.querySelector("[name=\"fields[".concat(selector, "]\"]")); // Check for multiple fields


        if (!$field) {
          $field = _this11.$form.querySelector("[name=\"fields[".concat(selector, "][]\"]"));
        } // Handle Repeater/Groups - a little more complicated to translate `group[0].field.handle`


        if (!$field && handle.includes('[')) {
          var blockIndex = handle.match(/\[(.*?)\]/)[1] || null;
          var regexString = "fields[".concat(handle.replace(/\./g, '][').replace(']]', ']').replace(/\[.*?\]/, '][rows][.*][fields]'), "]");
          regexString = regexString.replace(/\[/g, '\\[').replace(/\]/g, '\\]');

          var $targets = _this11.querySelectorAllRegex(new RegExp(regexString), 'name');

          if ($targets.length && $targets[blockIndex]) {
            $field = $targets[blockIndex];
          }
        }

        if ($field) {
          _this11.validator.showError($field, {
            serverMessage: error
          }); // Focus on the first error


          if (index === 0) {
            $field.focus();
          }
        }
      }); // Go to the first page with an error, for good UX

      this.togglePage(data, false);
    }
  }, {
    key: "onAjaxSuccess",
    value: function onAjaxSuccess(data) {
      // Fire the event, because we've overridden the handler
      this.submitHandler.formAfterSubmit(data); // Fire cleanup methods after _any_ ajax call

      this.afterAjaxSubmit(data); // Reset the form hash, as all has been saved

      this.updateFormHash(); // Triger any JS events for this page, right away before navigating away

      if (this.form.submitAction === 'submit') {
        this.triggerJsEvents();
      } // Check if we need to proceed to the next page


      if (data.nextPageId) {
        this.removeLoading();
        this.togglePage(data);
        return;
      } // If people have provided a redirect behaviour to handle their own redirecting


      if (data.redirectCallback) {
        data.redirectCallback();
        return;
      } // If we're redirecting away, do it immediately for nicer UX


      if (data.redirectUrl) {
        if (this.settings.submitActionTab === 'new-tab') {
          // Reset values if in a new tab. No need when in the same tab.
          this.resetForm(); // Allow people to modify the target from `window` with `redirectTarget`

          data.redirectTarget.open(data.redirectUrl, '_blank');
        } else {
          data.redirectTarget.location.href = data.redirectUrl;
        }

        return;
      } // Delay this a little, in case we're redirecting away - better UX to just keep it loading


      this.removeLoading(); // For multi-page ajax forms, deal with them a little differently.

      if (data.totalPages > 1) {
        // If we have a success message at the top, go to the first page
        if (this.settings.submitActionMessagePosition == 'top-form') {
          this.togglePage({
            nextPageIndex: 0,
            nextPageId: this.settings.pages[0].id,
            totalPages: this.settings.pages.length
          });
        } else {
          // Otherwise, we want to hide the buttons because we have to stay on the last page
          // to show the success message at the bottom of the form. Otherwise, showing it on the
          // first page of an empty form is just plain weird UX.
          if (this.$submitBtn) {
            this.$submitBtn.remove();
          } // Remove the back button - not great UX to go back to a finished form
          // Remember, its the button and the hidden input


          var $backButtonInputs = this.$form.querySelectorAll('[data-submit-action="back"]');
          $backButtonInputs.forEach(function ($backButtonInput) {
            $backButtonInput.remove();
          });
        }
      }

      if (this.settings.submitAction === 'message') {
        // Allow the submit action message to be sent from the response, or fallback to static.
        var submitActionMessage = data.submitActionMessage || this.settings.submitActionMessage;
        this.showFormAlert(submitActionMessage, 'success'); // Check if we need to remove the success message

        this.hideSuccess();

        if (this.settings.submitActionFormHide) {
          this.$form.style.display = 'none';
        } // Smooth-scroll to the top of the form.


        if (this.settings.scrollToTop) {
          this.scrollToForm();
        }
      } // Reset values regardless, for the moment


      this.resetForm(); // Remove the submission ID input in case we want to go again

      this.removeHiddenInput('submissionId'); // Reset the form hash, as all has been saved

      this.updateFormHash();
    }
  }, {
    key: "updateSubmitAction",
    value: function updateSubmitAction(action) {
      // All buttons should have a `[data-submit-action]` but just for backward-compatibility
      // assume when not present, we're submitting
      if (!action) {
        action = 'submit';
      } // Update the submit action on the form while we're at it. Store on the `$form`
      // for each of lookup on event hooks like captchas.


      this.form.submitAction = action;
      this.updateOrCreateHiddenInput('submitAction', action);
    }
  }, {
    key: "updateSubmissionInput",
    value: function updateSubmissionInput(data) {
      if (!data.submissionId || !data.nextPageId) {
        return;
      } // Add the hidden submission input, if it doesn't exist


      this.updateOrCreateHiddenInput('submissionId', data.submissionId);
    }
  }, {
    key: "updateOrCreateHiddenInput",
    value: function updateOrCreateHiddenInput(name, value) {
      var $input = this.$form.querySelector("[name=\"".concat(name, "\"][type=\"hidden\"]"));

      if (!$input) {
        $input = document.createElement('input');
        $input.setAttribute('type', 'hidden');
        $input.setAttribute('name', name);
        this.$form.appendChild($input);
      }

      $input.setAttribute('value', value);
    }
  }, {
    key: "resetForm",
    value: function resetForm() {
      // `$form.reset()` will do most, but programatically setting `checked` for checkboxes won't be cleared
      this.$form.reset();
      this.$form.querySelectorAll('[type="checkbox"]').forEach(function ($checkbox) {
        $checkbox.removeAttribute('checked');
      });
    }
  }, {
    key: "removeHiddenInput",
    value: function removeHiddenInput(name) {
      var $input = this.$form.querySelector("[name=\"".concat(name, "\"][type=\"hidden\"]"));

      if ($input) {
        $input.parentNode.removeChild($input);
      }
    }
  }, {
    key: "togglePage",
    value: function togglePage(data) {
      var _this12 = this;

      var scrollToTop = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      // Trigger an event when a page is toggled
      this.$form.dispatchEvent(new CustomEvent('onFormiePageToggle', {
        bubbles: true,
        detail: {
          data: data
        }
      })); // Hide all pages

      var $allPages = this.$form.querySelectorAll('[data-fui-page]');

      if (data.nextPageId) {
        $allPages.forEach(function ($page) {
          // Show the current page
          if ($page.id === "".concat(_this12.getPageId(data.nextPageId))) {
            $page.removeAttribute('data-fui-page-hidden');
          } else {
            $page.setAttribute('data-fui-page-hidden', true);
          }
        });
      } // Update tabs and progress bar if we're using them


      var $progress = this.$form.querySelector('[data-fui-progress-bar]');

      if ($progress && data.nextPageIndex >= 0) {
        var pageIndex = parseInt(data.nextPageIndex, 10) + 1;
        var progress = Math.round(pageIndex / data.totalPages * 100);
        $progress.style.width = "".concat(progress, "%");
        $progress.setAttribute('aria-valuenow', progress);
        $progress.textContent = "".concat(progress, "%");
      }

      var $tabs = this.$form.querySelectorAll('[data-fui-page-tab]');

      if (data.nextPageId) {
        $tabs.forEach(function ($tab) {
          // Show the current page
          if ($tab.id === "".concat(_this12.tabClass, "-").concat(data.nextPageId)) {
            $tab.classList.add(_this12.tabActiveClass);
          } else {
            $tab.classList.remove(_this12.tabActiveClass);
          }
        });
        var isComplete = true;
        $tabs.forEach(function ($tab) {
          if ($tab.classList.contains(_this12.tabActiveClass)) {
            isComplete = false;
          }

          if (isComplete) {
            $tab.classList.add(_this12.tabCompleteClass);
          } else {
            $tab.classList.remove(_this12.tabCompleteClass);
          }
        }); // Update the current page

        this.setCurrentPage(data.nextPageId);
      } // Smooth-scroll to the top of the form.


      if (this.settings.scrollToTop) {
        this.scrollToForm();
      }
    }
  }, {
    key: "setCurrentPage",
    value: function setCurrentPage(pageId) {
      this.settings.currentPageId = pageId;
      this.$currentPage = this.$form.querySelector("#".concat(this.getPageId(pageId)));
    }
  }, {
    key: "getCurrentPage",
    value: function getCurrentPage() {
      var _this13 = this;

      return this.settings.pages.find(function (page) {
        return page.id == _this13.settings.currentPageId;
      });
    }
  }, {
    key: "getCurrentPageIndex",
    value: function getCurrentPageIndex() {
      var currentPage = this.getCurrentPage();

      if (currentPage) {
        return this.settings.pages.indexOf(currentPage);
      }

      return 0;
    }
  }, {
    key: "getPageId",
    value: function getPageId(pageId) {
      return "".concat(this.config.formHashId, "-p-").concat(pageId);
    }
  }, {
    key: "scrollToForm",
    value: function scrollToForm() {
      // Check for scroll-padding-top or `scroll-margin-top`
      var extraPadding = parseInt(getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
      var extraMargin = parseInt(getComputedStyle(document.documentElement).scrollMarginTop) || 0; // Because the form can be hidden, use the parent wrapper

      window.scrollTo({
        top: this.$form.parentNode.getBoundingClientRect().top + window.pageYOffset - 100 - extraPadding - extraMargin,
        behavior: 'smooth'
      });
    }
  }, {
    key: "triggerJsEvents",
    value: function triggerJsEvents() {
      var currentPage = this.getCurrentPage(); // Find any JS events for the current page and fire

      if (currentPage && currentPage.settings.enableJsEvents) {
        var payload = {};
        currentPage.settings.jsGtmEventOptions.forEach(function (option) {
          payload[option.label] = option.value;
        }); // Push to the datalayer

        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(payload);
      }
    }
  }, {
    key: "querySelectorAllRegex",
    value: function querySelectorAllRegex(regex, attributeToSearch) {
      var output = [];

      var _iterator2 = _createForOfIteratorHelper(this.$form.querySelectorAll("[".concat(attributeToSearch, "]"))),
          _step2;

      try {
        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
          var element = _step2.value;

          if (regex.test(element.getAttribute(attributeToSearch))) {
            output.push(element);
          }
        }
      } catch (err) {
        _iterator2.e(err);
      } finally {
        _iterator2.f();
      }

      return output;
    }
  }]);

  return FormieFormTheme;
}();

/***/ }),

/***/ "./src/js/formie-lib.js":
/*!******************************!*\
  !*** ./src/js/formie-lib.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Formie": () => (/* binding */ Formie)
/* harmony export */ });
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/regenerator */ "../../../../node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _formie_form_base__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./formie-form-base */ "./src/js/formie-form-base.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }



function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }



var Formie = /*#__PURE__*/function () {
  function Formie() {
    _classCallCheck(this, Formie);

    this.forms = [];
  }

  _createClass(Formie, [{
    key: "initForms",
    value: function initForms() {
      var _this = this;

      this.$forms = document.querySelectorAll('form[data-fui-form]') || []; // We use this in the CP, where it's a bit tricky to add a form ID. So check just in case.
      // Might also be handy for front-end too!

      if (!this.$forms.length) {
        this.$forms = document.querySelectorAll('div[data-fui-form]') || [];
      }

      this.$forms.forEach(function ($form) {
        _this.initForm($form);
      }); // Emit a custom event to let scripts know the Formie class is ready

      document.dispatchEvent(new CustomEvent('onFormieInit', {
        bubbles: true,
        detail: {
          formie: this
        }
      }));
    }
  }, {
    key: "initForm",
    value: function () {
      var _initForm = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee($form) {
        var _this2 = this;

        var formConfig,
            initializeForm,
            registeredJs,
            form,
            _args = arguments;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                formConfig = _args.length > 1 && _args[1] !== undefined ? _args[1] : {};

                if ((0,_utils_utils__WEBPACK_IMPORTED_MODULE_1__.isEmpty)(formConfig)) {
                  // Initialize the form class with the `data-fui-form` param on the form
                  formConfig = JSON.parse($form.getAttribute('data-fui-form'));
                }

                if (!(0,_utils_utils__WEBPACK_IMPORTED_MODULE_1__.isEmpty)(formConfig)) {
                  _context.next = 5;
                  break;
                }

                console.error('Unable to parse `data-fui-form` form attribute for config. Ensure this attribute exists on your form and contains valid JSON.');
                return _context.abrupt("return");

              case 5:
                // Check if we are initializing a form multiple times
                initializeForm = this.getFormByHashId(formConfig.formHashId);

                if (!initializeForm) {
                  _context.next = 9;
                  break;
                }

                _context.next = 9;
                return this.destroyForm(initializeForm);

              case 9:
                // See if we need to init additional, conditional JS (field, captchas, etc)
                registeredJs = formConfig.registeredJs || []; // Add an instance to this factory to the form config

                formConfig.Formie = this; // Create the form class, save it to our collection

                form = new _formie_form_base__WEBPACK_IMPORTED_MODULE_2__.FormieFormBase($form, formConfig);
                this.forms.push(form); // Find all `data-field-config` attributes for the current page and form
                // and build an object of them to initialize when loaded.

                form.fieldConfigs = this.parseFieldConfig($form, $form); // Is there any additional JS config registered for this form?

                if (!registeredJs.length) {
                  _context.next = 22;
                  break;
                }

                if (!document.querySelector("[data-fui-scripts=\"".concat(formConfig.formHashId, "\"]"))) {
                  _context.next = 18;
                  break;
                }

                console.warn("Formie scripts already loaded for form #".concat(formConfig.formHashId, "."));
                return _context.abrupt("return");

              case 18:
                // Create a container to add these items to, so we can destroy them later
                form.$registeredJs = document.createElement('div');
                form.$registeredJs.setAttribute('data-fui-scripts', formConfig.formHashId);
                document.body.appendChild(form.$registeredJs); // Create a `<script>` for each registered JS

                registeredJs.forEach(function (config) {
                  var $script = document.createElement('script'); // Check if we've provided an external script to load. Ensure they're deferred so they don't block
                  // and use the onload call to trigger any actual scripts once its been loaded.

                  if (config.src) {
                    $script.src = config.src;
                    $script.defer = true; // Initialize all matching fields - their config is already rendered in templates

                    $script.onload = function () {
                      if (config.module) {
                        var fieldConfigs = form.fieldConfigs[config.module]; // Handle multiple fields on a page, creating a new JS class instance for each

                        if (fieldConfigs && Array.isArray(fieldConfigs) && fieldConfigs.length) {
                          fieldConfigs.forEach(function (fieldConfig) {
                            _this2.initJsClass(config.module, fieldConfig);
                          });
                        } // Handle integrations that have global settings, instead of per-field


                        if (config.settings) {
                          _this2.initJsClass(config.module, _objectSpread({
                            $form: $form
                          }, config.settings));
                        } // Special handling for some JS modules


                        if (config.module === 'FormieConditions') {
                          _this2.initJsClass(config.module, {
                            $form: $form
                          });
                        }
                      }
                    };
                  }

                  form.$registeredJs.appendChild($script);
                });

              case 22:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));

      function initForm(_x) {
        return _initForm.apply(this, arguments);
      }

      return initForm;
    }()
  }, {
    key: "initJsClass",
    value: function initJsClass(className, params) {
      var moduleClass = window[className];

      if (moduleClass) {
        new moduleClass(params);
      }
    } // Note the use of $form and $element to handle Repeater

  }, {
    key: "parseFieldConfig",
    value: function parseFieldConfig($element, $form) {
      var config = {};
      $element.querySelectorAll('[data-field-config]').forEach(function ($field) {
        var fieldConfig = JSON.parse($field.getAttribute('data-field-config')); // Some fields supply multiple modules, so normalise for ease-of-processing

        if (!Array.isArray(fieldConfig)) {
          fieldConfig = [fieldConfig];
        }

        fieldConfig.forEach(function (nestedFieldConfig) {
          if (!config[nestedFieldConfig.module]) {
            config[nestedFieldConfig.module] = [];
          } // Provide field classes with the data they need


          config[nestedFieldConfig.module].push(_objectSpread({
            $form: $form,
            $field: $field
          }, nestedFieldConfig));
        });
      });
      return config;
    }
  }, {
    key: "getForm",
    value: function getForm($form) {
      return this.forms.find(function (form) {
        return form.$form == $form;
      });
    }
  }, {
    key: "getFormById",
    value: function getFormById(id) {
      // eslint-disable-next-line array-callback-return
      return this.forms.find(function (form) {
        if (form.config) {
          return form.config.formId == id;
        }
      });
    }
  }, {
    key: "getFormByHashId",
    value: function getFormByHashId(hashId) {
      // eslint-disable-next-line array-callback-return
      return this.forms.find(function (form) {
        if (form.config) {
          return form.config.formHashId == hashId;
        }
      });
    }
  }, {
    key: "getFormByHandle",
    value: function getFormByHandle(handle) {
      // eslint-disable-next-line array-callback-return
      return this.forms.find(function (form) {
        if (form.config) {
          return form.config.formHandle == handle;
        }
      });
    }
  }, {
    key: "destroyForm",
    value: function () {
      var _destroyForm = _asyncToGenerator( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().mark(function _callee2(form) {
        var $form, index;
        return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_0___default().wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                // Allow passing in a DOM element, or a FormieBaseForm object
                if (form instanceof _formie_form_base__WEBPACK_IMPORTED_MODULE_2__.FormieFormBase) {
                  $form = form.$form;
                } else {
                  $form = form;
                  form = this.getForm($form);
                }

                if (!(!form || !$form)) {
                  _context2.next = 3;
                  break;
                }

                return _context2.abrupt("return");

              case 3:
                index = this.forms.indexOf(form);

                if (!(index === -1)) {
                  _context2.next = 6;
                  break;
                }

                return _context2.abrupt("return");

              case 6:
                // Mark the form as being destroyed, so no more events get added while we try and remove them
                form.destroyed = true; // Delete any additional scripts for the form - if any

                if (form.$registeredJs && form.$registeredJs.parentNode) {
                  form.$registeredJs.parentNode.removeChild(form.$registeredJs);
                } // Trigger an event (before events are removed)


                form.formDestroy({
                  form: form
                }); // Remove all event listeners attached to this form

                if (!(0,_utils_utils__WEBPACK_IMPORTED_MODULE_1__.isEmpty)(form.listeners)) {
                  Object.keys(form.listeners).forEach(function (eventKey) {
                    form.removeEventListener(eventKey);
                  });
                } // Destroy Bouncer events


                if (form.formTheme && form.formTheme.validator) {
                  form.formTheme.validator.destroy();
                } // Delete it from the factory


                this.forms.splice(index, 1);

              case 12:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, this);
      }));

      function destroyForm(_x2) {
        return _destroyForm.apply(this, arguments);
      }

      return destroyForm;
    }()
  }, {
    key: "refreshForCache",
    value: function refreshForCache(formHashId, callback) {
      var form = this.getFormByHashId(formHashId);

      if (!form) {
        console.error("Unable to find form \"".concat(formHashId, "\"."));
        return;
      }

      this.refreshFormTokens(form, callback);
    }
  }, {
    key: "refreshFormTokens",
    value: function refreshFormTokens(form, callback) {
      var _form$config = form.config,
          formHashId = _form$config.formHashId,
          formHandle = _form$config.formHandle;
      fetch("/actions/formie/forms/refresh-tokens?form=".concat(formHandle)).then(function (result) {
        return result.json();
      }).then(function (result) {
        // Fetch the form we want to deal with
        var $form = form.$form; // Update the CSRF input

        if (result.csrf.param) {
          var $csrfInput = $form.querySelector("input[name=\"".concat(result.csrf.param, "\"]"));

          if ($csrfInput) {
            $csrfInput.value = result.csrf.token;
            console.log("".concat(formHashId, ": Refreshed CSRF input %o."), result.csrf);
          } else {
            console.error("".concat(formHashId, ": Unable to locate CSRF input for \"").concat(result.csrf.param, "\"."));
          }
        } else {
          console.error("".concat(formHashId, ": Missing CSRF token information in cache-refresh response."));
        } // Update any captchas


        if (result.captchas) {
          Object.entries(result.captchas).forEach(function (_ref) {
            var _ref2 = _slicedToArray(_ref, 2),
                key = _ref2[0],
                value = _ref2[1];

            // In some cases, the captcha input might not have loaded yet, as some are dynamically created
            // (see Duplicate and JS captchas). So wait for the element to exist first
            (0,_utils_utils__WEBPACK_IMPORTED_MODULE_1__.waitForElement)("input[name=\"".concat(value.sessionKey, "\"]"), $form).then(function ($captchaInput) {
              if (value.value) {
                $captchaInput.value = value.value;
                console.log("".concat(formHashId, ": Refreshed \"").concat(key, "\" captcha input %o."), value);
              }
            }); // Add a timeout purely for logging, in case the element doesn't resolve in a reasonable time

            setTimeout(function () {
              if (!$form.querySelector("input[name=\"".concat(value.sessionKey, "\"]"))) {
                console.error("".concat(formHashId, ": Unable to locate captcha input for \"").concat(key, "\"."));
              }
            }, 10000);
          });
        } // Update the form's hash (if using Formie's themed JS)


        if (form.formTheme) {
          form.formTheme.updateFormHash();
        } // Fire a callback for users to do other bits


        if (callback) {
          callback(result);
        }
      });
    }
  }]);

  return Formie;
}();
window.Formie = Formie;

/***/ }),

/***/ "./src/js/formie.js":
/*!**************************!*\
  !*** ./src/js/formie.js ***!
  \**************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_polyfills__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/polyfills */ "./src/js/utils/polyfills.js");
/* harmony import */ var _formie_lib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./formie-lib */ "./src/js/formie-lib.js");

 // This should only be used when initializing Formie from the browser. When initializing with JS directly
// import `formie-lib.js` directly into your JS modules.

window.Formie = new _formie_lib__WEBPACK_IMPORTED_MODULE_1__.Formie(); // Whether we want to initialize the forms automatically.

var initForms = true;

if (document.currentScript && document.currentScript.hasAttribute('data-manual-init')) {
  initForms = false;
} // Don't init forms until the document is ready, or the document already loaded
// https://developer.mozilla.org/en-US/docs/Web/API/Document/DOMContentLoaded_event#checking_whether_loading_is_already_complete


if (initForms) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function (event) {
      window.Formie.initForms();
    });
  } else {
    window.Formie.initForms();
  }
}

/***/ }),

/***/ "./src/js/utils/bouncer.js":
/*!*********************************!*\
  !*** ./src/js/utils/bouncer.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "Bouncer": () => (/* binding */ Bouncer)
/* harmony export */ });
/* eslint-disable */

/*!
 * formbouncerjs v1.4.6
 * A lightweight form validation script that augments native HTML5 form validation elements and attributes.
 * (c) 2020 Chris Ferdinandi
 * MIT License
 * http://github.com/cferdinandi/bouncer
 */

/**
 * The plugin constructor
 * @param {DOMElement} formElement The DOM Element to use for forms to be validated
 * @param {Object} options  User settings [optional]
 */
var Bouncer = function Bouncer(formElement, options) {
  //
  // Variables
  //
  var defaults = {
    // Classes & IDs
    fieldClass: 'error',
    errorClass: 'error-message',
    fieldPrefix: 'bouncer-field_',
    errorPrefix: 'bouncer-error_',
    // Patterns
    patterns: {
      email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
      url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
      number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
      color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
      date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
      time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
      month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
    },
    // Custom Validations
    customValidations: {},
    // Messages
    messageAfterField: true,
    messageCustom: 'data-bouncer-message',
    messageTarget: 'data-bouncer-target',
    // messages: {
    //     missingValue: {
    //         checkbox: 'This field is required.',
    //         radio: 'Please select a value.',
    //         select: 'Please select a value.',
    //         'select-multiple': 'Please select at least one value.',
    //         default: 'Please fill out this field.',
    //     },
    //     patternMismatch: {
    //         email: 'Please enter a valid email address.',
    //         url: 'Please enter a URL.',
    //         number: 'Please enter a number',
    //         color: 'Please match the following format: #rrggbb',
    //         date: 'Please use the YYYY-MM-DD format',
    //         time: 'Please use the 24-hour time format. Ex. 23:00',
    //         month: 'Please use the YYYY-MM format',
    //         default: 'Please match the requested format.',
    //     },
    //     outOfRange: {
    //         over: 'Please select a value that is no more than {max}.',
    //         under: 'Please select a value that is no less than {min}.',
    //     },
    //     wrongLength: {
    //         over: 'Please shorten this text to no more than {maxLength} characters. You are currently using {length} characters.',
    //         under: 'Please lengthen this text to {minLength} characters or more. You are currently using {length} characters.',
    //     },
    //     fallback: 'There was an error with this field.',
    // },
    // Form Submission
    disableSubmit: false,
    // Allow blur/click/input events to be opt-out
    validateOnBlur: true,
    // Allow validation to be turned off altogether. Useful for server-side validation use.
    validateOnSubmit: true,
    // Custom Events
    emitEvents: true
  }; //
  // Methods
  //

  /**
   * A wrapper for Array.prototype.forEach() for non-arrays
   * @param  {Array-like} arr      The array-like object
   * @param  {Function}   callback The callback to run
   */

  var forEach = function forEach(arr, callback) {
    Array.prototype.forEach.call(arr, callback);
  };
  /**
   * Merge two or more objects together.
   * @param   {Object}   objects  The objects to merge together
   * @returns {Object}            Merged values of defaults and options
   */


  var extend = function extend() {
    var merged = {};
    forEach(arguments, function (obj) {
      for (var key in obj) {
        if (!obj.hasOwnProperty(key)) return;

        if (Object.prototype.toString.call(obj[key]) === '[object Object]') {
          merged[key] = extend(merged[key], obj[key]);
        } else {
          merged[key] = obj[key];
        } // merged[key] = obj[key];

      }
    });
    return merged;
  };
  /**
   * Emit a custom event
   * @param  {String} type    The event type
   * @param  {Object} options The settings object
   * @param  {Node}   anchor  The anchor element
   * @param  {Node}   toggle  The toggle element
   */


  var emitEvent = function emitEvent(elem, type, details) {
    if (typeof window.CustomEvent !== 'function') return;
    var event = new CustomEvent(type, {
      bubbles: true,
      detail: details || {}
    });
    elem.dispatchEvent(event);
  };
  /**
   * Add the `novalidate` attribute to all forms
   * @param {Boolean} remove  If true, remove the `novalidate` attribute
   */


  var addNoValidate = function addNoValidate(form) {
    form.setAttribute('novalidate', true);
  };
  /**
   * Remove the `novalidate` attribute to all forms
   */


  var removeNoValidate = function removeNoValidate(form) {
    form.removeAttribute('novalidate');
  };
  /**
   * Check if a required field is missing its value
   * @param  {Node} field The field to check
   * @return {Boolean}       It true, field is missing it's value
   */


  var missingValue = function missingValue(field) {
    // If not required, bail
    if (!field.hasAttribute('required')) return false; // Handle checkboxes

    if (field.type === 'checkbox') {
      // Watch out for grouped checkboxes. Only validate the group as a whole
      var checkboxInputs = field.form.querySelectorAll('[name="' + escapeCharacters(field.name) + '"]:not([type="hidden"])');

      if (checkboxInputs.length) {
        var checkedInputs = Array.prototype.filter.call(checkboxInputs, function (btn) {
          return btn.checked;
        }).length;
        return !checkedInputs;
      }

      return !field.checked;
    } // Don't validate any hidden fields


    if (field.type === 'hidden') {
      return false;
    } // Get the field value length


    var length = field.value.length; // Handle radio buttons

    if (field.type === 'radio') {
      length = Array.prototype.filter.call(field.form.querySelectorAll('[name="' + escapeCharacters(field.name) + '"]'), function (btn) {
        return btn.checked;
      }).length;
    } // Check for value


    return length < 1;
  };
  /**
   * Check if field value doesn't match a patter.
   * @param  {Node}   field    The field to check
   * @param  {Object} settings The plugin settings
   * @see https://www.w3.org/TR/html51/sec-forms.html#the-pattern-attribute
   * @return {Boolean}         If true, there's a pattern mismatch
   */


  var patternMismatch = function patternMismatch(field, settings) {
    // Check if there's a pattern to match
    var pattern = field.getAttribute('pattern');
    pattern = pattern ? new RegExp('^(?:' + pattern + ')$') : settings.patterns[field.type];
    if (!pattern || !field.value || field.value.length < 1) return false; // Validate the pattern

    return field.value.match(pattern) ? false : true;
  };
  /**
   * Check if field value is out-of-range
   * @param  {Node}    field    The field to check
   * @return {String}           Returns 'over', 'under', or false
   */


  var outOfRange = function outOfRange(field) {
    // Make sure field has value
    if (!field.value || field.value.length < 1) return false; // Check for range

    var max = field.getAttribute('max');
    var min = field.getAttribute('min'); // Check validity

    var num = parseFloat(field.value);
    if (max && num > max) return 'over';
    if (min && num < min) return 'under';
    return false;
  };
  /**
   * Check if the field value is too long or too short
   * @param  {Node}   field    The field to check
   * @return {String}           Returns 'over', 'under', or false
   */


  var wrongLength = function wrongLength(field) {
    // Make sure field has value
    if (!field.value || field.value.length < 1) return false; // Check for min/max length

    var max = field.getAttribute('maxlength');
    var min = field.getAttribute('minlength'); // Check validity

    var length = field.value.length;
    if (max && length > max) return 'over';
    if (min && length < min) return 'under';
    return false;
  };
  /**
   * Test for standard field validations
   * @param  {Node}   field    The field to test
   * @param  {Object} settings The plugin settings
   * @return {Object}          The tests and their results
   */


  var runValidations = function runValidations(field, settings) {
    return {
      missingValue: missingValue(field),
      patternMismatch: patternMismatch(field, settings),
      outOfRange: outOfRange(field),
      wrongLength: wrongLength(field)
    };
  };
  /**
   * Run any provided custom validations
   * @param  {Node}   field       The field to test
   * @param  {Object} errors      The existing errors
   * @param  {Object} validations The custom validations to run
   * @param  {Object} settings    The plugin settings
   * @return {Object}             The tests and their results
   */


  var customValidations = function customValidations(field, errors, validations, settings) {
    for (var test in validations) {
      if (validations.hasOwnProperty(test)) {
        errors[test] = validations[test](field, settings);
      }
    }

    return errors;
  };
  /**
   * Check if a field has any errors
   * @param  {Object}  errors The validation test results
   * @return {Boolean}        Returns true if there are errors
   */


  var hasErrors = function hasErrors(errors) {
    for (var type in errors) {
      if (errors[type]) return true;
    }

    return false;
  };
  /**
   * Check a field for errors
   * @param  {Node} field      The field to test
   * @param  {Object} settings The plugin settings
   * @return {Object}          The field validity and errors
   */


  var getErrors = function getErrors(field, settings) {
    // Get standard validation errors
    var errors = runValidations(field, settings); // Check for custom validations

    errors = customValidations(field, errors, settings.customValidations, settings);
    return {
      valid: !hasErrors(errors),
      errors: errors
    };
  };
  /**
   * Escape special characters for use with querySelector
   * @author Mathias Bynens
   * @link https://github.com/mathiasbynens/CSS.escape
   * @param {String} id The anchor ID to escape
   */


  var escapeCharacters = function escapeCharacters(id) {
    var string = String(id);
    var length = string.length;
    var index = -1;
    var codeUnit;
    var result = '';
    var firstCodeUnit = string.charCodeAt(0);

    while (++index < length) {
      codeUnit = string.charCodeAt(index); // Note: there’s no need to special-case astral symbols, surrogate
      // pairs, or lone surrogates.
      // If the character is NULL (U+0000), then throw an
      // `InvalidCharacterError` exception and terminate these steps.

      if (codeUnit === 0x0000) {
        throw new InvalidCharacterError('Invalid character: the input contains U+0000.');
      }

      if ( // If the character is in the range [\1-\1F] (U+0001 to U+001F) or is
      // U+007F, […]
      codeUnit >= 0x0001 && codeUnit <= 0x001F || codeUnit == 0x007F || // If the character is the first character and is in the range [0-9]
      // (U+0030 to U+0039), […]
      index === 0 && codeUnit >= 0x0030 && codeUnit <= 0x0039 || // If the character is the second character and is in the range [0-9]
      // (U+0030 to U+0039) and the first character is a `-` (U+002D), […]
      index === 1 && codeUnit >= 0x0030 && codeUnit <= 0x0039 && firstCodeUnit === 0x002D) {
        // http://dev.w3.org/csswg/cssom/#escape-a-character-as-code-point
        result += '\\' + codeUnit.toString(16) + ' ';
        continue;
      } // If the character is not handled by one of the above rules and is
      // greater than or equal to U+0080, is `-` (U+002D) or `_` (U+005F), or
      // is in one of the ranges [0-9] (U+0030 to U+0039), [A-Z] (U+0041 to
      // U+005A), or [a-z] (U+0061 to U+007A), […]


      if (codeUnit >= 0x0080 || codeUnit === 0x002D || codeUnit === 0x005F || codeUnit >= 0x0030 && codeUnit <= 0x0039 || codeUnit >= 0x0041 && codeUnit <= 0x005A || codeUnit >= 0x0061 && codeUnit <= 0x007A) {
        // the character itself
        result += string.charAt(index);
        continue;
      } // Otherwise, the escaped character.
      // http://dev.w3.org/csswg/cssom/#escape-a-character


      result += '\\' + string.charAt(index);
    } // Return sanitized hash


    return result;
  };
  /**
   * Get or create an ID for a field
   * @param  {Node}    field    The field
   * @param  {Object}  settings The plugin settings
   * @param  {Boolean} create   If true, create an ID if there isn't one
   * @return {String}           The field ID
   */


  var getFieldID = function getFieldID(field, settings, create) {
    var id = field.name ? field.name : field.id;

    if (!id && create) {
      id = settings.fieldPrefix + Math.floor(Math.random() * 999);
      field.id = id;
    } // if (field.type === 'checkbox') {
    //     id += '_' + (field.value || field.id);
    // }


    return id;
  };
  /**
   * Special handling for radio buttons and checkboxes wrapped in labels.
   * @param  {Node} field The field with the error
   * @return {Node}       The field to show the error on
   */


  var getErrorField = function getErrorField(field) {
    // If the field is a radio button, get the last item in the radio group
    // @todo if location is before, get first item
    if (field.type === 'radio' && field.name) {
      var group = field.form.querySelectorAll('[name="' + escapeCharacters(field.name) + '"]');
      field = group[group.length - 1];
    } // Get the associated label for radio button or checkbox
    // if (field.type === 'radio') {
    //     var label = field.closest('label') || field.form.querySelector('[for="' + field.id + '"]');
    //     field = label || field;
    // }


    if (field.type === 'checkbox' || field.type === 'radio') {
      field = field.closest('[data-field-handle]').firstChild;
    }

    return field;
  };
  /**
   * Get the location for a field's error message
   * @param  {Node}   field    The field
   * @param  {Node}   target   The target for error message
   * @param  {Object} settings The plugin settings
   * @return {Node}            The error location
   */


  var getErrorLocation = function getErrorLocation(field, target, settings) {
    // Check for a custom error message
    var selector = field.getAttribute(settings.messageTarget);

    if (selector) {
      var location = field.form.querySelector(selector);

      if (location) {
        // @bugfix by @HaroldPutman
        // https://github.com/cferdinandi/bouncer/pull/28
        return location.firstChild || location.appendChild(document.createTextNode(''));
      }
    } // If the message should come after the field


    if (settings.messageAfterField) {
      if (!target) {
        target = field;
      } // If there's no next sibling, create one


      if (!target.nextSibling) {
        target.parentNode.appendChild(document.createTextNode(''));
      }

      return target.nextSibling;
    } // If it should come before


    return target;
  };
  /**
   * Create a validation error message node
   * @param  {Node} field      The field
   * @param  {Object} settings The plugin settings
   * @return {Node}            The error message node
   */


  var createError = function createError(field, settings) {
    // Create the error message
    var error = document.createElement('div');
    error.className = settings.errorClass;
    error.setAttribute('data-error-message', '');
    error.id = settings.errorPrefix + getFieldID(field, settings, true); // Set for accessibility

    error.setAttribute('aria-live', 'polite');
    error.setAttribute('aria-atomic', true); // If the field is a radio button or checkbox, grab the last field label

    var fieldTarget = getErrorField(field); // Inject the error message into the DOM

    var location = getErrorLocation(field, fieldTarget, settings);
    location.parentNode.insertBefore(error, location);
    return error;
  };
  /**
   * Get the error message test
   * @param  {Node}            field    The field to get an error message for
   * @param  {Object}          errors   The errors on the field
   * @param  {Object}          settings The plugin settings
   * @return {String|Function}          The error message
   */


  var getErrorMessage = function getErrorMessage(field, errors, settings) {
    // Variables
    var messages = settings.messages; // Missing value error

    if (errors.missingValue) {
      return messages.missingValue[field.type] || messages.missingValue["default"];
    } // Numbers that are out of range


    if (errors.outOfRange) {
      return messages.outOfRange[errors.outOfRange].replace('{max}', field.getAttribute('max')).replace('{min}', field.getAttribute('min')).replace('{length}', field.value.length);
    } // Values that are too long or short


    if (errors.wrongLength) {
      return messages.wrongLength[errors.wrongLength].replace('{maxLength}', field.getAttribute('maxlength')).replace('{minLength}', field.getAttribute('minlength')).replace('{length}', field.value.length);
    } // Pattern mismatch error


    if (errors.patternMismatch) {
      var custom = field.getAttribute(settings.messageCustom);
      if (custom) return custom;
      return messages.patternMismatch[field.type] || messages.patternMismatch["default"];
    } // Custom validations


    for (var test in settings.customValidations) {
      if (settings.customValidations.hasOwnProperty(test)) {
        if (errors[test] && messages[test]) return messages[test];
      }
    } // Custom message, passed directly in


    if (errors.customMessage) {
      return errors.customMessage;
    } // Fallback error message


    return messages.fallback;
  };
  /**
   * Add error attributes to a field
   * @param  {Node}   field    The field with the error message
   * @param  {Node}   error    The error message
   * @param  {Object} settings The plugin settings
   */


  var addErrorAttributes = function addErrorAttributes(field, error, settings) {
    field.classList.add(settings.fieldClass);
    field.setAttribute('aria-describedby', error.id);
    field.setAttribute('aria-invalid', true);
    var $fieldNode = field.closest('[data-field-handle]');

    if ($fieldNode) {
      $fieldNode.classList.add(settings.fieldClass);
    }
  };
  /**
   * Show error attributes on a field or radio/checkbox group
   * @param  {Node}   field    The field with the error message
   * @param  {Node}   error    The error message
   * @param  {Object} settings The plugin settings
   */


  var showErrorAttributes = function showErrorAttributes(field, error, settings) {
    // If field is a radio button, add attributes to every button in the group
    if (field.type === 'radio' && field.name) {
      Array.prototype.forEach.call(document.querySelectorAll('[name="' + field.name + '"]'), function (button) {
        addErrorAttributes(button, error, settings);
      });
    } // Otherwise, add an error class and aria attribute to the field


    addErrorAttributes(field, error, settings);
  };
  /**
   * Show an error message in the DOM
   * @param  {Node} field      The field to show an error message for
   * @param  {Object}          errors   The errors on the field
   * @param  {Object}          settings The plugin settings
   */


  var showError = function showError(field, errors, settings) {
    // Get/create an error message
    var error = field.form.querySelector('#' + escapeCharacters(settings.errorPrefix + getFieldID(field, settings))) || createError(field, settings);
    var msg = getErrorMessage(field, errors, settings);
    error.textContent = typeof msg === 'function' ? msg(field, settings) : msg; // Add error attributes

    showErrorAttributes(field, error, settings); // Emit custom event

    if (settings.emitEvents) {
      emitEvent(field, 'bouncerShowError', {
        errors: errors
      });
    }
  };
  /**
   * Remove error attributes from a field
   * @param  {Node}   field    The field with the error message
   * @param  {Node}   error    The error message
   * @param  {Object} settings The plugin settings
   */


  var removeAttributes = function removeAttributes(field, settings) {
    field.classList.remove(settings.fieldClass);
    field.removeAttribute('aria-describedby');
    field.removeAttribute('aria-invalid');
    var $fieldNode = field.closest('[data-field-handle]');

    if ($fieldNode) {
      $fieldNode.classList.remove(settings.fieldClass);
    }
  };
  /**
   * Remove error attributes from the field or radio group
   * @param  {Node}   field    The field with the error message
   * @param  {Node}   error    The error message
   * @param  {Object} settings The plugin settings
   */


  var removeErrorAttributes = function removeErrorAttributes(field, settings) {
    // If field is a radio button, remove attributes from every button in the group
    if (field.type === 'radio' && field.name) {
      Array.prototype.forEach.call(document.querySelectorAll('[name="' + field.name + '"]'), function (button) {
        removeAttributes(button, settings);
      });
      return;
    } // Otherwise, add an error class and aria attribute to the field


    removeAttributes(field, settings);
  };
  /**
   * Remove an error message from the DOM
   * @param  {Node} field      The field with the error message
   * @param  {Object} settings The plugin settings
   */


  var removeError = function removeError(field, settings) {
    // Get the error message for this field
    var error = field.form.querySelector('#' + escapeCharacters(settings.errorPrefix + getFieldID(field, settings)));
    if (!error) return; // Remove the error

    error.parentNode.removeChild(error); // Remove error and a11y from the field

    removeErrorAttributes(field, settings); // Emit custom event

    if (settings.emitEvents) {
      emitEvent(field, 'bouncerRemoveError');
    }
  };
  /**
   * Remove errors from all fields
   * @param  {String} selector The selector for the form
   * @param  {Object} settings The plugin settings
   */


  var removeAllErrors = function removeAllErrors(form, settings) {
    forEach(form.querySelectorAll('input, select, textarea'), function (field) {
      removeError(field, settings);
    });
  }; //
  // Variables
  //


  var publicAPIs = {};
  var settings; //
  // Methods
  //

  /**
   * Show an error message in the DOM
   * @param  {Node} field      The field to show an error message for
   * @param  {Object}          errors   The errors on the field
   * @param  {Object}          options Additional plugin settings
   */

  publicAPIs.showError = function (field, errors, options) {
    var _settings = extend(settings, options || {});

    return showError(field, errors, _settings);
  };
  /**
   * Remove an error message from the DOM
   * @param  {Node} field      The field with the error message
   * @param  {Object} settings The plugin settings
   */


  publicAPIs.removeError = function (field, options) {
    var _settings = extend(settings, options || {});

    return removeError(field, _settings);
  };
  /**
   * Validate a field
   * @param  {Node} field     The field to validate
   * @param  {Object} options Validation options
   * @return {Object}         The validity state and errors
   */


  publicAPIs.validate = function (field, options) {
    // Don't validate submits, buttons, file and reset inputs, and disabled and readonly fields
    if (field.disabled || field.readOnly || field.type === 'reset' || field.type === 'submit' || field.type === 'button') return; // Local settings

    var _settings = extend(settings, options || {}); // Check for errors


    var isValid = getErrors(field, _settings); // If valid, remove any error messages

    if (isValid.valid) {
      removeError(field, _settings);
      return;
    } // Otherwise, show an error message


    showError(field, isValid.errors, _settings);
    return isValid;
  };
  /**
   * Validate all fields in a form or section
   * @param  {Node} target The form or section to validate fields in
   * @return {Array}       An array of fields with errors
   */


  publicAPIs.validateAll = function (target) {
    return Array.prototype.filter.call(target.querySelectorAll('input, select, textarea'), function (field) {
      var validate = publicAPIs.validate(field);
      return validate && !validate.valid;
    });
  };
  /**
   * Run a validation on field blur
   */


  var blurHandler = function blurHandler(event) {
    // Only run if the field is in a form to be validated
    if (!event.target.form || !event.target.form.isSameNode(formElement)) return; // Special-case for file field, blurs as soon as the selector kicks in

    if (event.target.type === 'file') return; // Don't trigger click event handling for checkbox/radio. We should use the change.

    if (event.target.type === 'checkbox' || event.target.type === 'radio') return; // Validate the field

    publicAPIs.validate(event.target);
  }; // Leave this as opt-in for the moment, for better file-support


  var changeHandler = function changeHandler(event) {
    // Only run if the field is in a form to be validated
    if (!event.target.form || !event.target.form.isSameNode(formElement)) return; // Only handle change events for some fields

    if (event.target.type !== 'file' && event.target.type !== 'checkbox' && event.target.type !== 'radio') return; // Validate the field

    publicAPIs.validate(event.target);
  };
  /**
   * Run a validation on a fields with errors when the value changes
   */


  var inputHandler = function inputHandler(event) {
    // Only run if the field is in a form to be validated
    if (!event.target.form || !event.target.form.isSameNode(formElement)) return; // Only run on fields with errors

    if (!event.target.classList.contains(settings.fieldClass)) return; // Don't trigger click event handling for checkbox/radio. We should use the change.

    if (event.target.type === 'checkbox' || event.target.type === 'radio') return; // Validate the field

    publicAPIs.validate(event.target);
  };
  /**
   * Run a validation on a fields with errors when the value changes
   */


  var clickHandler = function clickHandler(event) {
    // Only run if the field is in a form to be validated
    if (!event.target.form || !event.target.form.isSameNode(formElement)) return; // Only run on fields with errors

    if (!event.target.classList.contains(settings.fieldClass)) return; // Don't trigger click event handling for checkbox/radio. We should use the change.

    if (event.target.type === 'checkbox' || event.target.type === 'radio') return; // Validate the field

    publicAPIs.validate(event.target);
  };
  /**
   * Validate an entire form when it's submitted
   */


  var submitHandler = function submitHandler(event) {
    // Only run on matching elements
    if (!event.target.isSameNode(formElement)) return; // Prevent form submission

    event.preventDefault(); // Validate each field

    var errors = publicAPIs.validateAll(event.target); // If there are errors, focus on the first one

    if (errors.length > 0) {
      errors[0].focus();
      emitEvent(event.target, 'bouncerFormInvalid', {
        errors: errors
      });
      return;
    } // Otherwise, submit if not disabled


    if (!settings.disableSubmit) {
      event.target.submit();
    } // Emit custom event


    if (settings.emitEvents) {
      emitEvent(event.target, 'bouncerFormValid');
    }
  };
  /**
   * Destroy the current plugin instantiation
   */


  publicAPIs.destroy = function () {
    // Remove event listeners
    if (settings.validateOnBlur) {
      document.removeEventListener('blur', blurHandler, true);
      document.removeEventListener('input', inputHandler, false);
      document.removeEventListener('change', changeHandler, false);
      document.removeEventListener('click', clickHandler, false);
    }

    if (settings.validateOnSubmit) {
      document.removeEventListener('submit', submitHandler, false);
    } // Remove all errors


    removeAllErrors(formElement, settings); // Remove novalidate attribute

    removeNoValidate(formElement); // Emit custom event

    if (settings.emitEvents) {
      emitEvent(document, 'bouncerDestroyed', {
        settings: settings
      });
    } // Reset settings


    settings = null;
  };
  /**
   * Instantiate a new instance of the plugin
   */


  var init = function init() {
    // Create settings
    settings = extend(defaults, options || {}); // Add novalidate attribute

    addNoValidate(formElement); // Event Listeners

    if (settings.validateOnBlur) {
      document.addEventListener('blur', blurHandler, true);
      document.addEventListener('input', inputHandler, false);
      document.addEventListener('change', changeHandler, false);
      document.addEventListener('click', clickHandler, false);
    }

    if (settings.validateOnSubmit) {
      document.addEventListener('submit', submitHandler, false);
    } // Emit custom event


    if (settings.emitEvents) {
      emitEvent(document, 'bouncerInitialized', {
        settings: settings
      });
    }
  }; //
  // Inits & Event Listeners
  //


  init();
  return publicAPIs;
};

/***/ }),

/***/ "./src/js/utils/polyfills.js":
/*!***********************************!*\
  !*** ./src/js/utils/polyfills.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var formdata_polyfill__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! formdata-polyfill */ "../../../../node_modules/formdata-polyfill/formdata.min.js");
//
// Polyfills for IE11
//
// CustomEvent()
(function () {
  if (typeof window.CustomEvent === 'function') {
    return false;
  }

  function CustomEvent(event, params) {
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: null
    };
    var evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  }

  window.CustomEvent = CustomEvent;
})(); // FormData


 // closest

if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
  Element.prototype.closest = function (s) {
    var el = this;

    do {
      if (el.matches(s)) {
        return el;
      }

      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);

    return null;
  };
}

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ }),

/***/ "./src/scss/formie-base.scss":
/*!***********************************!*\
  !*** ./src/scss/formie-base.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/formie-theme.scss":
/*!************************************!*\
  !*** ./src/scss/formie-theme.scss ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/fields/phone-country.scss":
/*!********************************************!*\
  !*** ./src/scss/fields/phone-country.scss ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/fields/tags.scss":
/*!***********************************!*\
  !*** ./src/scss/fields/tags.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "../../../../node_modules/regenerator-runtime/runtime.js":
/*!***************************************************************!*\
  !*** ../../../../node_modules/regenerator-runtime/runtime.js ***!
  \***************************************************************/
/***/ ((module) => {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

var runtime = (function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; };
  var undefined; // More compressible than void 0.
  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

  function define(obj, key, value) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
    return obj[key];
  }
  try {
    // IE 8 has a broken Object.defineProperty that only works on DOM objects.
    define({}, "");
  } catch (err) {
    define = function(obj, key, value) {
      return obj[key] = value;
    };
  }

  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []);

    // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.
    defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) });

    return generator;
  }
  exports.wrap = wrap;

  // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.
  function tryCatch(fn, obj, arg) {
    try {
      return { type: "normal", arg: fn.call(obj, arg) };
    } catch (err) {
      return { type: "throw", arg: err };
    }
  }

  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed";

  // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.
  var ContinueSentinel = {};

  // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}

  // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.
  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });

  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  if (NativeIteratorPrototype &&
      NativeIteratorPrototype !== Op &&
      hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }

  var Gp = GeneratorFunctionPrototype.prototype =
    Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = GeneratorFunctionPrototype;
  defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: true });
  defineProperty(
    GeneratorFunctionPrototype,
    "constructor",
    { value: GeneratorFunction, configurable: true }
  );
  GeneratorFunction.displayName = define(
    GeneratorFunctionPrototype,
    toStringTagSymbol,
    "GeneratorFunction"
  );

  // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function(method) {
      define(prototype, method, function(arg) {
        return this._invoke(method, arg);
      });
    });
  }

  exports.isGeneratorFunction = function(genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor
      ? ctor === GeneratorFunction ||
        // For the native GeneratorFunction constructor, the best we can
        // do is to check its .name property.
        (ctor.displayName || ctor.name) === "GeneratorFunction"
      : false;
  };

  exports.mark = function(genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      define(genFun, toStringTagSymbol, "GeneratorFunction");
    }
    genFun.prototype = Object.create(Gp);
    return genFun;
  };

  // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.
  exports.awrap = function(arg) {
    return { __await: arg };
  };

  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;
        if (value &&
            typeof value === "object" &&
            hasOwn.call(value, "__await")) {
          return PromiseImpl.resolve(value.__await).then(function(value) {
            invoke("next", value, resolve, reject);
          }, function(err) {
            invoke("throw", err, resolve, reject);
          });
        }

        return PromiseImpl.resolve(value).then(function(unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function(error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }

    var previousPromise;

    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new PromiseImpl(function(resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }

      return previousPromise =
        // If enqueue has been called before, then we want to wait until
        // all previous Promises have been resolved before calling invoke,
        // so that results are always delivered in the correct order. If
        // enqueue has not been called before, then it is important to
        // call invoke immediately, without waiting on a callback to fire,
        // so that the async generator function has the opportunity to do
        // any necessary setup in a predictable way. This predictability
        // is why the Promise constructor synchronously invokes its
        // executor callback, and why async functions synchronously
        // execute code before the first await. Since we implement simple
        // async functions in terms of async generators, it is especially
        // important to get this right, even though it requires care.
        previousPromise ? previousPromise.then(
          callInvokeWithMethodAndArg,
          // Avoid propagating failures to Promises returned by later
          // invocations of the iterator.
          callInvokeWithMethodAndArg
        ) : callInvokeWithMethodAndArg();
    }

    // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).
    defineProperty(this, "_invoke", { value: enqueue });
  }

  defineIteratorMethods(AsyncIterator.prototype);
  define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  });
  exports.AsyncIterator = AsyncIterator;

  // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.
  exports.async = function(innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    if (PromiseImpl === void 0) PromiseImpl = Promise;

    var iter = new AsyncIterator(
      wrap(innerFn, outerFn, self, tryLocsList),
      PromiseImpl
    );

    return exports.isGeneratorFunction(outerFn)
      ? iter // If outerFn is a generator, return the full iterator.
      : iter.next().then(function(result) {
          return result.done ? result.value : iter.next();
        });
  };

  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;

    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }

      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        }

        // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
        return doneResult();
      }

      context.method = method;
      context.arg = arg;

      while (true) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }

        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;

        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }

          context.dispatchException(context.arg);

        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }

        state = GenStateExecuting;

        var record = tryCatch(innerFn, self, context);
        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done
            ? GenStateCompleted
            : GenStateSuspendedYield;

          if (record.arg === ContinueSentinel) {
            continue;
          }

          return {
            value: record.arg,
            done: context.done
          };

        } else if (record.type === "throw") {
          state = GenStateCompleted;
          // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.
          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  }

  // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.
  function maybeInvokeDelegate(delegate, context) {
    var methodName = context.method;
    var method = delegate.iterator[methodName];
    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method, or a missing .next mehtod, always terminate the
      // yield* loop.
      context.delegate = null;

      // Note: ["return"] must be used for ES3 parsing compatibility.
      if (methodName === "throw" && delegate.iterator["return"]) {
        // If the delegate iterator has a return method, give it a
        // chance to clean up.
        context.method = "return";
        context.arg = undefined;
        maybeInvokeDelegate(delegate, context);

        if (context.method === "throw") {
          // If maybeInvokeDelegate(context) changed context.method from
          // "return" to "throw", let that override the TypeError below.
          return ContinueSentinel;
        }
      }
      if (methodName !== "return") {
        context.method = "throw";
        context.arg = new TypeError(
          "The iterator does not provide a '" + methodName + "' method");
      }

      return ContinueSentinel;
    }

    var record = tryCatch(method, delegate.iterator, context.arg);

    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }

    var info = record.arg;

    if (! info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }

    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value;

      // Resume execution at the desired location (see delegateYield).
      context.next = delegate.nextLoc;

      // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.
      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }

    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    }

    // The delegate iterator is finished, so forget it and continue with
    // the outer generator.
    context.delegate = null;
    return ContinueSentinel;
  }

  // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.
  defineIteratorMethods(Gp);

  define(Gp, toStringTagSymbol, "Generator");

  // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.
  define(Gp, iteratorSymbol, function() {
    return this;
  });

  define(Gp, "toString", function() {
    return "[object Generator]";
  });

  function pushTryEntry(locs) {
    var entry = { tryLoc: locs[0] };

    if (1 in locs) {
      entry.catchLoc = locs[1];
    }

    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }

    this.tryEntries.push(entry);
  }

  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }

  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{ tryLoc: "root" }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }

  exports.keys = function(val) {
    var object = Object(val);
    var keys = [];
    for (var key in object) {
      keys.push(key);
    }
    keys.reverse();

    // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.
    return function next() {
      while (keys.length) {
        var key = keys.pop();
        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      }

      // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.
      next.done = true;
      return next;
    };
  };

  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }

      if (typeof iterable.next === "function") {
        return iterable;
      }

      if (!isNaN(iterable.length)) {
        var i = -1, next = function next() {
          while (++i < iterable.length) {
            if (hasOwn.call(iterable, i)) {
              next.value = iterable[i];
              next.done = false;
              return next;
            }
          }

          next.value = undefined;
          next.done = true;

          return next;
        };

        return next.next = next;
      }
    }

    // Return an iterator with no values.
    return { next: doneResult };
  }
  exports.values = values;

  function doneResult() {
    return { value: undefined, done: true };
  }

  Context.prototype = {
    constructor: Context,

    reset: function(skipTempReset) {
      this.prev = 0;
      this.next = 0;
      // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.
      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;

      this.method = "next";
      this.arg = undefined;

      this.tryEntries.forEach(resetTryEntry);

      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" &&
              hasOwn.call(this, name) &&
              !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },

    stop: function() {
      this.done = true;

      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;
      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }

      return this.rval;
    },

    dispatchException: function(exception) {
      if (this.done) {
        throw exception;
      }

      var context = this;
      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;

        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }

        return !! caught;
      }

      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;

        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }

        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");

          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }

          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }

          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },

    abrupt: function(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc <= this.prev &&
            hasOwn.call(entry, "finallyLoc") &&
            this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }

      if (finallyEntry &&
          (type === "break" ||
           type === "continue") &&
          finallyEntry.tryLoc <= arg &&
          arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }

      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;

      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }

      return this.complete(record);
    },

    complete: function(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }

      if (record.type === "break" ||
          record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }

      return ContinueSentinel;
    },

    finish: function(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },

    "catch": function(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }

      // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.
      throw new Error("illegal catch attempt");
    },

    delegateYield: function(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };

      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }

      return ContinueSentinel;
    }
  };

  // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.
  return exports;

}(
  // If this script is executing as a CommonJS module, use module.exports
  // as the regeneratorRuntime namespace. Otherwise create a new empty
  // object. Either way, the resulting object will be used to initialize
  // the regeneratorRuntime variable at the top of this file.
   true ? module.exports : 0
));

try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, in modern engines
  // we can explicitly access globalThis. In older engines we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}


/***/ }),

/***/ "../../../../node_modules/formdata-polyfill/formdata.min.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/formdata-polyfill/formdata.min.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/*! formdata-polyfill. MIT License. Jimmy W?rting <https://jimmy.warting.se/opensource> */
;(function(){var h;function l(a){var b=0;return function(){return b<a.length?{done:!1,value:a[b++]}:{done:!0}}}var m="function"==typeof Object.defineProperties?Object.defineProperty:function(a,b,c){if(a==Array.prototype||a==Object.prototype)return a;a[b]=c.value;return a};
function n(a){a=["object"==typeof globalThis&&globalThis,a,"object"==typeof window&&window,"object"==typeof self&&self,"object"==typeof global&&global];for(var b=0;b<a.length;++b){var c=a[b];if(c&&c.Math==Math)return c}throw Error("Cannot find global object");}var q=n(this);function r(a,b){if(b)a:{var c=q;a=a.split(".");for(var d=0;d<a.length-1;d++){var e=a[d];if(!(e in c))break a;c=c[e]}a=a[a.length-1];d=c[a];b=b(d);b!=d&&null!=b&&m(c,a,{configurable:!0,writable:!0,value:b})}}
r("Symbol",function(a){function b(f){if(this instanceof b)throw new TypeError("Symbol is not a constructor");return new c(d+(f||"")+"_"+e++,f)}function c(f,g){this.A=f;m(this,"description",{configurable:!0,writable:!0,value:g})}if(a)return a;c.prototype.toString=function(){return this.A};var d="jscomp_symbol_"+(1E9*Math.random()>>>0)+"_",e=0;return b});
r("Symbol.iterator",function(a){if(a)return a;a=Symbol("Symbol.iterator");for(var b="Array Int8Array Uint8Array Uint8ClampedArray Int16Array Uint16Array Int32Array Uint32Array Float32Array Float64Array".split(" "),c=0;c<b.length;c++){var d=q[b[c]];"function"===typeof d&&"function"!=typeof d.prototype[a]&&m(d.prototype,a,{configurable:!0,writable:!0,value:function(){return u(l(this))}})}return a});function u(a){a={next:a};a[Symbol.iterator]=function(){return this};return a}
function v(a){var b="undefined"!=typeof Symbol&&Symbol.iterator&&a[Symbol.iterator];return b?b.call(a):{next:l(a)}}var w;if("function"==typeof Object.setPrototypeOf)w=Object.setPrototypeOf;else{var y;a:{var z={a:!0},A={};try{A.__proto__=z;y=A.a;break a}catch(a){}y=!1}w=y?function(a,b){a.__proto__=b;if(a.__proto__!==b)throw new TypeError(a+" is not extensible");return a}:null}var B=w;function C(){this.m=!1;this.j=null;this.v=void 0;this.h=1;this.u=this.C=0;this.l=null}
function D(a){if(a.m)throw new TypeError("Generator is already running");a.m=!0}C.prototype.o=function(a){this.v=a};C.prototype.s=function(a){this.l={D:a,F:!0};this.h=this.C||this.u};C.prototype.return=function(a){this.l={return:a};this.h=this.u};function E(a,b){a.h=3;return{value:b}}function F(a){this.g=new C;this.G=a}F.prototype.o=function(a){D(this.g);if(this.g.j)return G(this,this.g.j.next,a,this.g.o);this.g.o(a);return H(this)};
function I(a,b){D(a.g);var c=a.g.j;if(c)return G(a,"return"in c?c["return"]:function(d){return{value:d,done:!0}},b,a.g.return);a.g.return(b);return H(a)}F.prototype.s=function(a){D(this.g);if(this.g.j)return G(this,this.g.j["throw"],a,this.g.o);this.g.s(a);return H(this)};
function G(a,b,c,d){try{var e=b.call(a.g.j,c);if(!(e instanceof Object))throw new TypeError("Iterator result "+e+" is not an object");if(!e.done)return a.g.m=!1,e;var f=e.value}catch(g){return a.g.j=null,a.g.s(g),H(a)}a.g.j=null;d.call(a.g,f);return H(a)}function H(a){for(;a.g.h;)try{var b=a.G(a.g);if(b)return a.g.m=!1,{value:b.value,done:!1}}catch(c){a.g.v=void 0,a.g.s(c)}a.g.m=!1;if(a.g.l){b=a.g.l;a.g.l=null;if(b.F)throw b.D;return{value:b.return,done:!0}}return{value:void 0,done:!0}}
function J(a){this.next=function(b){return a.o(b)};this.throw=function(b){return a.s(b)};this.return=function(b){return I(a,b)};this[Symbol.iterator]=function(){return this}}function K(a,b){b=new J(new F(b));B&&a.prototype&&B(b,a.prototype);return b}function L(a,b){a instanceof String&&(a+="");var c=0,d=!1,e={next:function(){if(!d&&c<a.length){var f=c++;return{value:b(f,a[f]),done:!1}}d=!0;return{done:!0,value:void 0}}};e[Symbol.iterator]=function(){return e};return e}
r("Array.prototype.entries",function(a){return a?a:function(){return L(this,function(b,c){return[b,c]})}});
if("undefined"!==typeof Blob&&("undefined"===typeof FormData||!FormData.prototype.keys)){var M=function(a,b){for(var c=0;c<a.length;c++)b(a[c])},N=function(a){return a.replace(/\r?\n|\r/g,"\r\n")},O=function(a,b,c){if(b instanceof Blob){c=void 0!==c?String(c+""):"string"===typeof b.name?b.name:"blob";if(b.name!==c||"[object Blob]"===Object.prototype.toString.call(b))b=new File([b],c);return[String(a),b]}return[String(a),String(b)]},P=function(a,b){if(a.length<b)throw new TypeError(b+" argument required, but only "+
a.length+" present.");},Q="object"===typeof globalThis?globalThis:"object"===typeof window?window:"object"===typeof self?self:this,R=Q.FormData,S=Q.XMLHttpRequest&&Q.XMLHttpRequest.prototype.send,T=Q.Request&&Q.fetch,U=Q.navigator&&Q.navigator.sendBeacon,V=Q.Element&&Q.Element.prototype,W=Q.Symbol&&Symbol.toStringTag;W&&(Blob.prototype[W]||(Blob.prototype[W]="Blob"),"File"in Q&&!File.prototype[W]&&(File.prototype[W]="File"));try{new File([],"")}catch(a){Q.File=function(b,c,d){b=new Blob(b,d||{});
Object.defineProperties(b,{name:{value:c},lastModified:{value:+(d&&void 0!==d.lastModified?new Date(d.lastModified):new Date)},toString:{value:function(){return"[object File]"}}});W&&Object.defineProperty(b,W,{value:"File"});return b}}var escape=function(a){return a.replace(/\n/g,"%0A").replace(/\r/g,"%0D").replace(/"/g,"%22")},X=function(a){this.i=[];var b=this;a&&M(a.elements,function(c){if(c.name&&!c.disabled&&"submit"!==c.type&&"button"!==c.type&&!c.matches("form fieldset[disabled] *"))if("file"===
c.type){var d=c.files&&c.files.length?c.files:[new File([],"",{type:"application/octet-stream"})];M(d,function(e){b.append(c.name,e)})}else"select-multiple"===c.type||"select-one"===c.type?M(c.options,function(e){!e.disabled&&e.selected&&b.append(c.name,e.value)}):"checkbox"===c.type||"radio"===c.type?c.checked&&b.append(c.name,c.value):(d="textarea"===c.type?N(c.value):c.value,b.append(c.name,d))})};h=X.prototype;h.append=function(a,b,c){P(arguments,2);this.i.push(O(a,b,c))};h.delete=function(a){P(arguments,
1);var b=[];a=String(a);M(this.i,function(c){c[0]!==a&&b.push(c)});this.i=b};h.entries=function b(){var c,d=this;return K(b,function(e){1==e.h&&(c=0);if(3!=e.h)return c<d.i.length?e=E(e,d.i[c]):(e.h=0,e=void 0),e;c++;e.h=2})};h.forEach=function(b,c){P(arguments,1);for(var d=v(this),e=d.next();!e.done;e=d.next()){var f=v(e.value);e=f.next().value;f=f.next().value;b.call(c,f,e,this)}};h.get=function(b){P(arguments,1);var c=this.i;b=String(b);for(var d=0;d<c.length;d++)if(c[d][0]===b)return c[d][1];
return null};h.getAll=function(b){P(arguments,1);var c=[];b=String(b);M(this.i,function(d){d[0]===b&&c.push(d[1])});return c};h.has=function(b){P(arguments,1);b=String(b);for(var c=0;c<this.i.length;c++)if(this.i[c][0]===b)return!0;return!1};h.keys=function c(){var d=this,e,f,g,k,p;return K(c,function(t){1==t.h&&(e=v(d),f=e.next());if(3!=t.h){if(f.done){t.h=0;return}g=f.value;k=v(g);p=k.next().value;return E(t,p)}f=e.next();t.h=2})};h.set=function(c,d,e){P(arguments,2);c=String(c);var f=[],g=O(c,
d,e),k=!0;M(this.i,function(p){p[0]===c?k&&(k=!f.push(g)):f.push(p)});k&&f.push(g);this.i=f};h.values=function d(){var e=this,f,g,k,p,t;return K(d,function(x){1==x.h&&(f=v(e),g=f.next());if(3!=x.h){if(g.done){x.h=0;return}k=g.value;p=v(k);p.next();t=p.next().value;return E(x,t)}g=f.next();x.h=2})};X.prototype._asNative=function(){for(var d=new R,e=v(this),f=e.next();!f.done;f=e.next()){var g=v(f.value);f=g.next().value;g=g.next().value;d.append(f,g)}return d};X.prototype._blob=function(){var d="----formdata-polyfill-"+
Math.random(),e=[],f="--"+d+'\r\nContent-Disposition: form-data; name="';this.forEach(function(g,k){return"string"==typeof g?e.push(f+escape(N(k))+('"\r\n\r\n'+N(g)+"\r\n")):e.push(f+escape(N(k))+('"; filename="'+escape(g.name)+'"\r\nContent-Type: '+(g.type||"application/octet-stream")+"\r\n\r\n"),g,"\r\n")});e.push("--"+d+"--");return new Blob(e,{type:"multipart/form-data; boundary="+d})};X.prototype[Symbol.iterator]=function(){return this.entries()};X.prototype.toString=function(){return"[object FormData]"};
V&&!V.matches&&(V.matches=V.matchesSelector||V.mozMatchesSelector||V.msMatchesSelector||V.oMatchesSelector||V.webkitMatchesSelector||function(d){d=(this.document||this.ownerDocument).querySelectorAll(d);for(var e=d.length;0<=--e&&d.item(e)!==this;);return-1<e});W&&(X.prototype[W]="FormData");if(S){var Y=Q.XMLHttpRequest.prototype.setRequestHeader;Q.XMLHttpRequest.prototype.setRequestHeader=function(d,e){Y.call(this,d,e);"content-type"===d.toLowerCase()&&(this.B=!0)};Q.XMLHttpRequest.prototype.send=
function(d){d instanceof X?(d=d._blob(),this.B||this.setRequestHeader("Content-Type",d.type),S.call(this,d)):S.call(this,d)}}T&&(Q.fetch=function(d,e){e&&e.body&&e.body instanceof X&&(e.body=e.body._blob());return T.call(this,d,e)});U&&(Q.navigator.sendBeacon=function(d,e){e instanceof X&&(e=e._asNative());return U.call(this,d,e)});Q.FormData=X};})();


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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
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
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/formie": 0,
/******/ 			"css/fields/phone-country": 0,
/******/ 			"css/fields/tags": 0,
/******/ 			"css/formie-theme": 0,
/******/ 			"css/formie-base": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["formieConfigChunkLoadingGlobal"] = self["formieConfigChunkLoadingGlobal"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/js/formie.js")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/formie-base.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/formie-theme.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/fields/phone-country.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/fields/tags.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;