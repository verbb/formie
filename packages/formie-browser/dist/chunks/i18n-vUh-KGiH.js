//#region src/js/utils/i18n.ts
function getWindowTranslationStore() {
	return window.FormieTranslations || {};
}
function hydrateTranslationsFromDom() {
	if (typeof document === "undefined") return;
	const scripts = Array.from(document.querySelectorAll("script[type=\"application/json\"][data-formie-translations]:not([data-formie-translations-loaded=\"true\"])"));
	if (scripts.length === 0) return;
	let nextStore = null;
	for (const script of scripts) {
		script.dataset.formieTranslationsLoaded = "true";
		const payload = script.textContent?.trim();
		if (!payload) continue;
		try {
			const translations = JSON.parse(payload);
			if (!translations || Array.isArray(translations) || typeof translations !== "object") continue;
			nextStore = {
				...nextStore ?? getWindowTranslationStore(),
				...translations
			};
		} catch {
			continue;
		}
	}
	if (nextStore) window.FormieTranslations = nextStore;
}
function getTranslationStore() {
	hydrateTranslationsFromDom();
	return getWindowTranslationStore();
}
function getFormieTranslations() {
	return { ...getTranslationStore() };
}
function setFormieTranslations(translations) {
	window.FormieTranslations = { ...translations };
	return getFormieTranslations();
}
function mergeFormieTranslations(translations) {
	window.FormieTranslations = {
		...getTranslationStore(),
		...translations
	};
	return getFormieTranslations();
}
function t(message, replacements = {}) {
	let output = getTranslationStore()[message] || message;
	output = output.replace(/{([a-zA-Z0-9]+)}/g, (match, key) => {
		if (Object.prototype.hasOwnProperty.call(replacements, key)) return String(replacements[key]);
		return match;
	});
	return output;
}
var translate = t;
//#endregion
export { translate as a, t as i, mergeFormieTranslations as n, setFormieTranslations as r, getFormieTranslations as t };
