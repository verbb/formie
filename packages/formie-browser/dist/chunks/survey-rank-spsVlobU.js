import { t as e } from "./debug-BV0DvdHx.js";
import { t } from "./styles-BfoIZwJp.js";
import { t as n } from "./shared-Bx9s0i0P.js";
import { t as r } from "./_survey-presentations-RbSqcQph.js";
//#region src/js/modules/fields/survey-rank.ts
var i = "[data-formie-survey-rank]", a = "[data-formie-survey-rank-list]", o = "[data-formie-survey-rank-item]", s = "[data-formie-rank-handle]", c = "formie-rank-placeholder", l = .42, u = "survey-rank", d = e("fields", "survey-rank");
t(u, [r]);
function f(e) {
	e.querySelectorAll(o).forEach((e, t) => {
		e instanceof HTMLElement && e.querySelectorAll("input[data-formie-rank-input]").forEach((e) => {
			e instanceof HTMLInputElement && (e.dataset.formieRankOrder = String(t));
		});
	});
}
function p(e) {
	return Array.from(e.querySelectorAll(o)).filter((e) => e instanceof HTMLElement);
}
function m(e, t) {
	return Array.from(e.children).indexOf(t);
}
function h(e, t, n) {
	return Array.from(e.children).filter((e) => e instanceof HTMLElement && e !== t && e !== n);
}
function g(e, t) {
	return Math.max(0, Math.min(e.bottom, t.bottom) - Math.max(e.top, t.top));
}
function _(e) {
	let t = e.getBoundingClientRect();
	return t.top + t.height / 2;
}
function v(e, t, n, r) {
	let i = e.getBoundingClientRect(), a = _(e), o = h(t, n, e);
	for (let e = 0; e < o.length; e += 1) {
		let t = o[e].getBoundingClientRect(), n = t.top + t.height * l;
		if (g(i, t) / t.height >= l) return r ? Math.min(e + 1, o.length) : e;
		if (a < n) return e;
	}
	return o.length;
}
function y(e, t, n, r) {
	let i = h(e, t, n)[r] ?? null;
	if (i) {
		e.insertBefore(t, i);
		return;
	}
	e.appendChild(t);
}
function b(e, t) {
	let n = document.createElement("li");
	return n.className = c, n.setAttribute("data-formie-rank-placeholder", "true"), n.setAttribute("aria-hidden", "true"), n.style.height = `${t.offsetHeight}px`, e.insertBefore(n, t), n;
}
function x(e) {
	return {
		position: e.style.position,
		left: e.style.left,
		top: e.style.top,
		width: e.style.width,
		zIndex: e.style.zIndex,
		pointerEvents: e.style.pointerEvents,
		margin: e.style.margin
	};
}
function S(e, t) {
	e.style.position = "fixed", e.style.left = `${t.left}px`, e.style.top = `${t.top}px`, e.style.width = `${t.width}px`, e.style.zIndex = "1000", e.style.pointerEvents = "none", e.style.margin = "0";
}
function C(e, t, n) {
	e.style.left = `${t.clientX - n.x}px`, e.style.top = `${t.clientY - n.y}px`;
}
function w(e, t) {
	e.style.position = t.position, e.style.left = t.left, e.style.top = t.top, e.style.width = t.width, e.style.zIndex = t.zIndex, e.style.pointerEvents = t.pointerEvents, e.style.margin = t.margin;
}
function T(e) {
	let t = e.querySelector(a);
	if (!(t instanceof HTMLElement)) return d.warn("Missing rank list; skipping field."), () => {};
	let r = p(t), i = e.closest("form"), o = null, c = null, l = null, h = null, g = null, T = null, E = null, D = null, O = null, k = !1, A = [], j = () => {
		if (o && c ? (t.insertBefore(o, c), c.remove()) : c && c.remove(), o && l && (w(o, l), o.removeAttribute("data-formie-rank-dragging")), g && T !== null) try {
			g.releasePointerCapture(T);
		} catch {}
		o = null, c = null, l = null, h = null, g = null, T = null, E = null, D = null, O = null, t.removeAttribute("data-formie-rank-sorting"), k && (f(t), n(e, u, "reorder", { rankField: e })), k = !1;
	}, M = (e) => {
		if (!o || !c || !h || e.pointerId !== T) return;
		e.preventDefault(), C(o, e, h);
		let n = _(o), r = O === null || n >= O;
		O = n;
		let i = v(o, t, c, r);
		i !== E && (E = i, y(t, c, o, i));
	}, N = (e) => {
		e.pointerId === T && (document.removeEventListener("pointermove", M), document.removeEventListener("pointerup", N), document.removeEventListener("pointercancel", N), c && D !== null && (k = m(t, c) !== D), j());
	};
	p(t).forEach((e) => {
		if (!e.querySelector(s)) return;
		let n = (n) => {
			if (n.button !== 0 || n.target instanceof HTMLInputElement) return;
			n.preventDefault();
			let r = e.getBoundingClientRect();
			o = e, g = e, T = n.pointerId, h = {
				x: n.clientX - r.left,
				y: n.clientY - r.top
			}, l = x(e), c = b(t, e), D = m(t, c), k = !1, S(e, r), e.setAttribute("data-formie-rank-dragging", "true"), t.setAttribute("data-formie-rank-sorting", "true"), e.setPointerCapture(n.pointerId), O = _(e), E = v(e, t, c, !0), document.addEventListener("pointermove", M), document.addEventListener("pointerup", N), document.addEventListener("pointercancel", N);
		};
		e.addEventListener("pointerdown", n), A.push(() => {
			e.removeEventListener("pointerdown", n);
		});
	});
	let P = (e) => {
		queueMicrotask(() => {
			e.defaultPrevented || (document.removeEventListener("pointermove", M), document.removeEventListener("pointerup", N), document.removeEventListener("pointercancel", N), j(), r.forEach((e) => t.appendChild(e)), f(t));
		});
	};
	return i?.addEventListener("reset", P), f(t), () => {
		i?.removeEventListener("reset", P), document.removeEventListener("pointermove", M), document.removeEventListener("pointerup", N), document.removeEventListener("pointercancel", N), A.forEach((e) => {
			e();
		}), j();
	};
}
var E = {
	moduleId: `formie:${u}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => e.target instanceof HTMLElement && (e.target.matches(i) || !!e.target.querySelector(i)),
	setup: async (e) => {
		if (!(e.target instanceof HTMLElement)) return;
		let t = e.target.matches(i) ? [e.target] : Array.from(e.target.querySelectorAll(i)).filter((e) => e instanceof HTMLElement);
		d.log("Module setup.", { fieldCount: t.length });
		let n = t.map((e) => T(e));
		return await e.emit("formie:module:survey-rank:init", { count: t.length }), { destroy: () => {
			n.forEach((e) => {
				e();
			}), d.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:survey-rank:destroy", {});
		} };
	}
};
//#endregion
export { E as surveyRankModule };
