import { s as getPaymentProviderActionEventName } from "./event-names-DamGPtXR.js";
import { t as definePaymentModule } from "./api-DE7LfK-R.js";
//#region src/js/modules/payments/go-cardless.ts
/** Event name from PHP addSubmitData – dispatched on form when backend returns GoCardless redirect URL */
var REDIRECT_EVENT = getPaymentProviderActionEventName("go-cardless", "redirect");
var goCardlessModule = definePaymentModule({
	id: "go-cardless",
	defaultRequiredInputSuffixes: [],
	load: async () => null,
	setup: async ({ services, root }) => {
		const handler = (event) => {
			const data = event.detail?.data;
			if (!data?.redirectUrl) {
				services.addError("Missing GoCardless redirect URL.");
				return;
			}
			window.location.href = data.redirectUrl;
		};
		root.addEventListener(REDIRECT_EVENT, handler);
		return { destroy: () => {
			root.removeEventListener(REDIRECT_EVENT, handler);
		} };
	}
});
//#endregion
export { goCardlessModule };
