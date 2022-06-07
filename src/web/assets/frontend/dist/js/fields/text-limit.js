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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"FormieTextLimit\": () => (/* binding */ FormieTextLimit)\n/* harmony export */ });\n/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/utils */ \"./src/js/utils/utils.js\");\nfunction _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError(\"Cannot call a class as a function\"); } }\n\nfunction _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if (\"value\" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }\n\nfunction _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, \"prototype\", { writable: false }); return Constructor; }\n\n\nvar FormieTextLimit = /*#__PURE__*/function () {\n  function FormieTextLimit() {\n    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};\n\n    _classCallCheck(this, FormieTextLimit);\n\n    this.$form = settings.$form;\n    this.form = this.$form.form;\n    this.$field = settings.$field;\n    this.$text = this.$field.querySelector('[data-max-limit]');\n    this.$input = this.$field.querySelector('input, textarea');\n\n    if (this.$text) {\n      this.initTextMax();\n    } else {\n      console.error('Unable to find rich text field “[data-max-limit]”');\n    }\n  }\n\n  _createClass(FormieTextLimit, [{\n    key: \"initTextMax\",\n    value: function initTextMax() {\n      this.maxChars = this.$text.getAttribute('data-max-chars');\n      this.maxWords = this.$text.getAttribute('data-max-words');\n\n      if (this.maxChars) {\n        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.characterCheck.bind(this), false);\n      }\n\n      if (this.maxWords) {\n        this.form.addEventListener(this.$input, (0,_utils_utils__WEBPACK_IMPORTED_MODULE_0__.eventKey)('keydown'), this.wordCheck.bind(this), false);\n      }\n    }\n  }, {\n    key: \"characterCheck\",\n    value: function characterCheck(e) {\n      var _this = this;\n\n      setTimeout(function () {\n        // If we're using a rich text editor, treat it a little differently\n        var isRichText = e.target.hasAttribute('contenteditable');\n        var value = isRichText ? e.target.innerHTML : e.target.value;\n        var charactersLeft = _this.maxChars - value.length;\n\n        if (charactersLeft <= 0) {\n          charactersLeft = '0';\n        }\n\n        _this.$text.innerHTML = t('{num} characters left', {\n          num: charactersLeft\n        });\n      }, 1);\n    }\n  }, {\n    key: \"wordCheck\",\n    value: function wordCheck(e) {\n      var _this2 = this;\n\n      setTimeout(function () {\n        // If we're using a rich text editor, treat it a little differently\n        var isRichText = e.target.hasAttribute('contenteditable');\n        var value = isRichText ? e.target.innerHTML : e.target.value;\n        var wordCount = value.split(/\\S+/).length - 1;\n        var regex = new RegExp('^\\\\s*\\\\S+(?:\\\\s+\\\\S+){0,' + (_this2.maxWords - 1) + '}');\n\n        if (wordCount >= _this2.maxWords) {\n          e.target.value = value.match(regex);\n        }\n\n        var wordsLeft = _this2.maxWords - wordCount;\n\n        if (wordsLeft <= 0) {\n          wordsLeft = '0';\n        }\n\n        _this2.$text.innerHTML = t('{num} words left', {\n          num: wordsLeft\n        });\n      }, 1);\n    }\n  }]);\n\n  return FormieTextLimit;\n}();\nwindow.FormieTextLimit = FormieTextLimit;//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUcsSUFBSUMsTUFBSixDQUFXLDhCQUE4QixNQUFJLENBQUNuQixRQUFMLEdBQWdCLENBQTlDLElBQW1ELEdBQTlELENBQVo7O1FBRUEsSUFBSWdCLFNBQVMsSUFBSSxNQUFJLENBQUNoQixRQUF0QixFQUFnQztVQUM1QkssQ0FBQyxDQUFDRyxNQUFGLENBQVNFLEtBQVQsR0FBaUJBLEtBQUssQ0FBQ1UsS0FBTixDQUFZRixLQUFaLENBQWpCO1FBQ0g7O1FBRUQsSUFBSUcsU0FBUyxHQUFHLE1BQUksQ0FBQ3JCLFFBQUwsR0FBZ0JnQixTQUFoQzs7UUFFQSxJQUFJSyxTQUFTLElBQUksQ0FBakIsRUFBb0I7VUFDaEJBLFNBQVMsR0FBRyxHQUFaO1FBQ0g7O1FBRUQsTUFBSSxDQUFDN0IsS0FBTCxDQUFXbUIsU0FBWCxHQUF1QkcsQ0FBQyxDQUFDLGtCQUFELEVBQXFCO1VBQ3pDQyxHQUFHLEVBQUVNO1FBRG9DLENBQXJCLENBQXhCO01BR0gsQ0F0QlMsRUFzQlAsQ0F0Qk8sQ0FBVjtJQXVCSDtFQXZFTDs7RUFBQTtBQUFBO0FBMEVBQyxNQUFNLENBQUNuQyxlQUFQLEdBQXlCQSxlQUF6QiIsInNvdXJjZXMiOlsid2VicGFjazovLy8uL3NyYy9qcy9maWVsZHMvdGV4dC1saW1pdC5qcz9hMDI1Il0sInNvdXJjZXNDb250ZW50IjpbImltcG9ydCB7IGV2ZW50S2V5IH0gZnJvbSAnLi4vdXRpbHMvdXRpbHMnO1xuXG5leHBvcnQgY2xhc3MgRm9ybWllVGV4dExpbWl0IHtcbiAgICBjb25zdHJ1Y3RvcihzZXR0aW5ncyA9IHt9KSB7XG4gICAgICAgIHRoaXMuJGZvcm0gPSBzZXR0aW5ncy4kZm9ybTtcbiAgICAgICAgdGhpcy5mb3JtID0gdGhpcy4kZm9ybS5mb3JtO1xuICAgICAgICB0aGlzLiRmaWVsZCA9IHNldHRpbmdzLiRmaWVsZDtcbiAgICAgICAgdGhpcy4kdGV4dCA9IHRoaXMuJGZpZWxkLnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLW1heC1saW1pdF0nKTtcbiAgICAgICAgdGhpcy4kaW5wdXQgPSB0aGlzLiRmaWVsZC5xdWVyeVNlbGVjdG9yKCdpbnB1dCwgdGV4dGFyZWEnKTtcblxuICAgICAgICBpZiAodGhpcy4kdGV4dCkge1xuICAgICAgICAgICAgdGhpcy5pbml0VGV4dE1heCgpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgY29uc29sZS5lcnJvcignVW5hYmxlIHRvIGZpbmQgcmljaCB0ZXh0IGZpZWxkIOKAnFtkYXRhLW1heC1saW1pdF3igJ0nKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGluaXRUZXh0TWF4KCkge1xuICAgICAgICB0aGlzLm1heENoYXJzID0gdGhpcy4kdGV4dC5nZXRBdHRyaWJ1dGUoJ2RhdGEtbWF4LWNoYXJzJyk7XG4gICAgICAgIHRoaXMubWF4V29yZHMgPSB0aGlzLiR0ZXh0LmdldEF0dHJpYnV0ZSgnZGF0YS1tYXgtd29yZHMnKTtcblxuICAgICAgICBpZiAodGhpcy5tYXhDaGFycykge1xuICAgICAgICAgICAgdGhpcy5mb3JtLmFkZEV2ZW50TGlzdGVuZXIodGhpcy4kaW5wdXQsIGV2ZW50S2V5KCdrZXlkb3duJyksIHRoaXMuY2hhcmFjdGVyQ2hlY2suYmluZCh0aGlzKSwgZmFsc2UpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKHRoaXMubWF4V29yZHMpIHtcbiAgICAgICAgICAgIHRoaXMuZm9ybS5hZGRFdmVudExpc3RlbmVyKHRoaXMuJGlucHV0LCBldmVudEtleSgna2V5ZG93bicpLCB0aGlzLndvcmRDaGVjay5iaW5kKHRoaXMpLCBmYWxzZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBjaGFyYWN0ZXJDaGVjayhlKSB7XG4gICAgICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgLy8gSWYgd2UncmUgdXNpbmcgYSByaWNoIHRleHQgZWRpdG9yLCB0cmVhdCBpdCBhIGxpdHRsZSBkaWZmZXJlbnRseVxuICAgICAgICAgICAgdmFyIGlzUmljaFRleHQgPSBlLnRhcmdldC5oYXNBdHRyaWJ1dGUoJ2NvbnRlbnRlZGl0YWJsZScpO1xuXG4gICAgICAgICAgICB2YXIgdmFsdWUgPSBpc1JpY2hUZXh0ID8gZS50YXJnZXQuaW5uZXJIVE1MIDogZS50YXJnZXQudmFsdWU7XG5cbiAgICAgICAgICAgIHZhciBjaGFyYWN0ZXJzTGVmdCA9IHRoaXMubWF4Q2hhcnMgLSB2YWx1ZS5sZW5ndGg7XG5cbiAgICAgICAgICAgIGlmIChjaGFyYWN0ZXJzTGVmdCA8PSAwKSB7XG4gICAgICAgICAgICAgICAgY2hhcmFjdGVyc0xlZnQgPSAnMCc7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHRoaXMuJHRleHQuaW5uZXJIVE1MID0gdCgne251bX0gY2hhcmFjdGVycyBsZWZ0Jywge1xuICAgICAgICAgICAgICAgIG51bTogY2hhcmFjdGVyc0xlZnQsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSwgMSk7XG4gICAgfVxuXG4gICAgd29yZENoZWNrKGUpIHtcbiAgICAgICAgc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgICAgICAvLyBJZiB3ZSdyZSB1c2luZyBhIHJpY2ggdGV4dCBlZGl0b3IsIHRyZWF0IGl0IGEgbGl0dGxlIGRpZmZlcmVudGx5XG4gICAgICAgICAgICB2YXIgaXNSaWNoVGV4dCA9IGUudGFyZ2V0Lmhhc0F0dHJpYnV0ZSgnY29udGVudGVkaXRhYmxlJyk7XG5cbiAgICAgICAgICAgIHZhciB2YWx1ZSA9IGlzUmljaFRleHQgPyBlLnRhcmdldC5pbm5lckhUTUwgOiBlLnRhcmdldC52YWx1ZTtcbiAgICAgICAgICAgIFxuICAgICAgICAgICAgdmFyIHdvcmRDb3VudCA9IHZhbHVlLnNwbGl0KC9cXFMrLykubGVuZ3RoIC0gMTtcbiAgICAgICAgICAgIHZhciByZWdleCA9IG5ldyBSZWdFeHAoJ15cXFxccypcXFxcUysoPzpcXFxccytcXFxcUyspezAsJyArICh0aGlzLm1heFdvcmRzIC0gMSkgKyAnfScpO1xuICAgICAgICAgICAgXG4gICAgICAgICAgICBpZiAod29yZENvdW50ID49IHRoaXMubWF4V29yZHMpIHtcbiAgICAgICAgICAgICAgICBlLnRhcmdldC52YWx1ZSA9IHZhbHVlLm1hdGNoKHJlZ2V4KTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdmFyIHdvcmRzTGVmdCA9IHRoaXMubWF4V29yZHMgLSB3b3JkQ291bnQ7XG5cbiAgICAgICAgICAgIGlmICh3b3Jkc0xlZnQgPD0gMCkge1xuICAgICAgICAgICAgICAgIHdvcmRzTGVmdCA9ICcwJztcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgdGhpcy4kdGV4dC5pbm5lckhUTUwgPSB0KCd7bnVtfSB3b3JkcyBsZWZ0Jywge1xuICAgICAgICAgICAgICAgIG51bTogd29yZHNMZWZ0LFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0sIDEpO1xuICAgIH1cbn1cblxud2luZG93LkZvcm1pZVRleHRMaW1pdCA9IEZvcm1pZVRleHRMaW1pdDtcbiJdLCJuYW1lcyI6WyJldmVudEtleSIsIkZvcm1pZVRleHRMaW1pdCIsInNldHRpbmdzIiwiJGZvcm0iLCJmb3JtIiwiJGZpZWxkIiwiJHRleHQiLCJxdWVyeVNlbGVjdG9yIiwiJGlucHV0IiwiaW5pdFRleHRNYXgiLCJjb25zb2xlIiwiZXJyb3IiLCJtYXhDaGFycyIsImdldEF0dHJpYnV0ZSIsIm1heFdvcmRzIiwiYWRkRXZlbnRMaXN0ZW5lciIsImNoYXJhY3RlckNoZWNrIiwiYmluZCIsIndvcmRDaGVjayIsImUiLCJzZXRUaW1lb3V0IiwiaXNSaWNoVGV4dCIsInRhcmdldCIsImhhc0F0dHJpYnV0ZSIsInZhbHVlIiwiaW5uZXJIVE1MIiwiY2hhcmFjdGVyc0xlZnQiLCJsZW5ndGgiLCJ0IiwibnVtIiwid29yZENvdW50Iiwic3BsaXQiLCJyZWdleCIsIlJlZ0V4cCIsIm1hdGNoIiwid29yZHNMZWZ0Iiwid2luZG93Il0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./src/js/fields/text-limit.js\n");

/***/ }),

/***/ "./src/js/utils/utils.js":
/*!*******************************!*\
  !*** ./src/js/utils/utils.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"eventKey\": () => (/* binding */ eventKey),\n/* harmony export */   \"isEmpty\": () => (/* binding */ isEmpty),\n/* harmony export */   \"toBoolean\": () => (/* binding */ toBoolean)\n/* harmony export */ });\nvar isEmpty = function isEmpty(obj) {\n  return obj && Object.keys(obj).length === 0 && obj.constructor === Object;\n};\nvar toBoolean = function toBoolean(val) {\n  return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;\n};\nvar eventKey = function eventKey(eventName) {\n  var namespace = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;\n\n  if (!namespace) {\n    namespace = Math.random().toString(36).substr(2, 5);\n  }\n\n  return eventName + '.' + namespace;\n};//# sourceURL=[module]\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiLi9zcmMvanMvdXRpbHMvdXRpbHMuanMuanMiLCJtYXBwaW5ncyI6Ijs7Ozs7O0FBQU8sSUFBTUEsT0FBTyxHQUFHLFNBQVZBLE9BQVUsQ0FBU0MsR0FBVCxFQUFjO0VBQ2pDLE9BQU9BLEdBQUcsSUFBSUMsTUFBTSxDQUFDQyxJQUFQLENBQVlGLEdBQVosRUFBaUJHLE1BQWpCLEtBQTRCLENBQW5DLElBQXdDSCxHQUFHLENBQUNJLFdBQUosS0FBb0JILE1BQW5FO0FBQ0gsQ0FGTTtBQUlBLElBQU1JLFNBQVMsR0FBRyxTQUFaQSxTQUFZLENBQVNDLEdBQVQsRUFBYztFQUNuQyxPQUFPLENBQUMsMkJBQTJCQyxJQUEzQixDQUFnQ0QsR0FBaEMsQ0FBRCxJQUF5QyxDQUFDLENBQUNBLEdBQWxEO0FBQ0gsQ0FGTTtBQUlBLElBQU1FLFFBQVEsR0FBRyxTQUFYQSxRQUFXLENBQVNDLFNBQVQsRUFBc0M7RUFBQSxJQUFsQkMsU0FBa0IsdUVBQU4sSUFBTTs7RUFDMUQsSUFBSSxDQUFDQSxTQUFMLEVBQWdCO0lBQ1pBLFNBQVMsR0FBR0MsSUFBSSxDQUFDQyxNQUFMLEdBQWNDLFFBQWQsQ0FBdUIsRUFBdkIsRUFBMkJDLE1BQTNCLENBQWtDLENBQWxDLEVBQXFDLENBQXJDLENBQVo7RUFDSDs7RUFFRCxPQUFPTCxTQUFTLEdBQUcsR0FBWixHQUFrQkMsU0FBekI7QUFDSCxDQU5NIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vc3JjL2pzL3V0aWxzL3V0aWxzLmpzP2Q5ZWUiXSwic291cmNlc0NvbnRlbnQiOlsiZXhwb3J0IGNvbnN0IGlzRW1wdHkgPSBmdW5jdGlvbihvYmopIHtcbiAgICByZXR1cm4gb2JqICYmIE9iamVjdC5rZXlzKG9iaikubGVuZ3RoID09PSAwICYmIG9iai5jb25zdHJ1Y3RvciA9PT0gT2JqZWN0O1xufTtcblxuZXhwb3J0IGNvbnN0IHRvQm9vbGVhbiA9IGZ1bmN0aW9uKHZhbCkge1xuICAgIHJldHVybiAhL14oPzpmKD86YWxzZSk/fG5vP3wwKykkL2kudGVzdCh2YWwpICYmICEhdmFsO1xufTtcblxuZXhwb3J0IGNvbnN0IGV2ZW50S2V5ID0gZnVuY3Rpb24oZXZlbnROYW1lLCBuYW1lc3BhY2UgPSBudWxsKSB7XG4gICAgaWYgKCFuYW1lc3BhY2UpIHtcbiAgICAgICAgbmFtZXNwYWNlID0gTWF0aC5yYW5kb20oKS50b1N0cmluZygzNikuc3Vic3RyKDIsIDUpO1xuICAgIH1cbiAgICBcbiAgICByZXR1cm4gZXZlbnROYW1lICsgJy4nICsgbmFtZXNwYWNlO1xufTtcblxuIl0sIm5hbWVzIjpbImlzRW1wdHkiLCJvYmoiLCJPYmplY3QiLCJrZXlzIiwibGVuZ3RoIiwiY29uc3RydWN0b3IiLCJ0b0Jvb2xlYW4iLCJ2YWwiLCJ0ZXN0IiwiZXZlbnRLZXkiLCJldmVudE5hbWUiLCJuYW1lc3BhY2UiLCJNYXRoIiwicmFuZG9tIiwidG9TdHJpbmciLCJzdWJzdHIiXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./src/js/utils/utils.js\n");

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