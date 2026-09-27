import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { i as t, m as n, t as r } from "./chunks/dist-1mhMV4JB.js";
import { c as i, d as a, l as o, o as s, r as c, t as l, u } from "./chunks/event-names-BCI2FLD8.js";
import { a as d, c as f, d as p, f as m, i as h, l as g, n as _, o as v, p as y, r as b, s as ee, t as x, u as S } from "./chunks/api-CCpJm3qd.js";
import { a as C, i as te, n as w, r as T, t as E } from "./chunks/debug-BV0DvdHx.js";
import { n as ne, r as D, t as O } from "./chunks/theme-classes-Tv7q7ToE.js";
import { i as k, t as re } from "./chunks/csrf-DxHg_ZYt.js";
import { n as A, t as ie } from "./chunks/http-BslIJLrj.js";
import { a as ae, i as oe, n as se, r as ce, t as le } from "./chunks/i18n-BY1ds1BL.js";
import { n as ue, t as de } from "./chunks/api-DYrLvcqr.js";
import { n as fe, r as pe, t as me } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as he, n as ge, r as _e, t as ve } from "./chunks/field-references.resolver--GdIlYhd.js";
import { t as ye } from "./chunks/api-CXzW6J-X.js";
//#region src/js/compatibility/event-map.ts
var be = [
	{
		legacyEvent: "onFormieLoaded",
		canonicalEvent: "formie:mount:after",
		disposition: "approximate",
		target: "document"
	},
	{
		legacyEvent: "onFormieInit",
		canonicalEvent: "formie:mount:after",
		disposition: "approximate",
		target: "document"
	},
	{
		legacyEvent: "onFormieReady",
		canonicalEvent: "formie:mount:after",
		disposition: "safe"
	},
	{
		legacyEvent: "onAfterFormieSubmit",
		canonicalEvent: "formie:submit:result",
		disposition: "safe"
	},
	{
		legacyEvent: "onFormieSubmitError",
		canonicalEvent: "formie:submit:result",
		disposition: "safe"
	},
	{
		legacyEvent: "onFormiePageToggle",
		canonicalEvent: "formie:page:navigate:after",
		disposition: "safe"
	},
	{
		legacyEvent: "onBeforeFormieSubmit",
		canonicalEvent: "formie:submit:before",
		disposition: "approximate"
	},
	{
		legacyEvent: "onFormieValidate",
		canonicalEvent: "formie:stage:validate:before",
		disposition: "approximate"
	},
	{
		legacyEvent: "onAfterFormieValidate",
		canonicalEvent: "formie:stage:validate:after",
		disposition: "approximate"
	},
	{
		legacyEvent: "onFormieSubmit",
		canonicalEvent: "formie:submit:after",
		disposition: "approximate"
	}
], xe = [
	{
		legacyEvent: "formieValidatorInitialized",
		canonicalEvent: "formie:validator:ready",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorDestroyed",
		canonicalEvent: "formie:validator:destroy",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorShowError",
		canonicalEvent: "formie:validator:show-error",
		disposition: "safe"
	},
	{
		legacyEvent: "formieValidatorClearError",
		canonicalEvent: "formie:validator:clear-error",
		disposition: "safe"
	}
];
function Se(e) {
	if (!e) return {
		enabled: !1,
		legacyDomEvents: !1,
		legacyValidatorEvents: !1
	};
	if (e === !0) return {
		enabled: !0,
		legacyDomEvents: !0,
		legacyValidatorEvents: !0
	};
	let t = e.legacyDomEvents ?? !0, n = e.legacyValidatorEvents ?? !0;
	return {
		enabled: t || n,
		legacyDomEvents: t,
		legacyValidatorEvents: n
	};
}
//#endregion
//#region src/js/compatibility/dom-adapter.ts
function Ce(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function we(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function Te(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function Ee(e, t, n, r, i) {
	let a = globalThis.Formie || i;
	return e.legacyEvent === "onFormieLoaded" ? { formie: a } : e.legacyEvent === "onFormieInit" ? {
		formie: a,
		form: i,
		$form: r,
		formId: i.id
	} : e.legacyEvent === "onFormieReady" ? {
		...t && typeof t == "object" ? t : {},
		form: r,
		target: n,
		instance: i
	} : e.legacyEvent === "onFormiePageToggle" ? Te(r, t) : t;
}
function De({ target: e, form: t, instance: n, options: r, unbinds: i }) {
	r.legacyDomEvents && be.forEach((r) => {
		let o = (i) => {
			i instanceof CustomEvent && we(r, i.detail) && Ce(r.target === "document" ? document : t, r.legacyEvent, Ee(r, i.detail, e, t, n));
		};
		e.addEventListener(a(r.canonicalEvent), o), i.push(() => {
			e.removeEventListener(a(r.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function j(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Oe(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function ke({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	j(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && Oe(e.detail, a) && j(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && Oe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && j(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && Oe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && j(n.target, "formieValidatorClearError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	};
	document.addEventListener("formie:validator:destroy", l), document.addEventListener("formie:validator:show-error", u), document.addEventListener("formie:validator:clear-error", d), i.push(() => {
		document.removeEventListener("formie:validator:destroy", l), document.removeEventListener("formie:validator:show-error", u), document.removeEventListener("formie:validator:clear-error", d);
	});
}
//#endregion
//#region src/js/core/error-aria-live.ts
function Ae(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function je(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Me(e) {
	return e === "off" ? null : e;
}
function M(e, t) {
	if (t) {
		e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true");
		return;
	}
	e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic");
}
//#endregion
//#region src/js/core/field-error-aria.ts
function Ne(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Pe(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	if (r.length) {
		e.setAttribute("aria-describedby", r.join(" "));
		return;
	}
	e.removeAttribute("aria-describedby");
}
function Fe(e, t) {
	e.setAttribute("aria-errormessage", t), Ne(e, t);
}
function Ie(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), Pe(e);
}
function N(e) {
	return !!e && e.hasAttribute("data-formie-validation-skip");
}
//#endregion
//#region src/js/core/validation-focus.ts
function Le(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Re(e) {
	return Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).find((e) => !N(e)) || (Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")).find((e) => !N(e)) ?? null);
}
function ze(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function Be(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t;
		if (n.getAttribute("data-formie-field-has-error") !== "true" && n.querySelector("[data-formie-field-error]") === null) return;
		n.setAttribute("data-formie-field-has-error", "true"), O(n, e, "fieldLayoutError");
		let r = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let i = t;
			if (N(i)) return;
			i.setAttribute("aria-invalid", "true"), O(i, e, "fieldControlError"), i.setAttribute("data-formie-input-has-error", "true"), r && Fe(i, r);
			let a = n.querySelector("[data-formie-instructions]");
			a?.id && Ne(i, a.id);
		});
	});
}
function Ve(e) {
	return !!Le(e) || !!ze(e);
}
function P(e) {
	let t = Le(e);
	if (t) {
		let e = Re(t);
		if (e) {
			if (e.scrollIntoView({
				behavior: "smooth",
				block: "center"
			}), typeof e.focus == "function") try {
				e.focus({ preventScroll: !0 });
			} catch {
				e.focus();
			}
			return !0;
		}
		return t.scrollIntoView({
			behavior: "smooth",
			block: "center"
		}), !0;
	}
	let n = ze(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/transport/forms-api.ts
var F = E("general", "transport");
function He(e) {
	let t = {};
	return [
		"theme",
		"themeConfig",
		"locale",
		"siteId"
	].forEach((n) => {
		e[n] !== void 0 && (t[n] = e[n]);
	}), t;
}
function Ue(e, t) {
	let n = e.success === !0, r = e.keepSubmitLoading === !0, i = e.errors, a = Object.fromEntries(Object.entries(i && typeof i == "object" ? i : {}).map(([e, t]) => [e, Array.isArray(t) ? t.filter((e) => typeof e == "string") : []])), o = a.form || [], s = {};
	Object.entries(a).forEach(([e, t]) => {
		e !== "form" && (s[e] = t);
	});
	let c = !n && o.length === 0 && Object.keys(s).length > 0 ? [t || "Submission failed."] : o, l = !n && r && c.length === 0 && Object.keys(s).length === 0;
	return {
		ok: n,
		outcome: typeof e.outcome == "string" ? e.outcome : void 0,
		version: typeof e.version == "number" ? e.version : null,
		submissionUid: typeof e.submissionUid == "string" ? e.submissionUid : null,
		errors: e.errors,
		session: e.session,
		completion: e.completion,
		action: e.submitAction === "back" || e.submitAction === "save" || e.submitAction === "submit" ? e.submitAction : void 0,
		message: e.submitActionMessage || (n ? "Submission completed." : l ? "" : c[0] || "Submission failed."),
		code: n ? void 0 : String(e.code || "SUBMIT_ERROR"),
		keepSubmitLoading: r,
		fieldErrors: Object.keys(s).length ? s : void 0,
		formErrors: c.length ? c : void 0,
		nextPage: e.nextPageId ? { id: String(e.nextPageId) } : null,
		redirect: e.redirectUrl ? {
			url: String(e.redirectUrl),
			target: e.submitActionTab === "new-tab" ? "new-tab" : "same-tab"
		} : null,
		submitData: Array.isArray(e.submitData) ? e.submitData : void 0,
		clientEvents: Array.isArray(e.clientEvents) ? e.clientEvents : void 0,
		meta: e
	};
}
async function We(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	F.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await A(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	return F.log("requestRender complete.", { hasHtml: !!a.html }), a;
}
async function Ge(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: He(n)
		}
	});
	F.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await A(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(a.errors) && a.errors.length > 0) throw Error(a.errors.map((e) => e.message || "Unknown GraphQL error").join("; "));
	if (!a.data?.formieHtmlForm) throw Error(`Form not found for handle "${t}".`);
	let o = a.data.formieHtmlForm;
	return F.log("requestGraphqlRender complete.", { hasHtml: !!o.html }), o;
}
async function I(e, t, n, r = {}, i) {
	let a = new URL(e, window.location.origin);
	a.searchParams.set("handle", t), n && a.searchParams.set("renderId", n), i && a.searchParams.set("requestToken", i), F.log("requestRefreshTokens start.", {
		endpoint: a.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let o = await A(a.toString(), r);
	return F.log("requestRefreshTokens complete.", { hasRefreshTokens: !!o.refreshTokens }), o.refreshTokens || o;
}
async function Ke(e, t, n) {
	let r = new URL(e, window.location.origin), i = new FormData();
	if (n && i.append("pageId", n), t) {
		[
			"handle",
			"renderId",
			"draftContextToken",
			"draftContext",
			"progressId",
			"requestToken",
			"expectedVersion"
		].forEach((e) => {
			let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
			n && i.append(e, n);
		}), re(i, t);
		for (let [e, n] of new FormData(t)) e.startsWith("fields[") && i.append(e, n);
	}
	F.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await (await ie(r.toString(), {
		method: "POST",
		body: i,
		profile: t?.dataset.formieRequestProfile
	})).json();
	if (t && a.session) {
		let e = a.session, n = t.querySelector("input[name=\"expectedVersion\"]"), r = t.querySelector("input[name=\"requestToken\"]");
		n && (n.value = String(e.version)), r && e.tokens?.request && (r.value = e.tokens.request);
	}
	return F.log("requestSetPage complete.", a), a;
}
function qe(e, t) {
	let n = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && i.append(e, n);
	}), re(i, t), F.log("clearSubmissionOnUnload start.", { requestUrl: n.toString() });
	try {
		if (t.dataset.formieRequestProfile !== "cross-origin-public" && typeof navigator.sendBeacon == "function" && navigator.sendBeacon(n.toString(), i)) return;
	} catch {}
	r(n.toString(), {
		method: "POST",
		body: i,
		keepalive: !0,
		headers: { Accept: "application/json" }
	}, { profile: t.dataset.formieRequestProfile });
}
async function Je(e, t) {
	let n = (e.getAttribute("method") || "POST").toUpperCase(), i = e.getAttribute("action") || window.location.href, a = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	F.log("submitForm start.", {
		method: n,
		action: i,
		submitAction: t.get("submitAction")
	});
	let o = await r(i, {
		method: n,
		body: t,
		headers: { Accept: "application/json" }
	}, { profile: e.dataset.formieRequestProfile }), s = o.headers.get("content-type") || "";
	if (!s.includes("application/json")) return o.ok ? (F.log("submitForm non-JSON success response.", {
		status: o.status,
		contentType: s
	}), {
		ok: !0,
		message: "Submission completed."
	}) : (F.warn("submitForm non-JSON HTTP error.", {
		status: o.status,
		contentType: s
	}), {
		ok: !1,
		code: "HTTP_ERROR",
		message: `Request failed (${o.status}).`,
		formErrors: [`Request failed (${o.status}).`]
	});
	let c = Ue(await o.json(), a);
	return F.log("submitForm JSON response normalized.", {
		ok: c.ok,
		code: c.code,
		hasRedirect: !!c.redirect?.url,
		hasSubmitData: Array.isArray(c.submitData) && c.submitData.length > 0
	}), c;
}
//#endregion
//#region src/js/submit/pipeline.ts
var Ye = [
	"prepare",
	"validate",
	"challenge",
	"payment",
	"send",
	"result"
], Xe = [
	"prepare",
	"validate",
	"challenge",
	"payment"
], L = E("general", "pipeline");
function Ze(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function Qe(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function $e(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function et(e, t) {
	if (t instanceof HTMLInputElement) {
		if (t.type === "file") {
			Array.from(t.files || []).forEach((n) => {
				e.append(t.name, n);
			});
			return;
		}
		e.append(t.name, t.value);
		return;
	}
	if (t instanceof HTMLSelectElement && t.multiple) {
		Array.from(t.selectedOptions).forEach((n) => {
			e.append(t.name, n.value);
		});
		return;
	}
	e.append(t.name, t.value);
}
function tt(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = Qe(t) ? t : null;
		n && !n.closest("[data-formie-page]") && $e(n) && et(e, n);
	});
}
function nt(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = Qe(t) ? t : null;
		r && r.name && !r.disabled && (r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), $e(r) && et(e, r)));
	}), n;
}
function rt(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function it(e, t) {
	let n = f(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return tt(i, e), rt(i, nt(i, r)), i.set("submitAction", t), i;
}
function at(e, t) {
	if (t !== "submit") return !1;
	let n = f(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function ot(e, t, n, r = {}) {
	L.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = at(e, t), c = {
		form: e,
		action: t,
		formData: it(e, t),
		abort: (e) => {
			i = !0, a = e, L.warn("Pipeline aborted.", { reason: e });
		},
		isAborted: () => i,
		abortReason: () => a
	}, l = {
		prepare: async (e) => {
			let t = e.form.querySelector("input[name=\"submitAction\"]");
			return t && (t.value = e.action), e.formData.set("submitAction", e.action), null;
		},
		validate: async (e) => {
			if (e.action !== "submit" || r.validateOnSubmit === !1) return null;
			if (r.validator) {
				let { scope: t, final: n } = g(e.form), i = r.validator.submit(n ? e.form : t, { final: n });
				if (i.length > 0) {
					let e = i[0]?.input;
					if (e) {
						e.scrollIntoView({
							behavior: "smooth",
							block: "center"
						});
						try {
							e.focus({ preventScroll: !0 });
						} catch {
							e.focus();
						}
					}
					return {
						ok: !1,
						stage: "validate",
						code: "VALIDATION_FAILED",
						message: r.validator.config.errorMessage || "Validation failed.",
						fieldErrors: r.validator.getFieldErrors(i),
						formErrors: [r.validator.config.errorMessage || "Validation failed."]
					};
				}
				return null;
			}
			return e.form.checkValidity() ? null : (e.form.querySelector(":invalid")?.focus(), {
				ok: !1,
				stage: "validate",
				code: "VALIDATION_FAILED",
				message: "Validation failed.",
				formErrors: ["Validation failed."]
			});
		},
		challenge: async () => null,
		payment: async () => null,
		send: async (e) => {
			e.formData = it(e.form, e.action);
			let t = await Je(e.form, e.formData);
			return o = t, t;
		},
		result: async (e) => (o && o.ok && o.redirect?.url && (o.redirect.target === "new-tab" ? window.open(o.redirect.url, "_blank", "noopener,noreferrer") : window.location.href = o.redirect.url), null)
	};
	{
		let e = await n.emitSafe("formie:submit:before", c);
		e.failed.length > 0 && L.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && L.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let u = r.preflightOnly ? Xe : Ye;
	for (let e of u) {
		if (L.log("Stage start.", {
			stage: e,
			action: t
		}), i) return L.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ze(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && L.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ze(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && L.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && L.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return L.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await l[e](c);
		L.log("Stage runner complete.", {
			stage: e,
			hasResult: !!r,
			ok: r ? r.ok : void 0,
			code: r?.code
		});
		{
			let t = await n.emitSafe(`formie:stage:${e}:after`, {
				...c,
				stage: e,
				result: r
			});
			t.failed.length > 0 && L.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ze(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && L.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && L.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return L.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await n.emitSafe("formie:submit:after", r);
				t.failed.length > 0 && L.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await n.emitSafe("formie:submit:final:after", r);
				t.failed.length > 0 && L.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return L.warn("Pipeline short-circuited by failed stage.", {
				stage: e,
				code: r.code,
				message: r.message
			}), r;
		}
	}
	let d = o || {
		ok: !0,
		stage: r.preflightOnly ? "payment" : "result",
		message: r.preflightOnly ? "Submission preflight completed." : "Submission completed."
	};
	{
		let e = await n.emitSafe("formie:submit:after", d);
		e.failed.length > 0 && L.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:after", d);
		e.failed.length > 0 && L.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return L.log("Pipeline completed.", {
		ok: d.ok,
		stage: d.stage,
		code: d.code
	}), d;
}
//#endregion
//#region src/js/core/field-error-container.ts
function st(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function ct(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = st(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/core/submit-result-ui.ts
var R = /* @__PURE__ */ new WeakMap();
function lt(e) {
	return (e.dataset.formieSubmitAction || "").trim();
}
function ut(e) {
	return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function dt(e) {
	return (e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function ft(e) {
	let t = (e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function pt(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function mt(e) {
	let t = R.get(e);
	typeof t == "number" && (window.clearTimeout(t), R.delete(e));
}
function ht(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function gt(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function _t(e, t) {
	return t === "bottom-form" ? gt(e) : ht(e);
}
function vt(e, t) {
	return t === "top-form" ? ht(e) : t === "bottom-form" && !pt(e) ? gt(e) : e;
}
function yt(e) {
	let t = ut(e), n = _t(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), O(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function bt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), O(n, e, "message", "messageError"), n.setAttribute("role", "alert"), M(n, Me(Ae(e))), n;
}
function xt(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = vt(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), O(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function St(e) {
	return ct(e, (t) => {
		O(t, e, "fieldErrors");
	});
}
function Ct(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		D(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), D(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), Ie(n, i);
		});
	}), S(e);
}
function wt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), D(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function Tt(e) {
	mt(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), D(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), lt(e) === "message" && pt(e) || d(e, !1);
}
function Et(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Dt(e, t) {
	let n = Me(Ae(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = `fields[${t.split(".").join("][")}]`, a = e.querySelector(`[name="${CSS.escape(i)}"], [name="${CSS.escape(i + "[]")}"]`)?.closest("[data-formie-field-handle]") || e.querySelector(`[data-formie-field-handle="${CSS.escape(t)}"]`);
		if (!a) return;
		let o = St(a), s = o.id && o.id.trim() ? o.id : `${t}-errors`;
		o.id = s, M(o, n), O(a, e, "fieldLayoutError"), a.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${s}-${n + 1}`, O(r, e, "fieldError"), r.textContent = t, o.appendChild(r);
		});
		let c = o.querySelector("[data-formie-field-error]")?.id;
		a.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), O(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), c && Fe(n, c);
			let r = a.querySelector("[data-formie-instructions]");
			r?.id && Ne(n, r.id);
		});
	}), S(e);
}
function Ot(e, t) {
	let n = yt(e), r = bt(e, n);
	O(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), n.setAttribute("role", "alert"), O(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function kt(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.meta || {}, n = String(t.paymentStatus || "");
	return n === "actionRequired" || n === "pending" || n === "unknown";
}
function At(e, t) {
	let n = yt(e), r = bt(e, n);
	O(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), O(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function jt(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || lt(e) === "message" && dt(e) !== "";
}
function Mt(e, t) {
	let n = dt(e);
	if (!n) return;
	let r = xt(e, n);
	O(r, e, "message", "messageSuccess"), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-success", "true"), r.setAttribute("role", "status"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true");
	let i = document.createElement("div");
	i.setAttribute("data-formie-success", "true"), O(i, e, "success"), i.innerHTML = t, r.appendChild(i), pt(e) && d(e, !0);
	let a = ft(e);
	if (a !== null) {
		let t = window.setTimeout(() => {
			R.delete(e), Tt(e);
		}, a);
		R.set(e, t);
	}
}
function z(e, t) {
	if (Ct(e), wt(e), Tt(e), Et(e), t.ok) {
		jt(e, t) && Mt(e, t.message || "");
		return;
	}
	if (!t.ok) {
		if (kt(t)) {
			let n = t.meta || {}, r = String(n.paymentMessage || "").trim();
			r && At(e, r);
			return;
		}
		t.fieldErrors && Dt(e, t.fieldErrors), t.formErrors?.length ? Ot(e, t.formErrors) : !t.fieldErrors && t.message && Ot(e, [t.message]), P(e);
	}
}
//#endregion
//#region src/js/core/submit-flow.ts
var Nt = E("general", "submit-flow");
function Pt(e) {
	return !(!e.ok && e.stage === "validate");
}
function Ft(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function B(e) {
	Ct(e), wt(e), Tt(e), Et(e);
}
async function It(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	B(r), v(r, c || null);
	let f = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), f = await ot(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), z(r, f), d(f), b(r, f, s), Pt(f) && await u(f);
	} catch (e) {
		f = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, z(r, f), d(f), Nt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		Ft(f) || h(r);
	}
	return f;
}
//#endregion
//#region src/js/events/event-bus.ts
var Lt = class {
	listeners = /* @__PURE__ */ new Map();
	on(e, t) {
		return this.listeners.has(e) || this.listeners.set(e, /* @__PURE__ */ new Set()), this.listeners.get(e)?.add(t), () => {
			this.listeners.get(e)?.delete(t);
		};
	}
	async emit(e, t) {
		let n = this.listeners.get(e);
		if (n && n.size !== 0) for (let e of n) await e(t);
	}
	async emitSafe(e, t) {
		let n = this.listeners.get(e), r = {
			eventName: e,
			total: n?.size || 0,
			succeeded: 0,
			failed: []
		};
		if (!n || n.size === 0) return r;
		let i = 0;
		for (let e of n) {
			try {
				await e(t), r.succeeded += 1;
			} catch (e) {
				r.failed.push({
					index: i,
					error: e
				});
			}
			i += 1;
		}
		return r;
	}
	async emitParallelSafe(e, t) {
		let n = this.listeners.get(e), r = {
			eventName: e,
			total: n?.size || 0,
			succeeded: 0,
			failed: []
		};
		return !n || n.size === 0 || (await Promise.allSettled(Array.from(n).map(async (e) => e(t)))).forEach((e, t) => {
			if (e.status === "fulfilled") {
				r.succeeded += 1;
				return;
			}
			r.failed.push({
				index: t,
				error: e.reason
			});
		}), r;
	}
	clear() {
		this.listeners.clear();
	}
}, V = class {
	modules = /* @__PURE__ */ new Map();
	register(e, t = {}) {
		if (!/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(e.moduleId) || e.version !== 1 || !Array.isArray(e.surfaces) || e.surfaces.some((e) => ![
			"server-rendered",
			"client-rendered",
			"cp-edit"
		].includes(e))) throw Error("Unsupported browser module definition. Register a namespaced moduleId compatible with version 1.");
		let n = this.modules.get(e.moduleId);
		return n === e ? !0 : n && !t.replace ? (console.warn(`[formie] Module "${e.moduleId}" is already registered. Pass { replace: true } to override the existing definition.`), !1) : (this.modules.set(e.moduleId, e), !0);
	}
	unregister(e) {
		this.modules.delete(e);
	}
	get(e) {
		return this.modules.get(e) || null;
	}
	getAll() {
		return Array.from(this.modules.values());
	}
}, Rt = new V(), zt = {
	"address-finder": () => import("./chunks/address-finder-5RA475tB.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-C0Qg8W0H.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-D23p1mBG.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-DDfyZ_EH.js").then((e) => e.placeKitModule)
}, Bt = {
	"captcha-eu": () => import("./chunks/captcha-eu-DXomaK8N.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-DMKQSyWt.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-DdSoV8OG.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-d6bljfnd.js").then((e) => e.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-BDMHMUfg.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-k8MjXGz6.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-DlSNwEU3.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-CgQdMe7h.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-Dzc5xSSX.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-N_3jmimn.js").then((e) => e.turnstileModule)
}, Vt = {
	calculations: () => import("./chunks/calculations-BxfOmyea.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-DHP3DW3Y.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-C_seffiw.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-aneMew98.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-B9IK9561.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-D39CIxKN.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-BBpPi__M.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-BZ0_fkoD.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-CKSNLOY_.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-nmQBIPPt.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-C28bjH9X.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-Bb7DFp0E.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-Daw0U-4h.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-e7bvO5ZW.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-ek8Udmiu.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-FGvfUvSl.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-67B-sTbF.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-Cuun7L4F.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-C4roYm8n.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-Ci7TEdDl.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-D9eqvIxh.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-CrHukOI-.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-BN6TdE1D.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-CPmgwJOW.js").then((e) => e.textLimitModule)
}, Ht = {
	bpoint: () => import("./chunks/bpoint-CRZIZHHM.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-3IwSMuKM.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-CLDVhOKn.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-V7IdfO44.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-DG6-YhtR.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-6qLbTkWh.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-DA-X6H0b.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-DHtQ-uAx.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-C6KtKLqX.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-ByOAqxCv.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-ChmGsyZd.js").then((e) => e.stripeModule)
}, Ut = {
	...Vt,
	...zt,
	...Bt,
	...Ht
}, H = /* @__PURE__ */ new Map();
async function Wt(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(Ut, e.slice(7)) ? Ut[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	H.has(e) || H.set(e, r().catch((t) => {
		throw H.delete(e), t;
	}));
	let i = await H.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function Gt(e, t, n) {
	let r = e.targets.length ? e.targets : [{
		targetType: "form",
		targetId: "form"
	}];
	return [...new Set(r.flatMap((e) => {
		if (e.targetType === "form" || e.targetType === "global") return [n || t];
		let r = `[${{
			field: "data-formie-field-uid",
			page: "data-formie-page-id",
			button: "data-formie-action"
		}[e.targetType]}="${CSS.escape(e.targetId)}"]`;
		return [...t.matches(r) ? [t] : [], ...t.querySelectorAll(r)];
	}))].filter((e) => !e.closest("[hidden], [data-formie-hidden=\"true\"], [data-formie-conditionally-hidden], [data-formie-page-hidden]"));
}
async function Kt(e, t) {
	n(e);
	let r = t.matchContext.surface ?? "server-rendered", { root: i, form: a } = t.setupContext, o = /* @__PURE__ */ new Map(), s = /* @__PURE__ */ new Map(), c = [], l = !1, u = Promise.resolve(), d = !1, f = async (e, n) => {
		s.set(e.key, e);
		let i = {
			key: e.key,
			moduleId: e.moduleId,
			required: e.required,
			surface: r,
			code: "MODULE_UNAVAILABLE",
			message: "A form feature could not start. Reload the page or contact the site administrator."
		};
		console.error("[formie] Browser module failure", i, n), await t.setupContext.emit("formie:browser:module:error", i);
	}, p = async (e) => {
		try {
			await e.destroy();
		} catch (n) {
			console.error("[formie] Browser module disposal failed", n), await t.setupContext.emit("formie:browser:module:error", {
				key: e.key,
				moduleId: e.moduleId,
				surface: r,
				code: "MODULE_DISPOSE_FAILED",
				message: "A form feature could not clean up. Reload the page before continuing."
			});
		}
	}, m = () => [...s.values()].some((e) => e.required), h = () => {
		if (!a || a.querySelector("[data-formie-module-error]")) return;
		let e = document.createElement("div");
		e.dataset.formieModuleError = "true", e.setAttribute("role", "alert"), e.textContent = "A required form feature could not start. Reload the page or contact the site administrator.", a.prepend(e);
	}, g = (e) => {
		m() && (e.preventDefault(), e.stopImmediatePropagation(), h());
	}, _ = async () => {
		let n = new Set(e.entries.filter((e) => e.surfaces.includes(r)).map((e) => e.key));
		for (let e of s.keys()) n.has(e) || s.delete(e);
		for (let [e, t] of o) if (!n.has(e)) {
			for (let { instance: e } of t.values()) await p(e), c.splice(c.indexOf(e), 1);
			o.delete(e), s.delete(e);
		}
		for (let n of e.entries) {
			if (l || !n.surfaces.includes(r)) continue;
			s.has(n.key) && s.set(n.key, n);
			let e = Gt(n, i, a), u = o.get(n.key) ?? /* @__PURE__ */ new Map();
			o.set(n.key, u);
			for (let [t, n] of u) e.includes(t) || (await p(n.instance), u.delete(t), c.splice(c.indexOf(n.instance), 1));
			let d;
			try {
				d = await Wt(n.moduleId, t.registry);
			} catch (e) {
				s.has(n.key) || await f(n, e);
				continue;
			}
			let m = !1, h = !1;
			for (let a of e) {
				if (l) return;
				let e = JSON.stringify([
					n.moduleId,
					n.config,
					n.required
				]), o = u.get(a);
				if (o?.config === e) continue;
				let g = {
					...t.setupContext,
					target: a,
					entryKey: n.key,
					surface: r,
					scope: n.targets[0]?.targetType ?? "form",
					options: n.config
				};
				try {
					if (o) {
						if (o.instance.update && o.moduleId === n.moduleId && o.required === n.required) {
							await o.instance.update(g), o.config = e;
							continue;
						}
						await p(o.instance), u.delete(a), c.splice(c.indexOf(o.instance), 1);
					}
					if (d.surfaces && !d.surfaces.includes(r)) throw Error(`Module ${n.moduleId} does not support ${r}.`);
					if (!d.match({
						...t.matchContext,
						mode: "server-rendered",
						target: a,
						scope: g.scope,
						manifestItem: n
					})) throw Error(`Module ${n.moduleId} does not support the rendered target.`);
					let s = await d.setup(g);
					if (!s) throw Error(`Module ${n.moduleId} did not initialize.`);
					if (l || !i.contains(a) && a !== i) {
						await p(s);
						continue;
					}
					s.key = n.key, s.moduleId = n.moduleId, s.target = a;
					let m = s.assertReady;
					s.assertReady = () => {
						try {
							m?.();
						} catch (e) {
							if (f(n, e), n.required) throw Error("A required form feature could not start.");
						}
					};
					let _ = s.onBeforeStage, v = s.onAfterStage;
					s.onBeforeStage = async (e) => {
						try {
							await _?.(e);
						} catch (t) {
							await f(n, t), n.required && e.abort("A required form feature could not complete. Reload the page or contact the site administrator.");
						}
					}, s.onAfterStage = async (e, t) => {
						try {
							await v?.(e, t);
						} catch (t) {
							await f(n, t), n.required && e.abort("A required form feature could not complete.");
						}
					}, u.set(a, {
						instance: s,
						config: e,
						moduleId: n.moduleId,
						required: n.required
					}), c.push(s), h = !0, await t.setupContext.emit("formie:browser:module:mount", {
						key: n.key,
						moduleId: n.moduleId,
						target: a
					});
				} catch (e) {
					m = !0, s.has(n.key) || await f(n, e);
				}
			}
			!m && (h || e.length === 0) && s.delete(n.key);
		}
		m() ? h() : a?.querySelector("[data-formie-module-error]")?.remove();
	}, v = new MutationObserver(() => {
		d || l || (d = !0, u = u.then(async () => {
			d = !1, l || await _();
		}), u.catch((e) => console.error("[formie] Module reconciliation failed", e)));
	});
	return c.push({
		assertReady: () => {
			if (m()) throw Error("A required form feature could not start. Reload the page or contact the site administrator.");
		},
		destroy: async () => {
			l = !0, v.disconnect(), a?.removeEventListener("submit", g, !0), await u;
			for (let e of o.values()) for (let { instance: t } of e.values()) await p(t);
			o.clear(), c.splice(1);
		},
		onBeforeStage: (e) => {
			m() && e.abort("A required form feature could not start. Reload the page or contact the site administrator.");
		}
	}), c.updateManifest = async (t) => {
		n(t), e = t, u = u.then(_), await u;
	}, a?.addEventListener("submit", g, !0), await _(), v.observe(i, {
		childList: !0,
		subtree: !0,
		attributes: !0,
		attributeFilter: [
			"hidden",
			"data-formie-hidden",
			"data-formie-conditionally-hidden",
			"data-formie-page-hidden",
			"data-formie-field-uid",
			"data-formie-page-id",
			"data-formie-action"
		]
	}), c;
}
//#endregion
//#region src/js/utils/form-started-at.ts
var qt = "formie:formStartedAt:";
function Jt(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${qt}${n}` : null, i = r ? sessionStorage.getItem(r) : null;
	i || (i = String(Date.now()), r && sessionStorage.setItem(r, i)), t.value = i;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var Yt = /* @__PURE__ */ new Set([
	"action",
	"redirect",
	"requestToken",
	"renderId",
	"formStartedAt",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"progressId"
]);
function U(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => U(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${U(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function Xt(e) {
	return U(e, /* @__PURE__ */ new WeakSet());
}
function Zt(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !k(n, t) && !Yt.has(n);
}
function Qt(e) {
	return Xt(Array.from(new FormData(e).entries()).filter(([t]) => Zt(String(t || ""), e)));
}
function $t(e, t = {}) {
	let n = null, r = !1, i = !1, a = null, o = null, s = null, c = () => {
		a !== null && (window.cancelAnimationFrame(a), a = null), o !== null && (window.clearTimeout(o), o = null), s !== null && (window.clearTimeout(s), s = null);
	}, l = () => r ? (i = Qt(e) !== n, i) : !1, u = () => {
		n = Qt(e), r = !0, i = !1;
	}, d = () => {
		c(), r = !1, a = window.requestAnimationFrame(() => {
			a = null, s = window.setTimeout(() => {
				s = null, u();
			}, 0);
		});
	}, f = () => {
		o !== null && window.clearTimeout(o), o = window.setTimeout(() => {
			o = null, l();
		}, 120);
	}, p = (e) => {
		(!t.shouldWarn || t.shouldWarn()) && l() && (e.preventDefault(), e.returnValue = "");
	};
	return e.addEventListener("input", f), e.addEventListener("change", f), window.addEventListener("beforeunload", p), d(), {
		captureBaseline: u,
		scheduleBaselineCapture: d,
		refreshDirtyState: l,
		destroy: () => {
			c(), e.removeEventListener("input", f), e.removeEventListener("change", f), window.removeEventListener("beforeunload", p);
		}
	};
}
//#endregion
//#region src/js/validation/rules/email.ts
var en = {
	rule: ({ input: e, getRule: n }) => {
		let r = n("email");
		return !r || t(e.value, {
			...typeof r == "object" ? r : {},
			type: "email"
		}) === null;
	},
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function tn(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function nn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !N(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var rn = {
	required: {
		rule: ({ input: e, getRule: n }) => {
			if (!n("required") || e.type === "hidden") return !0;
			if (e.type === "checkbox" || e.type === "radio") {
				let t = e.form?.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`) || [];
				return t.length ? Array.from(t).some((e) => e instanceof HTMLInputElement && e.checked) : e instanceof HTMLInputElement ? e.checked : !0;
			}
			return t(e.value, { type: "required" }) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? n("{label} cannot be blank.", { label: t })
	},
	email: en,
	url: {
		rule: ({ input: e, getRule: n }) => {
			let r = n("url");
			return !r || t(e.value, {
				...typeof r == "object" ? r : {},
				type: "url"
			}) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? n("{label} is not a valid URL.", { label: t })
	},
	number: {
		rule: ({ input: e, getRule: n }) => {
			let r = n("number");
			return !r || t(e.value, {
				...typeof r == "object" ? r : {},
				type: "number"
			}) === null;
		},
		message: ({ input: e, label: n, getRule: r, t: i }) => {
			let a = r("number");
			return t(e.value, {
				...typeof a == "object" ? a : {},
				type: "number"
			}, { label: n }) ?? i("{label} is not a valid number.", { label: n });
		}
	},
	match: {
		rule: (e) => {
			let n = nn(e);
			return !n || t(e.input.value, { type: "match" }, { comparison: n.value }) === null;
		},
		message: (e) => {
			let t = nn(e)?.closest("[data-formie-field-handle]"), n = tn(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, an = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, W = E("general", "validator");
function G(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function on(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var sn = class {
	form;
	errors = [];
	validators = {};
	boundListeners = !1;
	activated = /* @__PURE__ */ new WeakSet();
	submitted = !1;
	initialValues = /* @__PURE__ */ new WeakMap();
	onBlur;
	onChange;
	onInput;
	config;
	constructor(e, t = {}) {
		this.form = e, this.onBlur = this.blurHandler.bind(this), this.onChange = this.changeHandler.bind(this), this.onInput = this.inputHandler.bind(this), this.config = {
			live: !1,
			errorAriaLive: "polite",
			errorMessage: "",
			fieldContainerErrorClass: [],
			inputErrorClass: [],
			messagesClass: [],
			messageClass: [],
			fieldsSelector: "input:not([type=\"hidden\"]):not([type=\"submit\"]):not([type=\"button\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])",
			patterns: an,
			...t
		}, Object.entries(rn).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		W.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, o("ready"), { validator: this });
	}
	inputs(e = null) {
		if (G(e)) return N(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => G(e) && !N(e));
	}
	getInputValue(e) {
		return e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio") ? e.checked : e instanceof HTMLInputElement && e.type === "file" ? e.files?.length ? Array.from(e.files).map((e) => e.name).join("|") : "" : e.value ?? "";
	}
	isDirty(e) {
		return this.initialValues.has(e) ? this.getInputValue(e) !== this.initialValues.get(e) : (this.initialValues.set(e, this.getInputValue(e)), !1);
	}
	shouldShowError(e) {
		return this.submitted || this.activated.has(e);
	}
	isValid(e = null, t = {}) {
		return this.validate(e, t).length === 0;
	}
	validate(e = null, t = {}) {
		this.errors = [];
		let n = /* @__PURE__ */ new Set();
		return this.inputs(e).forEach((e) => {
			let r = !1;
			if (!this.isVisible(e, t)) return;
			let i = e.closest("[data-formie-field-handle]"), a = e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio") ? `${i?.getAttribute("data-formie-field-handle") || ""}:${e.name}` : null;
			if (a) {
				if (n.has(a)) return;
				n.add(a);
			}
			this.shouldShowError(e) && this.removeError(e);
			let o = this.getValidatorCallbackOptions(e);
			Object.entries(this.validators).forEach(([t, n]) => {
				if (!n.validate(o)) {
					let i = this.getErrorMessage(e, t, n, o);
					this.shouldShowError(e) && !r && this.showError(e, t, i), this.errors.push({
						input: e,
						field: o.field,
						validator: t,
						message: i,
						handle: o.field?.getAttribute("data-formie-field-handle") || null,
						result: !1
					}), r = !0;
				}
			}), !r && this.shouldShowError(e) && this.removeError(e);
		}), W.log("Validation pass complete.", {
			errorCount: this.errors.length,
			includeHiddenPages: t.includeHiddenPages === !0
		}), this.errors;
	}
	removeAllErrors() {
		this.inputs().forEach((e) => {
			this.removeError(e);
		});
	}
	removeError(e) {
		let t = e.closest("[data-formie-field-handle]");
		if (!t) {
			e.removeAttribute("aria-invalid");
			return;
		}
		let n = t.querySelector("[data-formie-field-errors]"), r = Array.from(t.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		t.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), n && (n.innerHTML = ""), t.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), Ie(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, o("clear-error"), { validator: this }), S(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= ct(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let a = r.getAttribute("data-formie-field-handle") || "field", s = `${a}-error`;
		i.id = i.id || `${a}-errors`, M(i, je(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			N(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), Fe(t, s));
		});
		for (let e = r; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, o("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: n
		}), S(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: oe,
			input: e,
			label: n,
			field: t,
			form: this.form,
			config: this.config,
			rules: r,
			getRule: (e) => this.getRule(t, e)
		};
	}
	getErrorMessage(e, t, n, r) {
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? oe("{label} is invalid.", { label: r.label });
	}
	getErrors() {
		return this.errors;
	}
	getFieldErrors(e = this.errors) {
		let t = {};
		return e.forEach((e) => {
			e.handle && !t[e.handle]?.length && (t[e.handle] = [e.message]);
		}), t;
	}
	getRule(e, t) {
		if (!e) return !1;
		let n = this.parseValidationRules(e.getAttribute("data-formie-validation"));
		return Object.prototype.hasOwnProperty.call(n, t) ? n[t] : !1;
	}
	parseValidationRules(e) {
		let t = {};
		if (!e) return t;
		let n = null;
		try {
			n = JSON.parse(e);
		} catch {
			return W.warn("Invalid validation rules payload.", { formId: this.form.id || null }), t;
		}
		return Array.isArray(n) && n.forEach((e) => {
			if (!e || typeof e != "object" || Array.isArray(e)) return;
			let n = e, r = typeof n.type == "string" ? n.type.trim() : "";
			r && (t[r] = n);
		}), t;
	}
	destroy() {
		W.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, o("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		if (e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]")) return !1;
		if (e.closest("[data-formie-page-hidden]")) return !!t.includeHiddenPages;
		let n = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-rich-text]");
		return n instanceof HTMLElement ? on(n) : on(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && G(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		if (e.target instanceof HTMLElement && G(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && !(e instanceof CustomEvent)) {
			if (e.target instanceof HTMLSelectElement) {
				this.activated.add(e.target), this.validate(e.target);
				return;
			}
			e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio") && (this.activated.add(e.target), this.validate(e.target));
		}
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && G(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
	}
	submit(e = null, { final: t = !1 } = {}) {
		return this.submitted = !0, W.log("Submit validation requested.", { final: t }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(e, { includeHiddenPages: t });
	}
	resetLiveState() {
		this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
	}
	addEventListeners() {
		this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, W.log("Event listeners attached."));
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, W.log("Event listeners removed.");
	}
	emitEvent(e, t, n = {}) {
		e.dispatchEvent(new CustomEvent(t, {
			bubbles: !0,
			detail: n
		}));
	}
	addValidator(e, t, n) {
		this.validators[e] = {
			validate: t,
			errorMessage: n
		};
	}
	removeValidator(e) {
		delete this.validators[e];
	}
};
//#endregion
//#region src/js/validation/enter-key-guard.ts
function cn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function ln(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !cn(e));
}
function un(e) {
	let { final: t } = g(e);
	return "submit";
}
function dn(e) {
	return !ln(e, un(e));
}
function fn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || dn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var K = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", pn = 300, mn = "/actions/formie/server/forms/render", hn = "/api", gn = "/actions/formie/server/forms/refresh-tokens", _n = "/actions/formie/server/submissions/submit", vn = "/actions/formie/server/submissions/set-page", yn = "/actions/formie/server/submissions/clear-submission", bn = "/actions/formie/file-upload/hydrate", q = E("general", "client"), xn = /* @__PURE__ */ new Set();
function J(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function Sn(e) {
	return e.formieRefreshTokens == null ? e.formieStaticCache != null && J(e.formieStaticCache, !0) : J(e.formieRefreshTokens, !0);
}
function Y(e) {
	let t = e instanceof HTMLElement ? e.dataset : {};
	return {
		mode: "server-rendered",
		transport: t.formieTransport || "rest",
		profile: t.formieRequestProfile,
		formHandle: t.formieHandle,
		endpoint: t.formieEndpoint,
		staticCache: Sn(t),
		autoVisible: J(t.formieAutoVisible, !0),
		compatibility: J(t.formieCompatibility, !1)
	};
}
function Cn(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function wn(e) {
	return e || "rest";
}
function Tn(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function En(e, t) {
	xn.has(e) || (xn.add(e), q.warn(t));
}
function Dn(e, t) {
	if (!e) return e;
	try {
		return new URL(e).toString();
	} catch {}
	if (!t) return e;
	try {
		return new URL(e, t).toString();
	} catch {
		return e;
	}
}
function X(e, t) {
	let n = (e || "").trim();
	return n ? n.includes(t) ? n : Dn(t, n) : t;
}
function On(e, t) {
	return X(e.endpoint || t.dataset.formieEndpoint, mn);
}
function kn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : Dn(hn, n) : hn;
}
function An(e, t) {
	return X(t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint, gn);
}
function jn(e, t) {
	if (!e) return t;
	try {
		let n = new URL(e, window.location.origin), r = new URL(t, window.location.origin);
		return n.searchParams.forEach((e, t) => {
			r.searchParams.has(t) || r.searchParams.set(t, e);
		}), r.toString();
	} catch {
		return t;
	}
}
function Mn(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = X(r, _n), a = t.getAttribute("action");
	t.setAttribute("action", jn(a, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href"), n = X(r, vn);
		e.setAttribute("href", jn(t, n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(r, bn));
	});
}
function Nn(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Pn(e) {
	return J(e.dataset.formieAutomaticSubmissionState, !0);
}
function Fn(e, t, n) {
	return X(n.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint, yn);
}
function In(e) {
	return Nn(e.dataset.formieUnloadWarning);
}
function Ln(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function Rn(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function zn(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function Bn(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function Vn(e) {
	return Bn(window.location.href, "resumeToken") || Bn(e.getAttribute("action"), "resumeToken");
}
function Hn(e) {
	return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function Un(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function Wn(e) {
	return Math.max(0, Un(e.dataset.formieSubmitDelay, pn));
}
function Z(e) {
	return Nn(e.dataset.formieValidationOnSubmit);
}
async function Gn(e) {
	let t = Wn(e);
	t < 1 || await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Kn(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function qn(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Kn(n, "data-formie-modules"), i = Kn(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function Jn(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function Yn(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function Q(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function Xn(e, t) {
	if (t) {
		if (t.csrf?.param && t.csrf?.token) {
			let n = e.querySelector(`input[name="${t.csrf.param}"]`);
			n ? n.value = t.csrf.token : (n = document.createElement("input"), n.type = "hidden", n.name = t.csrf.param, n.value = t.csrf.token, n.setAttribute("autocomplete", "off"), n.setAttribute("data-formie-csrf", ""), e.prepend(n));
		}
		if (t.requestToken) {
			let n = e.querySelector("input[name=\"requestToken\"]");
			n && (n.value = t.requestToken);
		}
		if (t.renderId) {
			let n = e.querySelector("input[name=\"renderId\"]");
			n && (n.value = t.renderId);
		}
		t.captchas && typeof t.captchas == "object" && Object.values(t.captchas).forEach((t) => {
			if (!t || typeof t != "object") return;
			let n = t;
			if (!n.sessionKey) return;
			let r = e.querySelector(`input[name="${n.sessionKey}"]`);
			r && typeof n.value == "string" && (r.value = n.value);
		});
	}
}
async function Zn(e, t) {
	let n = Cn(t.mode), r = wn(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!Tn(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? kn(t, e) : On(t, e), c = r === "graphql" ? await Ge(s, a, o, t) : await We(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function Qn(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await I(An(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	Xn(n, i), y(e, "formie:refresh-tokens:refreshed", i);
}
function $n(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = Fn(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		if (e) {
			t.setAttribute("data-formie-pending-action", e);
			return;
		}
		t.removeAttribute("data-formie-pending-action");
	};
	if (In(t)) {
		let n = $t(t, { shouldWarn: () => !zn(t) }), r = (e) => {
			if (!(e instanceof CustomEvent)) return;
			let t = e.detail;
			t?.ok && t.action === "save" && n.scheduleBaselineCapture();
		}, i = () => {
			n.scheduleBaselineCapture();
		};
		e.addEventListener("formie:submit:result", r), t.addEventListener("formie:state:reset", i), a.push(() => {
			e.removeEventListener("formie:submit:result", r), t.removeEventListener("formie:state:reset", i), n.destroy();
		});
	}
	if (l.forEach((e) => {
		let n = (e) => {
			let n = e.currentTarget.getAttribute("data-formie-action"), r = t.querySelector("input[name=\"submitAction\"]");
			u(n), n && r && (r.value = n);
		};
		e.addEventListener("click", n), a.push(() => {
			e.removeEventListener("click", n);
		});
	}), t.querySelectorAll("[data-formie-tab-link]").forEach((n) => {
		let r = async (n) => {
			if (o !== "ajax") {
				Hn(n) && Ln(t, "set-page");
				return;
			}
			n.preventDefault();
			let r = n.currentTarget, i = r?.getAttribute("data-formie-page-id"), a = r?.getAttribute("href");
			if (i && a) {
				y(e, "formie:page:navigate", {
					pageId: i,
					href: a
				});
				try {
					let n = await Ke(a, t, i);
					if (n.pageId && _(t, String(n.pageId)), !n.success) {
						let { form: e = [], ...r } = n.errors ?? {};
						B(t), Dt(t, r), Ot(t, e), P(t);
						return;
					}
					y(e, "formie:page:navigate:after", {
						pageId: i,
						href: a,
						response: n
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), y(e, "formie:page:navigate:error", {
						pageId: i,
						href: a,
						error: t
					});
				}
			}
		};
		n.addEventListener("click", r), a.push(() => {
			n.removeEventListener("click", r);
		});
	}), !Pn(t)) {
		let e = !1, n = () => {
			e || zn(t) || Vn(t) || (e = !0, qe(s, t));
		};
		window.addEventListener("pagehide", n), window.addEventListener("beforeunload", n), a.push(() => {
			window.removeEventListener("pagehide", n), window.removeEventListener("beforeunload", n);
		});
	}
	let d = async (a) => {
		if (c) return;
		let s = o === "ajax";
		if (a.preventDefault(), t.getAttribute("data-formie-loading") === "true") {
			if (t.getAttribute("data-formie-internal-resubmit") !== "true") return;
			t.removeAttribute("data-formie-internal-resubmit");
		} else t.removeAttribute("data-formie-internal-resubmit");
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), f = t.getAttribute("data-formie-pending-action"), m = t.querySelector("input[name=\"submitAction\"]"), _ = d || f || m?.value || "submit", b = null, ee = !1;
		try {
			if (s) b = await It({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Z(t),
				action: _,
				submitter: l,
				waitForSubmitDelay: Gn,
				onRefreshTokensAfterSubmit: async () => {
					await Qn(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					y(e, "formie:submit:result", t);
				}
			});
			else {
				if (B(t), v(t, l), await Gn(t), b = await ot(t, _, r, {
					validator: i,
					validateOnSubmit: Z(t),
					preflightOnly: !0
				}), b.ok) {
					p(t, _), c = !0, Ln(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, Rn(t), h(t), i && Z(t)) {
							let { scope: e, final: n } = g(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && z(t, {
								ok: !1,
								stage: "validate",
								code: "VALIDATION_FAILED",
								message: i.config.errorMessage || "Validation failed.",
								fieldErrors: i.getFieldErrors(r),
								formErrors: [i.config.errorMessage || "Validation failed."]
							});
						}
					};
					if (typeof t.requestSubmit == "function") {
						t.addEventListener("invalid", n, !0);
						try {
							t.requestSubmit();
						} finally {
							t.removeEventListener("invalid", n, !0);
						}
					} else t.submit();
					if (e) return;
					ee = !0;
					return;
				}
				z(t, b), y(e, "formie:submit:result", b), Rn(t);
			}
		} catch (n) {
			c = !1, b = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, z(t, b), y(e, "formie:submit:result", b), Rn(t);
		} finally {
			u(null), !s && !ee && !Ft(b) && h(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	});
}
async function er(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = An(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await I(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	o && n && (Xn(n, o), y(e, "formie:refresh-tokens:after", o));
}
function tr() {
	let t = /* @__PURE__ */ new Map(), r = new V(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
		"prepare",
		"normalize",
		"validate",
		"challenge",
		"payment",
		"send",
		"result"
	], s = async (e) => {
		let n = a.get(e);
		if (n) {
			await n;
			return;
		}
		let r = (async () => {
			q.log("Unmount requested.", { target: Q(e) });
			let n = i.get(e);
			n && (n(), i.delete(e));
			let r = t.get(e);
			if (!r) {
				q.log("Unmount skipped (no mounted state).", { target: Q(e) });
				return;
			}
			y(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null;
			for (let e of r.modules) await e.destroy();
			r.modules = [], r.bus.clear(), t.delete(e), y(e, "formie:unmount:after", { id: r.instance.id }), q.log("Unmount complete.", {
				id: r.instance.id,
				target: Q(e)
			});
		})().finally(() => {
			a.delete(e);
		});
		a.set(e, r), await r;
	}, c = async (a, c) => {
		q.log("Mount requested.", {
			target: Q(a),
			mode: c.mode,
			autoVisible: c.autoVisible
		});
		let l = i.get(a);
		l && (l(), i.delete(a));
		let u = t.get(a);
		if (u) return q.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: Q(a)
		}), u.instance;
		let d = new Lt(), f = [], p = a?.id || `formie-${t.size + 1}`, h = Y(a), g = {
			...h,
			...c,
			mode: Cn(c.mode ?? h.mode),
			transport: wn(c.transport ?? h.transport)
		}, _ = Se(g.compatibility), v = await Zn(a, g), b = Tn(a);
		b && e(b, g), g.staticCache = c.staticCache ?? Sn(b ? b.dataset : a.dataset);
		let x;
		try {
			x = qn(a, b), v?.modules && n(v.modules), x?.modules && n(x.modules);
		} catch (e) {
			if (b) {
				b.addEventListener("submit", (e) => {
					e.preventDefault(), e.stopImmediatePropagation();
				}, !0);
				let e = document.createElement("div");
				e.setAttribute("role", "alert"), e.textContent = "This form requires a compatible Formie browser package. Update Formie and its browser packages together.", b.prepend(e);
			}
			throw e;
		}
		let C = v || x ? {
			...v || {},
			...x || {}
		} : null, te = C?.theme, w = {}, T = C?.modules ?? {
			contractVersion: 1,
			entries: []
		};
		n(T), q.log("Resolved mount payload.", {
			target: Q(a),
			hasRenderPayload: !!v,
			hasEmbeddedPayload: !!x,
			moduleCount: T.entries.length
		});
		let E = ne(a, te, b), D = b ? new sn(b, {
			live: Nn(b.dataset.formieValidationOnFocus),
			errorAriaLive: Ae(b),
			errorMessage: b.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: E.fieldLayoutError || [],
			inputErrorClass: E.fieldControlError || [],
			messagesClass: E.fieldErrors || [],
			messageClass: E.fieldError || []
		}) : null;
		if (b && D) {
			let e = b;
			e.formieValidation = D, w.validation = D;
			let t = {
				validator: D,
				addValidator: D.addValidator.bind(D),
				removeValidator: D.removeValidator.bind(D)
			};
			y(b, "formie:validator:ready", t), y(a, "formie:validator:ready", t);
		}
		b && (Jt(b), g.themeConfig && typeof g.themeConfig == "object" && b.setAttribute("data-formie-theme-config", JSON.stringify(g.themeConfig)), g.theme && g.theme !== "formie" && b.setAttribute("data-formie-frontend-theme", g.theme), (v || g.endpoint || a.dataset.formieEndpoint) && Mn(a, b, g), g.mode === "server-rendered" && Ve(b) && (Be(b), P(b)), S(b)), Object.keys(E).length && y(a, "formie:theme:applied", { hasClasses: !0 });
		let O = await Kt(T, {
			registry: r,
			matchContext: {
				root: a,
				form: b,
				mode: g.mode
			},
			setupContext: {
				formId: p,
				root: a,
				form: b,
				target: a,
				scope: "form",
				state: w,
				on: (e, t) => d.on(e, t),
				emit: (e, t) => (y(a, e, t), d.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && q.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		q.log("Module setup complete.", {
			target: Q(a),
			moduleInstances: O.length
		});
		let k = {
			id: p,
			root: a,
			submit: async (e = "submit") => {
				if (q.log("Submit requested.", {
					id: p,
					target: Q(a),
					action: e
				}), !b) return {
					ok: !1,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				let t = b.querySelector("input[name=\"submitAction\"]");
				if (t && (t.value = e), b.getAttribute("data-formie-loading") === "true") return {
					ok: !1,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				let n = b.querySelector(`[data-formie-action="${e}"]`), r = await It({
					id: p,
					target: a,
					form: b,
					bus: d,
					validator: D,
					validateOnSubmit: Z(b),
					action: e,
					submitter: n,
					waitForSubmitDelay: Gn,
					onRefreshTokensAfterSubmit: async () => {
						await Qn(a, g, b);
					},
					dispatchSubmitResult: (e) => {
						y(a, "formie:submit:result", e);
					}
				});
				return q.log("Submit completed.", {
					id: p,
					action: e,
					ok: r.ok,
					code: r.code,
					message: r.message
				}), r;
			},
			destroy: async () => {
				await s(a);
			},
			on: (e, t) => d.on(e, t)
		};
		b && (ke({
			target: a,
			form: b,
			validatorDetail: D ? {
				validator: D,
				addValidator: D.addValidator.bind(D),
				removeValidator: D.removeValidator.bind(D)
			} : null,
			options: _,
			unbinds: f
		}), De({
			target: a,
			form: b,
			instance: k,
			options: _,
			unbinds: f
		})), b && ($n(a, b, g, d, D, f), D && (f.push(ee(b, D, a)), f.push(fn(b))), await er(a, g, b), b.dispatchEvent(new CustomEvent("formie:state:reset")), window.setTimeout(() => {
			b.dispatchEvent(new CustomEvent("formie:state:reset"));
		}, 350)), o.forEach((e) => {
			let t = d.on(`formie:stage:${e}:before`, async (t) => {
				y(a, `formie:stage:${e}:before`, t);
			}), n = d.on(`formie:stage:${e}:before`, async (e) => {
				for (let t of O) t.onBeforeStage && await t.onBeforeStage(e);
			}), r = d.on(`formie:stage:${e}:after`, async (t) => {
				y(a, `formie:stage:${e}:after`, t);
			}), i = d.on(`formie:stage:${e}:after`, async (e) => {
				let t = e;
				for (let e of O) e.onAfterStage && await e.onAfterStage(t, t.result);
			});
			f.push(t, n, r, i);
		});
		let re = d.on("formie:submit:before", async (e) => {
			y(a, "formie:submit:before", e);
		}), A = d.on("formie:submit:after", async (e) => {
			y(a, "formie:submit:after", e);
		}), ie = d.on("formie:submit:final:before", async (e) => {
			y(a, "formie:submit:final:before", e);
		}), ae = d.on("formie:submit:final:after", async (e) => {
			y(a, "formie:submit:final:after", e);
		});
		return f.push(re, A, ie, ae), t.set(a, {
			options: g,
			bus: d,
			form: b,
			validator: D,
			modules: O,
			unbinds: f,
			instance: k
		}), y(a, "formie:mount:after", {
			id: p,
			mode: g.mode
		}), b instanceof HTMLFormElement && m(b), q.log("Mount complete.", {
			id: p,
			target: Q(a),
			mode: g.mode
		}), k;
	}, l = (e, n) => {
		if (!n.autoVisible || Jn(e) || typeof IntersectionObserver > "u") return c(e, n);
		if (t.has(e)) return Promise.resolve(t.get(e)?.instance || null);
		if (i.has(e)) return q.log("Mount deferred (already waiting visibility).", { target: Q(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((t) => {
			t.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), q.log("Visibility reached, proceeding mount.", { target: Q(e) }), c(e, {
				...n,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return r.observe(e), i.set(e, () => {
			r.disconnect();
		}), q.log("Mount deferred until visible.", { target: Q(e) }), Promise.resolve(null);
	};
	return {
		mount: c,
		unmount: s,
		update: async (e, n) => {
			let r = t.get(e);
			if (!r) return c(e, {
				...Y(e),
				...n,
				mode: n.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...n
			};
			let i = n.payload?.theme || r.options.payload?.theme || qn(e, r.form)?.theme, a = ne(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && y(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => t.get(e)?.instance || null,
		refreshForCache: async (e) => {
			En("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let n = null;
			if (n = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !n) {
				q.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = t.get(n), i = Tn(n), a = r?.options || Y(n);
			if (!i) {
				q.warn("refreshForCache found no form element for target.", { target: Q(n) });
				return;
			}
			let o = a.formHandle || n.dataset.formieHandle || i.dataset.formieHandle, s = An(a, n), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				q.warn("refreshForCache found no form handle for target.", { target: Q(n) });
				return;
			}
			let l = await I(s, o, c, a, i?.querySelector("input[name=\"requestToken\"]")?.value);
			l && (Xn(i, l), y(n, "formie:refresh-tokens:after", l));
		},
		registerModule: (e, t) => r.register(e, t),
		unregisterModule: (e) => {
			r.unregister(e);
		},
		getRegisteredModules: () => r.getAll(),
		scan: async (e) => {
			let t = e || document, n = Array.from(t.querySelectorAll(K));
			q.log("Scan started.", {
				scope: t === document ? "document" : t,
				targetCount: n.length
			});
			let r = (await Promise.all(n.map((e) => {
				let t = Y(e);
				return l(e, t);
			}))).filter((e) => !!e);
			return q.log("Scan finished.", {
				mountedCount: r.length,
				deferredCount: n.length - r.length
			}), r;
		},
		observe: (e) => {
			if (typeof MutationObserver > "u") return () => {};
			let n = e || document;
			q.log("Observer started.", { scope: n === document ? "document" : n });
			let r = new MutationObserver((e) => {
				e.forEach((e) => {
					e.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(K) && (q.log("Observer detected new root.", { target: Q(e) }), l(e, Y(e))), e.querySelectorAll(K).forEach((e) => {
							q.log("Observer detected new nested root.", { target: Q(e) }), l(e, Y(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (t.has(e) && (q.log("Observer detected removed root.", { target: Q(e) }), s(e)), e.querySelectorAll(K).forEach((e) => {
							t.has(e) && (q.log("Observer detected removed nested root.", { target: Q(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(n, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), q.log("Observer stopped."), i.forEach((e, t) => {
					Yn(t, n) && (e(), i.delete(t));
				});
				let e = [];
				n instanceof Element && n.matches(K) && e.push(n), n.querySelectorAll(K).forEach((t) => {
					e.push(t);
				}), e.forEach((e) => {
					t.has(e) && s(e);
				});
			};
		}
	};
}
//#endregion
//#region src/js/core/hydrate-modules.ts
var nr = E("general", "module-hydrator");
async function rr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.modules ?? {
		contractVersion: 1,
		entries: []
	}, i = e.mode ?? "server-rendered", a = e.registry ?? new V(), o = new Lt(), s = await Kt(r, {
		registry: a,
		setupContext: {
			formId: n?.id || t.id || "formie-modules",
			root: t,
			form: n,
			target: t,
			scope: "form",
			state: {},
			options: {},
			on: (e, t) => o.on(e, t),
			emit: async (e, n) => {
				t.dispatchEvent(new CustomEvent(e, {
					detail: n,
					bubbles: !0
				})), await o.emit(e, n);
			}
		},
		matchContext: {
			root: t,
			form: n,
			mode: i,
			surface: e.surface ?? "cp-edit"
		}
	});
	return nr.log("Hydrated module manifest.", {
		moduleCount: r.entries.length,
		instanceCount: s.length,
		mode: i
	}), {
		prepare: async (e) => {
			if (!n) throw Error("Browser modules require a mounted form element.");
			s.forEach((e) => e.assertReady?.());
			let t;
			for (let r of [
				"prepare",
				"validate",
				"challenge",
				"payment",
				"send"
			]) {
				let i = {
					form: n,
					stage: r,
					action: e === "back" || e === "save" ? e : "submit",
					formData: new FormData(n),
					abort: (e) => {
						t = e || "A form feature could not complete.";
					},
					isAborted: () => !!t,
					abortReason: () => t
				};
				await o.emit(`formie:browser:${r}`, i);
				for (let e of s) await e.onBeforeStage?.(i);
				if (t) throw Error(t);
				if (r !== "send") {
					for (let e of s) await e.onAfterStage?.(i);
					if (t) throw Error(t);
				}
			}
			return Object.fromEntries(Array.from(n.querySelectorAll("input[type=\"hidden\"][name]")).map((e) => [e.name, e.value]));
		},
		result: async (e) => {
			if (!n) return;
			let t = {
				form: n,
				stage: "send",
				action: "submit",
				formData: new FormData(n),
				abort: () => {},
				isAborted: () => !1,
				abortReason: () => void 0
			};
			for (let n of s) await n.onAfterStage?.(t, e);
			let r = {
				...t,
				stage: "result"
			};
			await o.emit("formie:browser:result", r);
			for (let e of s) await e.onBeforeStage?.(r);
			await o.emit("formie:submit:result", e);
			for (let t of s) await t.onAfterStage?.(r, e);
			n.dispatchEvent(new CustomEvent("formie:submit:result", {
				detail: e,
				bubbles: !0
			}));
		},
		update: (e) => s.updateManifest(e),
		assertReady: () => s.forEach((e) => e.assertReady?.()),
		destroy: async () => {
			await ir(s), o.clear();
		},
		on: (e, t) => o.on(e, t),
		emit: async (e, t) => {
			await o.emit(e, t);
		},
		registerModule: (e, t = {}) => a.register(e, t),
		unregisterModule: (e) => {
			a.unregister(e);
		},
		getRegisteredModules: () => a.getAll()
	};
}
async function ir(e) {
	for (let t of e) try {
		await t.destroy();
	} catch (e) {
		console.error("[formie] Failed to destroy module instance.", e), nr.warn("Failed destroying module instance.", { error: e });
	}
}
//#endregion
//#region src/js/core/formie.ts
function $(e) {
	return e instanceof Element;
}
function ar(e) {
	return e.ok;
}
function or(e) {
	return typeof e == "string" ? `selector "${e}"` : $(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function sr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) $(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function cr(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : $(e) ? [e] : sr(e);
}
function lr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function ur(e) {
	let t = cr(e);
	return t.length > 0 || typeof e != "string" ? t : (await lr(), cr(e));
}
function dr(e) {
	return typeof e == "string" ? document : $(e) ? e.getRootNode() : document;
}
function fr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function pr(e, t, n, r) {
	let i = [], a = fr(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), c = [];
		if (e.onReady?.(s), c.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), ar(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
		})), e.onEvent) for (let t of l) c.push(s.on(t, (n) => {
			e.onEvent?.({
				name: t,
				payload: n
			}, s);
		}));
		n.set(o, {
			instance: s,
			unsubs: c
		}), i.push(s);
	}
	return i;
}
async function mr(e) {
	let t = e.client ?? tr(), n = /* @__PURE__ */ new Map(), r = await ur(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${or(e.element)}.`);
	await pr(e, t, n, r);
	let i = e.observe ? t.observe(dr(e.element)) : null;
	return {
		client: t,
		get instances() {
			return Array.from(n.values()).map(({ instance: e }) => e);
		},
		get(e) {
			let r = typeof e == "string" ? document.querySelector(e) : e;
			return r ? n.get(r)?.instance ?? t.getInstance(r) : null;
		},
		async rescan() {
			let r = cr(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : pr(e, t, n, r);
		},
		async destroy() {
			i?.();
			let e = Array.from(n.entries());
			for (let [r, i] of e) i.unsubs.forEach((e) => e()), await t.unmount(r), n.delete(r);
		}
	};
}
//#endregion
//#region src/js/core/client-rendered-modules.ts
var hr = {
	conditions: "Core evaluates structured conditions and the adapter renders visibility.",
	repeater: "The adapter owns row markup and core owns row values.",
	signature: "The adapter owns its signature control and cleanup.",
	"file-upload": "The shared transport stages selected files and submits attachment capabilities.",
	"upload-manager": "Native file selection uses the shared staged upload transport.",
	"checkbox-radio": "Framework controls own checked state.",
	"text-limit": "Core validation enforces the structured minimum and maximum rules.",
	"date-picker": "The adapter renders the structured date input contract."
};
async function gr(t, n, r = Rt) {
	for (let [e, t] of Object.entries(hr)) r.get(`formie:${e}`) || r.register({
		moduleId: `formie:${e}`,
		version: 1,
		surfaces: ["client-rendered"],
		kind: "field",
		match: () => !0,
		setup: async (n) => (await n.emit("formie:browser:module:delegated", {
			capability: e,
			reason: t,
			target: n.target
		}), { destroy: () => void 0 })
	});
	let i = t instanceof HTMLFormElement ? t : t.querySelector("form"), a = () => {
		if (!i) return;
		let t = n.getState();
		e(i, n.getBrowserRequestOptions()), i.action = t.definition.submission.endpoint, t.session.tokens.csrf && i.setAttribute("data-formie-csrf-param", t.session.tokens.csrf.name);
		for (let [e, n] of Object.entries({
			handle: t.definition.handle,
			...t.session.tokens.csrf ? { [t.session.tokens.csrf.name]: t.session.tokens.csrf.value } : {}
		})) {
			let t = i.querySelector(`input[type="hidden"][name="${CSS.escape(e)}"]`);
			t || (t = document.createElement("input"), t.type = "hidden", t.name = e, i.append(t)), t.value = n;
		}
	};
	a();
	let o = n.subscribe(a), s = await rr({
		root: t,
		modules: n.getState().definition.modules,
		surface: "client-rendered",
		registry: r
	});
	n.setBrowserModuleGuard(s.assertReady), n.setBrowserModulePreparation(s.prepare);
	let c = n.on("formie:submit:result", (e) => {
		let t = e, r = t.completion;
		t.success && r && (r.behavior === "redirect" && typeof r.url == "string" ? r.target === "new-tab" ? window.open(r.url, "_blank", "noopener,noreferrer") : window.location.assign(r.url) : r.behavior === "reload" ? window.location.reload() : r.behavior === "reset" && n.reset()), s.result({
			ok: t.success,
			outcome: t.outcome,
			version: t.version,
			submissionUid: t.submissionUid,
			errors: t.errors,
			session: t.session,
			completion: t.completion,
			meta: t
		});
	});
	return {
		...s,
		destroy: async () => {
			c(), o(), await s.destroy();
		}
	};
}
//#endregion
export { l as FORMIE_HTML_EVENT_NAMES, sn as FormieValidator, be as LEGACY_FORMIE_DOM_EVENT_BRIDGES, xe as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, V as ModuleRegistry, De as bindLegacyDomEventCompatibility, ke as bindLegacyValidatorCompatibility, _e as buildFieldValueRegistry, Rt as clientRenderedModuleRegistry, E as createDebug, tr as createFormieClient, w as debugLog, T as debugWarn, ye as defineAddressModule, de as defineCaptchaModule, ue as definePassiveCaptchaModule, x as definePaymentModule, me as fieldKeyToInputName, mr as formie, c as getFieldModuleEventName, le as getFormieTranslations, s as getGlobalModuleLifecycleEventName, i as getScopedModuleLifecycleEventName, rr as hydrateFormieModules, fe as inputNameToFieldKey, te as isFormieDebugEnabled, se as mergeFormieTranslations, gr as mountClientRenderedModules, pe as normalizeFieldKey, u as normalizeFormieEventName, he as parseFieldReference, ve as resolveFieldReferenceFromFormData, ge as resolveFieldReferenceLive, Se as resolveLegacyCompatibilityOptions, C as setFormieDebugEnabled, ce as setFormieTranslations, oe as t, a as toDomEventName, ae as translate };
