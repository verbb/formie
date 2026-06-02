import { a as CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS, i as CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS, o as CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS, r as getScriptAttributes, t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/captchas/turnstile.ts
function getTurnstileWaitForValueMs(provider) {
	const appearance = provider.appearance || "always";
	return (provider.execution || (appearance === "execute" ? "execute" : "render")) === "execute" ? CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS : CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS;
}
async function loadTurnstileGlobal(options) {
	const { async, defer } = getScriptAttributes(options.loadingMethod);
	return loadScriptAndEnsureGlobal("turnstile", {
		id: "FORMIE_TURNSTILE_SCRIPT",
		src: "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit",
		async,
		defer,
		timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS
	});
}
var turnstileModule = defineCaptchaModule({
	id: "turnstile",
	defaultPlaceholderSelector: "[data-turnstile-placeholder]",
	defaultTokenFieldNames: ["cf-turnstile-response"],
	load: ({ options }) => {
		return loadTurnstileGlobal(options.provider);
	},
	mount: ({ api, container, provider, services }) => {
		const appearance = provider.appearance || "always";
		const execution = provider.execution || (appearance === "execute" ? "execute" : "render");
		return api.render(container, {
			sitekey: provider.siteKey || "",
			theme: provider.theme || "auto",
			size: provider.size || "normal",
			appearance,
			execution,
			callback: (token) => {
				if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
				services.errors.clear();
			},
			"expired-callback": () => {
				services.tokens.clear();
				services.errors.clear();
			},
			"timeout-callback": () => {
				services.tokens.clear();
				services.errors.clear();
			},
			"error-callback": () => {
				services.tokens.clear();
			}
		});
	},
	screen: ({ api, widget, placeholder, services, provider, stageCtx }) => {
		if (services.tokens.has()) return;
		api.execute(widget);
		return services.tokens.wait(getTurnstileWaitForValueMs(provider)).then((hasToken) => {
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
export { turnstileModule };
