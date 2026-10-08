import { r as e } from "./event-names-BCI2FLD8.js";
//#region src/js/modules/fields/shared.ts
var t = (e) => e.replace(/["\\]/g, "\\$&"), n = /* @__PURE__ */ new WeakMap();
function r(e, t) {
	let n = e.getRootNode(), r = (n) => {
		n.target instanceof Element && n.target.contains(e) && t();
	};
	return n.addEventListener("formie:field:clear", r), () => n.removeEventListener("formie:field:clear", r);
}
function i(e) {
	return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : t(e);
}
function a(e) {
	return e.target instanceof HTMLElement && e.target.hasAttribute("data-formie-field-handle") ? [e.target] : e.target instanceof HTMLElement ? Array.from(e.target.querySelectorAll("[data-formie-field-handle]")).filter((e) => e instanceof HTMLElement) : [];
}
function o(e) {
	return a(e)[0] || null;
}
function s(e) {
	return e && e.formieValidation || null;
}
function c(e, t, r) {
	if (!e) return;
	let i = n.get(e) || /* @__PURE__ */ new Map(), a = i.get(t) || 0;
	if (a === 0) {
		let t = s(e);
		t && r(t);
	}
	i.set(t, a + 1), n.set(e, i);
}
function l(e, t, r) {
	if (!e) return;
	let i = n.get(e), a = i?.get(t) || 0;
	if (a <= 1) {
		let a = s(e);
		if (r.forEach((e) => {
			a?.removeValidator(e);
		}), i?.delete(t), !i || i.size === 0) {
			n.delete(e);
			return;
		}
		n.set(e, i);
		return;
	}
	i?.set(t, a - 1);
}
function u(e, t, n, r) {
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
function d(e) {
	return e.ownerDocument || document;
}
function f(e, t) {
	let n = d(e);
	if (t) {
		let r = [
			e.querySelector(`template[data-formie-template-id="${i(t)}"]`),
			e.querySelector(`script[data-formie-template-id="${i(t)}"]`),
			n.querySelector(`template[data-formie-template-id="${i(t)}"]`),
			n.querySelector(`script[data-formie-template-id="${i(t)}"]`),
			n.getElementById(t)
		];
		for (let e of r) if (e instanceof HTMLTemplateElement || e instanceof HTMLScriptElement) return e;
	}
	return null;
}
function p(e) {
	return e instanceof HTMLTemplateElement ? e.innerHTML : e instanceof HTMLScriptElement ? e.textContent || "" : e.innerHTML;
}
function m(t, n, r, i) {
	let a = e(n, r);
	t.dispatchEvent(new CustomEvent(a, {
		bubbles: !0,
		detail: i
	}));
}
//#endregion
export { o as a, u as c, a as i, l, m as n, f as o, i as r, p as s, r as t, c as u };
