import { t as e } from "./api-BYe9YPko.js";
import { r as t } from "./async-nPFRNQ06.js";
import { r as n } from "./scripts-CbQ7agX3.js";
//#region src/js/modules/payments/eway.ts
var r = [{
	id: "FORMIE_EWAY_SCRIPT_MIN",
	src: "https://secure.ewaypayments.com/scripts/eCrypt.min.js"
}, {
	id: "FORMIE_EWAY_SCRIPT",
	src: "https://secure.ewaypayments.com/scripts/eCrypt.js"
}];
async function i() {
	let e = null;
	for (let i of r) {
		try {
			return await n("eCrypt", {
				id: i.id,
				src: i.src,
				timeoutMs: 1e4
			}), await t(() => {
				let e = window.eCrypt;
				return e && typeof e.encryptValue == "function" ? e : null;
			}, {
				timeoutMs: 1e4,
				intervalMs: 50
			});
		} catch (t) {
			e = t instanceof Error ? t : /* @__PURE__ */ Error("Unknown eWay script load error.");
		}
		r.forEach(({ id: e }) => {
			document.getElementById(e)?.remove();
		});
	}
	throw e || /* @__PURE__ */ Error("Eway encryption script failed to load.");
}
var a = e({
	id: "eway",
	defaultRequiredInputSuffixes: ["ewayTokenData"],
	load: async (e) => {
		let { provider: t } = e.options;
		return t.cseKey?.trim() ? i() : (console.error("[formie] Missing cseKey for Eway."), null);
	},
	onBeforeAuthorize: async (e) => {
		let { field: t, services: n, provider: r, api: a } = e, o = r.cseKey;
		if (!o?.trim()) return n.addError("Missing cseKey for Eway."), !1;
		let s = a;
		if (!s?.encryptValue) try {
			s = await i();
		} catch (e) {
			return n.addError(e instanceof Error ? e.message : "Eway encryption script failed to load."), !1;
		}
		let c = t.querySelector("[data-eway-card=\"cardholder-name\"]")?.value ?? "", l = t.querySelector("[data-eway-card=\"card-number\"]")?.value ?? "", u = t.querySelector("[data-eway-card=\"expiry-date\"]")?.value ?? "", d = t.querySelector("[data-eway-card=\"security-code\"]")?.value ?? "";
		try {
			let e = {
				cardholderName: c,
				cardNumber: s.encryptValue(l, o),
				expiryDate: u,
				securityCode: s.encryptValue(d, o)
			};
			return n.updateInputs("ewayTokenData", JSON.stringify(e)), !0;
		} catch (e) {
			return n.addError(e instanceof Error ? e.message : "Failed to encrypt card details."), !1;
		}
	},
	onAfterSubmit: async ({ services: e }) => {
		e.updateInputs("ewayTokenData", "");
	}
});
//#endregion
export { a as ewayModule };
