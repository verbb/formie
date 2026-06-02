//#region src/js/utils/debug.ts
function getDebugGlobal() {
	return globalThis;
}
function isFormieDebugEnabled() {
	return getDebugGlobal().__FORMIE_DEBUG__ === true;
}
function setFormieDebugEnabled(enabled) {
	getDebugGlobal().__FORMIE_DEBUG__ = enabled;
}
function debugLog(scope, message, meta) {
	if (!isFormieDebugEnabled()) return;
	if (typeof meta === "undefined") {
		console.log(`[formie:${scope}] ${message}`);
		return;
	}
	console.log(`[formie:${scope}] ${message}`, meta);
}
function debugWarn(scope, message, meta) {
	if (!isFormieDebugEnabled()) return;
	if (typeof meta === "undefined") {
		console.warn(`[formie:${scope}] ${message}`);
		return;
	}
	console.warn(`[formie:${scope}] ${message}`, meta);
}
function createDebug(category, module) {
	const scope = module ? `${category}:${module}` : category;
	return {
		log: (message, meta) => {
			debugLog(scope, message, meta);
		},
		warn: (message, meta) => {
			debugWarn(scope, message, meta);
		}
	};
}
//#endregion
export { setFormieDebugEnabled as a, isFormieDebugEnabled as i, debugLog as n, debugWarn as r, createDebug as t };
