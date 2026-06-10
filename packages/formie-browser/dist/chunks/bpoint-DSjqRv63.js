import { t as e } from "./api-CmwLRq_n.js";
//#region src/js/modules/payments/bpoint.ts
var t = e({
	id: "bpoint",
	defaultRequiredInputSuffixes: ["bpointToken"],
	load: async () => null,
	onBeforeAuthorize: async ({ field: e, services: t }) => {
		if ((e.querySelector("input[name$=\"[bpointToken]\"]")?.value || "").trim() !== "") return !0;
		let n = e.querySelector("[data-bpoint-card=\"cardholder-name\"]")?.value?.trim() || "", r = e.querySelector("[data-bpoint-card=\"card-number\"]")?.value?.replace(/\s+/g, "") || "", i = e.querySelector("[data-bpoint-card=\"expiry-date\"]")?.value || "", a = e.querySelector("[data-bpoint-card=\"security-code\"]")?.value?.trim() || "", o = (i.match(/\d/g) || []).join("").slice(0, 4);
		if (!r || o.length !== 4 || !a) return t.addError("Please provide valid card details to continue."), !1;
		let s = {
			cardholderName: n,
			cardNumber: r,
			expiryDate: o,
			cvn: a
		};
		return t.updateInputs("bpointToken", JSON.stringify(s)), !0;
	},
	onAfterSubmit: async ({ services: e }) => {
		e.updateInputs("bpointToken", "");
	}
});
//#endregion
export { t as bpointModule };
