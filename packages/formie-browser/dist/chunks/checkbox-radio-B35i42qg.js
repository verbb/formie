import { r as L, d as O, a as T, e as M } from "./shared-BDEKVuB5.js";
import { j as q } from "./index-CZtn5KAB.js";
const h = "[data-formie-checkboxes-field-layout], [data-formie-radio-field-layout]", m = "minmaxOptions", f = "otherOptionText", y = "data-formie-checkbox-radio-max-disabled", x = "checkbox-radio", v = "checkbox-radio", b = q("fields", "checkbox-radio");
function d(e) {
  return e.hasAttribute("data-checkbox-toggle") || e.hasAttribute("data-formie-checkbox-toggle");
}
function k(e) {
  const n = e(m);
  if (!n || n === !0 || typeof n != "object")
    return {
      min: null,
      max: null
    };
  const o = n;
  return {
    min: typeof o.min == "number" ? o.min : null,
    max: typeof o.max == "number" ? o.max : null
  };
}
function I(e) {
  return e.hasAttribute("data-formie-other-option") || !!e.closest("[data-formie-other-option]");
}
function p(e) {
  const n = [];
  return e.querySelectorAll("[data-formie-other-option-text]").forEach((o) => {
    if (!(o instanceof HTMLInputElement))
      return;
    const t = o.closest("[data-formie-field-option]") ?? o.parentElement;
    if (!t)
      return;
    const r = t.querySelector('input[type="checkbox"][data-formie-other-option], input[type="radio"][data-formie-other-option]') ?? t.querySelector('input[type="checkbox"], input[type="radio"]');
    r instanceof HTMLInputElement && n.push({ choiceInput: r, textInput: o });
  }), n;
}
function u(e) {
  p(e).forEach(({ choiceInput: n, textInput: o }) => {
    const t = n.checked;
    o.disabled = !t, t || (o.value = "");
  });
}
function S(e) {
  const n = p(e);
  if (!n.length)
    return () => {
    };
  const o = [];
  return Array.from(e.querySelectorAll('input[type="checkbox"], input[type="radio"]')).filter((t) => t instanceof HTMLInputElement && !d(t)).forEach((t) => {
    const r = () => {
      u(e);
    };
    t.addEventListener("change", r), o.push(() => {
      t.removeEventListener("change", r);
    });
  }), n.forEach(({ textInput: t }) => {
    const r = () => {
      u(e);
    };
    t.addEventListener("input", r), t.addEventListener("change", r), o.push(() => {
      t.removeEventListener("input", r), t.removeEventListener("change", r);
    });
  }), u(e), () => {
    o.forEach((t) => {
      t();
    });
  };
}
function g(e) {
  return Array.from(e.querySelectorAll('input[type="checkbox"]')).filter((n) => n instanceof HTMLInputElement && !d(n)).filter((n) => n.checked).length;
}
function C(e) {
  L(e, v, (n) => {
    n.addValidator(
      f,
      ({ field: o, getRule: t }) => !o || !t(f) ? !0 : p(o).every(({ choiceInput: r, textInput: a }) => r.checked ? a.value.trim() !== "" : !0),
      ({ field: o, label: t, t: r, getRule: a }) => {
        if (!o || !a(f))
          return r("{label} is invalid.", { label: t });
        const i = p(o).find(({ choiceInput: s }) => s.checked)?.choiceInput.closest("[data-formie-field-option]")?.querySelector("[data-formie-field-option-label]")?.textContent?.trim() ?? t;
        return o.getAttribute("data-formie-validation-other-option-text-message") ?? r("Please enter a value for “{label}”.", { label: i });
      }
    ), n.addValidator(
      m,
      ({ field: o, getRule: t }) => {
        if (!o || !t(m))
          return !0;
        const r = g(o), { min: a, max: c } = k(t);
        return !(a !== null && r < a || c !== null && r > c);
      },
      ({ field: o, label: t, t: r, getRule: a }) => {
        if (!o)
          return r("{label} is invalid.", { label: t });
        const c = g(o), { min: i, max: s } = k(a);
        return i !== null && c < i ? o.getAttribute("data-formie-validation-min-options-message") ?? r("{label} should contain at least {min, number} {min, plural, one{option} other{options}}.", { label: t, min: i }) : s !== null && c > s ? o.getAttribute("data-formie-validation-max-options-message") ?? r("{label} should contain at most {max, number} {max, plural, one{option} other{options}}.", { label: t, max: s }) : r("{label} is invalid.", { label: t });
      }
    );
  });
}
function H(e) {
  T(e, v, [m, f]);
}
function l(e) {
  e.checked ? e.setAttribute("checked", "") : e.removeAttribute("checked");
}
function E(e) {
  const n = Array.from(e.querySelectorAll('input[type="checkbox"][required][data-formie-checkbox-input]')).filter((t) => t instanceof HTMLInputElement);
  if (!n.length)
    return;
  const o = n.some((t) => t.checked);
  n.forEach((t) => {
    if (o) {
      t.removeAttribute("required"), t.setAttribute("aria-required", "false");
      return;
    }
    t.setAttribute("required", "true"), t.setAttribute("aria-required", "true");
  });
}
function A(e) {
  const n = parseInt(e.closest("[data-formie-field-handle]")?.getAttribute("data-formie-max-options") || "", 10);
  if (!(n > 0))
    return;
  const o = Array.from(e.querySelectorAll('input[type="checkbox"]')).filter((a) => a instanceof HTMLInputElement && !d(a) && !I(a)), r = o.filter((a) => a.checked).length >= n;
  o.forEach((a) => {
    const c = r && !a.checked, i = a.hasAttribute(y);
    if (c) {
      a.disabled || (a.disabled = !0, a.setAttribute(y, "true"));
      return;
    }
    i && (a.disabled = !1, a.removeAttribute(y));
  });
}
function D(e, n) {
  Array.from(e.querySelectorAll('input[type="checkbox"]')).filter((t) => t instanceof HTMLInputElement && t !== n && !d(t)).forEach((t) => {
    t.disabled && !t.checked || (t.checked = n.checked, l(t), t.dispatchEvent(new Event("change", { bubbles: !0 })), t.dispatchEvent(new Event("input", { bubbles: !0 })));
  });
}
function V(e, n) {
  if (!e.checked || !e.name) {
    l(e);
    return;
  }
  Array.from(n.querySelectorAll(`input[type="radio"][name="${M(e.name)}"]`)).filter((t) => t instanceof HTMLInputElement).forEach((t) => {
    l(t);
  });
}
function R(e) {
  const n = Array.from(e.querySelectorAll('input[type="checkbox"], input[type="radio"]')).filter((r) => r instanceof HTMLInputElement);
  if (!n.length)
    return b.log("No checkbox/radio inputs found for field."), () => {
    };
  const o = n.map((r) => {
    const a = r.type === "radio" ? "change" : "click", c = () => {
      l(r), r.type === "checkbox" && d(r) && D(e, r), r.type === "radio" && V(r, e), E(e), A(e), queueMicrotask(() => {
        u(e);
      }), b.log("Input interaction processed.", {
        inputName: r.name,
        inputType: r.type,
        checked: r.checked
      });
    };
    return r.addEventListener(a, c), l(r), () => {
      r.removeEventListener(a, c);
    };
  }), t = S(e);
  return E(e), A(e), u(e), O(e, x, "init", {
    checkboxRadio: e
  }), () => {
    o.forEach((r) => {
      r();
    }), t();
  };
}
const G = {
  id: x,
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(h) || !!e.target.querySelector(h)),
  setup: async (e) => {
    if (!(e.target instanceof HTMLElement))
      return;
    const n = e.target.matches(h) ? [e.target] : Array.from(e.target.querySelectorAll(h)).filter((t) => t instanceof HTMLElement);
    C(e.form), b.log("Module setup.", {
      fieldCount: n.length
    });
    const o = n.map((t) => R(t));
    return await e.emit("formie:module:checkbox-radio:init", {
      count: n.length
    }), {
      destroy: () => {
        o.forEach((t) => {
          t();
        }), H(e.form), b.log("Module destroy.", {
          fieldCount: n.length
        }), e.emit("formie:module:checkbox-radio:destroy", {});
      }
    };
  }
};
export {
  G as checkboxRadioModule
};
