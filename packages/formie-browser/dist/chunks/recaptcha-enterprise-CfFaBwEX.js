import { a as i, c as p } from "./index-MuyEvWaf.js";
import { l } from "./recaptcha-shared-DQI1SN8Y.js";
const y = i({
  id: "recaptcha-enterprise",
  defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
  defaultTokenFieldNames: ["g-recaptcha-response"],
  load: ({ options: t }) => l(
    t.provider,
    !0,
    (t.provider.enterpriseType === "score" || t.provider.enterpriseType === "policy") && t.provider.siteKey || void 0
  ),
  mount: ({ api: t, container: s, provider: e, services: a }) => {
    const r = t.enterprise || t;
    return new Promise((c) => {
      r.ready(() => {
        if (e.enterpriseType !== "checkbox") {
          c(e.siteKey || `recaptcha-enterprise-${e.enterpriseType || "score"}`);
          return;
        }
        c(r.render(s, {
          sitekey: e.siteKey || "",
          theme: e.theme || "light",
          badge: e.badge || "bottomright",
          size: e.size || "normal",
          action: e.action || "submit",
          callback: (o) => {
            typeof o == "string" && o.trim() !== "" && a.tokens.write(o.trim()), a.errors.clear();
          },
          "expired-callback": () => {
            a.tokens.clear(), a.errors.clear();
          },
          "error-callback": () => {
            a.tokens.clear();
          }
        }));
      });
    });
  },
  screen: async ({ api: t, widget: s, provider: e, placeholder: a, services: r, stageCtx: c }) => {
    const o = t.enterprise || t;
    if (e.enterpriseType === "checkbox") {
      if (r.tokens.has())
        return;
      const n = r.errors.getDefaultMessage();
      r.errors.show(n, a), c.abort(n);
      return;
    }
    if (r.tokens.has())
      return;
    if (e.enterpriseType === "score" || e.enterpriseType === "policy") {
      const n = await o.execute(e.siteKey || "", { action: e.action || "submit" });
      typeof n == "string" && n.trim() !== "" && r.tokens.write(n.trim());
    } else
      o.execute(s);
    if (!await r.tokens.wait(p)) {
      const n = r.errors.getDefaultMessage();
      r.errors.show(n, a), c.abort(n);
    }
  },
  reset: ({ api: t, widget: s, provider: e, services: a }) => {
    const r = t.enterprise || t;
    e.enterpriseType === "checkbox" && r.reset(s), a.tokens.clear();
  },
  unmount: ({ api: t, widget: s, provider: e, services: a }) => {
    const r = t.enterprise || t;
    e.enterpriseType === "checkbox" && r.reset(s), a.tokens.clear();
  }
});
export {
  y as recaptchaEnterpriseModule
};
