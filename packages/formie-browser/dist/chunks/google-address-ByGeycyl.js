import { d as x, g as E } from "./index-Cmikarpm.js";
import { e as v, a as L } from "./scripts-D7TV7mth.js";
const w = "FORMIE_GOOGLE_ADDRESS_SCRIPT", m = "formieGoogleMapsReady";
let f = null;
async function R(t) {
  const e = window, n = e.google;
  if (typeof n < "u" && n !== null) {
    const s = n.maps;
    if (s?.places?.PlaceAutocompleteElement)
      return n;
    const i = s;
    return typeof i?.importLibrary == "function" && await i.importLibrary("places"), n;
  }
  if (f)
    return f;
  if (document.getElementById(w)) {
    const s = await v("google", 1e4), i = s?.maps;
    return typeof i?.importLibrary == "function" && await i.importLibrary("places"), s;
  }
  const o = new URL("https://maps.googleapis.com/maps/api/js");
  o.searchParams.set("key", t), o.searchParams.set("loading", "async"), o.searchParams.set("libraries", "places"), o.searchParams.set("callback", m), f = (async () => {
    const s = new Promise((d, r) => {
      const u = setTimeout(() => {
        e[m] && (delete e[m], r(new Error("Google Maps API load timeout")));
      }, 15e3);
      e[m] = () => {
        clearTimeout(u), delete e[m], d(e.google);
      };
    });
    await L({
      id: w,
      src: o.toString(),
      async: !0,
      defer: !0
    });
    const i = await s, l = i?.maps;
    return typeof l?.importLibrary == "function" && await l.importLibrary("places"), i;
  })();
  try {
    return await f;
  } catch (s) {
    throw f = null, s;
  }
}
function A(t, e) {
  e.formattedAddress && t.input.setValue("autoComplete", e.formattedAddress), e.address1 && t.input.setValue("address1", e.address1), e.city !== void 0 && t.input.setValue("city", e.city), e.state !== void 0 && t.input.setValue("state", e.state), e.zip !== void 0 && t.input.setValue("zip", e.zip), e.country !== void 0 && t.input.setValue("country", e.country);
}
function S() {
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
function b(t) {
  const e = S(), n = {};
  for (const a of t) {
    const o = a.types?.[0];
    if (!o || !e[o]) continue;
    const s = e[o], i = a[s] ?? a.short_text ?? a.long_text ?? "";
    n[o] = i;
  }
  return n;
}
function C(t) {
  let e = "";
  return (t.street_number || t.route) && (e = [t.street_number, t.route].filter(Boolean).join(" "), t.subpremise && (e = `${t.subpremise}/${e}`)), {
    address1: e,
    city: t.locality || t.postal_town || "",
    state: t.administrative_area_level_1 || "",
    zip: t.postal_code || "",
    country: t.country || ""
  };
}
function T(t) {
  const e = t.componentRestrictions;
  if (e && typeof e == "object") {
    const a = e.country;
    if (Array.isArray(a) ? a.length > 0 : a)
      return !0;
  }
  const n = t.includedRegionCodes;
  return Array.isArray(n) && n.length > 0;
}
function G(t) {
  const e = { types: ["geocode"], ...t.options || {} }, n = t.countryDefaultValue?.trim().toLowerCase();
  return !n || T(e) ? e : {
    ...e,
    componentRestrictions: { country: n },
    includedRegionCodes: [n.toUpperCase()]
  };
}
const I = x({
  id: "google-address",
  load: async ({ options: t }) => {
    const e = t.provider.apiKey;
    if (!e)
      throw new Error("Google Places API key is required");
    return R(e);
  },
  mount: async ({ api: t, field: e, services: n, provider: a }) => {
    const o = n.input.getAutocomplete(), s = t?.maps?.places?.PlaceAutocompleteElement;
    if (!o || typeof s != "function")
      return console.warn("[formie] Google Places API not ready; address autocomplete skipped."), null;
    const i = G(a), l = new s(i), d = window.getComputedStyle(o).height;
    l.style.height = d, l.style.boxSizing = "border-box";
    let r = o.parentElement;
    r?.classList.contains("formie-autocomplete-wrapper") || (r = document.createElement("div"), r.classList.add("formie-autocomplete-wrapper"), o.parentNode?.insertBefore(r, o), r.appendChild(o));
    const u = o.value;
    if (u) {
      const c = document.createElement("div");
      c.classList.add("formie-autocomplete-placeholder"), c.textContent = u, r.style.position = "relative", c.style.cssText = `
                position: absolute; left: 0; top: 0; height: ${d};
                line-height: ${d}; width: 100%; padding: 0 2.5rem;
                pointer-events: none; color: #6B7280; font-size: 14px; z-index: 1;
            `, r.appendChild(c), l.addEventListener("focusin", () => {
        c.style.display = "none";
      }), l.addEventListener("focusout", () => {
        o.value && (c.style.display = "");
      });
    }
    r.replaceChild(l, o), o.type = "hidden", o.name = o.getAttribute("name") || "", r.appendChild(o);
    const y = async (c) => {
      const h = c.placePrediction;
      if (!h) return;
      const p = await h.toPlace();
      if (await p.fetchFields({ fields: ["addressComponents", "formattedAddress"] }), !p.addressComponents) return;
      const P = b(
        p.addressComponents
      ), _ = C(P);
      A(n, {
        ..._,
        formattedAddress: p.formattedAddress
      }), e.dispatchEvent(
        new CustomEvent(E("google", "populate"), {
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
    return l.addEventListener("gmp-select", y), l;
  },
  onCurrentLocation: async (t, { field: e, services: n }) => {
    const { latitude: a, longitude: o } = t.coords, s = n.form, i = s?.action || window.location.href, l = e.getAttribute("data-formie-field-handle")?.trim(), d = s?.querySelector('[name="handle"]')?.value?.trim();
    if (!(!d || !l))
      try {
        const r = new FormData();
        r.append("action", "formie/address/google-places-geocode"), r.append("latlng", `${a},${o}`), r.append("handle", d), r.append("fieldHandle", l);
        const y = await (await fetch(i, {
          method: "POST",
          body: r,
          credentials: "include",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json"
          }
        })).json();
        if (y?.results?.[0]?.address_components) {
          const c = b(
            y.results[0].address_components
          ), g = C(c);
          A(n, g);
        }
      } catch {
      }
  }
});
export {
  G as buildGoogleAutocompleteOptions,
  I as googleAddressModule
};
