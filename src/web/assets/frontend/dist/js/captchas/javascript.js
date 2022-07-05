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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieJSCaptcha\": () => (/* binding */ FormieJSCaptcha)\n/* harmony export */ });\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nvar FormieJSCaptcha = /*#__PURE__*/_createClass(function FormieJSCaptcha() {\n  var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n  _classCallCheck(this, FormieJSCaptcha);\n\n  this.formId = settings.formId;\n  this.sessionKey = settings.sessionKey;\n  this.$form = document.querySelector('#' + this.formId);\n\n  if (!this.$form) {\n    console.error('Unable to find form #' + this.formId);\n    return;\n  }\n\n  this.$placeholder = this.$form.querySelector('[data-jscaptcha-placeholder]');\n\n  if (!this.$placeholder) {\n    console.error('Unable to find JavaScript Captcha placeholder for #' + this.formId);\n    return;\n  } // Find the value to add, as appended to the page\n\n\n  this.value = window['Formie' + this.sessionKey];\n\n  if (!this.value) {\n    console.error('Unable to find JavaScript Captcha value for Formie' + this.sessionKey);\n    return;\n  }\n\n  var $input = document.createElement('input');\n  $input.setAttribute('type', 'hidden');\n  $input.setAttribute('name', this.sessionKey);\n  $input.value = this.value;\n  this.$placeholder.appendChild($input);\n});\nwindow.FormieJSCaptcha = FormieJSCaptcha;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvY2FwdGNoYXMvamF2YXNjcmlwdC5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7O0FBQU8sSUFBTUEsZUFBYiw2QkFDSSwyQkFBMkI7RUFBQSxJQUFmQyxRQUFlLHVFQUFKLEVBQUk7O0VBQUE7O0VBQ3ZCLEtBQUtDLE1BQUwsR0FBY0QsUUFBUSxDQUFDQyxNQUF2QjtFQUNBLEtBQUtDLFVBQUwsR0FBa0JGLFFBQVEsQ0FBQ0UsVUFBM0I7RUFFQSxLQUFLQyxLQUFMLEdBQWFDLFFBQVEsQ0FBQ0MsYUFBVCxDQUF1QixNQUFNLEtBQUtKLE1BQWxDLENBQWI7O0VBRUEsSUFBSSxDQUFDLEtBQUtFLEtBQVYsRUFBaUI7SUFDYkcsT0FBTyxDQUFDQyxLQUFSLENBQWMsMEJBQTBCLEtBQUtOLE1BQTdDO0lBRUE7RUFDSDs7RUFFRCxLQUFLTyxZQUFMLEdBQW9CLEtBQUtMLEtBQUwsQ0FBV0UsYUFBWCxDQUF5Qiw4QkFBekIsQ0FBcEI7O0VBRUEsSUFBSSxDQUFDLEtBQUtHLFlBQVYsRUFBd0I7SUFDcEJGLE9BQU8sQ0FBQ0MsS0FBUixDQUFjLHdEQUF3RCxLQUFLTixNQUEzRTtJQUVBO0VBQ0gsQ0FsQnNCLENBb0J2Qjs7O0VBQ0EsS0FBS1EsS0FBTCxHQUFhQyxNQUFNLENBQUMsV0FBVyxLQUFLUixVQUFqQixDQUFuQjs7RUFFQSxJQUFJLENBQUMsS0FBS08sS0FBVixFQUFpQjtJQUNiSCxPQUFPLENBQUNDLEtBQVIsQ0FBYyx1REFBdUQsS0FBS0wsVUFBMUU7SUFFQTtFQUNIOztFQUVELElBQUlTLE1BQU0sR0FBR1AsUUFBUSxDQUFDUSxhQUFULENBQXVCLE9BQXZCLENBQWI7RUFDQUQsTUFBTSxDQUFDRSxZQUFQLENBQW9CLE1BQXBCLEVBQTRCLFFBQTVCO0VBQ0FGLE1BQU0sQ0FBQ0UsWUFBUCxDQUFvQixNQUFwQixFQUE0QixLQUFLWCxVQUFqQztFQUNBUyxNQUFNLENBQUNGLEtBQVAsR0FBZSxLQUFLQSxLQUFwQjtFQUVBLEtBQUtELFlBQUwsQ0FBa0JNLFdBQWxCLENBQThCSCxNQUE5QjtBQUNILENBcENMO0FBdUNBRCxNQUFNLENBQUNYLGVBQVAsR0FBeUJBLGVBQXpCIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vc3JjL2pzL2NhcHRjaGFzL2phdmFzY3JpcHQuanM/MTBlYyJdLCJzb3VyY2VzQ29udGVudCI6WyJleHBvcnQgY2xhc3MgRm9ybWllSlNDYXB0Y2hhIHtcbiAgICBjb25zdHJ1Y3RvcihzZXR0aW5ncyA9IHt9KSB7XG4gICAgICAgIHRoaXMuZm9ybUlkID0gc2V0dGluZ3MuZm9ybUlkO1xuICAgICAgICB0aGlzLnNlc3Npb25LZXkgPSBzZXR0aW5ncy5zZXNzaW9uS2V5O1xuXG4gICAgICAgIHRoaXMuJGZvcm0gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCcjJyArIHRoaXMuZm9ybUlkKTtcblxuICAgICAgICBpZiAoIXRoaXMuJGZvcm0pIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIGZvcm0gIycgKyB0aGlzLmZvcm1JZCk7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuJHBsYWNlaG9sZGVyID0gdGhpcy4kZm9ybS5xdWVyeVNlbGVjdG9yKCdbZGF0YS1qc2NhcHRjaGEtcGxhY2Vob2xkZXJdJyk7XG5cbiAgICAgICAgaWYgKCF0aGlzLiRwbGFjZWhvbGRlcikge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcignVW5hYmxlIHRvIGZpbmQgSmF2YVNjcmlwdCBDYXB0Y2hhIHBsYWNlaG9sZGVyIGZvciAjJyArIHRoaXMuZm9ybUlkKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gRmluZCB0aGUgdmFsdWUgdG8gYWRkLCBhcyBhcHBlbmRlZCB0byB0aGUgcGFnZVxuICAgICAgICB0aGlzLnZhbHVlID0gd2luZG93WydGb3JtaWUnICsgdGhpcy5zZXNzaW9uS2V5XTtcblxuICAgICAgICBpZiAoIXRoaXMudmFsdWUpIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIEphdmFTY3JpcHQgQ2FwdGNoYSB2YWx1ZSBmb3IgRm9ybWllJyArIHRoaXMuc2Vzc2lvbktleSk7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciAkaW5wdXQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpbnB1dCcpO1xuICAgICAgICAkaW5wdXQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ2hpZGRlbicpO1xuICAgICAgICAkaW5wdXQuc2V0QXR0cmlidXRlKCduYW1lJywgdGhpcy5zZXNzaW9uS2V5KTtcbiAgICAgICAgJGlucHV0LnZhbHVlID0gdGhpcy52YWx1ZTtcblxuICAgICAgICB0aGlzLiRwbGFjZWhvbGRlci5hcHBlbmRDaGlsZCgkaW5wdXQpO1xuICAgIH1cbn1cblxud2luZG93LkZvcm1pZUpTQ2FwdGNoYSA9IEZvcm1pZUpTQ2FwdGNoYTtcbiJdLCJuYW1lcyI6WyJGb3JtaWVKU0NhcHRjaGEiLCJzZXR0aW5ncyIsImZvcm1JZCIsInNlc3Npb25LZXkiLCIkZm9ybSIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsImNvbnNvbGUiLCJlcnJvciIsIiRwbGFjZWhvbGRlciIsInZhbHVlIiwid2luZG93IiwiJGlucHV0IiwiY3JlYXRlRWxlbWVudCIsInNldEF0dHJpYnV0ZSIsImFwcGVuZENoaWxkIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/captchas/javascript.js\n");

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