import { t as e } from "./request-profile-DhwkeCpS.js";
import { t } from "./dist-1mhMV4JB.js";
//#region src/js/utils/country-from-ip.ts
var n = "formie/address/country-from-ip", r = /* @__PURE__ */ new Map();
function i(e) {
	return new URL(/^https?:\/\//.test(e) || e.startsWith("/") ? e : `/actions/${e}`, window.location.origin).toString();
}
async function a(a = n, o) {
	let s = i(a);
	return r.has(s) || r.set(s, (async () => {
		try {
			let n = await t(s, { headers: { Accept: "application/json" } }, e(o));
			if (!n.ok) return null;
			let r = await n.json();
			return r?.countryCode ? r : null;
		} catch {
			return null;
		}
	})()), r.get(s);
}
function o(e = n, t) {
	return (n) => {
		a(e, t).then((e) => {
			n(e?.countryCode?.toLowerCase() || "");
		});
	};
}
//#endregion
export { a as n, o as t };
