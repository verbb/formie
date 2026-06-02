import { n as getAddressProviderEventName } from "./event-names-DamGPtXR.js";
import { t as defineAddressModule } from "./api-CbqEMQT5.js";
import { n as loadExternalScript, t as ensureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/address/google-address.ts
var SCRIPT_ID = "FORMIE_GOOGLE_ADDRESS_SCRIPT";
var CALLBACK_NAME = "formieGoogleMapsReady";
var loadPromise = null;
/** Load Google Maps with callback; required when using loading=async. */
async function loadGoogleMapsScript(apiKey) {
	const w = window;
	const existing = w.google;
	if (typeof existing !== "undefined" && existing !== null) {
		const maps = existing.maps;
		if (maps?.places?.PlaceAutocompleteElement) return existing;
		const imp = maps;
		if (typeof imp?.importLibrary === "function") await imp.importLibrary("places");
		return existing;
	}
	if (loadPromise) return loadPromise;
	if (document.getElementById(SCRIPT_ID)) {
		const google = await ensureGlobal("google", 1e4);
		const maps = google?.maps;
		if (typeof maps?.importLibrary === "function") await maps.importLibrary("places");
		return google;
	}
	const url = new URL("https://maps.googleapis.com/maps/api/js");
	url.searchParams.set("key", apiKey);
	url.searchParams.set("loading", "async");
	url.searchParams.set("libraries", "places");
	url.searchParams.set("callback", CALLBACK_NAME);
	loadPromise = (async () => {
		const ready = new Promise((resolve, reject) => {
			const t = setTimeout(() => {
				if (w[CALLBACK_NAME]) {
					delete w[CALLBACK_NAME];
					reject(/* @__PURE__ */ new Error("Google Maps API load timeout"));
				}
			}, 15e3);
			w[CALLBACK_NAME] = () => {
				clearTimeout(t);
				delete w[CALLBACK_NAME];
				resolve(w.google);
			};
		});
		await loadExternalScript({
			id: SCRIPT_ID,
			src: url.toString(),
			async: true,
			defer: true
		});
		const google = await ready;
		const maps = google?.maps;
		if (typeof maps?.importLibrary === "function") await maps.importLibrary("places");
		return google;
	})();
	try {
		return await loadPromise;
	} catch (e) {
		loadPromise = null;
		throw e;
	}
}
function setFieldValues(services, data) {
	if (data.formattedAddress) services.input.setValue("autoComplete", data.formattedAddress);
	if (data.address1) services.input.setValue("address1", data.address1);
	if (data.city !== void 0) services.input.setValue("city", data.city);
	if (data.state !== void 0) services.input.setValue("state", data.state);
	if (data.zip !== void 0) services.input.setValue("zip", data.zip);
	if (data.country !== void 0) services.input.setValue("country", data.country);
}
function componentMap() {
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
function parseAddressComponents(components) {
	const map = componentMap();
	const out = {};
	for (const comp of components) {
		const type = comp.types?.[0];
		if (!type || !map[type]) continue;
		out[type] = comp[map[type]] ?? comp.short_text ?? comp.long_text ?? "";
	}
	return out;
}
function buildAddressFromComponents(formData) {
	let address1 = "";
	if (formData.street_number || formData.route) {
		address1 = [formData.street_number, formData.route].filter(Boolean).join(" ");
		if (formData.subpremise) address1 = `${formData.subpremise}/${address1}`;
	}
	return {
		address1,
		city: formData.locality || formData.postal_town || "",
		state: formData.administrative_area_level_1 || "",
		zip: formData.postal_code || "",
		country: formData.country || ""
	};
}
var googleAddressModule = defineAddressModule({
	id: "google-address",
	load: async ({ options }) => {
		const apiKey = options.provider.apiKey;
		if (!apiKey) throw new Error("Google Places API key is required");
		return loadGoogleMapsScript(apiKey);
	},
	mount: async ({ api, field, services, provider }) => {
		const input = services.input.getAutocomplete();
		const PlaceAutocompleteElement = api?.maps?.places?.PlaceAutocompleteElement;
		if (!input || typeof PlaceAutocompleteElement !== "function") {
			console.warn("[formie] Google Places API not ready; address autocomplete skipped.");
			return null;
		}
		const autocomplete = new PlaceAutocompleteElement({
			types: ["geocode"],
			...provider.options || {}
		});
		const inputHeight = window.getComputedStyle(input).height;
		autocomplete.style.height = inputHeight;
		autocomplete.style.boxSizing = "border-box";
		let wrapper = input.parentElement;
		if (!wrapper?.classList.contains("formie-autocomplete-wrapper")) {
			wrapper = document.createElement("div");
			wrapper.classList.add("formie-autocomplete-wrapper");
			input.parentNode?.insertBefore(wrapper, input);
			wrapper.appendChild(input);
		}
		const savedValue = input.value;
		if (savedValue) {
			const overlay = document.createElement("div");
			overlay.classList.add("formie-autocomplete-placeholder");
			overlay.textContent = savedValue;
			wrapper.style.position = "relative";
			overlay.style.cssText = `
                position: absolute; left: 0; top: 0; height: ${inputHeight};
                line-height: ${inputHeight}; width: 100%; padding: 0 2.5rem;
                pointer-events: none; color: #6B7280; font-size: 14px; z-index: 1;
            `;
			wrapper.appendChild(overlay);
			autocomplete.addEventListener("focusin", () => {
				overlay.style.display = "none";
			});
			autocomplete.addEventListener("focusout", () => {
				if (input.value) overlay.style.display = "";
			});
		}
		wrapper.replaceChild(autocomplete, input);
		input.type = "hidden";
		input.name = input.getAttribute("name") || "";
		wrapper.appendChild(input);
		const onSelect = async (ev) => {
			const pred = ev.placePrediction;
			if (!pred) return;
			const place = await pred.toPlace();
			await place.fetchFields({ fields: ["addressComponents", "formattedAddress"] });
			if (!place.addressComponents) return;
			setFieldValues(services, {
				...buildAddressFromComponents(parseAddressComponents(place.addressComponents)),
				formattedAddress: place.formattedAddress
			});
			field.dispatchEvent(new CustomEvent(getAddressProviderEventName("google", "populate"), {
				bubbles: true,
				detail: {
					addressProvider: "google",
					place,
					formattedAddress: place.formattedAddress,
					addressComponents: place.addressComponents
				}
			}));
		};
		autocomplete.addEventListener("gmp-select", onSelect);
		return autocomplete;
	},
	onCurrentLocation: async (position, { field, services }) => {
		const { latitude, longitude } = position.coords;
		const form = services.form;
		const actionUrl = form?.action || window.location.href;
		const fieldHandle = field.getAttribute("data-formie-field-handle")?.trim();
		const formHandle = (form?.querySelector("[name=\"handle\"]"))?.value?.trim();
		if (!formHandle || !fieldHandle) return;
		try {
			const formData = new FormData();
			formData.append("action", "formie/address/google-places-geocode");
			formData.append("latlng", `${latitude},${longitude}`);
			formData.append("handle", formHandle);
			formData.append("fieldHandle", fieldHandle);
			const data = await (await fetch(actionUrl, {
				method: "POST",
				body: formData,
				credentials: "include",
				headers: {
					"X-Requested-With": "XMLHttpRequest",
					Accept: "application/json"
				}
			})).json();
			if (data?.results?.[0]?.address_components) setFieldValues(services, buildAddressFromComponents(parseAddressComponents(data.results[0].address_components)));
		} catch {}
	}
});
//#endregion
export { googleAddressModule };
