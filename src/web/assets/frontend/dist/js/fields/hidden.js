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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieHidden\": () => (/* binding */ FormieHidden)\n/* harmony export */ });\n/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ \"./src/js/utils/utils.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\n\nvar FormieHidden = /*#__PURE__*/function () {\n  function FormieHidden() {\n    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n    _classCallCheck(this, FormieHidden);\n\n    this.$form = settings.$form;\n    this.form = this.$form.form;\n    this.$field = settings.$field;\n    this.$input = this.$field.querySelector('input');\n    this.cookieName = settings.cookieName;\n\n    if (this.$input) {\n      this.initHiddenField();\n    } else {\n      console.error('Unable to find hidden input.');\n    }\n  }\n\n  _createClass(FormieHidden, [{\n    key: \"initHiddenField\",\n    value: function initHiddenField() {\n      // Populate the input with the cookie value.\n      var cookieValue = this.getCookie(this.cookieName);\n\n      if (cookieValue) {\n        this.$input.value = cookieValue;\n      } // Update the form hash, so we don't get change warnings\n\n\n      if (this.form.formTheme) {\n        this.form.formTheme.updateFormHash();\n      }\n    }\n  }, {\n    key: \"getCookie\",\n    value: function getCookie(name) {\n      var match = document.cookie.match(new RegExp(\"(^| )\".concat(name, \"=([^;]+)\")));\n\n      if (match) {\n        return match[2];\n      }\n    }\n  }]);\n\n  return FormieHidden;\n}();\nwindow.FormieHidden = FormieHidden;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvZmllbGRzL2hpZGRlbi5qcy5qcyIsIm1hcHBpbmdzIjoiOzs7Ozs7Ozs7OztBQUFBO0FBRU8sSUFBTUUsWUFBYjtFQUNJLHdCQUEyQjtJQUFBLElBQWZDLFFBQWUsdUVBQUosRUFBSTs7SUFBQTs7SUFDdkIsS0FBS0MsS0FBTCxHQUFhRCxRQUFRLENBQUNDLEtBQXRCO0lBQ0EsS0FBS0MsSUFBTCxHQUFZLEtBQUtELEtBQUwsQ0FBV0MsSUFBdkI7SUFDQSxLQUFLQyxNQUFMLEdBQWNILFFBQVEsQ0FBQ0csTUFBdkI7SUFDQSxLQUFLQyxNQUFMLEdBQWMsS0FBS0QsTUFBTCxDQUFZRSxhQUFaLENBQTBCLE9BQTFCLENBQWQ7SUFDQSxLQUFLQyxVQUFMLEdBQWtCTixRQUFRLENBQUNNLFVBQTNCOztJQUVBLElBQUksS0FBS0YsTUFBVCxFQUFpQjtNQUNiLEtBQUtHLGVBQUw7SUFDSCxDQUZELE1BRU87TUFDSEMsT0FBTyxDQUFDQyxLQUFSLENBQWMsOEJBQWQ7SUFDSDtFQUNKOztFQWJMO0lBQUE7SUFBQSxPQWVJLDJCQUFrQjtNQUNkO01BQ0EsSUFBTUMsV0FBVyxHQUFHLEtBQUtDLFNBQUwsQ0FBZSxLQUFLTCxVQUFwQixDQUFwQjs7TUFFQSxJQUFJSSxXQUFKLEVBQWlCO1FBQ2IsS0FBS04sTUFBTCxDQUFZUSxLQUFaLEdBQW9CRixXQUFwQjtNQUNILENBTmEsQ0FRZDs7O01BQ0EsSUFBSSxLQUFLUixJQUFMLENBQVVXLFNBQWQsRUFBeUI7UUFDckIsS0FBS1gsSUFBTCxDQUFVVyxTQUFWLENBQW9CQyxjQUFwQjtNQUNIO0lBQ0o7RUEzQkw7SUFBQTtJQUFBLE9BNkJJLG1CQUFVQyxJQUFWLEVBQWdCO01BQ1osSUFBTUMsS0FBSyxHQUFHQyxRQUFRLENBQUNDLE1BQVQsQ0FBZ0JGLEtBQWhCLENBQXNCLElBQUlHLE1BQUosZ0JBQW1CSixJQUFuQixjQUF0QixDQUFkOztNQUVBLElBQUlDLEtBQUosRUFBVztRQUNQLE9BQU9BLEtBQUssQ0FBQyxDQUFELENBQVo7TUFDSDtJQUNKO0VBbkNMOztFQUFBO0FBQUE7QUFzQ0FJLE1BQU0sQ0FBQ3JCLFlBQVAsR0FBc0JBLFlBQXRCIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vc3JjL2pzL2ZpZWxkcy9oaWRkZW4uanM/ZDZjMiJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgeyB0LCBldmVudEtleSB9IGZyb20gJy4uL3V0aWxzL3V0aWxzJztcblxuZXhwb3J0IGNsYXNzIEZvcm1pZUhpZGRlbiB7XG4gICAgY29uc3RydWN0b3Ioc2V0dGluZ3MgPSB7fSkge1xuICAgICAgICB0aGlzLiRmb3JtID0gc2V0dGluZ3MuJGZvcm07XG4gICAgICAgIHRoaXMuZm9ybSA9IHRoaXMuJGZvcm0uZm9ybTtcbiAgICAgICAgdGhpcy4kZmllbGQgPSBzZXR0aW5ncy4kZmllbGQ7XG4gICAgICAgIHRoaXMuJGlucHV0ID0gdGhpcy4kZmllbGQucXVlcnlTZWxlY3RvcignaW5wdXQnKTtcbiAgICAgICAgdGhpcy5jb29raWVOYW1lID0gc2V0dGluZ3MuY29va2llTmFtZTtcblxuICAgICAgICBpZiAodGhpcy4kaW5wdXQpIHtcbiAgICAgICAgICAgIHRoaXMuaW5pdEhpZGRlbkZpZWxkKCk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKCdVbmFibGUgdG8gZmluZCBoaWRkZW4gaW5wdXQuJyk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBpbml0SGlkZGVuRmllbGQoKSB7XG4gICAgICAgIC8vIFBvcHVsYXRlIHRoZSBpbnB1dCB3aXRoIHRoZSBjb29raWUgdmFsdWUuXG4gICAgICAgIGNvbnN0IGNvb2tpZVZhbHVlID0gdGhpcy5nZXRDb29raWUodGhpcy5jb29raWVOYW1lKTtcblxuICAgICAgICBpZiAoY29va2llVmFsdWUpIHtcbiAgICAgICAgICAgIHRoaXMuJGlucHV0LnZhbHVlID0gY29va2llVmFsdWU7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBVcGRhdGUgdGhlIGZvcm0gaGFzaCwgc28gd2UgZG9uJ3QgZ2V0IGNoYW5nZSB3YXJuaW5nc1xuICAgICAgICBpZiAodGhpcy5mb3JtLmZvcm1UaGVtZSkge1xuICAgICAgICAgICAgdGhpcy5mb3JtLmZvcm1UaGVtZS51cGRhdGVGb3JtSGFzaCgpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgZ2V0Q29va2llKG5hbWUpIHtcbiAgICAgICAgY29uc3QgbWF0Y2ggPSBkb2N1bWVudC5jb29raWUubWF0Y2gobmV3IFJlZ0V4cChgKF58ICkke25hbWV9PShbXjtdKylgKSk7XG5cbiAgICAgICAgaWYgKG1hdGNoKSB7XG4gICAgICAgICAgICByZXR1cm4gbWF0Y2hbMl07XG4gICAgICAgIH1cbiAgICB9XG59XG5cbndpbmRvdy5Gb3JtaWVIaWRkZW4gPSBGb3JtaWVIaWRkZW47XG4iXSwibmFtZXMiOlsidCIsImV2ZW50S2V5IiwiRm9ybWllSGlkZGVuIiwic2V0dGluZ3MiLCIkZm9ybSIsImZvcm0iLCIkZmllbGQiLCIkaW5wdXQiLCJxdWVyeVNlbGVjdG9yIiwiY29va2llTmFtZSIsImluaXRIaWRkZW5GaWVsZCIsImNvbnNvbGUiLCJlcnJvciIsImNvb2tpZVZhbHVlIiwiZ2V0Q29va2llIiwidmFsdWUiLCJmb3JtVGhlbWUiLCJ1cGRhdGVGb3JtSGFzaCIsIm5hbWUiLCJtYXRjaCIsImRvY3VtZW50IiwiY29va2llIiwiUmVnRXhwIiwid2luZG93Il0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/fields/hidden.js\n");

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"eventKey\": () => (/* binding */ eventKey),\n/* harmony export */   \"isEmpty\": () => (/* binding */ isEmpty),\n/* harmony export */   \"t\": () => (/* binding */ t),\n/* harmony export */   \"toBoolean\": () => (/* binding */ toBoolean)\n/* harmony export */ });\nvar isEmpty = function isEmpty(obj) {\n  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;\n};\nvar toBoolean = function toBoolean(val) {\n  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;\n};\nvar eventKey = function eventKey(eventName) {\n  var namespace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n\n  if (!namespace) {\n    namespace = Math.random().toString(36).substr(2, 5);\n  }\n\n  return \"\".concat(eventName, \".\").concat(namespace);\n};\nvar t = function t(string) {\n  var replacements = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};\n\n  if (window.FormieTranslations) {\n    string = window.FormieTranslations[string] || string;\n  }\n\n  return string.replace(/{([a-zA-Z0-9]+)}/g, function (match, p1) {\n    if (replacements[p1]) {\n      return replacements[p1];\n    }\n\n    return match;\n  });\n};//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvdXRpbHMvdXRpbHMuanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7OztBQUFPLElBQU1BLE9BQU8sR0FBRyxTQUFWQSxPQUFVLENBQVNDLEdBQVQsRUFBYztFQUNqQyxPQUFPQSxHQUFHLElBQUlDLE1BQU0sQ0FBQ0MsSUFBUCxDQUFZRixHQUFaLEVBQWlCRyxNQUFqQixLQUE0QixDQUFuQyxJQUF3Q0gsR0FBRyxDQUFDSSxXQUFKLEtBQW9CSCxNQUFuRTtBQUNILENBRk07QUFJQSxJQUFNSSxTQUFTLEdBQUcsU0FBWkEsU0FBWSxDQUFTQyxHQUFULEVBQWM7RUFDbkMsT0FBTyxDQUFDLDJCQUEyQkMsSUFBM0IsQ0FBZ0NELEdBQWhDLENBQUQsSUFBeUMsQ0FBQyxDQUFDQSxHQUFsRDtBQUNILENBRk07QUFJQSxJQUFNRSxRQUFRLEdBQUcsU0FBWEEsUUFBVyxDQUFTQyxTQUFULEVBQXNDO0VBQUEsSUFBbEJDLFNBQWtCLHVFQUFOLElBQU07O0VBQzFELElBQUksQ0FBQ0EsU0FBTCxFQUFnQjtJQUNaQSxTQUFTLEdBQUdDLElBQUksQ0FBQ0MsTUFBTCxHQUFjQyxRQUFkLENBQXVCLEVBQXZCLEVBQTJCQyxNQUEzQixDQUFrQyxDQUFsQyxFQUFxQyxDQUFyQyxDQUFaO0VBQ0g7O0VBRUQsaUJBQVVMLFNBQVYsY0FBdUJDLFNBQXZCO0FBQ0gsQ0FOTTtBQVFBLElBQU1LLENBQUMsR0FBRyxTQUFKQSxDQUFJLENBQVNDLE1BQVQsRUFBb0M7RUFBQSxJQUFuQkMsWUFBbUIsdUVBQUosRUFBSTs7RUFDakQsSUFBSUMsTUFBTSxDQUFDQyxrQkFBWCxFQUErQjtJQUMzQkgsTUFBTSxHQUFHRSxNQUFNLENBQUNDLGtCQUFQLENBQTBCSCxNQUExQixLQUFxQ0EsTUFBOUM7RUFDSDs7RUFFRCxPQUFPQSxNQUFNLENBQUNJLE9BQVAsQ0FBZSxtQkFBZixFQUFvQyxVQUFDQyxLQUFELEVBQVFDLEVBQVIsRUFBZTtJQUN0RCxJQUFJTCxZQUFZLENBQUNLLEVBQUQsQ0FBaEIsRUFBc0I7TUFDbEIsT0FBT0wsWUFBWSxDQUFDSyxFQUFELENBQW5CO0lBQ0g7O0lBRUQsT0FBT0QsS0FBUDtFQUNILENBTk0sQ0FBUDtBQU9ILENBWk0iLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvdXRpbHMvdXRpbHMuanM/ZDllZSJdLCJzb3VyY2VzQ29udGVudCI6WyJleHBvcnQgY29uc3QgaXNFbXB0eSA9IGZ1bmN0aW9uKG9iaikge1xuICAgIHJldHVybiBvYmogJiYgT2JqZWN0LmtleXMob2JqKS5sZW5ndGggPT09IDAgJiYgb2JqLmNvbnN0cnVjdG9yID09PSBPYmplY3Q7XG59O1xuXG5leHBvcnQgY29uc3QgdG9Cb29sZWFuID0gZnVuY3Rpb24odmFsKSB7XG4gICAgcmV0dXJuICEvXig/OmYoPzphbHNlKT98bm8/fDArKSQvaS50ZXN0KHZhbCkgJiYgISF2YWw7XG59O1xuXG5leHBvcnQgY29uc3QgZXZlbnRLZXkgPSBmdW5jdGlvbihldmVudE5hbWUsIG5hbWVzcGFjZSA9IG51bGwpIHtcbiAgICBpZiAoIW5hbWVzcGFjZSkge1xuICAgICAgICBuYW1lc3BhY2UgPSBNYXRoLnJhbmRvbSgpLnRvU3RyaW5nKDM2KS5zdWJzdHIoMiwgNSk7XG4gICAgfVxuXG4gICAgcmV0dXJuIGAke2V2ZW50TmFtZX0uJHtuYW1lc3BhY2V9YDtcbn07XG5cbmV4cG9ydCBjb25zdCB0ID0gZnVuY3Rpb24oc3RyaW5nLCByZXBsYWNlbWVudHMgPSB7fSkge1xuICAgIGlmICh3aW5kb3cuRm9ybWllVHJhbnNsYXRpb25zKSB7XG4gICAgICAgIHN0cmluZyA9IHdpbmRvdy5Gb3JtaWVUcmFuc2xhdGlvbnNbc3RyaW5nXSB8fCBzdHJpbmc7XG4gICAgfVxuXG4gICAgcmV0dXJuIHN0cmluZy5yZXBsYWNlKC97KFthLXpBLVowLTldKyl9L2csIChtYXRjaCwgcDEpID0+IHtcbiAgICAgICAgaWYgKHJlcGxhY2VtZW50c1twMV0pIHtcbiAgICAgICAgICAgIHJldHVybiByZXBsYWNlbWVudHNbcDFdO1xuICAgICAgICB9XG5cbiAgICAgICAgcmV0dXJuIG1hdGNoO1xuICAgIH0pO1xufTtcbiJdLCJuYW1lcyI6WyJpc0VtcHR5Iiwib2JqIiwiT2JqZWN0Iiwia2V5cyIsImxlbmd0aCIsImNvbnN0cnVjdG9yIiwidG9Cb29sZWFuIiwidmFsIiwidGVzdCIsImV2ZW50S2V5IiwiZXZlbnROYW1lIiwibmFtZXNwYWNlIiwiTWF0aCIsInJhbmRvbSIsInRvU3RyaW5nIiwic3Vic3RyIiwidCIsInN0cmluZyIsInJlcGxhY2VtZW50cyIsIndpbmRvdyIsIkZvcm1pZVRyYW5zbGF0aW9ucyIsInJlcGxhY2UiLCJtYXRjaCIsInAxIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/utils/utils.js\n");

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