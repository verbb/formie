import { a as c, c as s } from "./index-CZtn5KAB.js";
import { w as i, l } from "./recaptcha-shared-Bp5hmIt3.js";
const u = c({
  id: "recaptcha-v3",
  defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
  defaultTokenFieldNames: ["g-recaptcha-response"],
  load: ({ options: e }) => l(e.provider, !1, e.provider.siteKey || void 0),
  mount: ({ api: e, provider: o }) => new Promise((r) => {
    e.ready(() => {
      r(o.siteKey || "recaptcha-v3");
    });
  }),
  screen: async ({ api: e, provider: o, placeholder: r, services: t, stageCtx: n }) => {
    if (t.tokens.has())
      return;
    if (await i(e, async () => {
      const a = await e.execute(o.siteKey || "", { action: o.action || "submit" });
      typeof a == "string" && a.trim() !== "" && t.tokens.write(a.trim());
    }), !await t.tokens.wait(s)) {
      const a = t.errors.getDefaultMessage();
      t.errors.show(a, r), n.abort(a);
    }
  },
  unmount: ({ services: e }) => {
    e.tokens.clear();
  }
});
export {
  u as recaptchaV3Module
};
