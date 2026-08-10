import { n as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-DvlROAFO.js";
import { r as n } from "./scripts-CbQ7agX3.js";
//#region src/js/modules/address/address-finder.ts
var r = "FORMIE_ADDRESS_FINDER_SCRIPT", i = t({
	id: "address-finder",
	load: async () => n("AddressFinder", {
		id: r,
		src: "https://api.addressfinder.io/assets/v3/widget.js",
		async: !0,
		defer: !0
	}),
	mount: ({ api: t, field: n, services: r, provider: i }) => {
		let a = r.input.getAutocomplete();
		if (!a || t === void 0 || !t.Widget) throw Error("AddressFinder API not ready");
		let o = i.apiKey || "", s = i.countryCode || "au", c = new t.Widget(a, o, s, i.widgetOptions);
		return c.on("result:select", (t, i) => {
			i.address_line_2 ? (r.input.setValue("address1", i.address_line_2), r.input.setValue("address2", i.address_line_1)) : (r.input.setValue("address1", i.address_line_1 || ""), r.input.setValue("address2", "")), r.input.setValue("city", i.locality_name || ""), r.input.setValue("zip", i.postcode || ""), r.input.setValue("state", i.state_territory || ""), r.input.setValue("country", s), n.dispatchEvent(new CustomEvent(e("address-finder", "populate"), {
				bubbles: !0,
				detail: {
					addressProvider: "address-finder",
					fullAddress: t,
					metaData: i
				}
			}));
		}), c;
	}
});
//#endregion
export { i as addressFinderModule };
