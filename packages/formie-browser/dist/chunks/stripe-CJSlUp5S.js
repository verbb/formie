import { v as I, x as C, y as E, z as g } from "./index-Cmikarpm.js";
import { e as P } from "./styles-C3aqgtek.js";
import { l as A } from "./scripts-D7TV7mth.js";
const R = "@layer formie-theme{.formie-stripe-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:var(--formie-space-2);min-height:12rem;width:100%;padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);color:var(--formie-color-text-muted);text-align:center;box-sizing:border-box}.formie-stripe-placeholder[hidden]{display:none}}";
P("stripe", [R]);
const L = "FORMIE_STRIPE_SCRIPT", U = C("stripe", "confirm"), F = "[data-formie-stripe-elements-placeholder]", w = /* @__PURE__ */ new Set([
  "BIF",
  "CLP",
  "DJF",
  "GNF",
  "JPY",
  "KMF",
  "KRW",
  "MGA",
  "PYG",
  "RWF",
  "UGX",
  "VND",
  "VUV",
  "XAF",
  "XOF",
  "XPF"
]);
function M(r, e) {
  return w.has(e.toUpperCase()) ? Math.ceil(r) : Math.ceil(r * 100);
}
function b(r, e) {
  r && (g(r, e, "fieldErrors", "fieldError"), r.querySelectorAll("[data-payment-placeholder-error]").forEach((s) => {
    g(s, e, "fieldError");
  }));
}
function T(r, e, s = "Loading payment options...") {
  if (!r)
    return;
  b(r, e), r.removeAttribute("hidden"), r.innerHTML = "";
  const i = document.createElement("div");
  i.className = "formie-loading";
  const t = document.createElement("div");
  t.textContent = s, r.append(i, t);
}
function v(r, e, s) {
  if (!r)
    return;
  b(r, e), r.removeAttribute("hidden"), E(r, e, "fieldErrors"), r.innerHTML = "";
  const i = document.createElement("div");
  i.setAttribute("data-payment-placeholder-error", ""), i.textContent = s, E(i, e, "fieldError"), r.appendChild(i);
}
const k = I({
  id: "stripe",
  defaultRequiredInputSuffixes: ["stripePaymentIntentId"],
  load: async (r) => {
    const { provider: e } = r.options, s = e.publishableKey;
    return s?.trim() ? (await A("Stripe", {
      id: L,
      src: "https://js.stripe.com/v3"
    }))(s) : (console.error("[formie] Missing publishableKey for Stripe."), null);
  },
  mount: async (r) => {
    const { api: e, field: s, services: i, provider: t } = r, u = s.querySelector("[data-formie-stripe-elements]"), a = s.querySelector(F), d = s, c = i.form || i.root;
    if (!u || !e)
      return null;
    const l = t.initialPaymentInformation || {}, _ = [l.amount, l.currency].map((o) => String(o ?? "").trim()).filter((o, n) => (n === 0 ? t.amountType : t.currencyType) === "dynamic" && o !== ""), y = () => {
      const o = i.resolveAmount({ value: l.amount });
      if (!o.ok)
        return {
          ok: !1,
          error: "error" in o ? o.error : "Provide a payment amount to proceed."
        };
      const n = i.resolveCurrency({ value: l.currency });
      if (!n.ok)
        return {
          ok: !1,
          error: "error" in n ? n.error : "Provide a payment currency to proceed."
        };
      const m = n.value.toLowerCase(), p = t.amountType === "dynamic" ? M(o.value, m) : o.value;
      return {
        ok: !0,
        value: {
          ...l,
          capture_method: "automatic",
          mode: t.paymentType === "subscription" ? "subscription" : "payment",
          appearance: {},
          amount: p,
          currency: m
        }
      };
    }, S = () => {
      try {
        d.__formieStripeWidget?.paymentElement?.destroy?.();
      } catch {
      }
      d.__formieStripeWidget = null, d.__formieStripeElements = void 0, u.innerHTML = "";
    }, f = () => {
      const o = y();
      if (!o.ok) {
        S(), v(
          a,
          c,
          "error" in o ? o.error : "Unable to resolve payment details."
        );
        return;
      }
      try {
        if (d.__formieStripeWidget && d.__formieStripeElements) {
          d.__formieStripeElements.update(o.value), b(a, c), a?.setAttribute("hidden", "hidden");
          return;
        }
        T(a, c);
        const n = e.elements(o.value), m = n.create("payment", {});
        m.mount(u), m.on?.("loaderror", (p) => {
          const h = p?.error?.message || "Unable to load payment options.";
          S(), v(a, c, h);
        }), m.on?.("ready", () => {
          b(a, c), a?.setAttribute("hidden", "hidden");
        }), d.__formieStripeElements = n, d.__formieStripeInstance = e, d.__formieStripeWidget = { elements: n, paymentElement: m }, m.on || (b(a, c), a?.setAttribute("hidden", "hidden"));
      } catch (n) {
        S(), v(
          a,
          c,
          n instanceof Error ? n.message : "Unable to initialize Stripe payment element."
        );
      }
    };
    return d.__formieStripeEvaluateAndRender = f, d.__formieStripeDynamicUnbind?.(), _.length > 0 && (d.__formieStripeDynamicUnbind = i.watchFieldValueChanges(_, () => {
      f();
    }, 600)), f(), d.__formieStripeWidget || null;
  },
  unmount: async (r) => {
    r.widget?.paymentElement?.destroy();
    const e = r.field;
    e.__formieStripeWidget = null, e.__formieStripeElements = void 0, e.__formieStripeInstance = null, e.__formieStripeLastClientSecret = void 0, e.__formieStripeEvaluateAndRender = null, e.__formieStripeDynamicUnbind?.(), e.__formieStripeDynamicUnbind = null;
  },
  onBeforeAuthorize: async (r) => {
    const { widget: e, services: s, field: i } = r, t = i;
    let u = e;
    if (u?.elements || (t.__formieStripeEvaluateAndRender?.(), u = t.__formieStripeWidget || null), !u?.elements)
      return !1;
    const a = await u.elements.submit();
    return a?.error ? (s.addError(a.error.message), !1) : !0;
  },
  setup: async (r) => {
    const { services: e, options: s } = r, i = s.provider, t = r.target, u = async (d) => {
      try {
        const l = d.detail?.data;
        if (!l?.clientSecret || t.__formieStripeConfirming || t.__formieStripeLastClientSecret === l.clientSecret)
          return;
        const _ = t.__formieStripeElements;
        if (!_) {
          e.addError("Stripe elements not ready for 3DS.");
          return;
        }
        const y = t.__formieStripeInstance, S = i.publishableKey;
        if (!y || !S) {
          e.addError("Stripe is not initialized.");
          return;
        }
        t.__formieStripeConfirming = !0;
        const f = new URL(l.returnUrl || window.location.href);
        f.searchParams.set("origin", window.location.href);
        const n = await (l.type === "setup" ? y.confirmSetup : y.confirmPayment)({
          elements: _,
          clientSecret: l.clientSecret,
          redirect: "if_required",
          confirmParams: { return_url: f.toString() }
        });
        if (n?.error) {
          e.releaseSubmitLoading(), e.addError(n.error.message);
          return;
        }
        l.subscriptionId && e.updateInputs("stripeSubscriptionId", l.subscriptionId);
        const m = n && "paymentIntent" in n ? n.paymentIntent : null, p = n && "setupIntent" in n ? n.setupIntent : null;
        if (m?.id)
          e.updateInputs("stripePaymentIntentId", m.id);
        else if (p?.id)
          e.updateInputs("stripePaymentIntentId", p.id);
        else {
          e.releaseSubmitLoading(), e.addError("Stripe confirmation did not return an intent ID.");
          return;
        }
        t.__formieStripeLastClientSecret = l.clientSecret, e.triggerSubmit();
      } catch (c) {
        e.releaseSubmitLoading(), e.addError(c instanceof Error ? c.message : "Unable to confirm Stripe payment.");
      } finally {
        t.__formieStripeConfirming = !1;
      }
    };
    t.__formieStripeConfirmUnbind?.();
    const a = e.events.onForm(U, u);
    return t.__formieStripeConfirmUnbind = a, {
      destroy: () => {
        t.__formieStripeConfirmUnbind?.(), t.__formieStripeConfirmUnbind = null;
      }
    };
  },
  onAfterSubmit: async (r) => {
    const e = r.field;
    r.result?.ok && !r.result?.nextPage && (e.__formieStripeWidget?.paymentElement?.destroy?.(), e.__formieStripeWidget = null, e.__formieStripeElements = void 0, r.services.updateInputs(["stripePaymentIntentId", "stripeSubscriptionId"], ""), e.__formieStripeLastClientSecret = void 0);
  }
});
export {
  k as stripeModule
};
