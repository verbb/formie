import { n as getAddressProviderEventName } from "./event-names-DamGPtXR.js";
import { t as defineAddressModule } from "./api-CbqEMQT5.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/address/address-finder.ts
var SCRIPT_ID = "FORMIE_ADDRESS_FINDER_SCRIPT";
var addressFinderModule = defineAddressModule({
	id: "address-finder",
	load: async () => {
		return loadScriptAndEnsureGlobal("AddressFinder", {
			id: SCRIPT_ID,
			src: "https://api.addressfinder.io/assets/v3/widget.js",
			async: true,
			defer: true
		});
	},
	mount: ({ api, field, services, provider }) => {
		const input = services.input.getAutocomplete();
		if (!input || typeof api === "undefined" || !api.Widget) throw new Error("AddressFinder API not ready");
		const apiKey = provider.apiKey || "";
		const countryCode = provider.countryCode || "au";
		const widget = new api.Widget(input, apiKey, countryCode, provider.widgetOptions);
		widget.on("result:select", (fullAddress, metaData) => {
			if (metaData.address_line_2) {
				services.input.setValue("address1", metaData.address_line_2);
				services.input.setValue("address2", metaData.address_line_1);
			} else {
				services.input.setValue("address1", metaData.address_line_1 || "");
				services.input.setValue("address2", "");
			}
			services.input.setValue("city", metaData.locality_name || "");
			services.input.setValue("zip", metaData.postcode || "");
			services.input.setValue("state", metaData.state_territory || "");
			services.input.setValue("country", countryCode);
			field.dispatchEvent(new CustomEvent(getAddressProviderEventName("address-finder", "populate"), {
				bubbles: true,
				detail: {
					addressProvider: "address-finder",
					fullAddress,
					metaData
				}
			}));
		});
		return widget;
	}
});
//#endregion
export { addressFinderModule };
