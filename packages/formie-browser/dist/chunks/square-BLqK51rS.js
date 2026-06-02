import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/payments/square.ts
var SCRIPT_ID = "FORMIE_SQUARE_SCRIPT";
var squareModule = definePaymentModule({
	id: "square",
	defaultRequiredInputSuffixes: ["squarePaymentId"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		const applicationId = provider.applicationId;
		const locationId = provider.locationId;
		if (!applicationId?.trim() || !locationId?.trim()) {
			console.error("[formie] Missing applicationId or locationId for Square.");
			return null;
		}
		await loadScriptAndEnsureGlobal("Square", {
			id: SCRIPT_ID,
			src: provider.environment === "sandbox" ? "https://sandbox.web.squarecdn.com/v1/square.js" : "https://web.squarecdn.com/v1/square.js"
		});
		return window.Square;
	},
	mount: async (args) => {
		const { api, field, services, options } = args;
		const provider = options.provider;
		const placeholder = field.querySelector("[data-formie-square-button]");
		if (!placeholder || !api) return null;
		try {
			const card = await api.payments(provider.applicationId || "", provider.locationId || "").card();
			await card.attach(placeholder);
			return card;
		} catch (error) {
			services.addError(error instanceof Error ? error.message : "Unable to initialize payment.");
			return null;
		}
	},
	unmount: async () => {},
	onBeforeAuthorize: async (args) => {
		const { widget, services } = args;
		if (!widget) {
			services.addError("Square card is not ready.");
			return false;
		}
		try {
			const result = await widget.tokenize();
			if (result.status === "OK" && result.token) {
				services.updateInputs("squarePaymentId", result.token);
				return true;
			}
			services.addError(result.errors?.[0]?.message || "Tokenization failed.");
			return false;
		} catch {
			services.addError("Payment tokenization failed. Please try again.");
			return false;
		}
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("squarePaymentId", "");
	}
});
//#endregion
export { squareModule };
