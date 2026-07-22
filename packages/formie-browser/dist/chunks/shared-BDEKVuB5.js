import { l as g } from "./index-CZtn5KAB.js";
const E = (e) => e.replace(/["\\]/g, "\\$&"), c = /* @__PURE__ */ new WeakMap();
function u(e) {
  return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : E(e);
}
function h(e) {
  return e.target instanceof HTMLElement && e.target.hasAttribute("data-formie-field-handle") ? [e.target] : e.target instanceof HTMLElement ? Array.from(e.target.querySelectorAll("[data-formie-field-handle]")).filter((t) => t instanceof HTMLElement) : [];
}
function T(e) {
  return h(e)[0] || null;
}
function m(e) {
  return e && e.formieValidation || null;
}
function v(e, t, i) {
  if (!e)
    return;
  const r = c.get(e) || /* @__PURE__ */ new Map(), n = r.get(t) || 0;
  if (n === 0) {
    const s = m(e);
    s && i(s);
  }
  r.set(t, n + 1), c.set(e, r);
}
function b(e, t, i) {
  if (!e)
    return;
  const r = c.get(e), n = r?.get(t) || 0;
  if (n <= 1) {
    const s = m(e);
    if (i.forEach((l) => {
      s?.removeValidator(l);
    }), r?.delete(t), !r || r.size === 0) {
      c.delete(e);
      return;
    }
    c.set(e, r);
    return;
  }
  r?.set(t, n - 1);
}
function w(e, t, i, r) {
  const n = /* @__PURE__ */ new Map(), s = (a) => {
    if (!i(a) || n.has(a))
      return;
    const o = r(a);
    n.set(a, o || (() => {
    }));
  }, l = (a) => {
    a instanceof Element && a.matches(t) && s(a), a.querySelectorAll(t).forEach((o) => {
      s(o);
    });
  }, p = () => {
    n.forEach((a, o) => {
      e.contains(o) || (a(), n.delete(o));
    });
  };
  l(e);
  const f = new MutationObserver((a) => {
    a.forEach((o) => {
      o.addedNodes.forEach((d) => {
        d instanceof Element && l(d);
      });
    }), p();
  });
  return f.observe(e, {
    childList: !0,
    subtree: !0
  }), () => {
    f.disconnect(), n.forEach((a) => {
      a();
    }), n.clear();
  };
}
function M(e) {
  return e.ownerDocument || document;
}
function H(e, t) {
  const i = M(e);
  if (t) {
    const r = [
      e.querySelector(`template[data-formie-template-id="${u(t)}"]`),
      e.querySelector(`script[data-formie-template-id="${u(t)}"]`),
      i.querySelector(`template[data-formie-template-id="${u(t)}"]`),
      i.querySelector(`script[data-formie-template-id="${u(t)}"]`),
      i.getElementById(t)
    ];
    for (const n of r)
      if (n instanceof HTMLTemplateElement || n instanceof HTMLScriptElement)
        return n;
  }
  return null;
}
function L(e) {
  return e instanceof HTMLTemplateElement ? e.innerHTML : e instanceof HTMLScriptElement ? e.textContent || "" : e.innerHTML;
}
function C(e, t, i, r) {
  const n = g(t, i);
  e.dispatchEvent(new CustomEvent(n, {
    bubbles: !0,
    detail: r
  }));
}
export {
  b as a,
  T as b,
  L as c,
  C as d,
  u as e,
  H as f,
  h as g,
  w as o,
  v as r
};
