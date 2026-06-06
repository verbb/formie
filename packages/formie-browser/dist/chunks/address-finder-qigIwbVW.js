import { d as p, g as a } from "./index-Cmikarpm.js";
import { l as _ } from "./scripts-D7TV7mth.js";
const f = "FORMIE_ADDRESS_FINDER_SCRIPT", g = p({
  id: "address-finder",
  load: async () => _("AddressFinder", {
    id: f,
    src: "https://api.addressfinder.io/assets/v3/widget.js",
    async: !0,
    defer: !0
  }),
  mount: ({ api: t, field: u, services: e, provider: s }) => {
    const n = e.input.getAutocomplete();
    if (!n || typeof t > "u" || !t.Widget)
      throw new Error("AddressFinder API not ready");
    const i = s.apiKey || "", r = s.countryCode || "au", o = new t.Widget(
      n,
      i,
      r,
      s.widgetOptions
    );
    return o.on("result:select", (l, d) => {
      d.address_line_2 ? (e.input.setValue("address1", d.address_line_2), e.input.setValue("address2", d.address_line_1)) : (e.input.setValue("address1", d.address_line_1 || ""), e.input.setValue("address2", "")), e.input.setValue("city", d.locality_name || ""), e.input.setValue("zip", d.postcode || ""), e.input.setValue("state", d.state_territory || ""), e.input.setValue("country", r), u.dispatchEvent(
        new CustomEvent(a("address-finder", "populate"), {
          bubbles: !0,
          detail: {
            addressProvider: "address-finder",
            fullAddress: l,
            metaData: d
          }
        })
      );
    }), o;
  }
});
export {
  g as addressFinderModule
};
