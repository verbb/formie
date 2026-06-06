import { l } from "./scripts-CRvKwopA.js";
import { b as m, C as o } from "./index-BneHZL41.js";
async function R(t, r = !1, e) {
  const s = typeof t.language == "string" && t.language.trim() !== "" ? t.language.trim() : "en", { async: g, defer: i } = m(t.loadingMethod), n = r ? "https://www.google.com/recaptcha/enterprise.js" : "https://www.recaptcha.net/recaptcha/api.js", c = typeof e == "string" && e.trim() !== "" ? e.trim() : "explicit", a = new URL(n);
  return a.searchParams.set("render", c), a.searchParams.set("hl", s), c !== "explicit" && typeof t.badge == "string" && t.badge.trim() !== "" && a.searchParams.set("badge", t.badge.trim()), l("grecaptcha", {
    id: r ? "FORMIE_RECAPTCHA_ENTERPRISE_SCRIPT" : "FORMIE_RECAPTCHA_SCRIPT",
    src: a.toString(),
    async: g,
    defer: i,
    timeoutMs: o
  });
}
export {
  R as l
};
