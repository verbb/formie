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

/***/ "./src/js/fields/text-limit.js":
/*!*************************************!*\
  !*** ./src/js/fields/text-limit.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieTextLimit\": () => (/* binding */ FormieTextLimit)\n/* harmony export */ });\n/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ \"./src/js/utils/utils.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\n\nvar FormieTextLimit = /*#__PURE__*/function () {\n  function FormieTextLimit() {\n    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n    _classCallCheck(this, FormieTextLimit);\n\n    this.$form = settings.$form;\n    this.form = this.$form.form;\n    this.$field = settings.$field;\n    this.$text = this.$field.querySelector('[data-max-limit]');\n    this.$input = this.$field.querySelector('input, textarea');\n\n    if (this.$text) {\n      this.initTextMax();\n    } else {\n      console.error('Unable to find rich text field “[data-max-limit]”');\n    }\n  }\n\n  _createClass(FormieTextLimit, [{\n    key: \"initTextMax\",\n    value: function initTextMax() {\n      this.maxChars = this.$text.getAttribute('data-max-chars');\n      this.maxWords = this.$text.getAttribute('data-max-words');\n\n      if (this.maxChars) {\n        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.characterCheck.bind(this), false);\n      }\n\n      if (this.maxWords) {\n        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.wordCheck.bind(this), false);\n      }\n    }\n  }, {\n    key: \"characterCheck\",\n    value: function characterCheck(e) {\n      var _this = this;\n\n      setTimeout(function () {\n        // If we're using a rich text editor, treat it a little differently\n        var isRichText = e.target.hasAttribute('contenteditable');\n        var value = isRichText ? e.target.innerHTML : e.target.value;\n        var charactersLeft = _this.maxChars - value.length;\n\n        if (charactersLeft <= 0) {\n          charactersLeft = '0';\n        }\n\n        _this.$text.innerHTML = t('{num} characters left', {\n          num: charactersLeft\n        });\n      }, 1);\n    }\n  }, {\n    key: \"wordCheck\",\n    value: function wordCheck(e) {\n      var _this2 = this;\n\n      setTimeout(function () {\n        // If we're using a rich text editor, treat it a little differently\n        var isRichText = e.target.hasAttribute('contenteditable');\n        var value = isRichText ? e.target.innerHTML : e.target.value;\n        var wordCount = value.split(/\\S+/).length - 1;\n        var regex = new RegExp(\"^\\\\s*\\\\S+(?:\\\\s+\\\\S+){0,\".concat(_this2.maxWords - 1, \"}\"));\n\n        if (wordCount >= _this2.maxWords) {\n          e.target.value = value.match(regex);\n        }\n\n        var wordsLeft = _this2.maxWords - wordCount;\n\n        if (wordsLeft <= 0) {\n          wordsLeft = '0';\n        }\n\n        _this2.$text.innerHTML = t('{num} words left', {\n          num: wordsLeft\n        });\n      }, 1);\n    }\n  }]);\n\n  return FormieTextLimit;\n}();\nwindow.FormieTextLimit = FormieTextLimit;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUcsSUFBSUMsTUFBSixtQ0FBc0MsTUFBSSxDQUFDbkIsUUFBTCxHQUFnQixDQUF0RCxPQUFkOztRQUVBLElBQUlnQixTQUFTLElBQUksTUFBSSxDQUFDaEIsUUFBdEIsRUFBZ0M7VUFDNUJLLENBQUMsQ0FBQ0csTUFBRixDQUFTRSxLQUFULEdBQWlCQSxLQUFLLENBQUNVLEtBQU4sQ0FBWUYsS0FBWixDQUFqQjtRQUNIOztRQUVELElBQUlHLFNBQVMsR0FBRyxNQUFJLENBQUNyQixRQUFMLEdBQWdCZ0IsU0FBaEM7O1FBRUEsSUFBSUssU0FBUyxJQUFJLENBQWpCLEVBQW9CO1VBQ2hCQSxTQUFTLEdBQUcsR0FBWjtRQUNIOztRQUVELE1BQUksQ0FBQzdCLEtBQUwsQ0FBV21CLFNBQVgsR0FBdUJHLENBQUMsQ0FBQyxrQkFBRCxFQUFxQjtVQUN6Q0MsR0FBRyxFQUFFTTtRQURvQyxDQUFyQixDQUF4QjtNQUdILENBdEJTLEVBc0JQLENBdEJPLENBQVY7SUF1Qkg7RUF2RUw7O0VBQUE7QUFBQTtBQTBFQUMsTUFBTSxDQUFDbkMsZUFBUCxHQUF5QkEsZUFBekIiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9zcmMvanMvZmllbGRzL3RleHQtbGltaXQuanM/YTAyNSJdLCJzb3VyY2VzQ29udGVudCI6WyJpbXBvcnQgeyBldmVudEtleSB9IGZyb20gJy4uL3V0aWxzL3V0aWxzJztcblxuZXhwb3J0IGNsYXNzIEZvcm1pZVRleHRMaW1pdCB7XG4gICAgY29uc3RydWN0b3Ioc2V0dGluZ3MgPSB7fSkge1xuICAgICAgICB0aGlzLiRmb3JtID0gc2V0dGluZ3MuJGZvcm07XG4gICAgICAgIHRoaXMuZm9ybSA9IHRoaXMuJGZvcm0uZm9ybTtcbiAgICAgICAgdGhpcy4kZmllbGQgPSBzZXR0aW5ncy4kZmllbGQ7XG4gICAgICAgIHRoaXMuJHRleHQgPSB0aGlzLiRmaWVsZC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1tYXgtbGltaXRdJyk7XG4gICAgICAgIHRoaXMuJGlucHV0ID0gdGhpcy4kZmllbGQucXVlcnlTZWxlY3RvcignaW5wdXQsIHRleHRhcmVhJyk7XG5cbiAgICAgICAgaWYgKHRoaXMuJHRleHQpIHtcbiAgICAgICAgICAgIHRoaXMuaW5pdFRleHRNYXgoKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ1VuYWJsZSB0byBmaW5kIHJpY2ggdGV4dCBmaWVsZCDigJxbZGF0YS1tYXgtbGltaXRd4oCdJyk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBpbml0VGV4dE1heCgpIHtcbiAgICAgICAgdGhpcy5tYXhDaGFycyA9IHRoaXMuJHRleHQuZ2V0QXR0cmlidXRlKCdkYXRhLW1heC1jaGFycycpO1xuICAgICAgICB0aGlzLm1heFdvcmRzID0gdGhpcy4kdGV4dC5nZXRBdHRyaWJ1dGUoJ2RhdGEtbWF4LXdvcmRzJyk7XG5cbiAgICAgICAgaWYgKHRoaXMubWF4Q2hhcnMpIHtcbiAgICAgICAgICAgIHRoaXMuZm9ybS5hZGRFdmVudExpc3RlbmVyKHRoaXMuJGlucHV0LCBldmVudEtleSgna2V5ZG93bicpLCB0aGlzLmNoYXJhY3RlckNoZWNrLmJpbmQodGhpcyksIGZhbHNlKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0aGlzLm1heFdvcmRzKSB7XG4gICAgICAgICAgICB0aGlzLmZvcm0uYWRkRXZlbnRMaXN0ZW5lcih0aGlzLiRpbnB1dCwgZXZlbnRLZXkoJ2tleWRvd24nKSwgdGhpcy53b3JkQ2hlY2suYmluZCh0aGlzKSwgZmFsc2UpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgY2hhcmFjdGVyQ2hlY2soZSkge1xuICAgICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgICAgIC8vIElmIHdlJ3JlIHVzaW5nIGEgcmljaCB0ZXh0IGVkaXRvciwgdHJlYXQgaXQgYSBsaXR0bGUgZGlmZmVyZW50bHlcbiAgICAgICAgICAgIGNvbnN0IGlzUmljaFRleHQgPSBlLnRhcmdldC5oYXNBdHRyaWJ1dGUoJ2NvbnRlbnRlZGl0YWJsZScpO1xuXG4gICAgICAgICAgICBjb25zdCB2YWx1ZSA9IGlzUmljaFRleHQgPyBlLnRhcmdldC5pbm5lckhUTUwgOiBlLnRhcmdldC52YWx1ZTtcblxuICAgICAgICAgICAgbGV0IGNoYXJhY3RlcnNMZWZ0ID0gdGhpcy5tYXhDaGFycyAtIHZhbHVlLmxlbmd0aDtcblxuICAgICAgICAgICAgaWYgKGNoYXJhY3RlcnNMZWZ0IDw9IDApIHtcbiAgICAgICAgICAgICAgICBjaGFyYWN0ZXJzTGVmdCA9ICcwJztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy4kdGV4dC5pbm5lckhUTUwgPSB0KCd7bnVtfSBjaGFyYWN0ZXJzIGxlZnQnLCB7XG4gICAgICAgICAgICAgICAgbnVtOiBjaGFyYWN0ZXJzTGVmdCxcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9LCAxKTtcbiAgICB9XG5cbiAgICB3b3JkQ2hlY2soZSkge1xuICAgICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgICAgIC8vIElmIHdlJ3JlIHVzaW5nIGEgcmljaCB0ZXh0IGVkaXRvciwgdHJlYXQgaXQgYSBsaXR0bGUgZGlmZmVyZW50bHlcbiAgICAgICAgICAgIGNvbnN0IGlzUmljaFRleHQgPSBlLnRhcmdldC5oYXNBdHRyaWJ1dGUoJ2NvbnRlbnRlZGl0YWJsZScpO1xuXG4gICAgICAgICAgICBjb25zdCB2YWx1ZSA9IGlzUmljaFRleHQgPyBlLnRhcmdldC5pbm5lckhUTUwgOiBlLnRhcmdldC52YWx1ZTtcblxuICAgICAgICAgICAgY29uc3Qgd29yZENvdW50ID0gdmFsdWUuc3BsaXQoL1xcUysvKS5sZW5ndGggLSAxO1xuICAgICAgICAgICAgY29uc3QgcmVnZXggPSBuZXcgUmVnRXhwKGBeXFxcXHMqXFxcXFMrKD86XFxcXHMrXFxcXFMrKXswLCR7dGhpcy5tYXhXb3JkcyAtIDF9fWApO1xuXG4gICAgICAgICAgICBpZiAod29yZENvdW50ID49IHRoaXMubWF4V29yZHMpIHtcbiAgICAgICAgICAgICAgICBlLnRhcmdldC52YWx1ZSA9IHZhbHVlLm1hdGNoKHJlZ2V4KTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgbGV0IHdvcmRzTGVmdCA9IHRoaXMubWF4V29yZHMgLSB3b3JkQ291bnQ7XG5cbiAgICAgICAgICAgIGlmICh3b3Jkc0xlZnQgPD0gMCkge1xuICAgICAgICAgICAgICAgIHdvcmRzTGVmdCA9ICcwJztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy4kdGV4dC5pbm5lckhUTUwgPSB0KCd7bnVtfSB3b3JkcyBsZWZ0Jywge1xuICAgICAgICAgICAgICAgIG51bTogd29yZHNMZWZ0LFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sIDEpO1xuICAgIH1cbn1cblxud2luZG93LkZvcm1pZVRleHRMaW1pdCA9IEZvcm1pZVRleHRMaW1pdDtcbiJdLCJuYW1lcyI6WyJldmVudEtleSIsIkZvcm1pZVRleHRMaW1pdCIsInNldHRpbmdzIiwiJGZvcm0iLCJmb3JtIiwiJGZpZWxkIiwiJHRleHQiLCJxdWVyeVNlbGVjdG9yIiwiJGlucHV0IiwiaW5pdFRleHRNYXgiLCJjb25zb2xlIiwiZXJyb3IiLCJtYXhDaGFycyIsImdldEF0dHJpYnV0ZSIsIm1heFdvcmRzIiwiYWRkRXZlbnRMaXN0ZW5lciIsImNoYXJhY3RlckNoZWNrIiwiYmluZCIsIndvcmRDaGVjayIsImUiLCJzZXRUaW1lb3V0IiwiaXNSaWNoVGV4dCIsInRhcmdldCIsImhhc0F0dHJpYnV0ZSIsInZhbHVlIiwiaW5uZXJIVE1MIiwiY2hhcmFjdGVyc0xlZnQiLCJsZW5ndGgiLCJ0IiwibnVtIiwid29yZENvdW50Iiwic3BsaXQiLCJyZWdleCIsIlJlZ0V4cCIsIm1hdGNoIiwid29yZHNMZWZ0Iiwid2luZG93Il0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/fields/text-limit.js\n");

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"eventKey\": () => (/* binding */ eventKey),\n/* harmony export */   \"isEmpty\": () => (/* binding */ isEmpty),\n/* harmony export */   \"toBoolean\": () => (/* binding */ toBoolean)\n/* harmony export */ });\nvar isEmpty = function isEmpty(obj) {\n  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;\n};\nvar toBoolean = function toBoolean(val) {\n  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;\n};\nvar eventKey = function eventKey(eventName) {\n  var namespace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n\n  if (!namespace) {\n    namespace = Math.random().toString(36).substr(2, 5);\n  }\n\n  return \"\".concat(eventName, \".\").concat(namespace);\n};//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvdXRpbHMvdXRpbHMuanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQU8sSUFBTUEsT0FBTyxHQUFHLFNBQVZBLE9BQVUsQ0FBU0MsR0FBVCxFQUFjO0VBQ2pDLE9BQU9BLEdBQUcsSUFBSUMsTUFBTSxDQUFDQyxJQUFQLENBQVlGLEdBQVosRUFBaUJHLE1BQWpCLEtBQTRCLENBQW5DLElBQXdDSCxHQUFHLENBQUNJLFdBQUosS0FBb0JILE1BQW5FO0FBQ0gsQ0FGTTtBQUlBLElBQU1JLFNBQVMsR0FBRyxTQUFaQSxTQUFZLENBQVNDLEdBQVQsRUFBYztFQUNuQyxPQUFPLENBQUMsMkJBQTJCQyxJQUEzQixDQUFnQ0QsR0FBaEMsQ0FBRCxJQUF5QyxDQUFDLENBQUNBLEdBQWxEO0FBQ0gsQ0FGTTtBQUlBLElBQU1FLFFBQVEsR0FBRyxTQUFYQSxRQUFXLENBQVNDLFNBQVQsRUFBc0M7RUFBQSxJQUFsQkMsU0FBa0IsdUVBQU4sSUFBTTs7RUFDMUQsSUFBSSxDQUFDQSxTQUFMLEVBQWdCO0lBQ1pBLFNBQVMsR0FBR0MsSUFBSSxDQUFDQyxNQUFMLEdBQWNDLFFBQWQsQ0FBdUIsRUFBdkIsRUFBMkJDLE1BQTNCLENBQWtDLENBQWxDLEVBQXFDLENBQXJDLENBQVo7RUFDSDs7RUFFRCxpQkFBVUwsU0FBVixjQUF1QkMsU0FBdkI7QUFDSCxDQU5NIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vc3JjL2pzL3V0aWxzL3V0aWxzLmpzP2Q5ZWUiXSwic291cmNlc0NvbnRlbnQiOlsiZXhwb3J0IGNvbnN0IGlzRW1wdHkgPSBmdW5jdGlvbihvYmopIHtcbiAgICByZXR1cm4gb2JqICYmIE9iamVjdC5rZXlzKG9iaikubGVuZ3RoID09PSAwICYmIG9iai5jb25zdHJ1Y3RvciA9PT0gT2JqZWN0O1xufTtcblxuZXhwb3J0IGNvbnN0IHRvQm9vbGVhbiA9IGZ1bmN0aW9uKHZhbCkge1xuICAgIHJldHVybiAhL14oPzpmKD86YWxzZSk/fG5vP3wwKykkL2kudGVzdCh2YWwpICYmICEhdmFsO1xufTtcblxuZXhwb3J0IGNvbnN0IGV2ZW50S2V5ID0gZnVuY3Rpb24oZXZlbnROYW1lLCBuYW1lc3BhY2UgPSBudWxsKSB7XG4gICAgaWYgKCFuYW1lc3BhY2UpIHtcbiAgICAgICAgbmFtZXNwYWNlID0gTWF0aC5yYW5kb20oKS50b1N0cmluZygzNikuc3Vic3RyKDIsIDUpO1xuICAgIH1cblxuICAgIHJldHVybiBgJHtldmVudE5hbWV9LiR7bmFtZXNwYWNlfWA7XG59O1xuIl0sIm5hbWVzIjpbImlzRW1wdHkiLCJvYmoiLCJPYmplY3QiLCJrZXlzIiwibGVuZ3RoIiwiY29uc3RydWN0b3IiLCJ0b0Jvb2xlYW4iLCJ2YWwiLCJ0ZXN0IiwiZXZlbnRLZXkiLCJldmVudE5hbWUiLCJuYW1lc3BhY2UiLCJNYXRoIiwicmFuZG9tIiwidG9TdHJpbmciLCJzdWJzdHIiXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/utils/utils.js\n");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./src/js/fields/text-limit.js");
/******/ 	
/******/ })()
;