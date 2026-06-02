import { u as y } from "./index-MuyEvWaf.js";
import { e as u } from "./styles-C3aqgtek.js";
import { l as p } from "./scripts-BlHNQs0M.js";
const f = "@layer formie-theme{.formie-payway-button{border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;min-height:var(--formie-control-height);padding:var(--formie-space-2)}}";
u("payway", [f]);
const c = "FORMIE_PAYWAY_SCRIPT", h = y({
  id: "payway",
  defaultRequiredInputSuffixes: ["paywayTokenId"],
  load: async (e) => {
    const { provider: a } = e.options;
    return a.publishableKey?.trim() ? (await p("payway", {
      id: c,
      src: "https://api.payway.com.au/rest/v1/payway.js"
    }), null) : (console.error("[formie] Missing publishableKey for PayWay."), null);
  },
  mount: async (e) => {
    const { field: a, services: r, options: o } = e, i = o.provider;
    if (!a.querySelector("[data-formie-payway-button]"))
      return null;
    const t = window.payway;
    return t ? new Promise((s) => {
      t.createCreditCardFrame({
        layout: "wide",
        publishableApiKey: i.publishableKey || "",
        tokenMode: "callback"
      }, (d, l) => {
        if (d || !l) {
          r.addError(d?.message || "PayWay frame failed to load."), s(null);
          return;
        }
        s(l);
      });
    }) : null;
  },
  unmount: async (e) => {
    e.widget?.destroy();
  },
  onBeforeAuthorize: async (e) => {
    const { widget: a, services: r } = e;
    return a ? new Promise((o) => {
      a.getToken((i, n) => {
        if (i) {
          r.addError(i.message), o(!1);
          return;
        }
        n?.singleUseTokenId ? (r.updateInputs("paywayTokenId", n.singleUseTokenId), o(!0)) : (r.addError("Tokenization failed."), o(!1));
      });
    }) : (r.addError("PayWay card frame is not ready."), !1);
  },
  onAfterSubmit: async ({ services: e }) => {
    e.updateInputs("paywayTokenId", "");
  }
});
export {
  h as paywayModule
};
