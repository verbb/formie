import { x as I } from "./index-CZtn5KAB.js";
import { e as v } from "./styles-C3aqgtek.js";
import { l as g } from "./scripts--tQDv1Kx.js";
const P = "@layer formie-theme{.formie-paypal-button{border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;min-height:var(--formie-control-height);padding:var(--formie-space-2)}}";
v("paypal", [P]);
const A = "FORMIE_PAYPAL_SCRIPT";
function S(r) {
  if (!r)
    return "";
  const a = (r.purchase_units || [])[0]?.payments || {}, t = a.authorizations || [], l = a.captures || [];
  return String(t[0]?.id || l[0]?.id || "").trim();
}
function w(r, e) {
  r?.close && r.close(), e.removeAttribute("data-formie-paypal-rendered"), e.innerHTML = "";
}
function x(r, e) {
  return `https://www.paypal.com/sdk/js?${[
    "intent=authorize",
    `currency=${encodeURIComponent(e)}`,
    `client-id=${encodeURIComponent(r)}`
  ].join("&")}`;
}
const C = I({
  id: "paypal",
  defaultRequiredInputSuffixes: ["paypalOrderId", "paypalAuthId"],
  load: async (r) => {
    const { provider: e } = r.options, a = e.clientId;
    if (!a?.trim())
      return console.error("[formie] Missing clientId for PayPal."), null;
    const t = e.currency || "AUD", l = x(a, t);
    return await g("paypal", {
      id: A,
      src: l
    }), window.paypal;
  },
  mount: async (r) => {
    const { api: e, field: a, services: t, options: l, provider: o } = r, n = a.querySelector("[data-formie-paypal-button]");
    if (!n || !e || n.getAttribute("data-formie-paypal-rendered") === "true")
      return null;
    n.innerHTML = "";
    const m = !!o.useSandbox, c = t.resolveCurrency({ value: o.currency, defaultCurrency: "AUD" });
    if (!c.ok)
      return t.addError("error" in c ? c.error : "Invalid PayPal currency."), null;
    const f = c.value, s = {
      layout: o.buttonLayout || "vertical",
      color: o.buttonColor || "gold",
      shape: o.buttonShape || "rect",
      label: o.buttonLabel || "paypal",
      width: o.buttonWidth || 250,
      height: o.buttonHeight || 35
    };
    s.layout === "horizontal" && (s.tagline = o.buttonTagline ?? !0);
    let p = null;
    const h = {
      env: m ? "sandbox" : "production",
      style: s,
      createOrder: (d, y) => {
        t.removeError();
        const i = t.resolveAmount({
          type: o.amountType,
          fixed: o.amountFixed,
          variable: o.amountVariable
        });
        if (!i.ok) {
          const u = "error" in i ? i.error : "Invalid PayPal amount.";
          throw t.addError(u), new Error(u);
        }
        return y.order.create({
          intent: "AUTHORIZE",
          application_context: { user_action: "CONTINUE" },
          purchase_units: [{
            amount: {
              currency_code: f,
              value: String(i.value)
            }
          }]
        });
      },
      onError: (d) => {
        t.addError(d?.message || "PayPal error.");
      },
      onApprove: async (d, y) => {
        try {
          const i = await y.order.authorize(), u = S(i);
          t.updateInputs("paypalOrderId", d.orderID), t.updateInputs("paypalAuthId", u || ""), u ? t.addSuccess("Payment authorized. Finalize the form to complete payment.") : t.addSuccess("PayPal approval received. Finalizing payment on submit."), w(p, n);
        } catch {
          t.addError("Unable to authorize payment. Please try again.");
        }
      }
    }, b = e.Buttons(h);
    return n.setAttribute("data-formie-paypal-rendered", "true"), p = b.render(n), p;
  },
  unmount: async (r) => {
    r.widget?.close && r.widget.close();
    const e = r.field.querySelector("[data-formie-paypal-button]");
    e && (e.removeAttribute("data-formie-paypal-rendered"), e.innerHTML = "");
  },
  onAfterSubmit: async ({ services: r, result: e }) => (r.updateInputs(["paypalOrderId", "paypalAuthId"], ""), r.removeSuccess(), r.removeError(), !e?.ok || e?.nextPage ? { remount: !0 } : {})
});
export {
  C as paypalModule
};
