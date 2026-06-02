import { t as __commonJSMin } from "./chunk-K6L4z4UQ.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as getModuleFieldContainers, t as dispatchFieldEvent } from "./shared-DC6_1u8X.js";
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
//#region src/css/theme/_tokens.css?inline
var _tokens_default = "@layer formie-theme{.formie-form{--formie-font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;--formie-font-size-xs:.75rem;--formie-font-size-sm:.875rem;--formie-font-size-base:1rem;--formie-font-size-lg:1.125rem;--formie-font-size-xl:1.375rem;--formie-font-size-2xl:1.75rem;--formie-font-weight-normal:400;--formie-font-weight-medium:500;--formie-font-weight-semibold:600;--formie-font-weight-bold:700;--formie-line-height-tight:1.25;--formie-line-height-base:1.5;--formie-line-height-relaxed:1.4;--formie-letter-spacing-tight:-.02em;--formie-space-1:.25rem;--formie-space-1-5:.375rem;--formie-space-2:.5rem;--formie-space-2-5:.625rem;--formie-space-3:.75rem;--formie-space-3-5:.875rem;--formie-space-4:1rem;--formie-space-4-5:1.125rem;--formie-space-5:1.25rem;--formie-space-5-5:1.375rem;--formie-space-6:1.5rem;--formie-space-7:1.75rem;--formie-space-8:2rem;--formie-space-9:2.25rem;--formie-space-10:2.5rem;--formie-space-11:2.75rem;--formie-space-12:3rem;--formie-radius-sm:.25rem;--formie-radius-md:.375rem;--formie-radius-lg:.5rem;--formie-radius-full:999px;--formie-border-width:1px;--formie-black:#000;--formie-white:#fff;--formie-neutral-50:#f8fafc;--formie-neutral-100:#f1f5f9;--formie-neutral-200:#e2e8f0;--formie-neutral-300:#cbd5e1;--formie-neutral-400:#94a3b8;--formie-neutral-500:#64748b;--formie-neutral-600:#475569;--formie-neutral-700:#334155;--formie-neutral-800:#1e293b;--formie-neutral-900:#0f172a;--formie-neutral-950:#020617;--formie-primary-50:#e8ecfc;--formie-primary-100:#d2d9f9;--formie-primary-200:#a4b3f4;--formie-primary-300:#778dee;--formie-primary-400:#4967e9;--formie-primary-500:#1c41e3;--formie-primary-600:#1634b6;--formie-primary-700:#112788;--formie-primary-800:#0b1a5b;--formie-primary-900:#060d2d;--formie-primary-950:#040920;--formie-danger-50:#fef2f2;--formie-danger-100:#fee2e2;--formie-danger-200:#fecaca;--formie-danger-300:#fca5a5;--formie-danger-400:#f87171;--formie-danger-500:#ef4444;--formie-danger-600:#dc2626;--formie-danger-700:#b91c1c;--formie-danger-800:#991b1b;--formie-danger-900:#7f1d1d;--formie-danger-950:#450a0a;--formie-success-50:#f0fdf4;--formie-success-100:#dcfce7;--formie-success-200:#bbf7d0;--formie-success-300:#86efac;--formie-success-400:#4ade80;--formie-success-500:#22c55e;--formie-success-600:#16a34a;--formie-success-700:#15803d;--formie-success-800:#166534;--formie-success-900:#14532d;--formie-success-950:#052e16;--formie-color-background:var(--formie-white);--formie-color-surface:var(--formie-white);--formie-color-surface-subtle:var(--formie-neutral-50);--formie-color-surface-muted:var(--formie-neutral-100);--formie-color-text:var(--formie-neutral-700);--formie-color-text-muted:var(--formie-neutral-500);--formie-color-heading:var(--formie-neutral-900);--formie-color-border:var(--formie-neutral-300);--formie-color-border-soft:var(--formie-neutral-200);--formie-color-primary:var(--formie-primary-400);--formie-color-primary-hover:var(--formie-primary-500);--formie-color-primary-border:var(--formie-primary-500);--formie-color-primary-soft:var(--formie-primary-100);--formie-color-focus-ring:var(--formie-primary-300);--formie-color-danger:var(--formie-danger-500);--formie-color-danger-soft:var(--formie-danger-50);--formie-color-danger-dark:var(--formie-danger-900);--formie-color-success:var(--formie-success-500);--formie-color-success-soft:var(--formie-success-50);--formie-color-success-dark:var(--formie-success-900);--formie-color-button-text:var(--formie-color-surface);--formie-focus-ring-border-color:var(--formie-color-focus-ring);--formie-shadow-focus:0 0 0 3px #778dee73;--formie-shadow-danger-focus:0 0 0 3px #f8b4b473;--formie-title-form-size:1.4rem;--formie-body-size:.9375rem;--formie-gap-form:0;--formie-gap-form-header:var(--formie-space-4);--formie-gap-form-messages:var(--formie-space-4);--formie-gap-form-navigation:var(--formie-space-4);--formie-gap-form-body:0;--formie-gap-form-footer:var(--formie-space-4);--formie-message-padding:var(--formie-space-4);--formie-message-margin-bottom:var(--formie-space-4);--formie-message-size:var(--formie-font-size-sm);--formie-message-line-height:var(--formie-line-height-relaxed);--formie-button-border:var(--formie-border-width) solid var(--formie-color-border);--formie-button-border-hover:var(--formie-button-secondary-border-hover);--formie-button-border-radius:var(--formie-radius-sm);--formie-button-background:var(--formie-neutral-100);--formie-button-background-hover:var(--formie-neutral-200);--formie-button-text-color:var(--formie-color-heading);--formie-button-color:var(--formie-button-text-color);--formie-button-line-height:var(--formie-line-height-tight);--formie-button-font-weight:var(--formie-font-weight-medium);--formie-button-min-height:var(--formie-space-10);--formie-button-padding-y:var(--formie-space-2);--formie-button-padding-x:var(--formie-space-4);--formie-button-font-size:var(--formie-font-size-sm);--formie-button-gap:var(--formie-space-2);--formie-button-icon-size:.9375rem;--formie-button-icon-button-size:1.875rem;--formie-button-icon-border-radius:var(--formie-radius-full);--formie-button-icon-background:var(--formie-neutral-100);--formie-button-icon-background-hover:var(--formie-neutral-200);--formie-button-icon-border:var(--formie-border-width) solid var(--formie-neutral-300);--formie-button-icon-border-hover:var(--formie-border-width) solid var(--formie-neutral-400);--formie-button-icon-color:var(--formie-neutral-950);--formie-button-opacity-disabled:.7;--formie-button-shadow-focus:0 0 0 3px var(--formie-color-border-soft);--formie-icon-mask-plus:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z'/%3E%3C/svg%3E\");--formie-icon-mask-arrow-left:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z'/%3E%3C/svg%3E\");--formie-icon-mask-arrow-right:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M278.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z'/%3E%3C/svg%3E\");--formie-icon-mask-close:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='%23000' d='M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z'/%3E%3C/svg%3E\");--formie-button-primary-background:var(--formie-color-primary);--formie-button-primary-background-hover:var(--formie-color-primary-hover);--formie-button-primary-text-color:var(--formie-white);--formie-button-primary-border:var(--formie-border-width) solid transparent;--formie-button-primary-border-hover:var(--formie-border-width) solid var(--formie-color-primary-hover);--formie-button-primary-shadow-focus:0 0 0 3px var(--formie-primary-300);--formie-button-secondary-border:var(--formie-border-width) solid var(--formie-color-border);--formie-button-secondary-border-hover:var(--formie-button-secondary-border);--formie-button-secondary-background:var(--formie-color-surface);--formie-button-secondary-background-hover:var(--formie-neutral-100);--formie-button-secondary-text-color:var(--formie-color-heading);--formie-button-ghost-border:var(--formie-border-width) solid transparent;--formie-button-ghost-border-hover:var(--formie-button-ghost-border);--formie-button-ghost-background:transparent;--formie-button-ghost-background-hover:var(--formie-neutral-100);--formie-button-ghost-text-color:var(--formie-color-heading);--formie-button-ghost-shadow-focus:var(--formie-button-shadow-focus);--formie-button-link-text-color:var(--formie-color-primary);--formie-button-link-text-color-hover:var(--formie-color-primary-hover);--formie-tab-padding-y:var(--formie-space-2);--formie-tab-padding-x:var(--formie-space-4);--formie-tab-font-size:var(--formie-font-size-sm);--formie-gap-tabs:var(--formie-space-4);--formie-progress-height:1.2rem;--formie-progress-padding:var(--formie-space-4);--formie-progress-size:.8rem;--formie-loading-size:var(--formie-space-4);--formie-loading-margin-top:calc(var(--formie-loading-size) * -.5);--formie-loading-margin-left:calc(var(--formie-loading-size) * -.5);--formie-loading-border-width:2px;--formie-loading-animation:loading .5s infinite linear;--formie-loading-left:50%;--formie-loading-top:50%;--formie-loading-z-index:1;--formie-gap-pages:0;--formie-gap-page:var(--formie-space-4);--formie-gap-page-container:0;--formie-gap-page-header:var(--formie-space-4);--formie-gap-page-body:var(--formie-space-4);--formie-gap-page-footer:var(--formie-space-4);--formie-gap-page-buttons:var(--formie-space-4);--formie-title-page-size:var(--formie-font-size-lg);--formie-gap-rows:var(--formie-space-4);--formie-gap-row:var(--formie-space-4);--formie-gap-subfield-rows:var(--formie-space-2);--formie-gap-subfield-row:var(--formie-space-2);--formie-gap-nested-field-rows:var(--formie-space-2);--formie-gap-nested-field-row:var(--formie-space-2);--formie-subfield-row-column-min-width:12rem;--formie-nested-field-row-column-min-width:16rem;--formie-gap-errors:var(--formie-space-2);--formie-gap-field-errors:var(--formie-space-2);--formie-label-size:var(--formie-font-size-sm);--formie-meta-size:var(--formie-font-size-sm);--formie-control-height:2.375rem;--formie-control-padding-y:var(--formie-space-2);--formie-control-padding-x:var(--formie-space-3);--formie-control-font-size:var(--formie-font-size-sm);--formie-textarea-min-height:9rem;--formie-select-indicator-size:1.4rem;--formie-list-indent:var(--formie-space-5);--formie-link-underline-offset:.15em;--formie-gap-field:var(--formie-space-2);--formie-gap-field-layout:var(--formie-space-2);--formie-gap-field-content:var(--formie-space-2);--formie-gap-field-control:var(--formie-space-2);--formie-gap-options:var(--formie-space-2);--formie-summary-padding:var(--formie-space-4);--formie-gap-summary:var(--formie-space-4);--formie-file-summary-padding:var(--formie-space-4);--formie-gap-file-summary:var(--formie-space-3);--formie-rich-text-min-height:12rem;--formie-signature-width:100%;--formie-signature-height:8rem;--formie-signature-background:var(--formie-color-surface-subtle);--formie-signature-border:1px solid var(--formie-color-border);--formie-signature-border-radius:var(--formie-radius-sm);--formie-signature-remove-button-top:0;--formie-signature-remove-button-right:-14px;--formie-signature-remove-button-transform:translate(0, -50%);--formie-check-font-size:var(--formie-font-size-sm);--formie-check-line-height:var(--formie-line-height-base);--formie-check-margin-bottom:var(--formie-space-2);--formie-check-margin-right:var(--formie-space-4);--formie-check-background-color:var(--formie-color-surface-muted);--formie-check-size:var(--formie-space-4);--formie-check-label-padding-left:var(--formie-space-6);--formie-check-label-line-height:var(--formie-space-6);--formie-check-label-top:.3125rem;--formie-check-label-transition:all .15s cubic-bezier(.4, 0, .2, 1);--formie-check-label-background-color:var(--formie-color-surface);--formie-check-check-border-radius:2px;--formie-check-check-background-image:url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3E%3Cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3E%3C/svg%3E\");--formie-check-check-background-size:8px auto;--formie-check-radio-border-radius:50%;--formie-check-radio-background-image:url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23fff'/%3E%3C/svg%3E\");--formie-check-radio-background-size:8px auto;--formie-group-border:1px solid var(--formie-color-border);--formie-group-border-radius:var(--formie-radius-sm);--formie-group-padding:var(--formie-space-4);--formie-repeater-add-button-padding-left:var(--formie-space-8);--formie-repeater-add-button-icon-mask:var(--formie-icon-mask-plus);--formie-repeater-add-button-height:14px;--formie-repeater-add-button-width:14px;--formie-repeater-add-button-left:var(--formie-space-3);--formie-repeater-remove-button-top:0;--formie-repeater-remove-button-right:-14px;--formie-repeater-remove-button-transform:translate(0, -50%);--formie-table-width:100%;--formie-table-margin-bottom:1rem;--formie-table-border-collapse:collapse;--formie-table-row-padding:.2rem;--formie-table-th-text-align:inherit;--formie-table-th-font-size:.75rem;--formie-table-th-font-weight:600;--formie-table-add-button-padding-left:var(--formie-space-8);--formie-table-add-button-icon-mask:var(--formie-icon-mask-plus);--formie-table-add-button-height:14px;--formie-table-add-button-width:14px;--formie-table-add-button-left:var(--formie-space-3);--formie-table-remove-button-top:0;--formie-table-remove-button-right:-14px;--formie-table-remove-button-transform:translate(0, -50%);font-family:var(--formie-font-family);font-size:var(--formie-body-size);line-height:var(--formie-line-height-base);color:var(--formie-color-text);-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}}";
//#endregion
//#region src/css/theme/fields/_rich-text.css?inline
var _rich_text_default = "@layer formie-theme{.formie-rich-text{border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;padding:0;transition:border-color .15s,box-shadow .15s,background-color .15s;overflow:hidden}.formie-rich-text:focus-within{border-color:var(--formie-color-focus-ring);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-rich-text{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-rich-text:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-rich-text-toolbar{padding:var(--formie-space-1);border-bottom:var(--formie-border-width) solid var(--formie-color-border);background:#fff;flex-wrap:wrap;align-items:center;gap:0;display:flex;box-shadow:0 1px 2px #1118270f}.formie-rich-text .formie-rich-text-button{width:var(--formie-space-8);height:var(--formie-space-8);border-radius:var(--formie-radius-sm);color:var(--formie-color-heading);font-size:var(--formie-font-size-sm);cursor:pointer;box-shadow:none;background:0 0;border:0;justify-content:center;align-items:center;margin:0;padding:0;line-height:1;transition:background-color .15s,color .15s,box-shadow .15s;display:inline-flex}.formie-rich-text .formie-rich-text-button:hover,.formie-rich-text .formie-rich-text-button.formie-rich-text-selected{background:var(--formie-color-surface-muted)}.formie-rich-text .formie-rich-text-button:focus-visible{box-shadow:0 0 0 2px var(--formie-color-surface), 0 0 0 4px color-mix(in srgb, var(--formie-color-focus-ring) 60%, transparent);outline:0}.formie-rich-text [contenteditable=true]{min-height:var(--formie-rich-text-min-height);padding:var(--formie-space-3) calc(var(--formie-space-3) + var(--formie-space-1) / 2);box-shadow:none;overflow-wrap:anywhere;line-height:var(--formie-line-height-base);color:var(--formie-color-text);background:0 0;border:0;border-radius:0;outline:0}.formie-rich-text [contenteditable=true]>:first-child{margin-top:0}.formie-rich-text [contenteditable=true]>:last-child{margin-bottom:0}.formie-rich-text-content p,.formie-rich-text-content ul,.formie-rich-text-content ol,.formie-rich-text-content blockquote,.formie-rich-text-content dl,.formie-rich-text-content dd,.formie-rich-text-content figure,.formie-rich-text-content hr,.formie-rich-text-content pre{margin:0 0 var(--formie-space-4)}.formie-rich-text-content h1,.formie-rich-text-content h2,.formie-rich-text-content h3,.formie-rich-text-content h4,.formie-rich-text-content h5,.formie-rich-text-content h6{margin:0 0 var(--formie-space-3);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-semibold);line-height:var(--formie-line-height-tight)}.formie-rich-text-content h1{font-size:var(--formie-font-size-2xl)}.formie-rich-text-content h2{font-size:var(--formie-font-size-xl)}.formie-rich-text-content h3{font-size:var(--formie-font-size-lg)}.formie-rich-text-content h4{font-size:var(--formie-font-size-base)}.formie-rich-text-content h5,.formie-rich-text-content h6{font-size:var(--formie-font-size-sm)}.formie-rich-text-content ul,.formie-rich-text-content ol{padding-inline-start:var(--formie-list-indent)}.formie-rich-text-content ul{list-style:outside}.formie-rich-text-content ol{list-style:decimal}.formie-rich-text-content li+li{margin-top:var(--formie-space-1)}.formie-rich-text-content a{color:var(--formie-color-primary);text-underline-offset:var(--formie-link-underline-offset);text-decoration:underline}.formie-rich-text-content blockquote{color:var(--formie-color-text-muted);border-inline-start:4px solid var(--formie-color-border-soft);padding-inline-start:var(--formie-space-4)}.formie-rich-text-content pre{padding:var(--formie-space-4);border-radius:var(--formie-radius-md);background:var(--formie-color-surface-muted);overflow-x:auto}.formie-rich-text-content code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace;font-size:.95em}.formie-rich-text-content :not(pre)>code{border-radius:var(--formie-radius-sm);background:var(--formie-color-surface-muted);padding:.12em .35em}.formie-rich-text-content pre code{background:0 0;border-radius:0;padding:0}.formie-rich-text-content hr{border:0;border-top:var(--formie-border-width) solid var(--formie-color-border);height:0}.formie-rich-text-content img{max-width:100%;height:auto;display:block}.formie-rich-text-content[data-placeholder]:empty:before{content:attr(data-placeholder);color:var(--formie-color-text-muted);pointer-events:none}}";
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
	_tokens_default,
	pell_min_default,
	_rich_text_default
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
