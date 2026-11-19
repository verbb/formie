import { t as e } from "./api-BPtH5RBA.js";
import { n as t, t as n } from "./friendly-captcha-shared-BVcd0Bak.js";
//#region src/js/modules/captchas/friendly-captcha-v1.ts
var r = e({
	moduleId: "formie:friendly-captcha-v1",
	defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
	defaultTokenFieldNames: ["frc-captcha-solution"],
	load: async () => import("./friendly-challenge-BgPBEMXx.js"),
	mount: ({ api: e, container: t, provider: n, services: r }) => {
		let i = n.theme || "auto", a = window.matchMedia?.("(prefers-color-scheme: dark)").matches ?? !1;
		return (i === "dark" || i === "auto" && a) && t.classList.add("dark"), new e.WidgetInstance(t, {
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
		});
	},
	challenge: async ({ widget: e, placeholder: r, services: i, stageCtx: a }) => {
		if (!n(i) && (await e.start(), !await t(i))) {
			let e = i.errors.getDefaultMessage();
			i.errors.show(e, r), a.abort(e);
		}
	},
	unmount: ({ widget: e, services: t }) => {
		e.destroy(), t.tokens.clear();
	}
});
//#endregion
export { r as friendlyCaptchaV1Module };
