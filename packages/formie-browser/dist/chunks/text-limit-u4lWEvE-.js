import { i as e } from "./i18n-BY1ds1BL.js";
import { t } from "./styles-BfoIZwJp.js";
import { c as n, i as r, l as i } from "./shared-Bx9s0i0P.js";
import { n as a } from "./dist-BKMvLXjr.js";
//#region src/css/theme/fields/_text-limit.css?inline
var o = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}", s = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]", c = [
	"textMinCharacterLimit",
	"textMaxCharacterLimit",
	"textMinWordLimit",
	"textMaxWordLimit"
], l = "text-limit", u = "data-formie-text-limit-allow-overtype", d = "{count, plural, one{character allowed} other{characters allowed}}", f = "{count, plural, one{character left} other{characters left}}", p = "{count, plural, one{character over limit} other{characters over limit}}", m = "{count, plural, one{word allowed} other{words allowed}}", h = "{count, plural, one{word left} other{words left}}", g = "{count, plural, one{word over limit} other{words over limit}}", _ = /* @__PURE__ */ new WeakMap();
t("text-limit", [o]);
function v(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement;
}
function y(e, t) {
	return parseInt(e.getAttribute(t) || "", 10) || 0;
}
function b(e) {
	return e.hasAttribute("data-formie-min-chars") || e.hasAttribute("data-formie-max-chars") || e.hasAttribute("data-formie-min-words") || e.hasAttribute("data-formie-max-words");
}
function x(e) {
	return e.hasAttribute("data-formie-max-chars") || e.hasAttribute("data-formie-max-words");
}
function S(e) {
	return e.hasAttribute(u);
}
function C(e) {
	return e.value === "";
}
function w(e) {
	i(e, l, (e) => {
		e.addValidator("textMinCharacterLimit", ({ input: e }) => {
			if (!v(e)) return !0;
			let t = y(e, "data-formie-min-chars");
			return !t || C(e) ? !0 : a(e.value).graphemeCount >= t;
		}, ({ label: e, input: t, t: n }) => t.getAttribute("data-formie-validation-min-characters-message") || n("{label} must be no less than {min} characters.", {
			label: e,
			min: t.getAttribute("data-formie-min-chars") || ""
		})), e.addValidator("textMaxCharacterLimit", ({ input: e }) => {
			if (!v(e) || S(e)) return !0;
			let t = y(e, "data-formie-max-chars");
			return !t || C(e) ? !0 : a(e.value).graphemeCount <= t;
		}, ({ label: e, input: t, t: n }) => t.getAttribute("data-formie-validation-max-characters-message") || n("{label} must be no greater than {max} characters.", {
			label: e,
			max: t.getAttribute("data-formie-max-chars") || ""
		})), e.addValidator("textMinWordLimit", ({ input: e }) => {
			if (!v(e)) return !0;
			let t = y(e, "data-formie-min-words");
			return !t || e.value.trim() === "" ? !0 : a(e.value).wordCount >= t;
		}, ({ label: e, input: t, t: n }) => t.getAttribute("data-formie-validation-min-words-message") || n("{label} must be no less than {min} words.", {
			label: e,
			min: t.getAttribute("data-formie-min-words") || ""
		})), e.addValidator("textMaxWordLimit", ({ input: e }) => {
			if (!v(e) || S(e)) return !0;
			let t = y(e, "data-formie-max-words");
			return !t || e.value.trim() === "" ? !0 : a(e.value).wordCount <= t;
		}, ({ label: e, input: t, t: n }) => t.getAttribute("data-formie-validation-max-words-message") || n("{label} must be no greater than {max} words.", {
			label: e,
			max: t.getAttribute("data-formie-max-words") || ""
		}));
	});
}
function T(e) {
	n(e, l, c);
}
function E(e) {
	if (_.has(e)) return _.get(e) || null;
	let t = e.closest("[data-formie-field-handle]");
	if (!t) return _.set(e, null), null;
	let n = t.querySelector("[data-formie-limit-text]");
	if (n) return _.set(e, n), n;
	let r = t.querySelector("[data-formie-field-control]"), i = document.createElement("div");
	return i.className = "formie-field-limit formie-limit-text", i.setAttribute("data-formie-field-limit", "true"), i.setAttribute("data-formie-limit-text", "true"), r?.parentElement ? (r.insertAdjacentElement("afterend", i), _.set(e, i), i) : (t.appendChild(i), _.set(e, i), i);
}
function D(e, t, n) {
	return (n === "character" ? e.value === "" : e.value.trim() === "") ? "allowed" : t < 0 ? "over" : "left";
}
function O(e, t) {
	return e === "character" ? t === "allowed" ? d : t === "over" ? p : f : t === "allowed" ? m : t === "over" ? g : h;
}
function k(t, n, r, i, a) {
	let o = D(n, r, a), s = o === "allowed" ? i : Math.abs(r), c = document.createElement("span");
	c.className = o === "over" ? "formie-limit-number formie-limit-number-error" : "formie-limit-number", c.textContent = String(s);
	let l = O(a, o);
	t.replaceChildren(c, document.createTextNode(` ${e(l, { count: s })}`));
}
function A(e) {
	let t = y(e, "data-formie-max-chars"), n = y(e, "data-formie-max-words"), r = E(e);
	if (!r) return;
	let i = a(e.value);
	if (t > 0) {
		k(r, e, t - i.graphemeCount, t, "character");
		return;
	}
	n > 0 && k(r, e, n - i.wordCount, n, "word");
}
var j = {
	id: "text-limit",
	kind: "field",
	match: (e) => !!e.target.querySelector(s),
	setup: async (e) => {
		let t = e.options || {}, n = r(e), i = Array.from((n || e.target).querySelectorAll(s)).filter((e) => (e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement) && b(e)), a = i.filter((e) => x(e));
		t.allowOvertype && i.forEach((e) => {
			e.setAttribute(u, "true");
		}), w(e.form);
		let o = a.map((e) => {
			let t = () => {
				A(e);
			};
			return e.addEventListener("input", t), e.addEventListener("change", t), A(e), () => {
				e.removeEventListener("input", t), e.removeEventListener("change", t);
			};
		});
		return await e.emit("formie:module:text-limit:init", { count: i.length }), { destroy: () => {
			o.forEach((e) => {
				e();
			}), t.allowOvertype && i.forEach((e) => {
				e.removeAttribute(u);
			}), T(e.form), e.emit("formie:module:text-limit:destroy", {});
		} };
	}
};
//#endregion
export { j as textLimitModule };
