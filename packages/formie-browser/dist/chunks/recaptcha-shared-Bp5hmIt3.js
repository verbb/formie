import { l as o } from "./scripts--tQDv1Kx.js";
import { b as h, C as l } from "./index-CZtn5KAB.js";
async function p(t, a = !1, e) {
  const s = typeof t.language == "string" && t.language.trim() !== "" ? t.language.trim() : "en", { async: r, defer: n } = h(t.loadingMethod), g = a ? "https://www.google.com/recaptcha/enterprise.js" : "https://www.recaptcha.net/recaptcha/api.js", i = typeof e == "string" && e.trim() !== "" ? e.trim() : "explicit", c = new URL(g);
  return c.searchParams.set("render", i), c.searchParams.set("hl", s), i !== "explicit" && typeof t.badge == "string" && t.badge.trim() !== "" && c.searchParams.set("badge", t.badge.trim()), o("grecaptcha", {
    id: a ? "FORMIE_RECAPTCHA_ENTERPRISE_SCRIPT" : "FORMIE_RECAPTCHA_SCRIPT",
    src: c.toString(),
    async: r,
    defer: n,
    timeoutMs: l
  });
}
function R(t, a) {
  const e = t.enterprise || t;
  return new Promise((s, r) => {
    try {
      e.ready(() => {
        Promise.resolve(a()).then(s).catch(r);
      });
    } catch (n) {
      r(n);
    }
  });
}
export {
  p as l,
  R as w
};
