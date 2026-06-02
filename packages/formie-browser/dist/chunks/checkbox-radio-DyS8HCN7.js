import { r as A, d as E, a as p, e as M } from "./shared-8Shdc9Qt.js";
import { h as L } from "./index-MuyEvWaf.js";
const l = "[data-formie-checkboxes-field-layout], [data-formie-radio-field-layout]", d = "minmaxOptions", h = "data-formie-checkbox-radio-max-disabled", g = "checkbox-radio", x = "checkbox-radio", f = L("fields", "checkbox-radio");
function m(t) {
  return t.hasAttribute("data-checkbox-toggle") || t.hasAttribute("data-formie-checkbox-toggle");
}
function b(t) {
  const r = t(d);
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
function T(t) {
  A(t, x, (r) => {
    r.addValidator(
      d,
      ({ field: o, getRule: e }) => {
        if (!o || !e(d))
          return !0;
        const n = Array.from(o.querySelectorAll('input[type="checkbox"]')).filter((u) => u instanceof HTMLInputElement && !m(u)).filter((u) => u.checked).length, { min: a, max: i } = b(e);
        return !(a !== null && n < a || i !== null && n > i);
      },
      ({ field: o, label: e, t: c, getRule: n }) => {
        const { min: a, max: i } = o ? b(n) : { min: null, max: null };
        return a !== null && i !== null ? c("{attribute} must select between {min} and {max}.", { attribute: e, min: a, max: i }) : a !== null ? c("{attribute} must select no less than {min}.", { attribute: e, min: a }) : i !== null ? c("{attribute} must select no greater than {max}.", { attribute: e, max: i }) : c("{attribute} has an invalid value.", { attribute: e });
      }
    );
  });
}
function q(t) {
  p(t, x, [d]);
}
function s(t) {
  t.checked ? t.setAttribute("checked", "") : t.removeAttribute("checked");
}
function k(t) {
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
function y(t) {
  const r = parseInt(t.closest("[data-formie-field-handle]")?.getAttribute("data-formie-max-options") || "", 10);
  if (!(r > 0))
    return;
  const o = Array.from(t.querySelectorAll('input[type="checkbox"]')).filter((n) => n instanceof HTMLInputElement && !m(n)), c = o.filter((n) => n.checked).length >= r;
  o.forEach((n) => {
    const a = c && !n.checked, i = n.hasAttribute(h);
    if (a) {
      n.disabled || (n.disabled = !0, n.setAttribute(h, "true"));
      return;
    }
    i && (n.disabled = !1, n.removeAttribute(h));
  });
}
function v(t, r) {
  Array.from(t.querySelectorAll('input[type="checkbox"]')).filter((e) => e instanceof HTMLInputElement && e !== r && !m(e)).forEach((e) => {
    e.disabled && !e.checked || (e.checked = r.checked, s(e), e.dispatchEvent(new Event("change", { bubbles: !0 })), e.dispatchEvent(new Event("input", { bubbles: !0 })));
  });
}
function I(t, r) {
  if (!t.checked || !t.name) {
    s(t);
    return;
  }
  Array.from(r.querySelectorAll(`input[type="radio"][name="${M(t.name)}"]`)).filter((e) => e instanceof HTMLInputElement).forEach((e) => {
    s(e);
  });
}
function C(t) {
  const r = Array.from(t.querySelectorAll('input[type="checkbox"], input[type="radio"]')).filter((e) => e instanceof HTMLInputElement);
  if (!r.length)
    return f.log("No checkbox/radio inputs found for field."), () => {
    };
  const o = r.map((e) => {
    const c = e.type === "radio" ? "change" : "click", n = () => {
      s(e), e.type === "checkbox" && m(e) && v(t, e), e.type === "radio" && I(e, t), k(t), y(t), f.log("Input interaction processed.", {
        inputName: e.name,
        inputType: e.type,
        checked: e.checked
      });
    };
    return e.addEventListener(c, n), s(e), () => {
      e.removeEventListener(c, n);
    };
  });
  return k(t), y(t), E(t, g, "init", {
    checkboxRadio: t
  }), () => {
    o.forEach((e) => {
      e();
    });
  };
}
const H = {
  id: g,
  kind: "field",
  match: (t) => t.target instanceof HTMLElement && (t.target.matches(l) || !!t.target.querySelector(l)),
  setup: async (t) => {
    if (!(t.target instanceof HTMLElement))
      return;
    const r = t.target.matches(l) ? [t.target] : Array.from(t.target.querySelectorAll(l)).filter((e) => e instanceof HTMLElement);
    T(t.form), f.log("Module setup.", {
      fieldCount: r.length
    });
    const o = r.map((e) => C(e));
    return await t.emit("formie:module:checkbox-radio:init", {
      count: r.length
    }), {
      destroy: () => {
        o.forEach((e) => {
          e();
        }), q(t.form), f.log("Module destroy.", {
          fieldCount: r.length
        }), t.emit("formie:module:checkbox-radio:destroy", {});
      }
    };
  }
};
export {
  H as checkboxRadioModule
};
