import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import paywayCss from "#theme/integrations/_payway.css?inline";
//#region src/js/modules/payments/payway.ts
ensureModuleStyles("payway", [paywayCss]);
var SCRIPT_ID = "FORMIE_PAYWAY_SCRIPT";
var paywayModule = definePaymentModule({
	id: "payway",
	defaultRequiredInputSuffixes: ["paywayTokenId"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		if (!provider.publishableKey?.trim()) {
			console.error("[formie] Missing publishableKey for PayWay.");
			return null;
		}
		await loadScriptAndEnsureGlobal("payway", {
			id: SCRIPT_ID,
			src: "https://api.payway.com.au/rest/v1/payway.js"
		});
		return null;
	},
	mount: async (args) => {
		const { field, services, options } = args;
		const provider = options.provider;
		if (!field.querySelector("[data-formie-payway-button]")) return null;
		const payway = window.payway;
		if (!payway) return null;
		return new Promise((resolve) => {
			payway.createCreditCardFrame({
				layout: "wide",
				publishableApiKey: provider.publishableKey || "",
				tokenMode: "callback"
			}, (err, frame) => {
				if (err || !frame) {
					services.addError(err?.message || "PayWay frame failed to load.");
					resolve(null);
					return;
				}
				resolve(frame);
			});
		});
	},
	unmount: async (args) => {
		args.widget?.destroy();
	},
	onBeforeAuthorize: async (args) => {
		const { widget, services } = args;
		if (!widget) {
			services.addError("PayWay card frame is not ready.");
			return false;
		}
		return new Promise((resolve) => {
			widget.getToken((err, data) => {
				if (err) {
					services.addError(err.message);
					resolve(false);
					return;
				}
				if (data?.singleUseTokenId) {
					services.updateInputs("paywayTokenId", data.singleUseTokenId);
					resolve(true);
				} else {
					services.addError("Tokenization failed.");
					resolve(false);
				}
			});
		});
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("paywayTokenId", "");
	}
});
//#endregion
export { paywayModule };
