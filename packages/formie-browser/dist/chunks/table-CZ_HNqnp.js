import { d as h, c as L, f as A } from "./shared-DGn4SKv5.js";
import { e as M } from "./styles-C3aqgtek.js";
import { s as R } from "./index-7j3Qw3EW.js";
const S = '@layer formie-theme{.formie-table-wrapper{max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch}.formie-table{width:var(--formie-table-width);margin-bottom:var(--formie-table-margin-bottom);border-collapse:var(--formie-table-border-collapse)}.formie-table th{text-align:var(--formie-table-th-text-align);font-size:var(--formie-table-th-font-size);font-weight:var(--formie-table-th-font-weight);color:var(--formie-table-th-color, var(--formie-gray-500))}.formie-table th,.formie-table td{padding:var(--formie-table-row-padding);vertical-align:top}.formie-table th:first-child,.formie-table td:first-child{padding-left:0}.formie-table th:last-child,.formie-table td:last-child{padding-right:0}.formie-table [data-col-remove]{width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));min-width:calc(var(--formie-button-icon-button-size) + (var(--formie-table-row-padding) * 2));white-space:nowrap;text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox]{text-align:center;vertical-align:middle}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option{display:flex;align-items:center;justify-content:center;width:100%;min-height:var(--formie-check-size);margin:0}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label{display:block;width:var(--formie-check-size);min-width:var(--formie-check-size);height:var(--formie-check-size);margin:0 auto;padding-left:0;font-size:0;line-height:0}.formie-table [data-formie-table-column-type=checkbox] .formie-checkbox-option-label:before{position:static}.formie-table-color-input{min-width:4rem;padding:var(--formie-space-1)}.formie-table-multiline-input{min-height:calc(var(--formie-control-height) + var(--formie-space-2))}.formie-table-remove-button{display:inline-flex;vertical-align:middle}.formie-button.formie-table-add-button{position:relative;display:inline-flex;align-items:center;justify-content:center;width:auto;max-width:100%;justify-self:start;padding-left:var(--formie-table-add-button-padding-left)}.formie-button.formie-table-add-button:before{position:absolute;content:"";display:block;width:var(--formie-table-add-button-width);height:var(--formie-table-add-button-height);left:var(--formie-table-add-button-left);top:50%;transform:translateY(-50%);background-color:currentColor;-webkit-mask-image:var(--formie-table-add-button-icon-mask);mask-image:var(--formie-table-add-button-icon-mask);-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;-webkit-mask-size:contain;mask-size:contain}}', b = "[data-formie-table-field-layout]", H = "[data-formie-table]", _ = "[data-formie-table-body]", g = "[data-formie-table-row]", x = "[data-formie-table-add]", O = "[data-formie-table-remove]", y = "data-formie-template-id", k = "data-formie-table-row-id", s = "table";
M(s, [S]);
function z(e, o) {
  return A(e, o);
}
function c(e) {
  return e.querySelectorAll(g).length;
}
function B(e) {
  return Array.from(e.querySelectorAll(g)).reduce((o, r) => {
    const i = parseInt(r.getAttribute(k) || "", 10);
    return Number.isNaN(i) ? o : Math.max(o, i + 1);
  }, 0);
}
function p(e, o) {
  if (!e)
    return;
  const r = parseInt(e.getAttribute("data-formie-max-rows") || "", 10);
  e.disabled = r > 0 && o >= r;
}
function C(e, o) {
  const r = e.querySelector(H), i = e.querySelector(_), t = e.querySelector(x);
  if (!(r instanceof HTMLElement) || !(i instanceof HTMLElement))
    return () => {
    };
  const u = /* @__PURE__ */ new Map();
  let v = B(e);
  const w = () => {
    e.querySelectorAll(O).forEach((a) => {
      if (!(a instanceof HTMLElement) || u.has(a))
        return;
      const n = (d) => {
        d.preventDefault();
        const l = a.closest(g);
        if (!(l instanceof HTMLElement))
          return;
        const f = parseInt((t instanceof HTMLButtonElement ? t.getAttribute("data-formie-min-rows") : "") || "", 10);
        f > 0 && c(e) <= f || (l.remove(), p(t instanceof HTMLButtonElement ? t : null, c(e)), h(e, s, "remove", {
          table: e,
          row: l
        }));
      };
      a.addEventListener("click", n), u.set(a, n);
    });
  }, T = async () => {
    if (o.static || !(t instanceof HTMLButtonElement) || !t.getAttribute("data-formie-table-add"))
      return;
    const n = t.getAttribute(y) || e.getAttribute(y), d = parseInt(t.getAttribute("data-formie-max-rows") || "", 10);
    if (d > 0 && c(e) >= d)
      return;
    const l = z(e, n);
    if (!l)
      return;
    const f = L(l).replaceAll("__ROW__", String(v++)), m = document.createElement("tr");
    m.setAttribute("data-formie-table-row", "true"), m.setAttribute(k, String(v - 1)), m.innerHTML = f, i.appendChild(m), await R(50), w(), p(t, c(e)), h(e, s, "append", {
      table: e,
      row: m
    });
  }, E = (a) => {
    a.preventDefault(), T();
  };
  return t instanceof HTMLButtonElement && !o.static && t.addEventListener("click", E), w(), p(t instanceof HTMLButtonElement ? t : null, c(e)), h(e, s, "init", {
    table: e
  }), () => {
    t instanceof HTMLButtonElement && t.removeEventListener("click", E), u.forEach((a, n) => {
      n.removeEventListener("click", a);
    });
  };
}
const N = {
  id: s,
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(b) || !!e.target.querySelector(b)),
  setup: async (e) => {
    const o = e.options || {};
    if (!(e.target instanceof HTMLElement))
      return;
    const r = e.target.matches(b) ? [e.target] : Array.from(e.target.querySelectorAll(b)).filter((t) => t instanceof HTMLElement), i = r.map((t) => C(t, o));
    return await e.emit("formie:module:table:init", {
      count: r.length
    }), {
      destroy: () => {
        i.forEach((t) => {
          t();
        }), e.emit("formie:module:table:destroy", {});
      }
    };
  }
};
export {
  N as tableModule
};
