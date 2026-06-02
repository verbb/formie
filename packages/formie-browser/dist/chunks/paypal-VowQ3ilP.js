import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import paypalCss from "#theme/integrations/_paypal.css?inline";
//#region src/js/modules/payments/paypal.ts
ensureModuleStyles("paypal", [paypalCss]);
var SCRIPT_ID = "FORMIE_PAYPAL_SCRIPT";
function extractAuthIdFromAuthorization(result) {
	if (!result) return "";
	const payments = (result.purchase_units || [])[0]?.payments || {};
	const authorizations = payments.authorizations || [];
	const captures = payments.captures || [];
	return String(authorizations[0]?.id || captures[0]?.id || "").trim();
}
function getScriptUrl(clientId, currency) {
	return `https://www.paypal.com/sdk/js?${[
		"intent=authorize",
		`currency=${encodeURIComponent(currency)}`,
		`client-id=${encodeURIComponent(clientId)}`
	].join("&")}`;
}
var paypalModule = definePaymentModule({
	id: "paypal",
	defaultRequiredInputSuffixes: ["paypalOrderId", "paypalAuthId"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		const clientId = provider.clientId;
		if (!clientId?.trim()) {
			console.error("[formie] Missing clientId for PayPal.");
			return null;
		}
		await loadScriptAndEnsureGlobal("paypal", {
			id: SCRIPT_ID,
			src: getScriptUrl(clientId, provider.currency || "AUD")
		});
		return window.paypal;
	},
	mount: async (args) => {
		const { api, field, services, options, provider } = args;
		const placeholder = field.querySelector("[data-formie-paypal-button]");
		if (!placeholder || !api) return null;
		if (placeholder.getAttribute("data-formie-paypal-rendered") === "true") return null;
		placeholder.innerHTML = "";
		const useSandbox = Boolean(provider.useSandbox);
		const currencyResult = services.resolveCurrency({
			value: provider.currency,
			defaultCurrency: "AUD"
		});
		if (!currencyResult.ok) {
			services.addError("error" in currencyResult ? currencyResult.error : "Invalid PayPal currency.");
			return null;
		}
		const currency = currencyResult.value;
		const style = {
			layout: provider.buttonLayout || "vertical",
			color: provider.buttonColor || "gold",
			shape: provider.buttonShape || "rect",
			label: provider.buttonLabel || "paypal",
			width: provider.buttonWidth || 250,
			height: provider.buttonHeight || 35
		};
		if (style.layout === "horizontal") style.tagline = provider.buttonTagline ?? true;
		const paypalOptions = {
			env: useSandbox ? "sandbox" : "production",
			style,
			createOrder: (_data, actions) => {
				services.removeError();
				const amountResult = services.resolveAmount({
					type: provider.amountType,
					fixed: provider.amountFixed,
					variable: provider.amountVariable
				});
				if (!amountResult.ok) {
					const errorMessage = "error" in amountResult ? amountResult.error : "Invalid PayPal amount.";
					services.addError(errorMessage);
					throw new Error(errorMessage);
				}
				return actions.order.create({
					intent: "AUTHORIZE",
					application_context: { user_action: "CONTINUE" },
					purchase_units: [{ amount: {
						currency_code: currency,
						value: String(amountResult.value)
					} }]
				});
			},
			onError: (err) => {
				services.addError(err?.message || "PayPal error.");
			},
			onApprove: async (data, actions) => {
				try {
					const authId = extractAuthIdFromAuthorization(await actions.order.authorize());
					services.updateInputs("paypalOrderId", data.orderID);
					services.updateInputs("paypalAuthId", authId || "");
					if (authId) services.addSuccess("Payment authorized. Finalize the form to complete payment.");
					else services.addSuccess("PayPal approval received. Finalizing payment on submit.");
				} catch {
					services.addError("Unable to authorize payment. Please try again.");
				}
			}
		};
		const buttons = api.Buttons(paypalOptions);
		placeholder.setAttribute("data-formie-paypal-rendered", "true");
		return buttons.render(placeholder);
	},
	unmount: async (args) => {
		if (args.widget?.close) args.widget.close();
		const placeholder = args.field.querySelector("[data-formie-paypal-button]");
		if (placeholder) {
			placeholder.removeAttribute("data-formie-paypal-rendered");
			placeholder.innerHTML = "";
		}
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs(["paypalOrderId", "paypalAuthId"], "");
		services.removeSuccess();
		services.removeError();
	}
});
//#endregion
export { paypalModule };
