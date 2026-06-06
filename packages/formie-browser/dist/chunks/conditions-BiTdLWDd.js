import { S as te, A as re } from "./index-BEsfee-D.js";
import { e as V } from "./shared-D1wIMots.js";
import { j as F, h as ne } from "./index-Cmikarpm.js";
const D = "[data-formie-conditions]";
function ie(e) {
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
function $(e) {
  const t = Array.from(e.querySelectorAll(D));
  return e.matches(D) ? [e, ...t] : t;
}
function se(e) {
  const t = e.getAttribute("data-formie-conditions");
  if (!t)
    return null;
  try {
    const r = JSON.parse(t), n = Array.isArray(r.conditions) ? r.conditions.filter((i) => {
      if (!i || typeof i != "object")
        return !1;
      const s = i;
      return typeof s.field == "string" && typeof s.condition == "string";
    }).map((i) => {
      const s = i;
      return {
        field: i.field,
        source: ie(s.source),
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
const M = "data-formie-conditions-disabled", v = "data-formie-preserve-disabled", T = "data-formie-conditionally-hidden", C = "data-formie-page-hidden", O = "formie-conditionally-hidden", L = "fui-cp-muted-conditional-field", x = "fui-cp-muted-conditional-field--expanded", I = "data-formie-cp-muted", w = "formie-page-hidden", E = "data-formie-row-hidden", R = "formie-row-hidden", H = "data-formie-field-count", U = "[data-formie-row], [data-formie-subfield-row], [data-formie-nested-field-row]", oe = ":scope > [data-formie-field]";
function ae(e) {
  e.querySelectorAll("input, select, textarea").forEach((t) => {
    !(t instanceof HTMLInputElement) && !(t instanceof HTMLSelectElement) && !(t instanceof HTMLTextAreaElement) || (t instanceof HTMLInputElement && (t.type === "checkbox" || t.type === "radio" ? t.checked = !1 : t.type !== "hidden" && (t.value = "")), t instanceof HTMLSelectElement && (t.multiple ? Array.from(t.options).forEach((r) => {
      r.selected = !1;
    }) : t.selectedIndex = 0), t instanceof HTMLTextAreaElement && (t.value = ""));
  });
}
function ue(e, t) {
  const r = e.hasAttribute("data-formie-page"), n = r ? C : T, i = r ? w : O, s = e.hasAttribute(n);
  return t ? (s || e.setAttribute(n, "true"), e.classList.contains(i) || e.classList.add(i)) : (s && e.removeAttribute(n), e.classList.contains(i) && e.classList.remove(i)), s !== t;
}
function le(e, t) {
  e.querySelectorAll("input, textarea, select").forEach((r) => {
    if (t) {
      r.hasAttribute(M) || (r.hasAttribute("disabled") && r.setAttribute(v, "true"), r.setAttribute(M, "true")), r.setAttribute("disabled", "true");
      return;
    }
    r.hasAttribute(M) && (r.hasAttribute(v) ? (r.setAttribute("disabled", "true"), r.removeAttribute(v)) : r.removeAttribute("disabled"), r.removeAttribute(M));
  });
}
function ce(e) {
  return !e.hasAttribute(T) && !e.hasAttribute(C) && !e.hasAttribute(E) && !e.hasAttribute("hidden");
}
function fe(e) {
  const r = Array.from(e.querySelectorAll(oe)).filter((n) => ce(n)).length;
  if (r > 0) {
    const n = String(r);
    e.getAttribute(H) !== n && e.setAttribute(H, n), e.hasAttribute(E) && e.removeAttribute(E), e.classList.contains(R) && e.classList.remove(R);
    return;
  }
  e.hasAttribute(H) && e.removeAttribute(H), e.hasAttribute(E) || e.setAttribute(E, "true"), e.classList.contains(R) || e.classList.add(R);
}
function K(e) {
  e.removeAttribute(I), e.classList.remove(L), e.classList.remove(x);
}
function B(e) {
  e.removeAttribute(T), e.removeAttribute(C), e.classList.remove(O), e.classList.remove(w);
}
function Q(e) {
  let t = e.closest(U);
  for (; t; )
    fe(t), t = t.parentElement?.closest(U) || null;
}
function de(e, t, r, n = {}) {
  if (n.displayMode === "muted")
    return me(e, t);
  let i = !1;
  return (e.hasAttribute(I) || e.classList.contains(L) || e.classList.contains(x)) && (K(e), i = !0), i = ue(e, t) || i, le(e, t), Q(e), t && r && i && ae(e), i;
}
function me(e, t) {
  let r = !1;
  return t ? ((e.hasAttribute(T) || e.hasAttribute(C) || e.classList.contains(O) || e.classList.contains(w)) && (B(e), r = !0), e.hasAttribute(I) || (e.setAttribute(I, "true"), r = !0), e.classList.contains(L) || (e.classList.add(L), r = !0)) : ((e.hasAttribute(I) || e.classList.contains(L) || e.classList.contains(x)) && (K(e), r = !0), (e.hasAttribute(T) || e.hasAttribute(C) || e.classList.contains(O) || e.classList.contains(w)) && (B(e), r = !0)), Q(e), r;
}
const X = "input, select, textarea", he = "[data-formie-repeater-item], [data-formie-table-row]";
function A(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function pe(e) {
  const t = e.querySelector(X);
  if (!t)
    return null;
  const r = t.getAttribute("name") || "", n = Array.from(r.matchAll(/\[(\d+)\]/g));
  return n.length && n[n.length - 1]?.[1] || null;
}
function Y(e) {
  return e.closest(he);
}
function ge(e) {
  return Array.from(e.querySelectorAll(X)).filter((t) => A(t));
}
function Ae(e) {
  const t = e.getAttribute("name") || "";
  return Array.from(t.matchAll(/\[([^\]]+)\]/g)).map((r) => r[1] || "").filter(Boolean);
}
function be(e, t) {
  if (!t)
    return !0;
  const r = t.split(/[.:]/).filter(Boolean);
  if (!r.length)
    return !0;
  const n = Ae(e);
  return n.length < r.length ? !1 : r.every((i, s) => n[n.length - r.length + s] === i);
}
function ye(e, t) {
  if (!t)
    return e;
  const r = e.filter((n) => be(n, t));
  return r.length ? r : e;
}
function j(e, t) {
  const r = Y(e);
  if (!r)
    return t;
  const n = t.filter((i) => Y(i) === r);
  return n.length ? n : t;
}
function Z(e) {
  return e.source?.target === "field" && e.source.handle ? e.source : null;
}
function W(e, t, r) {
  const n = Z(r);
  if (!n || n.target !== "field" || !n.handle)
    return [];
  const i = V(n.handle), s = Array.from(e.querySelectorAll(`[data-formie-field-handle="${i}"]`));
  if (s.length)
    return j(t, s).flatMap((f) => ye(ge(f), n.selector));
  const l = F(n.handle), c = V(l), h = Array.from(e.querySelectorAll(`[name="${c}"]`)).filter((f) => A(f)), d = Array.from(e.querySelectorAll(`[name="${c}[]"]`)).filter((f) => A(f));
  if (h.length || d.length)
    return j(t, [...h, ...d]);
  if (!n.handle.includes("__ROW__"))
    return [];
  const _ = pe(t);
  if (_) {
    const f = F(n.handle.replace(/__ROW__/g, _)), b = V(f), N = Array.from(e.querySelectorAll(`[name="${b}"]`)).filter((o) => A(o)), y = Array.from(e.querySelectorAll(`[name="${b}[]"]`)).filter((o) => A(o));
    if (N.length || y.length)
      return [...N, ...y];
  }
  const P = F(n.handle).replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/__ROW__/g, "\\d+"), p = new RegExp(P);
  return Array.from(e.querySelectorAll("[name]")).filter((f) => A(f) && p.test(f.getAttribute("name") || ""));
}
function Se(e) {
  return e == null ? "" : String(e);
}
function Ee(e) {
  if (typeof e == "boolean")
    return e;
  if (typeof e == "number")
    return e !== 0;
  const t = Se(e).trim().toLowerCase();
  return !(!t || ["0", "false", "no", "off"].includes(t));
}
function Le(e) {
  return e.toLowerCase().replace(/\b\w/g, (t) => t.toUpperCase());
}
function Ie(e, t) {
  const r = Number.isFinite(Number(t.decimals)) ? Number(t.decimals) : 0, n = t.decimalPoint ?? ".", i = t.thousandsSeparator ?? ",", s = e.toFixed(r), [l, c = ""] = s.split("."), h = l.replace(/\B(?=(\d{3})+(?!\d))/g, i);
  return r === 0 ? h : `${h}${n}${c}`;
}
function S(e) {
  return String(e).padStart(2, "0");
}
function Te(e, t) {
  return [
    ["Y", String(e.getFullYear())],
    ["m", S(e.getMonth() + 1)],
    ["d", S(e.getDate())],
    ["j", String(e.getDate())],
    ["H", S(e.getHours())],
    ["h", S((e.getHours() + 11) % 12 + 1)],
    ["i", S(e.getMinutes())],
    ["A", e.getHours() >= 12 ? "PM" : "AM"],
    ["F", e.toLocaleString(void 0, { month: "long" })]
  ].reduce((n, [i, s]) => n.replaceAll(i, s), t);
}
function Ce(e) {
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
function _e(e, t) {
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
        return Ie(i, n);
      const s = n.preset || "", l = s === "custom" ? n.pattern || "" : Ce(s);
      if (!l)
        return e;
      const c = new Date(e);
      return Number.isNaN(c.getTime()) ? e : Te(c, l);
    }
    case "lower":
      return e.toLowerCase();
    case "upper":
      return e.toUpperCase();
    case "title":
      return Le(e);
    case "capitalize":
      return e && e.charAt(0).toUpperCase() + e.slice(1);
    case "replace": {
      const i = n.search || "";
      return i ? e.split(i).join(n.replace || "") : e;
    }
    case "truncate": {
      const i = Math.max(1, Number.parseInt(n.length || "50", 10) || 50), s = n.suffix || "...";
      return e.length <= i ? e : `${e.slice(0, Math.max(0, i - s.length))}${s}`;
    }
    case "map":
      return Ee(e) ? n.trueLabel || "Yes" : n.falseLabel || "No";
    default:
      return e;
  }
}
function Ne(e, t) {
  if (!t)
    return e;
  const r = t.transformerId ? e.map((n) => _e(n, t)) : e;
  return (r.length === 0 || r.every((n) => n.trim() === "")) && t.defaultValue ? [t.defaultValue] : r;
}
function Me(e, t) {
  return e.name || `__condition_input_${t}`;
}
function G(e) {
  const t = e.id ? e.ownerDocument.querySelector(`label[for="${e.id}"]`)?.textContent?.trim() : "";
  return t || e.closest("label")?.textContent?.trim() || "";
}
function Re(e, t = "") {
  const r = e[0];
  if (!r)
    return [];
  if (r instanceof HTMLInputElement) {
    if (r.type === "checkbox") {
      const n = e.filter((i) => i instanceof HTMLInputElement && i.checked);
      return t === "label" ? n.map((i) => G(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "radio") {
      const n = e.filter((i) => i instanceof HTMLInputElement && i.checked);
      return t === "label" ? n.map((i) => G(i)).filter(Boolean) : n.map((i) => i.value);
    }
    if (r.type === "file")
      return Array.from(r.files || []).map((n) => n.name);
  }
  return r instanceof HTMLSelectElement && r.multiple ? t === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : Array.from(r.selectedOptions).map((n) => n.value) : r instanceof HTMLSelectElement && t === "label" ? Array.from(r.selectedOptions).map((n) => n.label || n.text) : e.map((n) => n.value);
}
function z(e) {
  return ["input", "change"];
}
function He(e, t = null) {
  const r = /* @__PURE__ */ new Map();
  e.forEach((i, s) => {
    const l = Me(i, s), c = r.get(l) || [];
    c.push(i), r.set(l, c);
  });
  const n = Array.from(r.values()).flatMap((i) => Re(i, t?.selector || ""));
  return Ne(n, t);
}
function De(e) {
  return e.closest("[data-formie-conditionally-hidden]") || e.closest("[data-formie-page-hidden]") || e.closest("[hidden]") || e.closest('[aria-hidden="true"]') ? !1 : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
function Oe(e) {
  return e.length ? e.some((t) => De(t)) : null;
}
function we(e, t) {
  const r = e.conditions.map((n) => {
    const i = t(n), s = Z(n);
    return te(n, He(i, s), {
      visibility: Oe(i)
    });
  });
  return re(e, r);
}
const q = 4, g = ne("conditions");
function J(e) {
  const t = /* @__PURE__ */ new Set();
  return e.filter((r) => t.has(r) ? !1 : (t.add(r), !0));
}
const ve = {
  id: "conditions",
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(D) || !!e.target.querySelector(D)),
  setup: async (e) => {
    const t = e.target instanceof HTMLElement ? e.target : e.root;
    if (!$(t).length) {
      g.log("No condition nodes in scope.");
      return;
    }
    const r = [];
    let n = [], i = !1, s = !1;
    const l = () => {
      r.forEach((o) => {
        o();
      }), r.length = 0;
    }, c = () => $(t).flatMap((o) => {
      const a = se(o);
      if (!a || !a.conditions.length)
        return [];
      const u = J(a.conditions.flatMap((m) => W(t, o, m)));
      return [{
        node: o,
        settings: a,
        sourceInputs: u
      }];
    }), h = e.options?.cpDisplayMode === "muted" ? "muted" : "hide";
    let d = !1;
    const _ = () => {
      let o = !1;
      return n.forEach((a) => {
        const u = we(a.settings, (ee) => W(t, a.node, ee)), m = a.node.hasAttribute("data-formie-page") ? "hide" : h, k = de(
          a.node,
          u.shouldHide,
          a.settings.clearOnHide,
          { displayMode: m }
        );
        o = o || k, g.log("Condition evaluated.", {
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          stateChanged: k
        }), e.emit("formie:conditions:evaluated", {
          node: a.node,
          shouldHide: u.shouldHide,
          finalResult: u.finalResult,
          clearOnHide: a.settings.clearOnHide
        });
      }), o;
    }, P = () => {
      if (!d) {
        d = !0;
        try {
          for (let o = 0; o < q && _(); o += 1)
            o === q - 1 && g.warn("Reached max evaluation passes.", { maxPasses: q });
        } finally {
          d = !1;
        }
      }
    }, p = () => {
      d || i || (i = !0, requestAnimationFrame(() => {
        i = !1, !d && P();
      }));
    }, f = () => {
      if (J(n.flatMap((o) => o.sourceInputs)).forEach((o) => {
        const a = () => {
          p();
        };
        z().forEach((u) => {
          o.addEventListener(u, a);
        }), r.push(() => {
          z().forEach((u) => {
            o.removeEventListener(u, a);
          });
        });
      }), e.form) {
        const o = () => {
          window.setTimeout(() => {
            p();
          }, 0);
        };
        e.form.addEventListener("reset", o), r.push(() => {
          e.form?.removeEventListener("reset", o);
        });
      }
    }, b = () => {
      l(), n = c(), f(), g.log("Rebuilt condition graph.", {
        entryCount: n.length
      }), p();
    }, N = () => {
      d || s || (s = !0, requestAnimationFrame(() => {
        s = !1, !d && b();
      }));
    }, y = new MutationObserver((o) => {
      if (d)
        return;
      const a = o.some((m) => m.type === "childList" && (m.addedNodes.length > 0 || m.removedNodes.length > 0)), u = o.some((m) => m.type === "attributes");
      a ? N() : u && p();
    });
    return y.observe(t, {
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
    }), b(), await e.emit("formie:module:conditions:init", {
      count: n.length
    }), g.log("Module setup complete.", { entryCount: n.length }), {
      destroy: () => {
        l(), y.disconnect(), g.log("Module destroy."), e.emit("formie:module:conditions:destroy", {});
      }
    };
  }
};
export {
  ve as conditionsModule
};
