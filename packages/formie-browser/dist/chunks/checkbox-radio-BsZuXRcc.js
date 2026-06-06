import { r as p, d as E, a as M, e as v } from "./shared-CeyaSMHs.js";
import { h as L } from "./index-CSO3KCTK.js";
const l = "[data-formie-checkboxes-field-layout], [data-formie-radio-field-layout]", u = "minmaxOptions", h = "data-formie-checkbox-radio-max-disabled", A = "checkbox-radio", x = "checkbox-radio", d = L("fields", "checkbox-radio");
function m(t) {
  return t.hasAttribute("data-checkbox-toggle") || t.hasAttribute("data-formie-checkbox-toggle");
}
function b(t) {
  const r = t(u);
  if (!r || r === !0 || typeof r != "object")
    return {
      min: null,
      max: null
    };
  const o = r;
  return {
    min: typeof o.min == "number" ? o.min : null,
    max: typeof o.max == "number" ? o.max : null
  };
}
function k(t) {
  return Array.from(t.querySelectorAll('input[type="checkbox"]')).filter((r) => r instanceof HTMLInputElement && !m(r)).filter((r) => r.checked).length;
}
function T(t) {
  p(t, x, (r) => {
    r.addValidator(
      u,
      ({ field: o, getRule: e }) => {
        if (!o || !e(u))
          return !0;
        const a = k(o), { min: n, max: c } = b(e);
        return !(n !== null && a < n || c !== null && a > c);
      },
      ({ field: o, label: e, t: a, getRule: n }) => {
        if (!o)
          return a("{label} has an invalid value.", { label: e });
        const c = k(o), { min: i, max: f } = b(n);
        return i !== null && c < i ? o.getAttribute("data-formie-validation-min-options-message") ?? a("{label} should contain at least {min, number} {min, plural, one{option} other{options}}.", { label: e, min: i }) : f !== null && c > f ? o.getAttribute("data-formie-validation-max-options-message") ?? a("{label} should contain at most {max, number} {max, plural, one{option} other{options}}.", { label: e, max: f }) : a("{label} has an invalid value.", { label: e });
      }
    );
  });
}
function q(t) {
  M(t, x, [u]);
}
function s(t) {
  t.checked ? t.setAttribute("checked", "") : t.removeAttribute("checked");
}
function y(t) {
  const r = Array.from(t.querySelectorAll('input[type="checkbox"][required][data-formie-checkbox-input]')).filter((e) => e instanceof HTMLInputElement);
  if (!r.length)
    return;
  const o = r.some((e) => e.checked);
  r.forEach((e) => {
    if (o) {
      e.removeAttribute("required"), e.setAttribute("aria-required", "false");
      return;
    }
    e.setAttribute("required", "true"), e.setAttribute("aria-required", "true");
  });
}
function g(t) {
  const r = parseInt(t.closest("[data-formie-field-handle]")?.getAttribute("data-formie-max-options") || "", 10);
  if (!(r > 0))
    return;
  const o = Array.from(t.querySelectorAll('input[type="checkbox"]')).filter((n) => n instanceof HTMLInputElement && !m(n)), a = o.filter((n) => n.checked).length >= r;
  o.forEach((n) => {
    const c = a && !n.checked, i = n.hasAttribute(h);
    if (c) {
      n.disabled || (n.disabled = !0, n.setAttribute(h, "true"));
      return;
    }
    i && (n.disabled = !1, n.removeAttribute(h));
  });
}
function I(t, r) {
  Array.from(t.querySelectorAll('input[type="checkbox"]')).filter((e) => e instanceof HTMLInputElement && e !== r && !m(e)).forEach((e) => {
    e.disabled && !e.checked || (e.checked = r.checked, s(e), e.dispatchEvent(new Event("change", { bubbles: !0 })), e.dispatchEvent(new Event("input", { bubbles: !0 })));
  });
}
function C(t, r) {
  if (!t.checked || !t.name) {
    s(t);
    return;
  }
  Array.from(r.querySelectorAll(`input[type="radio"][name="${v(t.name)}"]`)).filter((e) => e instanceof HTMLInputElement).forEach((e) => {
    s(e);
  });
}
function S(t) {
  const r = Array.from(t.querySelectorAll('input[type="checkbox"], input[type="radio"]')).filter((e) => e instanceof HTMLInputElement);
  if (!r.length)
    return d.log("No checkbox/radio inputs found for field."), () => {
    };
  const o = r.map((e) => {
    const a = e.type === "radio" ? "change" : "click", n = () => {
      s(e), e.type === "checkbox" && m(e) && I(t, e), e.type === "radio" && C(e, t), y(t), g(t), d.log("Input interaction processed.", {
        inputName: e.name,
        inputType: e.type,
        checked: e.checked
      });
    };
    return e.addEventListener(a, n), s(e), () => {
      e.removeEventListener(a, n);
    };
  });
  return y(t), g(t), E(t, A, "init", {
    checkboxRadio: t
  }), () => {
    o.forEach((e) => {
      e();
    });
  };
}
const O = {
  id: A,
  kind: "field",
  match: (t) => t.target instanceof HTMLElement && (t.target.matches(l) || !!t.target.querySelector(l)),
  setup: async (t) => {
    if (!(t.target instanceof HTMLElement))
      return;
    const r = t.target.matches(l) ? [t.target] : Array.from(t.target.querySelectorAll(l)).filter((e) => e instanceof HTMLElement);
    T(t.form), d.log("Module setup.", {
      fieldCount: r.length
    });
    const o = r.map((e) => S(e));
    return await t.emit("formie:module:checkbox-radio:init", {
      count: r.length
    }), {
      destroy: () => {
        o.forEach((e) => {
          e();
        }), q(t.form), d.log("Module destroy.", {
          fieldCount: r.length
        }), t.emit("formie:module:checkbox-radio:destroy", {});
      }
    };
  }
};
export {
  O as checkboxRadioModule
};
