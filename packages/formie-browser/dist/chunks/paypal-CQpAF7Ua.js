import { t as e } from "./api-DqpfkZWL.js";
import { r as t } from "./scripts-CbQ7agX3.js";
import { t as n } from "./styles-BfoIZwJp.js";
//#endregion
//#region src/js/modules/payments/paypal.ts
n("paypal", ["@layer formie-theme{.formie-paypal-button{border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm);background:var(--formie-color-surface);box-sizing:border-box;min-height:var(--formie-control-height);padding:var(--formie-space-2)}}"]);
var r = "FORMIE_PAYPAL_SCRIPT";
function i(e) {
	if (!e) return "";
	let t = (e.purchase_units || [])[0]?.payments || {}, n = t.authorizations || [], r = t.captures || [];
	return String(n[0]?.id || r[0]?.id || "").trim();
}
function a(e, t) {
	e?.close && e.close(), t.removeAttribute("data-formie-paypal-rendered"), t.innerHTML = "";
}
function o(e, t) {
	return `https://www.paypal.com/sdk/js?${[
		"intent=authorize",
		`currency=${encodeURIComponent(t)}`,
		`client-id=${encodeURIComponent(e)}`
	].join("&")}`;
}
var s = e({
	id: "paypal",
	defaultRequiredInputSuffixes: ["paypalOrderId", "paypalAuthId"],
	load: async (e) => {
		let { provider: n } = e.options, i = n.clientId;
		return i?.trim() ? (await t("paypal", {
			id: r,
			src: o(i, n.currency || "AUD")
		}), window.paypal) : (console.error("[formie] Missing clientId for PayPal."), null);
	},
	mount: async (e) => {
		let { api: t, field: n, services: r, options: o, provider: s } = e, c = n.querySelector("[data-formie-paypal-button]");
		if (!c || !t || c.getAttribute("data-formie-paypal-rendered") === "true") return null;
		c.innerHTML = "";
		let l = !!s.useSandbox, u = r.resolveCurrency({
			value: s.currency,
			defaultCurrency: "AUD"
		});
		if (!u.ok) return r.addError("error" in u ? u.error : "Invalid PayPal currency."), null;
		let d = u.value, f = {
			layout: s.buttonLayout || "vertical",
			color: s.buttonColor || "gold",
			shape: s.buttonShape || "rect",
			label: s.buttonLabel || "paypal",
			width: s.buttonWidth || 250,
			height: s.buttonHeight || 35
		};
		f.layout === "horizontal" && (f.tagline = s.buttonTagline ?? !0);
		let p = null, m = {
			env: l ? "sandbox" : "production",
			style: f,
			createOrder: (e, t) => {
				r.removeError();
				let n = r.resolveAmount({
					type: s.amountType,
					fixed: s.amountFixed,
					variable: s.amountVariable
				});
				if (!n.ok) {
					let e = "error" in n ? n.error : "Invalid PayPal amount.";
					throw r.addError(e), Error(e);
				}
				return t.order.create({
					intent: "AUTHORIZE",
					application_context: { user_action: "CONTINUE" },
					purchase_units: [{ amount: {
						currency_code: d,
						value: String(n.value)
					} }]
				});
			},
			onError: (e) => {
				r.addError(e?.message || "PayPal error.");
			},
			onApprove: async (e, t) => {
				try {
					let n = i(await t.order.authorize());
					r.updateInputs("paypalOrderId", e.orderID), r.updateInputs("paypalAuthId", n || ""), n ? r.addSuccess("Payment authorized. Finalize the form to complete payment.") : r.addSuccess("PayPal approval received. Finalizing payment on submit."), a(p, c);
				} catch {
					r.addError("Unable to authorize payment. Please try again.");
				}
			}
		}, h = t.Buttons(m);
		return c.setAttribute("data-formie-paypal-rendered", "true"), p = h.render(c), p;
	},
	unmount: async (e) => {
		e.widget?.close && e.widget.close();
		let t = e.field.querySelector("[data-formie-paypal-button]");
		t && (t.removeAttribute("data-formie-paypal-rendered"), t.innerHTML = "");
	},
	onAfterSubmit: async ({ services: e, result: t }) => (e.updateInputs(["paypalOrderId", "paypalAuthId"], ""), e.removeSuccess(), e.removeError(), !t?.ok || t?.nextPage ? { remount: !0 } : {})
});
//#endregion
export { s as paypalModule };
