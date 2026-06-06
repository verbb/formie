import { g as d } from "./shared--BU5CFoL.js";
const r = "input[data-formie-hidden-input]";
function u(e) {
  const n = document.cookie ? document.cookie.split("; ") : [];
  for (const i of n) {
    const t = i.split("=");
    if (decodeURIComponent(t.shift() || "") === e)
      return decodeURIComponent(t.join("="));
  }
  return null;
}
const s = {
  id: "hidden",
  kind: "field",
  match: (e) => !!e.target.querySelector(r),
  setup: async (e) => {
    const n = e.options || {}, i = n.cookieName ? u(n.cookieName) : null, t = d(e).map((o) => o.querySelector(r)).filter((o) => o instanceof HTMLInputElement);
    return i !== null && t.forEach((o) => {
      o.value = i;
    }), await e.emit("formie:module:hidden:init", {
      count: t.length
    }), {
      destroy: () => {
        e.emit("formie:module:hidden:destroy", {});
      }
    };
  }
};
export {
  s as hiddenModule
};
