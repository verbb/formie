import { u } from "./index-BqkORC7E.js";
import { l as c } from "./scripts-DrPCOEBw.js";
const l = "FORMIE_SQUARE_SCRIPT", f = u({
  id: "square",
  defaultRequiredInputSuffixes: ["squarePaymentId"],
  load: async (n) => {
    const { provider: e } = n.options, r = e.applicationId, t = e.locationId;
    if (!r?.trim() || !t?.trim())
      return console.error("[formie] Missing applicationId or locationId for Square."), null;
    const a = e.environment === "sandbox" ? "https://sandbox.web.squarecdn.com/v1/square.js" : "https://web.squarecdn.com/v1/square.js";
    return await c("Square", {
      id: l,
      src: a
    }), window.Square;
  },
  mount: async (n) => {
    const { api: e, field: r, services: t, options: s } = n, a = s.provider, i = r.querySelector("[data-formie-square-button]");
    if (!i || !e)
      return null;
    try {
      const d = await e.payments(a.applicationId || "", a.locationId || "").card();
      return await d.attach(i), d;
    } catch (o) {
      return t.addError(o instanceof Error ? o.message : "Unable to initialize payment."), null;
    }
  },
  unmount: async () => {
  },
  onBeforeAuthorize: async (n) => {
    const { widget: e, services: r } = n;
    if (!e)
      return r.addError("Square card is not ready."), !1;
    try {
      const t = await e.tokenize();
      return t.status === "OK" && t.token ? (r.updateInputs("squarePaymentId", t.token), !0) : (r.addError(t.errors?.[0]?.message || "Tokenization failed."), !1);
    } catch {
      return r.addError("Payment tokenization failed. Please try again."), !1;
    }
  },
  onAfterSubmit: async ({ services: n }) => {
    n.updateInputs("squarePaymentId", "");
  }
});
export {
  f as squareModule
};
