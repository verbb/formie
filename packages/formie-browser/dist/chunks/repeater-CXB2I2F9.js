import { t as e } from "./debug-JxLdQzL0.js";
import { n as t } from "./async-CTgbK8eG.js";
import { t as n } from "./styles-BuYIxHcX.js";
import { a as r, o as i, t as a } from "./shared-ktsx_SHX.js";
//#region src/css/theme/fields/_repeater.css?inline
var o = "@layer formie-theme{.formie-repeater-container{gap:var(--formie-space-4);display:grid}.formie-repeater-item-wrapper{gap:var(--formie-space-4);padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-md);transition:border-color .15s,box-shadow .15s,background-color .15s;display:grid;position:relative}.formie-repeater-item-wrapper:focus-within{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-repeater-item-wrapper{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-repeater-item-wrapper:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-repeater-item-wrapper>.formie-repeater-remove-button{top:var(--formie-repeater-remove-button-top);right:var(--formie-repeater-remove-button-right);transform:var(--formie-repeater-remove-button-transform);font-size:0;line-height:0;position:absolute}.formie-button.formie-repeater-add-button{width:auto;max-width:100%;padding-left:var(--formie-repeater-add-button-padding-left);justify-content:center;justify-self:start;align-items:center;display:inline-flex;position:relative}.formie-button.formie-repeater-add-button:before{content:\"\";width:var(--formie-repeater-add-button-width);height:var(--formie-repeater-add-button-height);left:var(--formie-repeater-add-button-left);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);mask-image:var(--formie-repeater-add-button-icon-mask);background-color:currentColor;display:block;position:absolute;top:50%;transform:translateY(-50%);-webkit-mask-position:50%;mask-position:50%;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}}", s = "[data-formie-repeater-field-layout]", c = "[data-formie-repeater-container]", l = "[data-formie-repeater-item]", u = "[data-formie-repeater-add]", d = "[data-formie-repeater-remove]", f = "data-formie-template-id", p = "repeater", m = e("fields", "repeater");
n(p, [o]);
function h(e, t) {
	return r(e, t);
}
function g(e, t) {
	let n = document.createElement("div");
	return n.innerHTML = e.replaceAll("__ROW__", String(t)).trim(), n.firstElementChild instanceof HTMLElement ? n.firstElementChild : null;
}
function _(e) {
	return e.querySelectorAll(l).length;
}
function v(e, t) {
	if (!e) return;
	let n = parseInt(e.getAttribute("data-formie-max-rows") || "", 10);
	if (n > 0 && t >= n) {
		e.disabled = !0;
		return;
	}
	e.disabled = !1;
}
function y(e) {
	let n = e.matches(c) ? e : e.querySelector(c), r = e.querySelector(u);
	if (!(n instanceof HTMLElement)) return m.warn("Missing repeater container; skipping field."), () => {};
	let o = /* @__PURE__ */ new Map(), s = Array.from(e.querySelectorAll(l)).reduce((e, t) => {
		let n = parseInt(t.getAttribute("data-formie-repeater-item-id") || "", 10);
		return Number.isNaN(n) ? e : Math.max(e, n + 1);
	}, 0), y = () => {
		e.querySelectorAll(d).forEach((t) => {
			if (!(t instanceof HTMLElement) || o.has(t)) return;
			let n = (n) => {
				n.preventDefault();
				let i = t.closest(l);
				if (!(i instanceof HTMLElement)) return;
				let o = parseInt((r instanceof HTMLButtonElement ? r.getAttribute("data-formie-min-rows") : "") || "", 10);
				o > 0 && _(e) <= o || (i.remove(), v(r instanceof HTMLButtonElement ? r : null, _(e)), m.log("Row removed.", { rowCount: _(e) }), a(e, p, "remove", {
					repeater: e,
					row: i
				}));
			};
			t.addEventListener("click", n), o.set(t, n);
		});
	}, b = async () => {
		if (!(r instanceof HTMLButtonElement)) return;
		let o = r.getAttribute("data-formie-repeater-add");
		if (!o) {
			m.warn("Add handle missing.");
			return;
		}
		let c = r.getAttribute(f) || e.getAttribute(f), l = parseInt(r.getAttribute("data-formie-max-rows") || "", 10);
		if (l > 0 && _(e) >= l) return;
		let u = h(e, c);
		if (!u) {
			m.warn("Template not found for add action.", { handle: o });
			return;
		}
		let d = g(i(u), s++);
		if (!d) {
			m.warn("Failed to build row from template.");
			return;
		}
		n.appendChild(d), await t(50), y(), v(r, _(e)), m.log("Row appended.", { rowCount: _(e) }), a(e, p, "append", {
			repeater: e,
			row: d
		}), a(e, p, "init-row", {
			repeater: e,
			row: d
		});
	}, x = (e) => {
		e.preventDefault(), b();
	};
	if (r instanceof HTMLButtonElement && r.addEventListener("click", x), y(), v(r instanceof HTMLButtonElement ? r : null, _(e)), r instanceof HTMLButtonElement && _(e) === 0) {
		let e = parseInt(r.getAttribute("data-formie-min-rows") || "", 10);
		for (let t = 0; t < e; t += 1) b();
	}
	return a(e, p, "init", { repeater: e }), m.log("Field initialized.", { rowCount: _(e) }), () => {
		r instanceof HTMLButtonElement && r.removeEventListener("click", x), o.forEach((e, t) => {
			t.removeEventListener("click", e);
		});
	};
}
var b = {
	id: p,
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(s) || !!e.target.querySelector(s)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(s) ? [e.target] : Array.from(e.target.querySelectorAll(s)).filter((e) => e instanceof HTMLElement), n = t.map((e) => y(e));
		return m.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:repeater:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), m.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:repeater:destroy", {});
		} };
	}
};
//#endregion
export { b as repeaterModule };
