import { a as CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS, r as getScriptAttributes } from "./api-DOfDzYC_.js";
import { r as loadScriptAndEnsureGlobal } from "./scripts-BGD_iU_6.js";
//#region src/js/modules/captchas/recaptcha-shared.ts
async function loadRecaptchaGlobal(options, enterprise = false, renderValue) {
	const language = typeof options.language === "string" && options.language.trim() !== "" ? options.language.trim() : "en";
	const { async, defer } = getScriptAttributes(options.loadingMethod);
	const host = enterprise ? "https://www.google.com/recaptcha/enterprise.js" : "https://www.recaptcha.net/recaptcha/api.js";
	const render = typeof renderValue === "string" && renderValue.trim() !== "" ? renderValue.trim() : "explicit";
	const src = new URL(host);
	src.searchParams.set("render", render);
	src.searchParams.set("hl", language);
	if (render !== "explicit" && typeof options.badge === "string" && options.badge.trim() !== "") src.searchParams.set("badge", options.badge.trim());
	return loadScriptAndEnsureGlobal("grecaptcha", {
		id: enterprise ? "FORMIE_RECAPTCHA_ENTERPRISE_SCRIPT" : "FORMIE_RECAPTCHA_SCRIPT",
		src: src.toString(),
		async,
		defer,
		timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS
	});
}
//#endregion
export { loadRecaptchaGlobal as t };
