import { t as e } from "./debug-BV0DvdHx.js";
//#region src/js/modules/address/constants.ts
var t = "[data-formie-address-autocomplete-input]", n = "[data-formie-address-location]", r = {
	autoComplete: "[data-formie-address-autocomplete-input]",
	address1: "[data-formie-address-line1-input]",
	address2: "[data-formie-address-line2-input]",
	address3: "[data-formie-address-line3-input]",
	city: "[data-formie-address-city-input]",
	state: "[data-formie-address-state-input]",
	zip: "[data-formie-address-zip-input]",
	country: "[data-formie-address-country-input]"
}, i = new Set(["handle"]);
function a(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function o(e, t) {
	let n = t || {}, r = Object.entries(n).reduce((e, [t, n]) => (i.has(t) || (e[t] = n), e), {});
	return {
		handle: a(e, n),
		provider: r
	};
}
function s(e, t, n) {
	return e.addEventListener(t, n), () => {
		e.removeEventListener(t, n);
	};
}
function c(e) {
	let i = e.target, a = e.form, o = e.root, c = t;
	return {
		root: o,
		field: i,
		form: a,
		input: {
			getAutocomplete: () => i.querySelector(c),
			setValue: (e, t, n) => {
				let a = r[e], o = i.querySelector(a);
				o && (o.value = t || n || "");
			}
		},
		location: {
			getButton: () => i.querySelector(n),
			onUseLocation: (e) => {
				let t = i.querySelector(n);
				if (!t) return () => {};
				let r = (t) => {
					t.preventDefault(), navigator.geolocation && navigator.geolocation.getCurrentPosition(e, () => {}, { enableHighAccuracy: !0 });
				};
				return t.addEventListener("click", r), () => {
					t.removeEventListener("click", r);
				};
			}
		},
		events: { onField: (e, t) => s(i, e, t) }
	};
}
//#endregion
//#region src/js/modules/address/factories.ts
var l = e("address");
function u(e) {
	let t = e;
	return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function d(e) {
	return {
		id: e.id,
		kind: "address",
		match: (e) => !!e.target.querySelector("[data-formie-address-autocomplete-input]"),
		setup: async (t) => {
			let n = o(e.id, t.options || {}), r = c(t);
			l.log("Setup module.", { moduleId: e.id });
			let i = {
				...t,
				options: n,
				services: r
			}, a = [], s = null, d = null;
			if (!r.input.getAutocomplete()) return console.warn(`[formie] Address module "${e.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`), l.warn("Autocomplete input missing; skipping module.", { moduleId: e.id }), { destroy: () => {} };
			let f = async () => (s ||= (l.log("Loading provider API.", { moduleId: e.id }), e.load(i)), s), p = async () => {
				if (d || !u(t.target)) return;
				let i = await f();
				d = await e.mount({
					api: i,
					field: t.target,
					services: r,
					options: n,
					provider: n.provider
				}), l.log("Widget mounted.", { moduleId: e.id });
			};
			u(t.target) && await p(), ["formie:page:navigate:after", "formie:submit:result"].forEach((e) => {
				let n = () => {
					p();
				};
				t.root.addEventListener(e, n), a.push(() => {
					t.root.removeEventListener(e, n);
				});
			});
			let m = r.location.onUseLocation((i) => {
				e.onCurrentLocation && (async () => {
					if (await p(), !d) return;
					let a = await f();
					await e.onCurrentLocation?.(i, {
						api: a,
						widget: d,
						field: t.target,
						services: r,
						options: n,
						provider: n.provider
					});
				})();
			});
			return m && a.push(m), { destroy: async () => {
				if (l.log("Destroying module.", { moduleId: e.id }), a.forEach((e) => e()), d && e.unmount) {
					let i = await f();
					await e.unmount({
						api: i,
						widget: d,
						field: t.target,
						services: r,
						options: n,
						provider: n.provider
					}), l.log("Widget unmounted.", { moduleId: e.id });
				}
			} };
		}
	};
}
//#endregion
//#region src/js/modules/address/api.ts
var f = d;
//#endregion
export { r as n, f as t };
