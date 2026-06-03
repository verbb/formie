import { u as l, v as u } from "./index-BqkORC7E.js";
import { l as c } from "./scripts-DrPCOEBw.js";
const p = "FORMIE_PADDLE_SCRIPT", m = u("paddle", "initialize"), h = l({
  id: "paddle",
  defaultRequiredInputSuffixes: ["paddleCheckoutData"],
  load: async () => null,
  setup: async (d) => {
    const { services: t } = d;
    d.target;
    const o = d.options.provider, n = o.clientSideToken;
    if (!n?.trim())
      return t.addError("Missing clientSideToken for Paddle."), {};
    let a;
    try {
      a = await c("Paddle", {
        id: p,
        src: "https://cdn.paddle.com/paddle/v2/paddle.js"
      }), a.Environment.set(o.environment || "production");
    } catch (e) {
      return t.addError(e instanceof Error ? e.message : "Failed to load Paddle SDK."), {};
    }
    a.Initialize({
      token: n,
      checkout: {
        settings: {
          displayMode: "overlay",
          variant: "multi-page"
        }
      },
      eventCallback: (e) => {
        e.name === "checkout.completed" && (t.updateInputs("paddleCheckoutInit", ""), t.updateInputs("paddleCheckoutData", JSON.stringify(e.data || {})), setTimeout(() => {
          a.Checkout.close(), t.triggerSubmit();
        }, 500));
      }
    });
    const i = (e) => {
      if (!e?.items)
        return t.addError("Missing Paddle checkout items."), !1;
      try {
        t.releaseSubmitLoading(), a.Checkout.open(e);
      } catch (r) {
        return t.addError(r instanceof Error ? r.message : "Unable to open Paddle checkout."), !1;
      }
      return !0;
    }, s = t.events.onForm(m, ((e) => {
      i(e.detail?.data);
    }));
    return {
      destroy: () => {
        s();
      }
    };
  },
  onAfterSubmit: async ({ services: d }) => {
    d.updateInputs("paddleCheckoutInit", "true"), d.updateInputs("paddleCheckoutData", "");
  }
});
export {
  h as paddleModule
};
