import { t as definePaymentModule } from "./api-DE7LfK-R.js";
import { r as waitFor } from "./async-B3DUf1GZ.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/payments/eway.ts
var SCRIPT_SOURCES = [{
	id: "FORMIE_EWAY_SCRIPT_MIN",
	src: "https://secure.ewaypayments.com/scripts/eCrypt.min.js"
}, {
	id: "FORMIE_EWAY_SCRIPT",
	src: "https://secure.ewaypayments.com/scripts/eCrypt.js"
}];
async function ensureEwayEncryptApi() {
	let lastError = null;
	for (const source of SCRIPT_SOURCES) {
		try {
			await loadScriptAndEnsureGlobal("eCrypt", {
				id: source.id,
				src: source.src,
				timeoutMs: 1e4
			});
			return await waitFor(() => {
				const candidate = window.eCrypt;
				if (candidate && typeof candidate.encryptValue === "function") return candidate;
				return null;
			}, {
				timeoutMs: 1e4,
				intervalMs: 50
			});
		} catch (error) {
			lastError = error instanceof Error ? error : /* @__PURE__ */ new Error("Unknown eWay script load error.");
		}
		SCRIPT_SOURCES.forEach(({ id }) => {
			document.getElementById(id)?.remove();
		});
	}
	throw lastError || /* @__PURE__ */ new Error("Eway encryption script failed to load.");
}
var ewayModule = definePaymentModule({
	id: "eway",
	defaultRequiredInputSuffixes: ["ewayTokenData"],
	load: async (ctx) => {
		const { provider } = ctx.options;
		if (!provider.cseKey?.trim()) {
			console.error("[formie] Missing cseKey for Eway.");
			return null;
		}
		return ensureEwayEncryptApi();
	},
	onBeforeAuthorize: async (args) => {
		const { field, services, provider, api } = args;
		const cseKey = provider.cseKey;
		if (!cseKey?.trim()) {
			services.addError("Missing cseKey for Eway.");
			return false;
		}
		let eCrypt = api;
		if (!eCrypt?.encryptValue) try {
			eCrypt = await ensureEwayEncryptApi();
		} catch (error) {
			services.addError(error instanceof Error ? error.message : "Eway encryption script failed to load.");
			return false;
		}
		const cardholderName = field.querySelector("[data-eway-card=\"cardholder-name\"]")?.value ?? "";
		const cardNumber = field.querySelector("[data-eway-card=\"card-number\"]")?.value ?? "";
		const expiryDate = field.querySelector("[data-eway-card=\"expiry-date\"]")?.value ?? "";
		const securityCode = field.querySelector("[data-eway-card=\"security-code\"]")?.value ?? "";
		try {
			const cardDetails = {
				cardholderName,
				cardNumber: eCrypt.encryptValue(cardNumber, cseKey),
				expiryDate,
				securityCode: eCrypt.encryptValue(securityCode, cseKey)
			};
			services.updateInputs("ewayTokenData", JSON.stringify(cardDetails));
			return true;
		} catch (e) {
			services.addError(e instanceof Error ? e.message : "Failed to encrypt card details.");
			return false;
		}
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("ewayTokenData", "");
	}
});
//#endregion
export { ewayModule };
