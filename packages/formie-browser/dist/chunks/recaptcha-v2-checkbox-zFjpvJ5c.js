import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { t as loadRecaptchaGlobal } from "./recaptcha-shared-DTI4qWVR.js";
//#region src/js/modules/captchas/recaptcha-v2-checkbox.ts
var recaptchaV2CheckboxModule = defineCaptchaModule({
	id: "recaptcha-v2-checkbox",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options }) => {
		return loadRecaptchaGlobal(options.provider);
	},
	mount: ({ api, container, provider, services }) => {
		return new Promise((resolve) => {
			api.ready(() => {
				resolve(api.render(container, {
					sitekey: provider.siteKey || "",
					theme: provider.theme || "light",
					size: provider.size || "normal",
					callback: (token) => {
						if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
						services.errors.clear();
					},
					"expired-callback": () => {
						services.tokens.clear();
						services.errors.clear();
					},
					"error-callback": () => {
						services.tokens.clear();
					}
				}));
			});
		});
	},
	screen: ({ placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		const message = services.errors.getDefaultMessage();
		services.errors.show(message, placeholder);
		stageCtx.abort(message);
	},
	reset: ({ api, widget, services }) => {
		api.reset(widget);
		services.tokens.clear();
	},
	unmount: ({ api, widget, services }) => {
		api.reset(widget);
		services.tokens.clear();
	}
});
//#endregion
export { recaptchaV2CheckboxModule };
