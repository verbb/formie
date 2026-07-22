import { K as u } from "./index-Mwww76TL.js";
import { b as l, r as c, a as m } from "./shared-BDEKVuB5.js";
const o = "input[data-formie-password-input]", p = [
  "passwordMinLength",
  "passwordUppercase",
  "passwordLowercase",
  "passwordSpecialCharacter"
], n = "password-validation";
function i(r) {
  return r instanceof HTMLInputElement && r.matches(o);
}
function f(r) {
  return parseInt(r.getAttribute("data-formie-password-min-length") || "", 10) || 0;
}
function w(r) {
  return r.hasAttribute("data-formie-password-min-length") || r.hasAttribute("data-formie-password-require-uppercase") || r.hasAttribute("data-formie-password-require-lowercase") || r.hasAttribute("data-formie-password-require-special-character");
}
function d(r) {
  return r.value === "";
}
function h(r) {
  c(r, n, (s) => {
    s.addValidator("passwordMinLength", ({ input: e }) => {
      if (!i(e))
        return !0;
      const a = f(e);
      return !a || d(e) ? !0 : u(e.value).graphemeCount >= a;
    }, ({ label: e, input: a, t }) => a.getAttribute("data-formie-validation-min-characters-message") || t("{label} must be no less than {min} characters.", {
      label: e,
      min: a.getAttribute("data-formie-password-min-length") || ""
    })), s.addValidator("passwordUppercase", ({ input: e }) => !i(e) || !e.hasAttribute("data-formie-password-require-uppercase") || d(e) ? !0 : /[A-Z]/.test(e.value), ({ label: e, input: a, t }) => a.getAttribute("data-formie-validation-password-uppercase-message") || t("{label} must contain at least one uppercase letter.", { label: e })), s.addValidator("passwordLowercase", ({ input: e }) => !i(e) || !e.hasAttribute("data-formie-password-require-lowercase") || d(e) ? !0 : /[a-z]/.test(e.value), ({ label: e, input: a, t }) => a.getAttribute("data-formie-validation-password-lowercase-message") || t("{label} must contain at least one lowercase letter.", { label: e })), s.addValidator("passwordSpecialCharacter", ({ input: e }) => !i(e) || !e.hasAttribute("data-formie-password-require-special-character") || d(e) ? !0 : /[^a-zA-Z0-9]/.test(e.value), ({ label: e, input: a, t }) => a.getAttribute("data-formie-validation-password-special-character-message") || t("{label} must contain at least one special character.", { label: e }));
  });
}
function g(r) {
  m(r, n, p);
}
const V = {
  id: "password-validation",
  kind: "field",
  match: (r) => !!r.target.querySelector(`${o}[data-formie-password-min-length], ${o}[data-formie-password-require-uppercase], ${o}[data-formie-password-require-lowercase], ${o}[data-formie-password-require-special-character]`),
  setup: async (r) => {
    const s = l(r), e = Array.from((s || r.target).querySelectorAll(o)).filter((a) => a instanceof HTMLInputElement && w(a));
    return h(r.form), await r.emit("formie:module:password-validation:init", {
      count: e.length
    }), {
      destroy: () => {
        g(r.form), r.emit("formie:module:password-validation:destroy", {});
      }
    };
  }
};
export {
  V as passwordValidationModule
};
