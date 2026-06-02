import { a as CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS, t as defineCaptchaModule } from "./api-DOfDzYC_.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/captchas/captcha-eu.ts
async function loadCaptchaEuGlobal(options) {
	return loadScriptAndEnsureGlobal("KROT", {
		id: "FORMIE_CAPTCHA_EU_SCRIPT",
		src: `${String(options.endPoint || "https://www.captcha.eu").trim().replace(/\/+$/, "")}/sdk.js`,
		async: true,
		defer: true,
		timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS
	});
}
var captchaEuModule = defineCaptchaModule({
	id: "captcha-eu",
	defaultPlaceholderSelector: "[data-captcha-eu-placeholder]",
	defaultTokenFieldNames: ["captcha-eu-token"],
	load: ({ options }) => {
		return loadCaptchaEuGlobal(options.provider);
	},
	mount: ({ api, container, provider, services }) => {
		api.init();
		api.setup(String(provider.publicKey || ""));
		api.WidgetV2.render(container);
		api.on("CPT_OK", (event) => {
			services.tokens.write(JSON.stringify(event.detail || {}), { container });
			services.errors.clear();
		}, container);
		api.on("CPT_EXPIRED", () => {
			services.tokens.clear();
			services.errors.clear();
		}, container);
		return container;
	},
	screen: async ({ placeholder, services, stageCtx }) => {
		if (!await services.tokens.wait()) {
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
		}
	}
});
//#endregion
export { captchaEuModule };
