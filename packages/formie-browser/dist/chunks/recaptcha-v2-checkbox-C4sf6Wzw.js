import { a as c } from "./index-WW6SLpnK.js";
import { l as s } from "./recaptcha-shared-Bsph4Y9n.js";
const d = c({
  id: "recaptcha-v2-checkbox",
  defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
  defaultTokenFieldNames: ["g-recaptcha-response"],
  load: ({ options: e }) => s(e.provider),
  mount: ({ api: e, container: r, provider: a, services: t }) => new Promise((l) => {
    e.ready(() => {
      l(e.render(r, {
        sitekey: a.siteKey || "",
        theme: a.theme || "light",
        size: a.size || "normal",
        callback: (o) => {
          typeof o == "string" && o.trim() !== "" && t.tokens.write(o.trim()), t.errors.clear();
        },
        "expired-callback": () => {
          t.tokens.clear(), t.errors.clear();
        },
        "error-callback": () => {
          t.tokens.clear();
        }
      }));
    });
  }),
  screen: ({ placeholder: e, services: r, stageCtx: a }) => {
    if (r.tokens.has())
      return;
    const t = r.errors.getDefaultMessage();
    r.errors.show(t, e), a.abort(t);
  },
  reset: ({ api: e, widget: r, services: a }) => {
    e.reset(r), a.tokens.clear();
  },
  unmount: ({ api: e, widget: r, services: a }) => {
    e.reset(r), a.tokens.clear();
  }
});
export {
  d as recaptchaV2CheckboxModule
};
