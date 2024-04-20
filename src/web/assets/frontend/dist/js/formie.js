/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/formie-form-base.js":
/*!************************************!*\
  !*** ./src/js/formie-form-base.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieFormBase: () => (/* binding */ FormieFormBase)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _formie_form_theme__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./formie-form-theme */ "./src/js/formie-form-theme.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }



// Create an event dispatcher for registering and triggering events, no matter the `dispatchEvent` or `addEventListener` order.
// This is useful for registering validation rules, where fields that are lazy-loaded might register validators, but are
// triggered after the Form Theme's `dispatchEvent`.
var EventDispatcher = /*#__PURE__*/function () {
  function EventDispatcher() {
    _classCallCheck(this, EventDispatcher);
    this.listeners = new Map();
    this.dispatchedEvents = new Map();
  }
  _createClass(EventDispatcher, [{
    key: "addEventListener",
    value: function addEventListener(eventName, callback) {
      if (!this.listeners.has(eventName)) {
        this.listeners.set(eventName, []);
      }
      this.listeners.get(eventName).push(callback);

      // If there are pending events, execute the callbacks for those events
      if (this.dispatchedEvents.has(eventName)) {
        var eventDetail = this.dispatchedEvents.get(eventName);
        callback(eventDetail);
      }
    }
  }, {
    key: "removeEventListener",
    value: function removeEventListener(eventName, callback) {
      if (!this.listeners.has(eventName)) {
        return;
      }
      var index = this.listeners.get(eventName).indexOf(callback);
      if (index !== -1) {
        this.listeners.get(eventName).splice(index, 1);
      }
    }
  }, {
    key: "dispatchEvent",
    value: function dispatchEvent(eventName, eventDetail) {
      if (!this.listeners.has(eventName)) {
        // If there are no listeners, store the event for future listeners
        this.dispatchedEvents.set(eventName, eventDetail);
        return;
      }
      var callbacks = this.listeners.get(eventName);
      callbacks.forEach(function (callback) {
        callback(eventDetail);
      });
    }
  }]);
  return EventDispatcher;
}();
var FormieFormBase = /*#__PURE__*/function () {
  function FormieFormBase($form) {
    var _this = this;
    var config = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    _classCallCheck(this, FormieFormBase);
    this.$form = $form;
    this.config = config;
    this.settings = config.settings;
    this.listeners = {};
    this.eventDispatcher = new EventDispatcher();
    if (!this.$form) {
      return;
    }
    this.$form.form = this;
    if (this.settings.outputJsTheme) {
      this.formTheme = new _formie_form_theme__WEBPACK_IMPORTED_MODULE_1__.FormieFormTheme(this.$form, this.config);
    }

    // Add helper classes to fields when their inputs are focused, have values etc.
    this.registerFieldEvents(this.$form);

    // Emit a custom event to let scripts know the Formie class is ready
    this.$form.dispatchEvent(new CustomEvent('onFormieReady', {
      bubbles: true,
      detail: {
        form: this
      }
    }));

    // Hijack the form's submit handler, in case we need to do something
    this.addEventListener(this.$form, 'submit', function (e) {
      e.preventDefault();
      var beforeSubmitEvent = _this.eventObject('onBeforeFormieSubmit', {
        submitHandler: _this
      });
      if (!_this.$form.dispatchEvent(beforeSubmitEvent)) {
        return;
      }

      // Add a little delay for UX
      setTimeout(function () {
        // Call the validation hooks
        if (!_this.validate() || !_this.afterValidate()) {
          return;
        }

        // Trigger Captchas
        if (!_this.validateCaptchas()) {
          return;
        }

        // Trigger Payment Integrations
        if (!_this.validatePayment()) {
          return;
        }

        // Proceed with submitting the form, which raises other validation events
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
      }));

      // Ensure that once completed, we re-fetch the captcha value, which will have expired
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

          // Special-case for adding just the attribute without "true" as the value
          if (value === true) {
            $element.setAttribute(attribute, '');
          } else {
            $element.setAttribute(attribute, value);
          }
        });
      }
    }
  }, {
    key: "registerEvent",
    value: function registerEvent(eventName, callback) {
      this.eventDispatcher.addEventListener(eventName, callback);
    }
  }, {
    key: "triggerEvent",
    value: function triggerEvent(eventName, options) {
      this.eventDispatcher.dispatchEvent(eventName, options);
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

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieFormTheme: () => (/* binding */ FormieFormTheme)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _validator_validator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./validator/validator */ "./src/js/validator/validator.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }


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
    this.form = this.$form.form;

    // Setup classes according to theme config
    this.loadingClass = this.form.getClasses('loading');
    this.tabErrorClass = this.form.getClasses('tabError');
    this.tabActiveClass = this.form.getClasses('tabActive');
    this.tabCompleteClass = this.form.getClasses('tabComplete');
    this.errorMessageClass = this.form.getClasses('errorMessage');
    this.successMessageClass = this.form.getClasses('successMessage');
    this.tabClass = this.form.getClasses('tab');
    this.initValidator();

    // Check if this is a success page and if we need to hide the notice
    // This is for non-ajax forms, where the page has reloaded
    this.hideSuccess();

    // Hijack the form's submit handler, in case we need to do something
    this.addSubmitEventListener();

    // Save the form's current state so we can tell if its changed later on
    this.updateFormHash();

    // Listen to form changes if the user tries to reload
    if (this.settings.enableUnloadWarning) {
      this.addFormUnloadEventListener();
    }

    // Listen to tabs being clicked for ajax-enabled forms
    if (this.settings.submitMethod === 'ajax') {
      this.formTabEventListener();
    }

    // Emit a custom event to let scripts know the Formie class is ready
    this.$form.dispatchEvent(new CustomEvent('onFormieThemeReady', {
      bubbles: true,
      detail: {
        theme: this,
        addValidator: this.addValidator.bind(this)
      }
    }));
  }
  _createClass(FormieFormTheme, [{
    key: "initValidator",
    value: function initValidator() {
      var validatorSettings = {
        live: this.validationOnFocus,
        fieldContainerErrorClass: 'fui-error',
        inputErrorClass: 'fui-error',
        messagesClass: this.form.getClasses('fieldErrors'),
        messageClass: this.form.getClasses('errorMessage')
      };
      this.validator = new _validator_validator__WEBPACK_IMPORTED_MODULE_1__["default"](this.$form, validatorSettings);

      // Allow other modules to modify our validator. Use `triggerEvent` to support calling `registerEvent` as different
      // times during the app, as some fields or custom validators can be registered after this call.
      this.form.triggerEvent('registerFormieValidation', {
        validator: this.validator
      });
    }
  }, {
    key: "addValidator",
    value: function addValidator() {
      var _arguments = arguments;
      this.form.registerEvent('registerFormieValidation', function (e) {
        var _e$validator;
        (_e$validator = e.validator).addValidator.apply(_e$validator, _toConsumableArray(_arguments));
      });
    }
  }, {
    key: "addSubmitEventListener",
    value: function addSubmitEventListener() {
      var _this = this;
      var $submitBtns = this.$form.querySelectorAll('[type="submit"]');

      // Forms can have multiple submit buttons, and its easier to assign the currently clicked one
      // than tracking it through the submit handler.
      $submitBtns.forEach(function ($submitBtn) {
        _this.form.addEventListener($submitBtn, 'click', function (e) {
          _this.$submitBtn = e.target;

          // Store for later if we're using text spinner
          _this.originalButtonText = _this.$submitBtn.textContent.trim();
          var submitAction = _this.$submitBtn.getAttribute('data-submit-action') || 'submit';

          // Each submit button can do different things, to store that
          _this.updateSubmitAction(submitAction);
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
      this.beforeSubmit();

      // Save for later to trigger real submit
      this.submitHandler = e.detail.submitHandler;
    }
  }, {
    key: "onValidate",
    value: function onValidate(e) {
      // If invalid, we only want to stop if we're submitting.
      if (!this.validate()) {
        this.onFormError();

        // Set a flag on the event, so other listeners can potentially do something
        e.detail.invalid = true;
        e.preventDefault();
      }
    }
  }, {
    key: "onSubmit",
    value: function onSubmit(e) {
      // Stop base behaviour of just submitting the form
      e.preventDefault();

      // Either staight submit, or use Ajax
      if (this.settings.submitMethod === 'ajax') {
        this.ajaxSubmit();
      } else {
        // Before a server-side submit, refresh the saved hash immediately. Otherwise, the native submit
        // handler - which technically unloads the page - will trigger the changed alert.
        // But trigger an alert if we're going back, and back-submission data isn't set
        if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {
          // Don't reset the hash, trigger a warning if content has changed, because we're not submitting
        } else {
          this.updateFormHash();
        }

        // Triger any JS events for this page, only if submitting (not going back/saving)
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
      var _this2 = this;
      this.form.addEventListener(window, 'beforeunload', function (e) {
        if (_this2.savedFormHash !== _this2.hashForm()) {
          e.preventDefault();
          return e.returnValue = (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Are you sure you want to leave?');
        }
      });
    }
  }, {
    key: "formTabEventListener",
    value: function formTabEventListener() {
      var _this3 = this;
      var $tabs = this.$form.querySelectorAll('[data-fui-page-tab-anchor]');
      $tabs.forEach(function ($tab) {
        _this3.form.addEventListener($tab, 'click', function (e) {
          e.preventDefault();
          var pageIndex = e.target.getAttribute('data-fui-page-index');
          var pageId = e.target.getAttribute('data-fui-page-id');
          _this3.togglePage({
            nextPageIndex: pageIndex,
            nextPageId: pageId,
            totalPages: _this3.settings.pages.length
          });

          // Ensure we still update the current page server-side
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
      var formData = new FormData(this.$form);

      // Exlcude some params from the hash, that are programatically changed
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
      var focus = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      if (!this.validationOnSubmit) {
        return true;
      }

      // Only validate on submit actions
      if (this.form.submitAction !== 'submit') {
        return true;
      }
      var $fieldset = this.$form;
      if (this.$currentPage) {
        $fieldset = this.$currentPage;
      }

      // Validate just the current page (if there is one) or the entire form
      this.validator.validate($fieldset);
      var errors = this.validator.getErrors();

      // // If there are errors, focus on the first one
      if (errors.length > 0 && focus) {
        errors[0].input.focus();
      }

      // Remove any global errors if none - just in case
      if (errors.length === 0) {
        this.removeFormAlert();
      }
      return !errors.length;
    }
  }, {
    key: "hideSuccess",
    value: function hideSuccess() {
      var $successMessage = this.$form.parentNode.querySelector('[data-fui-alert-success]');
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
        $alert.innerHTML = text;

        // Set attributes on the alert according to theme config
        this.form.applyThemeConfig($alert, 'alert');

        // For error notices, we have potential special handling on position
        if (type == 'error') {
          this.form.applyThemeConfig($alert, 'alertError');
          if (this.settings.errorMessagePosition == 'bottom-form') {
            this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
          } else if (this.settings.errorMessagePosition == 'top-form') {
            this.$form.parentNode.insertBefore($alert, this.$form);
          }
        } else {
          this.form.applyThemeConfig($alert, 'alertSuccess');
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
      var _this4 = this;
      Object.keys(errors).forEach(function (pageId, index) {
        var $tab = _this4.$form.parentNode.querySelector("[data-fui-page-id=\"".concat(pageId, "\"]"));
        if ($tab) {
          $tab.parentNode.classList.add(_this4.tabErrorClass);
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
      var $alert = this.$form.parentNode.querySelector('[data-fui-alert-error]');
      if ($alert) {
        $alert.remove();
      }
    }
  }, {
    key: "removeTabErrors",
    value: function removeTabErrors() {
      var _this5 = this;
      var $tabs = this.$form.parentNode.querySelectorAll('[data-fui-page-tab]');
      $tabs.forEach(function ($tab) {
        $tab.classList.remove(_this5.tabErrorClass);
      });
    }
  }, {
    key: "beforeSubmit",
    value: function beforeSubmit() {
      var _this$validator;
      (_this$validator = this.validator) === null || _this$validator === void 0 || _this$validator.removeAllErrors();
      this.removeFormAlert();
      this.removeTabErrors();

      // Don't set a loading if we're going back and the unload warning appears, because there's no way to re-enable
      // the button after the user cancels the unload event
      if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {
        // Do nothing
      } else {
        this.addLoading();
      }
    }
  }, {
    key: "ajaxSubmit",
    value: function ajaxSubmit() {
      var _this6 = this;
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
        _this6.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request timed out.'));
      };
      xhr.onerror = function (e) {
        _this6.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('The request encountered a network error. Please try again.'));
      };
      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.errors) {
              _this6.onAjaxError(response.errorMessage, response);
            } else {
              _this6.onAjaxSuccess(response);
            }
          } catch (e) {
            _this6.onAjaxError((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('Unable to parse response `{e}`.', {
              e: e
            }));
          }
        } else {
          _this6.onAjaxError("".concat(xhr.status, ": ").concat(xhr.statusText));
        }
      };
      xhr.send(formData);
    }
  }, {
    key: "afterAjaxSubmit",
    value: function afterAjaxSubmit(data) {
      var _this7 = this;
      // Reset the submit action, immediately, whether fail or success
      this.updateSubmitAction('submit');
      this.updateSubmissionInput(data);

      // Check if there's any events in the response back, and fire them
      if (data.events && Array.isArray(data.events) && data.events.length) {
        // An error message may be shown in some cases (for 3D secure) so remove the form-global level error notice.
        this.removeFormAlert();
        data.events.forEach(function (eventData) {
          _this7.$form.dispatchEvent(new CustomEvent(eventData.event, {
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
      var _this8 = this;
      var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var errors = data.errors || {};
      var pageFieldErrors = data.pageFieldErrors || {};

      // Show an error message at the top of the form
      this.onFormError(errorMessage);

      // Update the page tabs (if any) to show error state
      this.showTabErrors(pageFieldErrors);

      // Fire a fail event
      this.submitHandler.formSubmitError(data);

      // Fire cleanup methods after _any_ ajax call
      this.afterAjaxSubmit(data);

      // Show server-side errors for each field
      Object.keys(errors).forEach(function (handle, index) {
        var _errors$handle = _slicedToArray(errors[handle], 1),
          error = _errors$handle[0];
        var selector = handle.split('.');
        selector = selector.join('][');
        var $field = _this8.$form.querySelector("[name=\"fields[".concat(selector, "]\"]"));

        // Check for multiple fields
        if (!$field) {
          $field = _this8.$form.querySelector("[name=\"fields[".concat(selector, "][]\"]"));
        }

        // Handle Repeater/Groups - a little more complicated to translate `group[0].field.handle`
        if (!$field && handle.includes('[')) {
          var blockIndex = handle.match(/\[(.*?)\]/)[1] || null;
          var regexString = "fields[".concat(handle.replace(/\./g, '][').replace(']]', ']').replace(/\[.*?\]/, '][rows][.*][fields]'), "]");
          regexString = regexString.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
          var $targets = _this8.querySelectorAllRegex(new RegExp(regexString), 'name');
          if ($targets.length && $targets[blockIndex]) {
            $field = $targets[blockIndex];
          }
        }
        if ($field) {
          if (error) {
            var _this8$validator;
            (_this8$validator = _this8.validator) === null || _this8$validator === void 0 || _this8$validator.showError($field, 'server', error);
          }

          // Focus on the first error
          if (index === 0) {
            $field.focus();
          }
        }
      });

      // Go to the first page with an error, for good UX
      this.togglePage(data, false);
    }
  }, {
    key: "onAjaxSuccess",
    value: function onAjaxSuccess(data) {
      // Fire the event, because we've overridden the handler
      this.submitHandler.formAfterSubmit(data);

      // Fire cleanup methods after _any_ ajax call
      this.afterAjaxSubmit(data);

      // Reset the form hash, as all has been saved
      this.updateFormHash();

      // Triger any JS events for this page, right away before navigating away
      if (this.form.submitAction === 'submit') {
        this.triggerJsEvents();
      }

      // Check if we need to proceed to the next page
      if (data.nextPageId) {
        this.removeLoading();
        this.togglePage(data);
        return;
      }

      // If people have provided a redirect behaviour to handle their own redirecting
      if (data.redirectCallback) {
        data.redirectCallback();
        return;
      }

      // If we're redirecting away, do it immediately for nicer UX
      if (data.redirectUrl) {
        if (this.settings.submitActionTab === 'new-tab') {
          // Reset values if in a new tab. No need when in the same tab.
          this.resetForm();

          // Allow people to modify the target from `window` with `redirectTarget`
          data.redirectTarget.open(data.redirectUrl, '_blank');
        } else {
          data.redirectTarget.location.href = data.redirectUrl;
        }
        return;
      }

      // Delay this a little, in case we're redirecting away - better UX to just keep it loading
      this.removeLoading();

      // For multi-page ajax forms, deal with them a little differently.
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
          }

          // Remove the back button - not great UX to go back to a finished form
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
        this.showFormAlert(submitActionMessage, 'success');

        // Check if we need to remove the success message
        this.hideSuccess();
        if (this.settings.submitActionFormHide) {
          this.$form.style.display = 'none';
        }

        // Smooth-scroll to the top of the form.
        if (this.settings.scrollToTop) {
          this.scrollToForm();
        }
      }

      // Reset values regardless, for the moment
      this.resetForm();

      // Remove the submission ID input in case we want to go again
      this.removeHiddenInput('submissionId');

      // Reset the form hash, as all has been saved
      this.updateFormHash();
    }
  }, {
    key: "updateSubmitAction",
    value: function updateSubmitAction(action) {
      // All buttons should have a `[data-submit-action]` but just for backward-compatibility
      // assume when not present, we're submitting
      if (!action) {
        action = 'submit';
      }

      // Update the submit action on the form while we're at it. Store on the `$form`
      // for each of lookup on event hooks like captchas.
      this.form.submitAction = action;
      this.updateOrCreateHiddenInput('submitAction', action);
    }
  }, {
    key: "updateSubmissionInput",
    value: function updateSubmissionInput(data) {
      if (!data.submissionId || !data.nextPageId) {
        return;
      }

      // Add the hidden submission input, if it doesn't exist
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
      var _this9 = this;
      var scrollToTop = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
      // Trigger an event when a page is toggled
      this.$form.dispatchEvent(new CustomEvent('onFormiePageToggle', {
        bubbles: true,
        detail: {
          data: data
        }
      }));

      // Hide all pages
      var $allPages = this.$form.querySelectorAll('[data-fui-page]');
      if (data.nextPageId) {
        $allPages.forEach(function ($page) {
          // Show the current page
          if ($page.id === "".concat(_this9.getPageId(data.nextPageId))) {
            $page.removeAttribute('data-fui-page-hidden');
          } else {
            $page.setAttribute('data-fui-page-hidden', true);
          }
        });
      }

      // Update tabs and progress bar if we're using them
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
          if ($tab.id === "".concat(_this9.tabClass, "-").concat(data.nextPageId)) {
            $tab.classList.add(_this9.tabActiveClass);
          } else {
            $tab.classList.remove(_this9.tabActiveClass);
          }
        });
        var isComplete = true;
        $tabs.forEach(function ($tab) {
          if ($tab.classList.contains(_this9.tabActiveClass)) {
            isComplete = false;
          }
          if (isComplete) {
            $tab.classList.add(_this9.tabCompleteClass);
          } else {
            $tab.classList.remove(_this9.tabCompleteClass);
          }
        });

        // Update the current page
        this.setCurrentPage(data.nextPageId);
      }

      // Smooth-scroll to the top of the form.
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
      var _this10 = this;
      return this.settings.pages.find(function (page) {
        return page.id == _this10.settings.currentPageId;
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
      var extraMargin = parseInt(getComputedStyle(document.documentElement).scrollMarginTop) || 0;

      // Because the form can be hidden, use the parent wrapper
      window.scrollTo({
        top: this.$form.parentNode.getBoundingClientRect().top + window.pageYOffset - 100 - extraPadding - extraMargin,
        behavior: 'smooth'
      });
    }
  }, {
    key: "triggerJsEvents",
    value: function triggerJsEvents() {
      var currentPage = this.getCurrentPage();

      // Find any JS events for the current page and fire
      if (currentPage && currentPage.settings.enableJsEvents) {
        var payload = {};
        currentPage.settings.jsGtmEventOptions.forEach(function (option) {
          payload[option.label] = option.value;
        });

        // Push to the datalayer
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

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Formie: () => (/* binding */ Formie)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _formie_form_base__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./formie-form-base */ "./src/js/formie-form-base.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function _regeneratorRuntime() { "use strict"; /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */ _regeneratorRuntime = function _regeneratorRuntime() { return e; }; var t, e = {}, r = Object.prototype, n = r.hasOwnProperty, o = Object.defineProperty || function (t, e, r) { t[e] = r.value; }, i = "function" == typeof Symbol ? Symbol : {}, a = i.iterator || "@@iterator", c = i.asyncIterator || "@@asyncIterator", u = i.toStringTag || "@@toStringTag"; function define(t, e, r) { return Object.defineProperty(t, e, { value: r, enumerable: !0, configurable: !0, writable: !0 }), t[e]; } try { define({}, ""); } catch (t) { define = function define(t, e, r) { return t[e] = r; }; } function wrap(t, e, r, n) { var i = e && e.prototype instanceof Generator ? e : Generator, a = Object.create(i.prototype), c = new Context(n || []); return o(a, "_invoke", { value: makeInvokeMethod(t, r, c) }), a; } function tryCatch(t, e, r) { try { return { type: "normal", arg: t.call(e, r) }; } catch (t) { return { type: "throw", arg: t }; } } e.wrap = wrap; var h = "suspendedStart", l = "suspendedYield", f = "executing", s = "completed", y = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} var p = {}; define(p, a, function () { return this; }); var d = Object.getPrototypeOf, v = d && d(d(values([]))); v && v !== r && n.call(v, a) && (p = v); var g = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(p); function defineIteratorMethods(t) { ["next", "throw", "return"].forEach(function (e) { define(t, e, function (t) { return this._invoke(e, t); }); }); } function AsyncIterator(t, e) { function invoke(r, o, i, a) { var c = tryCatch(t[r], t, o); if ("throw" !== c.type) { var u = c.arg, h = u.value; return h && "object" == _typeof(h) && n.call(h, "__await") ? e.resolve(h.__await).then(function (t) { invoke("next", t, i, a); }, function (t) { invoke("throw", t, i, a); }) : e.resolve(h).then(function (t) { u.value = t, i(u); }, function (t) { return invoke("throw", t, i, a); }); } a(c.arg); } var r; o(this, "_invoke", { value: function value(t, n) { function callInvokeWithMethodAndArg() { return new e(function (e, r) { invoke(t, n, e, r); }); } return r = r ? r.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg(); } }); } function makeInvokeMethod(e, r, n) { var o = h; return function (i, a) { if (o === f) throw new Error("Generator is already running"); if (o === s) { if ("throw" === i) throw a; return { value: t, done: !0 }; } for (n.method = i, n.arg = a;;) { var c = n.delegate; if (c) { var u = maybeInvokeDelegate(c, n); if (u) { if (u === y) continue; return u; } } if ("next" === n.method) n.sent = n._sent = n.arg;else if ("throw" === n.method) { if (o === h) throw o = s, n.arg; n.dispatchException(n.arg); } else "return" === n.method && n.abrupt("return", n.arg); o = f; var p = tryCatch(e, r, n); if ("normal" === p.type) { if (o = n.done ? s : l, p.arg === y) continue; return { value: p.arg, done: n.done }; } "throw" === p.type && (o = s, n.method = "throw", n.arg = p.arg); } }; } function maybeInvokeDelegate(e, r) { var n = r.method, o = e.iterator[n]; if (o === t) return r.delegate = null, "throw" === n && e.iterator["return"] && (r.method = "return", r.arg = t, maybeInvokeDelegate(e, r), "throw" === r.method) || "return" !== n && (r.method = "throw", r.arg = new TypeError("The iterator does not provide a '" + n + "' method")), y; var i = tryCatch(o, e.iterator, r.arg); if ("throw" === i.type) return r.method = "throw", r.arg = i.arg, r.delegate = null, y; var a = i.arg; return a ? a.done ? (r[e.resultName] = a.value, r.next = e.nextLoc, "return" !== r.method && (r.method = "next", r.arg = t), r.delegate = null, y) : a : (r.method = "throw", r.arg = new TypeError("iterator result is not an object"), r.delegate = null, y); } function pushTryEntry(t) { var e = { tryLoc: t[0] }; 1 in t && (e.catchLoc = t[1]), 2 in t && (e.finallyLoc = t[2], e.afterLoc = t[3]), this.tryEntries.push(e); } function resetTryEntry(t) { var e = t.completion || {}; e.type = "normal", delete e.arg, t.completion = e; } function Context(t) { this.tryEntries = [{ tryLoc: "root" }], t.forEach(pushTryEntry, this), this.reset(!0); } function values(e) { if (e || "" === e) { var r = e[a]; if (r) return r.call(e); if ("function" == typeof e.next) return e; if (!isNaN(e.length)) { var o = -1, i = function next() { for (; ++o < e.length;) if (n.call(e, o)) return next.value = e[o], next.done = !1, next; return next.value = t, next.done = !0, next; }; return i.next = i; } } throw new TypeError(_typeof(e) + " is not iterable"); } return GeneratorFunction.prototype = GeneratorFunctionPrototype, o(g, "constructor", { value: GeneratorFunctionPrototype, configurable: !0 }), o(GeneratorFunctionPrototype, "constructor", { value: GeneratorFunction, configurable: !0 }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, u, "GeneratorFunction"), e.isGeneratorFunction = function (t) { var e = "function" == typeof t && t.constructor; return !!e && (e === GeneratorFunction || "GeneratorFunction" === (e.displayName || e.name)); }, e.mark = function (t) { return Object.setPrototypeOf ? Object.setPrototypeOf(t, GeneratorFunctionPrototype) : (t.__proto__ = GeneratorFunctionPrototype, define(t, u, "GeneratorFunction")), t.prototype = Object.create(g), t; }, e.awrap = function (t) { return { __await: t }; }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, c, function () { return this; }), e.AsyncIterator = AsyncIterator, e.async = function (t, r, n, o, i) { void 0 === i && (i = Promise); var a = new AsyncIterator(wrap(t, r, n, o), i); return e.isGeneratorFunction(r) ? a : a.next().then(function (t) { return t.done ? t.value : a.next(); }); }, defineIteratorMethods(g), define(g, u, "Generator"), define(g, a, function () { return this; }), define(g, "toString", function () { return "[object Generator]"; }), e.keys = function (t) { var e = Object(t), r = []; for (var n in e) r.push(n); return r.reverse(), function next() { for (; r.length;) { var t = r.pop(); if (t in e) return next.value = t, next.done = !1, next; } return next.done = !0, next; }; }, e.values = values, Context.prototype = { constructor: Context, reset: function reset(e) { if (this.prev = 0, this.next = 0, this.sent = this._sent = t, this.done = !1, this.delegate = null, this.method = "next", this.arg = t, this.tryEntries.forEach(resetTryEntry), !e) for (var r in this) "t" === r.charAt(0) && n.call(this, r) && !isNaN(+r.slice(1)) && (this[r] = t); }, stop: function stop() { this.done = !0; var t = this.tryEntries[0].completion; if ("throw" === t.type) throw t.arg; return this.rval; }, dispatchException: function dispatchException(e) { if (this.done) throw e; var r = this; function handle(n, o) { return a.type = "throw", a.arg = e, r.next = n, o && (r.method = "next", r.arg = t), !!o; } for (var o = this.tryEntries.length - 1; o >= 0; --o) { var i = this.tryEntries[o], a = i.completion; if ("root" === i.tryLoc) return handle("end"); if (i.tryLoc <= this.prev) { var c = n.call(i, "catchLoc"), u = n.call(i, "finallyLoc"); if (c && u) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } else if (c) { if (this.prev < i.catchLoc) return handle(i.catchLoc, !0); } else { if (!u) throw new Error("try statement without catch or finally"); if (this.prev < i.finallyLoc) return handle(i.finallyLoc); } } } }, abrupt: function abrupt(t, e) { for (var r = this.tryEntries.length - 1; r >= 0; --r) { var o = this.tryEntries[r]; if (o.tryLoc <= this.prev && n.call(o, "finallyLoc") && this.prev < o.finallyLoc) { var i = o; break; } } i && ("break" === t || "continue" === t) && i.tryLoc <= e && e <= i.finallyLoc && (i = null); var a = i ? i.completion : {}; return a.type = t, a.arg = e, i ? (this.method = "next", this.next = i.finallyLoc, y) : this.complete(a); }, complete: function complete(t, e) { if ("throw" === t.type) throw t.arg; return "break" === t.type || "continue" === t.type ? this.next = t.arg : "return" === t.type ? (this.rval = this.arg = t.arg, this.method = "return", this.next = "end") : "normal" === t.type && e && (this.next = e), y; }, finish: function finish(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.finallyLoc === t) return this.complete(r.completion, r.afterLoc), resetTryEntry(r), y; } }, "catch": function _catch(t) { for (var e = this.tryEntries.length - 1; e >= 0; --e) { var r = this.tryEntries[e]; if (r.tryLoc === t) { var n = r.completion; if ("throw" === n.type) { var o = n.arg; resetTryEntry(r); } return o; } } throw new Error("illegal catch attempt"); }, delegateYield: function delegateYield(e, r, n) { return this.delegate = { iterator: values(e), resultName: r, nextLoc: n }, "next" === this.method && (this.arg = t), y; } }, e; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }


var Formie = /*#__PURE__*/function () {
  function Formie() {
    _classCallCheck(this, Formie);
    this.forms = [];
  }
  _createClass(Formie, [{
    key: "initForms",
    value: function initForms() {
      var _this = this;
      var useObserver = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
      this.$forms = document.querySelectorAll('form[data-fui-form]') || [];

      // We use this in the CP, where it's a bit tricky to add a form ID. So check just in case.
      // Might also be handy for front-end too!
      if (!this.$forms.length) {
        this.$forms = document.querySelectorAll('div[data-fui-form]') || [];
      }
      this.$forms.forEach(function ($form) {
        // Check if we want to use an `IntersectionObserver` to only initialize the form when visible
        if (useObserver) {
          var observer = new IntersectionObserver(function (entries) {
            if (entries[0].intersectionRatio !== 0) {
              _this.initForm($form);

              // Stop listening to prevent multiple init - just in case
              observer.disconnect();
            }
          });
          observer.observe($form);
        } else {
          _this.initForm($form);
        }
      });

      // Emit a custom event to let scripts know the Formie class is ready
      document.dispatchEvent(new CustomEvent('onFormieLoaded', {
        bubbles: true,
        detail: {
          formie: this
        }
      }));
    }
  }, {
    key: "initForm",
    value: function () {
      var _initForm = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee($form) {
        var _this2 = this;
        var formConfig,
          initializeForm,
          registeredJs,
          form,
          _args = arguments;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              formConfig = _args.length > 1 && _args[1] !== undefined ? _args[1] : {};
              if ((0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(formConfig)) {
                // Initialize the form class with the `data-fui-form` param on the form
                formConfig = JSON.parse($form.getAttribute('data-fui-form'));
              }
              if (!(0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(formConfig)) {
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
              formConfig.Formie = this;

              // Create the form class, save it to our collection
              form = new _formie_form_base__WEBPACK_IMPORTED_MODULE_1__.FormieFormBase($form, formConfig);
              this.forms.push(form);

              // Find all `data-field-config` attributes for the current page and form
              // and build an object of them to initialize when loaded.
              form.fieldConfigs = this.parseFieldConfig($form, $form);

              // Is there any additional JS config registered for this form?
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
              document.body.appendChild(form.$registeredJs);

              // Create a `<script>` for each registered JS
              registeredJs.forEach(function (config) {
                var $script = document.createElement('script');

                // Check if we've provided an external script to load. Ensure they're deferred so they don't block
                // and use the onload call to trigger any actual scripts once its been loaded.
                if (config.src) {
                  $script.src = config.src;
                  $script.defer = true;

                  // Initialize all matching fields - their config is already rendered in templates
                  $script.onload = function () {
                    if (config.module) {
                      var fieldConfigs = form.fieldConfigs[config.module];

                      // Handle multiple fields on a page, creating a new JS class instance for each
                      if (fieldConfigs && Array.isArray(fieldConfigs) && fieldConfigs.length) {
                        fieldConfigs.forEach(function (fieldConfig) {
                          _this2.initJsClass(config.module, fieldConfig);
                        });
                      }

                      // Handle integrations that have global settings, instead of per-field
                      if (config.settings) {
                        _this2.initJsClass(config.module, _objectSpread({
                          $form: $form
                        }, config.settings));
                      }

                      // Special handling for some JS modules
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
              // Emit a custom event to let scripts know the Formie class is ready
              document.dispatchEvent(new CustomEvent('onFormieInit', {
                bubbles: true,
                detail: {
                  formie: this,
                  form: form,
                  $form: $form,
                  formId: form.config.formHashId
                }
              }));
            case 23:
            case "end":
              return _context.stop();
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
    }

    // Note the use of $form and $element to handle Repeater
  }, {
    key: "parseFieldConfig",
    value: function parseFieldConfig($element, $form) {
      var config = {};
      $element.querySelectorAll('[data-field-config]').forEach(function ($field) {
        var fieldConfig = JSON.parse($field.getAttribute('data-field-config'));

        // Some fields supply multiple modules, so normalise for ease-of-processing
        if (!Array.isArray(fieldConfig)) {
          fieldConfig = [fieldConfig];
        }
        fieldConfig.forEach(function (nestedFieldConfig) {
          if (!config[nestedFieldConfig.module]) {
            config[nestedFieldConfig.module] = [];
          }

          // Provide field classes with the data they need
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
      var _destroyForm = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee2(form) {
        var $form, index;
        return _regeneratorRuntime().wrap(function _callee2$(_context2) {
          while (1) switch (_context2.prev = _context2.next) {
            case 0:
              // Allow passing in a DOM element, or a FormieBaseForm object
              if (form instanceof _formie_form_base__WEBPACK_IMPORTED_MODULE_1__.FormieFormBase) {
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
              form.destroyed = true;

              // Delete any additional scripts for the form - if any
              if (form.$registeredJs && form.$registeredJs.parentNode) {
                form.$registeredJs.parentNode.removeChild(form.$registeredJs);
              }

              // Trigger an event (before events are removed)
              form.formDestroy({
                form: form
              });

              // Remove all event listeners attached to this form
              if (!(0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(form.listeners)) {
                Object.keys(form.listeners).forEach(function (eventKey) {
                  form.removeEventListener(eventKey);
                });
              }

              // Destroy Bouncer events
              if (form.formTheme && form.formTheme.validator) {
                form.formTheme.validator.destroy();
              }

              // Delete it from the factory
              this.forms.splice(index, 1);
            case 12:
            case "end":
              return _context2.stop();
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
        var $form = form.$form;

        // Update the CSRF input
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
        }

        // Update any captchas
        if (result.captchas) {
          Object.entries(result.captchas).forEach(function (_ref) {
            var _ref2 = _slicedToArray(_ref, 2),
              key = _ref2[0],
              value = _ref2[1];
            // In some cases, the captcha input might not have loaded yet, as some are dynamically created
            // (see Duplicate and JS captchas). So wait for the element to exist first
            (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.waitForElement)("input[name=\"".concat(value.sessionKey, "\"]"), $form).then(function ($captchaInput) {
              if (value.value) {
                $captchaInput.value = value.value;
                console.log("".concat(formHashId, ": Refreshed \"").concat(key, "\" captcha input %o."), value);
              }
            });

            // Add a timeout purely for logging, in case the element doesn't resolve in a reasonable time
            setTimeout(function () {
              if (!$form.querySelector("input[name=\"".concat(value.sessionKey, "\"]"))) {
                console.error("".concat(formHashId, ": Unable to locate captcha input for \"").concat(key, "\"."));
              }
            }, 10000);
          });
        }

        // Update the form's hash (if using Formie's themed JS)
        if (form.formTheme) {
          form.formTheme.updateFormHash();
        }

        // Fire a callback for users to do other bits
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

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_polyfills__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/polyfills */ "./src/js/utils/polyfills.js");
/* harmony import */ var _formie_lib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./formie-lib */ "./src/js/formie-lib.js");



// This should only be used when initializing Formie from the browser. When initializing with JS directly
// import `formie-lib.js` directly into your JS modules.
window.Formie = new _formie_lib__WEBPACK_IMPORTED_MODULE_1__.Formie();

// Whether we want to initialize the forms automatically.
var script = document.currentScript;
var initForms = script !== null && script !== void 0 && script.hasAttribute('data-manual-init') ? false : true;
var useObserver = script !== null && script !== void 0 && script.hasAttribute('data-bypass-observer') ? false : true;

// Don't init forms until the document is ready, or the document already loaded
// https://developer.mozilla.org/en-US/docs/Web/API/Document/DOMContentLoaded_event#checking_whether_loading_is_already_complete
if (initForms) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function (event) {
      window.Formie.initForms(useObserver);
    });
  } else {
    window.Formie.initForms(useObserver);
  }
}

/***/ }),

/***/ "./src/js/utils/polyfills.js":
/*!***********************************!*\
  !*** ./src/js/utils/polyfills.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
})();

// FormData


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

/***/ "./src/js/validator/rules/index.js":
/*!*****************************************!*\
  !*** ./src/js/validator/rules/index.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _match__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./match */ "./src/js/validator/rules/match.js");
/* harmony import */ var _pattern__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./pattern */ "./src/js/validator/rules/pattern.js");
/* harmony import */ var _required__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./required */ "./src/js/validator/rules/required.js");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  match: _match__WEBPACK_IMPORTED_MODULE_0__["default"],
  pattern: _pattern__WEBPACK_IMPORTED_MODULE_1__["default"],
  required: _required__WEBPACK_IMPORTED_MODULE_2__["default"]
});

/***/ }),

/***/ "./src/js/validator/rules/match.js":
/*!*****************************************!*\
  !*** ./src/js/validator/rules/match.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   message: () => (/* binding */ message),
/* harmony export */   rule: () => (/* binding */ rule)
/* harmony export */ });
var getSourceField = function getSourceField(field, match) {
  // Get the source field to match against
  var form = field.closest('form');
  if (!form) {
    return false;
  }
  return form.querySelector("[data-field-handle=\"".concat(match, "\"]"));
};

// eslint-disable-next-line
var rule = function rule(_ref) {
  var field = _ref.field,
    input = _ref.input,
    config = _ref.config,
    getRule = _ref.getRule;
  var match = getRule('match');

  // Ignore any field that doesn't have a "match" rule
  if (!match) {
    return true;
  }
  var sourceField = getSourceField(field, match);
  if (!sourceField) {
    return true;
  }
  var sourceInput = sourceField.querySelector(config.fieldsSelector);
  if (!sourceInput) {
    return true;
  }

  // Serialize the form, then lookup value. We only support simple comparing right now
  var sourceValue = sourceInput.value;
  var destinationValue = input.value;
  return sourceValue === destinationValue;
};

// eslint-disable-next-line
var message = function message(_ref2) {
  var _sourceField$querySel, _sourceField$querySel2;
  var field = _ref2.field,
    label = _ref2.label,
    t = _ref2.t,
    getRule = _ref2.getRule;
  var match = getRule('match');
  var sourceField = getSourceField(field, match);
  var sourceLabel = (_sourceField$querySel = sourceField === null || sourceField === void 0 || (_sourceField$querySel2 = sourceField.querySelector('[data-field-label]')) === null || _sourceField$querySel2 === void 0 || (_sourceField$querySel2 = _sourceField$querySel2.childNodes[0].textContent) === null || _sourceField$querySel2 === void 0 ? void 0 : _sourceField$querySel2.trim()) !== null && _sourceField$querySel !== void 0 ? _sourceField$querySel : '';
  return t('{name} must match {value}.', {
    name: label,
    value: sourceLabel
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  rule: rule,
  message: message
});

/***/ }),

/***/ "./src/js/validator/rules/pattern.js":
/*!*******************************************!*\
  !*** ./src/js/validator/rules/pattern.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   message: () => (/* binding */ message),
/* harmony export */   rule: () => (/* binding */ rule)
/* harmony export */ });
var rule = function rule(_ref) {
  var input = _ref.input,
    config = _ref.config;
  var pattern = input.getAttribute('pattern');
  var patternToMatch = pattern ? new RegExp("^(?:".concat(pattern, ")$")) : config.patterns[input.type];
  if (!patternToMatch || !input.value || input.value.length < 1) {
    return true;
  }
  return input.value.match(patternToMatch) ? true : false;
};
var message = function message(_ref2) {
  var _ref3, _input$getAttribute;
  var input = _ref2.input,
    label = _ref2.label,
    t = _ref2.t;
  var messages = {
    email: t('{attribute} is not a valid email address.', {
      attribute: label
    }),
    url: t('{attribute} is not a valid URL.', {
      attribute: label
    }),
    number: t('{attribute} is not a valid number.', {
      attribute: label
    }),
    "default": t('{attribute} is not a valid format.', {
      attribute: label
    })
  };
  return (_ref3 = (_input$getAttribute = input.getAttribute("data-pattern-".concat(input.type, "-message"))) !== null && _input$getAttribute !== void 0 ? _input$getAttribute : messages[input.type]) !== null && _ref3 !== void 0 ? _ref3 : messages["default"];
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  rule: rule,
  message: message
});

/***/ }),

/***/ "./src/js/validator/rules/required.js":
/*!********************************************!*\
  !*** ./src/js/validator/rules/required.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   message: () => (/* binding */ message),
/* harmony export */   rule: () => (/* binding */ rule)
/* harmony export */ });
var rule = function rule(_ref) {
  var input = _ref.input;
  if (!input.hasAttribute('required') || input.type === 'hidden') {
    return true;
  }

  // For checkboxes (singular and group) and radio buttons
  if (input.type === 'checkbox' || input.type === 'radio') {
    var checkboxInputs = input.form.querySelectorAll("[name=\"".concat(input.name, "\"]:not([type=\"hidden\"])"));
    if (checkboxInputs.length) {
      var checkedInputs = Array.prototype.filter.call(checkboxInputs, function (btn) {
        return btn.checked;
      });
      return checkedInputs.length;
    }
    return input.checked;
  }
  return input.value.trim() !== '';
};
var message = function message(_ref2) {
  var _input$getAttribute;
  var input = _ref2.input,
    label = _ref2.label,
    t = _ref2.t;
  return (_input$getAttribute = input.getAttribute('data-required-message')) !== null && _input$getAttribute !== void 0 ? _input$getAttribute : t('{attribute} cannot be blank.', {
    attribute: label
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  rule: rule,
  message: message
});

/***/ }),

/***/ "./src/js/validator/validator.js":
/*!***************************************!*\
  !*** ./src/js/validator/validator.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
/* harmony import */ var _rules__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./rules */ "./src/js/validator/rules/index.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }


var FormieValidator = /*#__PURE__*/function () {
  function FormieValidator(form, config) {
    var _this = this;
    _classCallCheck(this, FormieValidator);
    this.form = form;
    this.errors = [];
    this.validators = {};
    this.boundListeners = false;
    this.config = _objectSpread({
      live: false,
      fieldContainerErrorClass: 'fui-error',
      inputErrorClass: 'fui-error',
      messagesClass: 'fui-errors',
      messageClass: 'fui-error-message',
      fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea',
      patterns: {
        // eslint-disable-next-line
        email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
        url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
        number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
        color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
        date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
        time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
        month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
      }
    }, config);

    // Register core validators
    Object.entries(_rules__WEBPACK_IMPORTED_MODULE_1__["default"]).forEach(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        validatorName = _ref2[0],
        validator = _ref2[1];
      _this.addValidator(validatorName, validator.rule, validator.message);
    });
    this.init();
  }
  _createClass(FormieValidator, [{
    key: "init",
    value: function init() {
      this.form.setAttribute('novalidate', true);
      if (this.config.live) {
        this.addEventListeners();
      }
      this.emitEvent(document, 'formieValidatorInitialized');
    }
  }, {
    key: "inputs",
    value: function inputs() {
      var inputOrSelector = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      // If this was a single form input, return straight away
      if (inputOrSelector instanceof HTMLElement && inputOrSelector.getAttribute('type')) {
        return [inputOrSelector];
      }

      // Otherwise, it's a selector to a regular DOM element. Find all inputs within that.
      if (!inputOrSelector) {
        inputOrSelector = this.form;
      }
      return inputOrSelector.querySelectorAll(this.config.fieldsSelector);
    }
  }, {
    key: "validate",
    value: function validate() {
      var _this2 = this;
      var inputOrSelector = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
      this.errors = [];
      this.inputs(inputOrSelector).forEach(function (input) {
        var errorShown = false;
        if (!_this2.isVisible(input)) {
          return;
        }
        _this2.removeError(input);
        Object.entries(_this2.validators).forEach(function (_ref3) {
          var _ref4 = _slicedToArray(_ref3, 2),
            validatorName = _ref4[0],
            validatorConfig = _ref4[1];
          var opts = _this2.getValidatorCallbackOptions(input);
          var isValid = validatorConfig.validate(opts);
          if (!isValid) {
            // Don't show multiple errors, but record them
            if (!errorShown) {
              var errorMessage = _this2.getErrorMessage(input, validatorName, validatorConfig);
              _this2.showError(input, validatorName, errorMessage);
            }
            _this2.errors.push({
              input: input,
              validator: validatorName,
              result: isValid
            });
            errorShown = true;
          }
        });
      });

      // Even if set to non-live, add event listeners to make the form have live validation, so that errors
      // are updated in real-time after the user hits submit. This is just good UX.
      if (!this.config.live) {
        this.addEventListeners();
      }
    }
  }, {
    key: "removeAllErrors",
    value: function removeAllErrors() {
      var _this3 = this;
      this.inputs().forEach(function (input) {
        _this3.removeError(input);
      });
    }
  }, {
    key: "removeError",
    value: function removeError(input) {
      var fieldContainer = input.closest('[data-field-handle]');
      if (!fieldContainer) {
        return;
      }
      var errorMessages = fieldContainer.querySelector('[data-field-error-messages]');
      if (errorMessages) {
        errorMessages.remove();
      }
      if (this.config.fieldContainerErrorClass) {
        fieldContainer.classList.remove(this.config.fieldContainerErrorClass);
      }
      if (this.config.inputErrorClass) {
        input.classList.remove(this.config.inputErrorClass);
      }
      this.emitEvent(input, 'formieValidatorClearError');
    }
  }, {
    key: "showError",
    value: function showError(input, validatorName, errorMessage) {
      var fieldContainer = input.closest('[data-field-handle]');
      if (!fieldContainer) {
        return;
      }
      var errorMessages = fieldContainer.querySelector('[data-field-error-messages]');
      if (!errorMessages) {
        errorMessages = document.createElement('div');
        errorMessages.setAttribute('data-field-error-messages', '');
        if (this.config.messagesClass) {
          errorMessages.classList.add(this.config.messagesClass);
        }
        fieldContainer.appendChild(errorMessages);
      }

      // Find or create error element
      var errorElement = fieldContainer.querySelector("[data-field-error-message-".concat(validatorName, "]"));
      if (!errorElement) {
        var _errorElement = document.createElement('div');
        _errorElement.setAttribute('data-field-error-message', '');
        _errorElement.setAttribute("data-field-error-message-".concat(validatorName), '');
        if (this.config.messageClass) {
          _errorElement.classList.add(this.config.messageClass);
        }
        _errorElement.textContent = errorMessage;

        // Append error element to errorMessages div
        errorMessages.appendChild(_errorElement);
      }

      // Add error classes to field and field container
      if (this.config.fieldContainerErrorClass) {
        fieldContainer.classList.add(this.config.fieldContainerErrorClass);
      }
      if (this.config.inputErrorClass) {
        input.classList.add(this.config.inputErrorClass);
      }
      this.emitEvent(input, 'formieValidatorShowError', {
        validatorName: validatorName,
        errorMessage: errorMessage
      });
    }
  }, {
    key: "getValidatorCallbackOptions",
    value: function getValidatorCallbackOptions(input) {
      var _fieldContainer$query,
        _fieldContainer$query2,
        _this4 = this;
      var fieldContainer = input.closest('[data-field-handle]');

      // The label is pretty common, so add that in
      var label = (_fieldContainer$query = fieldContainer === null || fieldContainer === void 0 || (_fieldContainer$query2 = fieldContainer.querySelector('[data-field-label]')) === null || _fieldContainer$query2 === void 0 || (_fieldContainer$query2 = _fieldContainer$query2.childNodes[0].textContent) === null || _fieldContainer$query2 === void 0 ? void 0 : _fieldContainer$query2.trim()) !== null && _fieldContainer$query !== void 0 ? _fieldContainer$query : '';
      return {
        t: _utils_utils__WEBPACK_IMPORTED_MODULE_0__.t,
        input: input,
        label: label,
        field: fieldContainer,
        config: this.config,
        getRule: function getRule(rule) {
          return _this4.getRule(fieldContainer, rule);
        }
      };
    }
  }, {
    key: "getErrorMessage",
    value: function getErrorMessage(input, validatorName, validator) {
      var opts = this.getValidatorCallbackOptions(input);
      var errorMessage = typeof validator.errorMessage === 'function' ? validator.errorMessage(opts) : validator.errorMessage;
      return errorMessage !== null && errorMessage !== void 0 ? errorMessage : (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.t)('{attribute} is invalid.', {
        attribute: opts.label
      });
    }
  }, {
    key: "getErrors",
    value: function getErrors() {
      return this.errors;
    }
  }, {
    key: "getRule",
    value: function getRule(field, rule) {
      if (field) {
        var ruleString = field.getAttribute('data-validation');
        if (ruleString) {
          var _rules = this.parseValidationRules(ruleString);
          if (_rules[rule]) {
            return _rules[rule];
          }
        }
      }
      return false;
    }
  }, {
    key: "parseValidationRules",
    value: function parseValidationRules(ruleString) {
      var rules = {};
      var parts = ruleString.split('|');
      parts.forEach(function (part) {
        var _part$split = part.split(':'),
          _part$split2 = _slicedToArray(_part$split, 2),
          key = _part$split2[0],
          value = _part$split2[1];
        rules[key] = value !== undefined ? value : true;
      });
      return rules;
    }
  }, {
    key: "destroy",
    value: function destroy() {
      this.removeEventListeners();

      // Remove novalidate attribute
      this.form.removeAttribute('novalidate');
      this.emitEvent(document, 'formieValidatorDestroyed');
    }
  }, {
    key: "isVisible",
    value: function isVisible(element) {
      return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }
  }, {
    key: "blurHandler",
    value: function blurHandler(e) {
      // Formie will have it's own events, so ignore those
      // Only run if the field is in a form to be validated
      if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
        return;
      }

      // Special-case for file field, blurs as soon as the selector kicks in
      if (e.target.type === 'file') {
        return;
      }

      // Don't trigger click event handling for checkbox/radio. We should use the change.
      if (e.target.type === 'checkbox' || e.target.type === 'radio') {
        return;
      }

      // Validate the field
      this.validate(e.target);
    }
  }, {
    key: "changeHandler",
    value: function changeHandler(e) {
      // Formie will have it's own events, so ignore those
      // Only run if the field is in a form to be validated
      if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
        return;
      }

      // Only handle change events for some fields
      if (e.target.type !== 'file' && e.target.type !== 'checkbox' && e.target.type !== 'radio') {
        return;
      }

      // Validate the field
      this.validate(e.target);
    }
  }, {
    key: "inputHandler",
    value: function inputHandler(e) {
      // Formie will have it's own events, so ignore those
      // Only run if the field is in a form to be validated
      if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
        return;
      }

      // Only run on fields with errors
      if (!e.target.classList.contains(this.config.inputErrorClass)) {
        return;
      }

      // // Don't trigger click event handling for checkbox/radio. We should use the change.
      if (e.target.type === 'checkbox' || e.target.type === 'radio') {
        return;
      }

      // Validate the field
      this.validate(e.target);
    }
  }, {
    key: "clickHandler",
    value: function clickHandler(e) {
      // Formie will have it's own events, so ignore those
      // Only run if the field is in a form to be validated
      if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
        return;
      }

      // Only run on fields with errors
      if (!e.target.classList.contains(this.config.inputErrorClass)) {
        return;
      }

      // Don't trigger click event handling for checkbox/radio. We should use the change.
      if (e.target.type === 'checkbox' || e.target.type === 'radio') {
        return;
      }

      // Validate the field
      this.validate(e.target);
    }
  }, {
    key: "addEventListeners",
    value: function addEventListeners() {
      if (!this.boundListeners) {
        this.form.addEventListener('blur', this.blurHandler.bind(this), true);
        this.form.addEventListener('change', this.changeHandler.bind(this), false);
        this.form.addEventListener('input', this.inputHandler.bind(this), false);
        this.form.addEventListener('click', this.clickHandler.bind(this), false);
        this.boundListeners = true;
      }
    }
  }, {
    key: "removeEventListeners",
    value: function removeEventListeners() {
      this.form.removeEventListener('blur', this.blurHandler, true);
      this.form.removeEventListener('change', this.changeHandler, false);
      this.form.removeEventListener('input', this.inputHandler, false);
      this.form.removeEventListener('click', this.clickHandler, false);
    }
  }, {
    key: "emitEvent",
    value: function emitEvent(el, type, details) {
      var event = new CustomEvent(type, {
        bubbles: true,
        detail: details || {}
      });
      el.dispatchEvent(event);
    }
  }, {
    key: "addValidator",
    value: function addValidator(name, validatorFunction, errorMessage) {
      this.validators[name] = {
        validate: validatorFunction,
        errorMessage: errorMessage
      };
    }
  }]);
  return FormieValidator;
}();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (FormieValidator);

/***/ }),

/***/ "./src/scss/formie-base.scss":
/*!***********************************!*\
  !*** ./src/scss/formie-base.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/formie-theme.scss":
/*!************************************!*\
  !*** ./src/scss/formie-theme.scss ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/fields/phone-country.scss":
/*!********************************************!*\
  !*** ./src/scss/fields/phone-country.scss ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/fields/stripe.scss":
/*!*************************************!*\
  !*** ./src/scss/fields/stripe.scss ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/scss/fields/tags.scss":
/*!***********************************!*\
  !*** ./src/scss/fields/tags.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "../../../../node_modules/formdata-polyfill/formdata.min.js":
/*!******************************************************************!*\
  !*** ../../../../node_modules/formdata-polyfill/formdata.min.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

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
/******/ 			"css/fields/stripe": 0,
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
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/js/formie.js")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/formie-base.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/formie-theme.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/fields/phone-country.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/fields/stripe.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/fields/phone-country","css/fields/tags","css/fields/stripe","css/formie-theme","css/formie-base"], () => (__webpack_require__("./src/scss/fields/tags.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;