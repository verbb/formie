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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieJSCaptcha\": () => (/* binding */ FormieJSCaptcha)\n/* harmony export */ });\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nvar FormieJSCaptcha = /*#__PURE__*/_createClass(function FormieJSCaptcha() {\n  var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n  _classCallCheck(this, FormieJSCaptcha);\n\n  this.formId = settings.formId;\n  this.sessionKey = settings.sessionKey;\n  this.$form = document.querySelector(\"#\".concat(this.formId));\n\n  if (!this.$form) {\n    console.error(\"Unable to find form #\".concat(this.formId));\n    return;\n  }\n\n  this.$placeholder = this.$form.querySelector('[data-jscaptcha-placeholder]');\n\n  if (!this.$placeholder) {\n    console.error(\"Unable to find JavaScript Captcha placeholder for #\".concat(this.formId));\n    return;\n  } // Find the value to add, as appended to the page\n\n\n  this.value = window[\"Formie\".concat(this.sessionKey)];\n\n  if (!this.value) {\n    console.error(\"Unable to find JavaScript Captcha value for Formie\".concat(this.sessionKey));\n    return;\n  }\n\n  var $input = document.createElement('input');\n  $input.setAttribute('type', 'hidden');\n  $input.setAttribute('name', this.sessionKey);\n  $input.value = this.value;\n  this.$placeholder.appendChild($input);\n});\nwindow.FormieJSCaptcha = FormieJSCaptcha;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvY2FwdGNoYXMvamF2YXNjcmlwdC5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7O0FBQU8sSUFBTUEsZUFBYiw2QkFDSSwyQkFBMkI7RUFBQSxJQUFmQyxRQUFlLHVFQUFKLEVBQUk7O0VBQUE7O0VBQ3ZCLEtBQUtDLE1BQUwsR0FBY0QsUUFBUSxDQUFDQyxNQUF2QjtFQUNBLEtBQUtDLFVBQUwsR0FBa0JGLFFBQVEsQ0FBQ0UsVUFBM0I7RUFFQSxLQUFLQyxLQUFMLEdBQWFDLFFBQVEsQ0FBQ0MsYUFBVCxZQUEyQixLQUFLSixNQUFoQyxFQUFiOztFQUVBLElBQUksQ0FBQyxLQUFLRSxLQUFWLEVBQWlCO0lBQ2JHLE9BQU8sQ0FBQ0MsS0FBUixnQ0FBc0MsS0FBS04sTUFBM0M7SUFFQTtFQUNIOztFQUVELEtBQUtPLFlBQUwsR0FBb0IsS0FBS0wsS0FBTCxDQUFXRSxhQUFYLENBQXlCLDhCQUF6QixDQUFwQjs7RUFFQSxJQUFJLENBQUMsS0FBS0csWUFBVixFQUF3QjtJQUNwQkYsT0FBTyxDQUFDQyxLQUFSLDhEQUFvRSxLQUFLTixNQUF6RTtJQUVBO0VBQ0gsQ0FsQnNCLENBb0J2Qjs7O0VBQ0EsS0FBS1EsS0FBTCxHQUFhQyxNQUFNLGlCQUFVLEtBQUtSLFVBQWYsRUFBbkI7O0VBRUEsSUFBSSxDQUFDLEtBQUtPLEtBQVYsRUFBaUI7SUFDYkgsT0FBTyxDQUFDQyxLQUFSLDZEQUFtRSxLQUFLTCxVQUF4RTtJQUVBO0VBQ0g7O0VBRUQsSUFBTVMsTUFBTSxHQUFHUCxRQUFRLENBQUNRLGFBQVQsQ0FBdUIsT0FBdkIsQ0FBZjtFQUNBRCxNQUFNLENBQUNFLFlBQVAsQ0FBb0IsTUFBcEIsRUFBNEIsUUFBNUI7RUFDQUYsTUFBTSxDQUFDRSxZQUFQLENBQW9CLE1BQXBCLEVBQTRCLEtBQUtYLFVBQWpDO0VBQ0FTLE1BQU0sQ0FBQ0YsS0FBUCxHQUFlLEtBQUtBLEtBQXBCO0VBRUEsS0FBS0QsWUFBTCxDQUFrQk0sV0FBbEIsQ0FBOEJILE1BQTlCO0FBQ0gsQ0FwQ0w7QUF1Q0FELE1BQU0sQ0FBQ1gsZUFBUCxHQUF5QkEsZUFBekIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvY2FwdGNoYXMvamF2YXNjcmlwdC5qcz8xMGVjIl0sInNvdXJjZXNDb250ZW50IjpbImV4cG9ydCBjbGFzcyBGb3JtaWVKU0NhcHRjaGEge1xuICAgIGNvbnN0cnVjdG9yKHNldHRpbmdzID0ge30pIHtcbiAgICAgICAgdGhpcy5mb3JtSWQgPSBzZXR0aW5ncy5mb3JtSWQ7XG4gICAgICAgIHRoaXMuc2Vzc2lvbktleSA9IHNldHRpbmdzLnNlc3Npb25LZXk7XG5cbiAgICAgICAgdGhpcy4kZm9ybSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoYCMke3RoaXMuZm9ybUlkfWApO1xuXG4gICAgICAgIGlmICghdGhpcy4kZm9ybSkge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcihgVW5hYmxlIHRvIGZpbmQgZm9ybSAjJHt0aGlzLmZvcm1JZH1gKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy4kcGxhY2Vob2xkZXIgPSB0aGlzLiRmb3JtLnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWpzY2FwdGNoYS1wbGFjZWhvbGRlcl0nKTtcblxuICAgICAgICBpZiAoIXRoaXMuJHBsYWNlaG9sZGVyKSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKGBVbmFibGUgdG8gZmluZCBKYXZhU2NyaXB0IENhcHRjaGEgcGxhY2Vob2xkZXIgZm9yICMke3RoaXMuZm9ybUlkfWApO1xuXG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBGaW5kIHRoZSB2YWx1ZSB0byBhZGQsIGFzIGFwcGVuZGVkIHRvIHRoZSBwYWdlXG4gICAgICAgIHRoaXMudmFsdWUgPSB3aW5kb3dbYEZvcm1pZSR7dGhpcy5zZXNzaW9uS2V5fWBdO1xuXG4gICAgICAgIGlmICghdGhpcy52YWx1ZSkge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcihgVW5hYmxlIHRvIGZpbmQgSmF2YVNjcmlwdCBDYXB0Y2hhIHZhbHVlIGZvciBGb3JtaWUke3RoaXMuc2Vzc2lvbktleX1gKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgJGlucHV0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnaW5wdXQnKTtcbiAgICAgICAgJGlucHV0LnNldEF0dHJpYnV0ZSgndHlwZScsICdoaWRkZW4nKTtcbiAgICAgICAgJGlucHV0LnNldEF0dHJpYnV0ZSgnbmFtZScsIHRoaXMuc2Vzc2lvbktleSk7XG4gICAgICAgICRpbnB1dC52YWx1ZSA9IHRoaXMudmFsdWU7XG5cbiAgICAgICAgdGhpcy4kcGxhY2Vob2xkZXIuYXBwZW5kQ2hpbGQoJGlucHV0KTtcbiAgICB9XG59XG5cbndpbmRvdy5Gb3JtaWVKU0NhcHRjaGEgPSBGb3JtaWVKU0NhcHRjaGE7XG4iXSwibmFtZXMiOlsiRm9ybWllSlNDYXB0Y2hhIiwic2V0dGluZ3MiLCJmb3JtSWQiLCJzZXNzaW9uS2V5IiwiJGZvcm0iLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3IiLCJjb25zb2xlIiwiZXJyb3IiLCIkcGxhY2Vob2xkZXIiLCJ2YWx1ZSIsIndpbmRvdyIsIiRpbnB1dCIsImNyZWF0ZUVsZW1lbnQiLCJzZXRBdHRyaWJ1dGUiLCJhcHBlbmRDaGlsZCJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/captchas/javascript.js\n");

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