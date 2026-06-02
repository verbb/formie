import { t as definePaymentModule } from "./api-DE7LfK-R.js";
//#region src/js/modules/payments/bpoint.ts
var bpointModule = definePaymentModule({
	id: "bpoint",
	defaultRequiredInputSuffixes: ["bpointToken"],
	load: async () => {
		return null;
	},
	onBeforeAuthorize: async ({ field, services }) => {
		if ((field.querySelector("input[name$=\"[bpointToken]\"]")?.value || "").trim() !== "") return true;
		const cardholderName = field.querySelector("[data-bpoint-card=\"cardholder-name\"]")?.value?.trim() || "";
		const cardNumber = field.querySelector("[data-bpoint-card=\"card-number\"]")?.value?.replace(/\s+/g, "") || "";
		const expiryRaw = field.querySelector("[data-bpoint-card=\"expiry-date\"]")?.value || "";
		const cvn = field.querySelector("[data-bpoint-card=\"security-code\"]")?.value?.trim() || "";
		const expiryDate = (expiryRaw.match(/\d/g) || []).join("").slice(0, 4);
		if (!cardNumber || expiryDate.length !== 4 || !cvn) {
			services.addError("Please provide valid card details to continue.");
			return false;
		}
		const cardData = {
			cardholderName,
			cardNumber,
			expiryDate,
			cvn
		};
		services.updateInputs("bpointToken", JSON.stringify(cardData));
		return true;
	},
	onAfterSubmit: async ({ services }) => {
		services.updateInputs("bpointToken", "");
	}
});
//#endregion
export { bpointModule };
