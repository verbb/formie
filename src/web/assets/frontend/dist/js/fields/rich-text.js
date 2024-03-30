/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "../../../../node_modules/pell/dist/pell.min.js":
/*!******************************************************!*\
  !*** ../../../../node_modules/pell/dist/pell.min.js ***!
  \******************************************************/
/***/ (function(__unused_webpack_module, exports) {

!function(t,e){ true?e(exports):0}(this,function(t){"use strict";var e=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var n=arguments[e];for(var r in n)Object.prototype.hasOwnProperty.call(n,r)&&(t[r]=n[r])}return t},c="defaultParagraphSeparator",l="formatBlock",a=function(t,e,n){return t.addEventListener(e,n)},s=function(t,e){return t.appendChild(e)},d=function(t){return document.createElement(t)},n=function(t){return document.queryCommandState(t)},f=function(t){var e=1<arguments.length&&void 0!==arguments[1]?arguments[1]:null;return document.execCommand(t,!1,e)},p={bold:{icon:"<b>B</b>",title:"Bold",state:function(){return n("bold")},result:function(){return f("bold")}},italic:{icon:"<i>I</i>",title:"Italic",state:function(){return n("italic")},result:function(){return f("italic")}},underline:{icon:"<u>U</u>",title:"Underline",state:function(){return n("underline")},result:function(){return f("underline")}},strikethrough:{icon:"<strike>S</strike>",title:"Strike-through",state:function(){return n("strikeThrough")},result:function(){return f("strikeThrough")}},heading1:{icon:"<b>H<sub>1</sub></b>",title:"Heading 1",result:function(){return f(l,"<h1>")}},heading2:{icon:"<b>H<sub>2</sub></b>",title:"Heading 2",result:function(){return f(l,"<h2>")}},paragraph:{icon:"&#182;",title:"Paragraph",result:function(){return f(l,"<p>")}},quote:{icon:"&#8220; &#8221;",title:"Quote",result:function(){return f(l,"<blockquote>")}},olist:{icon:"&#35;",title:"Ordered List",result:function(){return f("insertOrderedList")}},ulist:{icon:"&#8226;",title:"Unordered List",result:function(){return f("insertUnorderedList")}},code:{icon:"&lt;/&gt;",title:"Code",result:function(){return f(l,"<pre>")}},line:{icon:"&#8213;",title:"Horizontal Line",result:function(){return f("insertHorizontalRule")}},link:{icon:"&#128279;",title:"Link",result:function(){var t=window.prompt("Enter the link URL");t&&f("createLink",t)}},image:{icon:"&#128247;",title:"Image",result:function(){var t=window.prompt("Enter the image URL");t&&f("insertImage",t)}}},m={actionbar:"pell-actionbar",button:"pell-button",content:"pell-content",selected:"pell-button-selected"},r=function(n){var t=n.actions?n.actions.map(function(t){return"string"==typeof t?p[t]:p[t.name]?e({},p[t.name],t):t}):Object.keys(p).map(function(t){return p[t]}),r=e({},m,n.classes),i=n[c]||"div",o=d("div");o.className=r.actionbar,s(n.element,o);var u=n.element.content=d("div");return u.contentEditable=!0,u.className=r.content,u.oninput=function(t){var e=t.target.firstChild;e&&3===e.nodeType?f(l,"<"+i+">"):"<br>"===u.innerHTML&&(u.innerHTML=""),n.onChange(u.innerHTML)},u.onkeydown=function(t){var e;"Enter"===t.key&&"blockquote"===(e=l,document.queryCommandValue(e))&&setTimeout(function(){return f(l,"<"+i+">")},0)},s(n.element,u),t.forEach(function(t){var e=d("button");if(e.className=r.button,e.innerHTML=t.icon,e.title=t.title,e.setAttribute("type","button"),e.onclick=function(){return t.result()&&u.focus()},t.state){var n=function(){return e.classList[t.state()?"add":"remove"](r.selected)};a(u,"keyup",n),a(u,"mouseup",n),a(e,"click",n)}s(o,e)}),n.styleWithCSS&&f("styleWithCSS"),f(c,i),n.element},i={exec:f,init:r};t.exec=f,t.init=r,t.default=i,Object.defineProperty(t,"__esModule",{value:!0})});


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
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!************************************!*\
  !*** ./src/js/fields/rich-text.js ***!
  \************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "FormieRichText": () => (/* binding */ FormieRichText)
/* harmony export */ });
/* harmony import */ var pell__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pell */ "../../../../node_modules/pell/dist/pell.min.js");
/* harmony import */ var pell__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(pell__WEBPACK_IMPORTED_MODULE_0__);
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }


var FormieRichText = /*#__PURE__*/function () {
  function FormieRichText() {
    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

    _classCallCheck(this, FormieRichText);

    this.$form = settings.$form;
    this.form = this.$form.form;
    this.$field = settings.$field.querySelector('textarea');
    this.$container = settings.$field.querySelector('[data-rich-text]');
    this.scriptId = 'FORMIE_FONT_AWESOME_SCRIPT';
    this.defaultParagraphSeparator = 'p';
    this.buttons = settings.buttons;

    if (this.$field && this.$container) {
      this.initEditor();
    } else {
      console.error('Unable to find rich text field “[data-rich-text]”');
    }
  }

  _createClass(FormieRichText, [{
    key: "getButtons",
    value: function getButtons() {
      var buttonDefinitions = [{
        name: 'bold',
        icon: '<i class="far fa-bold"></i>'
      }, {
        name: 'italic',
        icon: '<i class="far fa-italic"></i>'
      }, {
        name: 'underline',
        icon: '<i class="far fa-underline"></i>'
      }, {
        name: 'strikethrough',
        icon: '<i class="far fa-strikethrough"></i>'
      }, {
        name: 'heading1',
        icon: '<i class="far fa-h1"></i>'
      }, {
        name: 'heading2',
        icon: '<i class="far fa-h2"></i>'
      }, {
        name: 'paragraph',
        icon: '<i class="far fa-paragraph"></i>'
      }, {
        name: 'quote',
        icon: '<i class="far fa-quote-right"></i>'
      }, {
        name: 'olist',
        icon: '<i class="far fa-list-ol"></i>'
      }, {
        name: 'ulist',
        icon: '<i class="far fa-list-ul"></i>'
      }, {
        name: 'code',
        icon: '<i class="far fa-code"></i>'
      }, {
        name: 'line',
        icon: '<i class="far fa-horizontal-rule"></i>'
      }, {
        name: 'link',
        icon: '<i class="far fa-link"></i>'
      }, {
        name: 'image',
        icon: '<i class="far fa-image"></i>'
      }, {
        name: 'alignleft',
        icon: '<i class="far fa-align-left"></i>',
        title: 'Align Left',
        result: function result() {
          return (0,pell__WEBPACK_IMPORTED_MODULE_0__.exec)('justifyLeft', '');
        }
      }, {
        name: 'aligncenter',
        icon: '<i class="far fa-align-center"></i>',
        title: 'Align Center',
        result: function result() {
          return (0,pell__WEBPACK_IMPORTED_MODULE_0__.exec)('justifyCenter', '');
        }
      }, {
        name: 'alignright',
        icon: '<i class="far fa-align-right"></i>',
        title: 'Align Right',
        result: function result() {
          return (0,pell__WEBPACK_IMPORTED_MODULE_0__.exec)('justifyRight', '');
        }
      }, {
        name: 'clear',
        icon: '<i class="far fa-eraser"></i>',
        title: 'Clear',
        result: function result() {
          if (window.getSelection().toString()) {
            var linesToDelete = window.getSelection().toString().split('\n').join('<br>');
            (0,pell__WEBPACK_IMPORTED_MODULE_0__.exec)('formatBlock', '<p>');
            document.execCommand('insertHTML', false, linesToDelete);
          } else {
            (0,pell__WEBPACK_IMPORTED_MODULE_0__.exec)('formatBlock', '<p>');
          }
        }
      }];

      if (!this.buttons) {
        this.buttons = ['bold', 'italic'];
      }

      var buttons = [];
      this.buttons.forEach(function (button) {
        var found = buttonDefinitions.find(function (element) {
          return element.name === button;
        });

        if (found) {
          buttons.push(found);
        }
      });
      return buttons;
    }
  }, {
    key: "initEditor",
    value: function initEditor() {
      var _this = this;

      // Assign this instance to the field's DOM, so it can be accessed by third parties
      this.$field.richText = this; // Load in FontAwesome, for better icons. Only load once though

      if (!document.getElementById(this.scriptId)) {
        var $script = document.createElement('script');
        $script.src = 'https://kit.fontawesome.com/bfee7f35b7.js';
        $script.id = this.scriptId;
        $script.defer = true;
        $script.async = true;
        $script.setAttribute('crossorigin', 'anonymous');
        document.body.appendChild($script);
      }

      var options = {
        element: this.$container,
        defaultParagraphSeparator: this.defaultParagraphSeparator,
        styleWithCSS: true,
        actions: this.getButtons(),
        onChange: function onChange(html) {
          // catch "empty" HTML if we're using a placeholder
          if (_this.$field.placeholder && html === "<".concat(_this.defaultParagraphSeparator, "><br></").concat(_this.defaultParagraphSeparator, ">")) {
            _this.$field.textContent = '';
            _this.editor.content.innerHTML = '';
          } else {
            _this.$field.textContent = html;
          } // Fire a custom event on the input


          _this.$field.dispatchEvent(new CustomEvent('populate', {
            bubbles: true
          }));
        },
        classes: {
          actionbar: 'fui-rich-text-toolbar',
          button: 'fui-rich-text-button',
          content: 'fui-input fui-rich-text-content',
          selected: 'fui-rich-text-selected'
        }
      }; // Emit an "beforeInit" event. This can directly modify the `options` param

      var beforeInitEvent = new CustomEvent('beforeInit', {
        bubbles: true,
        detail: {
          richText: this,
          options: options
        }
      });
      this.$field.dispatchEvent(beforeInitEvent); // save the defaultParagraphSeparator again, if it changed

      this.defaultParagraphSeparator = options.defaultParagraphSeparator || this.defaultParagraphSeparator;
      this.editor = (0,pell__WEBPACK_IMPORTED_MODULE_0__.init)(options); // Populate any values initially set

      this.editor.content.innerHTML = this.$field.textContent; // Populate placeholder if set

      if (this.$field.placeholder) {
        this.editor.content.setAttribute('data-placeholder', this.$field.placeholder);
      } // Emit an "afterInit" event


      this.$field.dispatchEvent(new CustomEvent('afterInit', {
        bubbles: true,
        detail: {
          richText: this
        }
      }));
    }
  }]);

  return FormieRichText;
}();
window.FormieRichText = FormieRichText;
})();

/******/ })()
;