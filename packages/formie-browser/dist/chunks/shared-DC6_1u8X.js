import { r as getFieldModuleEventName } from "./event-names-DamGPtXR.js";
//#region src/js/modules/fields/shared.ts
var fallbackCssEscape = (value) => {
	return value.replace(/["\\]/g, "\\$&");
};
var validatorRegistrations = /* @__PURE__ */ new WeakMap();
function escapeSelectorValue(value) {
	if (typeof window.CSS?.escape === "function") return window.CSS.escape(value);
	return fallbackCssEscape(value);
}
function getModuleFieldContainers(ctx) {
	if (ctx.target instanceof HTMLElement && ctx.target.hasAttribute("data-formie-field-handle")) return [ctx.target];
	if (ctx.target instanceof HTMLElement) return Array.from(ctx.target.querySelectorAll("[data-formie-field-handle]")).filter((element) => {
		return element instanceof HTMLElement;
	});
	return [];
}
function getModuleFieldTarget(ctx) {
	return getModuleFieldContainers(ctx)[0] || null;
}
function getFormValidator(form) {
	if (!form) return null;
	return form.formieValidation || null;
}
function retainFormValidators(form, key, register) {
	if (!form) return;
	const formRegistrations = validatorRegistrations.get(form) || /* @__PURE__ */ new Map();
	const currentCount = formRegistrations.get(key) || 0;
	if (currentCount === 0) {
		const validator = getFormValidator(form);
		if (validator) register(validator);
	}
	formRegistrations.set(key, currentCount + 1);
	validatorRegistrations.set(form, formRegistrations);
}
function releaseFormValidators(form, key, validatorNames) {
	if (!form) return;
	const formRegistrations = validatorRegistrations.get(form);
	const currentCount = formRegistrations?.get(key) || 0;
	if (currentCount <= 1) {
		const validator = getFormValidator(form);
		validatorNames.forEach((validatorName) => {
			validator?.removeValidator(validatorName);
		});
		formRegistrations?.delete(key);
		if (!formRegistrations || formRegistrations.size === 0) {
			validatorRegistrations.delete(form);
			return;
		}
		validatorRegistrations.set(form, formRegistrations);
		return;
	}
	formRegistrations?.set(key, currentCount - 1);
}
function getOwnerDocument(root) {
	return root.ownerDocument || document;
}
function getTemplateSource(root, templateId) {
	const doc = getOwnerDocument(root);
	if (templateId) {
		const explicitCandidates = [
			root.querySelector(`template[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
			root.querySelector(`script[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
			doc.querySelector(`template[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
			doc.querySelector(`script[data-formie-template-id="${escapeSelectorValue(templateId)}"]`),
			doc.getElementById(templateId)
		];
		for (const candidate of explicitCandidates) if (candidate instanceof HTMLTemplateElement || candidate instanceof HTMLScriptElement) return candidate;
	}
	return null;
}
function getTemplateSourceHtml(source) {
	if (source instanceof HTMLTemplateElement) return source.innerHTML;
	if (source instanceof HTMLScriptElement) return source.textContent || "";
	return source.innerHTML;
}
function dispatchFieldEvent(target, moduleId, name, detail) {
	const eventName = getFieldModuleEventName(moduleId, name);
	target.dispatchEvent(new CustomEvent(eventName, {
		bubbles: true,
		detail
	}));
}
//#endregion
export { getTemplateSource as a, retainFormValidators as c, getModuleFieldTarget as i, escapeSelectorValue as n, getTemplateSourceHtml as o, getModuleFieldContainers as r, releaseFormValidators as s, dispatchFieldEvent as t };
