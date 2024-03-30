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
/* harmony export */   "FormieAddressProvider": () => (/* binding */ FormieAddressProvider)
/* harmony export */ });
/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ "./src/js/utils/utils.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


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
/*!****************************************************!*\
  !*** ./src/js/address-providers/google-address.js ***!
  \****************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieGoogleAddress": () => (/* binding */ FormieGoogleAddress)
/* harmony export */ });
/* harmony import */ var _address_provider__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./address-provider */ "./src/js/address-providers/address-provider.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]; if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

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


var FormieGoogleAddress = /*#__PURE__*/function (_FormieAddressProvide) {
  _inherits(FormieGoogleAddress, _FormieAddressProvide);

  var _super = _createSuper(FormieGoogleAddress);

  function FormieGoogleAddress() {
    var _this;

    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieGoogleAddress);

    _this = _super.call(this, settings);
    _this.$form = settings.$form;
    _this.form = _this.$form.form;
    _this.$field = settings.$field;
    _this.$input = _this.$field.querySelector('[data-autocomplete]');
    _this.scriptId = 'FORMIE_GOOGLE_ADDRESS_SCRIPT';
    _this.appId = settings.appId;
    _this.apiKey = settings.apiKey;
    _this.geocodingApiKey = settings.geocodingApiKey || settings.apiKey;
    _this.options = settings.options; // Keep track of how many times we try to load.

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
      } // Ensure that Google places is ready


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

        _this3.setAddressValues(place.address_components); // Allow events to modify behaviour


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
      var componentMap = this.componentMap(); // Sort out the data from Google so its easier to manage

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
      }; // Use our own proxy to get around lack of support from Google Places and restricted API keys


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