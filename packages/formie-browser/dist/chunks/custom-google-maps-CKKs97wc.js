import { b as L, o as h } from "./shared-BDEKVuB5.js";
const b = "custom-google-maps", m = "[data-formie-custom-google-maps]";
let g = null;
function v(t) {
  return t instanceof HTMLElement && t.matches(m);
}
function E(t) {
  const o = t.getAttribute("data-formie-custom-google-maps-settings");
  if (!o)
    return {};
  try {
    return JSON.parse(o);
  } catch {
    return {};
  }
}
function _(t) {
  const o = {};
  return t.querySelectorAll("[data-formie-custom-google-maps-field]").forEach((r) => {
    const e = r.getAttribute("data-formie-custom-google-maps-field");
    e && (o[e] = r);
  }), {
    root: t,
    canvas: t.querySelector("[data-formie-custom-google-maps-canvas]"),
    searchInput: t.querySelector("[data-formie-custom-google-maps-search]"),
    currentLocationButton: t.querySelector("[data-formie-custom-google-maps-current-location]"),
    inputs: o
  };
}
function u(t) {
  if (t == null || t === "")
    return null;
  const o = Number(t);
  return Number.isFinite(o) ? o : null;
}
function M(t, o) {
  return {
    lat: u(t.inputs.lat?.value) ?? o.defaultLat ?? -37.7841813,
    lng: u(t.inputs.lng?.value) ?? o.defaultLng ?? 144.9378721
  };
}
function S(t, o) {
  return u(t.inputs.zoom?.value) ?? o.defaultZoom ?? 11;
}
function y(t, o) {
  t && (t.value = o == null ? "" : String(o), t.dispatchEvent(new Event("input", { bubbles: !0 })), t.dispatchEvent(new Event("change", { bubbles: !0 })));
}
function P(t) {
  return new Promise((o, r) => {
    const e = Array.from(document.scripts).find((n) => n.src === t);
    if (window.google?.maps?.places) {
      o();
      return;
    }
    if (e) {
      e.addEventListener("load", () => o(), { once: !0 }), e.addEventListener("error", () => r(new Error(`Failed to load ${t}`)), { once: !0 });
      return;
    }
    const a = document.createElement("script");
    a.src = t, a.async = !0, a.onload = () => o(), a.onerror = () => r(new Error(`Failed to load ${t}`)), document.head.appendChild(a);
  });
}
function C(t) {
  return new Promise((o) => {
    window.setTimeout(o, t);
  });
}
async function k(t = 12e3, o = 50) {
  const r = Date.now();
  for (; Date.now() - r < t; ) {
    const e = window.google?.maps;
    if (e?.importLibrary)
      try {
        e.Map || await e.importLibrary("maps"), e.Marker || await e.importLibrary("marker"), e.places?.Autocomplete || await e.importLibrary("places");
      } catch {
      }
    if (window.google?.maps?.Map && window.google.maps.Marker && window.google.maps.places?.Autocomplete)
      return window.google;
    await C(o);
  }
  throw new Error("Google Maps Places API did not initialize.");
}
function G(t) {
  return t.apiUrl ? (g || (g = P(t.apiUrl).then(() => k())), g) : Promise.reject(new Error("Google Maps API URL is missing."));
}
function s(t, o, r = !1) {
  const e = t?.find((a) => a.types.includes(o));
  return r ? e?.short_name || "" : e?.long_name || "";
}
function A(t) {
  const o = t.address_components || [], r = s(o, "street_number"), e = s(o, "route"), a = [r, e].filter(Boolean).join(" "), n = t.geometry?.location?.lat(), i = t.geometry?.location?.lng();
  return {
    formatted: t.formatted_address || "",
    raw: JSON.stringify(t),
    name: t.name || "",
    street1: a,
    city: s(o, "locality") || s(o, "postal_town") || s(o, "administrative_area_level_2"),
    state: s(o, "administrative_area_level_1", !0),
    zip: s(o, "postal_code"),
    neighborhood: s(o, "neighborhood") || s(o, "sublocality"),
    county: s(o, "administrative_area_level_2"),
    country: s(o, "country"),
    countryCode: s(o, "country", !0),
    placeId: t.place_id || "",
    lat: n === void 0 ? "" : String(n),
    lng: i === void 0 ? "" : String(i)
  };
}
function d(t, o, r, e) {
  Object.entries(o).forEach(([i, l]) => {
    y(t.inputs[i], l);
  }), o.formatted !== void 0 && y(t.searchInput, o.formatted);
  const a = u(o.lat), n = u(o.lng);
  if (a !== null && n !== null) {
    const i = { lat: a, lng: n };
    e?.setPosition(i), r?.setCenter(i);
  }
}
async function Z(t, o) {
  const r = await G(o), e = M(t, o), a = S(t, o);
  let n, i;
  t.canvas && (n = new r.maps.Map(t.canvas, {
    center: e,
    zoom: a,
    minZoom: o.minZoom ?? void 0,
    maxZoom: o.maxZoom ?? void 0
  }), i = new r.maps.Marker({
    position: e,
    map: n,
    draggable: !0
  }), n.addListener("click", (c) => {
    const f = c.latLng?.lat(), w = c.latLng?.lng();
    f === void 0 || w === void 0 || d(t, {
      lat: f,
      lng: w,
      zoom: n?.getZoom() || a
    }, n, i);
  }), i.addListener("dragend", () => {
    const c = i?.getPosition();
    c && d(t, {
      lat: c.lat(),
      lng: c.lng(),
      zoom: n?.getZoom() || a
    }, n, i);
  }));
  let l = null;
  t.searchInput && (l = new r.maps.places.Autocomplete(t.searchInput, {
    fields: ["address_components", "formatted_address", "geometry", "name", "place_id"],
    componentRestrictions: o.country ? { country: o.country.toLowerCase() } : void 0
  }), l.addListener("place_changed", () => {
    const c = l?.getPlace();
    c && d(t, A(c), n, i);
  }));
  const p = () => {
    navigator.geolocation?.getCurrentPosition((c) => {
      d(t, {
        lat: c.coords.latitude,
        lng: c.coords.longitude,
        zoom: n?.getZoom() || a
      }, n, i);
    });
  };
  return t.currentLocationButton?.addEventListener("click", p), () => {
    t.currentLocationButton?.removeEventListener("click", p);
  };
}
function z(t) {
  const o = _(t), r = E(t);
  let e = null, a = !1;
  return Z(o, r).then((n) => {
    if (a) {
      n();
      return;
    }
    e = n;
  }).catch((n) => {
    console.warn("[Formie] Unable to initialize Google Maps custom field.", n);
  }), () => {
    a = !0, e?.();
  };
}
const O = {
  id: b,
  kind: "field",
  match: ({ target: t }) => t instanceof Element && (t.matches(m) || !!t.querySelector(m)),
  setup: async (t) => {
    const r = L(t) || t.target;
    return r instanceof Element ? {
      destroy: h(r, m, v, z)
    } : void 0;
  }
};
export {
  O as customGoogleMapsModule
};
