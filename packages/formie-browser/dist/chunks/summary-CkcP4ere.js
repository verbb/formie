import { g as H, d as M } from "./shared-CvP8aWsY.js";
import { e as I } from "./styles-C3aqgtek.js";
import { h as x, t as L, o as V, p as z } from "./index-DL83ZezE.js";
const N = '@layer formie-theme{.formie-summary-container{padding:var(--formie-summary-padding);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-summary-heading{color:var(--formie-color-heading)}.formie-summary-blocks{display:grid;gap:var(--formie-gap-summary)}.formie-summary-blocks[data-formie-loading=true]{position:relative;min-height:calc(var(--formie-loading-size) + var(--formie-space-4))}.formie-summary-blocks[data-formie-loading=true]>*{opacity:0;pointer-events:none}.formie-summary-blocks[data-formie-loading=true]:before{position:absolute;inset:0;content:"";display:block;background:var(--formie-color-bg);border-radius:inherit;z-index:1}.formie-summary-blocks[data-formie-loading=true]:after{position:absolute;top:50%;left:50%;width:var(--formie-loading-size);height:var(--formie-loading-size);content:"";display:block;border:var(--formie-loading-border-width) solid var(--formie-loading-color);border-top-color:transparent;border-right-color:transparent;border-radius:var(--formie-radius-full);transform:translate(-50%,-50%);z-index:2;animation:formie-loading-spin var(--formie-loading-speed) linear infinite}}', C = "[data-formie-summary-blocks]", q = "[data-formie-summary-container]", U = "formie/fields/get-summary-html", b = "summary", i = x("fields", "summary");
I(b, [N]);
function _() {
  const e = new URL(window.location.href);
  return e.hash = "", e.toString();
}
function w(e) {
  return {
    accessToken: e.querySelector("[data-formie-summary-token]")?.value?.trim() || null
  };
}
async function P(e, n, t) {
  if (!n.accessToken)
    throw new Error("Summary field requires an access token.");
  const m = new FormData(e);
  return m.set("action", U), m.set("accessToken", n.accessToken), z(_(), {
    method: "POST",
    body: m,
    signal: t,
    headers: {
      Accept: "text/html"
    }
  });
}
function B(e, n) {
  const t = e.closest("form");
  if (!(t instanceof HTMLFormElement))
    return i.warn("Missing form ancestor; skipping field."), () => {
    };
  let m = !1, a = !0, h = !1, l = 0, d = 0, u = null;
  const v = () => {
    const r = e.querySelector(C);
    return r instanceof HTMLElement ? r : null;
  }, F = () => {
    const r = e.querySelector(q);
    return r instanceof HTMLElement ? r : null;
  }, f = (r) => {
    const o = v();
    if (o) {
      if (r) {
        o.setAttribute("data-formie-loading", "true"), o.setAttribute("aria-busy", "true"), L(o, t, "loading", !0);
        return;
      }
      o.removeAttribute("data-formie-loading"), o.removeAttribute("aria-busy"), L(o, t, "loading", !1);
    }
  }, A = w(e);
  f(!!A.accessToken);
  const c = () => {
    !h || m && !a || (i.log("Queueing fetch."), R());
  }, R = V(async () => {
    const r = w(e);
    if (!v() || !r.accessToken) {
      i.warn("Missing state for fetch.", r), f(!1);
      return;
    }
    d += 1;
    const o = d, O = l;
    u?.abort(), u = new AbortController(), f(!0);
    try {
      const s = await P(t, r, u.signal);
      if (o !== d)
        return;
      const y = F(), S = document.createElement("template");
      S.innerHTML = s.trim();
      const T = S.content.querySelector(q);
      y && T instanceof HTMLElement ? y.replaceWith(T) : y && (y.innerHTML = s), m = !0, a = l !== O, i.log("Fetch complete.", {
        isDirty: a,
        dirtyVersion: l,
        requestVersion: o
      }), M(e, b, "fetch-summary", {
        summary: e,
        html: s
      });
    } catch (s) {
      if (s instanceof DOMException && s.name === "AbortError") {
        i.log("Fetch aborted.");
        return;
      }
      console.error("[formie] Failed to load summary field HTML.", s);
    } finally {
      o === d && (f(!1), u = null, a && c());
    }
  }, 300), D = (r) => {
    const o = r?.target;
    o instanceof Node && e.contains(o) || (a = !0, l += 1, i.log("Marked dirty.", { dirtyVersion: l }));
  }, g = (r) => {
    D(r), c();
  }, p = () => {
    a = !0, i.log("Submit result received; refreshing."), c();
  }, k = () => {
    a = !0, i.log("Page navigation received; refreshing."), c();
  }, E = new IntersectionObserver((r) => {
    h = !!r[0]?.isIntersecting, h && (i.log("Field became visible."), M(e, b, "field-visible", {
      summary: e
    }), c());
  }, {
    root: t,
    rootMargin: "50px"
  });
  return E.observe(e), t.addEventListener("input", g), t.addEventListener("change", g), n.addEventListener("formie:page:navigate:after", k), n.addEventListener("formie:submit:result", p), () => {
    u?.abort(), E.disconnect(), t.removeEventListener("input", g), t.removeEventListener("change", g), n.removeEventListener("formie:page:navigate:after", k), n.removeEventListener("formie:submit:result", p), i.log("Field destroyed.");
  };
}
const Y = {
  id: b,
  kind: "field",
  match: (e) => !!e.target.querySelector(C),
  setup: async (e) => {
    const n = H(e).map((t) => B(t, e.root));
    return i.log("Module setup.", { fieldCount: n.length }), await e.emit("formie:module:summary:init", {
      count: n.length
    }), {
      destroy: () => {
        n.forEach((t) => {
          t();
        }), i.log("Module destroy.", { fieldCount: n.length }), e.emit("formie:module:summary:destroy", {});
      }
    };
  }
};
export {
  Y as summaryModule
};
