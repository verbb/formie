import { n as ADDRESS_SELECTORS, t as defineAddressModule } from "./api-CbqEMQT5.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/address/loqate.ts
var SCRIPT_ID = "FORMIE_LOQATE_SCRIPT";
var loqateModule = defineAddressModule({
	id: "loqate",
	load: async () => {
		await loadScriptAndEnsureGlobal("pca", {
			id: SCRIPT_ID,
			src: "https://services.pcapredict.com/js/address-3.91.min.js",
			async: true,
			defer: true
		});
		const link = document.createElement("link");
		link.href = "https://services.pcapredict.com/css/address-3.91.min.css";
		link.rel = "stylesheet";
		link.type = "text/css";
		if (!document.querySelector(`link[href="${link.href}"]`)) document.body.appendChild(link);
		return window.pca;
	},
	mount: ({ api, field, services, provider }) => {
		const namespace = provider.namespace || "";
		const apiKey = provider.apiKey || "";
		if (!apiKey) throw new Error("Loqate API key is required");
		const handleToSelector = {
			autoComplete: ADDRESS_SELECTORS.autoComplete,
			address1: ADDRESS_SELECTORS.address1,
			address2: ADDRESS_SELECTORS.address2,
			address3: ADDRESS_SELECTORS.address3,
			city: ADDRESS_SELECTORS.city,
			state: ADDRESS_SELECTORS.state,
			zip: ADDRESS_SELECTORS.zip,
			country: ADDRESS_SELECTORS.country
		};
		const getLoqateElementRef = (handle) => {
			if (namespace) return `${namespace}[${handle}]`;
			const cssSelector = handleToSelector[handle];
			const el = cssSelector ? field.querySelector(cssSelector) : null;
			if (el?.name) return el.name;
			if (el?.id) return el.id;
			return "";
		};
		const autoCompleteRef = getLoqateElementRef("autoComplete");
		if (!autoCompleteRef) throw new Error("Loqate: could not find autocomplete input within address field");
		const fields = [
			{
				element: autoCompleteRef,
				field: "",
				mode: api.fieldMode.SEARCH
			},
			{
				element: getLoqateElementRef("address1"),
				field: "Line1",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("address2"),
				field: "Line2",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("address3"),
				field: "Line3",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("city"),
				field: "City",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("state"),
				field: "Province",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("zip"),
				field: "PostalCode",
				mode: api.fieldMode.POPULATE
			},
			{
				element: getLoqateElementRef("country"),
				field: "CountryName",
				mode: api.fieldMode.COUNTRY
			}
		].filter((f) => f.element);
		const control = new api.Address(fields, {
			key: apiKey,
			simulateReactEvents: true,
			...provider.reconfigurableOptions || {}
		});
		if (typeof control.load === "function") control.load();
		return control;
	}
});
//#endregion
export { loqateModule };
