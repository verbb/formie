//#region src/js/utils/i18n.ts
function e() {
	return window.FormieTranslations || {};
}
function t() {
	if (typeof document > "u") return;
	let t = Array.from(document.querySelectorAll("script[type=\"application/json\"][data-formie-translations]:not([data-formie-translations-loaded=\"true\"])"));
	if (t.length === 0) return;
	let n = null;
	for (let r of t) {
		r.dataset.formieTranslationsLoaded = "true";
		let t = r.textContent?.trim();
		if (t) try {
			let r = JSON.parse(t);
			if (!r || Array.isArray(r) || typeof r != "object") continue;
			n = {
				...n ?? e(),
				...r
			};
		} catch {
			continue;
		}
	}
	n && (window.FormieTranslations = n);
}
function n() {
	return t(), e();
}
function r() {
	return { ...n() };
}
function i(e) {
	return window.FormieTranslations = { ...e }, r();
}
function a(e) {
	return window.FormieTranslations = {
		...n(),
		...e
	}, r();
}
function o(e, t = {}) {
	let r = n()[e] || e;
	return r = r.replace(/{([a-zA-Z0-9]+)}/g, (e, n) => Object.prototype.hasOwnProperty.call(t, n) ? String(t[n]) : e), r;
}
var s = o;
//#endregion
export { s as a, o as i, a as n, i as r, r as t };
