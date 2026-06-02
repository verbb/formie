import { t as e } from "./styles-BuYIxHcX.js";
import { r as t } from "./dist-BW49hac1.js";
import { c as n, i as r, s as i } from "./shared-ktsx_SHX.js";
//#region src/css/theme/fields/_text-limit.css?inline
var a = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}", o = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]", s = [
	"textMinCharacterLimit",
	"textMaxCharacterLimit",
	"textMinWordLimit",
	"textMaxWordLimit"
], c = "text-limit", l = "data-formie-text-limit-allow-overtype", u = /* @__PURE__ */ new WeakMap();
e("text-limit", [a]);
function d(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement;
}
function f(e, t) {
	return parseInt(e.getAttribute(t) || "", 10) || 0;
}
function p(e) {
	return e.hasAttribute("data-formie-min-chars") || e.hasAttribute("data-formie-max-chars") || e.hasAttribute("data-formie-min-words") || e.hasAttribute("data-formie-max-words");
}
function m(e) {
	return e.hasAttribute("data-formie-max-chars") || e.hasAttribute("data-formie-max-words");
}
function h(e) {
	return e.hasAttribute(l);
}
function g(e) {
	return e.value === "";
}
function _(e) {
	n(e, c, (e) => {
		e.addValidator("textMinCharacterLimit", ({ input: e }) => {
			if (!d(e)) return !0;
			let n = f(e, "data-formie-min-chars");
			return !n || g(e) ? !0 : t(e.value).graphemeCount >= n;
		}, ({ label: e, input: t, t: n }) => n("{attribute} must be no less than {min} characters.", {
			attribute: e,
			min: t.getAttribute("data-formie-min-chars") || ""
		})), e.addValidator("textMaxCharacterLimit", ({ input: e }) => {
			if (!d(e) || h(e)) return !0;
			let n = f(e, "data-formie-max-chars");
			return !n || g(e) ? !0 : t(e.value).graphemeCount <= n;
		}, ({ label: e, input: t, t: n }) => n("{attribute} must be no greater than {max} characters.", {
			attribute: e,
			max: t.getAttribute("data-formie-max-chars") || ""
		})), e.addValidator("textMinWordLimit", ({ input: e }) => {
			if (!d(e)) return !0;
			let n = f(e, "data-formie-min-words");
			return !n || e.value.trim() === "" ? !0 : t(e.value).wordCount >= n;
		}, ({ label: e, input: t, t: n }) => n("{attribute} must be no less than {min} words.", {
			attribute: e,
			min: t.getAttribute("data-formie-min-words") || ""
		})), e.addValidator("textMaxWordLimit", ({ input: e }) => {
			if (!d(e) || h(e)) return !0;
			let n = f(e, "data-formie-max-words");
			return !n || e.value.trim() === "" ? !0 : t(e.value).wordCount <= n;
		}, ({ label: e, input: t, t: n }) => n("{attribute} must be no greater than {max} words.", {
			attribute: e,
			max: t.getAttribute("data-formie-max-words") || ""
		}));
	});
}
function v(e) {
	i(e, c, s);
}
function y(e) {
	if (u.has(e)) return u.get(e) || null;
	let t = e.closest("[data-formie-field-handle]");
	if (!t) return u.set(e, null), null;
	let n = t.querySelector("[data-formie-limit-text]");
	if (n) return u.set(e, n), n;
	let r = t.querySelector("[data-formie-field-control]"), i = document.createElement("div");
	return i.className = "formie-field-limit formie-limit-text", i.setAttribute("data-formie-field-limit", "true"), i.setAttribute("data-formie-limit-text", "true"), r?.parentElement ? (r.insertAdjacentElement("afterend", i), u.set(e, i), i) : (t.appendChild(i), u.set(e, i), i);
}
function b(e, t, n) {
	let r = document.createElement("span");
	r.className = t < 0 ? "formie-limit-number formie-limit-number-error" : "formie-limit-number", r.textContent = String(t), e.replaceChildren(r, document.createTextNode(` ${Math.abs(t) === 1 ? n : `${n}s`} left`));
}
function x(e) {
	let n = f(e, "data-formie-max-chars"), r = f(e, "data-formie-max-words"), i = y(e);
	if (!i) return;
	let a = t(e.value);
	if (n > 0) {
		b(i, n - a.graphemeCount, "character");
		return;
	}
	r > 0 && b(i, r - a.wordCount, "word");
}
var S = {
	id: "text-limit",
	kind: "field",
	match: (e) => !!e.target.querySelector(o),
	setup: async (e) => {
		let t = e.options || {}, n = r(e), i = Array.from((n || e.target).querySelectorAll(o)).filter((e) => (e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement) && p(e)), a = i.filter((e) => m(e));
		t.allowOvertype && i.forEach((e) => {
			e.setAttribute(l, "true");
		}), _(e.form);
		let s = a.map((e) => {
			let t = () => {
				x(e);
			};
			return e.addEventListener("input", t), e.addEventListener("change", t), x(e), () => {
				e.removeEventListener("input", t), e.removeEventListener("change", t);
			};
		});
		return await e.emit("formie:module:text-limit:init", { count: i.length }), { destroy: () => {
			s.forEach((e) => {
				e();
			}), t.allowOvertype && i.forEach((e) => {
				e.removeAttribute(l);
			}), v(e.form), e.emit("formie:module:text-limit:destroy", {});
		} };
	}
};
//#endregion
export { S as textLimitModule };
