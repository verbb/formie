import { n as e } from "./async-nPFRNQ06.js";
import { t } from "./styles-BfoIZwJp.js";
import { a as n, o as r, t as i } from "./shared-Bx9s0i0P.js";
//#region src/css/theme/fields/_table.css?inline
var a = "@layer formie-theme{.formie-table-wrapper{-webkit-overflow-scrolling:touch;max-width:100%;overflow:auto hidden}.formie-table{width:var(--formie-table-width);margin-bottom:var(--formie-table-margin-bottom);border-collapse:var(--formie-table-border-collapse)}.formie-table th{text-align:var(--formie-table-th-text-align);font-size:var(--formie-table-th-font-size);font-weight:var(--formie-table-th-font-weight);color:var(--formie-table-th-color,var(--formie-color-text-muted))}.formie-table th,.formie-table td{padding:var(--formie-table-row-padding);vertical-align:top}.formie-table th:first-child,.formie-table td:first-child{padding-left:0}.formie-table th:last-child,.formie-table td:last-child{padding-right:0}.formie-table [data-col-remove]{width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));min-width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));white-space:nowrap;text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox]{text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option{width:100%;min-height:var(--formie-check-size);justify-content:center;align-items:center;margin:0;display:flex}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label{width:var(--formie-check-size);min-width:var(--formie-check-size);height:var(--formie-check-size);margin:0 auto;padding-left:0;font-size:0;line-height:0;display:block}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label:before{position:static}.formie-table-color-input{min-width:4rem;padding:var(--formie-space-1)}.formie-table-multiline-input{min-height:calc(var(--formie-control-height) + var(--formie-space-2))}.formie-table-remove-button{vertical-align:middle;display:inline-flex}.formie-button.formie-table-add-button{width:auto;max-width:100%;padding-left:var(--formie-table-add-button-padding-left);justify-content:center;justify-self:start;align-items:center;display:inline-flex;position:relative}.formie-button.formie-table-add-button:before{content:\"\";width:var(--formie-table-add-button-width);height:var(--formie-table-add-button-height);left:var(--formie-table-add-button-left);-webkit-mask-image:var(--formie-table-add-button-icon-mask);-webkit-mask-image:var(--formie-table-add-button-icon-mask);mask-image:var(--formie-table-add-button-icon-mask);background-color:currentColor;display:block;position:absolute;top:50%;transform:translateY(-50%);-webkit-mask-position:50%;mask-position:50%;-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat}}", o = "[data-formie-table-field-layout]", s = "[data-formie-table]", c = "[data-formie-table-body]", l = "[data-formie-table-row]", u = "[data-formie-table-add]", d = "[data-formie-table-remove]", f = "data-formie-template-id", p = "data-formie-table-row-id", m = "table";
t(m, [a]);
function h(e, t) {
	return n(e, t);
}
function g(e) {
	return e.querySelectorAll(l).length;
}
function _(e) {
	return Array.from(e.querySelectorAll(l)).reduce((e, t) => {
		let n = parseInt(t.getAttribute(p) || "", 10);
		return Number.isNaN(n) ? e : Math.max(e, n + 1);
	}, 0);
}
function v(e, t) {
	if (!e) return;
	let n = parseInt(e.getAttribute("data-formie-max-rows") || "", 10);
	e.disabled = n > 0 && t >= n;
}
function y(t, n) {
	let a = t.querySelector(s), o = t.querySelector(c), y = t.querySelector(u);
	if (!(a instanceof HTMLElement) || !(o instanceof HTMLElement)) return () => {};
	let b = /* @__PURE__ */ new Map(), x = _(t), S = () => {
		t.querySelectorAll(d).forEach((e) => {
			if (!(e instanceof HTMLElement) || b.has(e)) return;
			let n = (n) => {
				n.preventDefault();
				let r = e.closest(l);
				if (!(r instanceof HTMLElement)) return;
				let a = parseInt((y instanceof HTMLButtonElement ? y.getAttribute("data-formie-min-rows") : "") || "", 10);
				a > 0 && g(t) <= a || (r.remove(), v(y instanceof HTMLButtonElement ? y : null, g(t)), i(t, m, "remove", {
					table: t,
					row: r
				}));
			};
			e.addEventListener("click", n), b.set(e, n);
		});
	}, C = async () => {
		if (n.static || !(y instanceof HTMLButtonElement) || !y.getAttribute("data-formie-table-add")) return;
		let a = y.getAttribute(f) || t.getAttribute(f), s = parseInt(y.getAttribute("data-formie-max-rows") || "", 10);
		if (s > 0 && g(t) >= s) return;
		let c = h(t, a);
		if (!c) return;
		let l = r(c).replaceAll("__ROW__", String(x++)), u = document.createElement("tr");
		u.setAttribute("data-formie-table-row", "true"), u.setAttribute(p, String(x - 1)), u.innerHTML = l, o.appendChild(u), await e(50), S(), v(y, g(t)), i(t, m, "append", {
			table: t,
			row: u
		});
	}, w = (e) => {
		e.preventDefault(), C();
	};
	return y instanceof HTMLButtonElement && !n.static && y.addEventListener("click", w), S(), v(y instanceof HTMLButtonElement ? y : null, g(t)), i(t, m, "init", { table: t }), () => {
		y instanceof HTMLButtonElement && y.removeEventListener("click", w), b.forEach((e, t) => {
			t.removeEventListener("click", e);
		});
	};
}
var b = {
	id: m,
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(o) || !!e.target.querySelector(o)),
	setup: async (e) => {
		let t = e.options || {};
		if (!(e.target instanceof HTMLElement)) return;
		let n = e.target.matches(o) ? [e.target] : Array.from(e.target.querySelectorAll(o)).filter((e) => e instanceof HTMLElement), r = n.map((e) => y(e, t));
		return await e.emit("formie:module:table:init", { count: n.length }), { destroy: () => {
			r.forEach((e) => {
				e();
			}), e.emit("formie:module:table:destroy", {});
		} };
	}
};
//#endregion
export { b as tableModule };
