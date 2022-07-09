/*
 * ATTENTION: An "eval-source-map" devtool has been used.
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file with attached SourceMaps in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/captchas/javascript.js":
/*!***************************************!*\
  !*** ./src/js/captchas/javascript.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieJSCaptcha\": () => (/* binding */ FormieJSCaptcha)\n/* harmony export */ });\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nvar FormieJSCaptcha = /*#__PURE__*/_createClass(function FormieJSCaptcha() {\n  var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n  _classCallCheck(this, FormieJSCaptcha);\n\n  this.$form = settings.$form;\n  this.form = this.$form.form;\n  this.sessionKey = settings.sessionKey;\n  this.$placeholder = this.$form.querySelector('[data-jscaptcha-placeholder]');\n\n  if (!this.$placeholder) {\n    console.error('Unable to find JavaScript Captcha placeholder for [data-jscaptcha-placeholder]');\n    return;\n  } // Find the value to add, as appended to the page\n\n\n  this.value = window[\"Formie\".concat(this.sessionKey)];\n\n  if (!this.value) {\n    console.error(\"Unable to find JavaScript Captcha value for Formie\".concat(this.sessionKey));\n    return;\n  }\n\n  var $input = document.createElement('input');\n  $input.setAttribute('type', 'hidden');\n  $input.setAttribute('name', this.sessionKey);\n  $input.value = this.value;\n  this.$placeholder.appendChild($input);\n});\nwindow.FormieJSCaptcha = FormieJSCaptcha;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvY2FwdGNoYXMvamF2YXNjcmlwdC5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7O0FBQU8sSUFBTUEsZUFBYiw2QkFDSSwyQkFBMkI7RUFBQSxJQUFmQyxRQUFlLHVFQUFKLEVBQUk7O0VBQUE7O0VBQ3ZCLEtBQUtDLEtBQUwsR0FBYUQsUUFBUSxDQUFDQyxLQUF0QjtFQUNBLEtBQUtDLElBQUwsR0FBWSxLQUFLRCxLQUFMLENBQVdDLElBQXZCO0VBQ0EsS0FBS0MsVUFBTCxHQUFrQkgsUUFBUSxDQUFDRyxVQUEzQjtFQUVBLEtBQUtDLFlBQUwsR0FBb0IsS0FBS0gsS0FBTCxDQUFXSSxhQUFYLENBQXlCLDhCQUF6QixDQUFwQjs7RUFFQSxJQUFJLENBQUMsS0FBS0QsWUFBVixFQUF3QjtJQUNwQkUsT0FBTyxDQUFDQyxLQUFSLENBQWMsZ0ZBQWQ7SUFFQTtFQUNILENBWHNCLENBYXZCOzs7RUFDQSxLQUFLQyxLQUFMLEdBQWFDLE1BQU0saUJBQVUsS0FBS04sVUFBZixFQUFuQjs7RUFFQSxJQUFJLENBQUMsS0FBS0ssS0FBVixFQUFpQjtJQUNiRixPQUFPLENBQUNDLEtBQVIsNkRBQW1FLEtBQUtKLFVBQXhFO0lBRUE7RUFDSDs7RUFFRCxJQUFNTyxNQUFNLEdBQUdDLFFBQVEsQ0FBQ0MsYUFBVCxDQUF1QixPQUF2QixDQUFmO0VBQ0FGLE1BQU0sQ0FBQ0csWUFBUCxDQUFvQixNQUFwQixFQUE0QixRQUE1QjtFQUNBSCxNQUFNLENBQUNHLFlBQVAsQ0FBb0IsTUFBcEIsRUFBNEIsS0FBS1YsVUFBakM7RUFDQU8sTUFBTSxDQUFDRixLQUFQLEdBQWUsS0FBS0EsS0FBcEI7RUFFQSxLQUFLSixZQUFMLENBQWtCVSxXQUFsQixDQUE4QkosTUFBOUI7QUFDSCxDQTdCTDtBQWdDQUQsTUFBTSxDQUFDVixlQUFQLEdBQXlCQSxlQUF6QiIsInNvdXJjZXMiOlsid2VicGFjazovLy8uL3NyYy9qcy9jYXB0Y2hhcy9qYXZhc2NyaXB0LmpzPzEwZWMiXSwic291cmNlc0NvbnRlbnQiOlsiZXhwb3J0IGNsYXNzIEZvcm1pZUpTQ2FwdGNoYSB7XG4gICAgY29uc3RydWN0b3Ioc2V0dGluZ3MgPSB7fSkge1xuICAgICAgICB0aGlzLiRmb3JtID0gc2V0dGluZ3MuJGZvcm07XG4gICAgICAgIHRoaXMuZm9ybSA9IHRoaXMuJGZvcm0uZm9ybTtcbiAgICAgICAgdGhpcy5zZXNzaW9uS2V5ID0gc2V0dGluZ3Muc2Vzc2lvbktleTtcblxuICAgICAgICB0aGlzLiRwbGFjZWhvbGRlciA9IHRoaXMuJGZvcm0ucXVlcnlTZWxlY3RvcignW2RhdGEtanNjYXB0Y2hhLXBsYWNlaG9sZGVyXScpO1xuXG4gICAgICAgIGlmICghdGhpcy4kcGxhY2Vob2xkZXIpIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIEphdmFTY3JpcHQgQ2FwdGNoYSBwbGFjZWhvbGRlciBmb3IgW2RhdGEtanNjYXB0Y2hhLXBsYWNlaG9sZGVyXScpO1xuXG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBGaW5kIHRoZSB2YWx1ZSB0byBhZGQsIGFzIGFwcGVuZGVkIHRvIHRoZSBwYWdlXG4gICAgICAgIHRoaXMudmFsdWUgPSB3aW5kb3dbYEZvcm1pZSR7dGhpcy5zZXNzaW9uS2V5fWBdO1xuXG4gICAgICAgIGlmICghdGhpcy52YWx1ZSkge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcihgVW5hYmxlIHRvIGZpbmQgSmF2YVNjcmlwdCBDYXB0Y2hhIHZhbHVlIGZvciBGb3JtaWUke3RoaXMuc2Vzc2lvbktleX1gKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgJGlucHV0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW5wdXQnKTtcbiAgICAgICAgJGlucHV0LnNldEF0dHJpYnV0ZSgndHlwZScsICdoaWRkZW4nKTtcbiAgICAgICAgJGlucHV0LnNldEF0dHJpYnV0ZSgnbmFtZScsIHRoaXMuc2Vzc2lvbktleSk7XG4gICAgICAgICRpbnB1dC52YWx1ZSA9IHRoaXMudmFsdWU7XG5cbiAgICAgICAgdGhpcy4kcGxhY2Vob2xkZXIuYXBwZW5kQ2hpbGQoJGlucHV0KTtcbiAgICB9XG59XG5cbndpbmRvdy5Gb3JtaWVKU0NhcHRjaGEgPSBGb3JtaWVKU0NhcHRjaGE7XG4iXSwibmFtZXMiOlsiRm9ybWllSlNDYXB0Y2hhIiwic2V0dGluZ3MiLCIkZm9ybSIsImZvcm0iLCJzZXNzaW9uS2V5IiwiJHBsYWNlaG9sZGVyIiwicXVlcnlTZWxlY3RvciIsImNvbnNvbGUiLCJlcnJvciIsInZhbHVlIiwid2luZG93IiwiJGlucHV0IiwiZG9jdW1lbnQiLCJjcmVhdGVFbGVtZW50Iiwic2V0QXR0cmlidXRlIiwiYXBwZW5kQ2hpbGQiXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/captchas/javascript.js\n");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
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
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/js/captchas/javascript.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;