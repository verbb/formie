import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
//#region src/js/modules/captchas/friendly-captcha-v2.ts
var friendlyCaptchaV2Module = defineCaptchaModule({
	id: "friendly-captcha-v2",
	defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
	defaultTokenFieldNames: ["frc-captcha-response"],
	load: async () => {
		return import("./sdk-B7u9fTlP.js");
	},
	mount: ({ api, container, provider, services }) => {
		const widget = new api.FriendlyCaptchaSDK().createWidget({
			element: container,
			sitekey: provider.siteKey || "",
			formFieldName: "frc-captcha-response",
			language: provider.language,
			startMode: provider.startMode || "none"
		});
		widget.addEventListener("frc:widget.complete", (event) => {
			const detail = event.detail;
			if (typeof detail?.response === "string" && detail.response.trim() !== "") services.tokens.write(detail.response.trim());
			services.errors.clear();
		});
		widget.addEventListener("frc:widget.expire", () => {
			services.tokens.clear();
			services.errors.clear();
		});
		widget.addEventListener("frc:widget.error", (event) => {
			if (event.detail?.error) services.tokens.clear();
		});
		return widget;
	},
	screen: async ({ widget, placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		widget.start();
		if (!await services.tokens.wait()) {
			const message = services.errors.getDefaultMessage();
			services.errors.show(message, placeholder);
			stageCtx.abort(message);
		}
	},
	unmount: ({ widget, services }) => {
		widget.destroy();
		services.tokens.clear();
	}
});
//#endregion
export { friendlyCaptchaV2Module };
