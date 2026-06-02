import { C as k, w as H, q as A, K as D, T as K } from "./index-DtUMIYEa.js";
import { g as N, d as F } from "./shared-8Shdc9Qt.js";
import { h as R, i as S, n as V, j as z, r as B } from "./index-MuyEvWaf.js";
const L = "input[data-formie-calculation-input]", y = "calculations", g = R("fields", "calculations");
function O(e, n, t) {
  const a = S(e), o = {};
  return n.forEach(([u, i]) => {
    const d = B(i.sourceKey || "", a);
    o[u] = D(i, d.value);
  }), K(o, t.formatting);
}
function I(e, n) {
  const t = S(e), a = /* @__PURE__ */ new Set();
  return n.forEach(([, o]) => {
    const u = V(o.sourceKey || ""), i = t.get(u);
    if (i?.names?.length) {
      i.names.forEach((f) => {
        a.add(f);
      });
      return;
    }
    const d = z(u);
    d && (a.add(d), a.add(`${d}[]`));
  }), a;
}
function Q(e, n, t, a) {
  const o = k(a), u = H(a), i = /* @__PURE__ */ new Map();
  let d = null, f = !1, v = !1, h = !1;
  const p = () => {
    i.forEach((s, m) => {
      s.forEach((l, r) => {
        m.removeEventListener(r, l);
      });
    }), i.clear();
  }, b = (s) => {
    !s || f || queueMicrotask(() => {
      f || (t.dispatchEvent(new Event("input", { bubbles: !0 })), t.dispatchEvent(new Event("change", { bubbles: !0 })));
    });
  }, w = (s = !1) => {
    const m = O(e, u, a);
    g.log("Evaluate requested.", {
      fieldHandle: n.getAttribute("data-formie-field-handle") || null,
      isInit: s
    });
    const l = {
      calculations: t,
      init: s,
      formula: o,
      variables: m
    };
    if (F(n, y, "before-evaluate", l), !l.formula) {
      const r = t.value !== "";
      t.value = "", b(r);
      return;
    }
    try {
      const r = A(l.formula, l.variables, a), c = {
        calculations: t,
        init: s,
        formula: l.formula,
        variables: l.variables,
        result: r
      };
      F(n, y, "after-evaluate", c);
      const E = typeof c.result == "string" || typeof c.result == "number" ? String(c.result) : "", q = t.value !== E;
      t.value = E, g.log("Evaluate complete.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        valueChanged: q,
        nextValue: E
      }), b(q);
    } catch (r) {
      const c = t.value !== "";
      console.error("[formie] Failed to evaluate calculation.", r), g.warn("Evaluate failed.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        error: r instanceof Error ? r.message : r
      }), t.value = "", b(c);
    }
  }, C = (s = !1) => {
    v || f || (v = !0, queueMicrotask(() => {
      v = !1, w(s);
    }));
  }, M = () => {
    p();
    const s = I(e, u);
    if (g.log("Binding variable watchers.", {
      fieldHandle: n.getAttribute("data-formie-field-handle") || null,
      watchCount: s.size
    }), !s.size)
      return;
    const m = (l) => {
      const c = l.target?.name || "";
      !c || !s.has(c) || (g.log("Source change detected.", {
        fieldHandle: n.getAttribute("data-formie-field-handle") || null,
        sourceName: c,
        eventType: l.type
      }), C(!1));
    };
    ["input", "change"].forEach((l) => {
      e.addEventListener(l, m);
      const r = i.get(e) || /* @__PURE__ */ new Map();
      r.set(l, m), i.set(e, r);
    });
  }, T = () => {
    h || f || (h = !0, queueMicrotask(() => {
      h = !1, M(), C(!1);
    }));
  };
  return M(), d = new MutationObserver(() => {
    T();
  }), d.observe(e, {
    childList: !0,
    subtree: !0
  }), w(!0), () => {
    f = !0, d?.disconnect(), p();
  };
}
const P = {
  id: y,
  kind: "field",
  match: (e) => !!e.target.querySelector(L),
  setup: async (e) => {
    const n = e.options || {}, t = N(e);
    g.log("Module setup.", {
      fieldCount: t.length,
      formatting: n.formatting || null
    });
    const a = t.map((o) => {
      const u = o.querySelector(L);
      return u instanceof HTMLInputElement ? Q(e.root, o, u, n) : () => {
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
  P as calculationsModule
};
