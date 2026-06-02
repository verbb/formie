import { a as CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS, r as getScriptAttributes, t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { n as loadExternalScript, t as ensureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/captchas/hcaptcha.ts
async function loadHcaptchaGlobal(options) {
	const language = typeof options.language === "string" && options.language.trim() !== "" ? options.language.trim() : "en";
	const { async, defer } = getScriptAttributes(options.loadingMethod);
	const callbackName = "FORMIE_HCAPTCHA_ONLOAD";
	const globalWindow = window;
	const existing = globalWindow.hcaptcha;
	if (existing) return existing;
	const callbackPromise = new Promise((resolve) => {
		globalWindow[callbackName] = () => {
			delete globalWindow[callbackName];
			resolve();
		};
	});
	await loadExternalScript({
		id: "FORMIE_HCAPTCHA_SCRIPT",
		src: `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&onload=${encodeURIComponent(callbackName)}&hl=${encodeURIComponent(language)}`,
		async,
		defer
	});
	await callbackPromise;
	return ensureGlobal("hcaptcha", CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS);
}
var hcaptchaModule = defineCaptchaModule({
	id: "hcaptcha",
	defaultPlaceholderSelector: "[data-hcaptcha-placeholder]",
	defaultTokenFieldNames: ["h-captcha-response"],
	load: ({ options }) => {
		return loadHcaptchaGlobal(options.provider);
	},
	mount: ({ api, container, provider, services }) => {
		return api.render(container, {
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
			"chalexpired-callback": () => {
				services.tokens.clear();
				services.errors.clear();
			},
			"error-callback": () => {
				services.tokens.clear();
			}
		});
	},
	screen: ({ api, widget, placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		api.execute(widget);
		return services.tokens.wait().then((hasToken) => {
			if (!hasToken) {
				const message = services.errors.getDefaultMessage();
				services.errors.show(message, placeholder);
				stageCtx.abort(message);
			}
		});
	},
	unmount: ({ api, widget, services }) => {
		api.reset(widget);
		services.tokens.clear();
	}
});
//#endregion
export { hcaptchaModule };
