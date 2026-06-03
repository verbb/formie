import { d as p, c as L, f as y } from "./shared-DGqeMvcJ.js";
import { e as M } from "./styles-C3aqgtek.js";
import { h as k, s as A } from "./index-C1ZOKiAi.js";
const R = '@layer formie-theme{.formie-repeater-container{display:grid;gap:var(--formie-space-4)}.formie-repeater-item-wrapper{position:relative;display:grid;gap:var(--formie-space-4);padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-md);transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}.formie-repeater-item-wrapper:focus-within{border-color:var(--formie-focus-ring-border-color);box-shadow:var(--formie-shadow-focus)}.formie-field-has-error .formie-repeater-item-wrapper{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-repeater-item-wrapper:focus-within{box-shadow:var(--formie-shadow-danger-focus)}.formie-repeater-item-wrapper>.formie-repeater-remove-button{position:absolute;top:var(--formie-repeater-remove-button-top);right:var(--formie-repeater-remove-button-right);transform:var(--formie-repeater-remove-button-transform);font-size:0;line-height:0}.formie-button.formie-repeater-add-button{position:relative;display:inline-flex;align-items:center;justify-content:center;width:auto;max-width:100%;justify-self:start;padding-left:var(--formie-repeater-add-button-padding-left)}.formie-button.formie-repeater-add-button:before{position:absolute;content:"";display:block;width:var(--formie-repeater-add-button-width);height:var(--formie-repeater-add-button-height);left:var(--formie-repeater-add-button-left);top:50%;transform:translateY(-50%);background-color:currentColor;-webkit-mask-image:var(--formie-repeater-add-button-icon-mask);mask-image:var(--formie-repeater-add-button-icon-mask);-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;-webkit-mask-size:contain;mask-size:contain}}', u = "[data-formie-repeater-field-layout]", v = "[data-formie-repeater-container]", g = "[data-formie-repeater-item]", C = "[data-formie-repeater-add]", H = "[data-formie-repeater-remove]", E = "data-formie-template-id", l = "repeater", s = k("fields", "repeater");
M(l, [R]);
function S(e, o) {
  return y(e, o);
}
function _(e, o) {
  const t = document.createElement("div");
  return t.innerHTML = e.replaceAll("__ROW__", String(o)).trim(), t.firstElementChild instanceof HTMLElement ? t.firstElementChild : null;
}
function i(e) {
  return e.querySelectorAll(g).length;
}
function f(e, o) {
  if (!e)
    return;
  const t = parseInt(e.getAttribute("data-formie-max-rows") || "", 10);
  if (t > 0 && o >= t) {
    e.disabled = !0;
    return;
  }
  e.disabled = !1;
}
function I(e) {
  const o = e.matches(v) ? e : e.querySelector(v), t = e.querySelector(C);
  if (!(o instanceof HTMLElement))
    return s.warn("Missing repeater container; skipping field."), () => {
    };
  const n = /* @__PURE__ */ new Map();
  let T = Array.from(e.querySelectorAll(g)).reduce((r, a) => {
    const m = parseInt(a.getAttribute("data-formie-repeater-item-id") || "", 10);
    return Number.isNaN(m) ? r : Math.max(r, m + 1);
  }, 0);
  const w = () => {
    e.querySelectorAll(H).forEach((r) => {
      if (!(r instanceof HTMLElement) || n.has(r))
        return;
      const a = (m) => {
        m.preventDefault();
        const c = r.closest(g);
        if (!(c instanceof HTMLElement))
          return;
        const d = parseInt((t instanceof HTMLButtonElement ? t.getAttribute("data-formie-min-rows") : "") || "", 10);
        d > 0 && i(e) <= d || (c.remove(), f(t instanceof HTMLButtonElement ? t : null, i(e)), s.log("Row removed.", {
          rowCount: i(e)
        }), p(e, l, "remove", {
          repeater: e,
          row: c
        }));
      };
      r.addEventListener("click", a), n.set(r, a);
    });
  }, b = async () => {
    if (!(t instanceof HTMLButtonElement))
      return;
    const r = t.getAttribute("data-formie-repeater-add");
    if (!r) {
      s.warn("Add handle missing.");
      return;
    }
    const a = t.getAttribute(E) || e.getAttribute(E), m = parseInt(t.getAttribute("data-formie-max-rows") || "", 10);
    if (m > 0 && i(e) >= m)
      return;
    const c = S(e, a);
    if (!c) {
      s.warn("Template not found for add action.", { handle: r });
      return;
    }
    const d = _(L(c), T++);
    if (!d) {
      s.warn("Failed to build row from template.");
      return;
    }
    o.appendChild(d), await A(50), w(), f(t, i(e)), s.log("Row appended.", {
      rowCount: i(e)
    }), p(e, l, "append", {
      repeater: e,
      row: d
    }), p(e, l, "init-row", {
      repeater: e,
      row: d
    });
  }, h = (r) => {
    r.preventDefault(), b();
  };
  if (t instanceof HTMLButtonElement && t.addEventListener("click", h), w(), f(t instanceof HTMLButtonElement ? t : null, i(e)), t instanceof HTMLButtonElement && i(e) === 0) {
    const r = parseInt(t.getAttribute("data-formie-min-rows") || "", 10);
    for (let a = 0; a < r; a += 1)
      b();
  }
  return p(e, l, "init", {
    repeater: e
  }), s.log("Field initialized.", {
    rowCount: i(e)
  }), () => {
    t instanceof HTMLButtonElement && t.removeEventListener("click", h), n.forEach((r, a) => {
      a.removeEventListener("click", r);
    });
  };
}
const q = {
  id: l,
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(u) || !!e.target.querySelector(u)),
  setup: async (e) => {
    if (!(e.target instanceof HTMLElement))
      return;
    const o = e.target.matches(u) ? [e.target] : Array.from(e.target.querySelectorAll(u)).filter((n) => n instanceof HTMLElement), t = o.map((n) => I(n));
    return s.log("Module setup.", { fieldCount: o.length }), await e.emit("formie:module:repeater:init", {
      count: o.length
    }), {
      destroy: () => {
        t.forEach((n) => {
          n();
        }), s.log("Module destroy.", { fieldCount: o.length }), e.emit("formie:module:repeater:destroy", {});
      }
    };
  }
};
export {
  q as repeaterModule
};
