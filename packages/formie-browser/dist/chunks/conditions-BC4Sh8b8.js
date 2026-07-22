import { P as nt, C as it } from "./index-Mwww76TL.js";
import { e as V } from "./shared-BDEKVuB5.js";
import { i as F, j as st } from "./index-CZtn5KAB.js";
const O = "[data-formie-conditions]";
function at(t) {
  if (!t || typeof t != "object")
    return null;
  const e = t, r = e.transformerParams;
  return {
    raw: typeof e.raw == "string" ? e.raw : "",
    target: typeof e.target == "string" ? e.target : "",
    handle: typeof e.handle == "string" ? e.handle : "",
    selector: typeof e.selector == "string" ? e.selector : "",
    defaultValue: typeof e.defaultValue == "string" ? e.defaultValue : "",
    transformerId: typeof e.transformerId == "string" ? e.transformerId : "",
    transformerParams: r && typeof r == "object" ? Object.fromEntries(Object.entries(r).map(([n, i]) => [n, String(i ?? "")])) : {},
    isValid: e.isValid !== !1
  };
}
function $(t) {
  const e = Array.from(t.querySelectorAll(O));
  return t.matches(O) ? [t, ...e] : e;
}
function ot(t) {
  const e = t.getAttribute("data-formie-conditions");
  if (!e)
    return null;
  try {
    const r = JSON.parse(e), n = Array.isArray(r.conditions) ? r.conditions.filter((i) => {
      if (!i || typeof i != "object")
        return !1;
      const s = i;
      return typeof s.field == "string" && typeof s.condition == "string";
    }).map((i) => {
      const s = i;
      return {
        field: i.field,
        source: at(s.source),
        condition: i.condition,
        value: i.value
      };
    }) : [];
    return {
      showRule: r.showRule === "hide" ? "hide" : "show",
      conditionRule: r.conditionRule === "any" ? "any" : "all",
      clearOnHide: r.clearOnHide !== !1,
      isNested: !!r.isNested,
      conditions: n
    };
  } catch (r) {
    return console.error("[formie] Invalid condition JSON.", r), null;
  }
}
const h = "data-formie-conditions-disabled", y = "data-formie-preserve-disabled", _ = "data-formie-conditionally-hidden", M = "data-formie-page-hidden", w = "formie-conditionally-hidden", T = "fui-cp-muted-conditional-field", x = "fui-cp-muted-conditional-field--expanded", C = "data-formie-cp-muted", P = "formie-page-hidden", I = "data-formie-row-hidden", H = "formie-row-hidden", D = "data-formie-field-count", B = "[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]", ut = ":scope > [data-formie-field]";
function lt(t) {
  t.querySelectorAll("input, select, textarea").forEach((e) => {
    !(e instanceof HTMLInputElement) && !(e instanceof HTMLSelectElement) && !(e instanceof HTMLTextAreaElement) || (e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio" ? e.checked = !1 : e.type !== "hidden" && (e.value = "")), e instanceof HTMLSelectElement && (e.multiple ? Array.from(e.options).forEach((r) => {
      r.selected = !1;
    }) : e.selectedIndex = 0), e instanceof HTMLTextAreaElement && (e.value = ""));
  });
}
function ct(t, e) {
  const r = t.hasAttribute("data-formie-page"), n = r ? M : _, i = r ? P : w, s = t.hasAttribute(n);
  return e ? (s || t.setAttribute(n, "true"), t.classList.contains(i) || t.classList.add(i)) : (s && t.removeAttribute(n), t.classList.contains(i) && t.classList.remove(i)), s !== e;
}
const U = 'button[type="submit"], button[data-formie-action], input[type="submit"]';
function Y(t, e) {
  if (t instanceof HTMLButtonElement || t instanceof HTMLInputElement) {
    if (e) {
      t.hasAttribute(h) || (t.hasAttribute("disabled") && t.setAttribute(y, "true"), t.setAttribute(h, "true")), t.setAttribute("disabled", "true");
      return;
    }
    t.hasAttribute(h) && (t.hasAttribute(y) ? (t.setAttribute("disabled", "true"), t.removeAttribute(y)) : t.removeAttribute("disabled"), t.removeAttribute(h));
  }
}
function ft(t, e) {
  t.matches(U) && Y(t, e), t.querySelectorAll(U).forEach((r) => {
    Y(r, e);
  }), t.querySelectorAll("input, textarea, select").forEach((r) => {
    if (e) {
      r.hasAttribute(h) || (r.hasAttribute("disabled") && r.setAttribute(y, "true"), r.setAttribute(h, "true")), r.setAttribute("disabled", "true");
      return;
    }
    r.hasAttribute(h) && (r.hasAttribute(y) ? (r.setAttribute("disabled", "true"), r.removeAttribute(y)) : r.removeAttribute("disabled"), r.removeAttribute(h));
  });
}
function dt(t) {
  return !t.hasAttribute(_) && !t.hasAttribute(M) && !t.hasAttribute(I) && !t.hasAttribute("hidden");
}
function mt(t) {
  const r = Array.from(t.querySelectorAll(ut)).filter((n) => dt(n)).length;
  if (r > 0) {
    const n = String(r);
    t.getAttribute(D) !== n && t.setAttribute(D, n), t.hasAttribute(I) && t.removeAttribute(I), t.classList.contains(H) && t.classList.remove(H);
    return;
  }
  t.hasAttribute(D) && t.removeAttribute(D), t.hasAttribute(I) || t.setAttribute(I, "true"), t.classList.contains(H) || t.classList.add(H);
}
function X(t) {
  t.removeAttribute(C), t.classList.remove(T), t.classList.remove(x);
}
function j(t) {
  t.removeAttribute(_), t.removeAttribute(M), t.classList.remove(w), t.classList.remove(P);
}
function Z(t) {
  let e = t.closest(B);
  for (; e; )
    mt(e), e = e.parentElement?.closest(B) || null;
}
function ht(t, e, r, n = {}) {
  if (n.displayMode === "muted")
    return pt(t, e);
  let i = !1;
  return (t.hasAttribute(C) || t.classList.contains(T) || t.classList.contains(x)) && (X(t), i = !0), i = ct(t, e) || i, ft(t, e), Z(t), e && r && i && lt(t), i;
}
function pt(t, e) {
  let r = !1;
  return e ? ((t.hasAttribute(_) || t.hasAttribute(M) || t.classList.contains(w) || t.classList.contains(P)) && (j(t), r = !0), t.hasAttribute(C) || (t.setAttribute(C, "true"), r = !0), t.classList.contains(T) || (t.classList.add(T), r = !0)) : ((t.hasAttribute(C) || t.classList.contains(T) || t.classList.contains(x)) && (X(t), r = !0), (t.hasAttribute(_) || t.hasAttribute(M) || t.classList.contains(w) || t.classList.contains(P)) && (j(t), r = !0)), Z(t), r;
}
const tt = "input, select, textarea", bt = "[data-formie-repeater-item], [data-formie-table-row]";
function A(t) {
  return t instanceof HTMLInputElement || t instanceof HTMLSelectElement || t instanceof HTMLTextAreaElement;
}
function gt(t) {
  const e = t.querySelector(tt);
  if (!e)
    return null;
  const r = e.getAttribute("name") || "", n = Array.from(r.matchAll(/\[(\d+)\]/g));
  return n.length && n[n.length - 1]?.[1] || null;
}
function W(t) {
  return t.closest(bt);
}
function At(t) {
  return Array.from(t.querySelectorAll(tt)).filter((e) => A(e));
}
function yt(t) {
  const e = t.getAttribute("name") || "";
  return Array.from(e.matchAll(/\[([^\]]+)\]/g)).map((r) => r[1] || "").filter(Boolean);
}
function St(t, e) {
  if (!e)
    return !0;
  const r = e.split(/[.:]/).filter(Boolean);
  if (!r.length)
    return !0;
  const n = yt(t);
  return n.length < r.length ? !1 : r.every((i, s) => n[n.length - r.length + s] === i);
}
function Et(t, e) {
  if (!e)
    return t;
  const r = t.filter((n) => St(n, e));
  return r.length ? r : t;
}
function G(t, e) {
  const r = W(t);
  if (!r)
    return e;
  const n = e.filter((i) => W(i) === r);
  return n.length ? n : e;
}
function et(t) {
  return t.source?.target === "field" && t.source.handle ? t.source : null;
}
function z(t, e, r) {
  const n = et(r);
  if (!n || n.target !== "field" || !n.handle)
    return [];
  const i = V(n.handle), s = Array.from(t.querySelectorAll(`[data-formie-field-handle="${i}"]`));
  if (s.length)
    return G(e, s).flatMap((f) => Et(At(f), n.selector));
  const l = F(n.handle), c = V(l), p = Array.from(t.querySelectorAll(`[name="${c}"]`)).filter((f) => A(f)), d = Array.from(t.querySelectorAll(`[name="${c}[]"]`)).filter((f) => A(f));
  if (p.length || d.length)
    return G(e, [...p, ...d]);
  if (!n.handle.includes("__ROW__"))
    return [];
  const N = gt(e);
  if (N) {
    const f = F(n.handle.replace(/__ROW__/g, N)), S = V(f), R = Array.from(t.querySelectorAll(`[name="${S}"]`)).filter((a) => A(a)), E = Array.from(t.querySelectorAll(`[name="${S}[]"]`)).filter((a) => A(a));
    if (R.length || E.length)
      return [...R, ...E];
  }
  const v = F(n.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+"), b = new RegExp(v);
  return Array.from(t.querySelectorAll("[name]")).filter((f) => A(f) && b.test(f.getAttribute("name") || ""));
}
function Lt(t) {
  return t == null ? "" : String(t);
}
function It(t) {
  if (typeof t == "boolean")
    return t;
  if (typeof t == "number")
    return t !== 0;
  const e = Lt(t).trim().toLowerCase();
  return !(!e || ["0", "false", "no", "off"].includes(e));
}
function Tt(t) {
  return t.toLowerCase().replace(/\b\w/g, (e) => e.toUpperCase());
}
function Ct(t, e) {
  const r = Number.isFinite(Number(e.decimals)) ? Number(e.decimals) : 0, n = e.decimalPoint ?? ".", i = e.thousandsSeparator ?? ",", s = t.toFixed(r), [l, c = ""] = s.split("."), p = l.replace(/\B(?=(\d{3})+(?!\d))/g, i);
  return r === 0 ? p : `${p}${n}${c}`;
}
function L(t) {
  return String(t).padStart(2, "0");
}
function _t(t, e) {
  return [
    ["Y", String(t.getFullYear())],
    ["m", L(t.getMonth() + 1)],
    ["d", L(t.getDate())],
    ["j", String(t.getDate())],
    ["H", L(t.getHours())],
    ["h", L((t.getHours() + 11) % 12 + 1)],
    ["i", L(t.getMinutes())],
    ["A", t.getHours() >= 12 ? "PM" : "AM"],
    ["F", t.toLocaleString(void 0, { month: "long" })]
  ].reduce((n, [i, s]) => n.replaceAll(i, s), e);
}
function Mt(t) {
  switch (t) {
    case "datetimeUs12":
      return "m/d/Y h:i A";
    case "datetimeEu12":
      return "d/m/Y h:i A";
    case "datetimeEu24":
      return "d/m/Y H:i";
    case "datetimeIso24":
      return "Y-m-d H:i";
    case "dateUs":
      return "m/d/Y";
    case "dateEu":
      return "d/m/Y";
    case "isoDate":
      return "Y-m-d";
    case "dateLong":
      return "F j, Y";
    case "time12":
      return "h:i A";
    case "time24":
      return "H:i";
    default:
      return "";
  }
}
function Nt(t, e) {
  const r = e.transformerId, n = e.transformerParams;
  switch (r) {
    case "round":
    case "floor":
    case "ceil": {
      const i = Number(t);
      return Number.isFinite(i) ? String(r === "round" ? Math.round(i) : r === "floor" ? Math.floor(i) : Math.ceil(i)) : t;
    }
    case "format": {
      const i = Number(t);
      if (Number.isFinite(i) && t.trim() !== "")
        return Ct(i, n);
      const s = n.preset || "", l = s === "custom" ? n.pattern || "" : Mt(s);
      if (!l)
        return t;
      const c = new Date(t);
      return Number.isNaN(c.getTime()) ? t : _t(c, l);
    }
    case "lower":
      return t.toLowerCase();
    case "upper":
      return t.toUpperCase();
    case "title":
      return Tt(t);
    case "capitalize":
      return t && t.charAt(0).toUpperCase() + t.slice(1);
    case "replace": {
      const i = n.search || "";
      return i ? t.split(i).join(n.replace || "") : t;
    }
    case "truncate": {
      const i = Math.max(1, Number.parseInt(n.length || "50", 10) || 50), s = n.suffix || "...";
      return t.length <= i ? t : `${t.slice(0, Math.max(0, i - s.length))}${s}`;
    }
    case "map":
      return It(t) ? n.trueLabel || "Yes" : n.falseLabel || "No";
    default:
      return t;
  }
}
function Rt(t, e) {
  if (!e)
    return t;
  const r = e.transformerId ? t.map((n) => Nt(n, e)) : t;
  return (r.length === 0 || r.every((n) => n.trim() === "")) && e.defaultValue ? [e.defaultValue] : r;
}
function Ht(t, e) {
  return t.name || `__condition_input_${e}`;
}
function J(t) {
  const e = t.id ? t.ownerDocument.querySelector(`label[for="${t.id}"]`)?.textContent?.trim() : "";
  return e || t.closest("label")?.textContent?.trim() || "";
}
function Dt(t, e = "") {
  const r = t[0];
  if (!r)
    return [];
  if (r instanceof HTMLInputElement) {
    if (r.type === "checkbox") {
      const n = t.filter((i) => i instanceof HTMLInputElement && i.checked);
      return e === "label" ? n.map((i) => J(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "radio") {
      const n = t.filter((i) => i instanceof HTMLInputElement && i.checked);
      return e === "label" ? n.map((i) => J(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "file")
      return Array.from(r.files || []).map((n) => n.name);
  }
  return r instanceof HTMLSelectElement && r.multiple ? e === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : Array.from(r.selectedOptions).map((n) => n.value) : r instanceof HTMLSelectElement && e === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : t.map((n) => n.value);
}
function K(t) {
  return ["input", "change"];
}
function Ot(t, e = null) {
  const r = /* @__PURE__ */ new Map();
  t.forEach((i, s) => {
    const l = Ht(i, s), c = r.get(l) || [];
    c.push(i), r.set(l, c);
  });
  const n = Array.from(r.values()).flatMap((i) => Dt(i, e?.selector || ""));
  return Rt(n, e);
}
function wt(t) {
  return t.closest("[data-formie-conditionally-hidden]") || t.closest("[data-formie-page-hidden]") || t.closest("[hidden]") || t.closest('[aria-hidden="true"]') ? !1 : !!(t.offsetWidth || t.offsetHeight || t.getClientRects().length);
}
function Pt(t) {
  return t.length ? t.some((e) => wt(e)) : null;
}
function vt(t, e) {
  const r = t.conditions.map((n) => {
    const i = e(n), s = et(n);
    return nt(n, Ot(i, s), {
      visibility: Pt(i)
    });
  });
  return it(t, r);
}
const q = 4, g = st("conditions");
function Q(t) {
  const e = /* @__PURE__ */ new Set();
  return t.filter((r) => e.has(r) ? !1 : (e.add(r), !0));
}
const xt = {
  id: "conditions",
  kind: "field",
  match: (t) => t.target instanceof HTMLElement && (t.target.matches(O) || !!t.target.querySelector(O)),
  setup: async (t) => {
    const e = t.target instanceof HTMLElement ? t.target : t.root;
    if (!$(e).length) {
      g.log("No condition nodes in scope.");
      return;
    }
    const r = [];
    let n = [], i = !1, s = !1;
    const l = () => {
      r.forEach((a) => {
        a();
      }), r.length = 0;
    }, c = () => $(e).flatMap((a) => {
      const o = ot(a);
      if (!o || !o.conditions.length)
        return [];
      const u = Q(o.conditions.flatMap((m) => z(e, a, m)));
      return [{
        node: a,
        settings: o,
        sourceInputs: u
      }];
    }), p = t.options?.cpDisplayMode === "muted" ? "muted" : "hide";
    let d = !1;
    const N = () => {
      let a = !1;
      return n.forEach((o) => {
        const u = vt(o.settings, (rt) => z(e, o.node, rt)), m = o.node.hasAttribute("data-formie-page") ? "hide" : p, k = ht(
          o.node,
          u.shouldHide,
          o.settings.clearOnHide,
          { displayMode: m }
        );
        a = a || k, g.log("Condition evaluated.", {
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          stateChanged: k
        }), t.emit("formie:conditions:evaluated", {
          node: o.node,
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          clearOnHide: o.settings.clearOnHide
        });
      }), a;
    }, v = () => {
      if (!d) {
        d = !0;
        try {
          for (let a = 0; a < q && N(); a += 1)
            a === q - 1 && g.warn("Reached max evaluation passes.", { maxPasses: q });
        } finally {
          d = !1;
        }
      }
    }, b = () => {
      d || i || (i = !0, requestAnimationFrame(() => {
        i = !1, !d && v();
      }));
    }, f = () => {
      if (Q(n.flatMap((a) => a.sourceInputs)).forEach((a) => {
        const o = () => {
          b();
        };
        K().forEach((u) => {
          a.addEventListener(u, o);
        }), r.push(() => {
          K().forEach((u) => {
            a.removeEventListener(u, o);
          });
        });
      }), t.form) {
        const a = () => {
          window.setTimeout(() => {
            b();
          }, 0);
        };
        t.form.addEventListener("reset", a), r.push(() => {
          t.form?.removeEventListener("reset", a);
        });
      }
    }, S = () => {
      l(), n = c(), f(), g.log("Rebuilt condition graph.", {
        entryCount: n.length
      }), b();
    }, R = () => {
      d || s || (s = !0, requestAnimationFrame(() => {
        s = !1, !d && S();
      }));
    }, E = new MutationObserver((a) => {
      if (d)
        return;
      const o = a.some((m) => m.type === "childList" && (m.addedNodes.length > 0 || m.removedNodes.length > 0)), u = a.some((m) => m.type === "attributes");
      o ? R() : u && b();
    });
    return E.observe(e, {
      childList: !0,
      subtree: !0,
      attributes: !0,
      attributeFilter: [
        "hidden",
        "aria-hidden",
        "data-formie-conditionally-hidden",
        "data-formie-page-hidden",
        "data-formie-row-hidden"
      ]
    }), S(), await t.emit("formie:module:conditions:init", {
      count: n.length
    }), g.log("Module setup complete.", { entryCount: n.length }), {
      destroy: () => {
        l(), E.disconnect(), g.log("Module destroy."), t.emit("formie:module:conditions:destroy", {});
      }
    };
  }
};
export {
  xt as conditionsModule
};
