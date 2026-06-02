import { t as ensureModuleStyles } from "./styles-BIh6g7V_.js";
import { r as Le } from "./dist-D09GnXMW.js";
import { c as retainFormValidators, i as getModuleFieldTarget, s as releaseFormValidators } from "./shared-DC6_1u8X.js";
//#region src/css/theme/fields/_text-limit.css?inline
var _text_limit_default = "@layer formie-theme{.formie-limit-number{font-weight:var(--formie-font-weight-semibold);color:var(--formie-color-text)}.formie-limit-number-error{color:var(--formie-color-danger)}}";
//#endregion
//#region src/js/modules/fields/text-limit.ts
var INPUT_SELECTOR = "input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]";
var TEXT_LIMIT_VALIDATORS = [
	"textMinCharacterLimit",
	"textMaxCharacterLimit",
	"textMinWordLimit",
	"textMaxWordLimit"
];
var VALIDATOR_SCOPE = "text-limit";
var ALLOW_OVERTYPE_ATTR = "data-formie-text-limit-allow-overtype";
var limitTargetCache = /* @__PURE__ */ new WeakMap();
ensureModuleStyles("text-limit", [_text_limit_default]);
function isTextLimitInput(input) {
	return input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement;
}
function getLimitValue(input, attribute) {
	return parseInt(input.getAttribute(attribute) || "", 10) || 0;
}
function hasAnyLimitAttributes(input) {
	return input.hasAttribute("data-formie-min-chars") || input.hasAttribute("data-formie-max-chars") || input.hasAttribute("data-formie-min-words") || input.hasAttribute("data-formie-max-words");
}
function hasCounterLimitAttributes(input) {
	return input.hasAttribute("data-formie-max-chars") || input.hasAttribute("data-formie-max-words");
}
function allowsOvertype(input) {
	return input.hasAttribute(ALLOW_OVERTYPE_ATTR);
}
function hasNoRawValue(input) {
	return input.value === "";
}
function registerValidators(form) {
	retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
		validator.addValidator("textMinCharacterLimit", ({ input }) => {
			if (!isTextLimitInput(input)) return true;
			const limit = getLimitValue(input, "data-formie-min-chars");
			if (!limit || hasNoRawValue(input)) return true;
			return Le(input.value).graphemeCount >= limit;
		}, ({ label, input, t }) => {
			return t("{attribute} must be no less than {min} characters.", {
				attribute: label,
				min: input.getAttribute("data-formie-min-chars") || ""
			});
		});
		validator.addValidator("textMaxCharacterLimit", ({ input }) => {
			if (!isTextLimitInput(input)) return true;
			if (allowsOvertype(input)) return true;
			const limit = getLimitValue(input, "data-formie-max-chars");
			if (!limit || hasNoRawValue(input)) return true;
			return Le(input.value).graphemeCount <= limit;
		}, ({ label, input, t }) => {
			return t("{attribute} must be no greater than {max} characters.", {
				attribute: label,
				max: input.getAttribute("data-formie-max-chars") || ""
			});
		});
		validator.addValidator("textMinWordLimit", ({ input }) => {
			if (!isTextLimitInput(input)) return true;
			const limit = getLimitValue(input, "data-formie-min-words");
			if (!limit || input.value.trim() === "") return true;
			return Le(input.value).wordCount >= limit;
		}, ({ label, input, t }) => {
			return t("{attribute} must be no less than {min} words.", {
				attribute: label,
				min: input.getAttribute("data-formie-min-words") || ""
			});
		});
		validator.addValidator("textMaxWordLimit", ({ input }) => {
			if (!isTextLimitInput(input)) return true;
			if (allowsOvertype(input)) return true;
			const limit = getLimitValue(input, "data-formie-max-words");
			if (!limit || input.value.trim() === "") return true;
			return Le(input.value).wordCount <= limit;
		}, ({ label, input, t }) => {
			return t("{attribute} must be no greater than {max} words.", {
				attribute: label,
				max: input.getAttribute("data-formie-max-words") || ""
			});
		});
	});
}
function unregisterValidators(form) {
	releaseFormValidators(form, VALIDATOR_SCOPE, TEXT_LIMIT_VALIDATORS);
}
function getLimitTarget(input) {
	if (limitTargetCache.has(input)) return limitTargetCache.get(input) || null;
	const field = input.closest("[data-formie-field-handle]");
	if (!field) {
		limitTargetCache.set(input, null);
		return null;
	}
	const existingTarget = field.querySelector("[data-formie-limit-text]");
	if (existingTarget) {
		limitTargetCache.set(input, existingTarget);
		return existingTarget;
	}
	const control = field.querySelector("[data-formie-field-control]");
	const target = document.createElement("div");
	target.className = "formie-field-limit formie-limit-text";
	target.setAttribute("data-formie-field-limit", "true");
	target.setAttribute("data-formie-limit-text", "true");
	if (control?.parentElement) {
		control.insertAdjacentElement("afterend", target);
		limitTargetCache.set(input, target);
		return target;
	}
	field.appendChild(target);
	limitTargetCache.set(input, target);
	return target;
}
function renderCounter(target, remaining, unit) {
	const number = document.createElement("span");
	number.className = remaining < 0 ? "formie-limit-number formie-limit-number-error" : "formie-limit-number";
	number.textContent = String(remaining);
	target.replaceChildren(number, document.createTextNode(` ${Math.abs(remaining) === 1 ? unit : `${unit}s`} left`));
}
function updateCounter(input) {
	const maxChars = getLimitValue(input, "data-formie-max-chars");
	const maxWords = getLimitValue(input, "data-formie-max-words");
	const target = getLimitTarget(input);
	if (!target) return;
	const metrics = Le(input.value);
	if (maxChars > 0) {
		renderCounter(target, maxChars - metrics.graphemeCount, "character");
		return;
	}
	if (maxWords > 0) renderCounter(target, maxWords - metrics.wordCount, "word");
}
var textLimitModule = {
	id: "text-limit",
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(INPUT_SELECTOR);
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		const field = getModuleFieldTarget(ctx);
		const inputs = Array.from((field || ctx.target).querySelectorAll(INPUT_SELECTOR)).filter((input) => {
			return (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) && hasAnyLimitAttributes(input);
		});
		const counterInputs = inputs.filter((input) => {
			return hasCounterLimitAttributes(input);
		});
		if (options.allowOvertype) inputs.forEach((input) => {
			input.setAttribute(ALLOW_OVERTYPE_ATTR, "true");
		});
		registerValidators(ctx.form);
		const unbinds = counterInputs.map((input) => {
			const handler = () => {
				updateCounter(input);
			};
			input.addEventListener("input", handler);
			input.addEventListener("change", handler);
			updateCounter(input);
			return () => {
				input.removeEventListener("input", handler);
				input.removeEventListener("change", handler);
			};
		});
		await ctx.emit("formie:module:text-limit:init", { count: inputs.length });
		return { destroy: () => {
			unbinds.forEach((unbind) => {
				unbind();
			});
			if (options.allowOvertype) inputs.forEach((input) => {
				input.removeAttribute(ALLOW_OVERTYPE_ATTR);
			});
			unregisterValidators(ctx.form);
			ctx.emit("formie:module:text-limit:destroy", {});
		} };
	}
};
//#endregion
export { textLimitModule };
