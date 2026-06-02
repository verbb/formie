import { t as defineCaptchaModule } from "./api-DOfDzYC_.js";
//#region src/js/modules/captchas/friendly-captcha-v1.ts
var friendlyCaptchaV1Module = defineCaptchaModule({
	id: "friendly-captcha-v1",
	defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
	defaultTokenFieldNames: ["frc-captcha-solution"],
	load: async () => {
		return import("./friendly-challenge-Dg8XkStd.js");
	},
	mount: ({ api, container, provider, services }) => {
		return new api.WidgetInstance(container, {
			sitekey: provider.siteKey || "",
			startMode: provider.startMode || "none",
			language: provider.language || "en",
			solutionFieldName: "frc-captcha-solution",
			doneCallback: (token) => {
				if (typeof token === "string" && token.trim() !== "") services.tokens.write(token.trim());
				services.errors.clear();
			},
			errorCallback: () => {
				services.tokens.clear();
			}
		});
	},
	screen: async ({ widget, placeholder, services, stageCtx }) => {
		if (services.tokens.has()) return;
		await widget.start();
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
export { friendlyCaptchaV1Module };
