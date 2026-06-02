import { s as getPaymentProviderActionEventName } from "./event-names-DamGPtXR.js";
import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/payments/paddle.ts
var SCRIPT_ID = "FORMIE_PADDLE_SCRIPT";
var CHECKOUT_EVENT = getPaymentProviderActionEventName("paddle", "initialize");
var paddleModule = definePaymentModule({
	id: "paddle",
	defaultRequiredInputSuffixes: ["paddleCheckoutData"],
	load: async () => null,
	setup: async (ctx) => {
		const { services } = ctx;
		ctx.target;
		const provider = ctx.options.provider;
		const clientSideToken = provider.clientSideToken;
		if (!clientSideToken?.trim()) {
			services.addError("Missing clientSideToken for Paddle.");
			return {};
		}
		let paddle;
		try {
			paddle = await loadScriptAndEnsureGlobal("Paddle", {
				id: SCRIPT_ID,
				src: "https://cdn.paddle.com/paddle/v2/paddle.js"
			});
			paddle.Environment.set(provider.environment || "production");
		} catch (error) {
			services.addError(error instanceof Error ? error.message : "Failed to load Paddle SDK.");
			return {};
		}
		paddle.Initialize({
			token: clientSideToken,
			checkout: { settings: {
				displayMode: "overlay",
				variant: "multi-page"
			} },
			eventCallback: (e) => {
				if (e.name === "checkout.completed") {
					services.updateInputs("paddleCheckoutInit", "");
					services.updateInputs("paddleCheckoutData", JSON.stringify(e.data || {}));
					setTimeout(() => {
						paddle.Checkout.close();
						services.triggerSubmit();
					}, 500);
				}
			}
		});
		const openCheckout = (data) => {
			if (!data?.items) {
				services.addError("Missing Paddle checkout items.");
				return false;
			}
			try {
				services.releaseSubmitLoading();
				paddle.Checkout.open(data);
			} catch (error) {
				services.addError(error instanceof Error ? error.message : "Unable to open Paddle checkout.");
				return false;
			}
			return true;
		};
		const unbindCheckout = services.events.onForm(CHECKOUT_EVENT, ((event) => {
			openCheckout(event.detail?.data);
		}));
		return { destroy: () => {
			unbindCheckout();
		} };
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("paddleCheckoutInit", "true");
		services.updateInputs("paddleCheckoutData", "");
	}
});
//#endregion
export { paddleModule };
