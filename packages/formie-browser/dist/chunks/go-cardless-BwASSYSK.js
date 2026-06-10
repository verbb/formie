import { s as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-CmwLRq_n.js";
//#region src/js/modules/payments/go-cardless.ts
var n = e("go-cardless", "redirect"), r = t({
	id: "go-cardless",
	defaultRequiredInputSuffixes: [],
	load: async () => null,
	setup: async ({ services: e, root: t }) => {
		let r = (t) => {
			let n = t.detail?.data;
			if (!n?.redirectUrl) {
				e.addError("Missing GoCardless redirect URL.");
				return;
			}
			window.location.href = n.redirectUrl;
		};
		return t.addEventListener(n, r), { destroy: () => {
			t.removeEventListener(n, r);
		} };
	}
});
//#endregion
export { r as goCardlessModule };
