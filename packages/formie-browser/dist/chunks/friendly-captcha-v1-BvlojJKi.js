import { t as e } from "./api-BBbsV96E.js";
//#region src/js/modules/captchas/friendly-captcha-v1.ts
var t = e({
	id: "friendly-captcha-v1",
	defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
	defaultTokenFieldNames: ["frc-captcha-solution"],
	load: async () => import("./friendly-challenge-DxQDBa72.js"),
	mount: ({ api: e, container: t, provider: n, services: r }) => new e.WidgetInstance(t, {
		sitekey: n.siteKey || "",
		startMode: n.startMode || "none",
		language: n.language || "en",
		solutionFieldName: "frc-captcha-solution",
		doneCallback: (e) => {
			typeof e == "string" && e.trim() !== "" && r.tokens.write(e.trim()), r.errors.clear();
		},
		errorCallback: () => {
			r.tokens.clear();
		}
	}),
	screen: async ({ widget: e, placeholder: t, services: n, stageCtx: r }) => {
		if (!n.tokens.has() && (await e.start(), !await n.tokens.wait())) {
			let e = n.errors.getDefaultMessage();
			n.errors.show(e, t), r.abort(e);
		}
	},
	unmount: ({ widget: e, services: t }) => {
		e.destroy(), t.tokens.clear();
	}
});
//#endregion
export { t as friendlyCaptchaV1Module };
