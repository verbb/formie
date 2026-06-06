import { z as l } from "./index-BEsfee-D.js";
import { f as L, r as T, a as w } from "./shared-D1wIMots.js";
import { e as E } from "./styles-C3aqgtek.js";
import { u as v } from "./index-Cmikarpm.js";
const C = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}", f = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]", M = [
  "textMinCharacterLimit",
  "textMaxCharacterLimit",
  "textMinWordLimit",
  "textMaxWordLimit"
], A = "text-limit", c = "data-formie-text-limit-allow-overtype", y = "{count, plural, one{character left} other{characters left}}", I = "{count, plural, one{word left} other{words left}}", n = /* @__PURE__ */ new WeakMap();
E("text-limit", [C]);
function d(t) {
  return t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement;
}
function m(t, i) {
  return parseInt(t.getAttribute(i) || "", 10) || 0;
}
function V(t) {
  return t.hasAttribute("data-formie-min-chars") || t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-min-words") || t.hasAttribute("data-formie-max-words");
}
function _(t) {
  return t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-max-words");
}
function h(t) {
  return t.hasAttribute(c);
}
function x(t) {
  return t.value === "";
}
function S(t) {
  T(t, A, (i) => {
    i.addValidator("textMinCharacterLimit", ({ input: e }) => {
      if (!d(e))
        return !0;
      const r = m(e, "data-formie-min-chars");
      return !r || x(e) ? !0 : l(e.value).graphemeCount >= r;
    }, ({ label: e, input: r, t: a }) => r.getAttribute("data-formie-validation-min-characters-message") || a("{label} must be no less than {min} characters.", {
      label: e,
      min: r.getAttribute("data-formie-min-chars") || ""
    })), i.addValidator("textMaxCharacterLimit", ({ input: e }) => {
      if (!d(e) || h(e))
        return !0;
      const r = m(e, "data-formie-max-chars");
      return !r || x(e) ? !0 : l(e.value).graphemeCount <= r;
    }, ({ label: e, input: r, t: a }) => r.getAttribute("data-formie-validation-max-characters-message") || a("{label} must be no greater than {max} characters.", {
      label: e,
      max: r.getAttribute("data-formie-max-chars") || ""
    })), i.addValidator("textMinWordLimit", ({ input: e }) => {
      if (!d(e))
        return !0;
      const r = m(e, "data-formie-min-words");
      return !r || e.value.trim() === "" ? !0 : l(e.value).wordCount >= r;
    }, ({ label: e, input: r, t: a }) => r.getAttribute("data-formie-validation-min-words-message") || a("{label} must be no less than {min} words.", {
      label: e,
      min: r.getAttribute("data-formie-min-words") || ""
    })), i.addValidator("textMaxWordLimit", ({ input: e }) => {
      if (!d(e) || h(e))
        return !0;
      const r = m(e, "data-formie-max-words");
      return !r || e.value.trim() === "" ? !0 : l(e.value).wordCount <= r;
    }, ({ label: e, input: r, t: a }) => r.getAttribute("data-formie-validation-max-words-message") || a("{label} must be no greater than {max} words.", {
      label: e,
      max: r.getAttribute("data-formie-max-words") || ""
    }));
  });
}
function O(t) {
  w(t, A, M);
}
function R(t) {
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
function g(t, i, e) {
  const r = document.createElement("span");
  r.className = i < 0 ? "formie-limit-number formie-limit-number-error" : "formie-limit-number", r.textContent = String(i);
  const a = e === "character" ? y : I;
  t.replaceChildren(
    r,
    document.createTextNode(` ${v(a, { count: Math.abs(i) })}`)
  );
}
function b(t) {
  const i = m(t, "data-formie-max-chars"), e = m(t, "data-formie-max-words"), r = R(t);
  if (!r)
    return;
  const a = l(t.value);
  if (i > 0) {
    const s = i - a.graphemeCount;
    g(r, s, "character");
    return;
  }
  if (e > 0) {
    const s = e - a.wordCount;
    g(r, s, "word");
  }
}
const q = {
  id: "text-limit",
  kind: "field",
  match: (t) => !!t.target.querySelector(f),
  setup: async (t) => {
    const i = t.options || {}, e = L(t), r = Array.from((e || t.target).querySelectorAll(f)).filter((o) => (o instanceof HTMLInputElement || o instanceof HTMLTextAreaElement) && V(o)), a = r.filter((o) => _(o));
    i.allowOvertype && r.forEach((o) => {
      o.setAttribute(c, "true");
    }), S(t.form);
    const s = a.map((o) => {
      const u = () => {
        b(o);
      };
      return o.addEventListener("input", u), o.addEventListener("change", u), b(o), () => {
        o.removeEventListener("input", u), o.removeEventListener("change", u);
      };
    });
    return await t.emit("formie:module:text-limit:init", {
      count: r.length
    }), {
      destroy: () => {
        s.forEach((o) => {
          o();
        }), i.allowOvertype && r.forEach((o) => {
          o.removeAttribute(c);
        }), O(t.form), t.emit("formie:module:text-limit:destroy", {});
      }
    };
  }
};
export {
  q as textLimitModule
};
