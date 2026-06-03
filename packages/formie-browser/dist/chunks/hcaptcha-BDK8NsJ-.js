import { a as h, b as i, C as d } from "./index-WW6SLpnK.js";
import { a as p, e as m } from "./scripts-BjPwFQ0L.js";
async function u(a) {
  const o = typeof a.language == "string" && a.language.trim() !== "" ? a.language.trim() : "en", { async: t, defer: e } = i(a.loadingMethod), r = "FORMIE_HCAPTCHA_ONLOAD", c = window, l = c.hcaptcha;
  if (l)
    return l;
  const n = new Promise((s) => {
    c[r] = () => {
      delete c[r], s();
    };
  });
  return await p({
    id: "FORMIE_HCAPTCHA_SCRIPT",
    src: `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&onload=${encodeURIComponent(r)}&hl=${encodeURIComponent(o)}`,
    async: t,
    defer: e
  }), await n, m("hcaptcha", d);
}
const k = h({
  id: "hcaptcha",
  defaultPlaceholderSelector: "[data-hcaptcha-placeholder]",
  defaultTokenFieldNames: ["h-captcha-response"],
  load: ({ options: a }) => u(a.provider),
  mount: ({ api: a, container: o, provider: t, services: e }) => a.render(o, {
    sitekey: t.siteKey || "",
    theme: t.theme || "light",
    size: t.size || "normal",
    callback: (r) => {
      typeof r == "string" && r.trim() !== "" && e.tokens.write(r.trim()), e.errors.clear();
    },
    "expired-callback": () => {
      e.tokens.clear(), e.errors.clear();
    },
    "chalexpired-callback": () => {
      e.tokens.clear(), e.errors.clear();
    },
    "error-callback": () => {
      e.tokens.clear();
    }
  }),
  screen: ({ api: a, widget: o, placeholder: t, services: e, stageCtx: r }) => {
    if (!e.tokens.has())
      return a.execute(o), e.tokens.wait().then((c) => {
        if (!c) {
          const l = e.errors.getDefaultMessage();
          e.errors.show(l, t), r.abort(l);
        }
      });
  },
  unmount: ({ api: a, widget: o, services: t }) => {
    a.reset(o), t.tokens.clear();
  }
});
export {
  k as hcaptchaModule
};
