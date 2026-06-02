import { r as getModuleFieldContainers } from "./shared-DC6_1u8X.js";
//#region src/js/modules/fields/hidden.ts
var INPUT_SELECTOR = "input[data-formie-hidden-input]";
function getCookieValue(name) {
	const cookies = document.cookie ? document.cookie.split("; ") : [];
	for (const cookie of cookies) {
		const parts = cookie.split("=");
		if (decodeURIComponent(parts.shift() || "") === name) return decodeURIComponent(parts.join("="));
	}
	return null;
}
var hiddenModule = {
	id: "hidden",
	kind: "field",
	match: (ctx) => {
		return !!ctx.target.querySelector(INPUT_SELECTOR);
	},
	setup: async (ctx) => {
		const options = ctx.options || {};
		const cookieValue = options.cookieName ? getCookieValue(options.cookieName) : null;
		const inputs = getModuleFieldContainers(ctx).map((field) => {
			return field.querySelector(INPUT_SELECTOR);
		}).filter((input) => {
			return input instanceof HTMLInputElement;
		});
		if (cookieValue !== null) inputs.forEach((input) => {
			input.value = cookieValue;
		});
		await ctx.emit("formie:module:hidden:init", { count: inputs.length });
		return { destroy: () => {
			ctx.emit("formie:module:hidden:destroy", {});
		} };
	}
};
//#endregion
export { hiddenModule };
