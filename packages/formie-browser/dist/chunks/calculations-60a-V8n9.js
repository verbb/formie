import { j as H, _ as A, $ as D, U as N, F as T } from "./index-BEsfee-D.js";
import { g as K, d as L } from "./shared-D1wIMots.js";
import { h as R, i as k, n as U, j as V, r as _ } from "./index-Cmikarpm.js";
const S = "input[data-formie-calculation-input]", y = "calculations", g = R("fields", "calculations");
function j(e, r, t) {
  const a = k(e), o = {};
  return r.forEach(([u, i]) => {
    const d = _(i.sourceKey || "", a);
    o[u] = N(i, d.value);
  }), T(o, t.formatting);
}
function z(e, r) {
  const t = k(e), a = /* @__PURE__ */ new Set();
  return r.forEach(([, o]) => {
    const u = U(o.sourceKey || ""), i = t.get(u);
    if (i?.names?.length) {
      i.names.forEach((f) => {
        a.add(f);
      });
      return;
    }
    const d = V(u);
    d && (a.add(d), a.add(`${d}[]`));
  }), a;
}
function B(e, r, t, a) {
  const o = H(a), u = A(a), i = /* @__PURE__ */ new Map();
  let d = null, f = !1, v = !1, h = !1;
  const p = () => {
    i.forEach((s, m) => {
      s.forEach((n, l) => {
        m.removeEventListener(l, n);
      });
    }), i.clear();
  }, b = (s) => {
    !s || f || queueMicrotask(() => {
      f || (t.dispatchEvent(new Event("input", { bubbles: !0 })), t.dispatchEvent(new Event("change", { bubbles: !0 })));
    });
  }, w = (s = !1) => {
    const m = j(e, u, a);
    g.log("Evaluate requested.", {
      fieldHandle: r.getAttribute("data-formie-field-handle") || null,
      isInit: s
    });
    const n = {
      calculations: t,
      init: s,
      formula: o,
      variables: m
    };
    if (L(r, y, "before-evaluate", n), !n.formula) {
      const l = t.value !== "";
      t.value = "", b(l);
      return;
    }
    try {
      const l = D(n.formula, n.variables, a), c = {
        calculations: t,
        init: s,
        formula: n.formula,
        variables: n.variables,
        result: l
      };
      L(r, y, "after-evaluate", c);
      const E = typeof c.result == "string" || typeof c.result == "number" ? String(c.result) : "", F = t.value !== E;
      t.value = E, g.log("Evaluate complete.", {
        fieldHandle: r.getAttribute("data-formie-field-handle") || null,
        valueChanged: F,
        nextValue: E
      }), b(F);
    } catch (l) {
      const c = t.value !== "";
      console.error("[formie] Failed to evaluate calculation.", l), g.warn("Evaluate failed.", {
        fieldHandle: r.getAttribute("data-formie-field-handle") || null,
        error: l instanceof Error ? l.message : l
      }), t.value = "", b(c);
    }
  }, M = (s = !1) => {
    v || f || (v = !0, queueMicrotask(() => {
      v = !1, w(s);
    }));
  }, C = () => {
    p();
    const s = z(e, u);
    if (g.log("Binding variable watchers.", {
      fieldHandle: r.getAttribute("data-formie-field-handle") || null,
      watchCount: s.size
    }), !s.size)
      return;
    const m = (n) => {
      const c = n.target?.name || "";
      !c || !s.has(c) || (g.log("Source change detected.", {
        fieldHandle: r.getAttribute("data-formie-field-handle") || null,
        sourceName: c,
        eventType: n.type
      }), M(!1));
    };
    ["input", "change"].forEach((n) => {
      e.addEventListener(n, m);
      const l = i.get(e) || /* @__PURE__ */ new Map();
      l.set(n, m), i.set(e, l);
    });
  }, q = () => {
    h || f || (h = !0, queueMicrotask(() => {
      h = !1, C(), M(!1);
    }));
  };
  return C(), d = new MutationObserver(() => {
    q();
  }), d.observe(e, {
    childList: !0,
    subtree: !0
  }), w(!0), () => {
    f = !0, d?.disconnect(), p();
  };
}
const Q = {
  id: y,
  kind: "field",
  match: (e) => !!e.target.querySelector(S),
  setup: async (e) => {
    const r = e.options || {}, t = K(e);
    g.log("Module setup.", {
      fieldCount: t.length,
      formatting: r.formatting || null
    });
    const a = t.map((o) => {
      const u = o.querySelector(S);
      return u instanceof HTMLInputElement ? B(e.root, o, u, r) : () => {
      };
    });
    return await e.emit("formie:module:calculations:init", {
      count: a.length
    }), {
      destroy: () => {
        g.log("Module destroy.", {
          fieldCount: a.length
        }), a.forEach((o) => {
          o();
        }), e.emit("formie:module:calculations:destroy", {});
      }
    };
  }
};
export {
  Q as calculationsModule
};
