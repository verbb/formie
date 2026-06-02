import { t as __commonJSMin } from "./chunk-K6L4z4UQ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as getModuleFieldContainers, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
import tokensCss from "#theme/_tokens.css?inline";
import richTextCss from "#theme/fields/_rich-text.css?inline";
//#endregion
//#region ../../node_modules/pell/dist/pell.min.css?inline
var import_pell_min = (/* @__PURE__ */ __commonJSMin(((exports, module) => {
	(function(t, e) {
		"object" == typeof exports && "undefined" != typeof module ? e(exports) : "function" == typeof define && define.amd ? define(["exports"], e) : e(t.pell = {});
	})(exports, function(t) {
		"use strict";
		var e = Object.assign || function(t) {
			for (var e = 1; e < arguments.length; e++) {
				var n = arguments[e];
				for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (t[r] = n[r]);
			}
			return t;
		}, c = "defaultParagraphSeparator", l = "formatBlock", a = function(t, e, n) {
			return t.addEventListener(e, n);
		}, s = function(t, e) {
			return t.appendChild(e);
		}, d = function(t) {
			return document.createElement(t);
		}, n = function(t) {
			return document.queryCommandState(t);
		}, f = function(t) {
			var e = 1 < arguments.length && void 0 !== arguments[1] ? arguments[1] : null;
			return document.execCommand(t, !1, e);
		}, p = {
			bold: {
				icon: "<b>B</b>",
				title: "Bold",
				state: function() {
					return n("bold");
				},
				result: function() {
					return f("bold");
				}
			},
			italic: {
				icon: "<i>I</i>",
				title: "Italic",
				state: function() {
					return n("italic");
				},
				result: function() {
					return f("italic");
				}
			},
			underline: {
				icon: "<u>U</u>",
				title: "Underline",
				state: function() {
					return n("underline");
				},
				result: function() {
					return f("underline");
				}
			},
			strikethrough: {
				icon: "<strike>S</strike>",
				title: "Strike-through",
				state: function() {
					return n("strikeThrough");
				},
				result: function() {
					return f("strikeThrough");
				}
			},
			heading1: {
				icon: "<b>H<sub>1</sub></b>",
				title: "Heading 1",
				result: function() {
					return f(l, "<h1>");
				}
			},
			heading2: {
				icon: "<b>H<sub>2</sub></b>",
				title: "Heading 2",
				result: function() {
					return f(l, "<h2>");
				}
			},
			paragraph: {
				icon: "&#182;",
				title: "Paragraph",
				result: function() {
					return f(l, "<p>");
				}
			},
			quote: {
				icon: "&#8220; &#8221;",
				title: "Quote",
				result: function() {
					return f(l, "<blockquote>");
				}
			},
			olist: {
				icon: "&#35;",
				title: "Ordered List",
				result: function() {
					return f("insertOrderedList");
				}
			},
			ulist: {
				icon: "&#8226;",
				title: "Unordered List",
				result: function() {
					return f("insertUnorderedList");
				}
			},
			code: {
				icon: "&lt;/&gt;",
				title: "Code",
				result: function() {
					return f(l, "<pre>");
				}
			},
			line: {
				icon: "&#8213;",
				title: "Horizontal Line",
				result: function() {
					return f("insertHorizontalRule");
				}
			},
			link: {
				icon: "&#128279;",
				title: "Link",
				result: function() {
					var t = window.prompt("Enter the link URL");
					t && f("createLink", t);
				}
			},
			image: {
				icon: "&#128247;",
				title: "Image",
				result: function() {
					var t = window.prompt("Enter the image URL");
					t && f("insertImage", t);
				}
			}
		}, m = {
			actionbar: "pell-actionbar",
			button: "pell-button",
			content: "pell-content",
			selected: "pell-button-selected"
		}, r = function(n) {
			var t = n.actions ? n.actions.map(function(t) {
				return "string" == typeof t ? p[t] : p[t.name] ? e({}, p[t.name], t) : t;
			}) : Object.keys(p).map(function(t) {
				return p[t];
			}), r = e({}, m, n.classes), i = n[c] || "div", o = d("div");
			o.className = r.actionbar, s(n.element, o);
			var u = n.element.content = d("div");
			return u.contentEditable = !0, u.className = r.content, u.oninput = function(t) {
				var e = t.target.firstChild;
				e && 3 === e.nodeType ? f(l, "<" + i + ">") : "<br>" === u.innerHTML && (u.innerHTML = ""), n.onChange(u.innerHTML);
			}, u.onkeydown = function(t) {
				var e;
				"Enter" === t.key && "blockquote" === (e = l, document.queryCommandValue(e)) && setTimeout(function() {
					return f(l, "<" + i + ">");
				}, 0);
			}, s(n.element, u), t.forEach(function(t) {
				var e = d("button");
				if (e.className = r.button, e.innerHTML = t.icon, e.title = t.title, e.setAttribute("type", "button"), e.onclick = function() {
					return t.result() && u.focus();
				}, t.state) {
					var n = function() {
						return e.classList[t.state() ? "add" : "remove"](r.selected);
					};
					a(u, "keyup", n), a(u, "mouseup", n), a(e, "click", n);
				}
				s(o, e);
			}), n.styleWithCSS && f("styleWithCSS"), f(c, i), n.element;
		}, i = {
			exec: f,
			init: r
		};
		t.exec = f, t.init = r, t.default = i, Object.defineProperty(t, "__esModule", { value: !0 });
	});
})))();
var pell_min_default = ".pell{border:1px solid #0a0a0a1a}.pell,.pell-content{box-sizing:border-box}.pell-content{outline:0;height:300px;padding:10px;overflow-y:auto}.pell-actionbar{background-color:#fff;border-bottom:1px solid #0a0a0a1a}.pell-button{cursor:pointer;vertical-align:bottom;background-color:#0000;border:none;outline:0;width:30px;height:30px}.pell-button-selected{background-color:#f0f0f0}";
//#endregion
//#region src/icons/rich-text/aligncenter.svg?raw
var aligncenter_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M7 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M7 18h10\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/alignleft.svg?raw
var alignleft_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M4 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M4 18h10\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/alignright.svg?raw
var alignright_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M10 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M10 18h10\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/bold.svg?raw
var bold_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M7 6h6a3 3 0 0 1 0 6H7z\" />\n  <path d=\"M7 12h7a3 3 0 0 1 0 6H7z\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/clear.svg?raw
var clear_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"m4 20 8-8\" />\n  <path d=\"m12 12 7-7\" />\n  <path d=\"m5 15-2-2a2 2 0 0 1 0-2.83L9.17 4a2 2 0 0 1 2.83 0l4 4\" />\n  <path d=\"M16 20H8\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/code.svg?raw
var code_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"m9 18-6-6 6-6\" />\n  <path d=\"m15 6 6 6-6 6\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/heading1.svg?raw
var heading1_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">H1</text>\n</svg>\n";
//#endregion
//#region src/icons/rich-text/heading2.svg?raw
var heading2_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">H2</text>\n</svg>\n";
//#endregion
//#region src/icons/rich-text/image.svg?raw
var image_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <rect x=\"3\" y=\"5\" width=\"18\" height=\"14\" rx=\"2\" />\n  <path d=\"m8 13 3-3 5 5\" />\n  <path d=\"m13 12 2-2 4 4\" />\n  <circle cx=\"8.5\" cy=\"9.5\" r=\"1\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/italic.svg?raw
var italic_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M13 6h6\" />\n  <path d=\"M5 18h6\" />\n  <path d=\"M14 6 10 18\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/line.svg?raw
var line_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 12h16\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/link.svg?raw
var link_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 13a5 5 0 0 0 7.07 0l1.41-1.41a5 5 0 0 0-7.07-7.07L10 5\" />\n  <path d=\"M14 11a5 5 0 0 0-7.07 0L5.5 12.43a5 5 0 0 0 7.07 7.07L14 19\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/olist.svg?raw
var olist_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 6h10\" />\n  <path d=\"M10 12h10\" />\n  <path d=\"M10 18h10\" />\n  <path d=\"M4 6h.01\" />\n  <path d=\"M4 12h.01\" />\n  <path d=\"M4 18h.01\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/paragraph.svg?raw
var paragraph_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">P</text>\n</svg>\n";
//#endregion
//#region src/icons/rich-text/quote.svg?raw
var quote_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M8 10H6a2 2 0 0 0-2 2v2h4v-4z\" />\n  <path d=\"M18 10h-2a2 2 0 0 0-2 2v2h4v-4z\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/strikethrough.svg?raw
var strikethrough_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M6 12h12\" />\n  <path d=\"M9 7a3 3 0 0 1 6 0c0 4-6 2-6 6a3 3 0 0 0 6 0\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/ulist.svg?raw
var ulist_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 6h10\" />\n  <path d=\"M10 12h10\" />\n  <path d=\"M10 18h10\" />\n  <circle cx=\"4\" cy=\"6\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n  <circle cx=\"4\" cy=\"12\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n  <circle cx=\"4\" cy=\"18\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n</svg>\n";
//#endregion
//#region src/icons/rich-text/underline.svg?raw
var underline_default = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M8 6v5a4 4 0 0 0 8 0V6\" />\n  <path d=\"M6 20h12\" />\n</svg>\n";
//#endregion
//#region src/js/modules/fields/rich-text.ts
var CONTAINER_SELECTOR = "[data-formie-rich-text]";
var FIELD_SELECTOR = "[data-formie-field], [data-formie-field-handle]";
var INPUT_SELECTOR = "textarea[data-formie-multi-line-text-input]";
var MODULE_ID = "rich-text";
ensureModuleStyles(MODULE_ID, [
	tokensCss,
	pell_min_default,
	richTextCss
]);
var RICH_TEXT_ICONS = {
	bold: bold_default,
	italic: italic_default,
	underline: underline_default,
	strikethrough: strikethrough_default,
	heading1: heading1_default,
	heading2: heading2_default,
	paragraph: paragraph_default,
	quote: quote_default,
	olist: olist_default,
	ulist: ulist_default,
	code: code_default,
	line: line_default,
	link: link_default,
	image: image_default,
	alignleft: alignleft_default,
	aligncenter: aligncenter_default,
	alignright: alignright_default,
	clear: clear_default
};
function hasRichTextField(target) {
	if (target.matches(FIELD_SELECTOR)) return !!target.querySelector(CONTAINER_SELECTOR) && !!target.querySelector(INPUT_SELECTOR);
	return Array.from(target.querySelectorAll(FIELD_SELECTOR)).some((field) => {
		return !!field.querySelector(CONTAINER_SELECTOR) && !!field.querySelector(INPUT_SELECTOR);
	});
}
function getActionDefinitions() {
	return [
		{
			name: "bold",
			icon: RICH_TEXT_ICONS.bold
		},
		{
			name: "italic",
			icon: RICH_TEXT_ICONS.italic
		},
		{
			name: "underline",
			icon: RICH_TEXT_ICONS.underline
		},
		{
			name: "strikethrough",
			icon: RICH_TEXT_ICONS.strikethrough
		},
		{
			name: "heading1",
			icon: RICH_TEXT_ICONS.heading1
		},
		{
			name: "heading2",
			icon: RICH_TEXT_ICONS.heading2
		},
		{
			name: "paragraph",
			icon: RICH_TEXT_ICONS.paragraph
		},
		{
			name: "quote",
			icon: RICH_TEXT_ICONS.quote
		},
		{
			name: "olist",
			icon: RICH_TEXT_ICONS.olist
		},
		{
			name: "ulist",
			icon: RICH_TEXT_ICONS.ulist
		},
		{
			name: "code",
			icon: RICH_TEXT_ICONS.code
		},
		{
			name: "line",
			icon: RICH_TEXT_ICONS.line
		},
		{
			name: "link",
			icon: RICH_TEXT_ICONS.link
		},
		{
			name: "image",
			icon: RICH_TEXT_ICONS.image
		},
		{
			name: "alignleft",
			icon: RICH_TEXT_ICONS.alignleft,
			title: "Align Left",
			result: () => (0, import_pell_min.exec)("justifyLeft", "")
		},
		{
			name: "aligncenter",
			icon: RICH_TEXT_ICONS.aligncenter,
			title: "Align Center",
			result: () => (0, import_pell_min.exec)("justifyCenter", "")
		},
		{
			name: "alignright",
			icon: RICH_TEXT_ICONS.alignright,
			title: "Align Right",
			result: () => (0, import_pell_min.exec)("justifyRight", "")
		},
		{
			name: "clear",
			icon: RICH_TEXT_ICONS.clear,
			title: "Clear",
			result: () => {
				const selection = window.getSelection()?.toString() || "";
				if (selection) {
					const linesToDelete = selection.split("\n").join("<br>");
					(0, import_pell_min.exec)("formatBlock", "<p>");
					document.execCommand("insertHTML", false, linesToDelete);
					return;
				}
				(0, import_pell_min.exec)("formatBlock", "<p>");
			}
		}
	];
}
function getActions(buttons) {
	const selectedButtons = buttons?.length ? buttons : ["bold", "italic"];
	const definitions = getActionDefinitions();
	return selectedButtons.map((button) => {
		return definitions.find((definition) => {
			return definition.name === button;
		});
	}).filter((definition) => {
		return !!definition;
	});
}
function initRichTextField(container, input, options) {
	const pellOptions = {
		element: container,
		defaultParagraphSeparator: "p",
		styleWithCSS: true,
		actions: getActions(options.buttons),
		onChange: (html) => {
			input.value = input.placeholder && html === "<p><br></p>" ? "" : html;
			input.dispatchEvent(new Event("input", { bubbles: true }));
			dispatchFieldEvent(input, MODULE_ID, "populate", {
				richText: input,
				value: input.value
			});
		},
		classes: {
			actionbar: "formie-rich-text-toolbar",
			button: "formie-rich-text-button",
			content: "formie-input formie-rich-text-content",
			selected: "formie-rich-text-selected"
		}
	};
	dispatchFieldEvent(input, MODULE_ID, "before-init", {
		richText: input,
		options: pellOptions
	});
	const editor = (0, import_pell_min.init)(pellOptions);
	input.richText = editor;
	editor.content.innerHTML = input.value || "";
	if (input.placeholder) editor.content.setAttribute("data-placeholder", input.placeholder);
	dispatchFieldEvent(input, MODULE_ID, "after-init", { richText: editor });
	return () => {
		container.innerHTML = "";
		delete input.richText;
	};
}
var richTextModule = {
	id: MODULE_ID,
	kind: "field",
	match: (ctx) => {
		return ctx.target instanceof HTMLElement && hasRichTextField(ctx.target);
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		const cleanups = getModuleFieldContainers(ctx).map((field) => {
			const container = field.querySelector(CONTAINER_SELECTOR);
			const input = field.querySelector(INPUT_SELECTOR);
			if (!(container instanceof HTMLElement) || !(input instanceof HTMLTextAreaElement)) return () => {};
			return initRichTextField(container, input, options);
		});
		await ctx.emit("formie:module:rich-text:init", { count: cleanups.length });
		return { destroy: () => {
			cleanups.forEach((cleanup) => {
				cleanup();
			});
			ctx.emit("formie:module:rich-text:destroy", {});
		} };
	}
};
//#endregion
export { richTextModule };
