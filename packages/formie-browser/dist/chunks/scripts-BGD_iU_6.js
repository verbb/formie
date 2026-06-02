import { r as waitFor } from "./async-B3DUf1GZ.js";
//#region src/js/utils/scripts.ts
var scriptLoadCache = /* @__PURE__ */ new Map();
async function ensureGlobal(globalName, timeoutMs = 5e3) {
	return waitFor(() => {
		const value = window[globalName];
		if (typeof value === "undefined" || value === null) return null;
		return value;
	}, {
		timeoutMs,
		intervalMs: 30
	});
}
async function loadExternalScript({ id, src, async = true, defer = true }) {
	const existing = document.getElementById(id);
	if (existing) return existing;
	if (!scriptLoadCache.has(id)) scriptLoadCache.set(id, new Promise((resolve, reject) => {
		const script = document.createElement("script");
		script.id = id;
		script.src = src;
		script.async = async;
		script.defer = defer;
		script.onload = () => {
			resolve(script);
		};
		script.onerror = () => {
			scriptLoadCache.delete(id);
			reject(/* @__PURE__ */ new Error(`Failed to load external script: ${src}`));
		};
		document.body.appendChild(script);
	}));
	return scriptLoadCache.get(id);
}
async function loadScriptAndEnsureGlobal(globalName, options) {
	const existing = window[globalName];
	if (typeof existing !== "undefined" && existing !== null) return existing;
	await loadExternalScript(options);
	return ensureGlobal(globalName, options.timeoutMs);
}
//#endregion
export { loadExternalScript as n, loadScriptAndEnsureGlobal as r, ensureGlobal as t };
