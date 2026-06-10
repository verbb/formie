import { t as e } from "./chunk-3b4jIN3o.js";
import { t } from "./styles-BfoIZwJp.js";
import { r as n, t as r } from "./shared-Bx9s0i0P.js";
//#endregion
//#region ../../node_modules/pell/dist/pell.min.css?inline
var i = (/* @__PURE__ */ e(((e, t) => {
	(function(n, r) {
		typeof e == "object" && t !== void 0 ? r(e) : typeof define == "function" && define.amd ? define(["exports"], r) : r(n.pell = {});
	})(e, function(e) {
		var t = Object.assign || function(e) {
			for (var t = 1; t < arguments.length; t++) {
				var n = arguments[t];
				for (var r in n) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
			}
			return e;
		}, n = "defaultParagraphSeparator", r = "formatBlock", i = function(e, t, n) {
			return e.addEventListener(t, n);
		}, a = function(e, t) {
			return e.appendChild(t);
		}, o = function(e) {
			return document.createElement(e);
		}, s = function(e) {
			return document.queryCommandState(e);
		}, c = function(e) {
			var t = 1 < arguments.length && arguments[1] !== void 0 ? arguments[1] : null;
			return document.execCommand(e, !1, t);
		}, l = {
			bold: {
				icon: "<b>B</b>",
				title: "Bold",
				state: function() {
					return s("bold");
				},
				result: function() {
					return c("bold");
				}
			},
			italic: {
				icon: "<i>I</i>",
				title: "Italic",
				state: function() {
					return s("italic");
				},
				result: function() {
					return c("italic");
				}
			},
			underline: {
				icon: "<u>U</u>",
				title: "Underline",
				state: function() {
					return s("underline");
				},
				result: function() {
					return c("underline");
				}
			},
			strikethrough: {
				icon: "<strike>S</strike>",
				title: "Strike-through",
				state: function() {
					return s("strikeThrough");
				},
				result: function() {
					return c("strikeThrough");
				}
			},
			heading1: {
				icon: "<b>H<sub>1</sub></b>",
				title: "Heading 1",
				result: function() {
					return c(r, "<h1>");
				}
			},
			heading2: {
				icon: "<b>H<sub>2</sub></b>",
				title: "Heading 2",
				result: function() {
					return c(r, "<h2>");
				}
			},
			paragraph: {
				icon: "&#182;",
				title: "Paragraph",
				result: function() {
					return c(r, "<p>");
				}
			},
			quote: {
				icon: "&#8220; &#8221;",
				title: "Quote",
				result: function() {
					return c(r, "<blockquote>");
				}
			},
			olist: {
				icon: "&#35;",
				title: "Ordered List",
				result: function() {
					return c("insertOrderedList");
				}
			},
			ulist: {
				icon: "&#8226;",
				title: "Unordered List",
				result: function() {
					return c("insertUnorderedList");
				}
			},
			code: {
				icon: "&lt;/&gt;",
				title: "Code",
				result: function() {
					return c(r, "<pre>");
				}
			},
			line: {
				icon: "&#8213;",
				title: "Horizontal Line",
				result: function() {
					return c("insertHorizontalRule");
				}
			},
			link: {
				icon: "&#128279;",
				title: "Link",
				result: function() {
					var e = window.prompt("Enter the link URL");
					e && c("createLink", e);
				}
			},
			image: {
				icon: "&#128247;",
				title: "Image",
				result: function() {
					var e = window.prompt("Enter the image URL");
					e && c("insertImage", e);
				}
			}
		}, u = {
			actionbar: "pell-actionbar",
			button: "pell-button",
			content: "pell-content",
			selected: "pell-button-selected"
		}, d = function(e) {
			var s = e.actions ? e.actions.map(function(e) {
				return typeof e == "string" ? l[e] : l[e.name] ? t({}, l[e.name], e) : e;
			}) : Object.keys(l).map(function(e) {
				return l[e];
			}), d = t({}, u, e.classes), f = e[n] || "div", p = o("div");
			p.className = d.actionbar, a(e.element, p);
			var m = e.element.content = o("div");
			return m.contentEditable = !0, m.className = d.content, m.oninput = function(t) {
				var n = t.target.firstChild;
				n && n.nodeType === 3 ? c(r, "<" + f + ">") : m.innerHTML === "<br>" && (m.innerHTML = ""), e.onChange(m.innerHTML);
			}, m.onkeydown = function(e) {
				var t;
				e.key === "Enter" && (t = r, document.queryCommandValue(t)) === "blockquote" && setTimeout(function() {
					return c(r, "<" + f + ">");
				}, 0);
			}, a(e.element, m), s.forEach(function(e) {
				var t = o("button");
				if (t.className = d.button, t.innerHTML = e.icon, t.title = e.title, t.setAttribute("type", "button"), t.onclick = function() {
					return e.result() && m.focus();
				}, e.state) {
					var n = function() {
						return t.classList[e.state() ? "add" : "remove"](d.selected);
					};
					i(m, "keyup", n), i(m, "mouseup", n), i(t, "click", n);
				}
				a(p, t);
			}), e.styleWithCSS && c("styleWithCSS"), c(n, f), e.element;
		}, f = {
			exec: c,
			init: d
		};
		e.exec = c, e.init = d, e.default = f, Object.defineProperty(e, "__esModule", { value: !0 });
	});
})))(), a = ".pell{border:1px solid #0a0a0a1a}.pell,.pell-content{box-sizing:border-box}.pell-content{outline:0;height:300px;padding:10px;overflow-y:auto}.pell-actionbar{background-color:#fff;border-bottom:1px solid #0a0a0a1a}.pell-button{cursor:pointer;vertical-align:bottom;background-color:#0000;border:none;outline:0;width:30px;height:30px}.pell-button-selected{background-color:#f0f0f0}", o = "@layer formie-theme{.formie-form{--formie-font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;--formie-font-size-xs:.75rem;--formie-font-size-sm:.875rem;--formie-font-size-base:1rem;--formie-font-size-lg:1.125rem;--formie-font-size-xl:1.375rem;--formie-font-size-2xl:1.75rem;--formie-font-weight-normal:400;--formie-font-weight-medium:500;--formie-font-weight-semibold:600;--formie-font-weight-bold:700;--formie-line-height-tight:1.25;--formie-line-height-base:1.5;--formie-line-height-relaxed:1.4;--formie-letter-spacing-tight:-.02em;--formie-space-1:.25rem;--formie-space-1-5:.375rem;--formie-space-2:.5rem;--formie-space-2-5:.625rem;--formie-space-3:.75rem;--formie-space-3-5:.875rem;--formie-space-4:1rem;--formie-space-4-5:1.125rem;--formie-space-5:1.25rem;--formie-space-5-5:1.375rem;--formie-space-6:1.5rem;--formie-space-7:1.75rem;--formie-space-8:2rem;--formie-space-9:2.25rem;--formie-space-10:2.5rem;--formie-space-11:2.75rem;--formie-space-12:3rem;--formie-radius-sm:.25rem;--formie-radius-md:.375rem;--formie-radius-lg:.5rem;--formie-radius-full:999px;--formie-border-width:1px;--formie-black:#000;--formie-white:#fff;--formie-neutral-50:#f8fafc;--formie-neutral-100:#f1f5f9;--formie-neutral-200:#e2e8f0;--formie-neutral-300:#cbd5e1;--formie-neutral-400:#94a3b8;--formie-neutral-500:#64748b;--formie-neutral-600:#475569;--formie-neutral-700:#334155;--formie-neutral-800:#1e293b;--formie-neutral-900:#0f172a;--formie-neutral-950:#020617;--formie-primary-50:#e8ecfc;--formie-primary-100:#d2d9f9;--formie-primary-200:#a4b3f4;--formie-primary-300:#778dee;--formie-primary-400:#4967e9;--formie-primary-500:#1c41e3;--formie-primary-600:#1634b6;--formie-primary-700:#112788;--formie-primary-800:#0b1a5b;--formie-primary-900:#060d2d;--formie-primary-950:#040920;--formie-danger-50:#fef2f2;--formie-danger-100:#fee2e2;--formie-danger-200:#fecaca;--formie-danger-300:#fca5a5;--formie-danger-400:#f87171;--formie-danger-500:#ef4444;--formie-danger-600:#dc2626;--formie-danger-700:#b91c1c;--formie-danger-800:#991b1b;--formie-danger-900:#7f1d1d;--formie-danger-950:#450a0a;--formie-success-50:#f0fdf4;--formie-success-100:#dcfce7;--formie-success-200:#bbf7d0;--formie-success-300:#86efac;--formie-success-400:#4ade80;--formie-success-500:#22c55e;--formie-success-600:#16a34a;--formie-success-700:#15803d;--formie-success-800:#166534;--formie-success-900:#14532d;--formie-success-950:#052e16;--formie-color-background:var(--formie-white);--formie-color-surface:var(--formie-white);--formie-color-surface-subtle:var(--formie-neutral-50);--formie-color-surface-muted:var(--formie-neutral-100);--formie-color-text:var(--formie-neutral-700);--formie-color-text-muted:var(--formie-neutral-500);--formie-color-heading:var(--formie-neutral-900);--formie-color-border:var(--formie-neutral-300);--formie-color-border-soft:var(--formie-neutral-200);--formie-color-primary:var(--formie-primary-400);--formie-color-primary-hover:var(--formie-primary-500);--formie-color-primary-border:var(--formie-primary-500);--formie-color-primary-soft:var(--formie-primary-100);--formie-color-focus-ring:var(--formie-primary-300);--formie-color-danger:var(--formie-danger-500);--formie-color-danger-soft:var(--formie-danger-50);--formie-color-danger-dark:var(--formie-danger-900);--formie-color-success:var(--formie-success-500);--formie-color-success-soft:var(--formie-success-50);--formie-color-success-dark:var(--formie-success-900);--formie-color-button-text:var(--formie-color-surface);--formie-focus-ring-border-color:var(--formie-color-focus-ring);--formie-shadow-focus:0 0 0 3px #778dee73;--formie-shadow-danger-focus:0 0 0 3px #f8b4b473;--formie-title-form-size:1.4rem;--formie-body-size:.9375rem;--formie-gap-form:0;--formie-gap-form-header:var(--formie-space-4);--formie-gap-form-messages:var(--formie-space-4);--formie-gap-form-navigation:var(--formie-space-4);--formie-gap-form-body:0;--formie-gap-form-footer:var(--formie-space-4);--formie-message-padding:var(--formie-space-4);--formie-message-margin-bottom:var(--formie-space-4);--formie-message-size:var(--formie-font-size-sm);--formie-message-line-height:var(--formie-line-height-relaxed);--formie-button-border:var(--formie-border-width) solid var(--formie-color-border);--formie-button-border-hover:var(--formie-button-secondary-border-hover);--formie-button-border-radius:var(--formie-radius-sm);--formie-button-background:var(--formie-neutral-100);--formie-button-background-hover:var(--formie-neutral-200);--formie-button-text-color:var(--formie-color-heading);--formie-button-color:var(--formie-button-text-color);--formie-button-line-height:var(--formie-line-height-tight);--formie-button-font-weight:var(--formie-font-weight-medium);--formie-button-min-height:var(--formie-space-10);--formie-button-padding-y:var(--formie-space-2);--formie-button-padding-x:var(--formie-space-4);--formie-button-font-size:var(--formie-font-size-sm);--formie-button-gap:var(--formie-space-2);--formie-button-icon-size:.9375rem;--formie-button-icon-button-size:1.875rem;--formie-button-icon-border-radius:var(--formie-radius-full);--formie-button-icon-background:var(--formie-neutral-100);--formie-button-icon-background-hover:var(--formie-neutral-200);--formie-button-icon-border:var(--formie-border-width) solid var(--formie-neutral-300);--formie-button-icon-border-hover:var(--formie-border-width) solid var(--formie-neutral-400);--formie-button-icon-color:var(--formie-neutral-950);--formie-button-opacity-disabled:.7;--formie-button-shadow-focus:0 0 0 3px var(--formie-color-border-soft);--formie-icon-mask-plus:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 448 512'%3E%3Cpath fill='%23000' d='M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z'/%3E%3C/svg%3E\");--formie-icon-mask-arrow-left:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z'/%3E%3C/svg%3E\");--formie-icon-mask-arrow-right:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23000' d='M278.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z'/%3E%3C/svg%3E\");--formie-icon-mask-close:url(\"data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'%3E%3Cpath fill='%23000' d='M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z'/%3E%3C/svg%3E\");--formie-button-primary-background:var(--formie-color-primary);--formie-button-primary-background-hover:var(--formie-color-primary-hover);--formie-button-primary-text-color:var(--formie-white);--formie-button-primary-border:var(--formie-border-width) solid transparent;--formie-button-primary-border-hover:var(--formie-border-width) solid var(--formie-color-primary-hover);--formie-button-primary-shadow-focus:0 0 0 3px var(--formie-primary-300);--formie-button-secondary-border:var(--formie-border-width) solid var(--formie-color-border);--formie-button-secondary-border-hover:var(--formie-button-secondary-border);--formie-button-secondary-background:var(--formie-color-surface);--formie-button-secondary-background-hover:var(--formie-neutral-100);--formie-button-secondary-text-color:var(--formie-color-heading);--formie-button-ghost-border:var(--formie-border-width) solid transparent;--formie-button-ghost-border-hover:var(--formie-button-ghost-border);--formie-button-ghost-background:transparent;--formie-button-ghost-background-hover:var(--formie-neutral-100);--formie-button-ghost-text-color:var(--formie-color-heading);--formie-button-ghost-shadow-focus:var(--formie-button-shadow-focus);--formie-button-link-text-color:var(--formie-color-primary);--formie-button-link-text-color-hover:var(--formie-color-primary-hover);--formie-tab-padding-y:var(--formie-space-2);--formie-tab-padding-x:var(--formie-space-4);--formie-tab-font-size:var(--formie-font-size-sm);--formie-gap-tabs:var(--formie-space-4);--formie-progress-height:1.2rem;--formie-progress-padding:var(--formie-space-4);--formie-progress-size:.8rem;--formie-loading-size:var(--formie-space-4);--formie-loading-margin-top:calc(var(--formie-loading-size) * -.5);--formie-loading-margin-left:calc(var(--formie-loading-size) * -.5);--formie-loading-border-width:2px;--formie-loading-animation:loading .5s infinite linear;--formie-loading-left:50%;--formie-loading-top:50%;--formie-loading-z-index:1;--formie-gap-pages:0;--formie-gap-page:var(--formie-space-4);--formie-gap-page-container:0;--formie-gap-page-header:var(--formie-space-4);--formie-gap-page-body:var(--formie-space-4);--formie-gap-page-footer:var(--formie-space-4);--formie-gap-page-buttons:var(--formie-space-4);--formie-title-page-size:var(--formie-font-size-lg);--formie-gap-rows:var(--formie-space-4);--formie-gap-row:var(--formie-space-4);--formie-gap-subfield-rows:var(--formie-space-2);--formie-gap-subfield-row:var(--formie-space-2);--formie-gap-nested-field-rows:var(--formie-space-2);--formie-gap-nested-field-row:var(--formie-space-2);--formie-subfield-row-column-min-width:12rem;--formie-nested-field-row-column-min-width:16rem;--formie-gap-errors:var(--formie-space-2);--formie-gap-field-errors:var(--formie-space-2);--formie-label-size:var(--formie-font-size-sm);--formie-meta-size:var(--formie-font-size-sm);--formie-control-height:2.375rem;--formie-control-padding-y:var(--formie-space-2);--formie-control-padding-x:var(--formie-space-3);--formie-control-font-size:var(--formie-font-size-sm);--formie-textarea-min-height:9rem;--formie-select-indicator-size:1.4rem;--formie-list-indent:var(--formie-space-5);--formie-link-underline-offset:.15em;--formie-gap-field:var(--formie-space-2);--formie-gap-field-layout:var(--formie-space-2);--formie-gap-field-content:var(--formie-space-2);--formie-gap-field-control:var(--formie-space-2);--formie-gap-options:var(--formie-space-2);--formie-summary-padding:var(--formie-space-4);--formie-gap-summary:var(--formie-space-4);--formie-file-summary-padding:var(--formie-space-4);--formie-gap-file-summary:var(--formie-space-3);--formie-rich-text-min-height:12rem;--formie-signature-width:100%;--formie-signature-height:8rem;--formie-signature-background:var(--formie-color-surface-subtle);--formie-signature-border:1px solid var(--formie-color-border);--formie-signature-border-radius:var(--formie-radius-sm);--formie-signature-remove-button-top:0;--formie-signature-remove-button-right:-14px;--formie-signature-remove-button-transform:translate(0, -50%);--formie-check-font-size:var(--formie-font-size-sm);--formie-check-line-height:var(--formie-line-height-base);--formie-check-margin-bottom:var(--formie-space-2);--formie-check-margin-right:var(--formie-space-4);--formie-check-background-color:var(--formie-color-surface-muted);--formie-check-size:var(--formie-space-4);--formie-check-label-padding-left:var(--formie-space-6);--formie-check-label-line-height:var(--formie-space-6);--formie-check-label-top:.3125rem;--formie-check-label-transition:all .15s cubic-bezier(.4, 0, .2, 1);--formie-check-label-background-color:var(--formie-color-surface);--formie-check-check-border-radius:2px;--formie-check-check-background-image:url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3E%3Cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3E%3C/svg%3E\");--formie-check-check-background-size:8px auto;--formie-check-radio-border-radius:50%;--formie-check-radio-background-image:url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%23fff'/%3E%3C/svg%3E\");--formie-check-radio-background-size:8px auto;--formie-group-border:1px solid var(--formie-color-border);--formie-group-border-radius:var(--formie-radius-sm);--formie-group-padding:var(--formie-space-4);--formie-repeater-add-button-padding-left:var(--formie-space-8);--formie-repeater-add-button-icon-mask:var(--formie-icon-mask-plus);--formie-repeater-add-button-height:14px;--formie-repeater-add-button-width:14px;--formie-repeater-add-button-left:var(--formie-space-3);--formie-repeater-remove-button-top:0;--formie-repeater-remove-button-right:-14px;--formie-repeater-remove-button-transform:translate(0, -50%);--formie-table-width:100%;--formie-table-margin-bottom:1rem;--formie-table-border-collapse:collapse;--formie-table-row-padding:.2rem;--formie-table-th-text-align:inherit;--formie-table-th-font-size:.75rem;--formie-table-th-font-weight:600;--formie-table-add-button-padding-left:var(--formie-space-8);--formie-table-add-button-icon-mask:var(--formie-icon-mask-plus);--formie-table-add-button-height:14px;--formie-table-add-button-width:14px;--formie-table-add-button-left:var(--formie-space-3);--formie-table-remove-button-top:0;--formie-table-remove-button-right:-14px;--formie-table-remove-button-transform:translate(0, -50%);font-family:var(--formie-font-family);font-size:var(--formie-body-size);line-height:var(--formie-line-height-base);color:var(--formie-color-text);-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}}", s = "@layer formie-theme{.formie-rich-text{border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;padding:0;transition:border-color .15s,box-shadow .15s,background-color .15s;overflow:hidden}.formie-rich-text:focus-within{border-color:var(--formie-color-focus-ring);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-rich-text{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-rich-text:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-rich-text-toolbar{padding:var(--formie-space-1);border-bottom:var(--formie-border-width) solid var(--formie-color-border);background:#fff;flex-wrap:wrap;align-items:center;gap:0;display:flex;box-shadow:0 1px 2px #1118270f}.formie-rich-text .formie-rich-text-button{width:var(--formie-space-8);height:var(--formie-space-8);border-radius:var(--formie-radius-sm);color:var(--formie-color-heading);font-size:var(--formie-font-size-sm);cursor:pointer;box-shadow:none;background:0 0;border:0;justify-content:center;align-items:center;margin:0;padding:0;line-height:1;transition:background-color .15s,color .15s,box-shadow .15s;display:inline-flex}.formie-rich-text .formie-rich-text-button:hover,.formie-rich-text .formie-rich-text-button.formie-rich-text-selected{background:var(--formie-color-surface-muted)}.formie-rich-text .formie-rich-text-button:focus-visible{box-shadow:0 0 0 2px var(--formie-color-surface), 0 0 0 4px color-mix(in srgb, var(--formie-color-focus-ring) 60%, transparent);outline:0}.formie-rich-text [contenteditable=true]{min-height:var(--formie-rich-text-min-height);padding:var(--formie-space-3) calc(var(--formie-space-3) + var(--formie-space-1) / 2);box-shadow:none;overflow-wrap:anywhere;line-height:var(--formie-line-height-base);color:var(--formie-color-text);background:0 0;border:0;border-radius:0;outline:0}.formie-rich-text [contenteditable=true]>:first-child{margin-top:0}.formie-rich-text [contenteditable=true]>:last-child{margin-bottom:0}.formie-rich-text-content p,.formie-rich-text-content ul,.formie-rich-text-content ol,.formie-rich-text-content blockquote,.formie-rich-text-content dl,.formie-rich-text-content dd,.formie-rich-text-content figure,.formie-rich-text-content hr,.formie-rich-text-content pre{margin:0 0 var(--formie-space-4)}.formie-rich-text-content h1,.formie-rich-text-content h2,.formie-rich-text-content h3,.formie-rich-text-content h4,.formie-rich-text-content h5,.formie-rich-text-content h6{margin:0 0 var(--formie-space-3);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-semibold);line-height:var(--formie-line-height-tight)}.formie-rich-text-content h1{font-size:var(--formie-font-size-2xl)}.formie-rich-text-content h2{font-size:var(--formie-font-size-xl)}.formie-rich-text-content h3{font-size:var(--formie-font-size-lg)}.formie-rich-text-content h4{font-size:var(--formie-font-size-base)}.formie-rich-text-content h5,.formie-rich-text-content h6{font-size:var(--formie-font-size-sm)}.formie-rich-text-content ul,.formie-rich-text-content ol{padding-inline-start:var(--formie-list-indent)}.formie-rich-text-content ul{list-style:outside}.formie-rich-text-content ol{list-style:decimal}.formie-rich-text-content li+li{margin-top:var(--formie-space-1)}.formie-rich-text-content a{color:var(--formie-color-primary);text-underline-offset:var(--formie-link-underline-offset);text-decoration:underline}.formie-rich-text-content blockquote{color:var(--formie-color-text-muted);border-inline-start:4px solid var(--formie-color-border-soft);padding-inline-start:var(--formie-space-4)}.formie-rich-text-content pre{padding:var(--formie-space-4);border-radius:var(--formie-radius-md);background:var(--formie-color-surface-muted);overflow-x:auto}.formie-rich-text-content code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace;font-size:.95em}.formie-rich-text-content :not(pre)>code{border-radius:var(--formie-radius-sm);background:var(--formie-color-surface-muted);padding:.12em .35em}.formie-rich-text-content pre code{background:0 0;border-radius:0;padding:0}.formie-rich-text-content hr{border:0;border-top:var(--formie-border-width) solid var(--formie-color-border);height:0}.formie-rich-text-content img{max-width:100%;height:auto;display:block}.formie-rich-text-content[data-placeholder]:empty:before{content:attr(data-placeholder);color:var(--formie-color-text-muted);pointer-events:none}}", c = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M7 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M7 18h10\" />\n</svg>\n", l = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M4 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M4 18h10\" />\n</svg>\n", u = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 6h16\" />\n  <path d=\"M10 10h10\" />\n  <path d=\"M4 14h16\" />\n  <path d=\"M10 18h10\" />\n</svg>\n", d = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M7 6h6a3 3 0 0 1 0 6H7z\" />\n  <path d=\"M7 12h7a3 3 0 0 1 0 6H7z\" />\n</svg>\n", f = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"m4 20 8-8\" />\n  <path d=\"m12 12 7-7\" />\n  <path d=\"m5 15-2-2a2 2 0 0 1 0-2.83L9.17 4a2 2 0 0 1 2.83 0l4 4\" />\n  <path d=\"M16 20H8\" />\n</svg>\n", p = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"m9 18-6-6 6-6\" />\n  <path d=\"m15 6 6 6-6 6\" />\n</svg>\n", m = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">H1</text>\n</svg>\n", h = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">H2</text>\n</svg>\n", g = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <rect x=\"3\" y=\"5\" width=\"18\" height=\"14\" rx=\"2\" />\n  <path d=\"m8 13 3-3 5 5\" />\n  <path d=\"m13 12 2-2 4 4\" />\n  <circle cx=\"8.5\" cy=\"9.5\" r=\"1\" />\n</svg>\n", _ = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M13 6h6\" />\n  <path d=\"M5 18h6\" />\n  <path d=\"M14 6 10 18\" />\n</svg>\n", v = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M4 12h16\" />\n</svg>\n", y = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 13a5 5 0 0 0 7.07 0l1.41-1.41a5 5 0 0 0-7.07-7.07L10 5\" />\n  <path d=\"M14 11a5 5 0 0 0-7.07 0L5.5 12.43a5 5 0 0 0 7.07 7.07L14 19\" />\n</svg>\n", b = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 6h10\" />\n  <path d=\"M10 12h10\" />\n  <path d=\"M10 18h10\" />\n  <path d=\"M4 6h.01\" />\n  <path d=\"M4 12h.01\" />\n  <path d=\"M4 18h.01\" />\n</svg>\n", x = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" aria-hidden=\"true\" focusable=\"false\">\n  <text x=\"50%\" y=\"50%\" dominant-baseline=\"central\" text-anchor=\"middle\" font-family=\"system-ui, sans-serif\" font-size=\"12\" font-weight=\"700\" fill=\"currentColor\">P</text>\n</svg>\n", S = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M8 10H6a2 2 0 0 0-2 2v2h4v-4z\" />\n  <path d=\"M18 10h-2a2 2 0 0 0-2 2v2h4v-4z\" />\n</svg>\n", C = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M6 12h12\" />\n  <path d=\"M9 7a3 3 0 0 1 6 0c0 4-6 2-6 6a3 3 0 0 0 6 0\" />\n</svg>\n", w = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M10 6h10\" />\n  <path d=\"M10 12h10\" />\n  <path d=\"M10 18h10\" />\n  <circle cx=\"4\" cy=\"6\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n  <circle cx=\"4\" cy=\"12\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n  <circle cx=\"4\" cy=\"18\" r=\"1\" fill=\"currentColor\" stroke=\"none\" />\n</svg>\n", T = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" width=\"16\" height=\"16\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">\n  <path d=\"M8 6v5a4 4 0 0 0 8 0V6\" />\n  <path d=\"M6 20h12\" />\n</svg>\n", E = "[data-formie-rich-text]", D = "[data-formie-field], [data-formie-field-handle]", O = "textarea[data-formie-multi-line-text-input]", k = "rich-text";
t(k, [
	o,
	a,
	s
]);
var A = {
	bold: d,
	italic: _,
	underline: T,
	strikethrough: C,
	heading1: m,
	heading2: h,
	paragraph: x,
	quote: S,
	olist: b,
	ulist: w,
	code: p,
	line: v,
	link: y,
	image: g,
	alignleft: l,
	aligncenter: c,
	alignright: u,
	clear: f
};
function j(e) {
	return e.matches(D) ? !!e.querySelector(E) && !!e.querySelector(O) : Array.from(e.querySelectorAll(D)).some((e) => !!e.querySelector(E) && !!e.querySelector(O));
}
function M() {
	return [
		{
			name: "bold",
			icon: A.bold
		},
		{
			name: "italic",
			icon: A.italic
		},
		{
			name: "underline",
			icon: A.underline
		},
		{
			name: "strikethrough",
			icon: A.strikethrough
		},
		{
			name: "heading1",
			icon: A.heading1
		},
		{
			name: "heading2",
			icon: A.heading2
		},
		{
			name: "paragraph",
			icon: A.paragraph
		},
		{
			name: "quote",
			icon: A.quote
		},
		{
			name: "olist",
			icon: A.olist
		},
		{
			name: "ulist",
			icon: A.ulist
		},
		{
			name: "code",
			icon: A.code
		},
		{
			name: "line",
			icon: A.line
		},
		{
			name: "link",
			icon: A.link
		},
		{
			name: "image",
			icon: A.image
		},
		{
			name: "alignleft",
			icon: A.alignleft,
			title: "Align Left",
			result: () => (0, i.exec)("justifyLeft", "")
		},
		{
			name: "aligncenter",
			icon: A.aligncenter,
			title: "Align Center",
			result: () => (0, i.exec)("justifyCenter", "")
		},
		{
			name: "alignright",
			icon: A.alignright,
			title: "Align Right",
			result: () => (0, i.exec)("justifyRight", "")
		},
		{
			name: "clear",
			icon: A.clear,
			title: "Clear",
			result: () => {
				let e = window.getSelection()?.toString() || "";
				if (e) {
					let t = e.split("\n").join("<br>");
					(0, i.exec)("formatBlock", "<p>"), document.execCommand("insertHTML", !1, t);
					return;
				}
				(0, i.exec)("formatBlock", "<p>");
			}
		}
	];
}
function N(e) {
	let t = e?.length ? e : ["bold", "italic"], n = M();
	return t.map((e) => n.find((t) => t.name === e)).filter((e) => !!e);
}
function P(e, t, n) {
	let a = {
		element: e,
		defaultParagraphSeparator: "p",
		styleWithCSS: !0,
		actions: N(n.buttons),
		onChange: (e) => {
			t.value = t.placeholder && e === "<p><br></p>" ? "" : e, t.dispatchEvent(new Event("input", { bubbles: !0 })), r(t, k, "populate", {
				richText: t,
				value: t.value
			});
		},
		classes: {
			actionbar: "formie-rich-text-toolbar",
			button: "formie-rich-text-button",
			content: "formie-input formie-rich-text-content",
			selected: "formie-rich-text-selected"
		}
	};
	r(t, k, "before-init", {
		richText: t,
		options: a
	});
	let o = (0, i.init)(a);
	return t.richText = o, o.content.innerHTML = t.value || "", t.placeholder && o.content.setAttribute("data-placeholder", t.placeholder), r(t, k, "after-init", { richText: o }), () => {
		e.innerHTML = "", delete t.richText;
	};
}
var F = {
	id: k,
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && j(e.target),
	setup: async (e) => {
		let t = e.options || {}, r = n(e).map((e) => {
			let n = e.querySelector(E), r = e.querySelector(O);
			return !(n instanceof HTMLElement) || !(r instanceof HTMLTextAreaElement) ? () => {} : P(n, r, t);
		});
		return await e.emit("formie:module:rich-text:init", { count: r.length }), { destroy: () => {
			r.forEach((e) => {
				e();
			}), e.emit("formie:module:rich-text:destroy", {});
		} };
	}
};
//#endregion
export { F as richTextModule };
