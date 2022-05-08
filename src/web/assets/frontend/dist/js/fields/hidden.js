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

/***/ "./src/js/fields/hidden.js":
/*!*********************************!*\
  !*** ./src/js/fields/hidden.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieHidden\": () => (/* binding */ FormieHidden)\n/* harmony export */ });\n/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ \"./src/js/utils/utils.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\n\nvar FormieHidden = /*#__PURE__*/function () {\n  function FormieHidden() {\n    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n    _classCallCheck(this, FormieHidden);\n\n    this.$form = settings.$form;\n    this.form = this.$form.form;\n    this.$field = settings.$field;\n    this.$input = this.$field.querySelector('input');\n    this.cookieName = settings.cookieName;\n\n    if (this.$input) {\n      this.initHiddenField();\n    } else {\n      console.error('Unable to find hidden input.');\n    }\n  }\n\n  _createClass(FormieHidden, [{\n    key: \"initHiddenField\",\n    value: function initHiddenField() {\n      // Populate the input with the cookie value.\n      var cookieValue = this.getCookie(this.cookieName);\n\n      if (cookieValue) {\n        this.$input.value = cookieValue;\n      } // Update the form hash, so we don't get change warnings\n\n\n      if (this.form.formTheme) {\n        this.form.formTheme.updateFormHash();\n      }\n    }\n  }, {\n    key: \"getCookie\",\n    value: function getCookie(name) {\n      var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));\n\n      if (match) {\n        return match[2];\n      }\n    }\n  }]);\n\n  return FormieHidden;\n}();\nwindow.FormieHidden = FormieHidden;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvZmllbGRzL2hpZGRlbi5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBO0FBRU8sSUFBTUMsWUFBYjtFQUNJLHdCQUEyQjtJQUFBLElBQWZDLFFBQWUsdUVBQUosRUFBSTs7SUFBQTs7SUFDdkIsS0FBS0MsS0FBTCxHQUFhRCxRQUFRLENBQUNDLEtBQXRCO0lBQ0EsS0FBS0MsSUFBTCxHQUFZLEtBQUtELEtBQUwsQ0FBV0MsSUFBdkI7SUFDQSxLQUFLQyxNQUFMLEdBQWNILFFBQVEsQ0FBQ0csTUFBdkI7SUFDQSxLQUFLQyxNQUFMLEdBQWMsS0FBS0QsTUFBTCxDQUFZRSxhQUFaLENBQTBCLE9BQTFCLENBQWQ7SUFDQSxLQUFLQyxVQUFMLEdBQWtCTixRQUFRLENBQUNNLFVBQTNCOztJQUVBLElBQUksS0FBS0YsTUFBVCxFQUFpQjtNQUNiLEtBQUtHLGVBQUw7SUFDSCxDQUZELE1BRU87TUFDSEMsT0FBTyxDQUFDQyxLQUFSLENBQWMsOEJBQWQ7SUFDSDtFQUNKOztFQWJMO0lBQUE7SUFBQSxPQWVJLDJCQUFrQjtNQUNkO01BQ0EsSUFBSUMsV0FBVyxHQUFHLEtBQUtDLFNBQUwsQ0FBZSxLQUFLTCxVQUFwQixDQUFsQjs7TUFFQSxJQUFJSSxXQUFKLEVBQWlCO1FBQ2IsS0FBS04sTUFBTCxDQUFZUSxLQUFaLEdBQW9CRixXQUFwQjtNQUNILENBTmEsQ0FRZDs7O01BQ0EsSUFBSSxLQUFLUixJQUFMLENBQVVXLFNBQWQsRUFBeUI7UUFDckIsS0FBS1gsSUFBTCxDQUFVVyxTQUFWLENBQW9CQyxjQUFwQjtNQUNIO0lBQ0o7RUEzQkw7SUFBQTtJQUFBLE9BNkJJLG1CQUFVQyxJQUFWLEVBQWdCO01BQ1osSUFBSUMsS0FBSyxHQUFHQyxRQUFRLENBQUNDLE1BQVQsQ0FBZ0JGLEtBQWhCLENBQXNCLElBQUlHLE1BQUosQ0FBVyxVQUFVSixJQUFWLEdBQWlCLFVBQTVCLENBQXRCLENBQVo7O01BRUEsSUFBSUMsS0FBSixFQUFXO1FBQ1AsT0FBT0EsS0FBSyxDQUFDLENBQUQsQ0FBWjtNQUNIO0lBQ0o7RUFuQ0w7O0VBQUE7QUFBQTtBQXNDQUksTUFBTSxDQUFDckIsWUFBUCxHQUFzQkEsWUFBdEIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvZmllbGRzL2hpZGRlbi5qcz9kNmMyIl0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCB7IGV2ZW50S2V5IH0gZnJvbSAnLi4vdXRpbHMvdXRpbHMnO1xuXG5leHBvcnQgY2xhc3MgRm9ybWllSGlkZGVuIHtcbiAgICBjb25zdHJ1Y3RvcihzZXR0aW5ncyA9IHt9KSB7XG4gICAgICAgIHRoaXMuJGZvcm0gPSBzZXR0aW5ncy4kZm9ybTtcbiAgICAgICAgdGhpcy5mb3JtID0gdGhpcy4kZm9ybS5mb3JtO1xuICAgICAgICB0aGlzLiRmaWVsZCA9IHNldHRpbmdzLiRmaWVsZDtcbiAgICAgICAgdGhpcy4kaW5wdXQgPSB0aGlzLiRmaWVsZC5xdWVyeVNlbGVjdG9yKCdpbnB1dCcpO1xuICAgICAgICB0aGlzLmNvb2tpZU5hbWUgPSBzZXR0aW5ncy5jb29raWVOYW1lO1xuXG4gICAgICAgIGlmICh0aGlzLiRpbnB1dCkge1xuICAgICAgICAgICAgdGhpcy5pbml0SGlkZGVuRmllbGQoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIGhpZGRlbiBpbnB1dC4nKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGluaXRIaWRkZW5GaWVsZCgpIHtcbiAgICAgICAgLy8gUG9wdWxhdGUgdGhlIGlucHV0IHdpdGggdGhlIGNvb2tpZSB2YWx1ZS5cbiAgICAgICAgbGV0IGNvb2tpZVZhbHVlID0gdGhpcy5nZXRDb29raWUodGhpcy5jb29raWVOYW1lKTtcblxuICAgICAgICBpZiAoY29va2llVmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuJGlucHV0LnZhbHVlID0gY29va2llVmFsdWU7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBVcGRhdGUgdGhlIGZvcm0gaGFzaCwgc28gd2UgZG9uJ3QgZ2V0IGNoYW5nZSB3YXJuaW5nc1xuICAgICAgICBpZiAodGhpcy5mb3JtLmZvcm1UaGVtZSkge1xuICAgICAgICAgICAgdGhpcy5mb3JtLmZvcm1UaGVtZS51cGRhdGVGb3JtSGFzaCgpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZ2V0Q29va2llKG5hbWUpIHtcbiAgICAgICAgdmFyIG1hdGNoID0gZG9jdW1lbnQuY29va2llLm1hdGNoKG5ldyBSZWdFeHAoJyhefCApJyArIG5hbWUgKyAnPShbXjtdKyknKSk7XG4gICAgICAgIFxuICAgICAgICBpZiAobWF0Y2gpIHtcbiAgICAgICAgICAgIHJldHVybiBtYXRjaFsyXTtcbiAgICAgICAgfVxuICAgIH1cbn1cblxud2luZG93LkZvcm1pZUhpZGRlbiA9IEZvcm1pZUhpZGRlbjtcbiJdLCJuYW1lcyI6WyJldmVudEtleSIsIkZvcm1pZUhpZGRlbiIsInNldHRpbmdzIiwiJGZvcm0iLCJmb3JtIiwiJGZpZWxkIiwiJGlucHV0IiwicXVlcnlTZWxlY3RvciIsImNvb2tpZU5hbWUiLCJpbml0SGlkZGVuRmllbGQiLCJjb25zb2xlIiwiZXJyb3IiLCJjb29raWVWYWx1ZSIsImdldENvb2tpZSIsInZhbHVlIiwiZm9ybVRoZW1lIiwidXBkYXRlRm9ybUhhc2giLCJuYW1lIiwibWF0Y2giLCJkb2N1bWVudCIsImNvb2tpZSIsIlJlZ0V4cCIsIndpbmRvdyJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/fields/hidden.js\n");

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"eventKey\": () => (/* binding */ eventKey),\n/* harmony export */   \"isEmpty\": () => (/* binding */ isEmpty),\n/* harmony export */   \"toBoolean\": () => (/* binding */ toBoolean)\n/* harmony export */ });\nvar isEmpty = function isEmpty(obj) {\n  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;\n};\nvar toBoolean = function toBoolean(val) {\n  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;\n};\nvar eventKey = function eventKey(eventName) {\n  return eventName + '.' + Math.random();\n};//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvdXRpbHMvdXRpbHMuanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQU8sSUFBTUEsT0FBTyxHQUFHLFNBQVZBLE9BQVUsQ0FBU0MsR0FBVCxFQUFjO0VBQ2pDLE9BQU9BLEdBQUcsSUFBSUMsTUFBTSxDQUFDQyxJQUFQLENBQVlGLEdBQVosRUFBaUJHLE1BQWpCLEtBQTRCLENBQW5DLElBQXdDSCxHQUFHLENBQUNJLFdBQUosS0FBb0JILE1BQW5FO0FBQ0gsQ0FGTTtBQUlBLElBQU1JLFNBQVMsR0FBRyxTQUFaQSxTQUFZLENBQVNDLEdBQVQsRUFBYztFQUNuQyxPQUFPLENBQUMsMkJBQTJCQyxJQUEzQixDQUFnQ0QsR0FBaEMsQ0FBRCxJQUF5QyxDQUFDLENBQUNBLEdBQWxEO0FBQ0gsQ0FGTTtBQUlBLElBQU1FLFFBQVEsR0FBRyxTQUFYQSxRQUFXLENBQVNDLFNBQVQsRUFBb0I7RUFDeEMsT0FBT0EsU0FBUyxHQUFHLEdBQVosR0FBa0JDLElBQUksQ0FBQ0MsTUFBTCxFQUF6QjtBQUNILENBRk0iLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvdXRpbHMvdXRpbHMuanM/ZDllZSJdLCJzb3VyY2VzQ29udGVudCI6WyJleHBvcnQgY29uc3QgaXNFbXB0eSA9IGZ1bmN0aW9uKG9iaikge1xuICAgIHJldHVybiBvYmogJiYgT2JqZWN0LmtleXMob2JqKS5sZW5ndGggPT09IDAgJiYgb2JqLmNvbnN0cnVjdG9yID09PSBPYmplY3Q7XG59O1xuXG5leHBvcnQgY29uc3QgdG9Cb29sZWFuID0gZnVuY3Rpb24odmFsKSB7XG4gICAgcmV0dXJuICEvXig/OmYoPzphbHNlKT98bm8/fDArKSQvaS50ZXN0KHZhbCkgJiYgISF2YWw7XG59O1xuXG5leHBvcnQgY29uc3QgZXZlbnRLZXkgPSBmdW5jdGlvbihldmVudE5hbWUpIHtcbiAgICByZXR1cm4gZXZlbnROYW1lICsgJy4nICsgTWF0aC5yYW5kb20oKTtcbn07XG5cbiJdLCJuYW1lcyI6WyJpc0VtcHR5Iiwib2JqIiwiT2JqZWN0Iiwia2V5cyIsImxlbmd0aCIsImNvbnN0cnVjdG9yIiwidG9Cb29sZWFuIiwidmFsIiwidGVzdCIsImV2ZW50S2V5IiwiZXZlbnROYW1lIiwiTWF0aCIsInJhbmRvbSJdLCJzb3VyY2VSb290IjoiIn0=\n//# sourceURL=webpack-internal:///./src/js/utils/utils.js\n");

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
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval-source-map devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./src/js/fields/hidden.js");
/******/ 	
/******/ })()
;