import { a as c, c as l } from "./index-DL83ZezE.js";
import { l as d } from "./recaptcha-shared-BpjJn6iX.js";
async function p(e, o, r = 1e3) {
  const t = Date.now() + r;
  for (; Date.now() < t; ) {
    const n = typeof e.getResponse == "function" ? e.getResponse(o) : "";
    if (typeof n == "string" && n.trim() !== "")
      return n.trim();
    await new Promise((a) => {
      window.setTimeout(a, 100);
    });
  }
}
const u = c({
  id: "recaptcha-v2-invisible",
  defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
  defaultTokenFieldNames: ["g-recaptcha-response"],
  load: ({ options: e }) => d(e.provider),
  mount: ({ api: e, container: o, provider: r, services: t }) => new Promise((n) => {
    e.ready(() => {
      const a = e.render(o, {
        sitekey: r.siteKey || "",
        badge: r.badge || "bottomright",
        size: "invisible",
        callback: (i) => {
          const s = typeof i == "string" && i.trim() !== "" ? i.trim() : typeof e.getResponse == "function" ? e.getResponse(a) : "";
          s && t.tokens.write(s), t.errors.clear();
        },
        "expired-callback": () => {
          t.tokens.clear(), t.errors.clear();
        },
        "error-callback": () => {
          t.tokens.clear();
        }
      });
      n({
        // Keep the widget id in a tiny object so later lifecycle
        // methods have a stable shape to work with.
        id: a
      });
    });
  }),
  screen: async ({ api: e, widget: o, placeholder: r, services: t, stageCtx: n }) => {
    if (t.tokens.has())
      return;
    e.execute(o.id);
    const a = await p(e, o.id);
    if (typeof a == "string" && a.trim() !== "" && t.tokens.write(a.trim()), !await t.tokens.wait(l)) {
      const s = t.errors.getDefaultMessage();
      t.errors.show(s, r), n.abort(s);
    }
  },
  unmount: ({ api: e, widget: o, services: r }) => {
    e.reset(o.id), r.tokens.clear();
  }
});
export {
  u as recaptchaV2InvisibleModule
};
