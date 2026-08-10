import { t as e } from "./api-yyKZxh-a.js";
import { n as t, t as n } from "./recaptcha-shared-CztK2f0i.js";
//#region src/js/modules/captchas/recaptcha-enterprise.ts
var r = e({
	id: "recaptcha-enterprise",
	defaultPlaceholderSelector: "[data-recaptcha-placeholder]",
	defaultTokenFieldNames: ["g-recaptcha-response"],
	load: ({ options: e }) => n(e.provider, !0, (e.provider.enterpriseType === "score" || e.provider.enterpriseType === "policy") && e.provider.siteKey || void 0),
	mount: ({ api: e, container: t, provider: n, services: r }) => {
		let i = e.enterprise || e;
		return new Promise((e) => {
			i.ready(() => {
				if (n.enterpriseType !== "checkbox") {
					e(n.siteKey || `recaptcha-enterprise-${n.enterpriseType || "score"}`);
					return;
				}
				e(i.render(t, {
					sitekey: n.siteKey || "",
					theme: n.theme || "light",
					badge: n.badge || "bottomright",
					size: n.size || "normal",
					action: n.action || "submit",
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
		});
	},
	screen: async ({ api: e, widget: n, provider: r, placeholder: i, services: a, stageCtx: o }) => {
		let s = e.enterprise || e;
		if (r.enterpriseType === "checkbox") {
			if (a.tokens.has()) return;
			let e = a.errors.getDefaultMessage();
			a.errors.show(e, i), o.abort(e);
			return;
		}
		if (!a.tokens.has() && (await t(e, async () => {
			if (r.enterpriseType === "score" || r.enterpriseType === "policy") {
				let e = await s.execute(r.siteKey || "", { action: r.action || "submit" });
				typeof e == "string" && e.trim() !== "" && a.tokens.write(e.trim());
			} else s.execute(n);
		}), !await a.tokens.wait(12e4))) {
			let e = a.errors.getDefaultMessage();
			a.errors.show(e, i), o.abort(e);
		}
	},
	reset: ({ api: e, widget: t, provider: n, services: r }) => {
		let i = e.enterprise || e;
		n.enterpriseType === "checkbox" && i.reset(t), r.tokens.clear();
	},
	unmount: ({ api: e, widget: t, provider: n, services: r }) => {
		let i = e.enterprise || e;
		n.enterpriseType === "checkbox" && i.reset(t), r.tokens.clear();
	}
});
//#endregion
export { r as recaptchaEnterpriseModule };
