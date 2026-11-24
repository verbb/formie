import { s as e } from "./event-names-BCI2FLD8.js";
import { r as t, t as n } from "./api-Bd1QUEc3.js";
import { i as r, t as i } from "./theme-classes-DAQuEqdP.js";
import { i as a } from "./scripts-Br6ohEoT.js";
import { t as o } from "./styles-CBQ_fQem.js";
//#endregion
//#region src/js/modules/payments/stripe.ts
o("stripe", ["@layer formie-theme{.formie-stripe-placeholder{justify-content:center;align-items:center;gap:var(--formie-space-2);width:100%;min-height:12rem;padding:var(--formie-space-4);border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);color:var(--formie-color-text-muted);text-align:center;box-sizing:border-box;flex-direction:column;display:flex}.formie-stripe-placeholder[hidden]{display:none}}"]);
var s = "FORMIE_STRIPE_SCRIPT", c = e("stripe", "confirm"), l = "[data-formie-stripe-elements-placeholder]", u = /* @__PURE__ */ new Set([
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
	"VND",
	"VUV",
	"XAF",
	"XOF",
	"XPF"
]), d = /* @__PURE__ */ new Set([
	"BHD",
	"JOD",
	"KWD",
	"OMR",
	"TND"
]);
function f(e, t) {
	return u.has(t.toUpperCase()) ? Math.ceil(e) : d.has(t.toUpperCase()) ? Math.round((e + 2 ** -52) * 100) * 10 : Math.round((e + 2 ** -52) * 100);
}
function p(e, t) {
	e && (r(e, t, "fieldErrors", "fieldError"), e.querySelectorAll("[data-payment-placeholder-error]").forEach((e) => {
		r(e, t, "fieldError");
	}));
}
function m(e, t, n = "Loading payment options...") {
	if (!e) return;
	p(e, t), e.removeAttribute("hidden"), e.innerHTML = "";
	let r = document.createElement("div");
	i(r, t, "loading");
	let a = document.createElement("div");
	a.textContent = n, e.append(r, a);
}
function h(e, t, n) {
	if (!e) return;
	p(e, t), e.removeAttribute("hidden"), i(e, t, "fieldErrors"), e.innerHTML = "";
	let r = document.createElement("div");
	r.setAttribute("data-payment-placeholder-error", ""), r.textContent = n, i(r, t, "fieldError"), e.appendChild(r);
}
var g = n({
	moduleId: "formie:stripe",
	defaultRequiredInputSuffixes: ["stripePaymentIntentId"],
	load: async (e) => {
		let { provider: t } = e.options, n = t.publishableKey;
		return n?.trim() ? (await a("Stripe", {
			id: s,
			src: "https://js.stripe.com/v3"
		}))(n) : (console.error("[formie] Missing publishableKey for Stripe."), null);
	},
	mount: async (e) => {
		let { api: t, field: n, services: r, provider: i } = e, a = n.querySelector("[data-formie-stripe-elements]"), o = n.querySelector(l), s = n, c = r.form || r.root;
		if (!a || !t) return null;
		let u = i.initialPaymentInformation || {}, d = [u.amount, u.currency].map((e) => String(e ?? "").trim()).filter((e, t) => (t === 0 ? i.amountType : i.currencyType) === "dynamic" && e !== ""), g = () => {
			let e = r.resolveAmount({
				type: i.amountType,
				value: u.amount
			});
			if (!e.ok) return {
				ok: !1,
				error: "error" in e ? e.error : "Provide a payment amount to proceed."
			};
			let t = r.resolveCurrency({
				type: i.currencyType,
				value: u.currency
			});
			if (!t.ok) return {
				ok: !1,
				error: "error" in t ? t.error : "Provide a payment currency to proceed."
			};
			let n = t.value.toLowerCase(), a = i.amountType === "dynamic" || i.currencyType === "dynamic" ? f(e.value, n) : e.value;
			return {
				ok: !0,
				value: {
					...u,
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
			if (!e.ok) _(), h(o, c, "error" in e ? e.error : "Unable to resolve payment details.");
			else try {
				if (s.__formieStripeWidget && s.__formieStripeElements) {
					s.__formieStripeElements.update(e.value), p(o, c), o?.setAttribute("hidden", "hidden");
					return;
				}
				m(o, c);
				let n = t.elements(e.value), r = n.create("payment", {});
				r.mount(a), r.on?.("loaderror", (e) => {
					let t = e?.error?.message || "Unable to load payment options.";
					_(), h(o, c, t);
				}), r.on?.("ready", () => {
					p(o, c), o?.setAttribute("hidden", "hidden");
				}), s.__formieStripeElements = n, s.__formieStripeInstance = t, s.__formieStripeWidget = {
					elements: n,
					paymentElement: r
				}, r.on || (p(o, c), o?.setAttribute("hidden", "hidden"));
			} catch (e) {
				_(), h(o, c, e instanceof Error ? e.message : "Unable to initialize Stripe payment element.");
			}
		};
		return s.__formieStripeEvaluateAndRender = v, s.__formieStripeDynamicUnbind?.(), d.length > 0 && (s.__formieStripeDynamicUnbind = r.watchFieldValueChanges(d, () => {
			v();
		}, 600)), v(), s.__formieStripeWidget || null;
	},
	unmount: async (e) => {
		let t = e.field;
		t.__formieStripeWidget?.paymentElement?.destroy(), t.__formieStripeWidget = null, t.__formieStripeElements = void 0, t.__formieStripeInstance = null, t.__formieStripeLastClientSecret = void 0, t.__formieStripeEvaluateAndRender = null, t.__formieStripeDynamicUnbind?.(), t.__formieStripeDynamicUnbind = null;
	},
	onBeforePayment: async (e) => {
		let { widget: t, services: n, field: r } = e, i = r, a = t;
		if (a?.elements || (i.__formieStripeEvaluateAndRender?.(), a = i.__formieStripeWidget || null), !a?.elements) return !1;
		let o = await a.elements.submit();
		return !o?.error || (n.addError(o.error.message), !1);
	},
	setup: async (e) => {
		let { services: n, options: r } = e, i = r.provider, a = e.target;
		return a.__formieStripeConfirmUnbind?.(), a.__formieStripeConfirmUnbind = n.events.onForm(c, async (e) => {
			try {
				let r = e.detail?.data;
				if (!r?.clientSecret || !t(a) || a.__formieStripeConfirming || a.__formieStripeLastClientSecret === r.clientSecret) return;
				let o = a.__formieStripeElements;
				if (!o) {
					n.addError("Stripe elements not ready for 3DS.");
					return;
				}
				let s = a.__formieStripeInstance, c = i.publishableKey;
				if (!s || !c) {
					n.addError("Stripe is not initialized.");
					return;
				}
				a.__formieStripeConfirming = !0;
				let l = new URL(r.returnUrl || window.location.href);
				l.searchParams.set("origin", window.location.href);
				let u = await (r.type === "setup" ? s.confirmSetup : s.confirmPayment)({
					elements: o,
					clientSecret: r.clientSecret,
					redirect: "if_required",
					confirmParams: { return_url: l.toString() }
				});
				if (u?.error) {
					n.releaseSubmitLoading(), n.addError(u.error.message);
					return;
				}
				r.subscriptionId && n.updateInputs("stripeSubscriptionId", r.subscriptionId);
				let d = u && "paymentIntent" in u ? u.paymentIntent : null, f = u && "setupIntent" in u ? u.setupIntent : null;
				if (d?.id) n.updateInputs("stripePaymentIntentId", d.id);
				else if (f?.id) n.updateInputs("stripePaymentIntentId", f.id);
				else {
					n.releaseSubmitLoading(), n.addError("Stripe confirmation did not return an intent ID.");
					return;
				}
				a.__formieStripeLastClientSecret = r.clientSecret, n.triggerSubmit();
			} catch (e) {
				n.releaseSubmitLoading(), n.addError(e instanceof Error ? e.message : "Unable to confirm Stripe payment.");
			} finally {
				a.__formieStripeConfirming = !1;
			}
		}), { destroy: () => {
			a.__formieStripeConfirmUnbind?.(), a.__formieStripeConfirmUnbind = null;
		} };
	},
	onAfterSubmit: async (e) => {
		let t = e.field;
		e.result?.ok && !e.result?.nextPage && (t.__formieStripeWidget?.paymentElement?.destroy?.(), t.__formieStripeWidget = null, t.__formieStripeElements = void 0, e.services.updateInputs(["stripePaymentIntentId", "stripeSubscriptionId"], ""), t.__formieStripeLastClientSecret = void 0);
	}
});
//#endregion
export { g as stripeModule };
