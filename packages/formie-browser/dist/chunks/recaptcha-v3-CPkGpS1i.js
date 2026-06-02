import { a as c, c as i } from "./index-MuyEvWaf.js";
import { l } from "./recaptcha-shared-DQI1SN8Y.js";
const u = c({
  id: "recaptcha-v3",
  defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
  defaultTokenFieldNames: ["g-recaptcha-response"],
  load: ({ options: e }) => l(e.provider, !1, e.provider.siteKey || void 0),
  mount: ({ api: e, provider: t }) => new Promise((o) => {
    e.ready(() => {
      o(t.siteKey || "recaptcha-v3");
    });
  }),
  screen: async ({ api: e, provider: t, placeholder: o, services: a, stageCtx: s }) => {
    if (a.tokens.has())
      return;
    const r = await e.execute(t.siteKey || "", { action: t.action || "submit" });
    if (typeof r == "string" && r.trim() !== "" && a.tokens.write(r.trim()), !await a.tokens.wait(i)) {
      const n = a.errors.getDefaultMessage();
      a.errors.show(n, o), s.abort(n);
    }
  },
  unmount: ({ services: e }) => {
    e.tokens.clear();
  }
});
export {
  u as recaptchaV3Module
};
