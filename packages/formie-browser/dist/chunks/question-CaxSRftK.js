//#region src/js/modules/captchas/question.ts
var e = "formieCaptchaQuestion", t = 0, n = {
	moduleId: "formie:question-captcha",
	version: 2,
	surfaces: ["client-rendered"],
	kind: "captcha",
	match: () => !0,
	setup: async (n) => {
		let r = n.options || {}, i = n.form ?? n.root, a = `formie-question-captcha-${++t}`, o = document.createElement("div");
		o.className = "formie-question-captcha", o.setAttribute("data-formie-question-captcha", "");
		let s = document.createElement("label");
		s.htmlFor = `${a}-answer`, s.textContent = r.question || "";
		let c = document.createElement("input");
		c.type = "text", c.id = `${a}-answer`, c.required = !0, c.autocomplete = "off", c.setAttribute("aria-required", "true"), c.setAttribute("data-formie-question-input", "");
		let l = document.createElement("p");
		l.id = `${a}-error`, l.className = "formie-question-captcha-error", l.setAttribute("role", "alert"), l.hidden = !0;
		let u = document.createElement("input");
		u.type = "hidden", u.name = e;
		let d = () => {
			u.value = JSON.stringify({
				index: r.index ?? 0,
				answer: c.value
			});
		}, f = (e) => {
			l.textContent = e || "", l.hidden = !e, c.toggleAttribute("aria-invalid", !!e), e ? c.setAttribute("aria-describedby", l.id) : c.removeAttribute("aria-describedby");
		}, p = () => {
			d(), f(null);
		};
		return d(), c.addEventListener("input", p), o.append(s, c, l, u), i.append(o), {
			beforeSubmit: (e) => {
				if (e.action !== "submit" || c.value.trim() !== "") return;
				let t = r.requiredMessage || "Please answer the security question.";
				f(t), c.focus(), e.abort(t);
			},
			afterSubmit: (t, n) => {
				let r = n.errors?.fields?.[e];
				f(Array.isArray(r) && r.length > 0 ? r[0] : null);
			},
			destroy: () => {
				c.removeEventListener("input", p), o.remove();
			}
		};
	}
};
//#endregion
export { n as questionCaptchaModule };
