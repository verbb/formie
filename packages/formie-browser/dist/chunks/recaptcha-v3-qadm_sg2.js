import { t as e } from "./api-C2fmBZFA.js";
import { n as t, t as n } from "./recaptcha-shared-CE_gRQGd.js";
//#region src/js/modules/captchas/recaptcha-v3.ts
var r = e({
	moduleId: "formie:recaptcha-v3",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options: e }) => n(e.provider),
	mount: ({ api: e, container: n, provider: r, services: i }) => new Promise((a, o) => {
		t(e, async () => {
			a(e.render(n, {
				sitekey: r.siteKey || "",
				size: "invisible",
				badge: r.badge || "bottomright",
				theme: r.theme || "light",
				"expired-callback": () => i.tokens.clear(),
				"error-callback": () => i.tokens.clear()
			}));
		}).catch(o);
	}),
	challenge: async ({ api: e, widget: n, provider: r, placeholder: i, services: a, stageCtx: o }) => {
		if (!a.tokens.has() && (await t(e, async () => {
			let t = await e.execute(n, { action: r.action || "submit" });
			typeof t == "string" && t.trim() !== "" && a.tokens.write(t.trim());
		}), !await a.tokens.wait(12e4))) {
			let e = a.errors.getDefaultMessage();
			a.errors.show(e, i), o.abort(e);
		}
	},
	reset: ({ api: e, widget: t, services: n }) => {
		e.reset(t), n.tokens.clear();
	},
	unmount: ({ api: e, widget: t, services: n }) => {
		e.reset(t), n.tokens.clear();
	}
});
//#endregion
export { r as recaptchaV3Module };
