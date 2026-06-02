//#region src/js/modules/styles.ts
var injectedModuleStyles = /* @__PURE__ */ new Set();
function ensureModuleStyles(moduleId, styles) {
	if (typeof document === "undefined") return;
	const styleId = `formie-module-style:${moduleId}`;
	if (injectedModuleStyles.has(styleId) || document.querySelector(`style[data-formie-module-style="${moduleId}"]`)) {
		injectedModuleStyles.add(styleId);
		return;
	}
	const cssText = styles.filter((style) => typeof style === "string" && style.trim().length > 0).join("\n");
	if (!cssText) {
		injectedModuleStyles.add(styleId);
		return;
	}
	const style = document.createElement("style");
	style.setAttribute("data-formie-module-style", moduleId);
	style.textContent = cssText;
	document.head.appendChild(style);
	injectedModuleStyles.add(styleId);
}
//#endregion
export { ensureModuleStyles as t };
