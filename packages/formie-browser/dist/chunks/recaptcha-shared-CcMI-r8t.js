import { r as e } from "./async-2GdXNFCE.js";
import { a as t, r as n } from "./api-BPtH5RBA.js";
import { n as r } from "./scripts-DrccjrFv.js";
//#region src/js/modules/captchas/recaptcha-shared.ts
async function i(i, a = !1, o) {
	let s = typeof i.language == "string" && i.language.trim() !== "" ? i.language.trim() : "en", { async: c, defer: l } = n(i.loadingMethod), u = a ? "https://www.google.com/recaptcha/enterprise.js" : "https://www.recaptcha.net/recaptcha/api.js", d = typeof o == "string" && o.trim() !== "" ? o.trim() : "explicit", f = new URL(u);
	f.searchParams.set("render", d), f.searchParams.set("hl", s), d !== "explicit" && typeof i.badge == "string" && i.badge.trim() !== "" && f.searchParams.set("badge", i.badge.trim());
	let p = () => {
		let e = window.grecaptcha, t = a ? e?.enterprise : e;
		return e && t && typeof t.ready == "function" && typeof t.execute == "function" && typeof t.render == "function" ? e : null;
	};
	return p() || (await r({
		id: a ? "FORMIE_RECAPTCHA_ENTERPRISE_SCRIPT" : "FORMIE_RECAPTCHA_SCRIPT",
		src: f.toString(),
		async: c,
		defer: l
	}), e(p, { timeoutMs: t }));
}
function a(e, t, n = !1) {
	let r = n ? e.enterprise : e;
	return new Promise((e, n) => {
		try {
			if (!r) throw Error("The reCAPTCHA API is unavailable.");
			r.ready(() => {
				Promise.resolve(t()).then(e).catch(n);
			});
		} catch (e) {
			n(e);
		}
	});
}
//#endregion
export { a as n, i as t };
