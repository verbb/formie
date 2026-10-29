import { a as e, i as t, r as n, t as r } from "./api-BPtH5RBA.js";
import { n as i, t as a } from "./scripts-DrccjrFv.js";
//#region src/js/modules/captchas/hcaptcha.ts
async function o(t) {
	let r = typeof t.language == "string" && t.language.trim() !== "" ? t.language.trim() : "en", { async: o, defer: s } = n(t.loadingMethod), c = "FORMIE_HCAPTCHA_ONLOAD", l = window, u = l.hcaptcha;
	if (u) return u;
	let d = new Promise((e) => {
		l[c] = () => {
			delete l[c], e();
		};
	});
	return await i({
		id: "FORMIE_HCAPTCHA_SCRIPT",
		src: `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&onload=FORMIE_HCAPTCHA_ONLOAD&hl=${encodeURIComponent(r)}`,
		async: o,
		defer: s
	}), await d, a("hcaptcha", e);
}
var s = r({
	moduleId: "formie:hcaptcha",
	defaultPlaceholderSelector: "[data-hcaptcha-placeholder]",
	defaultTokenFieldNames: ["h-captcha-response"],
	load: ({ options: e }) => o(e.provider),
	mount: ({ api: e, container: t, provider: n, services: r }) => e.render(t, {
		sitekey: n.siteKey || "",
		theme: n.theme || "light",
		size: n.size || "normal",
		callback: (e) => {
			typeof e == "string" && e.trim() !== "" && r.tokens.write(e.trim()), r.errors.clear();
		},
		"expired-callback": () => {
			r.tokens.clear(), r.errors.clear();
		},
		"chalexpired-callback": () => {
			r.tokens.clear(), r.errors.clear();
		},
		"error-callback": () => {
			r.tokens.clear();
		}
	}),
	challenge: ({ api: e, widget: n, placeholder: r, services: i, stageCtx: a }) => {
		if (!i.tokens.has()) return e.execute(n), i.tokens.wait(t).then((e) => {
			if (!e) {
				let e = i.errors.getDefaultMessage();
				i.errors.show(e, r), a.abort(e);
			}
		});
	},
	unmount: ({ api: e, widget: t, services: n }) => {
		e.reset(t), n.tokens.clear();
	}
});
//#endregion
export { s as hcaptchaModule };
