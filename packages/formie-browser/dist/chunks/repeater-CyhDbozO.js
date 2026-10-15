import { t as e } from "./debug-BV0DvdHx.js";
import { n as t } from "./async-2GdXNFCE.js";
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
	let t = Array.from(e?.querySelectorAll("input:not([type=\"hidden\"]), select, textarea, button, a[href], [role=\"button\"], [role=\"combobox\"], [role=\"textbox\"], [contenteditable=\"true\"]") || []).find((e) => e.tabIndex >= 0 && !e.matches(":disabled") && !e.closest("[hidden], [inert]") && e.getClientRects().length > 0);
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
function b(e, n) {
	let i = e.matches(c) ? e : e.querySelector(c), o = e.querySelector(u);
	if (!(i instanceof HTMLElement)) return m.warn("Missing repeater container; skipping field."), () => {};
	let s = e.closest("form"), b = !1, x = 0, S = /* @__PURE__ */ new Map(), C = Array.from(e.querySelectorAll(l)).reduce((e, t) => {
		let n = parseInt(t.getAttribute("data-formie-repeater-item-id") || "", 10);
		return Number.isNaN(n) ? e : Math.max(e, n + 1);
	}, 0), w = () => {
		e.querySelectorAll(d).forEach((t) => {
			if (!(t instanceof HTMLElement) || S.has(t)) return;
			let i = (i) => {
				i.preventDefault();
				let a = t.closest(l);
				if (!(a instanceof HTMLElement)) return;
				let s = parseInt((o instanceof HTMLButtonElement ? o.getAttribute("data-formie-min-rows") : "") || "", 10);
				if (s > 0 && _(e) <= s) return;
				let c = a.contains(document.activeElement) && (!n || i instanceof MouseEvent && i.detail === 0), u = a.nextElementSibling, d = a.previousElementSibling;
				a.remove(), y(o instanceof HTMLButtonElement ? o : null, _(e)), c && !v(u) && !v(d) && o instanceof HTMLButtonElement && o.focus(), m.log("Row removed.", { rowCount: _(e) }), r(e, p, "remove", {
					repeater: e,
					row: a
				});
			};
			t.addEventListener("click", i), S.set(t, i);
		});
	}, T = async (n = !1) => {
		if (!(o instanceof HTMLButtonElement)) return;
		let s = o.getAttribute("data-formie-repeater-add");
		if (!s) {
			m.warn("Add handle missing.");
			return;
		}
		let c = o.getAttribute(f) || e.getAttribute(f), l = parseInt(o.getAttribute("data-formie-max-rows") || "", 10);
		if (l > 0 && _(e) >= l) return;
		let u = h(e, c);
		if (!u) {
			m.warn("Template not found for add action.", { handle: s });
			return;
		}
		let d = g(a(u), C++);
		if (!d) {
			m.warn("Failed to build row from template.");
			return;
		}
		let S = x;
		i.appendChild(d), await t(50), !b && S === x && d.isConnected && (w(), n && document.activeElement === o && v(d), y(o, _(e)), m.log("Row appended.", { rowCount: _(e) }), r(e, p, "append", {
			repeater: e,
			row: d
		}), r(e, p, "init-row", {
			repeater: e,
			row: d
		}));
	}, E = (e) => {
		e.preventDefault();
		let t = !n || e instanceof MouseEvent && e.detail === 0;
		T(t);
	};
	if (o instanceof HTMLButtonElement && o.addEventListener("click", E), w(), y(o instanceof HTMLButtonElement ? o : null, _(e)), o instanceof HTMLButtonElement && _(e) === 0) {
		let e = parseInt(o.getAttribute("data-formie-min-rows") || "", 10);
		for (let t = 0; t < e; t += 1) T();
	}
	let D = Array.from(i.children).map((e) => e.cloneNode(!0)), O = (t) => {
		queueMicrotask(() => {
			t.defaultPrevented || b || (x++, S.forEach((e, t) => t.removeEventListener("click", e)), S.clear(), i.replaceChildren(...D.map((e) => e.cloneNode(!0))), w(), y(o instanceof HTMLButtonElement ? o : null, _(e)));
		});
	};
	return s?.addEventListener("reset", O), r(e, p, "init", { repeater: e }), m.log("Field initialized.", { rowCount: _(e) }), () => {
		b = !0, s?.removeEventListener("reset", O), o instanceof HTMLButtonElement && o.removeEventListener("click", E), S.forEach((e, t) => {
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
		let t = e.target.matches(s) ? [e.target] : Array.from(e.target.querySelectorAll(s)).filter((e) => e instanceof HTMLElement), n = t.map((t) => b(t, e.surface === "cp-edit"));
		return m.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:repeater:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), m.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:repeater:destroy", {});
		} };
	}
};
//#endregion
export { x as repeaterModule };
