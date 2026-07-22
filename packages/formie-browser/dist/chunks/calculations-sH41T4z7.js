import { Y as q, V as D, W as O, H as N, z as P } from "./index-Mwww76TL.js";
import { g as T, d as C } from "./shared-BDEKVuB5.js";
import { i as w, r as y, n as A, j as W, k as F } from "./index-CZtn5KAB.js";
const I = /* @__PURE__ */ new Set(["first", "last", "index", "all", "count", "rows"]);
function R(a) {
  return a.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function B(a, n) {
  const e = String(a || "").trim().toLowerCase();
  if (!e || n <= 0)
    return [];
  if (e === "even") {
    const s = [];
    for (let t = 1; t <= n; t++)
      t % 2 === 0 && s.push(t - 1);
    return s;
  }
  if (e === "odd") {
    const s = [];
    for (let t = 1; t <= n; t++)
      t % 2 === 1 && s.push(t - 1);
    return s;
  }
  const r = e.match(/^every:(\d+)$/);
  if (r) {
    const s = Math.max(1, Number.parseInt(r[1] || "1", 10)), t = [];
    for (let c = 1; c <= n; c += s)
      t.push(c - 1);
    return t;
  }
  const l = [];
  return e.split(/\s*,\s*/).forEach((s) => {
    const t = s.trim();
    if (!t)
      return;
    const c = t.match(/^(\d+)\s*-\s*(\d+)$/);
    if (c) {
      let i = Number.parseInt(c[1] || "0", 10), u = Number.parseInt(c[2] || "0", 10);
      i > u && ([i, u] = [u, i]);
      for (let f = i; f <= u; f++)
        f >= 1 && f <= n && l.push(f - 1);
      return;
    }
    const o = Number.parseInt(t, 10);
    Number.isFinite(o) && o >= 1 && o <= n && l.push(o - 1);
  }), [...new Set(l)].sort((s, t) => s - t);
}
function V(a) {
  const n = A(a), e = n.split(".").filter(Boolean);
  return e.length < 2 ? {
    fieldKey: n,
    columnKey: e[e.length - 1] || ""
  } : e.length >= 3 && /^\d+$/.test(e[1] || "") ? {
    fieldKey: e[0] || "",
    columnKey: e.slice(2).join(".")
  } : {
    fieldKey: e[0] || "",
    columnKey: e.slice(1).join(".")
  };
}
function x(a, n, e) {
  const r = new RegExp(`^${R(a)}\\.(\\d+)\\.${R(n)}$`);
  return [...e.keys()].filter((l) => r.test(l)).sort((l, s) => {
    const t = Number.parseInt(l.split(".")[1] || "0", 10), c = Number.parseInt(s.split(".")[1] || "0", 10);
    return t - c;
  });
}
function _(a, n) {
  return y(a, n).value;
}
function j(a, n, e) {
  const r = /* @__PURE__ */ new Set(), { fieldKey: l, columnKey: s } = V(a), t = String(n.scope || "").trim().toLowerCase();
  if (!l || !s || !I.has(t)) {
    const o = w(a);
    return o && (r.add(o), r.add(`${o}[]`)), r;
  }
  return x(l, s, e).forEach((o) => {
    const i = e.get(o);
    if (i?.names?.length) {
      i.names.forEach((f) => {
        r.add(f);
      });
      return;
    }
    const u = w(o);
    u && (r.add(u), r.add(`${u}[]`));
  }), r;
}
function U(a, n, e) {
  const r = String(n.scope || "").trim().toLowerCase();
  if (!r || !I.has(r))
    return y(a, e);
  const { fieldKey: l, columnKey: s } = V(a);
  if (!l || !s)
    return y(a, e);
  const t = x(l, s, e), c = t.map((o) => _(o, e));
  if (r === "count")
    return {
      key: `${l}.${s}`,
      value: String(t.length),
      found: !0
    };
  if (r === "first")
    return {
      key: t[0] || `${l}.0.${s}`,
      value: c[0] ?? "",
      found: t.length > 0
    };
  if (r === "last")
    return {
      key: t[t.length - 1] || `${l}.0.${s}`,
      value: c[c.length - 1] ?? "",
      found: t.length > 0
    };
  if (r === "index") {
    const o = Number.parseInt(String(n.index ?? "0"), 10), i = `${l}.${o}.${s}`;
    return y(i, e);
  }
  if (r === "all") {
    const o = c.flatMap((i) => Array.isArray(i) ? i : i === "" ? [] : [i]);
    return {
      key: `${l}.${s}`,
      value: o,
      found: o.length > 0
    };
  }
  if (r === "rows") {
    const o = B(String(n.rows || ""), t.length);
    if (o.length === 0)
      return {
        key: `${l}.${s}`,
        value: "",
        found: !1
      };
    if (o.length === 1)
      return {
        key: t[o[0]] || `${l}.${o[0]}.${s}`,
        value: c[o[0]] ?? "",
        found: !0
      };
    const i = o.flatMap((u) => {
      const f = c[u];
      return Array.isArray(f) ? f : f === "" ? [] : [f];
    });
    return {
      key: `${l}.${s}`,
      value: i,
      found: i.length > 0
    };
  }
  return y(a, e);
}
const L = "input[data-formie-calculation-input]";
function H(a) {
  const n = a;
  return {
    scope: n.scope,
    index: n.index,
    rows: n.rows,
    fieldKind: n.fieldKind
  };
}
const $ = "calculations", p = W("fields", "calculations");
function Q(a, n, e) {
  const r = F(a), l = {};
  return n.forEach(([s, t]) => {
    const c = H(t);
    if (String(c.scope || "").trim()) {
      const u = U(t.sourceKey || "", c, r);
      l[s] = N(t, u.value);
      return;
    }
    const i = y(t.sourceKey || "", r);
    l[s] = N(t, i.value);
  }), P(l, e.formatting);
}
function Y(a, n) {
  const e = F(a), r = /* @__PURE__ */ new Set();
  return n.forEach(([, l]) => {
    const s = H(l);
    if (String(s.scope || "").trim()) {
      j(l.sourceKey || "", s, e).forEach((u) => {
        r.add(u);
      });
      return;
    }
    const c = A(l.sourceKey || ""), o = e.get(c);
    if (o?.names?.length) {
      o.names.forEach((u) => {
        r.add(u);
      });
      return;
    }
    const i = w(c);
    i && (r.add(i), r.add(`${i}[]`));
  }), r;
}
function G(a, n, e, r) {
  const l = q(r), s = D(r), t = /* @__PURE__ */ new Map();
  let c = null, o = !1, i = !1, u = !1;
  const f = () => {
    t.forEach((h, v) => {
      h.forEach((d, m) => {
        v.removeEventListener(m, d);
      });
    }), t.clear();
  }, b = (h) => {
    !h || o || queueMicrotask(() => {
      o || (e.dispatchEvent(new Event("input", { bubbles: !0 })), e.dispatchEvent(new Event("change", { bubbles: !0 })));
    });
  }, S = (h = !1) => {
    const v = Q(a, s, r);
    p.log("Evaluate requested.", {
      fieldHandle: n.getAttribute("data-formie-field-handle") || null,
      isInit: h
    });
    const d = {
      calculations: e,
      init: h,
      formula: l,
      variables: v
    };
    if (C(n, $, "before-evaluate", d), !d.formula) {
      const m = e.value !== "";
      e.value = "", b(m);
      return;
    }
    try {
      const m = O(d.formula, d.variables, r), g = {
        calculations: e,
        init: h,
        formula: d.formula,
        variables: d.variables,
        result: m
      };
      C(n, $, "after-evaluate", g);
      const E = typeof g.result == "string" || typeof g.result == "number" ? String(g.result) : "", K = e.value !== E;
      e.value = E, p.log("Evaluate complete.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        valueChanged: K,
        nextValue: E
      }), b(K);
    } catch (m) {
      const g = e.value !== "";
      console.error("[formie] Failed to evaluate calculation.", m), p.warn("Evaluate failed.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        error: m instanceof Error ? m.message : m
      }), e.value = "", b(g);
    }
  }, k = (h = !1) => {
    i || o || (i = !0, queueMicrotask(() => {
      i = !1, S(h);
    }));
  }, M = () => {
    f();
    const h = Y(a, s);
    if (p.log("Binding variable watchers.", {
      fieldHandle: n.getAttribute("data-formie-field-handle") || null,
      watchCount: h.size
    }), !h.size)
      return;
    const v = (d) => {
      const g = d.target?.name || "";
      !g || !h.has(g) || (p.log("Source change detected.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        sourceName: g,
        eventType: d.type
      }), k(!1));
    };
    ["input", "change"].forEach((d) => {
      a.addEventListener(d, v);
      const m = t.get(a) || /* @__PURE__ */ new Map();
      m.set(d, v), t.set(a, m);
    });
  }, z = () => {
    u || o || (u = !0, queueMicrotask(() => {
      u = !1, M(), k(!1);
    }));
  };
  return M(), c = new MutationObserver(() => {
    z();
  }), c.observe(a, {
    childList: !0,
    subtree: !0
  }), S(!0), () => {
    o = !0, c?.disconnect(), f();
  };
}
const ee = {
  id: $,
  kind: "field",
  match: (a) => !!a.target.querySelector(L),
  setup: async (a) => {
    const n = a.options || {}, e = T(a);
    p.log("Module setup.", {
      fieldCount: e.length,
      formatting: n.formatting || null
    });
    const r = e.map((l) => {
      const s = l.querySelector(L);
      return s instanceof HTMLInputElement ? G(a.root, l, s, n) : () => {
      };
    });
    return await a.emit("formie:module:calculations:init", {
      count: r.length
    }), {
      destroy: () => {
        p.log("Module destroy.", {
          fieldCount: r.length
        }), r.forEach((l) => {
          l();
        }), a.emit("formie:module:calculations:destroy", {});
      }
    };
  }
};
export {
  ee as calculationsModule
};
