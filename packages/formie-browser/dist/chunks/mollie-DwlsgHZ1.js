import { s as getPaymentProviderActionEventName } from "./event-names-DamGPtXR.js";
import { t as definePaymentModule } from "./api-DE7LfK-R.js";
//#region src/js/modules/payments/mollie.ts
/** Event name from PHP addSubmitData – dispatched on form when backend returns Mollie checkout URL */
var REDIRECT_EVENT = getPaymentProviderActionEventName("mollie", "redirect");
var mollieModule = definePaymentModule({
	id: "mollie",
	defaultRequiredInputSuffixes: [],
	load: async () => null,
	setup: async ({ services, root }) => {
		const handler = (event) => {
			const data = event.detail?.data;
			if (!data?.checkoutUrl) {
				services.addError("Missing Mollie checkout URL.");
				return;
			}
			window.location.href = data.checkoutUrl;
		};
		root.addEventListener(REDIRECT_EVENT, handler);
		return { destroy: () => {
			root.removeEventListener(REDIRECT_EVENT, handler);
		} };
	}
});
//#endregion
export { mollieModule };
