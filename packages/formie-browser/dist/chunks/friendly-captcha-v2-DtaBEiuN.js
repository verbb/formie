import { t as e } from "./api-BPtH5RBA.js";
import { n as t, t as n } from "./friendly-captcha-shared-BVcd0Bak.js";
//#region src/js/modules/captchas/friendly-captcha-v2.ts
var r = e({
	moduleId: "formie:friendly-captcha-v2",
	defaultPlaceholderSelector: "[data-friendly-captcha-placeholder]",
	defaultTokenFieldNames: ["frc-captcha-response"],
	load: async () => import("./sdk-_cmrRYgp.js"),
	mount: ({ api: e, container: t, provider: n, services: r }) => {
		let i = new e.FriendlyCaptchaSDK().createWidget({
			element: t,
			sitekey: n.siteKey || "",
			formFieldName: "frc-captcha-response",
			language: n.language,
			startMode: n.startMode || "none",
			theme: n.theme || "auto"
		});
		return i.addEventListener("frc:widget.complete", (e) => {
			let t = e.detail;
			typeof t?.response == "string" && t.response.trim() !== "" && r.tokens.write(t.response.trim()), r.errors.clear();
		}), i.addEventListener("frc:widget.expire", () => {
			r.tokens.clear(), r.errors.clear();
		}), i.addEventListener("frc:widget.error", (e) => {
			e.detail?.error && r.tokens.clear();
		}), i;
	},
	challenge: async ({ widget: e, placeholder: r, services: i, stageCtx: a }) => {
		if (!n(i) && (e.start(), !await t(i))) {
			let e = i.errors.getDefaultMessage();
			i.errors.show(e, r), a.abort(e);
		}
	},
	unmount: ({ widget: e, services: t }) => {
		e.destroy(), t.tokens.clear();
	}
});
//#endregion
export { r as friendlyCaptchaV2Module };
