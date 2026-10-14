import { t as e } from "./debug-BV0DvdHx.js";
import { n as t } from "./async-nPFRNQ06.js";
import { t as n } from "./styles-BfoIZwJp.js";
import { n as r, o as i, s as a } from "./shared-OKhSay59.js";
//#region src/css/theme/fields/_repeater.css?inline
var o = "@layer formie-theme{.formie-repeater-container{gap:var(--formie-space-4);display:grid}.formie-repeater-item-wrapper{gap:var(--formie-space-4);padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-md);transition:border-color .15s,box-shadow .15s,background-color .15s;display:grid;position:relative}.formie-repeater-item-wrapper:focus-within{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-repeater-item-wrapper{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-repeater-item-wrapper:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-repeater-item-wrapper>.formie-repeater-remove-button{top:var(--formie-repeater-remove-button-top);right:var(--formie-repeater-remove-button-right);transform:var(--formie-repeater-remove-button-transform);font-size:0;line-height:0;position:absolute}.formie-button.formie-repeater-add-button{width:auto;max-width:100%;padding-left:var(--formie-repeater-add-button-padding-left);justify-content:center;justify-self:start;align-items:center;display:inline-flex;position:relative}.formie-button.formie-repeater-add-button:before{content:\"\";width:var(--formie-repeater-add-button-width);height:var(--formie-repeater-add-button-height);left:var(--formie-repeater-add-button-left);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);mask-image:var(--formie-repeater-add-button-icon-mask);background-color:currentColor;display:block;position:absolute;top:50%;transform:translateY(-50%);-webkit-mask-position:50%;mask-position:50%;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}}", s = "[data-formie-repeater-field-layout]", c = "[data-formie-repeater-container]", l = "[data-formie-repeater-item]", u = "[data-formie-repeater-add]", d = "[data-formie-repeater-remove]", f = "data-formie-template-id", p = "repeater", m = e("fields", "repeater");
n(p, [o]);
function h(e, t) {
	return i(e, t);
}
function g(e, t) {
	let n = document.createElement("div");
	return n.innerHTML = e.replaceAll("__ROW__", String(t)).trim(), n.firstElementChild instanceof HTMLElement ? n.firstElementChild : null;
}
function _(e) {
	return e.querySelectorAll(l).length;
}
function v(e) {
	let t = Array.from(e?.querySelectorAll("input:not([type=\"hidden\"]), select, textarea, button, a[href], [tabindex]") || []).find((e) => e.tabIndex >= 0 && !e.matches(":disabled") && !e.closest("[hidden], [inert]") && e.getClientRects().length > 0);
	return t?.focus(), !!t;
}
function y(e, t) {
	if (!e) return;
	let n = parseInt(e.getAttribute("data-formie-max-rows") || "", 10);
	if (n > 0 && t >= n) {
		e.disabled = !0;
		return;
	}
	e.disabled = !1;
}
function b(e) {
	let n = e.matches(c) ? e : e.querySelector(c), i = e.querySelector(u);
	if (!(n instanceof HTMLElement)) return m.warn("Missing repeater container; skipping field."), () => {};
	let o = e.closest("form"), s = !1, b = 0, x = /* @__PURE__ */ new Map(), S = Array.from(e.querySelectorAll(l)).reduce((e, t) => {
		let n = parseInt(t.getAttribute("data-formie-repeater-item-id") || "", 10);
		return Number.isNaN(n) ? e : Math.max(e, n + 1);
	}, 0), C = () => {
		e.querySelectorAll(d).forEach((t) => {
			if (!(t instanceof HTMLElement) || x.has(t)) return;
			let n = (n) => {
				n.preventDefault();
				let a = t.closest(l);
				if (!(a instanceof HTMLElement)) return;
				let o = parseInt((i instanceof HTMLButtonElement ? i.getAttribute("data-formie-min-rows") : "") || "", 10);
				if (o > 0 && _(e) <= o) return;
				let s = a.contains(document.activeElement), c = a.nextElementSibling, u = a.previousElementSibling;
				a.remove(), y(i instanceof HTMLButtonElement ? i : null, _(e)), s && !v(c) && !v(u) && i instanceof HTMLButtonElement && i.focus(), m.log("Row removed.", { rowCount: _(e) }), r(e, p, "remove", {
					repeater: e,
					row: a
				});
			};
			t.addEventListener("click", n), x.set(t, n);
		});
	}, w = async () => {
		if (!(i instanceof HTMLButtonElement)) return;
		let o = i.getAttribute("data-formie-repeater-add");
		if (!o) {
			m.warn("Add handle missing.");
			return;
		}
		let c = i.getAttribute(f) || e.getAttribute(f), l = parseInt(i.getAttribute("data-formie-max-rows") || "", 10);
		if (l > 0 && _(e) >= l) return;
		let u = h(e, c);
		if (!u) {
			m.warn("Template not found for add action.", { handle: o });
			return;
		}
		let d = g(a(u), S++);
		if (!d) {
			m.warn("Failed to build row from template.");
			return;
		}
		let x = b;
		n.appendChild(d), await t(50), !s && x === b && d.isConnected && (C(), document.activeElement === i && v(d), y(i, _(e)), m.log("Row appended.", { rowCount: _(e) }), r(e, p, "append", {
			repeater: e,
			row: d
		}), r(e, p, "init-row", {
			repeater: e,
			row: d
		}));
	}, T = (e) => {
		e.preventDefault(), w();
	};
	if (i instanceof HTMLButtonElement && i.addEventListener("click", T), C(), y(i instanceof HTMLButtonElement ? i : null, _(e)), i instanceof HTMLButtonElement && _(e) === 0) {
		let e = parseInt(i.getAttribute("data-formie-min-rows") || "", 10);
		for (let t = 0; t < e; t += 1) w();
	}
	let E = Array.from(n.children).map((e) => e.cloneNode(!0)), D = (t) => {
		queueMicrotask(() => {
			t.defaultPrevented || s || (b++, x.forEach((e, t) => t.removeEventListener("click", e)), x.clear(), n.replaceChildren(...E.map((e) => e.cloneNode(!0))), C(), y(i instanceof HTMLButtonElement ? i : null, _(e)));
		});
	};
	return o?.addEventListener("reset", D), r(e, p, "init", { repeater: e }), m.log("Field initialized.", { rowCount: _(e) }), () => {
		s = !0, o?.removeEventListener("reset", D), i instanceof HTMLButtonElement && i.removeEventListener("click", T), x.forEach((e, t) => {
			t.removeEventListener("click", e);
		});
	};
}
var x = {
	moduleId: `formie:${p}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(s) || !!e.target.querySelector(s)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(s) ? [e.target] : Array.from(e.target.querySelectorAll(s)).filter((e) => e instanceof HTMLElement), n = t.map((e) => b(e));
		return m.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:repeater:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), m.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:repeater:destroy", {});
		} };
	}
};
//#endregion
export { x as repeaterModule };
