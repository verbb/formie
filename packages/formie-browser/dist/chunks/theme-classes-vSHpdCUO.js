//#region src/js/theme/theme-classes.ts
var themeRegistry = /* @__PURE__ */ new WeakMap();
var THEME_ROOT_SELECTORS = "[data-formie-form], [data-formie], form";
function splitClasses(value) {
	if (!value) return [];
	return (Array.isArray(value) ? value : [value]).flatMap((item) => {
		return String(item).split(/\s+/);
	}).map((item) => {
		return item.trim();
	}).filter(Boolean);
}
function uniqueClasses(classes) {
	return Array.from(new Set(classes));
}
function resolveThemeClassMap(source) {
	if (!source) return {};
	const directMatch = themeRegistry.get(source);
	if (directMatch) return directMatch;
	const root = source.closest(THEME_ROOT_SELECTORS);
	if (!root) return {};
	return themeRegistry.get(root) || {};
}
function normalizeThemeClassMap(theme) {
	const normalized = {};
	Object.entries(theme || {}).forEach(([key, value]) => {
		const classes = uniqueClasses(splitClasses(value));
		if (classes.length) normalized[key] = classes;
	});
	return normalized;
}
function registerThemeClassMap(target, theme, form) {
	const normalized = normalizeThemeClassMap(theme);
	const resolvedForm = form || (target instanceof HTMLFormElement ? target : target.querySelector("form"));
	themeRegistry.set(target, normalized);
	if (resolvedForm) themeRegistry.set(resolvedForm, normalized);
	return normalized;
}
function getThemeClasses(source, key) {
	return resolveThemeClassMap(source)[key] || [];
}
function addThemeClasses(target, source, ...keys) {
	const classes = uniqueClasses(keys.flatMap((key) => {
		return getThemeClasses(source, key);
	}));
	if (classes.length) target.classList.add(...classes);
}
function removeThemeClasses(target, source, ...keys) {
	const classes = uniqueClasses(keys.flatMap((key) => {
		return getThemeClasses(source, key);
	}));
	if (classes.length) target.classList.remove(...classes);
}
function toggleThemeClasses(target, source, key, enabled) {
	getThemeClasses(source, key).forEach((className) => {
		target.classList.toggle(className, enabled);
	});
}
//#endregion
export { toggleThemeClasses as i, registerThemeClassMap as n, removeThemeClasses as r, addThemeClasses as t };
