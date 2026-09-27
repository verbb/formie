import { t as e } from "./api-CLDiLxn0.js";
import { t } from "./recaptcha-shared-Cc2JsfdH.js";
//#region src/js/modules/captchas/recaptcha-v2-checkbox.ts
var n = e({
	moduleId: "formie:recaptcha-v2-checkbox",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options: e }) => t(e.provider),
	mount: ({ api: e, container: t, provider: n, services: r }) => new Promise((i) => {
		e.ready(() => {
			i(e.render(t, {
				sitekey: n.siteKey || "",
				theme: n.theme || "light",
				size: n.size || "normal",
				callback: (e) => {
					typeof e == "string" && e.trim() !== "" && r.tokens.write(e.trim()), r.errors.clear();
				},
				"expired-callback": () => {
					r.tokens.clear(), r.errors.clear();
				},
				"error-callback": () => {
					r.tokens.clear();
				}
			}));
		});
	}),
	challenge: ({ placeholder: e, services: t, stageCtx: n }) => {
		if (t.tokens.has()) return;
		let r = t.errors.getDefaultMessage();
		t.errors.show(r, e), n.abort(r);
	},
	reset: ({ api: e, widget: t, services: n }) => {
		e.reset(t), n.tokens.clear();
	},
	unmount: ({ api: e, widget: t, services: n }) => {
		e.reset(t), n.tokens.clear();
	}
});
//#endregion
export { n as recaptchaV2CheckboxModule };
