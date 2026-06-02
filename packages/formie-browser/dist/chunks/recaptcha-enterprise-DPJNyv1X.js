import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { t as loadRecaptchaGlobal } from "./recaptcha-shared-DTI4qWVR.js";
//#region src/js/modules/captchas/recaptcha-enterprise.ts
var recaptchaEnterpriseModule = defineCaptchaModule({
	id: "recaptcha-enterprise",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options }) => {
		return loadRecaptchaGlobal(options.provider, true, options.provider.enterpriseType === "score" || options.provider.enterpriseType === "policy" ? options.provider.siteKey || void 0 : void 0);
	},
	mount: ({ api, container, provider, services }) => {
		const enterpriseApi = api.enterprise || api;
		return new Promise((resolve) => {
			enterpriseApi.ready(() => {
				if (provider.enterpriseType !== "checkbox") {
					resolve(provider.siteKey || `recaptcha-enterprise-${provider.enterpriseType || "score"}`);
					return;
				}
				resolve(enterpriseApi.render(container, {
					sitekey: provider.siteKey || "",
					theme: provider.theme || "light",
					badge: provider.badge || "bottomright",
					size: provider.size || "normal",
					action: provider.action || "submit",
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
	screen: async ({ api, widget, provider, placeholder, services, stageCtx }) => {
		const enterpriseApi = api.enterprise || api;
		if (provider.enterpriseType === "checkbox") {
			if (services.tokens.has()) return;
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
			return;
		}
		if (services.tokens.has()) return;
		if (provider.enterpriseType === "score" || provider.enterpriseType === "policy") {
			const token = await enterpriseApi.execute(provider.siteKey || "", { action: provider.action || "submit" });
			if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
		} else enterpriseApi.execute(widget);
		if (!await services.tokens.wait(12e4)) {
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
		}
	},
	reset: ({ api, widget, provider, services }) => {
		const enterpriseApi = api.enterprise || api;
		if (provider.enterpriseType === "checkbox") enterpriseApi.reset(widget);
		services.tokens.clear();
	},
	unmount: ({ api, widget, provider, services }) => {
		const enterpriseApi = api.enterprise || api;
		if (provider.enterpriseType === "checkbox") enterpriseApi.reset(widget);
		services.tokens.clear();
	}
});
//#endregion
export { recaptchaEnterpriseModule };
