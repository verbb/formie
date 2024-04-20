/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/address-providers/address-provider.js":
/*!******************************************************!*\
  !*** ./src/js/address-providers/address-provider.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieAddressProvider: () => (/* binding */ FormieAddressProvider)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : String(i); }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var FormieAddressProvider = /*#__PURE__*/function () {
  function FormieAddressProvider() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieAddressProvider);
    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field;
    this.$locationBtn = this.$field.querySelector('[data-fui-address-location-btn]');
    this.loadingClass = this.form.getClasses('loading');
    this.initLocationBtn();
  }
  _createClass(FormieAddressProvider, [{
    key: "initLocationBtn",
    value: function initLocationBtn() {
      var _this = this;
      if (!this.$locationBtn) {
        return;
      }
      this.form.addEventListener(this.$locationBtn, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('click'), function (e) {
        e.preventDefault();
        _this.onStartFetchLocation();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(function (position) {
            _this.onCurrentLocation(position);
          }, function (error) {
            console.log("Unable to fetch location ".concat(error.code, "."));
            _this.onEndFetchLocation();
          }, {
            enableHighAccuracy: true
          });
        } else {
          console.log('Browser does not support geolocation.');
          _this.onEndFetchLocation();
        }
      });
    }
  }, {
    key: "onCurrentLocation",
    value: function onCurrentLocation(position) {
      this.onEndFetchLocation();
    }
  }, {
    key: "onStartFetchLocation",
    value: function onStartFetchLocation() {
      this.$locationBtn.classList.add(this.loadingClass);
      this.$locationBtn.setAttribute('aria-disabled', true);
    }
  }, {
    key: "onEndFetchLocation",
    value: function onEndFetchLocation() {
      this.$locationBtn.classList.remove(this.loadingClass);
      this.$locationBtn.setAttribute('aria-disabled', false);
    }
  }]);
  return FormieAddressProvider;
}();
window.FormieAddressProvider = FormieAddressProvider;

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
/*!****************************************************!*\
  !*** ./src/js/address-providers/google-address.js ***!
  \****************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FormieGoogleAddress: () => (/* binding */ FormieGoogleAddress)
/* harmony export */ });
/* harmony import */ var _address_provider__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./address-provider */ "./src/js/address-providers/address-provider.js");
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
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

var FormieGoogleAddress = /*#__PURE__*/function (_FormieAddressProvide) {
  _inherits(FormieGoogleAddress, _FormieAddressProvide);
  function FormieGoogleAddress() {
    var _this;
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _classCallCheck(this, FormieGoogleAddress);
    _this = _callSuper(this, FormieGoogleAddress, [settings]);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.$field = settings.$field;
    _this.$input = _this.$field.querySelector('[data-autocomplete]');
    _this.scriptId = 'FORMIE_GOOGLE_ADDRESS_SCRIPT';
    _this.appId = settings.appId;
    _this.apiKey = settings.apiKey;
    _this.geocodingApiKey = settings.geocodingApiKey || settings.apiKey;
    _this.options = settings.options;

    // Keep track of how many times we try to load.
    _this.retryTimes = 0;
    _this.maxRetryTimes = 150;
    _this.waitTimeout = 200;
    if (!_this.$input) {
      console.error('Unable to find input `[data-autocomplete]`.');
      return _possibleConstructorReturn(_this);
    }
    _this.initScript();
    return _this;
  }
  _createClass(FormieGoogleAddress, [{
    key: "initScript",
    value: function initScript() {
      var _this2 = this;
      // Prevent the script from loading multiple times (which throw warnings anyway)
      if (!document.getElementById(this.scriptId)) {
        var script = document.createElement('script');
        script.src = "https://maps.googleapis.com/maps/api/js?key=".concat(this.apiKey, "&libraries=places");
        script.defer = true;
        script.async = true;
        script.id = this.scriptId;
        script.onload = function () {
          // Just in case there's a small delay in initializing the scripts after loaded
          _this2.waitForLoad();
        };
        document.body.appendChild(script);
      } else {
        // Script already present, but might not be loaded yet...
        this.waitForLoad();
      }
    }
  }, {
    key: "waitForLoad",
    value: function waitForLoad() {
      // Prevent running forever
      if (this.retryTimes > this.maxRetryTimes) {
        console.error("Unable to load Google API after ".concat(this.retryTimes, " times."));
        return;
      }

      // Ensure that Google places is ready
      if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
        this.retryTimes += 1;
        setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
      } else {
        this.initAutocomplete();
      }
    }
  }, {
    key: "initAutocomplete",
    value: function initAutocomplete() {
      var _this3 = this;
      var options = _objectSpread({
        types: ['geocode']
      }, this.options);
      var autocomplete = new google.maps.places.Autocomplete(this.$input, options);
      autocomplete.setFields(['address_component']);
      autocomplete.addListener('place_changed', function () {
        var place = autocomplete.getPlace();
        if (!place.address_components) {
          // Seem to be having some issues with `address_components` being empty for units...
          return;
        }
        _this3.setAddressValues(place.address_components);

        // Allow events to modify behaviour
        var populateAddressEvent = new CustomEvent('populateAddress', {
          bubbles: true,
          detail: {
            addressProvider: _this3,
            addressComponents: place.address_components
          }
        });
        _this3.$field.dispatchEvent(populateAddressEvent);
      });
    }
  }, {
    key: "setAddressValues",
    value: function setAddressValues(address) {
      var formData = {};
      var componentMap = this.componentMap();

      // Sort out the data from Google so its easier to manage
      for (var i = 0; i < address.length; i++) {
        var _address$i$types = _slicedToArray(address[i].types, 1),
          addressType = _address$i$types[0];
        if (componentMap[addressType]) {
          formData[addressType] = address[i][componentMap[addressType]];
        }
      }
      if (formData.street_number && formData.route) {
        var street = "".concat(formData.street_number, " ").concat(formData.route);
        if (formData.subpremise) {
          street = "".concat(formData.subpremise, "/").concat(street);
        }
        this.setFieldValue('[data-address1]', street);
      }
      this.setFieldValue('[data-city]', formData.locality, formData.postal_town);
      this.setFieldValue('[data-zip]', formData.postal_code);
      this.setFieldValue('[data-state]', formData.administrative_area_level_1);
      this.setFieldValue('[data-country]', formData.country);
    }
  }, {
    key: "onCurrentLocation",
    value: function onCurrentLocation(position) {
      var _this4 = this;
      var _position$coords = position.coords,
        latitude = _position$coords.latitude,
        longitude = _position$coords.longitude;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', window.location.href, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('Cache-Control', 'no-cache');
      xhr.timeout = 10 * 1000;
      xhr.ontimeout = function () {
        console.log('The request timed out.');
      };
      xhr.onerror = function (e) {
        console.log('The request encountered a network error. Please try again.');
      };
      xhr.onload = function () {
        _this4.onEndFetchLocation();
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response && response.results && response.results[0] && response.results[0].address_components) {
              _this4.setAddressValues(response.results[0].address_components);
            }
            if (response.error_message || response.error) {
              console.log(response);
            }
          } catch (e) {
            console.log(e);
          }
        } else {
          console.log("".concat(xhr.status, ": ").concat(xhr.statusText));
        }
      };

      // Use our own proxy to get around lack of support from Google Places and restricted API keys
      var formData = new FormData();
      formData.append('action', 'formie/address/google-places-geocode');
      formData.append('latlng', "".concat(latitude, ",").concat(longitude));
      formData.append('key', this.geocodingApiKey);
      xhr.send(formData);
    }
  }, {
    key: "componentMap",
    value: function componentMap() {
      /* eslint-disable camelcase */
      return {
        subpremise: 'short_name',
        street_number: 'short_name',
        route: 'long_name',
        postal_town: 'long_name',
        locality: 'long_name',
        administrative_area_level_1: 'short_name',
        country: 'short_name',
        postal_code: 'short_name'
      };
      /* eslint-enable camelcase */
    }
  }, {
    key: "setFieldValue",
    value: function setFieldValue(selector, value, fallback) {
      if (this.$field.querySelector(selector)) {
        this.$field.querySelector(selector).value = value || fallback || '';
      }
    }
  }]);
  return FormieGoogleAddress;
}(_address_provider__WEBPACK_IMPORTED_MODULE_0__.FormieAddressProvider);
window.FormieGoogleAddress = FormieGoogleAddress;
})();

/******/ })()
;