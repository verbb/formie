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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieJSCaptcha\": () => (/* binding */ FormieJSCaptcha)\n/* harmony export */ });\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nvar FormieJSCaptcha = /*#__PURE__*/_createClass(function FormieJSCaptcha() {\n  var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n  _classCallCheck(this, FormieJSCaptcha);\n\n  this.formId = settings.formId;\n  this.sessionKey = settings.sessionKey;\n  this.$form = document.querySelector('#' + this.formId);\n\n  if (!this.$form) {\n    console.error('Unable to find form #' + this.formId);\n    return;\n  }\n\n  this.$placeholder = this.$form.querySelector('.formie-jscaptcha-placeholder');\n\n  if (!this.$placeholder) {\n    console.error('Unable to find JavaScript Captcha placeholder for #' + this.formId + '.formie-jscaptcha-placeholder');\n    return;\n  } // Find the value to add, as appended to the page\n\n\n  this.value = window['Formie' + this.sessionKey];\n\n  if (!this.value) {\n    console.error('Unable to find JavaScript Captcha value for Formie' + this.sessionKey);\n    return;\n  }\n\n  var $input = document.createElement('input');\n  $input.setAttribute('type', 'hidden');\n  $input.setAttribute('name', this.sessionKey);\n  $input.value = this.value;\n  this.$placeholder.appendChild($input);\n});\nwindow.FormieJSCaptcha = FormieJSCaptcha;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvY2FwdGNoYXMvamF2YXNjcmlwdC5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7O0FBQU8sSUFBTUEsZUFBYiw2QkFDSSwyQkFBMkI7RUFBQSxJQUFmQyxRQUFlLHVFQUFKLEVBQUk7O0VBQUE7O0VBQ3ZCLEtBQUtDLE1BQUwsR0FBY0QsUUFBUSxDQUFDQyxNQUF2QjtFQUNBLEtBQUtDLFVBQUwsR0FBa0JGLFFBQVEsQ0FBQ0UsVUFBM0I7RUFFQSxLQUFLQyxLQUFMLEdBQWFDLFFBQVEsQ0FBQ0MsYUFBVCxDQUF1QixNQUFNLEtBQUtKLE1BQWxDLENBQWI7O0VBRUEsSUFBSSxDQUFDLEtBQUtFLEtBQVYsRUFBaUI7SUFDYkcsT0FBTyxDQUFDQyxLQUFSLENBQWMsMEJBQTBCLEtBQUtOLE1BQTdDO0lBRUE7RUFDSDs7RUFFRCxLQUFLTyxZQUFMLEdBQW9CLEtBQUtMLEtBQUwsQ0FBV0UsYUFBWCxDQUF5QiwrQkFBekIsQ0FBcEI7O0VBRUEsSUFBSSxDQUFDLEtBQUtHLFlBQVYsRUFBd0I7SUFDcEJGLE9BQU8sQ0FBQ0MsS0FBUixDQUFjLHdEQUF3RCxLQUFLTixNQUE3RCxHQUFzRSwrQkFBcEY7SUFFQTtFQUNILENBbEJzQixDQW9CdkI7OztFQUNBLEtBQUtRLEtBQUwsR0FBYUMsTUFBTSxDQUFDLFdBQVcsS0FBS1IsVUFBakIsQ0FBbkI7O0VBRUEsSUFBSSxDQUFDLEtBQUtPLEtBQVYsRUFBaUI7SUFDYkgsT0FBTyxDQUFDQyxLQUFSLENBQWMsdURBQXVELEtBQUtMLFVBQTFFO0lBRUE7RUFDSDs7RUFFRCxJQUFJUyxNQUFNLEdBQUdQLFFBQVEsQ0FBQ1EsYUFBVCxDQUF1QixPQUF2QixDQUFiO0VBQ0FELE1BQU0sQ0FBQ0UsWUFBUCxDQUFvQixNQUFwQixFQUE0QixRQUE1QjtFQUNBRixNQUFNLENBQUNFLFlBQVAsQ0FBb0IsTUFBcEIsRUFBNEIsS0FBS1gsVUFBakM7RUFDQVMsTUFBTSxDQUFDRixLQUFQLEdBQWUsS0FBS0EsS0FBcEI7RUFFQSxLQUFLRCxZQUFMLENBQWtCTSxXQUFsQixDQUE4QkgsTUFBOUI7QUFDSCxDQXBDTDtBQXVDQUQsTUFBTSxDQUFDWCxlQUFQLEdBQXlCQSxlQUF6QiIsInNvdXJjZXMiOlsid2VicGFjazovLy8uL3NyYy9qcy9jYXB0Y2hhcy9qYXZhc2NyaXB0LmpzPzEwZWMiXSwic291cmNlc0NvbnRlbnQiOlsiZXhwb3J0IGNsYXNzIEZvcm1pZUpTQ2FwdGNoYSB7XG4gICAgY29uc3RydWN0b3Ioc2V0dGluZ3MgPSB7fSkge1xuICAgICAgICB0aGlzLmZvcm1JZCA9IHNldHRpbmdzLmZvcm1JZDtcbiAgICAgICAgdGhpcy5zZXNzaW9uS2V5ID0gc2V0dGluZ3Muc2Vzc2lvbktleTtcblxuICAgICAgICB0aGlzLiRmb3JtID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignIycgKyB0aGlzLmZvcm1JZCk7XG5cbiAgICAgICAgaWYgKCF0aGlzLiRmb3JtKSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKCdVbmFibGUgdG8gZmluZCBmb3JtICMnICsgdGhpcy5mb3JtSWQpO1xuXG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLiRwbGFjZWhvbGRlciA9IHRoaXMuJGZvcm0ucXVlcnlTZWxlY3RvcignLmZvcm1pZS1qc2NhcHRjaGEtcGxhY2Vob2xkZXInKTtcblxuICAgICAgICBpZiAoIXRoaXMuJHBsYWNlaG9sZGVyKSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKCdVbmFibGUgdG8gZmluZCBKYXZhU2NyaXB0IENhcHRjaGEgcGxhY2Vob2xkZXIgZm9yICMnICsgdGhpcy5mb3JtSWQgKyAnLmZvcm1pZS1qc2NhcHRjaGEtcGxhY2Vob2xkZXInKTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gRmluZCB0aGUgdmFsdWUgdG8gYWRkLCBhcyBhcHBlbmRlZCB0byB0aGUgcGFnZVxuICAgICAgICB0aGlzLnZhbHVlID0gd2luZG93WydGb3JtaWUnICsgdGhpcy5zZXNzaW9uS2V5XTtcblxuICAgICAgICBpZiAoIXRoaXMudmFsdWUpIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIEphdmFTY3JpcHQgQ2FwdGNoYSB2YWx1ZSBmb3IgRm9ybWllJyArIHRoaXMuc2Vzc2lvbktleSk7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciAkaW5wdXQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpbnB1dCcpO1xuICAgICAgICAkaW5wdXQuc2V0QXR0cmlidXRlKCd0eXBlJywgJ2hpZGRlbicpO1xuICAgICAgICAkaW5wdXQuc2V0QXR0cmlidXRlKCduYW1lJywgdGhpcy5zZXNzaW9uS2V5KTtcbiAgICAgICAgJGlucHV0LnZhbHVlID0gdGhpcy52YWx1ZTtcblxuICAgICAgICB0aGlzLiRwbGFjZWhvbGRlci5hcHBlbmRDaGlsZCgkaW5wdXQpO1xuICAgIH1cbn1cblxud2luZG93LkZvcm1pZUpTQ2FwdGNoYSA9IEZvcm1pZUpTQ2FwdGNoYTtcbiJdLCJuYW1lcyI6WyJGb3JtaWVKU0NhcHRjaGEiLCJzZXR0aW5ncyIsImZvcm1JZCIsInNlc3Npb25LZXkiLCIkZm9ybSIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsImNvbnNvbGUiLCJlcnJvciIsIiRwbGFjZWhvbGRlciIsInZhbHVlIiwid2luZG93IiwiJGlucHV0IiwiY3JlYXRlRWxlbWVudCIsInNldEF0dHJpYnV0ZSIsImFwcGVuZENoaWxkIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/captchas/javascript.js\n");

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