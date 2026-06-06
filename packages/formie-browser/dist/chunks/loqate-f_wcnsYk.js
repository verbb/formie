import { d as p, A as d } from "./index-BneHZL41.js";
import { l as y } from "./scripts-CRvKwopA.js";
const E = "FORMIE_LOQATE_SCRIPT", C = p({
  id: "loqate",
  load: async () => {
    await y("pca", {
      id: E,
      src: "https://services.pcapredict.com/js/address-3.91.min.js",
      async: !0,
      defer: !0
    });
    const e = document.createElement("link");
    return e.href = "https://services.pcapredict.com/css/address-3.91.min.css", e.rel = "stylesheet", e.type = "text/css", document.querySelector(`link[href="${e.href}"]`) || document.body.appendChild(e), window.pca;
  },
  mount: ({ api: e, field: m, services: P, provider: n }) => {
    const l = n.namespace || "", i = n.apiKey || "";
    if (!i)
      throw new Error("Loqate API key is required");
    const f = {
      autoComplete: d.autoComplete,
      address1: d.address1,
      address2: d.address2,
      address3: d.address3,
      city: d.city,
      state: d.state,
      zip: d.zip,
      country: d.country
    }, t = (o) => {
      if (l)
        return `${l}[${o}]`;
      const a = f[o], s = a ? m.querySelector(a) : null;
      return s?.name ? s.name : s?.id ? s.id : "";
    }, c = t("autoComplete");
    if (!c)
      throw new Error("Loqate: could not find autocomplete input within address field");
    const u = [
      { element: c, field: "", mode: e.fieldMode.SEARCH },
      { element: t("address1"), field: "Line1", mode: e.fieldMode.POPULATE },
      { element: t("address2"), field: "Line2", mode: e.fieldMode.POPULATE },
      { element: t("address3"), field: "Line3", mode: e.fieldMode.POPULATE },
      { element: t("city"), field: "City", mode: e.fieldMode.POPULATE },
      { element: t("state"), field: "Province", mode: e.fieldMode.POPULATE },
      { element: t("zip"), field: "PostalCode", mode: e.fieldMode.POPULATE },
      { element: t("country"), field: "CountryName", mode: e.fieldMode.COUNTRY }
    ].filter((o) => o.element), r = new e.Address(u, {
      key: i,
      simulateReactEvents: !0,
      ...n.reconfigurableOptions || {}
    });
    return typeof r.load == "function" && r.load(), r;
  }
});
export {
  C as loqateModule
};
