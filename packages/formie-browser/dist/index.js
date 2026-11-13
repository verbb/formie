import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { n as t, o as n, p as r } from "./chunks/dist-r-cqeuZD.js";
import { c as i, d as a, l as o, o as s, r as c, t as l, u } from "./chunks/event-names-BCI2FLD8.js";
import { _ as d, a as f, c as p, d as m, f as h, g, h as _, i as v, l as y, m as b, o as x, p as S, r as C, s as w, t as T, u as E, v as ee } from "./chunks/api-Cgy32byY.js";
import { a as te, i as D, n as O, r as k, t as A } from "./chunks/debug-rhzqP1j2.js";
import { i as j, r as ne, t as M } from "./chunks/theme-classes-DAQuEqdP.js";
import { n as N } from "./chunks/skip-CEW89ExY.js";
import { i as re, t as ie } from "./chunks/csrf-DxHg_ZYt.js";
import { n as P, t as ae } from "./chunks/http-C8fs27Xp.js";
import { i as oe } from "./chunks/async-2GdXNFCE.js";
import { a as se, i as ce, n as le, r as ue, t as de } from "./chunks/i18n-BY1ds1BL.js";
import { n as fe, t as pe } from "./chunks/api-BPtH5RBA.js";
import { n as me, r as he, t as ge } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as _e, n as ve, r as ye, t as be } from "./chunks/field-references.resolver-BV79piPl.js";
import { t as xe } from "./chunks/api-CmuOZ0i3.js";
//#region src/js/compatibility/event-map.ts
var Se = [
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
], Ce = [
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
function we(e) {
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
function Te(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Ee(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function De(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function Oe(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? De(r, t) : t;
}
function ke({ target: e, form: t, instance: n, options: r, unbinds: i }) {
	r.legacyDomEvents && Se.forEach((r) => {
		let o = (i) => {
			i instanceof CustomEvent && Ee(r, i.detail) && Te(r.target === "document" ? document : t, r.legacyEvent, Oe(r, i.detail, e, t, n));
		};
		e.addEventListener(a(r.canonicalEvent), o), i.push(() => {
			e.removeEventListener(a(r.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function F(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Ae(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function je({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	F(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && Ae(e.detail, a) && F(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && Ae(n.detail, a) && n.target instanceof Element && t.contains(n.target) && F(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && Ae(n.detail, a) && n.target instanceof Element && t.contains(n.target) && F(n.target, "formieValidatorClearError", {
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
function I(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function Me(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Ne(e) {
	return e === "off" ? null : e;
}
function Pe(e, t) {
	t ? (e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true")) : (e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic"));
}
//#endregion
//#region src/js/core/field-error-aria.ts
function Fe(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Ie(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	r.length ? e.setAttribute("aria-describedby", r.join(" ")) : e.removeAttribute("aria-describedby");
}
function Le(e, t) {
	e.setAttribute("aria-errormessage", t), Fe(e, t);
}
function Re(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), Ie(e);
}
//#endregion
//#region src/js/core/validation-focus.ts
function ze(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Be(e) {
	if (e instanceof HTMLTextAreaElement) {
		let t = e.richText;
		if (t) return t.content;
	}
	return e;
}
function Ve(e) {
	for (let t of e) {
		let e = Be(t);
		if (!(e instanceof HTMLInputElement && e.type === "hidden") && e.getClientRects().length > 0) return e;
	}
	return null;
}
function He(e) {
	return Ve(Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).filter((e) => !N(e))) || Ve(Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")));
}
function Ue(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function We(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = Array.from(n.querySelectorAll("[data-formie-field-error]")), i = r.some((e) => e.closest("[data-formie-field-handle]") === n), a = n.getAttribute("data-formie-field-has-error") === "true" && r.length === 0;
		if (!i && !a) return;
		n.setAttribute("data-formie-field-has-error", "true"), M(n, e, "fieldLayoutError");
		let o = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let r = t;
			if (N(r)) return;
			r.setAttribute("aria-invalid", "true"), M(r, e, "fieldControlError"), r.setAttribute("data-formie-input-has-error", "true"), o && Le(r, o);
			let i = n.querySelector("[data-formie-instructions]");
			i?.id && Fe(r, i.id);
		});
	});
}
function Ge(e) {
	return !!ze(e) || !!Ue(e);
}
function Ke(e) {
	let t = document.activeElement;
	(!t || t === document.body || t.getClientRects().length === 0) && L(e);
}
function L(e) {
	let t = ze(e);
	if (t) {
		let e = He(t);
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
	let n = Ue(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/transport/forms-api.ts
var R = A("general", "transport");
function qe(e) {
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
function Je(e, t) {
	let n = e.success === !0, r = e.completion && typeof e.completion == "object" ? e.completion : null, i = e.payment && typeof e.payment == "object" ? e.payment : null, a = [
		"requiresAction",
		"pending",
		"unknown"
	].includes(i?.status || ""), o = e.errors, s = Object.fromEntries(Object.entries(o && typeof o == "object" ? o : {}).map(([e, t]) => [e, Array.isArray(t) ? t.filter((e) => typeof e == "string") : []])), c = s.form || [], l = {};
	Object.entries(s).forEach(([e, t]) => {
		e !== "form" && (l[e] = t);
	});
	let u = !n && c.length === 0 && Object.keys(l).length > 0 ? [t || "Submission failed."] : c, d = !n && a && u.length === 0 && Object.keys(l).length === 0;
	return {
		ok: n,
		outcome: typeof e.outcome == "string" ? e.outcome : void 0,
		version: typeof e.version == "number" ? e.version : null,
		submissionUid: typeof e.submissionUid == "string" ? e.submissionUid : null,
		errors: e.errors,
		session: e.session,
		completion: r,
		action: e.submitAction === "back" || e.submitAction === "save" || e.submitAction === "submit" ? e.submitAction : void 0,
		message: r?.message || e.successMessage || e.submitActionMessage || (n ? "Submission completed." : d ? "" : u[0] || "Submission failed."),
		code: n ? void 0 : String(e.code || "SUBMIT_ERROR"),
		keepSubmitLoading: a,
		payment: i,
		fieldErrors: Object.keys(l).length ? l : void 0,
		formErrors: u.length ? u : void 0,
		nextPage: e.nextPageId ? { id: String(e.nextPageId) } : null,
		redirect: i?.status === "requiresAction" && i.action?.type === "redirect" && i.action.url ? {
			url: i.action.url,
			target: "same-tab"
		} : r?.url ? {
			url: r.url,
			target: r.target
		} : e.redirectUrl ? {
			url: String(e.redirectUrl),
			target: e.redirectTarget === "new-tab" ? "new-tab" : "same-tab"
		} : null,
		submitData: Array.isArray(e.submitData) ? e.submitData : void 0,
		clientEvents: Array.isArray(e.clientEvents) ? e.clientEvents : void 0,
		meta: e
	};
}
async function Ye(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	R.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await P(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	return R.log("requestRender complete.", { hasHtml: !!a.html }), a;
}
async function Xe(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: qe(n)
		}
	});
	R.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await P(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(a.errors) && a.errors.length > 0) throw Error(a.errors.map((e) => e.message || "Unknown GraphQL error").join("; "));
	if (!a.data?.formieHtmlForm) throw Error(`Form not found for handle "${t}".`);
	let o = a.data.formieHtmlForm;
	return R.log("requestGraphqlRender complete.", { hasHtml: !!o.html }), o;
}
async function Ze(e, t, n, r = {}, i, a) {
	let o = new URL(e, window.location.origin);
	o.searchParams.set("handle", t), a !== void 0 && a !== "" && o.searchParams.set("siteId", String(a)), n && o.searchParams.set("renderId", n), i && o.searchParams.set("requestToken", i), R.log("requestRefreshTokens start.", {
		endpoint: o.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let s = await P(o.toString(), r);
	return R.log("requestRefreshTokens complete.", { hasRefreshTokens: !!s.refreshTokens }), s.refreshTokens || s;
}
async function Qe(e, t, n) {
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
		}), ie(i, t);
		for (let [e, n] of new FormData(t)) e.startsWith("fields[") && i.append(e, n);
	}
	R.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await (await ae(r.toString(), {
		method: "POST",
		body: i,
		profile: t?.dataset.formieRequestProfile
	})).json();
	if (t && a.session) {
		let e = a.session, n = t.querySelector("input[name=\"expectedVersion\"]"), r = t.querySelector("input[name=\"requestToken\"]");
		n && (n.value = String(e.version)), r && e.tokens?.request && (r.value = e.tokens.request);
	}
	return R.log("requestSetPage complete.", a), a;
}
function $e(e, t) {
	let r = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && i.append(e, n);
	}), ie(i, t), R.log("clearSubmissionOnUnload start.", { requestUrl: r.toString() });
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
async function et(e, t) {
	let r = (e.getAttribute("method") || "POST").toUpperCase(), i = e.getAttribute("action") || window.location.href, a = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	R.log("submitForm start.", {
		method: r,
		action: i,
		submitAction: t.get("submitAction")
	});
	let o = await n(i, {
		method: r,
		body: t,
		headers: { Accept: "application/json" }
	}, { profile: e.dataset.formieRequestProfile }), s = o.headers.get("content-type") || "";
	if (!s.includes("application/json")) return o.ok ? (R.log("submitForm non-JSON success response.", {
		status: o.status,
		contentType: s
	}), {
		ok: !0,
		message: "Submission completed."
	}) : (R.warn("submitForm non-JSON HTTP error.", {
		status: o.status,
		contentType: s
	}), {
		ok: !1,
		code: "HTTP_ERROR",
		message: `Request failed (${o.status}).`,
		formErrors: [`Request failed (${o.status}).`]
	});
	let c = Je(await o.json(), a);
	return R.log("submitForm JSON response normalized.", {
		ok: c.ok,
		code: c.code,
		hasRedirect: !!c.redirect?.url,
		hasSubmitData: Array.isArray(c.submitData) && c.submitData.length > 0
	}), c;
}
//#endregion
//#region src/js/submit/pipeline.ts
var tt = [
	"prepare",
	"validate",
	"challenge",
	"payment",
	"send",
	"result"
], nt = [
	"prepare",
	"validate",
	"challenge",
	"payment"
], z = A("general", "pipeline");
function rt(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function it(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function at(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function ot(e, t) {
	if (t instanceof HTMLInputElement) {
		if (t.type === "file") {
			Array.from(t.files || []).forEach((n) => {
				e.append(t.name, n);
			});
			return;
		}
		e.append(t.name, t.value);
	} else t instanceof HTMLSelectElement && t.multiple ? Array.from(t.selectedOptions).forEach((n) => {
		e.append(t.name, n.value);
	}) : e.append(t.name, t.value);
}
function st(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = it(t) ? t : null;
		n && !n.closest("[data-formie-page]") && at(n) && ot(e, n);
	});
}
function ct(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = it(t) ? t : null;
		r && r.name && !r.disabled && (r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), at(r) && ot(e, r)));
	}), n;
}
function lt(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function ut(e, t) {
	let n = y(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return st(i, e), lt(i, ct(i, r)), i.set("submitAction", t), i;
}
function dt(e, t) {
	if (t !== "submit") return !1;
	let n = y(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function ft(e, t, n, r = {}) {
	z.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = dt(e, t), c = ee(e, {
		form: e,
		action: t,
		formData: ut(e, t),
		abort: (e) => {
			i = !0, a = e, z.warn("Pipeline aborted.", { reason: e });
		},
		isAborted: () => i,
		abortReason: () => a
	}), l = (t, r) => n.emitSafe(t, ee(e, r)), u = {
		prepare: async (e) => {
			let t = e.form.querySelector("input[name=\"submitAction\"]");
			return t && (t.value = e.action), e.formData.set("submitAction", e.action), null;
		},
		validate: async (e) => {
			if (e.action !== "submit" || r.validateOnSubmit === !1) return null;
			if (r.validator) {
				let { scope: t, final: n } = E(e.form), i = r.validator.submit(n ? e.form : t, { final: n });
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
			h(e.form), e.formData = ut(e.form, e.action);
			let t = await et(e.form, e.formData);
			return o = t, t;
		},
		result: async (e) => (o && o.ok && o.redirect?.url && (o.redirect.target === "new-tab" ? window.open(o.redirect.url, "_blank", "noopener,noreferrer") : (e.form.setAttribute("data-formie-internal-navigation", "redirect"), window.location.href = o.redirect.url)), null)
	};
	{
		let e = await n.emitSafe("formie:submit:before", c);
		e.failed.length > 0 && z.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && z.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let d = r.preflightOnly ? nt : tt;
	for (let e of d) {
		if (z.log("Stage start.", {
			stage: e,
			action: t
		}), i) return z.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), rt(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && z.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = rt(e, a);
			{
				let n = await l("formie:submit:after", t);
				n.failed.length > 0 && z.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			if (s) {
				let n = await l("formie:submit:final:after", t);
				n.failed.length > 0 && z.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			return z.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await u[e](c);
		z.log("Stage runner complete.", {
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
			t.failed.length > 0 && z.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = rt(e, a);
			{
				let n = await l("formie:submit:after", t);
				n.failed.length > 0 && z.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			if (s) {
				let n = await l("formie:submit:final:after", t);
				n.failed.length > 0 && z.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			return z.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await l("formie:submit:after", r);
				t.failed.length > 0 && z.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await l("formie:submit:final:after", r);
				t.failed.length > 0 && z.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return z.warn("Pipeline short-circuited by failed stage.", {
				stage: e,
				code: r.code,
				message: r.message
			}), r;
		}
	}
	let f = o || {
		ok: !0,
		stage: r.preflightOnly ? "payment" : "result",
		code: r.preflightOnly ? "PREFLIGHT_COMPLETE" : void 0,
		message: r.preflightOnly ? "Submission preflight completed." : "Submission completed."
	};
	{
		let e = await l("formie:submit:after", f);
		e.failed.length > 0 && z.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await l("formie:submit:final:after", f);
		e.failed.length > 0 && z.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return z.log("Pipeline completed.", {
		ok: f.ok,
		stage: f.stage,
		code: f.code
	}), f;
}
//#endregion
//#region src/js/core/field-error-container.ts
function pt(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function mt(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = pt(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/theme/message-attributes.ts
function ht(e, t, n, r = !1) {
	let i = t.getAttribute("data-formie-message-attributes");
	if (!i) return;
	let a;
	try {
		a = JSON.parse(i);
	} catch {
		return;
	}
	if (!a || typeof a != "object" || Array.isArray(a)) return;
	let o = a[n];
	o && typeof o == "object" && !Array.isArray(o) && Object.entries(o).forEach(([t, n]) => {
		t === "class" || !/^[^\s"'<>/=]+$/.test(t) || typeof n != "string" && n !== !0 || (r ? e.removeAttribute(t) : e.setAttribute(t, n === !0 ? "" : n));
	});
}
//#endregion
//#region src/js/core/submit-result-ui.ts
var B = /* @__PURE__ */ new WeakMap();
function gt(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function _t(e) {
	return (e.dataset.formieErrorMessagePosition ?? "top-form").trim();
}
function vt(e) {
	return (e.dataset.formieSuccessMessagePosition || e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function yt(e) {
	let t = (e.dataset.formieSuccessMessageTimeout || e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function bt(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function xt(e) {
	let t = B.get(e);
	typeof t == "number" && (window.clearTimeout(t), B.delete(e));
}
function St(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function Ct(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function wt(e, t) {
	return t === "bottom-form" ? Ct(e) : St(e);
}
function Tt(e, t) {
	return t === "top-form" ? St(e) : t === "bottom-form" && !bt(e) ? Ct(e) : e;
}
function Et(e) {
	let t = _t(e), n = wt(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), M(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function Dt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), ht(n, e, "messageError"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), M(n, e, "message", "messageError"), n.setAttribute("role", "alert"), Pe(n, Ne(I(e))), n;
}
function Ot(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = Tt(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), M(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function kt(e) {
	return mt(e, (t) => {
		M(t, e, "fieldErrors");
	});
}
function At(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		j(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), j(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), Re(n, i);
		});
	}), S(e);
}
function jt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), j(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function Mt(e) {
	xt(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), ht(n, e, "messageSuccess", !0), j(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), gt(e) === "message" && bt(e) || x(e, !1);
}
function Nt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Pt(e, t) {
	let n = Ne(I(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = `fields[${t.split(".").join("][")}]`, a = e.querySelector(`[name="${CSS.escape(i)}"], [name="${CSS.escape(i + "[]")}"]`)?.closest("[data-formie-field-handle]") || e.querySelector(`[data-formie-field-handle="${CSS.escape(t)}"]`);
		if (!a) return;
		let o = kt(a), s = o.id && o.id.trim() ? o.id : `${t}-errors`;
		o.id = s, Pe(o, n), M(a, e, "fieldLayoutError"), a.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${s}-${n + 1}`, M(r, e, "fieldError"), r.textContent = t, o.appendChild(r);
		});
		let c = o.querySelector("[data-formie-field-error]")?.id;
		a.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), M(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), c && Le(n, c);
			let r = a.querySelector("[data-formie-instructions]");
			r?.id && Fe(n, r.id);
		});
	}), S(e);
}
function Ft(e, t) {
	if (!_t(e)) return;
	let n = Et(e), r = Dt(e, n);
	M(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), M(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function It(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.payment?.status;
	return t === "requiresAction" || t === "pending" || t === "unknown";
}
function Lt(e, t) {
	let n = Et(e), r = Dt(e, n);
	M(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), M(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function Rt(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || gt(e) === "message" && vt(e) !== "";
}
function zt(e, t, n = {}) {
	let r = vt(e);
	if (!r) return;
	let i = Ot(e, r);
	ht(i, e, "messageSuccess"), M(i, e, "message", "messageSuccess"), i.setAttribute("data-formie-message", "true"), i.setAttribute("data-formie-message-success", "true"), i.setAttribute("role", "status"), i.setAttribute("aria-live", "polite"), i.setAttribute("aria-atomic", "true");
	let a = document.createElement("div");
	a.setAttribute("data-formie-success", "true"), M(a, e, "success"), a.innerHTML = t, i.appendChild(a), n.hideForm !== !1 && bt(e) && x(e, !0);
	let o = e.dataset.formieScrollToTop?.trim().toLowerCase();
	o !== void 0 && [
		"",
		"true",
		"1"
	].includes(o) && i.scrollIntoView({
		behavior: "smooth",
		block: "start"
	});
	let s = yt(e);
	if (s !== null) {
		let t = window.setTimeout(() => {
			B.delete(e), Mt(e);
		}, s);
		B.set(e, t);
	}
}
function V(e, t) {
	if (At(e), jt(e), Mt(e), Nt(e), t.ok) {
		let n = t.meta?.summaryTokens;
		n && typeof n == "object" && e.querySelectorAll("[data-formie-field-uid]").forEach((e) => {
			let t = n[e.dataset.formieFieldUid || ""];
			typeof t == "string" && t && e.querySelector("[data-formie-summary-container]")?.setAttribute("data-formie-summary-token", t);
		}), Rt(e, t) && zt(e, t.message || "", { hideForm: t.action !== "save" });
	} else if (!t.ok) {
		if (It(t)) {
			let n = String(t.payment?.message || "").trim();
			n && Lt(e, n);
			return;
		}
		t.fieldErrors && Pt(e, t.fieldErrors), t.formErrors?.length ? Ft(e, t.formErrors) : !t.fieldErrors && t.message && Ft(e, [t.message]), L(e);
	}
}
//#endregion
//#region src/js/core/submit-flow.ts
var Bt = A("general", "submit-flow");
function Vt(e) {
	return !(!e.ok && e.stage === "validate");
}
function Ht(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function Ut(e) {
	At(e), jt(e), Mt(e), Nt(e);
}
async function Wt(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	Ut(r), w(r, c || null);
	let p = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), p = await ft(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), V(r, p), d(p), v(r, p, s), Vt(p) && await u(p);
	} catch (e) {
		p = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, V(r, p), d(p), Bt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		Ht(p) || f(r);
	}
	return p;
}
//#endregion
//#region src/js/events/event-bus.ts
var Gt = class {
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
			e.status === "fulfilled" ? r.succeeded += 1 : r.failed.push({
				index: t,
				error: e.reason
			});
		}), r;
	}
	clear() {
		this.listeners.clear();
	}
}, Kt = class {
	modules = /* @__PURE__ */ new Map();
	register(e, t = {}) {
		if (!/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(e.moduleId) || e.version !== 2 || !Array.isArray(e.surfaces) || e.surfaces.length === 0 || e.surfaces.some((e) => ![
			"server-rendered",
			"client-rendered",
			"cp-edit"
		].includes(e)) || ![
			"field",
			"captcha",
			"payment",
			"address",
			"core"
		].includes(e.kind) || typeof e.match != "function" || typeof e.setup != "function") throw Error("Unsupported browser module definition. Register a namespaced moduleId compatible with version 2.");
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
}, qt = new Kt(), Jt = {
	"address-finder": () => import("./chunks/address-finder-ChSNCcm_.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-Bhd-xa3w.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-DkxXoKs4.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BUOGAm6N.js").then((e) => e.placeKitModule)
}, Yt = {
	"captcha-eu": () => import("./chunks/captcha-eu-D9AClU5R.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-C_cF1WD2.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-BenESH-n.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-O_4uECEr.js").then((e) => e.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-BFUGhUaC.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-CeRtAdE6.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-BLA8baiY.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-DYQD60Os.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-P3EoSQud.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-DQF_MuTJ.js").then((e) => e.turnstileModule)
}, Xt = {
	calculations: () => import("./chunks/calculations-BfXZpq57.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-CSXo6svm.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-DAvR038W.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-BSX-oRTX.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-rNm5VFbb.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-CLqFCssQ.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-uV8HdZBv.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-CGPnDsea.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-DRXQhHGA.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-DGQ4tIL-.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-Bc2TpcgF.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-Bdym1qjs.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-DncMVXX1.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-Csvfopqd.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-C7ZUaaVE.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-Lk2Gl3jn.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-BKkG6TDM.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-BbCtOQuJ.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-wthGJrHx.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-Cs6ZyCzO.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-BR3gY0Wx.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-CHgNcPVS.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-cRBJYL_P.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-C9hbZzkL.js").then((e) => e.textLimitModule)
}, Zt = {
	bpoint: () => import("./chunks/bpoint-x3LNc8wY.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-MLx81OV2.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-B_1RZ0dV.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-4Auk3bRw.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-DzBZaXkT.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-DuERPoRM.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-CMWdZeJM.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-DIf4lLNy.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-Buhf_sMb.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-C_OCgzMo.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-B7NlLPMi.js").then((e) => e.stripeModule)
}, Qt = {
	...Xt,
	...Jt,
	...Yt,
	...Zt
}, $t = /* @__PURE__ */ new Map();
function en(e) {
	if (e instanceof Error) return {
		name: e.name || "Error",
		message: e.message || "No error message was provided.",
		stack: e.stack
	};
	if (typeof e == "string") return {
		name: "Error",
		message: e
	};
	try {
		return {
			name: "Error",
			message: JSON.stringify(e) || String(e)
		};
	} catch {
		return {
			name: "Error",
			message: String(e)
		};
	}
}
function tn(e, t, n) {
	let r = [...t].filter((e) => e.required), i = [
		"Formie browser module diagnostics",
		`Surface: ${e}`,
		`Browser: ${typeof navigator > "u" ? "Unavailable" : navigator.userAgent}`
	];
	return e === "cp-edit" && typeof location < "u" && i.push(`Page: ${location.origin}${location.pathname}`), r.forEach((t, r) => {
		let a = n.get(t.key);
		i.push("", `Failure ${r + 1}`, `Module: ${t.moduleId}`, `Key: ${t.key}`, `Code: ${t.code}`), e === "cp-edit" && a && (i.push(`Error: ${a.name}: ${a.message}`), a.stack && i.push("Stack:", a.stack));
	}), i.join("\n");
}
async function nn(e) {
	if (navigator.clipboard?.writeText) {
		await navigator.clipboard.writeText(e);
		return;
	}
	let t = document.createElement("textarea");
	t.value = e, t.setAttribute("readonly", ""), t.style.position = "fixed", t.style.opacity = "0", document.body.appendChild(t), t.select();
	try {
		if (!document.execCommand("copy")) throw Error("The browser did not allow clipboard access.");
	} finally {
		t.remove();
	}
}
async function rn(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(Qt, e.slice(7)) ? Qt[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	$t.has(e) || $t.set(e, r().catch((t) => {
		throw $t.delete(e), t;
	}));
	let i = await $t.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function an(e, t, n) {
	return [...new Set(e.targets.flatMap((e) => {
		if (e.type === "form") return [n || t];
		let r = e.type === "selector" ? e.selector : e.type === "field" ? `[data-formie-field-uid="${CSS.escape(e.uid)}"]` : e.type === "page" ? `[data-formie-page-id="${CSS.escape(e.id)}"]` : `[data-formie-action="${CSS.escape(e.action)}"]`;
		return [...t.matches(r) ? [t] : [], ...t.querySelectorAll(r)];
	}))].filter((e) => !e.closest("[hidden], [data-formie-hidden=\"true\"], [data-formie-conditionally-hidden], [data-formie-page-hidden]"));
}
async function on(e, n) {
	t(e);
	let r = e.surface;
	if (n.matchContext.surface !== r || n.setupContext.surface !== r) throw Error(`Browser module manifest surface ${r} cannot mount as ${n.matchContext.surface}.`);
	let { root: i, form: a } = n.setupContext, o = /* @__PURE__ */ new Map(), s = /* @__PURE__ */ new Map(), c = /* @__PURE__ */ new Map(), l = [], u = !1, d = Promise.resolve(), f = !1, p = async (e, t) => {
		let i = {
			key: e.key,
			moduleId: e.moduleId,
			required: e.required,
			surface: r,
			code: "MODULE_UNAVAILABLE",
			message: "A form feature could not start. Reload the page or contact the site administrator."
		};
		s.set(e.key, i), c.set(e.key, en(t)), console.error("[formie] Browser module failure", i, t), await n.setupContext.emit("formie:browser:module:error", i);
	}, m = async (e) => {
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
	}, h = () => [...s.values()].some((e) => e.required), g = () => {
		if (!a) return;
		let e = a.querySelector("[data-formie-module-error]");
		if (!e) {
			let t = document.createElement("p");
			t.textContent = r === "cp-edit" ? "Reload the page. If the problem continues, copy the technical details below." : "Reload the page or contact the site administrator.";
			let n = document.createElement("pre");
			if (n.dataset.formieModuleErrorDetails = "true", n.tabIndex = 0, r === "cp-edit") e = document.createElement("pk-alert"), e.className = "formie-module-error", e.setAttribute("variant", "error"), e.setAttribute("announce", "assertive"), e.setAttribute("heading", "A required form feature could not start."), e.setAttribute("details-label", "Details"), e.setAttribute("copy-label", "Copy details"), e.setAttribute("copied-label", "Details copied."), e.setAttribute("copyable", ""), n.slot = "details", e.append(t, n);
			else {
				e = document.createElement("div"), e.className = "formie-module-error formie-module-error--fallback";
				let r = document.createElement("div");
				r.className = "formie-module-error__notice", r.setAttribute("role", "alert");
				let i = document.createElement("span");
				i.className = "formie-module-error__indicator", i.setAttribute("aria-hidden", "true"), i.textContent = "!";
				let a = document.createElement("div");
				a.className = "formie-module-error__content";
				let o = document.createElement("strong");
				o.className = "formie-module-error__title", o.textContent = "A required form feature could not start.", a.append(o, t), r.append(i, a);
				let s = document.createElement("details");
				s.className = "formie-module-error__details";
				let c = document.createElement("summary");
				c.textContent = "Details";
				let l = document.createElement("div");
				l.className = "formie-module-error__actions";
				let u = document.createElement("button");
				u.className = "formie-module-error__copy", u.type = "button", u.textContent = "Copy details";
				let d = document.createElement("span");
				d.className = "formie-module-error__copy-status", d.setAttribute("role", "status"), d.setAttribute("aria-live", "polite"), u.addEventListener("click", async () => {
					try {
						await nn(n.textContent || ""), d.textContent = "Details copied.";
					} catch {
						d.textContent = "Copy failed. Select the details above and copy them manually.";
						let e = document.createRange();
						e.selectNodeContents(n), window.getSelection()?.removeAllRanges(), window.getSelection()?.addRange(e);
					}
				}), l.append(u, d), s.append(c, n, l), e.append(r, s);
			}
			e.dataset.formieModuleError = "true", i !== a && a.contains(i) ? i.before(e) : a.prepend(e);
		}
		let t = e.querySelector("[data-formie-module-error-details]"), n = tn(r, s.values(), c);
		t && t.textContent !== n && (t.textContent = n);
	}, _ = (e) => {
		h() && (e.preventDefault(), e.stopImmediatePropagation(), g());
	}, v = async () => {
		let t = new Set(e.entries.map((e) => e.key));
		for (let e of s.keys()) t.has(e) || (s.delete(e), c.delete(e));
		let d = [];
		for (let [e, n] of o) t.has(e) || (d.push(...Array.from(n.values(), ({ instance: e }) => e)), o.delete(e), s.delete(e), c.delete(e));
		for (let e of d.reverse()) await m(e), l.splice(l.indexOf(e), 1);
		for (let t of e.entries) {
			if (u) continue;
			let e = s.get(t.key);
			e && s.set(t.key, {
				...e,
				required: t.required
			});
			let d;
			try {
				d = an(t, i, a);
			} catch (e) {
				s.has(t.key) || await p(t, e);
				continue;
			}
			let f = o.get(t.key) ?? /* @__PURE__ */ new Map();
			o.set(t.key, f);
			for (let [e, t] of Array.from(f.entries()).reverse()) d.includes(e) || (await m(t.instance), f.delete(e), l.splice(l.indexOf(t.instance), 1));
			let h;
			try {
				h = await rn(t.moduleId, n.registry);
			} catch (e) {
				s.has(t.key) || await p(t, e);
				continue;
			}
			let g = !1, _ = !1;
			for (let e of d) {
				if (u) return;
				let a = JSON.stringify([
					t.moduleId,
					t.config,
					t.required
				]), o = f.get(e);
				if (o?.config === a) continue;
				let c = {
					...n.setupContext,
					target: e,
					entryKey: t.key,
					surface: r,
					scope: t.targets[0]?.type ?? "form",
					options: t.config
				};
				try {
					if (o) {
						if (o.instance.update && o.moduleId === t.moduleId && o.required === t.required) {
							await o.instance.update(c), o.config = a;
							continue;
						}
						await m(o.instance), f.delete(e), l.splice(l.indexOf(o.instance), 1);
					}
					if (h.surfaces && !h.surfaces.includes(r)) throw Error(`Module ${t.moduleId} does not support ${r}.`);
					if (h.kind !== t.kind) throw Error(`Module ${t.moduleId} is registered as ${h.kind}, not ${t.kind}.`);
					if (!h.match({
						...n.matchContext,
						target: e,
						scope: c.scope,
						manifestItem: t
					})) {
						if (h.kind === "field" || h.kind === "core") {
							_ = !0;
							continue;
						}
						throw Error(`Module ${t.moduleId} does not support the rendered target.`);
					}
					let s = await h.setup(c);
					if (!s) throw Error(`Module ${t.moduleId} did not initialize.`);
					if (u || !i.contains(e) && e !== i) {
						await m(s);
						continue;
					}
					s.key = t.key, s.moduleId = t.moduleId, s.kind = t.kind, s.target = e;
					let d = s.assertReady;
					s.assertReady = () => {
						try {
							d?.();
						} catch (e) {
							if (p(t, e), t.required) throw Error("A required form feature could not start.");
						}
					};
					let g = s.beforeSubmit, v = s.afterSubmit;
					s.beforeSubmit = async (e) => {
						try {
							await g?.(e);
						} catch (n) {
							await p(t, n), t.required && e.abort("A required form feature could not complete. Reload the page or contact the site administrator.");
						}
					}, s.afterSubmit = async (e, n) => {
						try {
							await v?.(e, n);
						} catch (e) {
							await p(t, e);
						}
					}, f.set(e, {
						instance: s,
						config: a,
						moduleId: t.moduleId,
						required: t.required
					}), l.push(s), _ = !0, await n.setupContext.emit("formie:browser:module:mount", {
						key: t.key,
						moduleId: t.moduleId,
						target: e
					});
				} catch (e) {
					g = !0, s.has(t.key) || await p(t, e);
				}
			}
			!g && (_ || d.length === 0) && (s.delete(t.key), c.delete(t.key));
		}
		h() ? g() : a?.querySelector("[data-formie-module-error]")?.remove();
	}, y = new MutationObserver(() => {
		f || u || (f = !0, d = d.then(async () => {
			f = !1, u || await v();
		}), d.catch((e) => console.error("[formie] Module reconciliation failed", e)));
	});
	return a?.addEventListener("submit", _, !0), await v(), y.observe(i, {
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
	}), {
		get instances() {
			return l;
		},
		get failures() {
			return [...s.values()];
		},
		assertReady: () => {
			if (h()) throw Error("A required form feature could not start. Reload the page or contact the site administrator.");
		},
		destroy: async () => {
			u = !0, y.disconnect(), a?.removeEventListener("submit", _, !0), await d;
			for (let e of Array.from(o.values()).reverse()) for (let { instance: t } of Array.from(e.values()).reverse()) await m(t);
			o.clear(), l.splice(0), s.clear(), c.clear(), a?.querySelector("[data-formie-module-error]")?.remove();
		},
		updateManifest: async (n) => {
			if (t(n), n.surface !== r) throw Error("A mounted browser module runtime cannot change surfaces.");
			e = n, d = d.then(v), await d;
		}
	};
}
//#endregion
//#region src/js/utils/unload-warning.ts
var sn = /* @__PURE__ */ new Set([
	"action",
	"redirect",
	"requestToken",
	"uploadCreateToken",
	"renderId",
	"formStartedAt",
	"formSubmittedAt",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"progressId"
]);
function cn(e) {
	let t = /* @__PURE__ */ new Set(), n = !0;
	return d(e, "formie:unload-warning:collect-exclusions", {
		form: e,
		exclude: (...e) => {
			n && e.forEach((e) => {
				typeof e == "string" && e !== "" && t.add(e);
			});
		}
	}), n = !1, t;
}
function ln(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => ln(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${ln(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function un(e) {
	return ln(e, /* @__PURE__ */ new WeakSet());
}
function dn(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !re(n, t) && !sn.has(n);
}
function fn(e, t, n) {
	return un(e.filter(([e]) => !n.has(e) && dn(String(e || ""), t)));
}
function pn(e, t = {}) {
	let n = [], r = !1, i = !1, a = !1, o = null, s = null, c = null, l = () => {
		o !== null && (window.cancelAnimationFrame(o), o = null), s !== null && (window.clearTimeout(s), s = null), c !== null && (window.clearTimeout(c), c = null);
	}, u = () => {
		if (!r) return !1;
		let t = cn(e);
		return a = fn(Array.from(new FormData(e).entries()), e, t) !== fn(n, e, t), a;
	}, d = () => {
		i || (l(), n = Array.from(new FormData(e).entries()), r = !0, a = !1);
	}, f = () => {
		i || (l(), r = !1, o = window.requestAnimationFrame(() => {
			o = null, c = window.setTimeout(() => {
				c = null, d();
			}, 0);
		}));
	}, p = () => {
		s !== null && window.clearTimeout(s), s = window.setTimeout(() => {
			s = null, u();
		}, 120);
	}, m = (e) => {
		(!t.shouldWarn || t.shouldWarn()) && u() && (e.preventDefault(), e.returnValue = "");
	};
	return e.addEventListener("input", p), e.addEventListener("change", p), window.addEventListener("beforeunload", m), d(), {
		captureBaseline: d,
		scheduleBaselineCapture: f,
		refreshDirtyState: u,
		destroy: () => {
			i = !0, r = !1, n = [], l(), e.removeEventListener("input", p), e.removeEventListener("change", p), window.removeEventListener("beforeunload", m);
		}
	};
}
//#endregion
//#region src/js/validation/rules/email.ts
var mn = {
	rule: ({ input: e, getRule: t }) => {
		let n = t("email");
		return !n || r(e.value, {
			...typeof n == "object" ? n : {},
			type: "email"
		}) === null;
	},
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function hn(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function gn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !N(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var _n = {
	required: {
		rule: ({ input: e, getRule: t }) => {
			if (!t("required") || e.type === "hidden" || e.closest("[data-formie-field-handle]")?.getAttribute("data-formie-field-type") === "table") return !0;
			if (e instanceof HTMLInputElement && e.type === "file") {
				if (e.files?.length) return !0;
				let t = e.closest("[data-formie-field-handle]"), n = e.name.replace(/\[\]$/, "");
				return Array.from(t?.querySelectorAll("input[type=\"hidden\"][data-formie-file-upload-asset-id]") || []).some((e) => !e.disabled && e.name.startsWith(`${n}[`) && Number(e.getAttribute("data-formie-file-upload-asset-id")) > 0);
			}
			if (e.type === "checkbox" || e.type === "radio") {
				let t = e.form?.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`) || [];
				return t.length ? Array.from(t).some((e) => e instanceof HTMLInputElement && e.checked) : e instanceof HTMLInputElement ? e.checked : !0;
			}
			return r(e.value, { type: "required" }) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? n("{label} cannot be blank.", { label: t })
	},
	email: mn,
	url: {
		rule: ({ input: e, getRule: t }) => {
			let n = t("url");
			return !n || r(e.value, {
				...typeof n == "object" ? n : {},
				type: "url"
			}) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? n("{label} is not a valid URL.", { label: t })
	},
	number: {
		rule: ({ input: e, getRule: t }) => {
			let n = t("number");
			return !n || r(e.value, {
				...typeof n == "object" ? n : {},
				type: "number"
			}) === null;
		},
		message: ({ input: e, label: t, getRule: n, t: i }) => {
			let a = n("number");
			return r(e.value, {
				...typeof a == "object" ? a : {},
				type: "number"
			}, { label: t }) ?? i("{label} is not a valid number.", { label: t });
		}
	},
	match: {
		rule: (e) => {
			let t = gn(e);
			return !t || r(e.input.value, { type: "match" }, { comparison: t.value }) === null;
		},
		message: (e) => {
			let t = gn(e)?.closest("[data-formie-field-handle]"), n = hn(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, vn = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, H = A("general", "validator");
function U(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && (!!e.name || e.hasAttribute("data-formie-date-datepicker-input") || e.hasAttribute("data-formie-upload-manager-status"));
}
function W(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var yn = class {
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
			patterns: vn,
			...t
		}, Object.entries(_n).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		H.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, o("ready"), { validator: this });
	}
	inputs(e = null) {
		if (U(e)) return N(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => U(e) && !N(e));
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
			let r = !1, i = t.renderErrors !== !1 && this.shouldShowError(e);
			if (!this.isVisible(e, t)) return;
			let a = e.closest("[data-formie-field-handle]"), o = e instanceof HTMLInputElement && (e.type === "checkbox" || e.type === "radio") ? `${a?.getAttribute("data-formie-field-handle") || ""}:${e.name}` : null;
			if (o) {
				if (n.has(o)) return;
				n.add(o);
			}
			i && this.removeError(e);
			let s = this.getValidatorCallbackOptions(e);
			Object.entries(this.validators).forEach(([t, n]) => {
				if (!n.validate(s)) {
					let a = this.getErrorMessage(e, t, n, s);
					i && !r && this.showError(e, t, a), this.errors.push({
						input: e,
						field: s.field,
						validator: t,
						message: a,
						handle: s.field?.getAttribute("data-formie-field-handle") || null,
						result: !1
					}), r = !0;
				}
			}), !r && i && this.removeError(e);
		}), H.log("Validation pass complete.", {
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
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), Re(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, o("clear-error"), { validator: this }), S(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= mt(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let a = r.getAttribute("data-formie-field-handle") || "field";
		i.id = i.id || `${e.id || `${this.form.id}-${a}`}-errors`;
		let s = `${i.id}-1`;
		Pe(i, Me(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			N(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), Le(t, s));
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
			t: ce,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? ce("{label} is invalid.", { label: r.label });
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
			return H.warn("Invalid validation rules payload.", { formId: this.form.id || null }), t;
		}
		return Array.isArray(n) && n.forEach((e) => {
			if (!e || typeof e != "object" || Array.isArray(e)) return;
			let n = e, r = typeof n.type == "string" ? n.type.trim() : "";
			r && (t[r] = n);
		}), t;
	}
	destroy() {
		H.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, o("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		if (e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]")) return !1;
		if (e.closest("[data-formie-page-hidden]")) return !!t.includeHiddenPages;
		let n = e.closest("[data-formie-field-handle]"), r = n?.querySelector("[data-formie-rich-text]");
		if (r instanceof HTMLElement) return W(r);
		if (e instanceof HTMLSelectElement && e.hasAttribute("data-formie-combobox-input")) {
			let t = e.nextElementSibling;
			if (t instanceof HTMLElement && t.classList.contains("formie-combobox")) return W(t);
		}
		return e instanceof HTMLSelectElement && n instanceof HTMLElement ? W(e) || W(n) : W(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && U(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		e.target instanceof HTMLElement && U(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || (e.target instanceof HTMLSelectElement || e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio")) && (this.activated.add(e.target), this.validate(e.target)));
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && U(e.target) && !N(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
	}
	submit(e = null, { final: t = !1 } = {}) {
		return this.submitted = !0, H.log("Submit validation requested.", { final: t }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(e, { includeHiddenPages: t });
	}
	resetLiveState() {
		this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
	}
	addEventListeners() {
		this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, H.log("Event listeners attached."));
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, H.log("Event listeners removed.");
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
function bn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function xn(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !bn(e));
}
function Sn(e) {
	let { final: t } = E(e);
	return "submit";
}
function Cn(e) {
	return !xn(e, Sn(e));
}
function wn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || Cn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var G = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", Tn = 300, En = "/actions/formie/server/forms/render", Dn = "/api", On = "/actions/formie/server/forms/refresh-tokens", kn = "/actions/formie/server/submissions/submit", An = "/actions/formie/server/submissions/set-page", jn = "/actions/formie/server/submissions/clear-submission", Mn = "/actions/formie/file-upload/hydrate", K = A("general", "client"), Nn = /* @__PURE__ */ new Set();
function q(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function Pn(e) {
	return e.formieRefreshTokens == null ? e.formieStaticCache != null && q(e.formieStaticCache, !0) : q(e.formieRefreshTokens, !0);
}
function J(e) {
	let t = e instanceof HTMLElement ? e.dataset : {};
	return {
		mode: "server-rendered",
		transport: t.formieTransport || "rest",
		profile: t.formieRequestProfile,
		formHandle: t.formieHandle,
		endpoint: t.formieEndpoint,
		staticCache: Pn(t),
		autoVisible: q(t.formieAutoVisible, !0),
		compatibility: q(t.formieCompatibility, !1)
	};
}
function Fn(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function In(e) {
	return e || "rest";
}
function Y(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function Ln(e, t) {
	Nn.has(e) || (Nn.add(e), K.warn(t));
}
function X(e, t) {
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
function Z(e, t) {
	let n = (e || "").trim();
	if (!n) return t;
	if (n.includes(t)) return n;
	let r = t.replace(/^\/actions/, "");
	try {
		let e = new URL(n, window.location.origin);
		if (e.pathname.endsWith(r) || Array.from(e.searchParams.values()).some((e) => e.endsWith(r))) return n;
	} catch {}
	return X(t, n);
}
function Rn(e, t) {
	return Z(e.endpoint || t.dataset.formieEndpoint, En);
}
function zn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : X(Dn, n) : Dn;
}
function Bn(e, t) {
	let n = t.dataset.formieRefreshTokensEndpoint || Y(t)?.dataset.formieRefreshTokensEndpoint;
	return n ? X(n, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, On);
}
function Vn(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = t.getAttribute("action") || t.dataset.formieSubmitEndpoint;
	t.setAttribute("action", i ? X(i, r) : Z(r, kn)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href");
		e.setAttribute("href", t ? X(t, r) : Z(r, An));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(e.getAttribute("data-formie-file-upload-hydrate-endpoint") || Mn, r));
	});
}
function Hn(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Un(e) {
	return q(e.dataset.formieAutomaticSubmissionState, !0);
}
function Wn(e, t, n) {
	return n.dataset.formieClearSubmissionEndpoint ? X(n.dataset.formieClearSubmissionEndpoint, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, jn);
}
function Gn(e) {
	return Hn(e.dataset.formieUnloadWarning);
}
function Kn(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function qn(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function Jn(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function Yn(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function Xn(e) {
	return Yn(window.location.href, "resumeToken") || Yn(e.getAttribute("action"), "resumeToken");
}
function Zn(e) {
	return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function Qn(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function $n(e) {
	return Math.max(0, Qn(e.dataset.formieSubmitDelay, Tn));
}
function Q(e) {
	return Hn(e.dataset.formieValidationOnSubmit);
}
async function er(e) {
	let t = $n(e);
	await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function tr(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function nr(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = tr(n, "data-formie-modules"), i = tr(n, "data-formie-theme-classes") || tr(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function rr(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function ir(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function $(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function ar(e, t) {
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
		if (t.uploadCreateToken) {
			let n = e.querySelector("input[name=\"uploadCreateToken\"]");
			n || (n = document.createElement("input"), n.type = "hidden", n.name = "uploadCreateToken", e.append(n)), n.value = t.uploadCreateToken;
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
async function or(e, t) {
	let n = Fn(t.mode), r = In(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!Y(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? zn(t, e) : Rn(t, e), c = r === "graphql" ? await Xe(s, a, o, t) : await Ye(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function sr(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await Ze(Bn(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n.querySelector("input[name=\"requestToken\"]")?.value, n.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	ar(n, i), d(e, "formie:refresh-tokens:refreshed", i);
}
function cr(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = Wn(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		e ? t.setAttribute("data-formie-pending-action", e) : t.removeAttribute("data-formie-pending-action");
	};
	if (Gn(t)) {
		let n = pn(t, { shouldWarn: () => !Jn(t) }), r = (e) => {
			if (!(e instanceof CustomEvent)) return;
			let t = e.detail;
			t?.ok && (t.action === "save" || t.outcome === "revised") && n.scheduleBaselineCapture();
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
				Zn(n) && Kn(t, "set-page");
				return;
			}
			n.preventDefault();
			let r = n.currentTarget, i = r?.getAttribute("data-formie-page-id"), a = r?.getAttribute("href");
			if (i && a) {
				d(e, "formie:page:navigate", {
					pageId: i,
					href: a
				});
				try {
					let n = await Qe(a, t, i);
					if (n.pageId && C(t, String(n.pageId)), !n.success) {
						let { form: e = [], ...r } = n.errors ?? {};
						Ut(t), Pt(t, r), Ft(t, e), L(t);
						return;
					}
					d(e, "formie:page:navigate:after", {
						pageId: i,
						href: a,
						response: n
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), d(e, "formie:page:navigate:error", {
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
	}), !Un(t)) {
		let e = !1, n = () => {
			e || Jn(t) || Xn(t) || (e = !0, $e(s, t));
		};
		window.addEventListener("pagehide", n), window.addEventListener("beforeunload", n), a.push(() => {
			window.removeEventListener("pagehide", n), window.removeEventListener("beforeunload", n);
		});
	}
	let p = async (a) => {
		if (c) return;
		let s = o === "ajax";
		if (a.preventDefault(), t.getAttribute("data-formie-loading") === "true") {
			if (t.getAttribute("data-formie-internal-resubmit") !== "true") return;
			t.removeAttribute("data-formie-internal-resubmit");
		} else t.removeAttribute("data-formie-internal-resubmit");
		let l = a.submitter, p = l?.getAttribute("data-formie-action"), m = t.getAttribute("data-formie-pending-action"), g = t.querySelector("input[name=\"submitAction\"]"), _ = p || m || g?.value || "submit", v = null, y = !1;
		try {
			if (s) v = await Wt({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Q(t),
				action: _,
				submitter: l,
				waitForSubmitDelay: er,
				onRefreshTokensAfterSubmit: async () => {
					await sr(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					d(e, "formie:submit:result", t);
				}
			});
			else {
				if (Ut(t), w(t, l), await er(t), v = await ft(t, _, r, {
					validator: i,
					validateOnSubmit: Q(t),
					preflightOnly: !0
				}), v.ok) {
					b(t, _), c = !0, Kn(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, qn(t), f(t), i && Q(t)) {
							let { scope: e, final: n } = E(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && V(t, {
								ok: !1,
								stage: "validate",
								code: "VALIDATION_FAILED",
								message: i.config.errorMessage || "Validation failed.",
								fieldErrors: i.getFieldErrors(r),
								formErrors: [i.config.errorMessage || "Validation failed."]
							});
						}
					};
					if (h(t), typeof t.requestSubmit == "function") {
						t.addEventListener("invalid", n, !0);
						try {
							t.requestSubmit();
						} finally {
							t.removeEventListener("invalid", n, !0);
						}
					} else t.submit();
					if (e) return;
					y = !0;
					return;
				}
				V(t, v), d(e, "formie:submit:result", v), qn(t);
			}
		} catch (n) {
			c = !1, v = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, V(t, v), d(e, "formie:submit:result", v), qn(t);
		} finally {
			u(null), !s && !y && !Ht(v) && f(t);
		}
	};
	t.addEventListener("submit", p), a.push(() => {
		t.removeEventListener("submit", p);
	});
}
async function lr(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = Bn(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await Ze(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value, n?.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	o && n && (ar(n, o), d(e, "formie:refresh-tokens:after", o));
}
function ur() {
	let n = /* @__PURE__ */ new Map(), r = new Kt(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
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
			K.log("Unmount requested.", { target: $(e) });
			let t = i.get(e);
			t && (t(), i.delete(e));
			let r = n.get(e);
			r ? (d(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null, await r.modules.destroy(), r.bus.clear(), n.delete(e), d(e, "formie:unmount:after", { id: r.instance.id }), r.unbindEventContext(), K.log("Unmount complete.", {
				id: r.instance.id,
				target: $(e)
			})) : K.log("Unmount skipped (no mounted state).", { target: $(e) });
		})().finally(() => {
			a.delete(e);
		});
		a.set(e, r), await r;
	}, c = async (a, c) => {
		K.log("Mount requested.", {
			target: $(a),
			mode: c.mode,
			autoVisible: c.autoVisible
		});
		let l = i.get(a);
		l && (l(), i.delete(a));
		let u = n.get(a);
		if (u) return K.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: $(a)
		}), u.instance;
		let f = new Gt(), h = [], v = a?.id || `formie-${n.size + 1}`, y = J(a), b = {
			...y,
			...c,
			mode: Fn(c.mode ?? y.mode),
			transport: In(c.transport ?? y.transport)
		}, x = await or(a, b), C = Y(a);
		c.compatibility === void 0 && C?.hasAttribute("data-formie-compatibility") && (b.compatibility = J(C).compatibility);
		let w = we(b.compatibility);
		C && e(C, b), b.staticCache = c.staticCache ?? Pn(C ? C.dataset : a.dataset);
		let T;
		try {
			T = nr(a, C), x?.modules && t(x.modules), T?.modules && t(T.modules);
		} catch (e) {
			if (C) {
				C.addEventListener("submit", (e) => {
					e.preventDefault(), e.stopImmediatePropagation();
				}, !0);
				let e = document.createElement("div");
				e.setAttribute("role", "alert"), e.textContent = "This form requires a compatible Formie browser package. Update Formie and its browser packages together.", C.prepend(e);
			}
			throw e;
		}
		let E = x || T ? {
			...x || {},
			...T || {}
		} : null, ee = E?.theme, te = {}, D = E?.modules ?? {
			contractVersion: 2,
			surface: "server-rendered",
			entries: []
		};
		t(D), K.log("Resolved mount payload.", {
			target: $(a),
			hasRenderPayload: !!x,
			hasEmbeddedPayload: !!T,
			moduleCount: D.entries.length
		});
		let O = ne(a, ee, C), k = C ? new yn(C, {
			live: Hn(C.dataset.formieValidationOnFocus),
			errorAriaLive: I(C),
			errorMessage: C.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: O.fieldLayoutError || [],
			inputErrorClass: O.fieldControlError || [],
			messagesClass: O.fieldErrors || [],
			messageClass: O.fieldError || []
		}) : null;
		if (C && k) {
			let e = C;
			e.formieValidation = k, te.validation = k;
			let t = {
				validator: k,
				addValidator: k.addValidator.bind(k),
				removeValidator: k.removeValidator.bind(k)
			};
			d(C, "formie:validator:ready", t), d(a, "formie:validator:ready", t);
		}
		C && (m(C), (x || b.endpoint || a.dataset.formieEndpoint) && Vn(a, C, b), b.mode === "server-rendered" && Ge(C) && (We(C), L(C)), S(C)), Object.keys(O).length && d(a, "formie:theme:applied", { hasClasses: !0 }), C?.querySelector("craft-csrf-input") && await oe("craft-csrf-input", C);
		let A = await on(D, {
			registry: r,
			matchContext: {
				root: a,
				form: C,
				surface: D.surface
			},
			setupContext: {
				formId: v,
				root: a,
				form: C,
				target: a,
				scope: "form",
				surface: D.surface,
				state: te,
				on: (e, t) => f.on(e, t),
				emit: (e, t) => (d(a, e, t), f.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && K.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		K.log("Module setup complete.", {
			target: $(a),
			moduleInstances: A.instances.length
		});
		let j = {
			id: v,
			root: a,
			submit: async (e = "submit") => {
				if (K.log("Submit requested.", {
					id: v,
					target: $(a),
					action: e
				}), !C) return {
					ok: !1,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				let t = C.querySelector("input[name=\"submitAction\"]");
				if (t && (t.value = e), C.getAttribute("data-formie-loading") === "true") return {
					ok: !1,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				let n = C.querySelector(`[data-formie-action="${e}"]`), r = await Wt({
					id: v,
					target: a,
					form: C,
					bus: f,
					validator: k,
					validateOnSubmit: Q(C),
					action: e,
					submitter: n,
					waitForSubmitDelay: er,
					onRefreshTokensAfterSubmit: async () => {
						await sr(a, b, C);
					},
					dispatchSubmitResult: (e) => {
						d(a, "formie:submit:result", e);
					}
				});
				return K.log("Submit completed.", {
					id: v,
					action: e,
					ok: r.ok,
					code: r.code,
					message: r.message
				}), r;
			},
			destroy: async () => {
				await s(a);
			},
			on: (e, t) => f.on(e, t)
		}, M = g(a, C, j);
		if (C && (je({
			target: a,
			form: C,
			validatorDetail: k ? {
				validator: k,
				addValidator: k.addValidator.bind(k),
				removeValidator: k.removeValidator.bind(k)
			} : null,
			options: w,
			unbinds: h
		}), ke({
			target: a,
			form: C,
			instance: j,
			options: w,
			unbinds: h
		})), C) {
			cr(a, C, b, f, k, h), k && (h.push(p(C, k, a)), h.push(wn(C)));
			try {
				await lr(a, b, C);
			} catch (e) {
				throw M(), e;
			}
		}
		o.forEach((e) => {
			let t = f.on(`formie:stage:${e}:before`, async (t) => {
				d(a, `formie:stage:${e}:before`, t);
			}), n = f.on(`formie:stage:${e}:before`, async (t) => {
				let n = {
					core: "prepare",
					field: "prepare",
					address: "prepare",
					captcha: "challenge",
					payment: "payment"
				}, r = t;
				if (e === "prepare") try {
					A.assertReady();
				} catch {
					r.abort("A required form feature could not start. Reload the page or contact the site administrator.");
					return;
				}
				for (let t of A.instances) if (t.beforeSubmit && n[t.kind ?? "core"] === e) {
					let { form: e, action: n, formData: i, abort: a, isAborted: o, abortReason: s } = r;
					await t.beforeSubmit({
						form: e,
						action: n,
						formData: i,
						abort: a,
						isAborted: o,
						abortReason: s
					});
				}
			}), r = f.on(`formie:stage:${e}:after`, async (t) => {
				d(a, `formie:stage:${e}:after`, t);
			});
			h.push(t, n, r);
		});
		let N = f.on("formie:submit:before", async (e) => {
			d(a, "formie:submit:before", e);
		}), re = f.on("formie:submit:after", async (e) => {
			if (d(a, "formie:submit:after", e), !C) return;
			let t = e;
			if (t.code === "PREFLIGHT_COMPLETE") return;
			let n = {
				form: C,
				action: t.action ?? "submit",
				formData: new FormData(C)
			};
			for (let e of A.instances) await e.afterSubmit?.(n, t);
		}), ie = f.on("formie:submit:final:before", async (e) => {
			d(a, "formie:submit:final:before", e);
		}), P = f.on("formie:submit:final:after", async (e) => {
			d(a, "formie:submit:final:after", e);
		});
		return h.push(N, re, ie, P), n.set(a, {
			options: b,
			bus: f,
			form: C,
			validator: k,
			modules: A,
			unbinds: h,
			instance: j,
			unbindEventContext: M
		}), C instanceof HTMLFormElement && b.mode === "server-rendered" && Ge(C) && Ke(C), d(a, "formie:mount:after", {
			id: v,
			mode: b.mode,
			instance: j
		}), C instanceof HTMLFormElement && _(C), K.log("Mount complete.", {
			id: v,
			target: $(a),
			mode: b.mode
		}), j;
	}, l = (e, t) => {
		if (!t.autoVisible || rr(e) || typeof IntersectionObserver > "u") return c(e, t);
		if (n.has(e)) return Promise.resolve(n.get(e)?.instance || null);
		if (i.has(e)) return K.log("Mount deferred (already waiting visibility).", { target: $(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((n) => {
			n.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), K.log("Visibility reached, proceeding mount.", { target: $(e) }), c(e, {
				...t,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return r.observe(e), i.set(e, () => {
			r.disconnect();
		}), K.log("Mount deferred until visible.", { target: $(e) }), Promise.resolve(null);
	};
	return {
		mount: c,
		unmount: s,
		update: async (e, t) => {
			let r = n.get(e);
			if (!r) return c(e, {
				...J(e),
				...t,
				mode: t.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...t
			};
			let i = t.payload?.theme || r.options.payload?.theme || nr(e, r.form)?.theme, a = ne(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && d(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => n.get(e)?.instance || null,
		refreshForCache: async (e) => {
			Ln("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let t = null;
			if (t = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !t) {
				K.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = n.get(t), i = Y(t), a = r?.options || J(t);
			if (!i) {
				K.warn("refreshForCache found no form element for target.", { target: $(t) });
				return;
			}
			let o = a.formHandle || t.dataset.formieHandle || i.dataset.formieHandle, s = Bn(a, t), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				K.warn("refreshForCache found no form handle for target.", { target: $(t) });
				return;
			}
			let l = await Ze(s, o, c, a, i.querySelector("input[name=\"requestToken\"]")?.value, i.querySelector("input[name=\"siteId\"]")?.value || a.siteId);
			l && (ar(i, l), d(t, "formie:refresh-tokens:after", l));
		},
		registerModule: (e, t) => r.register(e, t),
		unregisterModule: (e) => {
			r.unregister(e);
		},
		getRegisteredModules: () => r.getAll(),
		scan: async (e) => {
			let t = e || document, n = Array.from(t.querySelectorAll(G));
			K.log("Scan started.", {
				scope: t === document ? "document" : t,
				targetCount: n.length
			});
			let r = (await Promise.all(n.map((e) => {
				let t = J(e);
				return l(e, t);
			}))).filter((e) => !!e);
			return K.log("Scan finished.", {
				mountedCount: r.length,
				deferredCount: n.length - r.length
			}), r;
		},
		observe: (e) => {
			if (typeof MutationObserver > "u") return () => {};
			let t = e || document;
			K.log("Observer started.", { scope: t === document ? "document" : t });
			let r = new MutationObserver((e) => {
				e.forEach((e) => {
					e.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(G) && (K.log("Observer detected new root.", { target: $(e) }), l(e, J(e))), e.querySelectorAll(G).forEach((e) => {
							K.log("Observer detected new nested root.", { target: $(e) }), l(e, J(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (n.has(e) && (K.log("Observer detected removed root.", { target: $(e) }), s(e)), e.querySelectorAll(G).forEach((e) => {
							n.has(e) && (K.log("Observer detected removed nested root.", { target: $(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(t, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), K.log("Observer stopped."), i.forEach((e, n) => {
					ir(n, t) && (e(), i.delete(n));
				});
				let e = [];
				t instanceof Element && t.matches(G) && e.push(t), t.querySelectorAll(G).forEach((t) => {
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
var dr = A("general", "module-hydrator");
async function fr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.surface ?? "cp-edit", i = e.modules ?? {
		contractVersion: 2,
		surface: r,
		entries: []
	}, a = e.registry ?? new Kt(), o = new Gt(), s = await on(i, {
		registry: a,
		setupContext: {
			formId: n?.id || t.id || "formie-modules",
			root: t,
			form: n,
			target: t,
			scope: "form",
			surface: r,
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
			surface: r
		}
	});
	return dr.log("Hydrated module manifest.", {
		moduleCount: i.entries.length,
		instanceCount: s.instances.length,
		surface: r
	}), {
		get instances() {
			return s.instances;
		},
		get failures() {
			return s.failures;
		},
		prepare: async (e) => {
			if (!n) throw Error("Browser modules require a mounted form element.");
			s.assertReady(), s.instances.forEach((e) => e.assertReady?.());
			let t, r = {
				form: n,
				action: e === "back" || e === "save" ? e : "submit",
				formData: new FormData(n),
				abort: (e) => {
					t = e || "A form feature could not complete.";
				},
				isAborted: () => !!t,
				abortReason: () => t
			}, i = {
				core: 0,
				field: 0,
				address: 0,
				captcha: 1,
				payment: 2
			};
			for (let e of [...s.instances].sort((e, t) => i[e.kind ?? "core"] - i[t.kind ?? "core"])) if (await e.beforeSubmit?.(r), t) throw Error(t);
			return Object.fromEntries(Array.from(n.querySelectorAll("input[type=\"hidden\"][name]")).map((e) => [e.name, e.value]));
		},
		result: async (e) => {
			if (!n) return;
			let t = {
				form: n,
				action: e.action ?? "submit",
				formData: new FormData(n)
			};
			await o.emit("formie:submit:result", e);
			for (let n of s.instances) await n.afterSubmit?.(t, e);
			n.dispatchEvent(new CustomEvent("formie:submit:result", {
				detail: e,
				bubbles: !0
			}));
		},
		update: (e) => s.updateManifest(e),
		assertReady: () => {
			s.assertReady(), s.instances.forEach((e) => e.assertReady?.());
		},
		destroy: async () => {
			await s.destroy(), o.clear();
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
//#endregion
//#region src/js/core/formie.ts
function pr(e) {
	return e instanceof Element;
}
function mr(e) {
	return e.ok;
}
function hr(e) {
	return typeof e == "string" ? `selector "${e}"` : pr(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function gr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) pr(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function _r(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : pr(e) ? [e] : gr(e);
}
function vr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function yr(e) {
	let t = _r(e);
	return t.length > 0 || typeof e != "string" ? t : (await vr(), _r(e));
}
function br(e) {
	return typeof e == "string" ? document : pr(e) ? e.getRootNode() : document;
}
function xr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function Sr(e, t, n, r) {
	let i = [], a = xr(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), c = [];
		if (e.onReady?.(s), c.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), mr(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
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
async function Cr(e) {
	let t = e.client ?? ur(), n = /* @__PURE__ */ new Map(), r = await yr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${hr(e.element)}.`);
	await Sr(e, t, n, r);
	let i = e.observe ? t.observe(br(e.element)) : null;
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
			let r = _r(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : Sr(e, t, n, r);
		},
		async destroy() {
			i?.();
			let e = Array.from(n.entries());
			for (let [r, i] of e) i.unsubs.forEach((e) => e()), await t.unmount(r), n.delete(r);
		}
	};
}
//#endregion
//#region src/js/core/craft-forms.ts
var wr = "form[data-formie-craft-form]:not([data-formie-init=\"false\"])", Tr = /* @__PURE__ */ new WeakMap();
function Er(e) {
	let t = Tr.get(e);
	if (t) return t;
	let n = JSON.parse(e.dataset.formieThemeClasses || "{}"), r = ne(e, n), i = e.getAttribute("novalidate"), a = new yn(e, {
		live: e.dataset.formieValidationOnFocus === "true",
		errorAriaLive: I(e),
		fieldContainerErrorClass: r.fieldLayoutError || [],
		inputErrorClass: r.fieldControlError || [],
		messagesClass: r.fieldErrors || [],
		messageClass: r.fieldError || []
	}), o = (t) => {
		let n = t.submitter;
		t.defaultPrevented || i !== null || e.dataset.formieValidationOnSubmit === "false" || n?.hasAttribute("formnovalidate") || a.submit().length && (t.preventDefault(), L(e));
	}, s = (t) => {
		queueMicrotask(() => {
			!t.defaultPrevented && Tr.has(e) && a.resetLiveState();
		});
	};
	e.addEventListener("submit", o), e.addEventListener("reset", s);
	let c = {
		validator: a,
		owners: /* @__PURE__ */ new Set(),
		destroy: () => {
			e.removeEventListener("submit", o), e.removeEventListener("reset", s), a.removeEventListeners(), a.resetLiveState(), i === null ? e.removeAttribute("novalidate") : e.setAttribute("novalidate", i), Tr.delete(e);
		}
	};
	return Tr.set(e, c), e.dispatchEvent(new CustomEvent("formie:validator:ready", {
		bubbles: !0,
		detail: {
			validator: a,
			addValidator: a.addValidator.bind(a),
			removeValidator: a.removeValidator.bind(a)
		}
	})), c;
}
function Dr({ root: e = document, observe: t = !1 } = {}) {
	let n = Symbol("craftForms"), r = /* @__PURE__ */ new Map(), i = !1, a = (e, t) => {
		t.owners.delete(n), t.owners.size || t.destroy(), r.delete(e);
	}, o = () => {
		if (i) return;
		let t = new Set(e.querySelectorAll(wr));
		e instanceof HTMLFormElement && e.matches(wr) && t.add(e);
		for (let [e, n] of r) t.has(e) || a(e, n);
		for (let e of t) if (!r.has(e)) try {
			let t = Er(e);
			t.owners.add(n), r.set(e, t);
		} catch (e) {
			console.warn("Formie could not initialize Craft form validation.", e);
		}
	};
	o();
	let s = t ? new MutationObserver(o) : null;
	return s?.observe(e, {
		childList: !0,
		subtree: !0,
		attributes: !0,
		attributeFilter: ["data-formie-craft-form", "data-formie-init"]
	}), {
		get: (e) => r.get(e)?.validator || null,
		rescan: o,
		destroy: () => {
			i = !0, s?.disconnect();
			for (let [e, t] of r) a(e, t);
		}
	};
}
//#endregion
//#region src/js/core/client-rendered-modules.ts
var Or = {
	conditions: {
		kind: "core",
		reason: "Core evaluates structured conditions and the adapter renders visibility."
	},
	repeater: {
		kind: "field",
		reason: "The adapter owns row markup and core owns row values."
	},
	signature: {
		kind: "field",
		reason: "The adapter owns its signature control and cleanup."
	},
	"file-upload": {
		kind: "field",
		reason: "The shared transport stages selected files and submits attachment capabilities."
	},
	"upload-manager": {
		kind: "field",
		reason: "Native file selection uses the shared staged upload transport."
	},
	"checkbox-radio": {
		kind: "field",
		reason: "Framework controls own checked state."
	},
	"text-limit": {
		kind: "field",
		reason: "Core validation enforces the structured minimum and maximum rules."
	},
	"date-picker": {
		kind: "field",
		reason: "The adapter renders the structured date input contract."
	}
};
async function kr(t, n, r = qt) {
	for (let [e, t] of Object.entries(Or)) {
		let n = `formie:${e}`;
		r.get(n) || r.register({
			moduleId: n,
			version: 2,
			surfaces: ["client-rendered"],
			kind: t.kind,
			match: () => !0,
			setup: async (e) => (await e.emit("formie:browser:module:delegated", {
				moduleId: n,
				reason: t.reason,
				target: e.target
			}), { destroy: () => void 0 })
		});
	}
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
	let o = n.subscribe(a), s = await fr({
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
		get instances() {
			return s.instances;
		},
		get failures() {
			return s.failures;
		},
		destroy: async () => {
			c(), o(), await s.destroy();
		}
	};
}
//#endregion
export { l as FORMIE_HTML_EVENT_NAMES, yn as FormieValidator, Se as LEGACY_FORMIE_DOM_EVENT_BRIDGES, Ce as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, Kt as ModuleRegistry, ke as bindLegacyDomEventCompatibility, je as bindLegacyValidatorCompatibility, ye as buildFieldValueRegistry, qt as clientRenderedModuleRegistry, Dr as craftForms, A as createDebug, ur as createFormieClient, O as debugLog, k as debugWarn, xe as defineAddressModule, pe as defineCaptchaModule, fe as definePassiveCaptchaModule, T as definePaymentModule, ge as fieldKeyToInputName, Cr as formie, c as getFieldModuleEventName, de as getFormieTranslations, s as getGlobalModuleLifecycleEventName, i as getScopedModuleLifecycleEventName, fr as hydrateFormieModules, me as inputNameToFieldKey, D as isFormieDebugEnabled, le as mergeFormieTranslations, kr as mountClientRenderedModules, he as normalizeFieldKey, u as normalizeFormieEventName, _e as parseFieldReference, be as resolveFieldReferenceFromFormData, ve as resolveFieldReferenceLive, we as resolveLegacyCompatibilityOptions, te as setFormieDebugEnabled, ue as setFormieTranslations, ce as t, a as toDomEventName, se as translate };
