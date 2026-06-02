import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { t as loadRecaptchaGlobal } from "./recaptcha-shared-DTI4qWVR.js";
//#region src/js/modules/captchas/recaptcha-v3.ts
var recaptchaV3Module = defineCaptchaModule({
	id: "recaptcha-v3",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options }) => {
		return loadRecaptchaGlobal(options.provider, false, options.provider.siteKey || void 0);
	},
	mount: ({ api, provider }) => {
		return new Promise((resolve) => {
			api.ready(() => {
				resolve(provider.siteKey || "recaptcha-v3");
			});
		});
	},
	screen: async ({ api, provider, placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		const token = await api.execute(provider.siteKey || "", { action: provider.action || "submit" });
		if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
		if (!await services.tokens.wait(12e4)) {
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
		}
	},
	unmount: ({ services }) => {
		services.tokens.clear();
	}
});
//#endregion
export { recaptchaV3Module };
