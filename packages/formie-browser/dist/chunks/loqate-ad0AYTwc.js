import { d as m, f as a } from "./index-CZtn5KAB.js";
import { l as u } from "./scripts--tQDv1Kx.js";
const p = "FORMIE_LOQATE_SCRIPT", A = m({
  id: "loqate",
  load: async () => {
    await u("pca", {
      id: p,
      src: "https://services.pcapredict.com/js/address-3.91.min.js",
      async: !0,
      defer: !0
    });
    const e = document.createElement("link");
    return e.href = "https://services.pcapredict.com/css/address-3.91.min.css", e.rel = "stylesheet", e.type = "text/css", document.querySelector(`link[href="${e.href}"]`) || document.body.appendChild(e), window.pca;
  },
  mount: ({ api: e, field: c, services: P, provider: n }) => {
    const i = n.namespace || "", r = n.apiKey || "";
    if (!r)
      throw new Error("Loqate API key is required");
    const t = (d) => {
      if (i)
        return `${i}[${d}]`;
      const o = a(c, d);
      return o?.name ? o.name : o?.id ? o.id : "";
    }, l = t("autoComplete");
    if (!l)
      throw new Error("Loqate: could not find autocomplete input within address field");
    const f = [
      { element: l, field: "", mode: e.fieldMode.SEARCH },
      { element: t("address1"), field: "Line1", mode: e.fieldMode.POPULATE },
      { element: t("address2"), field: "Line2", mode: e.fieldMode.POPULATE },
      { element: t("address3"), field: "Line3", mode: e.fieldMode.POPULATE },
      { element: t("city"), field: "City", mode: e.fieldMode.POPULATE },
      { element: t("state"), field: "Province", mode: e.fieldMode.POPULATE },
      { element: t("zip"), field: "PostalCode", mode: e.fieldMode.POPULATE },
      { element: t("country"), field: "CountryName", mode: e.fieldMode.COUNTRY }
    ].filter((d) => d.element), s = new e.Address(f, {
      key: r,
      simulateReactEvents: !0,
      ...n.reconfigurableOptions || {}
    });
    return typeof s.load == "function" && s.load(), s;
  }
});
export {
  A as loqateModule
};
