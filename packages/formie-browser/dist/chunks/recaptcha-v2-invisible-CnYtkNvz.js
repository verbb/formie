import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { t as loadRecaptchaGlobal } from "./recaptcha-shared-DTI4qWVR.js";
//#region src/js/modules/captchas/recaptcha-v2-invisible.ts
async function waitForRecaptchaResponse(api, widgetId, timeoutMs = 1e3) {
	const deadline = Date.now() + timeoutMs;
	while (Date.now() < deadline) {
		const response = typeof api.getResponse === "function" ? api.getResponse(widgetId) : "";
		if (typeof response === "string" && response.trim() !== "") return response.trim();
		await new Promise((resolve) => {
			window.setTimeout(resolve, 100);
		});
	}
}
var recaptchaV2InvisibleModule = defineCaptchaModule({
	id: "recaptcha-v2-invisible",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options }) => {
		return loadRecaptchaGlobal(options.provider);
	},
	mount: ({ api, container, provider, services }) => {
		return new Promise((resolve) => {
			api.ready(() => {
				const widgetId = api.render(container, {
					sitekey: provider.siteKey || "",
					badge: provider.badge || "bottomright",
					size: "invisible",
					callback: (token) => {
						const response = typeof token === "string" && token.trim() !== "" ? token.trim() : typeof api.getResponse === "function" ? api.getResponse(widgetId) : "";
						if (response) services.tokens.write(response);
						services.errors.clear();
					},
					"expired-callback": () => {
						services.tokens.clear();
						services.errors.clear();
					},
					"error-callback": () => {
						services.tokens.clear();
					}
				});
				resolve({ id: widgetId });
			});
		});
	},
	screen: async ({ api, widget, placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		api.execute(widget.id);
		const token = await waitForRecaptchaResponse(api, widget.id);
		if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
		if (!await services.tokens.wait(12e4)) {
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
		}
	},
	unmount: ({ api, widget, services }) => {
		api.reset(widget.id);
		services.tokens.clear();
	}
});
//#endregion
export { recaptchaV2InvisibleModule };
