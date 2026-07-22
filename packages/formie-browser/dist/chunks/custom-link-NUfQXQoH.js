import { b as c, o as l } from "./shared-BDEKVuB5.js";
const a = "custom-link", u = "[data-formie-custom-link]";
function i(t, e) {
  return t.getAttribute(e) === "1";
}
function s(t, e) {
  return e === "email" ? {
    type: "email",
    inputMode: "email",
    autocomplete: "email"
  } : e === "tel" || e === "sms" ? {
    type: "tel",
    inputMode: "tel",
    autocomplete: "tel"
  } : {
    type: !i(t, "data-formie-custom-link-allow-root-relative") && !i(t, "data-formie-custom-link-allow-anchors") && !i(t, "data-formie-custom-link-allow-custom-schemes") ? "url" : "text",
    inputMode: "url",
    autocomplete: "url"
  };
}
function m(t) {
  return t instanceof HTMLElement && t.matches(u);
}
function p(t) {
  const e = t.querySelector("[data-formie-custom-link-type]"), n = t.querySelector("[data-formie-custom-link-value]");
  if (!(e instanceof HTMLSelectElement) || !(n instanceof HTMLInputElement))
    return () => {
    };
  const o = () => {
    const r = s(t, e.value);
    n.type = r.type, n.inputMode = r.inputMode, n.setAttribute("autocomplete", r.autocomplete);
  };
  return o(), e.addEventListener("change", o), () => {
    e.removeEventListener("change", o);
  };
}
const d = {
  id: a,
  kind: "field",
  match: ({ target: t }) => t instanceof Element && (t.matches(u) || !!t.querySelector(u)),
  setup: async (t) => {
    const n = c(t) || t.target;
    return n instanceof Element ? {
      destroy: l(n, u, m, p)
    } : void 0;
  }
};
export {
  d as customLinkModule
};
