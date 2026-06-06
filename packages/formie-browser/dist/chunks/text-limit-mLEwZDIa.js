import { z as u } from "./index-BEsfee-D.js";
import { f as b, r as w, a as E } from "./shared--BU5CFoL.js";
import { e as v } from "./styles-C3aqgtek.js";
import { u as C } from "./index-D6npYxvr.js";
const M = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}", f = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]", _ = [
  "textMinCharacterLimit",
  "textMaxCharacterLimit",
  "textMinWordLimit",
  "textMaxWordLimit"
], L = "text-limit", d = "data-formie-text-limit-allow-overtype", I = "{count, plural, one{character allowed} other{characters allowed}}", y = "{count, plural, one{character left} other{characters left}}", R = "{count, plural, one{character over limit} other{characters over limit}}", O = "{count, plural, one{word allowed} other{words allowed}}", S = "{count, plural, one{word left} other{words left}}", V = "{count, plural, one{word over limit} other{words over limit}}", s = /* @__PURE__ */ new WeakMap();
v("text-limit", [M]);
function c(t) {
  return t instanceof HTMLInputElement || t instanceof HTMLTextAreaElement;
}
function l(t, a) {
  return parseInt(t.getAttribute(a) || "", 10) || 0;
}
function W(t) {
  return t.hasAttribute("data-formie-min-chars") || t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-min-words") || t.hasAttribute("data-formie-max-words");
}
function D(t) {
  return t.hasAttribute("data-formie-max-chars") || t.hasAttribute("data-formie-max-words");
}
function h(t) {
  return t.hasAttribute(d);
}
function x(t) {
  return t.value === "";
}
function H(t) {
  w(t, L, (a) => {
    a.addValidator("textMinCharacterLimit", ({ input: e }) => {
      if (!c(e))
        return !0;
      const r = l(e, "data-formie-min-chars");
      return !r || x(e) ? !0 : u(e.value).graphemeCount >= r;
    }, ({ label: e, input: r, t: o }) => r.getAttribute("data-formie-validation-min-characters-message") || o("{label} must be no less than {min} characters.", {
      label: e,
      min: r.getAttribute("data-formie-min-chars") || ""
    })), a.addValidator("textMaxCharacterLimit", ({ input: e }) => {
      if (!c(e) || h(e))
        return !0;
      const r = l(e, "data-formie-max-chars");
      return !r || x(e) ? !0 : u(e.value).graphemeCount <= r;
    }, ({ label: e, input: r, t: o }) => r.getAttribute("data-formie-validation-max-characters-message") || o("{label} must be no greater than {max} characters.", {
      label: e,
      max: r.getAttribute("data-formie-max-chars") || ""
    })), a.addValidator("textMinWordLimit", ({ input: e }) => {
      if (!c(e))
        return !0;
      const r = l(e, "data-formie-min-words");
      return !r || e.value.trim() === "" ? !0 : u(e.value).wordCount >= r;
    }, ({ label: e, input: r, t: o }) => r.getAttribute("data-formie-validation-min-words-message") || o("{label} must be no less than {min} words.", {
      label: e,
      min: r.getAttribute("data-formie-min-words") || ""
    })), a.addValidator("textMaxWordLimit", ({ input: e }) => {
      if (!c(e) || h(e))
        return !0;
      const r = l(e, "data-formie-max-words");
      return !r || e.value.trim() === "" ? !0 : u(e.value).wordCount <= r;
    }, ({ label: e, input: r, t: o }) => r.getAttribute("data-formie-validation-max-words-message") || o("{label} must be no greater than {max} words.", {
      label: e,
      max: r.getAttribute("data-formie-max-words") || ""
    }));
  });
}
function X(t) {
  E(t, L, _);
}
function F(t) {
  if (s.has(t))
    return s.get(t) || null;
  const a = t.closest("[data-formie-field-handle]");
  if (!a)
    return s.set(t, null), null;
  const e = a.querySelector("[data-formie-limit-text]");
  if (e)
    return s.set(t, e), e;
  const r = a.querySelector("[data-formie-field-control]"), o = document.createElement("div");
  return o.className = "formie-field-limit formie-limit-text", o.setAttribute("data-formie-field-limit", "true"), o.setAttribute("data-formie-limit-text", "true"), r?.parentElement ? (r.insertAdjacentElement("afterend", o), s.set(t, o), o) : (a.appendChild(o), s.set(t, o), o);
}
function N(t, a, e) {
  return (e === "character" ? t.value === "" : t.value.trim() === "") ? "allowed" : a < 0 ? "over" : "left";
}
function q(t, a) {
  return t === "character" ? a === "allowed" ? I : a === "over" ? R : y : a === "allowed" ? O : a === "over" ? V : S;
}
function T(t, a, e, r, o) {
  const n = N(a, e, o), i = n === "allowed" ? r : Math.abs(e), m = document.createElement("span");
  m.className = n === "over" ? "formie-limit-number formie-limit-number-error" : "formie-limit-number", m.textContent = String(i);
  const A = q(o, n);
  t.replaceChildren(
    m,
    document.createTextNode(` ${C(A, { count: i })}`)
  );
}
function g(t) {
  const a = l(t, "data-formie-max-chars"), e = l(t, "data-formie-max-words"), r = F(t);
  if (!r)
    return;
  const o = u(t.value);
  if (a > 0) {
    const n = a - o.graphemeCount;
    T(r, t, n, a, "character");
    return;
  }
  if (e > 0) {
    const n = e - o.wordCount;
    T(r, t, n, e, "word");
  }
}
const j = {
  id: "text-limit",
  kind: "field",
  match: (t) => !!t.target.querySelector(f),
  setup: async (t) => {
    const a = t.options || {}, e = b(t), r = Array.from((e || t.target).querySelectorAll(f)).filter((i) => (i instanceof HTMLInputElement || i instanceof HTMLTextAreaElement) && W(i)), o = r.filter((i) => D(i));
    a.allowOvertype && r.forEach((i) => {
      i.setAttribute(d, "true");
    }), H(t.form);
    const n = o.map((i) => {
      const m = () => {
        g(i);
      };
      return i.addEventListener("input", m), i.addEventListener("change", m), g(i), () => {
        i.removeEventListener("input", m), i.removeEventListener("change", m);
      };
    });
    return await t.emit("formie:module:text-limit:init", {
      count: r.length
    }), {
      destroy: () => {
        n.forEach((i) => {
          i();
        }), a.allowOvertype && r.forEach((i) => {
          i.removeAttribute(d);
        }), X(t.form), t.emit("formie:module:text-limit:destroy", {});
      }
    };
  }
};
export {
  j as textLimitModule
};
