import { n as e } from "./event-names-BCI2FLD8.js";
import { t } from "./api-sPqGbOww.js";
import { n, t as r } from "./scripts-CbQ7agX3.js";
//#region src/js/modules/address/google-address.ts
var i = "FORMIE_GOOGLE_ADDRESS_SCRIPT", a = "formieGoogleMapsReady", o = null;
async function s(e) {
	let t = window, s = t.google;
	if (s != null) {
		let e = s.maps;
		if (e?.places?.PlaceAutocompleteElement) return s;
		let t = e;
		return typeof t?.importLibrary == "function" && await t.importLibrary("places"), s;
	}
	if (o) return o;
	if (document.getElementById(i)) {
		let e = await r("google", 1e4), t = e?.maps;
		return typeof t?.importLibrary == "function" && await t.importLibrary("places"), e;
	}
	let c = new URL("https://maps.googleapis.com/maps/api/js");
	c.searchParams.set("key", e), c.searchParams.set("loading", "async"), c.searchParams.set("libraries", "places"), c.searchParams.set("callback", a), o = (async () => {
		let e = new Promise((e, n) => {
			let r = setTimeout(() => {
				t[a] && (delete t[a], n(/* @__PURE__ */ Error("Google Maps API load timeout")));
			}, 15e3);
			t[a] = () => {
				clearTimeout(r), delete t[a], e(t.google);
			};
		});
		await n({
			id: i,
			src: c.toString(),
			async: !0,
			defer: !0
		});
		let r = await e, o = r?.maps;
		return typeof o?.importLibrary == "function" && await o.importLibrary("places"), r;
	})();
	try {
		return await o;
	} catch (e) {
		throw o = null, e;
	}
}
function c(e, t) {
	t.formattedAddress && e.input.setValue("autoComplete", t.formattedAddress), t.address1 && e.input.setValue("address1", t.address1), t.city !== void 0 && e.input.setValue("city", t.city), t.state !== void 0 && e.input.setValue("state", t.state), t.zip !== void 0 && e.input.setValue("zip", t.zip), t.country !== void 0 && e.input.setValue("country", t.country);
}
function l() {
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
function u(e) {
	let t = l(), n = {};
	for (let r of e) {
		let e = r.types?.[0];
		!e || !t[e] || (n[e] = r[t[e]] ?? r.short_text ?? r.long_text ?? "");
	}
	return n;
}
function d(e) {
	let t = "";
	return (e.street_number || e.route) && (t = [e.street_number, e.route].filter(Boolean).join(" "), e.subpremise && (t = `${e.subpremise}/${t}`)), {
		address1: t,
		city: e.locality || e.postal_town || "",
		state: e.administrative_area_level_1 || "",
		zip: e.postal_code || "",
		country: e.country || ""
	};
}
function f(e) {
	let t = e.componentRestrictions;
	if (t && typeof t == "object") {
		let e = t.country;
		if (Array.isArray(e) ? e.length > 0 : e) return !0;
	}
	let n = e.includedRegionCodes;
	return Array.isArray(n) && n.length > 0;
}
function p(e) {
	let t = {
		types: ["geocode"],
		...e.options || {}
	}, n = e.countryDefaultValue?.trim().toLowerCase();
	return !n || f(t) ? t : {
		...t,
		componentRestrictions: { country: n },
		includedRegionCodes: [n.toUpperCase()]
	};
}
var m = t({
	id: "google-address",
	load: async ({ options: e }) => {
		let t = e.provider.apiKey;
		if (!t) throw Error("Google Places API key is required");
		return s(t);
	},
	mount: async ({ api: t, field: n, services: r, provider: i }) => {
		let a = r.input.getAutocomplete(), o = t?.maps?.places?.PlaceAutocompleteElement;
		if (!a || typeof o != "function") return console.warn("[formie] Google Places API not ready; address autocomplete skipped."), null;
		let s = new o(p(i)), l = window.getComputedStyle(a).height;
		s.style.height = l, s.style.boxSizing = "border-box";
		let f = a.parentElement;
		f?.classList.contains("formie-autocomplete-wrapper") || (f = document.createElement("div"), f.classList.add("formie-autocomplete-wrapper"), a.parentNode?.insertBefore(f, a), f.appendChild(a));
		let m = a.value;
		if (m) {
			let e = document.createElement("div");
			e.classList.add("formie-autocomplete-placeholder"), e.textContent = m, f.style.position = "relative", e.style.cssText = `
                position: absolute; left: 0; top: 0; height: ${l};
                line-height: ${l}; width: 100%; padding: 0 2.5rem;
                pointer-events: none; color: #6B7280; font-size: 14px; z-index: 1;
            `, f.appendChild(e), s.addEventListener("focusin", () => {
				e.style.display = "none";
			}), s.addEventListener("focusout", () => {
				a.value && (e.style.display = "");
			});
		}
		return f.replaceChild(s, a), a.type = "hidden", a.name = a.getAttribute("name") || "", f.appendChild(a), s.addEventListener("gmp-select", async (t) => {
			let i = t.placePrediction;
			if (!i) return;
			let a = await i.toPlace();
			await a.fetchFields({ fields: ["addressComponents", "formattedAddress"] }), a.addressComponents && (c(r, {
				...d(u(a.addressComponents)),
				formattedAddress: a.formattedAddress
			}), n.dispatchEvent(new CustomEvent(e("google", "populate"), {
				bubbles: !0,
				detail: {
					addressProvider: "google",
					place: a,
					formattedAddress: a.formattedAddress,
					addressComponents: a.addressComponents
				}
			})));
		}), s;
	},
	onCurrentLocation: async (e, { field: t, services: n }) => {
		let { latitude: r, longitude: i } = e.coords, a = n.form, o = a?.action || window.location.href, s = t.getAttribute("data-formie-field-handle")?.trim(), l = (a?.querySelector("[name=\"handle\"]"))?.value?.trim();
		if (!(!l || !s)) try {
			let e = new FormData();
			e.append("action", "formie/address/google-places-geocode"), e.append("latlng", `${r},${i}`), e.append("handle", l), e.append("fieldHandle", s);
			let t = await (await fetch(o, {
				method: "POST",
				body: e,
				credentials: "include",
				headers: {
					"X-Requested-With": "XMLHttpRequest",
					Accept: "application/json"
				}
			})).json();
			t?.results?.[0]?.address_components && c(n, d(u(t.results[0].address_components)));
		} catch {}
	}
});
//#endregion
export { m as googleAddressModule };
