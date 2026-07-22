import { s as b } from "./_survey-presentations-C8LAnIwa.js";
import { d as P } from "./shared-BDEKVuB5.js";
import { e as H } from "./styles-C3aqgtek.js";
import { j as w } from "./index-CZtn5KAB.js";
const m = "[data-formie-survey-rank]", D = "[data-formie-survey-rank-list]", x = "[data-formie-survey-rank-item]", O = "[data-formie-rank-handle]", B = "formie-rank-placeholder", M = 0.42, k = "survey-rank", v = w("fields", "survey-rank");
H(k, [b]);
function R(e) {
  e.querySelectorAll(x).forEach((t, n) => {
    t instanceof HTMLElement && t.querySelectorAll("input[data-formie-rank-input]").forEach((r) => {
      r instanceof HTMLInputElement && (r.dataset.formieRankOrder = String(n));
    });
  });
}
function _(e) {
  return Array.from(e.querySelectorAll(x)).filter((t) => t instanceof HTMLElement);
}
function A(e, t) {
  return Array.from(e.children).indexOf(t);
}
function C(e, t, n) {
  return Array.from(e.children).filter((r) => r instanceof HTMLElement && r !== t && r !== n);
}
function q(e, t) {
  return Math.max(0, Math.min(e.bottom, t.bottom) - Math.max(e.top, t.top));
}
function L(e) {
  const t = e.getBoundingClientRect();
  return t.top + t.height / 2;
}
function T(e, t, n, r) {
  const c = e.getBoundingClientRect(), i = L(e), s = C(t, n, e);
  for (let l = 0; l < s.length; l += 1) {
    const a = s[l].getBoundingClientRect(), f = a.top + a.height * M;
    if (q(c, a) / a.height >= M)
      return r ? Math.min(l + 1, s.length) : l;
    if (i < f)
      return l;
  }
  return s.length;
}
function G(e, t, n, r) {
  const i = C(e, t, n)[r] ?? null;
  if (i) {
    e.insertBefore(t, i);
    return;
  }
  e.appendChild(t);
}
function Y(e, t) {
  const n = document.createElement("li");
  return n.className = B, n.setAttribute("data-formie-rank-placeholder", "true"), n.setAttribute("aria-hidden", "true"), n.style.height = `${t.offsetHeight}px`, e.insertBefore(n, t), n;
}
function $(e) {
  return {
    position: e.style.position,
    left: e.style.left,
    top: e.style.top,
    width: e.style.width,
    zIndex: e.style.zIndex,
    pointerEvents: e.style.pointerEvents,
    margin: e.style.margin
  };
}
function z(e, t) {
  e.style.position = "fixed", e.style.left = `${t.left}px`, e.style.top = `${t.top}px`, e.style.width = `${t.width}px`, e.style.zIndex = "1000", e.style.pointerEvents = "none", e.style.margin = "0";
}
function F(e, t, n) {
  e.style.left = `${t.clientX - n.x}px`, e.style.top = `${t.clientY - n.y}px`;
}
function N(e, t) {
  e.style.position = t.position, e.style.left = t.left, e.style.top = t.top, e.style.width = t.width, e.style.zIndex = t.zIndex, e.style.pointerEvents = t.pointerEvents, e.style.margin = t.margin;
}
function U(e) {
  const t = e.querySelector(D);
  if (!(t instanceof HTMLElement))
    return v.warn("Missing rank list; skipping field."), () => {
    };
  let n = null, r = null, c = null, i = null, s = null, l = null, a = null, f = null, p = null, h = !1;
  const I = [], S = () => {
    if (n && r ? (t.insertBefore(n, r), r.remove()) : r && r.remove(), n && c && (N(n, c), n.removeAttribute("data-formie-rank-dragging")), s && l !== null)
      try {
        s.releasePointerCapture(l);
      } catch {
      }
    n = null, r = null, c = null, i = null, s = null, l = null, a = null, f = null, p = null, t.removeAttribute("data-formie-rank-sorting"), h && (R(t), P(e, k, "reorder", {
      rankField: e
    })), h = !1;
  }, E = (o) => {
    if (!n || !r || !i || o.pointerId !== l)
      return;
    o.preventDefault(), F(n, o, i);
    const y = L(n), u = p === null || y >= p;
    p = y;
    const d = T(n, t, r, u);
    d !== a && (a = d, G(t, r, n, d));
  }, g = (o) => {
    o.pointerId === l && (document.removeEventListener("pointermove", E), document.removeEventListener("pointerup", g), document.removeEventListener("pointercancel", g), r && f !== null && (h = A(t, r) !== f), S());
  };
  return _(t).forEach((o) => {
    if (!o.querySelector(O))
      return;
    const y = (u) => {
      if (u.button !== 0 || u.target instanceof HTMLInputElement)
        return;
      u.preventDefault();
      const d = o.getBoundingClientRect();
      n = o, s = o, l = u.pointerId, i = {
        x: u.clientX - d.left,
        y: u.clientY - d.top
      }, c = $(o), r = Y(t, o), f = A(t, r), h = !1, z(o, d), o.setAttribute("data-formie-rank-dragging", "true"), t.setAttribute("data-formie-rank-sorting", "true"), o.setPointerCapture(u.pointerId), p = L(o), a = T(o, t, r, !0), document.addEventListener("pointermove", E), document.addEventListener("pointerup", g), document.addEventListener("pointercancel", g);
    };
    o.addEventListener("pointerdown", y), I.push(() => {
      o.removeEventListener("pointerdown", y);
    });
  }), R(t), () => {
    document.removeEventListener("pointermove", E), document.removeEventListener("pointerup", g), document.removeEventListener("pointercancel", g), I.forEach((o) => {
      o();
    }), S();
  };
}
const J = {
  id: k,
  kind: "field",
  match: (e) => e.target instanceof HTMLElement && (e.target.matches(m) || !!e.target.querySelector(m)),
  setup: async (e) => {
    if (!(e.target instanceof HTMLElement))
      return;
    const t = e.target.matches(m) ? [e.target] : Array.from(e.target.querySelectorAll(m)).filter((r) => r instanceof HTMLElement);
    v.log("Module setup.", {
      fieldCount: t.length
    });
    const n = t.map((r) => U(r));
    return await e.emit("formie:module:survey-rank:init", {
      count: t.length
    }), {
      destroy: () => {
        n.forEach((r) => {
          r();
        }), v.log("Module destroy.", {
          fieldCount: t.length
        }), e.emit("formie:module:survey-rank:destroy", {});
      }
    };
  }
};
export {
  J as surveyRankModule
};
