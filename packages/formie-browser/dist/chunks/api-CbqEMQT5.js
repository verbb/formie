import { t as createDebug } from "./debug-KnZeKYBI.js";
//#region src/js/modules/address/constants.ts
/** Default selector for the autocomplete input within an address field. */
var DEFAULT_AUTOCOMPLETE_SELECTOR = "[data-formie-address-autocomplete-input]";
var ADDRESS_LOCATION_SELECTOR = "[data-formie-address-location]";
/** Selector for address sub-field inputs used when populating from provider. */
var ADDRESS_SELECTORS = {
	autoComplete: "[data-formie-address-autocomplete-input]",
	address1: "[data-formie-address-line1-input]",
	address2: "[data-formie-address-line2-input]",
	address3: "[data-formie-address-line3-input]",
	city: "[data-formie-address-city-input]",
	state: "[data-formie-address-state-input]",
	zip: "[data-formie-address-zip-input]",
	country: "[data-formie-address-country-input]"
};
//#endregion
//#region src/js/modules/address/host.ts
var ADDRESS_OPTION_KEYS = new Set(["handle"]);
function getAddressProviderHandle(id, options) {
	return (typeof options.handle === "string" && options.handle.trim() !== "" ? options.handle.trim() : "") || id;
}
function normalizeAddressModuleOptions(id, rawOptions) {
	const options = rawOptions || {};
	const provider = Object.entries(options).reduce((carry, [key, value]) => {
		if (ADDRESS_OPTION_KEYS.has(key)) return carry;
		carry[key] = value;
		return carry;
	}, {});
	return {
		handle: getAddressProviderHandle(id, options),
		provider
	};
}
function bindDomEvent(target, eventName, callback) {
	target.addEventListener(eventName, callback);
	return () => {
		target.removeEventListener(eventName, callback);
	};
}
function createAddressHostServices(ctx) {
	const field = ctx.target;
	const form = ctx.form;
	const root = ctx.root;
	const autocompleteSelector = DEFAULT_AUTOCOMPLETE_SELECTOR;
	return {
		root,
		field,
		form,
		input: {
			getAutocomplete: () => {
				return field.querySelector(autocompleteSelector);
			},
			setValue: (selectorKey, value, fallback) => {
				const selector = ADDRESS_SELECTORS[selectorKey];
				const el = field.querySelector(selector);
				if (el) el.value = value || fallback || "";
			}
		},
		location: {
			getButton: () => {
				return field.querySelector(ADDRESS_LOCATION_SELECTOR);
			},
			onUseLocation: (callback) => {
				const btn = field.querySelector(ADDRESS_LOCATION_SELECTOR);
				if (!btn) return () => {};
				const handler = (e) => {
					e.preventDefault();
					if (!navigator.geolocation) return;
					navigator.geolocation.getCurrentPosition(callback, () => {}, { enableHighAccuracy: true });
				};
				btn.addEventListener("click", handler);
				return () => {
					btn.removeEventListener("click", handler);
				};
			}
		},
		events: { onField: (eventName, callback) => bindDomEvent(field, eventName, callback) }
	};
}
//#endregion
//#region src/js/modules/address/factories.ts
var debug = createDebug("address");
function isTargetVisible(element) {
	const node = element;
	return !node.closest("[data-formie-page-hidden]") && !node.closest("[hidden]");
}
function createManagedAddressModule(adapter) {
	return {
		id: adapter.id,
		kind: "address",
		match: (ctx) => {
			return !!ctx.target.querySelector("[data-formie-address-autocomplete-input]");
		},
		setup: async (ctx) => {
			const options = normalizeAddressModuleOptions(adapter.id, ctx.options || {});
			const services = createAddressHostServices(ctx);
			debug.log("Setup module.", { moduleId: adapter.id });
			const setupCtx = {
				...ctx,
				options,
				services
			};
			const cleanups = [];
			let apiPromise = null;
			let widget = null;
			if (!services.input.getAutocomplete()) {
				console.warn(`[formie] Address module "${adapter.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`);
				debug.warn("Autocomplete input missing; skipping module.", { moduleId: adapter.id });
				return { destroy: () => {} };
			}
			const getApi = async () => {
				if (!apiPromise) {
					debug.log("Loading provider API.", { moduleId: adapter.id });
					apiPromise = adapter.load(setupCtx);
				}
				return apiPromise;
			};
			const ensureMounted = async () => {
				if (widget || !isTargetVisible(ctx.target)) return;
				const api = await getApi();
				widget = await adapter.mount({
					api,
					field: ctx.target,
					services,
					options,
					provider: options.provider
				});
				debug.log("Widget mounted.", { moduleId: adapter.id });
			};
			if (isTargetVisible(ctx.target)) await ensureMounted();
			["formie:page:navigate:after", "formie:submit:result"].forEach((eventName) => {
				const handleVisibility = () => {
					ensureMounted();
				};
				ctx.root.addEventListener(eventName, handleVisibility);
				cleanups.push(() => {
					ctx.root.removeEventListener(eventName, handleVisibility);
				});
			});
			const locationCleanup = services.location.onUseLocation((position) => {
				if (!adapter.onCurrentLocation) return;
				(async () => {
					await ensureMounted();
					if (!widget) return;
					const api = await getApi();
					await adapter.onCurrentLocation?.(position, {
						api,
						widget,
						field: ctx.target,
						services,
						options,
						provider: options.provider
					});
				})();
			});
			if (locationCleanup) cleanups.push(locationCleanup);
			return { destroy: async () => {
				debug.log("Destroying module.", { moduleId: adapter.id });
				cleanups.forEach((c) => c());
				if (widget && adapter.unmount) {
					const api = await getApi();
					await adapter.unmount({
						api,
						widget,
						field: ctx.target,
						services,
						options,
						provider: options.provider
					});
					debug.log("Widget unmounted.", { moduleId: adapter.id });
				}
			} };
		}
	};
}
//#endregion
//#region src/js/modules/address/api.ts
var defineAddressModule = createManagedAddressModule;
//#endregion
export { ADDRESS_SELECTORS as n, defineAddressModule as t };
