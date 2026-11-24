import { r as e } from "./event-names-BCI2FLD8.js";
//#region src/js/modules/fields/shared.ts
var t = (e) => e.replace(/["\\]/g, "\\$&"), n = /* @__PURE__ */ new WeakMap(), r = /* @__PURE__ */ new WeakMap();
function i(e, t, n, i) {
	let a = r.get(t) || /* @__PURE__ */ new Map(), o = a.get(n);
	if (o) return o;
	let s = null, c = () => {
		s && e?.removeEventListener("reset", s), r.get(t)?.delete(n);
	}, l = {
		afterReset: null,
		unmount: (e) => {
			l.afterReset === e && (l.afterReset = null), t.isConnected || c();
		}
	};
	if (a.set(n, l), r.set(t, a), !e) return l;
	let u = i();
	return s = (e) => {
		queueMicrotask(() => {
			e.defaultPrevented || (t.isConnected ? (u(), l.afterReset?.()) : c());
		});
	}, e.addEventListener("reset", s), l;
}
function a(e) {
	let t = e.cloneNode(!0);
	return t instanceof Element && t.querySelectorAll("input, textarea, select").forEach((e) => {
		e instanceof HTMLInputElement && e.type !== "hidden" ? (e.value = e.defaultValue, e.checked = e.defaultChecked) : e instanceof HTMLTextAreaElement ? e.value = e.defaultValue : e instanceof HTMLSelectElement && Array.from(e.options).forEach((e) => {
			e.selected = e.defaultSelected;
		});
	}), t;
}
function o(e, t) {
	let n = e.getRootNode(), r = (n) => {
		n.target instanceof Element && n.target.contains(e) && t();
	};
	return n.addEventListener("formie:field:clear", r), () => n.removeEventListener("formie:field:clear", r);
}
function s(e) {
	return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : t(e);
}
function c(e) {
	return e.target instanceof HTMLElement && e.target.hasAttribute("data-formie-field-handle") ? [e.target] : e.target instanceof HTMLElement ? Array.from(e.target.querySelectorAll("[data-formie-field-handle]")).filter((e) => e instanceof HTMLElement) : [];
}
function l(e) {
	return c(e)[0] || null;
}
function u(e) {
	return e && e.formieValidation || null;
}
function d(e, t, r) {
	if (!e) return;
	let i = n.get(e) || /* @__PURE__ */ new Map(), a = i.get(t) || 0;
	if (a === 0) {
		let t = u(e);
		t && r(t);
	}
	i.set(t, a + 1), n.set(e, i);
}
function f(e, t, r) {
	if (!e) return;
	let i = n.get(e), a = i?.get(t) || 0;
	if (a <= 1) {
		let a = u(e);
		if (r.forEach((e) => {
			a?.removeValidator(e);
		}), i?.delete(t), !i || i.size === 0) {
			n.delete(e);
			return;
		}
		n.set(e, i);
	} else i?.set(t, a - 1);
}
function p(e, t, n, r) {
	let i = /* @__PURE__ */ new Map(), a = (e) => {
		if (!n(e) || i.has(e)) return;
		let t = r(e);
		i.set(e, t || (() => {}));
	}, o = (e) => {
		e instanceof Element && e.matches(t) && a(e), e.querySelectorAll(t).forEach((e) => {
			a(e);
		});
	}, s = () => {
		i.forEach((t, n) => {
			e.contains(n) || (t(), i.delete(n));
		});
	};
	o(e);
	let c = new MutationObserver((e) => {
		e.forEach((e) => {
			e.addedNodes.forEach((e) => {
				e instanceof Element && o(e);
			});
		}), s();
	});
	return c.observe(e, {
		childList: !0,
		subtree: !0
	}), () => {
		c.disconnect(), i.forEach((e) => {
			e();
		}), i.clear();
	};
}
function m(e) {
	return e.ownerDocument || document;
}
function h(e, t) {
	let n = m(e);
	if (t) {
		let r = [
			e.querySelector(`template[data-formie-template-id="${s(t)}"]`),
			e.querySelector(`script[data-formie-template-id="${s(t)}"]`),
			n.querySelector(`template[data-formie-template-id="${s(t)}"]`),
			n.querySelector(`script[data-formie-template-id="${s(t)}"]`),
			n.getElementById(t)
		];
		for (let e of r) if (e instanceof HTMLTemplateElement || e instanceof HTMLScriptElement) return e;
	}
	return null;
}
function g(e) {
	return e instanceof HTMLTemplateElement ? e.innerHTML : e instanceof HTMLScriptElement ? e.textContent || "" : e.innerHTML;
}
function _(t, n, r, i) {
	let a = e(n, r);
	t.dispatchEvent(new CustomEvent(a, {
		bubbles: !0,
		detail: i
	}));
}
//#endregion
export { c as a, g as c, i as d, d as f, s as i, p as l, a as n, l as o, _ as r, h as s, o as t, f as u };
