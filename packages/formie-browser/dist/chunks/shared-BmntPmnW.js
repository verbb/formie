import { k as u } from "./index-BcqwpPNE.js";
const f = (e) => e.replace(/["\\]/g, "\\$&"), a = /* @__PURE__ */ new WeakMap();
function l(e) {
  return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : f(e);
}
function d(e) {
  return e.target instanceof HTMLElement && e.target.hasAttribute("data-formie-field-handle") ? [e.target] : e.target instanceof HTMLElement ? Array.from(e.target.querySelectorAll("[data-formie-field-handle]")).filter((t) => t instanceof HTMLElement) : [];
}
function g(e) {
  return d(e)[0] || null;
}
function s(e) {
  return e && e.formieValidation || null;
}
function E(e, t, i) {
  if (!e)
    return;
  const n = a.get(e) || /* @__PURE__ */ new Map(), r = n.get(t) || 0;
  if (r === 0) {
    const o = s(e);
    o && i(o);
  }
  n.set(t, r + 1), a.set(e, n);
}
function M(e, t, i) {
  if (!e)
    return;
  const n = a.get(e), r = n?.get(t) || 0;
  if (r <= 1) {
    const o = s(e);
    if (i.forEach((c) => {
      o?.removeValidator(c);
    }), n?.delete(t), !n || n.size === 0) {
      a.delete(e);
      return;
    }
    a.set(e, n);
    return;
  }
  n?.set(t, r - 1);
}
function m(e) {
  return e.ownerDocument || document;
}
function S(e, t) {
  const i = m(e);
  if (t) {
    const n = [
      e.querySelector(`template[data-formie-template-id="${l(t)}"]`),
      e.querySelector(`script[data-formie-template-id="${l(t)}"]`),
      i.querySelector(`template[data-formie-template-id="${l(t)}"]`),
      i.querySelector(`script[data-formie-template-id="${l(t)}"]`),
      i.getElementById(t)
    ];
    for (const r of n)
      if (r instanceof HTMLTemplateElement || r instanceof HTMLScriptElement)
        return r;
  }
  return null;
}
function T(e) {
  return e instanceof HTMLTemplateElement ? e.innerHTML : e instanceof HTMLScriptElement ? e.textContent || "" : e.innerHTML;
}
function H(e, t, i, n) {
  const r = u(t, i);
  e.dispatchEvent(new CustomEvent(r, {
    bubbles: !0,
    detail: n
  }));
}
export {
  M as a,
  T as b,
  S as c,
  H as d,
  l as e,
  g as f,
  d as g,
  E as r
};
