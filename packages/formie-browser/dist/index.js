import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { f as t, s as n } from "./chunks/dist-DsjQF4UQ.js";
import { c as r, d as i, l as a, o, r as s, t as c, u as l } from "./chunks/event-names-BCI2FLD8.js";
import { a as u, c as d, d as f, f as p, i as m, l as h, n as g, o as _, p as v, r as y, s as b, t as x, u as S } from "./chunks/api-JXcZlBs7.js";
import { a as C, i as ee, n as te, r as ne, t as w } from "./chunks/debug-BV0DvdHx.js";
import { n as re, r as T, t as E } from "./chunks/theme-classes-Tv7q7ToE.js";
import { i as D, t as O } from "./chunks/csrf-DxHg_ZYt.js";
import { t as k } from "./chunks/http-BIzNeQTA.js";
import { a as ie, i as A, n as ae, r as oe, t as se } from "./chunks/i18n-BY1ds1BL.js";
import { n as ce, t as le } from "./chunks/api-DYrLvcqr.js";
import { n as ue, r as de, t as fe } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as pe, n as me, r as he, t as ge } from "./chunks/field-references.resolver-Bq207xxF.js";
import { t as _e } from "./chunks/api-CXzW6J-X.js";
//#region src/js/compatibility/event-map.ts
var ve = [
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
], ye = [
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
function be(e) {
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
function xe(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Se(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function Ce(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function we(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? Ce(r, t) : t;
}
function Te({ target: e, form: t, instance: n, options: r, unbinds: a }) {
	r.legacyDomEvents && ve.forEach((r) => {
		let o = (i) => {
			i instanceof CustomEvent && Se(r, i.detail) && xe(r.target === "document" ? document : t, r.legacyEvent, we(r, i.detail, e, t, n));
		};
		e.addEventListener(i(r.canonicalEvent), o), a.push(() => {
			e.removeEventListener(i(r.canonicalEvent), o);
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
function M(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function Ee({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	j(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && M(e.detail, a) && j(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && M(n.detail, a) && n.target instanceof Element && t.contains(n.target) && j(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && M(n.detail, a) && n.target instanceof Element && t.contains(n.target) && j(n.target, "formieValidatorClearError", {
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
function N(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function De(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Oe(e) {
	return e === "off" ? null : e;
}
function ke(e, t) {
	if (t) {
		e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true");
		return;
	}
	e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic");
}
//#endregion
//#region src/js/core/field-error-aria.ts
function Ae(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function je(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	if (r.length) {
		e.setAttribute("aria-describedby", r.join(" "));
		return;
	}
	e.removeAttribute("aria-describedby");
}
function Me(e, t) {
	e.setAttribute("aria-errormessage", t), Ae(e, t);
}
function Ne(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), je(e);
}
function P(e) {
	return !!e && e.hasAttribute("data-formie-validation-skip");
}
//#endregion
//#region src/js/core/validation-focus.ts
function Pe(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Fe(e) {
	return Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).find((e) => !P(e)) || (Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")).find((e) => !P(e)) ?? null);
}
function Ie(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function Le(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t;
		if (n.getAttribute("data-formie-field-has-error") !== "true" && n.querySelector("[data-formie-field-error]") === null) return;
		n.setAttribute("data-formie-field-has-error", "true"), E(n, e, "fieldLayoutError");
		let r = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let i = t;
			if (P(i)) return;
			i.setAttribute("aria-invalid", "true"), E(i, e, "fieldControlError"), i.setAttribute("data-formie-input-has-error", "true"), r && Me(i, r);
			let a = n.querySelector("[data-formie-instructions]");
			a?.id && Ae(i, a.id);
		});
	});
}
function Re(e) {
	return !!Pe(e) || !!Ie(e);
}
function ze(e) {
	let t = Pe(e);
	if (t) {
		let e = Fe(t);
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
	let n = Ie(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/transport/forms-api.ts
var F = w("general", "transport");
function Be(e) {
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
function Ve(e, t = "", n = {}) {
	if (Array.isArray(e)) {
		let r = e.map((e) => typeof e == "string" ? e : String(e ?? "")).filter((e) => e.trim() !== "");
		return t && r.length && (n[t] = (n[t] || []).concat(r)), n;
	}
	return e && typeof e == "object" && Object.entries(e).forEach(([e, r]) => {
		Ve(r, t ? `${t}.${e}` : e, n);
	}), n;
}
function He(e, t) {
	let n = e.success === !0, r = e.keepSubmitLoading === !0, i = e.errors, a = Ve(i || {}), o = a.form || [], s = {};
	Object.entries(a).forEach(([e, t]) => {
		if (e === "form") return;
		let n = e.split(".")[0];
		s[n] = (s[n] || []).concat(t);
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
async function Ue(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	F.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await k(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	return F.log("requestRender complete.", { hasHtml: !!a.html }), a;
}
async function We(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: Be(n)
		}
	});
	F.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await k(e, {
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
async function Ge(e, t, n, r = {}, i) {
	let a = new URL(e, window.location.origin);
	a.searchParams.set("handle", t), n && a.searchParams.set("renderId", n), i && a.searchParams.set("requestToken", i), F.log("requestRefreshTokens start.", {
		endpoint: a.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let o = await k(a.toString(), r);
	return F.log("requestRefreshTokens complete.", { hasRefreshTokens: !!o.refreshTokens }), o.refreshTokens || o;
}
async function Ke(e, t, n) {
	let r = new URL(e, window.location.origin), i = new FormData();
	n && i.append("pageId", n), t && ([
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
	}), O(i, t)), F.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await k(r.toString(), {
		method: "POST",
		body: i,
		profile: t?.dataset.formieRequestProfile
	});
	if (t && a.session) {
		let e = a.session, n = t.querySelector("input[name=\"expectedVersion\"]"), r = t.querySelector("input[name=\"requestToken\"]");
		n && (n.value = String(e.version)), r && e.tokens?.request && (r.value = e.tokens.request);
	}
	return F.log("requestSetPage complete.", a), a;
}
function qe(e, t) {
	let r = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && i.append(e, n);
	}), O(i, t), F.log("clearSubmissionOnUnload start.", { requestUrl: r.toString() });
	try {
		if (t.dataset.formieRequestProfile !== "cross-origin-public" && typeof navigator.sendBeacon == "function" && navigator.sendBeacon(r.toString(), i)) return;
	} catch {}
	n(r.toString(), {
		method: "POST",
		body: i,
		keepalive: !0,
		headers: { Accept: "application/json" }
	}, { profile: t.dataset.formieRequestProfile });
}
async function Je(e, t) {
	let r = (e.getAttribute("method") || "POST").toUpperCase(), i = e.getAttribute("action") || window.location.href, a = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	F.log("submitForm start.", {
		method: r,
		action: i,
		submitAction: t.get("submitAction")
	});
	let o = await n(i, {
		method: r,
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
	let c = He(await o.json(), a);
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
], I = w("general", "pipeline");
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
	let n = d(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return tt(i, e), rt(i, nt(i, r)), i.set("submitAction", t), i;
}
function at(e, t) {
	if (t !== "submit") return !1;
	let n = d(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function ot(e, t, n, r = {}) {
	I.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = at(e, t), c = {
		form: e,
		action: t,
		formData: it(e, t),
		abort: (e) => {
			i = !0, a = e, I.warn("Pipeline aborted.", { reason: e });
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
				let { scope: t, final: n } = h(e.form), i = r.validator.submit(n ? e.form : t, { final: n });
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
		e.failed.length > 0 && I.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && I.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let u = r.preflightOnly ? Xe : Ye;
	for (let e of u) {
		if (I.log("Stage start.", {
			stage: e,
			action: t
		}), i) return I.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ze(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && I.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ze(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && I.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && I.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return I.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await l[e](c);
		I.log("Stage runner complete.", {
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
			t.failed.length > 0 && I.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ze(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && I.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && I.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return I.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await n.emitSafe("formie:submit:after", r);
				t.failed.length > 0 && I.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await n.emitSafe("formie:submit:final:after", r);
				t.failed.length > 0 && I.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return I.warn("Pipeline short-circuited by failed stage.", {
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
		e.failed.length > 0 && I.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:after", d);
		e.failed.length > 0 && I.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return I.log("Pipeline completed.", {
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
var L = /* @__PURE__ */ new WeakMap();
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
function R(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function pt(e) {
	let t = L.get(e);
	typeof t == "number" && (window.clearTimeout(t), L.delete(e));
}
function mt(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function ht(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function gt(e, t) {
	return t === "bottom-form" ? ht(e) : mt(e);
}
function _t(e, t) {
	return t === "top-form" ? mt(e) : t === "bottom-form" && !R(e) ? ht(e) : e;
}
function vt(e) {
	let t = ut(e), n = gt(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), E(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function yt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), E(n, e, "message", "messageError"), n.setAttribute("role", "alert"), ke(n, Oe(N(e))), n;
}
function bt(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = _t(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), E(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function xt(e) {
	return ct(e, (t) => {
		E(t, e, "fieldErrors");
	});
}
function St(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		T(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), T(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), Ne(n, i);
		});
	}), S(e);
}
function Ct(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), T(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function z(e) {
	pt(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), T(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), lt(e) === "message" && R(e) || u(e, !1);
}
function wt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Tt(e, t) {
	let n = Oe(N(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = e.querySelector(`[data-formie-field-handle="${t}"]`);
		if (!i) return;
		let a = xt(i), o = a.id && a.id.trim() ? a.id : `${t}-errors`;
		a.id = o, ke(a, n), E(i, e, "fieldLayoutError"), i.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${o}-${n + 1}`, E(r, e, "fieldError"), r.textContent = t, a.appendChild(r);
		});
		let s = a.querySelector("[data-formie-field-error]")?.id;
		i.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), E(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), s && Me(n, s);
			let r = i.querySelector("[data-formie-instructions]");
			r?.id && Ae(n, r.id);
		});
	}), S(e);
}
function Et(e, t) {
	let n = vt(e), r = yt(e, n);
	E(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), n.setAttribute("role", "alert"), E(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function Dt(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.meta || {}, n = String(t.paymentStatus || "");
	return n === "actionRequired" || n === "pending" || n === "unknown";
}
function Ot(e, t) {
	let n = vt(e), r = yt(e, n);
	E(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), E(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function kt(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || lt(e) === "message" && dt(e) !== "";
}
function At(e, t) {
	let n = dt(e);
	if (!n) return;
	let r = bt(e, n);
	E(r, e, "message", "messageSuccess"), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-success", "true"), r.setAttribute("role", "status"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true");
	let i = document.createElement("div");
	i.setAttribute("data-formie-success", "true"), E(i, e, "success"), i.innerHTML = t, r.appendChild(i), R(e) && u(e, !0);
	let a = ft(e);
	if (a !== null) {
		let t = window.setTimeout(() => {
			L.delete(e), z(e);
		}, a);
		L.set(e, t);
	}
}
function B(e, t) {
	if (St(e), Ct(e), z(e), wt(e), t.ok) {
		kt(e, t) && At(e, t.message || "");
		return;
	}
	if (!t.ok) {
		if (Dt(t)) {
			let n = t.meta || {}, r = String(n.paymentMessage || "").trim();
			r && Ot(e, r);
			return;
		}
		t.fieldErrors && Tt(e, t.fieldErrors), t.formErrors?.length ? Et(e, t.formErrors) : !t.fieldErrors && t.message && Et(e, [t.message]), ze(e);
	}
}
//#endregion
//#region src/js/core/submit-flow.ts
var jt = w("general", "submit-flow");
function Mt(e) {
	return !(!e.ok && e.stage === "validate");
}
function Nt(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function Pt(e) {
	St(e), Ct(e), z(e), wt(e);
}
async function Ft(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	Pt(r), _(r, c || null);
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
		}), B(r, f), d(f), y(r, f, s), Mt(f) && await u(f);
	} catch (e) {
		f = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, B(r, f), d(f), jt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		Nt(f) || m(r);
	}
	return f;
}
//#endregion
//#region src/js/events/event-bus.ts
var It = class {
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
}, Lt = new V(), Rt = {
	"address-finder": () => import("./chunks/address-finder-5RA475tB.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-Y9fxVr0S.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-D23p1mBG.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-DDfyZ_EH.js").then((e) => e.placeKitModule)
}, zt = {
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
}, Bt = {
	calculations: () => import("./chunks/calculations-CNhBzTDB.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-DHP3DW3Y.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-C_seffiw.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-BehFjWur.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-B9IK9561.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-D39CIxKN.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-BBpPi__M.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-BZ0_fkoD.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-BUAXn4ka.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-BTCkqyd_.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-C28bjH9X.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-_s3H7Hd-.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-BhE3Ekly.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-KA0cMw5N.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-BL5DbZ9q.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-FGvfUvSl.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-67B-sTbF.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-Cuun7L4F.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-BXazJTT-.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-Ci7TEdDl.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-D9eqvIxh.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-CrHukOI-.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-BN6TdE1D.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-DFDdmpW4.js").then((e) => e.textLimitModule)
}, Vt = {
	bpoint: () => import("./chunks/bpoint-B6oThOrT.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-tPYhyOe-.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-BCyT7T1P.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-DPllETSi.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-BjRCimbQ.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-B_zsm46C.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-JggaFNop.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-Dvokb-rf.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-DihbUPwB.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-DRwmwHxv.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-CaBoWTM3.js").then((e) => e.stripeModule)
}, Ht = {
	...Bt,
	...Rt,
	...zt,
	...Vt
}, H = /* @__PURE__ */ new Map();
async function Ut(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(Ht, e.slice(7)) ? Ht[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	H.has(e) || H.set(e, r().catch((t) => {
		throw H.delete(e), t;
	}));
	let i = await H.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function Wt(e, t, n) {
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
async function Gt(e, n) {
	t(e);
	let r = n.matchContext.surface ?? "server-rendered", { root: i, form: a } = n.setupContext, o = /* @__PURE__ */ new Map(), s = /* @__PURE__ */ new Map(), c = [], l = !1, u = Promise.resolve(), d = !1, f = async (e, t) => {
		s.set(e.key, e);
		let i = {
			key: e.key,
			moduleId: e.moduleId,
			required: e.required,
			surface: r,
			code: "MODULE_UNAVAILABLE",
			message: "A form feature could not start. Reload the page or contact the site administrator."
		};
		console.error("[formie] Browser module failure", i, t), await n.setupContext.emit("formie:browser:module:error", i);
	}, p = async (e) => {
		try {
			await e.destroy();
		} catch (t) {
			console.error("[formie] Browser module disposal failed", t), await n.setupContext.emit("formie:browser:module:error", {
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
		let t = new Set(e.entries.filter((e) => e.surfaces.includes(r)).map((e) => e.key));
		for (let e of s.keys()) t.has(e) || s.delete(e);
		for (let [e, n] of o) if (!t.has(e)) {
			for (let { instance: e } of n.values()) await p(e), c.splice(c.indexOf(e), 1);
			o.delete(e), s.delete(e);
		}
		for (let t of e.entries) {
			if (l || !t.surfaces.includes(r)) continue;
			s.has(t.key) && s.set(t.key, t);
			let e = Wt(t, i, a), u = o.get(t.key) ?? /* @__PURE__ */ new Map();
			o.set(t.key, u);
			for (let [t, n] of u) e.includes(t) || (await p(n.instance), u.delete(t), c.splice(c.indexOf(n.instance), 1));
			let d;
			try {
				d = await Ut(t.moduleId, n.registry);
			} catch (e) {
				s.has(t.key) || await f(t, e);
				continue;
			}
			let m = !1, h = !1;
			for (let a of e) {
				if (l) return;
				let e = JSON.stringify([
					t.moduleId,
					t.config,
					t.required
				]), o = u.get(a);
				if (o?.config === e) continue;
				let g = {
					...n.setupContext,
					target: a,
					entryKey: t.key,
					surface: r,
					scope: t.targets[0]?.targetType ?? "form",
					options: t.config
				};
				try {
					if (o) {
						if (o.instance.update && o.moduleId === t.moduleId && o.required === t.required) {
							await o.instance.update(g), o.config = e;
							continue;
						}
						await p(o.instance), u.delete(a), c.splice(c.indexOf(o.instance), 1);
					}
					if (d.surfaces && !d.surfaces.includes(r)) throw Error(`Module ${t.moduleId} does not support ${r}.`);
					if (!d.match({
						...n.matchContext,
						mode: "server-rendered",
						target: a,
						scope: g.scope,
						manifestItem: t
					})) throw Error(`Module ${t.moduleId} does not support the rendered target.`);
					let s = await d.setup(g);
					if (!s) throw Error(`Module ${t.moduleId} did not initialize.`);
					if (l || !i.contains(a) && a !== i) {
						await p(s);
						continue;
					}
					s.key = t.key, s.moduleId = t.moduleId, s.target = a;
					let m = s.assertReady;
					s.assertReady = () => {
						try {
							m?.();
						} catch (e) {
							if (f(t, e), t.required) throw Error("A required form feature could not start.");
						}
					};
					let _ = s.onBeforeStage, v = s.onAfterStage;
					s.onBeforeStage = async (e) => {
						try {
							await _?.(e);
						} catch (n) {
							await f(t, n), t.required && e.abort("A required form feature could not complete. Reload the page or contact the site administrator.");
						}
					}, s.onAfterStage = async (e, n) => {
						try {
							await v?.(e, n);
						} catch (n) {
							await f(t, n), t.required && e.abort("A required form feature could not complete.");
						}
					}, u.set(a, {
						instance: s,
						config: e,
						moduleId: t.moduleId,
						required: t.required
					}), c.push(s), h = !0, await n.setupContext.emit("formie:browser:module:mount", {
						key: t.key,
						moduleId: t.moduleId,
						target: a
					});
				} catch (e) {
					m = !0, s.has(t.key) || await f(t, e);
				}
			}
			!m && (h || e.length === 0) && s.delete(t.key);
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
	}), c.updateManifest = async (n) => {
		t(n), e = n, u = u.then(_), await u;
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
var Kt = "formie:formStartedAt:";
function qt(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${Kt}${n}` : null, i = r ? sessionStorage.getItem(r) : null;
	i || (i = String(Date.now()), r && sessionStorage.setItem(r, i)), t.value = i;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var Jt = /* @__PURE__ */ new Set([
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
function Yt(e) {
	return U(e, /* @__PURE__ */ new WeakSet());
}
function Xt(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !D(n, t) && !Jt.has(n);
}
function Zt(e) {
	return Yt(Array.from(new FormData(e).entries()).filter(([t]) => Xt(String(t || ""), e)));
}
function Qt(e, t = {}) {
	let n = null, r = !1, i = !1, a = null, o = null, s = null, c = () => {
		a !== null && (window.cancelAnimationFrame(a), a = null), o !== null && (window.clearTimeout(o), o = null), s !== null && (window.clearTimeout(s), s = null);
	}, l = () => r ? (i = Zt(e) !== n, i) : !1, u = () => {
		n = Zt(e), r = !0, i = !1;
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
var $t = {
	rule: ({ input: e, getRule: t }) => !t("email") || !e.value || e.value.length < 1 || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function en(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function tn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !P(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var nn = {
	required: {
		rule: ({ input: e, getRule: t }) => {
			if (!t("required") || e.type === "hidden") return !0;
			if (e.type === "checkbox" || e.type === "radio") {
				let t = e.form?.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`) || [];
				return t.length ? Array.from(t).some((e) => e instanceof HTMLInputElement && e.checked) : e instanceof HTMLInputElement ? e.checked : !0;
			}
			return e.value.trim() !== "";
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? n("{label} cannot be blank.", { label: t })
	},
	email: $t,
	url: {
		rule: ({ input: e, getRule: t }) => {
			if (!t("url") || !e.value || e.value.length < 1) return !0;
			try {
				return new URL(e.value), !0;
			} catch {
				return !1;
			}
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? n("{label} is not a valid URL.", { label: t })
	},
	number: {
		rule: ({ input: e, getRule: t }) => {
			let n = t("number");
			if (!n || !e.value || e.value.trim() === "") return !0;
			let r = parseFloat(e.value);
			if (Number.isNaN(r)) return !1;
			if (n !== !0 && typeof n == "object") {
				let e = typeof n.min == "number" ? n.min : null, t = typeof n.max == "number" ? n.max : null;
				if (e !== null && r < e || t !== null && r > t) return !1;
			}
			return !0;
		},
		message: ({ input: e, label: t, getRule: n, t: r }) => {
			let i = n("number"), a = i !== !0 && i && typeof i == "object" && typeof i.min == "number" ? i.min : null, o = i !== !0 && i && typeof i == "object" && typeof i.max == "number" ? i.max : null;
			return a !== null && o !== null || a !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? r("{label} must be no less than {min}.", {
				label: t,
				min: a
			}) : o === null ? e.getAttribute("data-formie-validation-number-message") ?? e.getAttribute("data-formie-pattern-number-message") ?? e.getAttribute("data-pattern-number-message") ?? r("{label} is not a valid number.", { label: t }) : e.getAttribute("data-formie-validation-number-max-message") ?? r("{label} must be no greater than {max}.", {
				label: t,
				max: o
			});
		}
	},
	match: {
		rule: (e) => {
			let t = tn(e);
			return !t || t.value === e.input.value;
		},
		message: (e) => {
			let t = tn(e)?.closest("[data-formie-field-handle]"), n = en(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, rn = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, W = w("general", "validator");
function G(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function an(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var on = class {
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
			patterns: rn,
			...t
		}, Object.entries(nn).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		W.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, a("ready"), { validator: this });
	}
	inputs(e = null) {
		if (G(e)) return P(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => G(e) && !P(e));
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
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), Ne(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, a("clear-error"), { validator: this }), S(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= ct(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let o = r.getAttribute("data-formie-field-handle") || "field", s = `${o}-error`;
		i.id = i.id || `${o}-errors`, ke(i, De(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			P(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), Me(t, s));
		});
		for (let e = r; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, a("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: n
		}), S(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: A,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? A("{label} is invalid.", { label: r.label });
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
		W.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, a("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		if (e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]")) return !1;
		if (e.closest("[data-formie-page-hidden]")) return !!t.includeHiddenPages;
		let n = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-rich-text]");
		return n instanceof HTMLElement ? an(n) : an(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && G(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		if (e.target instanceof HTMLElement && G(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && !(e instanceof CustomEvent)) {
			if (e.target instanceof HTMLSelectElement) {
				this.activated.add(e.target), this.validate(e.target);
				return;
			}
			e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio") && (this.activated.add(e.target), this.validate(e.target));
		}
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && G(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
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
function sn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function cn(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !sn(e));
}
function ln(e) {
	let { final: t } = h(e);
	return "submit";
}
function un(e) {
	return !cn(e, ln(e));
}
function dn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || un(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var K = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", fn = 300, pn = "/actions/formie/server/forms/render", mn = "/api", hn = "/actions/formie/server/forms/refresh-tokens", gn = "/actions/formie/server/submissions/submit", _n = "/actions/formie/server/submissions/set-page", vn = "/actions/formie/server/submissions/clear-submission", yn = "/actions/formie/file-upload/hydrate", q = w("general", "client"), bn = /* @__PURE__ */ new Set();
function J(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function xn(e) {
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
		staticCache: xn(t),
		autoVisible: J(t.formieAutoVisible, !0),
		compatibility: J(t.formieCompatibility, !1)
	};
}
function Sn(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function Cn(e) {
	return e || "rest";
}
function wn(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function Tn(e, t) {
	bn.has(e) || (bn.add(e), q.warn(t));
}
function En(e, t) {
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
	return n ? n.includes(t) ? n : En(t, n) : t;
}
function Dn(e, t) {
	return X(e.endpoint || t.dataset.formieEndpoint, pn);
}
function On(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : En(mn, n) : mn;
}
function kn(e, t) {
	return X(t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint, hn);
}
function An(e, t) {
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
function jn(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = X(r, gn), a = t.getAttribute("action");
	t.setAttribute("action", An(a, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href"), n = X(r, _n);
		e.setAttribute("href", An(t, n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(r, yn));
	});
}
function Mn(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Nn(e) {
	return J(e.dataset.formieAutomaticSubmissionState, !0);
}
function Pn(e, t, n) {
	return X(n.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint, vn);
}
function Fn(e) {
	return Mn(e.dataset.formieUnloadWarning);
}
function In(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function Ln(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function Rn(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function zn(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function Bn(e) {
	return zn(window.location.href, "resumeToken") || zn(e.getAttribute("action"), "resumeToken");
}
function Vn(e) {
	return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function Hn(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function Un(e) {
	return Math.max(0, Hn(e.dataset.formieSubmitDelay, fn));
}
function Z(e) {
	return Mn(e.dataset.formieValidationOnSubmit);
}
async function Wn(e) {
	let t = Un(e);
	t < 1 || await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Gn(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function Kn(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Gn(n, "data-formie-modules"), i = Gn(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function qn(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function Jn(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function Q(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function Yn(e, t) {
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
async function Xn(e, t) {
	let n = Sn(t.mode), r = Cn(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!wn(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? On(t, e) : Dn(t, e), c = r === "graphql" ? await We(s, a, o, t) : await Ue(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function Zn(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await Ge(kn(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	Yn(n, i), v(e, "formie:refresh-tokens:refreshed", i);
}
function Qn(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = Pn(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		if (e) {
			t.setAttribute("data-formie-pending-action", e);
			return;
		}
		t.removeAttribute("data-formie-pending-action");
	};
	if (Fn(t)) {
		let n = Qt(t, { shouldWarn: () => !Rn(t) }), r = (e) => {
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
				Vn(n) && In(t, "set-page");
				return;
			}
			n.preventDefault();
			let r = n.currentTarget, i = r?.getAttribute("data-formie-page-id"), a = r?.getAttribute("href");
			if (i && a) {
				g(t, i), v(e, "formie:page:navigate", {
					pageId: i,
					href: a
				});
				try {
					let n = await Ke(a, t, i);
					v(e, "formie:page:navigate:after", {
						pageId: i,
						href: a,
						response: n
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), v(e, "formie:page:navigate:error", {
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
	}), !Nn(t)) {
		let e = !1, n = () => {
			e || Rn(t) || Bn(t) || (e = !0, qe(s, t));
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
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), p = t.getAttribute("data-formie-pending-action"), g = t.querySelector("input[name=\"submitAction\"]"), y = d || p || g?.value || "submit", b = null, x = !1;
		try {
			if (s) b = await Ft({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Z(t),
				action: y,
				submitter: l,
				waitForSubmitDelay: Wn,
				onRefreshTokensAfterSubmit: async () => {
					await Zn(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					v(e, "formie:submit:result", t);
				}
			});
			else {
				if (Pt(t), _(t, l), await Wn(t), b = await ot(t, y, r, {
					validator: i,
					validateOnSubmit: Z(t),
					preflightOnly: !0
				}), b.ok) {
					f(t, y), c = !0, In(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, Ln(t), m(t), i && Z(t)) {
							let { scope: e, final: n } = h(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && B(t, {
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
					x = !0;
					return;
				}
				B(t, b), v(e, "formie:submit:result", b), Ln(t);
			}
		} catch (n) {
			c = !1, b = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, B(t, b), v(e, "formie:submit:result", b), Ln(t);
		} finally {
			u(null), !s && !x && !Nt(b) && m(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	});
}
async function $n(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = kn(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await Ge(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	o && n && (Yn(n, o), v(e, "formie:refresh-tokens:after", o));
}
function er() {
	let n = /* @__PURE__ */ new Map(), r = new V(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
		"prepare",
		"normalize",
		"validate",
		"challenge",
		"payment",
		"send",
		"result"
	], s = async (e) => {
		let t = a.get(e);
		if (t) {
			await t;
			return;
		}
		let r = (async () => {
			q.log("Unmount requested.", { target: Q(e) });
			let t = i.get(e);
			t && (t(), i.delete(e));
			let r = n.get(e);
			if (!r) {
				q.log("Unmount skipped (no mounted state).", { target: Q(e) });
				return;
			}
			v(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null;
			for (let e of r.modules) await e.destroy();
			r.modules = [], r.bus.clear(), n.delete(e), v(e, "formie:unmount:after", { id: r.instance.id }), q.log("Unmount complete.", {
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
		let u = n.get(a);
		if (u) return q.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: Q(a)
		}), u.instance;
		let d = new It(), f = [], m = a?.id || `formie-${n.size + 1}`, h = Y(a), g = {
			...h,
			...c,
			mode: Sn(c.mode ?? h.mode),
			transport: Cn(c.transport ?? h.transport)
		}, _ = be(g.compatibility), y = await Xn(a, g), x = wn(a);
		x && e(x, g), g.staticCache = c.staticCache ?? xn(x ? x.dataset : a.dataset);
		let C;
		try {
			C = Kn(a, x), y?.modules && t(y.modules), C?.modules && t(C.modules);
		} catch (e) {
			if (x) {
				x.addEventListener("submit", (e) => {
					e.preventDefault(), e.stopImmediatePropagation();
				}, !0);
				let e = document.createElement("div");
				e.setAttribute("role", "alert"), e.textContent = "This form requires a compatible Formie browser package. Update Formie and its browser packages together.", x.prepend(e);
			}
			throw e;
		}
		let ee = y || C ? {
			...y || {},
			...C || {}
		} : null, te = ee?.theme, ne = {}, w = ee?.modules ?? {
			contractVersion: 1,
			entries: []
		};
		t(w), q.log("Resolved mount payload.", {
			target: Q(a),
			hasRenderPayload: !!y,
			hasEmbeddedPayload: !!C,
			moduleCount: w.entries.length
		});
		let T = re(a, te, x), E = x ? new on(x, {
			live: Mn(x.dataset.formieValidationOnFocus),
			errorAriaLive: N(x),
			errorMessage: x.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: T.fieldLayoutError || [],
			inputErrorClass: T.fieldControlError || [],
			messagesClass: T.fieldErrors || [],
			messageClass: T.fieldError || []
		}) : null;
		if (x && E) {
			let e = x;
			e.formieValidation = E, ne.validation = E;
			let t = {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			};
			v(x, "formie:validator:ready", t), v(a, "formie:validator:ready", t);
		}
		x && (qt(x), g.themeConfig && typeof g.themeConfig == "object" && x.setAttribute("data-formie-theme-config", JSON.stringify(g.themeConfig)), g.theme && g.theme !== "formie" && x.setAttribute("data-formie-frontend-theme", g.theme), (y || g.endpoint || a.dataset.formieEndpoint) && jn(a, x, g), g.mode === "server-rendered" && Re(x) && (Le(x), ze(x)), S(x)), Object.keys(T).length && v(a, "formie:theme:applied", { hasClasses: !0 });
		let D = await Gt(w, {
			registry: r,
			matchContext: {
				root: a,
				form: x,
				mode: g.mode
			},
			setupContext: {
				formId: m,
				root: a,
				form: x,
				target: a,
				scope: "form",
				state: ne,
				on: (e, t) => d.on(e, t),
				emit: (e, t) => (v(a, e, t), d.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && q.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		q.log("Module setup complete.", {
			target: Q(a),
			moduleInstances: D.length
		});
		let O = {
			id: m,
			root: a,
			submit: async (e = "submit") => {
				if (q.log("Submit requested.", {
					id: m,
					target: Q(a),
					action: e
				}), !x) return {
					ok: !1,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				let t = x.querySelector("input[name=\"submitAction\"]");
				if (t && (t.value = e), x.getAttribute("data-formie-loading") === "true") return {
					ok: !1,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				let n = x.querySelector(`[data-formie-action="${e}"]`), r = await Ft({
					id: m,
					target: a,
					form: x,
					bus: d,
					validator: E,
					validateOnSubmit: Z(x),
					action: e,
					submitter: n,
					waitForSubmitDelay: Wn,
					onRefreshTokensAfterSubmit: async () => {
						await Zn(a, g, x);
					},
					dispatchSubmitResult: (e) => {
						v(a, "formie:submit:result", e);
					}
				});
				return q.log("Submit completed.", {
					id: m,
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
		x && (Ee({
			target: a,
			form: x,
			validatorDetail: E ? {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			} : null,
			options: _,
			unbinds: f
		}), Te({
			target: a,
			form: x,
			instance: O,
			options: _,
			unbinds: f
		})), x && (Qn(a, x, g, d, E, f), E && (f.push(b(x, E, a)), f.push(dn(x))), await $n(a, g, x), x.dispatchEvent(new CustomEvent("formie:state:reset")), window.setTimeout(() => {
			x.dispatchEvent(new CustomEvent("formie:state:reset"));
		}, 350)), o.forEach((e) => {
			let t = d.on(`formie:stage:${e}:before`, async (t) => {
				v(a, `formie:stage:${e}:before`, t);
			}), n = d.on(`formie:stage:${e}:before`, async (e) => {
				for (let t of D) t.onBeforeStage && await t.onBeforeStage(e);
			}), r = d.on(`formie:stage:${e}:after`, async (t) => {
				v(a, `formie:stage:${e}:after`, t);
			}), i = d.on(`formie:stage:${e}:after`, async (e) => {
				let t = e;
				for (let e of D) e.onAfterStage && await e.onAfterStage(t, t.result);
			});
			f.push(t, n, r, i);
		});
		let k = d.on("formie:submit:before", async (e) => {
			v(a, "formie:submit:before", e);
		}), ie = d.on("formie:submit:after", async (e) => {
			v(a, "formie:submit:after", e);
		}), A = d.on("formie:submit:final:before", async (e) => {
			v(a, "formie:submit:final:before", e);
		}), ae = d.on("formie:submit:final:after", async (e) => {
			v(a, "formie:submit:final:after", e);
		});
		return f.push(k, ie, A, ae), n.set(a, {
			options: g,
			bus: d,
			form: x,
			validator: E,
			modules: D,
			unbinds: f,
			instance: O
		}), v(a, "formie:mount:after", {
			id: m,
			mode: g.mode
		}), x instanceof HTMLFormElement && p(x), q.log("Mount complete.", {
			id: m,
			target: Q(a),
			mode: g.mode
		}), O;
	}, l = (e, t) => {
		if (!t.autoVisible || qn(e) || typeof IntersectionObserver > "u") return c(e, t);
		if (n.has(e)) return Promise.resolve(n.get(e)?.instance || null);
		if (i.has(e)) return q.log("Mount deferred (already waiting visibility).", { target: Q(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((n) => {
			n.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), q.log("Visibility reached, proceeding mount.", { target: Q(e) }), c(e, {
				...t,
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
		update: async (e, t) => {
			let r = n.get(e);
			if (!r) return c(e, {
				...Y(e),
				...t,
				mode: t.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...t
			};
			let i = t.payload?.theme || r.options.payload?.theme || Kn(e, r.form)?.theme, a = re(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && v(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => n.get(e)?.instance || null,
		refreshForCache: async (e) => {
			Tn("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let t = null;
			if (t = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !t) {
				q.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = n.get(t), i = wn(t), a = r?.options || Y(t);
			if (!i) {
				q.warn("refreshForCache found no form element for target.", { target: Q(t) });
				return;
			}
			let o = a.formHandle || t.dataset.formieHandle || i.dataset.formieHandle, s = kn(a, t), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				q.warn("refreshForCache found no form handle for target.", { target: Q(t) });
				return;
			}
			let l = await Ge(s, o, c, a, i?.querySelector("input[name=\"requestToken\"]")?.value);
			l && (Yn(i, l), v(t, "formie:refresh-tokens:after", l));
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
			let t = e || document;
			q.log("Observer started.", { scope: t === document ? "document" : t });
			let r = new MutationObserver((e) => {
				e.forEach((e) => {
					e.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(K) && (q.log("Observer detected new root.", { target: Q(e) }), l(e, Y(e))), e.querySelectorAll(K).forEach((e) => {
							q.log("Observer detected new nested root.", { target: Q(e) }), l(e, Y(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (n.has(e) && (q.log("Observer detected removed root.", { target: Q(e) }), s(e)), e.querySelectorAll(K).forEach((e) => {
							n.has(e) && (q.log("Observer detected removed nested root.", { target: Q(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(t, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), q.log("Observer stopped."), i.forEach((e, n) => {
					Jn(n, t) && (e(), i.delete(n));
				});
				let e = [];
				t instanceof Element && t.matches(K) && e.push(t), t.querySelectorAll(K).forEach((t) => {
					e.push(t);
				}), e.forEach((e) => {
					n.has(e) && s(e);
				});
			};
		}
	};
}
//#endregion
//#region src/js/core/hydrate-modules.ts
var tr = w("general", "module-hydrator");
async function nr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.modules ?? {
		contractVersion: 1,
		entries: []
	}, i = e.mode ?? "server-rendered", a = e.registry ?? new V(), o = new It(), s = await Gt(r, {
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
	return tr.log("Hydrated module manifest.", {
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
			await rr(s), o.clear();
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
async function rr(e) {
	for (let t of e) try {
		await t.destroy();
	} catch (e) {
		console.error("[formie] Failed to destroy module instance.", e), tr.warn("Failed destroying module instance.", { error: e });
	}
}
//#endregion
//#region src/js/core/formie.ts
function $(e) {
	return e instanceof Element;
}
function ir(e) {
	return e.ok;
}
function ar(e) {
	return typeof e == "string" ? `selector "${e}"` : $(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function or(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) $(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function sr(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : $(e) ? [e] : or(e);
}
function cr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function lr(e) {
	let t = sr(e);
	return t.length > 0 || typeof e != "string" ? t : (await cr(), sr(e));
}
function ur(e) {
	return typeof e == "string" ? document : $(e) ? e.getRootNode() : document;
}
function dr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function fr(e, t, n, r) {
	let i = [], a = dr(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), l = [];
		if (e.onReady?.(s), l.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), ir(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
		})), e.onEvent) for (let t of c) l.push(s.on(t, (n) => {
			e.onEvent?.({
				name: t,
				payload: n
			}, s);
		}));
		n.set(o, {
			instance: s,
			unsubs: l
		}), i.push(s);
	}
	return i;
}
async function pr(e) {
	let t = e.client ?? er(), n = /* @__PURE__ */ new Map(), r = await lr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${ar(e.element)}.`);
	await fr(e, t, n, r);
	let i = e.observe ? t.observe(ur(e.element)) : null;
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
			let r = sr(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : fr(e, t, n, r);
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
var mr = {
	conditions: "Core evaluates structured conditions and the adapter renders visibility.",
	repeater: "The adapter owns row markup and core owns row values.",
	signature: "The adapter owns its signature control and cleanup.",
	"file-upload": "The shared transport stages selected files and submits attachment capabilities.",
	"upload-manager": "Native file selection uses the shared staged upload transport.",
	"checkbox-radio": "Framework controls own checked state.",
	"text-limit": "Core validation enforces the structured minimum and maximum rules.",
	"date-picker": "The adapter renders the structured date input contract."
};
async function hr(t, n, r = Lt) {
	for (let [e, t] of Object.entries(mr)) r.get(`formie:${e}`) || r.register({
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
	let o = n.subscribe(a), s = await nr({
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
export { c as FORMIE_HTML_EVENT_NAMES, on as FormieValidator, ve as LEGACY_FORMIE_DOM_EVENT_BRIDGES, ye as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, V as ModuleRegistry, Te as bindLegacyDomEventCompatibility, Ee as bindLegacyValidatorCompatibility, he as buildFieldValueRegistry, Lt as clientRenderedModuleRegistry, w as createDebug, er as createFormieClient, te as debugLog, ne as debugWarn, _e as defineAddressModule, le as defineCaptchaModule, ce as definePassiveCaptchaModule, x as definePaymentModule, fe as fieldKeyToInputName, pr as formie, s as getFieldModuleEventName, se as getFormieTranslations, o as getGlobalModuleLifecycleEventName, r as getScopedModuleLifecycleEventName, nr as hydrateFormieModules, ue as inputNameToFieldKey, ee as isFormieDebugEnabled, ae as mergeFormieTranslations, hr as mountClientRenderedModules, de as normalizeFieldKey, l as normalizeFormieEventName, pe as parseFieldReference, ge as resolveFieldReferenceFromFormData, me as resolveFieldReferenceLive, be as resolveLegacyCompatibilityOptions, C as setFormieDebugEnabled, oe as setFormieTranslations, A as t, i as toDomEventName, ie as translate };
