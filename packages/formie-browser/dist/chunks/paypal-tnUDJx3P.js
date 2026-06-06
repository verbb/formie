import { v as b } from "./index-Cmikarpm.js";
import { e as I } from "./styles-C3aqgtek.js";
import { l as v } from "./scripts-D7TV7mth.js";
const g = "@layer formie-theme{.formie-paypal-button{border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;min-height:var(--formie-control-height);padding:var(--formie-space-2)}}";
I("paypal", [g]);
const A = "FORMIE_PAYPAL_SCRIPT";
function P(r) {
  if (!r)
    return "";
  const a = (r.purchase_units || [])[0]?.payments || {}, e = a.authorizations || [], c = a.captures || [];
  return String(e[0]?.id || c[0]?.id || "").trim();
}
function S(r, o) {
  return `https://www.paypal.com/sdk/js?${[
    "intent=authorize",
    `currency=${encodeURIComponent(o)}`,
    `client-id=${encodeURIComponent(r)}`
  ].join("&")}`;
}
const U = b({
  id: "paypal",
  defaultRequiredInputSuffixes: ["paypalOrderId", "paypalAuthId"],
  load: async (r) => {
    const { provider: o } = r.options, a = o.clientId;
    if (!a?.trim())
      return console.error("[formie] Missing clientId for PayPal."), null;
    const e = o.currency || "AUD", c = S(a, e);
    return await v("paypal", {
      id: A,
      src: c
    }), window.paypal;
  },
  mount: async (r) => {
    const { api: o, field: a, services: e, options: c, provider: t } = r, i = a.querySelector("[data-formie-paypal-button]");
    if (!i || !o || i.getAttribute("data-formie-paypal-rendered") === "true")
      return null;
    i.innerHTML = "";
    const y = !!t.useSandbox, s = e.resolveCurrency({ value: t.currency, defaultCurrency: "AUD" });
    if (!s.ok)
      return e.addError("error" in s ? s.error : "Invalid PayPal currency."), null;
    const m = s.value, l = {
      layout: t.buttonLayout || "vertical",
      color: t.buttonColor || "gold",
      shape: t.buttonShape || "rect",
      label: t.buttonLabel || "paypal",
      width: t.buttonWidth || 250,
      height: t.buttonHeight || 35
    };
    l.layout === "horizontal" && (l.tagline = t.buttonTagline ?? !0);
    const f = {
      env: y ? "sandbox" : "production",
      style: l,
      createOrder: (d, p) => {
        e.removeError();
        const n = e.resolveAmount({
          type: t.amountType,
          fixed: t.amountFixed,
          variable: t.amountVariable
        });
        if (!n.ok) {
          const u = "error" in n ? n.error : "Invalid PayPal amount.";
          throw e.addError(u), new Error(u);
        }
        return p.order.create({
          intent: "AUTHORIZE",
          application_context: { user_action: "CONTINUE" },
          purchase_units: [{
            amount: {
              currency_code: m,
              value: String(n.value)
            }
          }]
        });
      },
      onError: (d) => {
        e.addError(d?.message || "PayPal error.");
      },
      onApprove: async (d, p) => {
        try {
          const n = await p.order.authorize(), u = P(n);
          e.updateInputs("paypalOrderId", d.orderID), e.updateInputs("paypalAuthId", u || ""), u ? e.addSuccess("Payment authorized. Finalize the form to complete payment.") : e.addSuccess("PayPal approval received. Finalizing payment on submit.");
        } catch {
          e.addError("Unable to authorize payment. Please try again.");
        }
      }
    }, h = o.Buttons(f);
    return i.setAttribute("data-formie-paypal-rendered", "true"), h.render(i);
  },
  unmount: async (r) => {
    r.widget?.close && r.widget.close();
    const o = r.field.querySelector("[data-formie-paypal-button]");
    o && (o.removeAttribute("data-formie-paypal-rendered"), o.innerHTML = "");
  },
  onAfterSubmit: async ({ services: r }) => {
    r.updateInputs(["paypalOrderId", "paypalAuthId"], ""), r.removeSuccess(), r.removeError();
  }
});
export {
  U as paypalModule
};
