import { z as u } from "./index-BEsfee-D.js";
import { b as L, r as w, a as T } from "./shared-DGqeMvcJ.js";
import { e as v } from "./styles-C3aqgtek.js";
const C = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}", c = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]", E = [
  "textMinCharacterLimit",
  "textMaxCharacterLimit",
  "textMinWordLimit",
  "textMaxWordLimit"
], A = "text-limit", f = "data-formie-text-limit-allow-overtype", n = /* @__PURE__ */ new WeakMap();
v("text-limit", [C]);
function d(t) {
  return t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement;
}
function m(t, i) {
  return parseInt(t.getAttribute(i) || "", 10) || 0;
}
function M(t) {
  return t.hasAttribute("data-formie-min-chars") || t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-min-words") || t.hasAttribute("data-formie-max-words");
}
function y(t) {
  return t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-max-words");
}
function h(t) {
  return t.hasAttribute(f);
}
function x(t) {
  return t.value === "";
}
function V(t) {
  w(t, A, (i) => {
    i.addValidator("textMinCharacterLimit", ({ input: e }) => {
      if (!d(e))
        return !0;
      const r = m(e, "data-formie-min-chars");
      return !r || x(e) ? !0 : u(e.value).graphemeCount >= r;
    }, ({ label: e, input: r, t: a }) => a("{attribute} must be no less than {min} characters.", {
      attribute: e,
      min: r.getAttribute("data-formie-min-chars") || ""
    })), i.addValidator("textMaxCharacterLimit", ({ input: e }) => {
      if (!d(e) || h(e))
        return !0;
      const r = m(e, "data-formie-max-chars");
      return !r || x(e) ? !0 : u(e.value).graphemeCount <= r;
    }, ({ label: e, input: r, t: a }) => a("{attribute} must be no greater than {max} characters.", {
      attribute: e,
      max: r.getAttribute("data-formie-max-chars") || ""
    })), i.addValidator("textMinWordLimit", ({ input: e }) => {
      if (!d(e))
        return !0;
      const r = m(e, "data-formie-min-words");
      return !r || e.value.trim() === "" ? !0 : u(e.value).wordCount >= r;
    }, ({ label: e, input: r, t: a }) => a("{attribute} must be no less than {min} words.", {
      attribute: e,
      min: r.getAttribute("data-formie-min-words") || ""
    })), i.addValidator("textMaxWordLimit", ({ input: e }) => {
      if (!d(e) || h(e))
        return !0;
      const r = m(e, "data-formie-max-words");
      return !r || e.value.trim() === "" ? !0 : u(e.value).wordCount <= r;
    }, ({ label: e, input: r, t: a }) => a("{attribute} must be no greater than {max} words.", {
      attribute: e,
      max: r.getAttribute("data-formie-max-words") || ""
    }));
  });
}
function I(t) {
  T(t, A, E);
}
function O(t) {
  if (n.has(t))
    return n.get(t) || null;
  const i = t.closest("[data-formie-field-handle]");
  if (!i)
    return n.set(t, null), null;
  const e = i.querySelector("[data-formie-limit-text]");
  if (e)
    return n.set(t, e), e;
  const r = i.querySelector("[data-formie-field-control]"), a = document.createElement("div");
  return a.className = "formie-field-limit formie-limit-text", a.setAttribute("data-formie-field-limit", "true"), a.setAttribute("data-formie-limit-text", "true"), r?.parentElement ? (r.insertAdjacentElement("afterend", a), n.set(t, a), a) : (i.appendChild(a), n.set(t, a), a);
}
function b(t, i, e) {
  const r = document.createElement("span");
  r.className = i < 0 ? "formie-limit-number formie-limit-number-error" : "formie-limit-number", r.textContent = String(i), t.replaceChildren(
    r,
    document.createTextNode(` ${Math.abs(i) === 1 ? e : `${e}s`} left`)
  );
}
function g(t) {
  const i = m(t, "data-formie-max-chars"), e = m(t, "data-formie-max-words"), r = O(t);
  if (!r)
    return;
  const a = u(t.value);
  if (i > 0) {
    const s = i - a.graphemeCount;
    b(r, s, "character");
    return;
  }
  if (e > 0) {
    const s = e - a.wordCount;
    b(r, s, "word");
  }
}
const _ = {
  id: "text-limit",
  kind: "field",
  match: (t) => !!t.target.querySelector(c),
  setup: async (t) => {
    const i = t.options || {}, e = L(t), r = Array.from((e || t.target).querySelectorAll(c)).filter((o) => (o instanceof HTMLInputElement || o instanceof HTMLTextAreaElement) && M(o)), a = r.filter((o) => y(o));
    i.allowOvertype && r.forEach((o) => {
      o.setAttribute(f, "true");
    }), V(t.form);
    const s = a.map((o) => {
      const l = () => {
        g(o);
      };
      return o.addEventListener("input", l), o.addEventListener("change", l), g(o), () => {
        o.removeEventListener("input", l), o.removeEventListener("change", l);
      };
    });
    return await t.emit("formie:module:text-limit:init", {
      count: r.length
    }), {
      destroy: () => {
        s.forEach((o) => {
          o();
        }), i.allowOvertype && r.forEach((o) => {
          o.removeAttribute(f);
        }), I(t.form), t.emit("formie:module:text-limit:destroy", {});
      }
    };
  }
};
export {
  _ as textLimitModule
};
