import { s as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-DslEDLxm.js";
//#region src/js/modules/payments/mollie.ts
var n = e("mollie", "redirect"), r = t({
	id: "mollie",
	defaultRequiredInputSuffixes: [],
	load: async () => null,
	setup: async ({ services: e, root: t }) => {
		let r = (t) => {
			let n = t.detail?.data;
			if (!n?.checkoutUrl) {
				e.addError("Missing Mollie checkout URL.");
				return;
			}
			window.location.href = n.checkoutUrl;
		};
		return t.addEventListener(n, r), { destroy: () => {
			t.removeEventListener(n, r);
		} };
	}
});
//#endregion
export { r as mollieModule };
