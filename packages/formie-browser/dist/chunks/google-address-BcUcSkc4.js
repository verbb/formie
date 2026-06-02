import { d as v, g as C } from "./index-MuyEvWaf.js";
import { e as E, a as L } from "./scripts-BlHNQs0M.js";
const w = "FORMIE_GOOGLE_ADDRESS_SCRIPT", m = "formieGoogleMapsReady";
let f = null;
async function S(t) {
  const e = window, r = e.google;
  if (typeof r < "u" && r !== null) {
    const s = r.maps;
    if (s?.places?.PlaceAutocompleteElement)
      return r;
    const i = s;
    return typeof i?.importLibrary == "function" && await i.importLibrary("places"), r;
  }
  if (f)
    return f;
  if (document.getElementById(w)) {
    const s = await E("google", 1e4), i = s?.maps;
    return typeof i?.importLibrary == "function" && await i.importLibrary("places"), s;
  }
  const o = new URL("https://maps.googleapis.com/maps/api/js");
  o.searchParams.set("key", t), o.searchParams.set("loading", "async"), o.searchParams.set("libraries", "places"), o.searchParams.set("callback", m), f = (async () => {
    const s = new Promise((c, n) => {
      const u = setTimeout(() => {
        e[m] && (delete e[m], n(new Error("Google Maps API load timeout")));
      }, 15e3);
      e[m] = () => {
        clearTimeout(u), delete e[m], c(e.google);
      };
    });
    await L({
      id: w,
      src: o.toString(),
      async: !0,
      defer: !0
    });
    const i = await s, a = i?.maps;
    return typeof a?.importLibrary == "function" && await a.importLibrary("places"), i;
  })();
  try {
    return await f;
  } catch (s) {
    throw f = null, s;
  }
}
function b(t, e) {
  e.formattedAddress && t.input.setValue("autoComplete", e.formattedAddress), e.address1 && t.input.setValue("address1", e.address1), e.city !== void 0 && t.input.setValue("city", e.city), e.state !== void 0 && t.input.setValue("state", e.state), e.zip !== void 0 && t.input.setValue("zip", e.zip), e.country !== void 0 && t.input.setValue("country", e.country);
}
function T() {
  return {
    subpremise: "shortText",
    street_number: "shortText",
    route: "longText",
    postal_town: "longText",
    locality: "longText",
    administrative_area_level_1: "shortText",
    country: "shortText",
    postal_code: "shortText"
  };
}
function A(t) {
  const e = T(), r = {};
  for (const d of t) {
    const o = d.types?.[0];
    if (!o || !e[o]) continue;
    const s = e[o], i = d[s] ?? d.short_text ?? d.long_text ?? "";
    r[o] = i;
  }
  return r;
}
function P(t) {
  let e = "";
  return (t.street_number || t.route) && (e = [t.street_number, t.route].filter(Boolean).join(" "), t.subpremise && (e = `${t.subpremise}/${e}`)), {
    address1: e,
    city: t.locality || t.postal_town || "",
    state: t.administrative_area_level_1 || "",
    zip: t.postal_code || "",
    country: t.country || ""
  };
}
const I = v({
  id: "google-address",
  load: async ({ options: t }) => {
    const e = t.provider.apiKey;
    if (!e)
      throw new Error("Google Places API key is required");
    return S(e);
  },
  mount: async ({ api: t, field: e, services: r, provider: d }) => {
    const o = r.input.getAutocomplete(), s = t?.maps?.places?.PlaceAutocompleteElement;
    if (!o || typeof s != "function")
      return console.warn("[formie] Google Places API not ready; address autocomplete skipped."), null;
    const i = { types: ["geocode"], ...d.options || {} }, a = new s(i), c = window.getComputedStyle(o).height;
    a.style.height = c, a.style.boxSizing = "border-box";
    let n = o.parentElement;
    n?.classList.contains("formie-autocomplete-wrapper") || (n = document.createElement("div"), n.classList.add("formie-autocomplete-wrapper"), o.parentNode?.insertBefore(n, o), n.appendChild(o));
    const u = o.value;
    if (u) {
      const l = document.createElement("div");
      l.classList.add("formie-autocomplete-placeholder"), l.textContent = u, n.style.position = "relative", l.style.cssText = `
                position: absolute; left: 0; top: 0; height: ${c};
                line-height: ${c}; width: 100%; padding: 0 2.5rem;
                pointer-events: none; color: #6B7280; font-size: 14px; z-index: 1;
            `, n.appendChild(l), a.addEventListener("focusin", () => {
        l.style.display = "none";
      }), a.addEventListener("focusout", () => {
        o.value && (l.style.display = "");
      });
    }
    n.replaceChild(a, o), o.type = "hidden", o.name = o.getAttribute("name") || "", n.appendChild(o);
    const y = async (l) => {
      const h = l.placePrediction;
      if (!h) return;
      const p = await h.toPlace();
      if (await p.fetchFields({ fields: ["addressComponents", "formattedAddress"] }), !p.addressComponents) return;
      const _ = A(
        p.addressComponents
      ), x = P(_);
      b(r, {
        ...x,
        formattedAddress: p.formattedAddress
      }), e.dispatchEvent(
        new CustomEvent(C("google", "populate"), {
          bubbles: !0,
          detail: {
            addressProvider: "google",
            place: p,
            formattedAddress: p.formattedAddress,
            addressComponents: p.addressComponents
          }
        })
      );
    };
    return a.addEventListener("gmp-select", y), a;
  },
  onCurrentLocation: async (t, { field: e, services: r }) => {
    const { latitude: d, longitude: o } = t.coords, s = r.form, i = s?.action || window.location.href, a = e.getAttribute("data-formie-field-handle")?.trim(), c = s?.querySelector('[name="handle"]')?.value?.trim();
    if (!(!c || !a))
      try {
        const n = new FormData();
        n.append("action", "formie/address/google-places-geocode"), n.append("latlng", `${d},${o}`), n.append("handle", c), n.append("fieldHandle", a);
        const y = await (await fetch(i, {
          method: "POST",
          body: n,
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json"
          }
        })).json();
        if (y?.results?.[0]?.address_components) {
          const l = A(
            y.results[0].address_components
          ), g = P(l);
          b(r, g);
        }
      } catch {
      }
  }
});
export {
  I as googleAddressModule
};
