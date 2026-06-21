import { s as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-C8wyLC-s.js";
import { r as n, t as r } from "./theme-classes-Tv7q7ToE.js";
import { r as i } from "./scripts-CbQ7agX3.js";
import { t as a } from "./styles-BfoIZwJp.js";
//#endregion
//#region src/js/modules/payments/stripe.ts
a("stripe", ["@layer formie-theme{.formie-stripe-placeholder{justify-content:center;align-items:center;gap:var(--formie-space-2);width:100%;min-height:12rem;padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);color:var(--formie-color-text-muted);text-align:center;box-sizing:border-box;flex-direction:column;display:flex}.formie-stripe-placeholder[hidden]{display:none}}"]);
var o = "FORMIE_STRIPE_SCRIPT", s = e("stripe", "confirm"), c = "[data-formie-stripe-elements-placeholder]", l = new Set([
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
function u(e, t) {
	return l.has(t.toUpperCase()) ? Math.ceil(e) : Math.ceil(e * 100);
}
function d(e, t) {
	e && (n(e, t, "fieldErrors", "fieldError"), e.querySelectorAll("[data-payment-placeholder-error]").forEach((e) => {
		n(e, t, "fieldError");
	}));
}
function f(e, t, n = "Loading payment options...") {
	if (!e) return;
	d(e, t), e.removeAttribute("hidden"), e.innerHTML = "";
	let r = document.createElement("div");
	r.className = "formie-loading";
	let i = document.createElement("div");
	i.textContent = n, e.append(r, i);
}
function p(e, t, n) {
	if (!e) return;
	d(e, t), e.removeAttribute("hidden"), r(e, t, "fieldErrors"), e.innerHTML = "";
	let i = document.createElement("div");
	i.setAttribute("data-payment-placeholder-error", ""), i.textContent = n, r(i, t, "fieldError"), e.appendChild(i);
}
var m = t({
	id: "stripe",
	defaultRequiredInputSuffixes: ["stripePaymentIntentId"],
	load: async (e) => {
		let { provider: t } = e.options, n = t.publishableKey;
		return n?.trim() ? (await i("Stripe", {
			id: o,
			src: "https://js.stripe.com/v3"
		}))(n) : (console.error("[formie] Missing publishableKey for Stripe."), null);
	},
	mount: async (e) => {
		let { api: t, field: n, services: r, provider: i } = e, a = n.querySelector("[data-formie-stripe-elements]"), o = n.querySelector(c), s = n, l = r.form || r.root;
		if (!a || !t) return null;
		let m = i.initialPaymentInformation || {}, h = [m.amount, m.currency].map((e) => String(e ?? "").trim()).filter((e, t) => (t === 0 ? i.amountType : i.currencyType) === "dynamic" && e !== ""), g = () => {
			let e = r.resolveAmount({ value: m.amount });
			if (!e.ok) return {
				ok: !1,
				error: "error" in e ? e.error : "Provide a payment amount to proceed."
			};
			let t = r.resolveCurrency({ value: m.currency });
			if (!t.ok) return {
				ok: !1,
				error: "error" in t ? t.error : "Provide a payment currency to proceed."
			};
			let n = t.value.toLowerCase(), a = i.amountType === "dynamic" ? u(e.value, n) : e.value;
			return {
				ok: !0,
				value: {
					...m,
					capture_method: "automatic",
					mode: i.paymentType === "subscription" ? "subscription" : "payment",
					appearance: {},
					amount: a,
					currency: n
				}
			};
		}, _ = () => {
			try {
				s.__formieStripeWidget?.paymentElement?.destroy?.();
			} catch {}
			s.__formieStripeWidget = null, s.__formieStripeElements = void 0, a.innerHTML = "";
		}, v = () => {
			let e = g();
			if (!e.ok) {
				_(), p(o, l, "error" in e ? e.error : "Unable to resolve payment details.");
				return;
			}
			try {
				if (s.__formieStripeWidget && s.__formieStripeElements) {
					s.__formieStripeElements.update(e.value), d(o, l), o?.setAttribute("hidden", "hidden");
					return;
				}
				f(o, l);
				let n = t.elements(e.value), r = n.create("payment", {});
				r.mount(a), r.on?.("loaderror", (e) => {
					let t = e?.error?.message || "Unable to load payment options.";
					_(), p(o, l, t);
				}), r.on?.("ready", () => {
					d(o, l), o?.setAttribute("hidden", "hidden");
				}), s.__formieStripeElements = n, s.__formieStripeInstance = t, s.__formieStripeWidget = {
					elements: n,
					paymentElement: r
				}, r.on || (d(o, l), o?.setAttribute("hidden", "hidden"));
			} catch (e) {
				_(), p(o, l, e instanceof Error ? e.message : "Unable to initialize Stripe payment element.");
			}
		};
		return s.__formieStripeEvaluateAndRender = v, s.__formieStripeDynamicUnbind?.(), h.length > 0 && (s.__formieStripeDynamicUnbind = r.watchFieldValueChanges(h, () => {
			v();
		}, 600)), v(), s.__formieStripeWidget || null;
	},
	unmount: async (e) => {
		e.widget?.paymentElement?.destroy();
		let t = e.field;
		t.__formieStripeWidget = null, t.__formieStripeElements = void 0, t.__formieStripeInstance = null, t.__formieStripeLastClientSecret = void 0, t.__formieStripeEvaluateAndRender = null, t.__formieStripeDynamicUnbind?.(), t.__formieStripeDynamicUnbind = null;
	},
	onBeforeAuthorize: async (e) => {
		let { widget: t, services: n, field: r } = e, i = r, a = t;
		if (a?.elements || (i.__formieStripeEvaluateAndRender?.(), a = i.__formieStripeWidget || null), !a?.elements) return !1;
		let o = await a.elements.submit();
		return o?.error ? (n.addError(o.error.message), !1) : !0;
	},
	setup: async (e) => {
		let { services: t, options: n } = e, r = n.provider, i = e.target;
		return i.__formieStripeConfirmUnbind?.(), i.__formieStripeConfirmUnbind = t.events.onForm(s, async (e) => {
			try {
				let n = e.detail?.data;
				if (!n?.clientSecret || i.__formieStripeConfirming || i.__formieStripeLastClientSecret === n.clientSecret) return;
				let a = i.__formieStripeElements;
				if (!a) {
					t.addError("Stripe elements not ready for 3DS.");
					return;
				}
				let o = i.__formieStripeInstance, s = r.publishableKey;
				if (!o || !s) {
					t.addError("Stripe is not initialized.");
					return;
				}
				i.__formieStripeConfirming = !0;
				let c = new URL(n.returnUrl || window.location.href);
				c.searchParams.set("origin", window.location.href);
				let l = await (n.type === "setup" ? o.confirmSetup : o.confirmPayment)({
					elements: a,
					clientSecret: n.clientSecret,
					redirect: "if_required",
					confirmParams: { return_url: c.toString() }
				});
				if (l?.error) {
					t.releaseSubmitLoading(), t.addError(l.error.message);
					return;
				}
				n.subscriptionId && t.updateInputs("stripeSubscriptionId", n.subscriptionId);
				let u = l && "paymentIntent" in l ? l.paymentIntent : null, d = l && "setupIntent" in l ? l.setupIntent : null;
				if (u?.id) t.updateInputs("stripePaymentIntentId", u.id);
				else if (d?.id) t.updateInputs("stripePaymentIntentId", d.id);
				else {
					t.releaseSubmitLoading(), t.addError("Stripe confirmation did not return an intent ID.");
					return;
				}
				i.__formieStripeLastClientSecret = n.clientSecret, t.triggerSubmit();
			} catch (e) {
				t.releaseSubmitLoading(), t.addError(e instanceof Error ? e.message : "Unable to confirm Stripe payment.");
			} finally {
				i.__formieStripeConfirming = !1;
			}
		}), { destroy: () => {
			i.__formieStripeConfirmUnbind?.(), i.__formieStripeConfirmUnbind = null;
		} };
	},
	onAfterSubmit: async (e) => {
		let t = e.field;
		e.result?.ok && !e.result?.nextPage && (t.__formieStripeWidget?.paymentElement?.destroy?.(), t.__formieStripeWidget = null, t.__formieStripeElements = void 0, e.services.updateInputs(["stripePaymentIntentId", "stripeSubscriptionId"], ""), t.__formieStripeLastClientSecret = void 0);
	}
});
//#endregion
export { m as stripeModule };
