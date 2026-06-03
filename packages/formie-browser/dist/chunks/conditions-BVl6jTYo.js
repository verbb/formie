import { S as j, A as U } from "./index-BEsfee-D.js";
import { e as R } from "./shared-DGqeMvcJ.js";
import { j as _, h as W } from "./index-C1ZOKiAi.js";
const C = "[data-formie-conditions]";
function G(e) {
  if (!e || typeof e != "object")
    return null;
  const t = e, r = t.transformerParams;
  return {
    raw: typeof t.raw == "string" ? t.raw : "",
    target: typeof t.target == "string" ? t.target : "",
    handle: typeof t.handle == "string" ? t.handle : "",
    selector: typeof t.selector == "string" ? t.selector : "",
    defaultValue: typeof t.defaultValue == "string" ? t.defaultValue : "",
    transformerId: typeof t.transformerId == "string" ? t.transformerId : "",
    transformerParams: r && typeof r == "object" ? Object.fromEntries(Object.entries(r).map(([n, i]) => [n, String(i ?? "")])) : {},
    isValid: t.isValid !== !1
  };
}
function O(e) {
  const t = Array.from(e.querySelectorAll(C));
  return e.matches(C) ? [e, ...t] : t;
}
function z(e) {
  const t = e.getAttribute("data-formie-conditions");
  if (!t)
    return null;
  try {
    const r = JSON.parse(t), n = Array.isArray(r.conditions) ? r.conditions.filter((i) => {
      if (!i || typeof i != "object")
        return !1;
      const o = i;
      return typeof o.field == "string" && typeof o.condition == "string";
    }).map((i) => {
      const o = i;
      return {
        field: i.field,
        source: G(o.source),
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
const I = "data-formie-conditions-disabled", H = "data-formie-preserve-disabled", x = "data-formie-conditionally-hidden", $ = "data-formie-page-hidden", J = "formie-conditionally-hidden", K = "formie-page-hidden", y = "data-formie-row-hidden", L = "formie-row-hidden", T = "data-formie-field-count", w = "[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]", Q = ":scope > [data-formie-field]";
function X(e) {
  e.querySelectorAll("input, select, textarea").forEach((t) => {
    !(t instanceof HTMLInputElement) && !(t instanceof HTMLSelectElement) && !(t instanceof HTMLTextAreaElement) || (t instanceof HTMLInputElement && (t.type === "checkbox" || t.type === "radio" ? t.checked = !1 : t.type !== "hidden" && (t.value = "")), t instanceof HTMLSelectElement && (t.multiple ? Array.from(t.options).forEach((r) => {
      r.selected = !1;
    }) : t.selectedIndex = 0), t instanceof HTMLTextAreaElement && (t.value = ""));
  });
}
function Z(e, t) {
  const r = e.hasAttribute("data-formie-page"), n = r ? $ : x, i = r ? K : J, o = e.hasAttribute(n);
  return t ? (o || e.setAttribute(n, "true"), e.classList.contains(i) || e.classList.add(i)) : (o && e.removeAttribute(n), e.classList.contains(i) && e.classList.remove(i)), o !== t;
}
function ee(e, t) {
  e.querySelectorAll("input, textarea, select").forEach((r) => {
    if (t) {
      r.hasAttribute(I) || (r.hasAttribute("disabled") && r.setAttribute(H, "true"), r.setAttribute(I, "true")), r.setAttribute("disabled", "true");
      return;
    }
    r.hasAttribute(I) && (r.hasAttribute(H) ? (r.setAttribute("disabled", "true"), r.removeAttribute(H)) : r.removeAttribute("disabled"), r.removeAttribute(I));
  });
}
function te(e) {
  return !e.hasAttribute(x) && !e.hasAttribute($) && !e.hasAttribute(y) && !e.hasAttribute("hidden");
}
function re(e) {
  const r = Array.from(e.querySelectorAll(Q)).filter((n) => te(n)).length;
  if (r > 0) {
    const n = String(r);
    e.getAttribute(T) !== n && e.setAttribute(T, n), e.hasAttribute(y) && e.removeAttribute(y), e.classList.contains(L) && e.classList.remove(L);
    return;
  }
  e.hasAttribute(T) && e.removeAttribute(T), e.hasAttribute(y) || e.setAttribute(y, "true"), e.classList.contains(L) || e.classList.add(L);
}
function ne(e) {
  let t = e.closest(w);
  for (; t; )
    re(t), t = t.parentElement?.closest(w) || null;
}
function ie(e, t, r) {
  const n = Z(e, t);
  return ee(e, t), ne(e), t && r && n && X(e), n;
}
const v = "input, select, textarea", oe = "[data-formie-repeater-item], [data-formie-table-row]";
function g(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function se(e) {
  const t = e.querySelector(v);
  if (!t)
    return null;
  const r = t.getAttribute("name") || "", n = Array.from(r.matchAll(/\[(\d+)\]/g));
  return n.length && n[n.length - 1]?.[1] || null;
}
function D(e) {
  return e.closest(oe);
}
function ae(e) {
  return Array.from(e.querySelectorAll(v)).filter((t) => g(t));
}
function ue(e) {
  const t = e.getAttribute("name") || "";
  return Array.from(t.matchAll(/\[([^\]]+)\]/g)).map((r) => r[1] || "").filter(Boolean);
}
function le(e, t) {
  if (!t)
    return !0;
  const r = t.split(/[.:]/).filter(Boolean);
  if (!r.length)
    return !0;
  const n = ue(e);
  return n.length < r.length ? !1 : r.every((i, o) => n[n.length - r.length + o] === i);
}
function ce(e, t) {
  if (!t)
    return e;
  const r = e.filter((n) => le(n, t));
  return r.length ? r : e;
}
function V(e, t) {
  const r = D(e);
  if (!r)
    return t;
  const n = t.filter((i) => D(i) === r);
  return n.length ? n : t;
}
function B(e) {
  return e.source?.target === "field" && e.source.handle ? e.source : null;
}
function P(e, t, r) {
  const n = B(r);
  if (!n || n.target !== "field" || !n.handle)
    return [];
  const i = R(n.handle), o = Array.from(e.querySelectorAll(`[data-formie-field-handle="${i}"]`));
  if (o.length)
    return V(t, o).flatMap((f) => ce(ae(f), n.selector));
  const l = _(n.handle), c = R(l), m = Array.from(e.querySelectorAll(`[name="${c}"]`)).filter((f) => g(f)), S = Array.from(e.querySelectorAll(`[name="${c}[]"]`)).filter((f) => g(f));
  if (m.length || S.length)
    return V(t, [...m, ...S]);
  if (!n.handle.includes("__ROW__"))
    return [];
  const h = se(t);
  if (h) {
    const f = _(n.handle.replace(/__ROW__/g, h)), b = R(f), s = Array.from(e.querySelectorAll(`[name="${b}"]`)).filter((u) => g(u)), a = Array.from(e.querySelectorAll(`[name="${b}[]"]`)).filter((u) => g(u));
    if (s.length || a.length)
      return [...s, ...a];
  }
  const N = _(n.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+"), E = new RegExp(N);
  return Array.from(e.querySelectorAll("[name]")).filter((f) => g(f) && E.test(f.getAttribute("name") || ""));
}
function fe(e) {
  return e == null ? "" : String(e);
}
function de(e) {
  if (typeof e == "boolean")
    return e;
  if (typeof e == "number")
    return e !== 0;
  const t = fe(e).trim().toLowerCase();
  return !(!t || ["0", "false", "no", "off"].includes(t));
}
function me(e) {
  return e.toLowerCase().replace(/\b\w/g, (t) => t.toUpperCase());
}
function he(e, t) {
  const r = Number.isFinite(Number(t.decimals)) ? Number(t.decimals) : 0, n = t.decimalPoint ?? ".", i = t.thousandsSeparator ?? ",", o = e.toFixed(r), [l, c = ""] = o.split("."), m = l.replace(/\B(?=(\d{3})+(?!\d))/g, i);
  return r === 0 ? m : `${m}${n}${c}`;
}
function A(e) {
  return String(e).padStart(2, "0");
}
function pe(e, t) {
  return [
    ["Y", String(e.getFullYear())],
    ["m", A(e.getMonth() + 1)],
    ["d", A(e.getDate())],
    ["j", String(e.getDate())],
    ["H", A(e.getHours())],
    ["h", A((e.getHours() + 11) % 12 + 1)],
    ["i", A(e.getMinutes())],
    ["A", e.getHours() >= 12 ? "PM" : "AM"],
    ["F", e.toLocaleString(void 0, { month: "long" })]
  ].reduce((n, [i, o]) => n.replaceAll(i, o), t);
}
function ge(e) {
  switch (e) {
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
function be(e, t) {
  const r = t.transformerId, n = t.transformerParams;
  switch (r) {
    case "round":
    case "floor":
    case "ceil": {
      const i = Number(e);
      return Number.isFinite(i) ? String(r === "round" ? Math.round(i) : r === "floor" ? Math.floor(i) : Math.ceil(i)) : e;
    }
    case "format": {
      const i = Number(e);
      if (Number.isFinite(i) && e.trim() !== "")
        return he(i, n);
      const o = n.preset || "", l = o === "custom" ? n.pattern || "" : ge(o);
      if (!l)
        return e;
      const c = new Date(e);
      return Number.isNaN(c.getTime()) ? e : pe(c, l);
    }
    case "lower":
      return e.toLowerCase();
    case "upper":
      return e.toUpperCase();
    case "title":
      return me(e);
    case "capitalize":
      return e && e.charAt(0).toUpperCase() + e.slice(1);
    case "replace": {
      const i = n.search || "";
      return i ? e.split(i).join(n.replace || "") : e;
    }
    case "truncate": {
      const i = Math.max(1, Number.parseInt(n.length || "50", 10) || 50), o = n.suffix || "...";
      return e.length <= i ? e : `${e.slice(0, Math.max(0, i - o.length))}${o}`;
    }
    case "map":
      return de(e) ? n.trueLabel || "Yes" : n.falseLabel || "No";
    default:
      return e;
  }
}
function Ae(e, t) {
  if (!t)
    return e;
  const r = t.transformerId ? e.map((n) => be(n, t)) : e;
  return (r.length === 0 || r.every((n) => n.trim() === "")) && t.defaultValue ? [t.defaultValue] : r;
}
function ye(e, t) {
  return e.name || `__condition_input_${t}`;
}
function F(e) {
  const t = e.id ? e.ownerDocument.querySelector(`label[for="${e.id}"]`)?.textContent?.trim() : "";
  return t || e.closest("label")?.textContent?.trim() || "";
}
function Se(e, t = "") {
  const r = e[0];
  if (!r)
    return [];
  if (r instanceof HTMLInputElement) {
    if (r.type === "checkbox") {
      const n = e.filter((i) => i instanceof HTMLInputElement && i.checked);
      return t === "label" ? n.map((i) => F(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "radio") {
      const n = e.filter((i) => i instanceof HTMLInputElement && i.checked);
      return t === "label" ? n.map((i) => F(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "file")
      return Array.from(r.files || []).map((n) => n.name);
  }
  return r instanceof HTMLSelectElement && r.multiple ? t === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : Array.from(r.selectedOptions).map((n) => n.value) : r instanceof HTMLSelectElement && t === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : e.map((n) => n.value);
}
function k(e) {
  return ["input", "change"];
}
function Ee(e, t = null) {
  const r = /* @__PURE__ */ new Map();
  e.forEach((i, o) => {
    const l = ye(i, o), c = r.get(l) || [];
    c.push(i), r.set(l, c);
  });
  const n = Array.from(r.values()).flatMap((i) => Se(i, t?.selector || ""));
  return Ae(n, t);
}
function Ie(e) {
  return e.closest("[data-formie-conditionally-hidden]") || e.closest("[data-formie-page-hidden]") || e.closest("[hidden]") || e.closest('[aria-hidden="true"]') ? !1 : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
function Le(e) {
  return e.length ? e.some((t) => Ie(t)) : null;
}
function Te(e, t) {
  const r = e.conditions.map((n) => {
    const i = t(n), o = B(n);
    return j(n, Ee(i, o), {
      visibility: Le(i)
    });
  });
  return U(e, r);
}
const M = 4, p = W("conditions");
function q(e) {
  const t = /* @__PURE__ */ new Set();
  return e.filter((r) => t.has(r) ? !1 : (t.add(r), !0));
}
const _e = {
  id: "conditions",
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(C) || !!e.target.querySelector(C)),
  setup: async (e) => {
    const t = e.target instanceof HTMLElement ? e.target : e.root;
    if (!O(t).length) {
      p.log("No condition nodes in scope.");
      return;
    }
    const r = [];
    let n = [], i = !1, o = !1;
    const l = () => {
      r.forEach((s) => {
        s();
      }), r.length = 0;
    }, c = () => O(t).flatMap((s) => {
      const a = z(s);
      if (!a || !a.conditions.length)
        return [];
      const u = q(a.conditions.flatMap((d) => P(t, s, d)));
      return [{
        node: s,
        settings: a,
        sourceInputs: u
      }];
    }), m = () => {
      let s = !1;
      return n.forEach((a) => {
        const u = Te(a.settings, (Y) => P(t, a.node, Y)), d = ie(a.node, u.shouldHide, a.settings.clearOnHide);
        s = s || d, p.log("Condition evaluated.", {
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          stateChanged: d
        }), e.emit("formie:conditions:evaluated", {
          node: a.node,
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          clearOnHide: a.settings.clearOnHide
        });
      }), s;
    }, S = () => {
      for (let s = 0; s < M && m(); s += 1)
        s === M - 1 && p.warn("Reached max evaluation passes.", { maxPasses: M });
    }, h = () => {
      i || (i = !0, queueMicrotask(() => {
        i = !1, S();
      }));
    }, N = () => {
      if (q(n.flatMap((s) => s.sourceInputs)).forEach((s) => {
        const a = () => {
          h();
        };
        k().forEach((u) => {
          s.addEventListener(u, a);
        }), r.push(() => {
          k().forEach((u) => {
            s.removeEventListener(u, a);
          });
        });
      }), e.form) {
        const s = () => {
          window.setTimeout(() => {
            h();
          }, 0);
        };
        e.form.addEventListener("reset", s), r.push(() => {
          e.form?.removeEventListener("reset", s);
        });
      }
    }, E = () => {
      l(), n = c(), N(), p.log("Rebuilt condition graph.", {
        entryCount: n.length
      }), h();
    }, f = () => {
      o || (o = !0, queueMicrotask(() => {
        o = !1, E();
      }));
    }, b = new MutationObserver((s) => {
      const a = s.some((d) => d.type === "childList" && (d.addedNodes.length > 0 || d.removedNodes.length > 0)), u = s.some((d) => d.type === "attributes");
      a ? f() : u && h();
    });
    return b.observe(t, {
      childList: !0,
      subtree: !0,
      attributes: !0,
      attributeFilter: [
        "class",
        "style",
        "hidden",
        "aria-hidden",
        "data-formie-conditionally-hidden",
        "data-formie-page-hidden",
        "data-formie-row-hidden"
      ]
    }), E(), await e.emit("formie:module:conditions:init", {
      count: n.length
    }), p.log("Module setup complete.", { entryCount: n.length }), {
      destroy: () => {
        l(), b.disconnect(), p.log("Module destroy."), e.emit("formie:module:conditions:destroy", {});
      }
    };
  }
};
export {
  _e as conditionsModule
};
