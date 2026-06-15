import { t as e } from "./api-BUb6P-xu.js";
import { n as t, t as n } from "./recaptcha-shared-CLYJvojk.js";
//#region src/js/modules/captchas/recaptcha-v3.ts
var r = e({
	id: "recaptcha-v3",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options: e }) => n(e.provider, !1, e.provider.siteKey || void 0),
	mount: ({ api: e, provider: t }) => new Promise((n) => {
		e.ready(() => {
			n(t.siteKey || "recaptcha-v3");
		});
	}),
	screen: async ({ api: e, provider: n, placeholder: r, services: i, stageCtx: a }) => {
		if (!i.tokens.has() && (await t(e, async () => {
			let t = await e.execute(n.siteKey || "", { action: n.action || "submit" });
			typeof t == "string" && t.trim() !== "" && i.tokens.write(t.trim());
		}), !await i.tokens.wait(12e4))) {
			let e = i.errors.getDefaultMessage();
			i.errors.show(e, r), a.abort(e);
		}
	},
	unmount: ({ services: e }) => {
		e.tokens.clear();
	}
});
//#endregion
export { r as recaptchaV3Module };
