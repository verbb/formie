import { a as l, C as n } from "./index-C1ZOKiAi.js";
import { l as s } from "./scripts-BkAAux0i.js";
async function c(e) {
  const t = String(e.endPoint || "https://www.captcha.eu").trim().replace(/\/+$/, "");
  return s("KROT", {
    id: "FORMIE_CAPTCHA_EU_SCRIPT",
    src: `${t}/sdk.js`,
    async: !0,
    defer: !0,
    timeoutMs: n
  });
}
const h = l({
  id: "captcha-eu",
  defaultPlaceholderSelector: "[data-captcha-eu-placeholder]",
  defaultTokenFieldNames: ["captcha-eu-token"],
  load: ({ options: e }) => c(e.provider),
  mount: ({ api: e, container: t, provider: o, services: a }) => (e.init(), e.setup(String(o.publicKey || "")), e.WidgetV2.render(t), e.on("CPT_OK", (r) => {
    a.tokens.write(JSON.stringify(r.detail || {}), {
      container: t
    }), a.errors.clear();
  }, t), e.on("CPT_EXPIRED", () => {
    a.tokens.clear(), a.errors.clear();
  }, t), t),
  screen: async ({ placeholder: e, services: t, stageCtx: o }) => {
    if (!await t.tokens.wait()) {
      const r = t.errors.getDefaultMessage();
      t.errors.show(r, e), o.abort(r);
    }
  }
});
export {
  h as captchaEuModule
};
