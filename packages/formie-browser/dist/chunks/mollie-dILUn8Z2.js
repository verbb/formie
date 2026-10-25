import { s as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-e9FPXfJj.js";
//#region src/js/modules/payments/mollie.ts
var n = e("mollie", "redirect"), r = t({
	moduleId: "formie:mollie",
	defaultRequiredInputSuffixes: [],
	load: async () => null,
	setup: async ({ services: e, root: t }) => {
		let r = (t) => {
			let n = t.detail?.data;
			n?.checkoutUrl ? window.location.href = n.checkoutUrl : e.addError("Missing Mollie checkout URL.");
		};
		return t.addEventListener(n, r), { destroy: () => {
			t.removeEventListener(n, r);
		} };
	}
});
//#endregion
export { r as mollieModule };
