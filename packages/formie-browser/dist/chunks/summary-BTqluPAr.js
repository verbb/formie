import { g as H, d as S } from "./shared-BDEKVuB5.js";
import { e as I } from "./styles-C3aqgtek.js";
import { j as N, t as L, q as _, u as x } from "./index-CZtn5KAB.js";
const V = '@layer formie-theme{.formie-summary-container{padding:var(--formie-summary-padding);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-summary-heading{color:var(--formie-color-heading)}.formie-summary-blocks{display:grid;gap:var(--formie-gap-summary)}.formie-summary-blocks[data-formie-loading=true]{position:relative;min-height:calc(var(--formie-loading-size) + var(--formie-space-4))}.formie-summary-blocks[data-formie-loading=true]>*{opacity:0;pointer-events:none}.formie-summary-blocks[data-formie-loading=true]:before{position:absolute;inset:0;content:"";display:block;background:var(--formie-color-bg);border-radius:inherit;z-index:1}.formie-summary-blocks[data-formie-loading=true]:after{position:absolute;top:50%;left:50%;width:var(--formie-loading-size);height:var(--formie-loading-size);content:"";display:block;border:var(--formie-loading-border-width) solid var(--formie-loading-color);border-top-color:transparent;border-right-color:transparent;border-radius:var(--formie-radius-full);transform:translate(-50%,-50%);z-index:2;animation:formie-loading-spin var(--formie-loading-speed) linear infinite}}', w = "[data-formie-summary-blocks]", C = "[data-formie-summary-container]", z = "formie/fields/get-summary-html", U = "data-formie-theme-config", P = "data-formie-frontend-theme", h = "summary", i = N("fields", "summary");
I(h, [V]);
function B() {
  const e = new URL(window.location.href);
  return e.hash = "", e.toString();
}
function q(e, t) {
  return {
    accessToken: e.querySelector("[data-formie-summary-token]")?.value?.trim() || null,
    themeConfig: t.getAttribute(U)?.trim() || null,
    frontendTheme: t.getAttribute(P)?.trim() || null
  };
}
async function j(e, t, r) {
  if (!t.accessToken)
    throw new Error("Summary field requires an access token.");
  const a = new FormData(e);
  return a.set("action", z), a.set("accessToken", t.accessToken), t.themeConfig && a.set("themeConfig", t.themeConfig), t.frontendTheme && a.set("frontendTheme", t.frontendTheme), x(B(), {
    method: "POST",
    body: a,
    signal: r,
    headers: {
      Accept: "text/html"
    }
  });
}
function G(e, t) {
  const r = e.closest("form");
  if (!(r instanceof HTMLFormElement))
    return i.warn("Missing form ancestor; skipping field."), () => {
    };
  let a = !1, s = !0, b = !1, u = 0, d = 0, l = null;
  const v = () => {
    const o = e.querySelector(w);
    return o instanceof HTMLElement ? o : null;
  }, F = () => {
    const o = e.querySelector(C);
    return o instanceof HTMLElement ? o : null;
  }, f = (o) => {
    const n = v();
    if (n) {
      if (o) {
        n.setAttribute("data-formie-loading", "true"), n.setAttribute("aria-busy", "true"), L(n, r, "loading", !0);
        return;
      }
      n.removeAttribute("data-formie-loading"), n.removeAttribute("aria-busy"), L(n, r, "loading", !1);
    }
  }, A = q(e, r);
  f(!!A.accessToken);
  const c = () => {
    !b || a && !s || (i.log("Queueing fetch."), R());
  }, R = _(async () => {
    const o = q(e, r);
    if (!v() || !o.accessToken) {
      i.warn("Missing state for fetch.", o), f(!1);
      return;
    }
    d += 1;
    const n = d, D = u;
    l?.abort(), l = new AbortController(), f(!0);
    try {
      const m = await j(r, o, l.signal);
      if (n !== d)
        return;
      const y = F(), k = document.createElement("template");
      k.innerHTML = m.trim();
      const M = k.content.querySelector(C);
      y && M instanceof HTMLElement ? y.replaceWith(M) : y && (y.innerHTML = m), a = !0, s = u !== D, i.log("Fetch complete.", {
        isDirty: s,
        dirtyVersion: u,
        requestVersion: n
      }), S(e, h, "fetch-summary", {
        summary: e,
        html: m
      });
    } catch (m) {
      if (m instanceof DOMException && m.name === "AbortError") {
        i.log("Fetch aborted.");
        return;
      }
      console.error("[formie] Failed to load summary field HTML.", m);
    } finally {
      n === d && (f(!1), l = null, s && c());
    }
  }, 300), O = (o) => {
    const n = o?.target;
    n instanceof Node && e.contains(n) || (s = !0, u += 1, i.log("Marked dirty.", { dirtyVersion: u }));
  }, g = (o) => {
    O(o), c();
  }, p = () => {
    s = !0, i.log("Submit result received; refreshing."), c();
  }, T = () => {
    s = !0, i.log("Page navigation received; refreshing."), c();
  }, E = new IntersectionObserver((o) => {
    b = !!o[0]?.isIntersecting, b && (i.log("Field became visible."), S(e, h, "field-visible", {
      summary: e
    }), c());
  }, {
    root: r,
    rootMargin: "50px"
  });
  return E.observe(e), r.addEventListener("input", g), r.addEventListener("change", g), t.addEventListener("formie:page:navigate:after", T), t.addEventListener("formie:submit:result", p), () => {
    l?.abort(), E.disconnect(), r.removeEventListener("input", g), r.removeEventListener("change", g), t.removeEventListener("formie:page:navigate:after", T), t.removeEventListener("formie:submit:result", p), i.log("Field destroyed.");
  };
}
const Y = {
  id: h,
  kind: "field",
  match: (e) => !!e.target.querySelector(w),
  setup: async (e) => {
    const t = H(e).map((r) => G(r, e.root));
    return i.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:summary:init", {
      count: t.length
    }), {
      destroy: () => {
        t.forEach((r) => {
          r();
        }), i.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:summary:destroy", {});
      }
    };
  }
};
export {
  Y as summaryModule
};
