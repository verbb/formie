import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { i as t, m as n, t as r } from "./chunks/dist-ZLeW0zZ3.js";
import { c as i, d as a, l as o, o as s, r as c, t as l, u } from "./chunks/event-names-BCI2FLD8.js";
import { a as d, c as f, d as p, f as m, i as h, l as g, n as _, o as v, p as y, r as b, s as x, t as S, u as C } from "./chunks/api-D6kJ7kXZ.js";
import { a as ee, i as te, n as ne, r as w, t as T } from "./chunks/debug-BV0DvdHx.js";
import { i as E, r as re, t as D } from "./chunks/theme-classes-DAQuEqdP.js";
import { i as O, t as ie } from "./chunks/csrf-DxHg_ZYt.js";
import { n as k, t as ae } from "./chunks/http-C2QdHuWO.js";
import { a as oe, i as se, n as ce, r as le, t as ue } from "./chunks/i18n-BY1ds1BL.js";
import { n as de, t as fe } from "./chunks/api-DYYsVIub.js";
import { n as pe, r as me, t as he } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as ge, n as _e, r as ve, t as ye } from "./chunks/field-references.resolver-Ca8MguFv.js";
import { t as be } from "./chunks/api-ghNr2uxX.js";
//#region src/js/compatibility/event-map.ts
var xe = [
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
], Se = [
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
function Ce(e) {
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
function we(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Te(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function Ee(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function De(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? Ee(r, t) : t;
}
function Oe({ target: e, form: t, instance: n, options: r, unbinds: i }) {
	r.legacyDomEvents && xe.forEach((r) => {
		let o = (i) => {
			i instanceof CustomEvent && Te(r, i.detail) && we(r.target === "document" ? document : t, r.legacyEvent, De(r, i.detail, e, t, n));
		};
		e.addEventListener(a(r.canonicalEvent), o), i.push(() => {
			e.removeEventListener(a(r.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function A(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function j(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function ke({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	A(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && j(e.detail, a) && A(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && j(n.detail, a) && n.target instanceof Element && t.contains(n.target) && A(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && j(n.detail, a) && n.target instanceof Element && t.contains(n.target) && A(n.target, "formieValidatorClearError", {
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
function M(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function Ae(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function je(e) {
	return e === "off" ? null : e;
}
function N(e, t) {
	if (t) {
		e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true");
		return;
	}
	e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic");
}
//#endregion
//#region src/js/core/field-error-aria.ts
function Me(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Ne(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	if (r.length) {
		e.setAttribute("aria-describedby", r.join(" "));
		return;
	}
	e.removeAttribute("aria-describedby");
}
function Pe(e, t) {
	e.setAttribute("aria-errormessage", t), Me(e, t);
}
function Fe(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), Ne(e);
}
function P(e) {
	return !!e && e.hasAttribute("data-formie-validation-skip");
}
//#endregion
//#region src/js/core/validation-focus.ts
function Ie(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Le(e) {
	return Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).find((e) => !P(e)) || (Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")).find((e) => !P(e)) ?? null);
}
function Re(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function ze(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t;
		if (n.getAttribute("data-formie-field-has-error") !== "true" && n.querySelector("[data-formie-field-error]") === null) return;
		n.setAttribute("data-formie-field-has-error", "true"), D(n, e, "fieldLayoutError");
		let r = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let i = t;
			if (P(i)) return;
			i.setAttribute("aria-invalid", "true"), D(i, e, "fieldControlError"), i.setAttribute("data-formie-input-has-error", "true"), r && Pe(i, r);
			let a = n.querySelector("[data-formie-instructions]");
			a?.id && Me(i, a.id);
		});
	});
}
function Be(e) {
	return !!Ie(e) || !!Re(e);
}
function Ve(e) {
	let t = Ie(e);
	if (t) {
		let e = Le(t);
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
	let n = Re(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/transport/forms-api.ts
var F = T("general", "transport");
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
async function We(e, t, n = {}, r = {}) {
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
async function Ke(e, t, n, r = {}, i) {
	let a = new URL(e, window.location.origin);
	a.searchParams.set("handle", t), n && a.searchParams.set("renderId", n), i && a.searchParams.set("requestToken", i), F.log("requestRefreshTokens start.", {
		endpoint: a.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let o = await k(a.toString(), r);
	return F.log("requestRefreshTokens complete.", { hasRefreshTokens: !!o.refreshTokens }), o.refreshTokens || o;
}
async function qe(e, t, n) {
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
	F.log("requestSetPage start.", {
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
	return F.log("requestSetPage complete.", a), a;
}
function Je(e, t) {
	let n = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && i.append(e, n);
	}), ie(i, t), F.log("clearSubmissionOnUnload start.", { requestUrl: n.toString() });
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
async function Ye(e, t) {
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
var Xe = [
	"prepare",
	"validate",
	"challenge",
	"payment",
	"send",
	"result"
], Ze = [
	"prepare",
	"validate",
	"challenge",
	"payment"
], I = T("general", "pipeline");
function Qe(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function $e(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function et(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function tt(e, t) {
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
function nt(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = $e(t) ? t : null;
		n && !n.closest("[data-formie-page]") && et(n) && tt(e, n);
	});
}
function rt(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = $e(t) ? t : null;
		r && r.name && !r.disabled && (r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), et(r) && tt(e, r)));
	}), n;
}
function it(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function at(e, t) {
	let n = f(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return nt(i, e), it(i, rt(i, r)), i.set("submitAction", t), i;
}
function ot(e, t) {
	if (t !== "submit") return !1;
	let n = f(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function st(e, t, n, r = {}) {
	I.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = ot(e, t), c = {
		form: e,
		action: t,
		formData: at(e, t),
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
			e.formData = at(e.form, e.action);
			let t = await Ye(e.form, e.formData);
			return o = t, t;
		},
		result: async (e) => (o && o.ok && o.redirect?.url && (o.redirect.target === "new-tab" ? window.open(o.redirect.url, "_blank", "noopener,noreferrer") : (e.form.setAttribute("data-formie-internal-navigation", "redirect"), window.location.href = o.redirect.url)), null)
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
	let u = r.preflightOnly ? Ze : Xe;
	for (let e of u) {
		if (I.log("Stage start.", {
			stage: e,
			action: t
		}), i) return I.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Qe(e, a);
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
			let t = Qe(e, a);
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
			let t = Qe(e, a);
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
		code: r.preflightOnly ? "PREFLIGHT_COMPLETE" : void 0,
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
function ct(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function lt(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = ct(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/core/submit-result-ui.ts
var L = /* @__PURE__ */ new WeakMap();
function ut(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function dt(e) {
	return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function ft(e) {
	return (e.dataset.formieSuccessMessagePosition || e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function pt(e) {
	let t = (e.dataset.formieSuccessMessageTimeout || e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function mt(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function ht(e) {
	let t = L.get(e);
	typeof t == "number" && (window.clearTimeout(t), L.delete(e));
}
function gt(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function _t(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function vt(e, t) {
	return t === "bottom-form" ? _t(e) : gt(e);
}
function yt(e, t) {
	return t === "top-form" ? gt(e) : t === "bottom-form" && !mt(e) ? _t(e) : e;
}
function bt(e) {
	let t = dt(e), n = vt(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), D(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function xt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), D(n, e, "message", "messageError"), n.setAttribute("role", "alert"), N(n, je(M(e))), n;
}
function St(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = yt(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), D(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function Ct(e) {
	return lt(e, (t) => {
		D(t, e, "fieldErrors");
	});
}
function wt(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		E(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), E(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), Fe(n, i);
		});
	}), C(e);
}
function Tt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), E(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function Et(e) {
	ht(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), E(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), ut(e) === "message" && mt(e) || d(e, !1);
}
function Dt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Ot(e, t) {
	let n = je(M(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = `fields[${t.split(".").join("][")}]`, a = e.querySelector(`[name="${CSS.escape(i)}"], [name="${CSS.escape(i + "[]")}"]`)?.closest("[data-formie-field-handle]") || e.querySelector(`[data-formie-field-handle="${CSS.escape(t)}"]`);
		if (!a) return;
		let o = Ct(a), s = o.id && o.id.trim() ? o.id : `${t}-errors`;
		o.id = s, N(o, n), D(a, e, "fieldLayoutError"), a.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${s}-${n + 1}`, D(r, e, "fieldError"), r.textContent = t, o.appendChild(r);
		});
		let c = o.querySelector("[data-formie-field-error]")?.id;
		a.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), D(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), c && Pe(n, c);
			let r = a.querySelector("[data-formie-instructions]");
			r?.id && Me(n, r.id);
		});
	}), C(e);
}
function kt(e, t) {
	let n = bt(e), r = xt(e, n);
	D(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), n.setAttribute("role", "alert"), D(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function At(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.payment?.status;
	return t === "requiresAction" || t === "pending" || t === "unknown";
}
function jt(e, t) {
	let n = bt(e), r = xt(e, n);
	D(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), D(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function Mt(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || ut(e) === "message" && ft(e) !== "";
}
function Nt(e, t) {
	let n = ft(e);
	if (!n) return;
	let r = St(e, n);
	D(r, e, "message", "messageSuccess"), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-success", "true"), r.setAttribute("role", "status"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true");
	let i = document.createElement("div");
	i.setAttribute("data-formie-success", "true"), D(i, e, "success"), i.innerHTML = t, r.appendChild(i), mt(e) && d(e, !0);
	let a = pt(e);
	if (a !== null) {
		let t = window.setTimeout(() => {
			L.delete(e), Et(e);
		}, a);
		L.set(e, t);
	}
}
function R(e, t) {
	if (wt(e), Tt(e), Et(e), Dt(e), t.ok) {
		Mt(e, t) && Nt(e, t.message || "");
		return;
	}
	if (!t.ok) {
		if (At(t)) {
			let n = String(t.payment?.message || "").trim();
			n && jt(e, n);
			return;
		}
		t.fieldErrors && Ot(e, t.fieldErrors), t.formErrors?.length ? kt(e, t.formErrors) : !t.fieldErrors && t.message && kt(e, [t.message]), Ve(e);
	}
}
//#endregion
//#region src/js/core/submit-flow.ts
var Pt = T("general", "submit-flow");
function Ft(e) {
	return !(!e.ok && e.stage === "validate");
}
function It(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function z(e) {
	wt(e), Tt(e), Et(e), Dt(e);
}
async function Lt(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	z(r), v(r, c || null);
	let f = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), f = await st(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), R(r, f), d(f), b(r, f, s), Ft(f) && await u(f);
	} catch (e) {
		f = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, R(r, f), d(f), Pt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		It(f) || h(r);
	}
	return f;
}
//#endregion
//#region src/js/events/event-bus.ts
var Rt = class {
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
}, B = class {
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
}, zt = new B(), Bt = {
	"address-finder": () => import("./chunks/address-finder-O49uDRoR.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-C1P1nTDM.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-DD1S7PY9.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BE5gi3vo.js").then((e) => e.placeKitModule)
}, Vt = {
	"captcha-eu": () => import("./chunks/captcha-eu-DERJbXnH.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-GFqCt1Mh.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-WoW246JG.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-D0T8aIHz.js").then((e) => e.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-CruXddZq.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-CCnJYCnG.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-BuVqRRYn.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-CloqhO2A.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-C04kNGYQ.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-n9DNyPxi.js").then((e) => e.turnstileModule)
}, Ht = {
	calculations: () => import("./chunks/calculations-CX5nsXGP.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-18yYJzpK.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-uP2fNn2e.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-BvMbUhsZ.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-B5AkIZLS.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-FBXzYP2j.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-CXBthhJs.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-BloA6HqR.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-D9Lr-mS0.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-DvlIinbA.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-CvIeBVSw.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-CkCkXCsS.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-CqDNfNCn.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-CI-NNMRe.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-MQKfsisl.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-BWRKp9I7.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-Bqak9rUT.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-BH8wwtRd.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-Btgf9fVc.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-fk1hUPff.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-hPi8iMXl.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-C04ujvBU.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-9RF567j5.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-BbbkikKF.js").then((e) => e.textLimitModule)
}, Ut = {
	bpoint: () => import("./chunks/bpoint-D0RKFM4F.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-or6dtjoT.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-DzJ-0FKg.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-DG8Ju83x.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-C8m3Fvqu.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-B3EvrVxw.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-BIYR9Um3.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-B_yxIo2d.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-CeOzS0sL.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-DEzxv9D9.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-CVPQGDz3.js").then((e) => e.stripeModule)
}, Wt = {
	...Ht,
	...Bt,
	...Vt,
	...Ut
}, V = /* @__PURE__ */ new Map();
function Gt(e) {
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
function Kt(e, t, n) {
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
async function qt(e) {
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
async function Jt(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(Wt, e.slice(7)) ? Wt[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	V.has(e) || V.set(e, r().catch((t) => {
		throw V.delete(e), t;
	}));
	let i = await V.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function Yt(e, t, n) {
	return [...new Set(e.targets.flatMap((e) => {
		if (e.type === "form") return [n || t];
		let r = e.type === "selector" ? e.selector : e.type === "field" ? `[data-formie-field-uid="${CSS.escape(e.uid)}"]` : e.type === "page" ? `[data-formie-page-id="${CSS.escape(e.id)}"]` : `[data-formie-action="${CSS.escape(e.action)}"]`;
		return [...t.matches(r) ? [t] : [], ...t.querySelectorAll(r)];
	}))].filter((e) => !e.closest("[hidden], [data-formie-hidden=\"true\"], [data-formie-conditionally-hidden], [data-formie-page-hidden]"));
}
async function Xt(e, t) {
	n(e);
	let r = e.surface;
	if (t.matchContext.surface !== r || t.setupContext.surface !== r) throw Error(`Browser module manifest surface ${r} cannot mount as ${t.matchContext.surface}.`);
	let { root: i, form: a } = t.setupContext, o = /* @__PURE__ */ new Map(), s = /* @__PURE__ */ new Map(), c = /* @__PURE__ */ new Map(), l = [], u = !1, d = Promise.resolve(), f = !1, p = async (e, n) => {
		let i = {
			key: e.key,
			moduleId: e.moduleId,
			required: e.required,
			surface: r,
			code: "MODULE_UNAVAILABLE",
			message: "A form feature could not start. Reload the page or contact the site administrator."
		};
		s.set(e.key, i), c.set(e.key, Gt(n)), console.error("[formie] Browser module failure", i, n), await t.setupContext.emit("formie:browser:module:error", i);
	}, m = async (e) => {
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
						await qt(n.textContent || ""), d.textContent = "Details copied.";
					} catch {
						d.textContent = "Copy failed. Select the details above and copy them manually.";
						let e = document.createRange();
						e.selectNodeContents(n), window.getSelection()?.removeAllRanges(), window.getSelection()?.addRange(e);
					}
				}), l.append(u, d), s.append(c, n, l), e.append(r, s);
			}
			e.dataset.formieModuleError = "true", i !== a && a.contains(i) ? i.before(e) : a.prepend(e);
		}
		let t = e.querySelector("[data-formie-module-error-details]");
		t && (t.textContent = Kt(r, s.values(), c));
	}, _ = (e) => {
		h() && (e.preventDefault(), e.stopImmediatePropagation(), g());
	}, v = async () => {
		let n = new Set(e.entries.map((e) => e.key));
		for (let e of s.keys()) n.has(e) || (s.delete(e), c.delete(e));
		let d = [];
		for (let [e, t] of o) n.has(e) || (d.push(...Array.from(t.values(), ({ instance: e }) => e)), o.delete(e), s.delete(e), c.delete(e));
		for (let e of d.reverse()) await m(e), l.splice(l.indexOf(e), 1);
		for (let n of e.entries) {
			if (u) continue;
			let e = s.get(n.key);
			e && s.set(n.key, {
				...e,
				required: n.required
			});
			let d;
			try {
				d = Yt(n, i, a);
			} catch (e) {
				s.has(n.key) || await p(n, e);
				continue;
			}
			let f = o.get(n.key) ?? /* @__PURE__ */ new Map();
			o.set(n.key, f);
			for (let [e, t] of Array.from(f.entries()).reverse()) d.includes(e) || (await m(t.instance), f.delete(e), l.splice(l.indexOf(t.instance), 1));
			let h;
			try {
				h = await Jt(n.moduleId, t.registry);
			} catch (e) {
				s.has(n.key) || await p(n, e);
				continue;
			}
			let g = !1, _ = !1;
			for (let e of d) {
				if (u) return;
				let a = JSON.stringify([
					n.moduleId,
					n.config,
					n.required
				]), o = f.get(e);
				if (o?.config === a) continue;
				let c = {
					...t.setupContext,
					target: e,
					entryKey: n.key,
					surface: r,
					scope: n.targets[0]?.type ?? "form",
					options: n.config
				};
				try {
					if (o) {
						if (o.instance.update && o.moduleId === n.moduleId && o.required === n.required) {
							await o.instance.update(c), o.config = a;
							continue;
						}
						await m(o.instance), f.delete(e), l.splice(l.indexOf(o.instance), 1);
					}
					if (h.surfaces && !h.surfaces.includes(r)) throw Error(`Module ${n.moduleId} does not support ${r}.`);
					if (h.kind !== n.kind) throw Error(`Module ${n.moduleId} is registered as ${h.kind}, not ${n.kind}.`);
					if (!h.match({
						...t.matchContext,
						target: e,
						scope: c.scope,
						manifestItem: n
					})) throw Error(`Module ${n.moduleId} does not support the rendered target.`);
					let s = await h.setup(c);
					if (!s) throw Error(`Module ${n.moduleId} did not initialize.`);
					if (u || !i.contains(e) && e !== i) {
						await m(s);
						continue;
					}
					s.key = n.key, s.moduleId = n.moduleId, s.kind = n.kind, s.target = e;
					let d = s.assertReady;
					s.assertReady = () => {
						try {
							d?.();
						} catch (e) {
							if (p(n, e), n.required) throw Error("A required form feature could not start.");
						}
					};
					let g = s.beforeSubmit, v = s.afterSubmit;
					s.beforeSubmit = async (e) => {
						try {
							await g?.(e);
						} catch (t) {
							await p(n, t), n.required && e.abort("A required form feature could not complete. Reload the page or contact the site administrator.");
						}
					}, s.afterSubmit = async (e, t) => {
						try {
							await v?.(e, t);
						} catch (e) {
							await p(n, e);
						}
					}, f.set(e, {
						instance: s,
						config: a,
						moduleId: n.moduleId,
						required: n.required
					}), l.push(s), _ = !0, await t.setupContext.emit("formie:browser:module:mount", {
						key: n.key,
						moduleId: n.moduleId,
						target: e
					});
				} catch (e) {
					g = !0, s.has(n.key) || await p(n, e);
				}
			}
			!g && (_ || d.length === 0) && (s.delete(n.key), c.delete(n.key));
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
		updateManifest: async (t) => {
			if (n(t), t.surface !== r) throw Error("A mounted browser module runtime cannot change surfaces.");
			e = t, d = d.then(v), await d;
		}
	};
}
//#endregion
//#region src/js/utils/form-started-at.ts
var Zt = "formie:formStartedAt:";
function Qt(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${Zt}${n}` : null, i = r ? sessionStorage.getItem(r) : null;
	i || (i = String(Date.now()), r && sessionStorage.setItem(r, i)), t.value = i;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var $t = /* @__PURE__ */ new Set([
	"action",
	"redirect",
	"requestToken",
	"uploadCreateToken",
	"renderId",
	"formStartedAt",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"progressId"
]);
function H(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => H(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${H(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function en(e) {
	return H(e, /* @__PURE__ */ new WeakSet());
}
function tn(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !O(n, t) && !$t.has(n);
}
function nn(e) {
	return en(Array.from(new FormData(e).entries()).filter(([t]) => tn(String(t || ""), e)));
}
function rn(e, t = {}) {
	let n = null, r = !1, i = !1, a = null, o = null, s = null, c = () => {
		a !== null && (window.cancelAnimationFrame(a), a = null), o !== null && (window.clearTimeout(o), o = null), s !== null && (window.clearTimeout(s), s = null);
	}, l = () => r ? (i = nn(e) !== n, i) : !1, u = () => {
		n = nn(e), r = !0, i = !1;
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
var an = {
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
function on(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function sn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !P(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var cn = {
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
	email: an,
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
			let n = sn(e);
			return !n || t(e.input.value, { type: "match" }, { comparison: n.value }) === null;
		},
		message: (e) => {
			let t = sn(e)?.closest("[data-formie-field-handle]"), n = on(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, ln = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, U = T("general", "validator");
function W(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function un(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var dn = class {
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
			patterns: ln,
			...t
		}, Object.entries(cn).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		U.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, o("ready"), { validator: this });
	}
	inputs(e = null) {
		if (W(e)) return P(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => W(e) && !P(e));
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
		}), U.log("Validation pass complete.", {
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
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), Fe(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, o("clear-error"), { validator: this }), C(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= lt(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let a = r.getAttribute("data-formie-field-handle") || "field", s = `${a}-error`;
		i.id = i.id || `${a}-errors`, N(i, Ae(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			P(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), Pe(t, s));
		});
		for (let e = r; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, o("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: n
		}), C(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: se,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? se("{label} is invalid.", { label: r.label });
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
			return U.warn("Invalid validation rules payload.", { formId: this.form.id || null }), t;
		}
		return Array.isArray(n) && n.forEach((e) => {
			if (!e || typeof e != "object" || Array.isArray(e)) return;
			let n = e, r = typeof n.type == "string" ? n.type.trim() : "";
			r && (t[r] = n);
		}), t;
	}
	destroy() {
		U.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, o("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		if (e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]")) return !1;
		if (e.closest("[data-formie-page-hidden]")) return !!t.includeHiddenPages;
		let n = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-rich-text]");
		return n instanceof HTMLElement ? un(n) : un(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		if (e.target instanceof HTMLElement && W(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && !(e instanceof CustomEvent)) {
			if (e.target instanceof HTMLSelectElement) {
				this.activated.add(e.target), this.validate(e.target);
				return;
			}
			e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio") && (this.activated.add(e.target), this.validate(e.target));
		}
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !P(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
	}
	submit(e = null, { final: t = !1 } = {}) {
		return this.submitted = !0, U.log("Submit validation requested.", { final: t }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(e, { includeHiddenPages: t });
	}
	resetLiveState() {
		this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
	}
	addEventListeners() {
		this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, U.log("Event listeners attached."));
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, U.log("Event listeners removed.");
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
function fn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function pn(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !fn(e));
}
function mn(e) {
	let { final: t } = g(e);
	return "submit";
}
function hn(e) {
	return !pn(e, mn(e));
}
function gn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || hn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var G = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", _n = 300, vn = "/actions/formie/server/forms/render", yn = "/api", bn = "/actions/formie/server/forms/refresh-tokens", xn = "/actions/formie/server/submissions/submit", Sn = "/actions/formie/server/submissions/set-page", Cn = "/actions/formie/server/submissions/clear-submission", wn = "/actions/formie/file-upload/hydrate", K = T("general", "client"), Tn = /* @__PURE__ */ new Set();
function q(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function En(e) {
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
		staticCache: En(t),
		autoVisible: q(t.formieAutoVisible, !0),
		compatibility: q(t.formieCompatibility, !1)
	};
}
function Dn(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function On(e) {
	return e || "rest";
}
function kn(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function An(e, t) {
	Tn.has(e) || (Tn.add(e), K.warn(t));
}
function jn(e, t) {
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
function Y(e, t) {
	let n = (e || "").trim();
	return n ? n.includes(t) ? n : jn(t, n) : t;
}
function Mn(e, t) {
	return Y(e.endpoint || t.dataset.formieEndpoint, vn);
}
function Nn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : jn(yn, n) : yn;
}
function Pn(e, t) {
	return Y(t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint, bn);
}
function Fn(e, t) {
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
function In(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = Y(r, xn), a = t.getAttribute("action");
	t.setAttribute("action", Fn(a, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href"), n = Y(r, Sn);
		e.setAttribute("href", Fn(t, n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", Y(r, wn));
	});
}
function Ln(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Rn(e) {
	return q(e.dataset.formieAutomaticSubmissionState, !0);
}
function zn(e, t, n) {
	return Y(n.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint, Cn);
}
function Bn(e) {
	return Ln(e.dataset.formieUnloadWarning);
}
function Vn(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function Hn(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function Un(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function Wn(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function Gn(e) {
	return Wn(window.location.href, "resumeToken") || Wn(e.getAttribute("action"), "resumeToken");
}
function Kn(e) {
	return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function qn(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function Jn(e) {
	return Math.max(0, qn(e.dataset.formieSubmitDelay, _n));
}
function X(e) {
	return Ln(e.dataset.formieValidationOnSubmit);
}
async function Yn(e) {
	let t = Jn(e);
	t < 1 || await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Z(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function Xn(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Z(n, "data-formie-modules"), i = Z(n, "data-formie-theme-classes") || Z(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function Zn(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function Qn(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function Q(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function $n(e, t) {
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
async function er(e, t) {
	let n = Dn(t.mode), r = On(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!kn(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? Nn(t, e) : Mn(t, e), c = r === "graphql" ? await Ge(s, a, o, t) : await We(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function tr(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await Ke(Pn(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	$n(n, i), y(e, "formie:refresh-tokens:refreshed", i);
}
function nr(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = zn(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		if (e) {
			t.setAttribute("data-formie-pending-action", e);
			return;
		}
		t.removeAttribute("data-formie-pending-action");
	};
	if (Bn(t)) {
		let n = rn(t, { shouldWarn: () => !Un(t) }), r = (e) => {
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
				Kn(n) && Vn(t, "set-page");
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
					let n = await qe(a, t, i);
					if (n.pageId && _(t, String(n.pageId)), !n.success) {
						let { form: e = [], ...r } = n.errors ?? {};
						z(t), Ot(t, r), kt(t, e), Ve(t);
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
	}), !Rn(t)) {
		let e = !1, n = () => {
			e || Un(t) || Gn(t) || (e = !0, Je(s, t));
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
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), f = t.getAttribute("data-formie-pending-action"), m = t.querySelector("input[name=\"submitAction\"]"), _ = d || f || m?.value || "submit", b = null, x = !1;
		try {
			if (s) b = await Lt({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: X(t),
				action: _,
				submitter: l,
				waitForSubmitDelay: Yn,
				onRefreshTokensAfterSubmit: async () => {
					await tr(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					y(e, "formie:submit:result", t);
				}
			});
			else {
				if (z(t), v(t, l), await Yn(t), b = await st(t, _, r, {
					validator: i,
					validateOnSubmit: X(t),
					preflightOnly: !0
				}), b.ok) {
					p(t, _), c = !0, Vn(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, Hn(t), h(t), i && X(t)) {
							let { scope: e, final: n } = g(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && R(t, {
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
				R(t, b), y(e, "formie:submit:result", b), Hn(t);
			}
		} catch (n) {
			c = !1, b = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, R(t, b), y(e, "formie:submit:result", b), Hn(t);
		} finally {
			u(null), !s && !x && !It(b) && h(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	});
}
async function rr(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = Pn(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await Ke(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value);
	o && n && ($n(n, o), y(e, "formie:refresh-tokens:after", o));
}
function ir() {
	let t = /* @__PURE__ */ new Map(), r = new B(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
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
			K.log("Unmount requested.", { target: Q(e) });
			let n = i.get(e);
			n && (n(), i.delete(e));
			let r = t.get(e);
			if (!r) {
				K.log("Unmount skipped (no mounted state).", { target: Q(e) });
				return;
			}
			y(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null, await r.modules.destroy(), r.bus.clear(), t.delete(e), y(e, "formie:unmount:after", { id: r.instance.id }), K.log("Unmount complete.", {
				id: r.instance.id,
				target: Q(e)
			});
		})().finally(() => {
			a.delete(e);
		});
		a.set(e, r), await r;
	}, c = async (a, c) => {
		K.log("Mount requested.", {
			target: Q(a),
			mode: c.mode,
			autoVisible: c.autoVisible
		});
		let l = i.get(a);
		l && (l(), i.delete(a));
		let u = t.get(a);
		if (u) return K.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: Q(a)
		}), u.instance;
		let d = new Rt(), f = [], p = a?.id || `formie-${t.size + 1}`, h = J(a), g = {
			...h,
			...c,
			mode: Dn(c.mode ?? h.mode),
			transport: On(c.transport ?? h.transport)
		}, _ = Ce(g.compatibility), v = await er(a, g), b = kn(a);
		b && e(b, g), g.staticCache = c.staticCache ?? En(b ? b.dataset : a.dataset);
		let S;
		try {
			S = Xn(a, b), v?.modules && n(v.modules), S?.modules && n(S.modules);
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
		let ee = v || S ? {
			...v || {},
			...S || {}
		} : null, te = ee?.theme, ne = {}, w = ee?.modules ?? {
			contractVersion: 2,
			surface: "server-rendered",
			entries: []
		};
		n(w), K.log("Resolved mount payload.", {
			target: Q(a),
			hasRenderPayload: !!v,
			hasEmbeddedPayload: !!S,
			moduleCount: w.entries.length
		});
		let T = re(a, te, b), E = b ? new dn(b, {
			live: Ln(b.dataset.formieValidationOnFocus),
			errorAriaLive: M(b),
			errorMessage: b.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: T.fieldLayoutError || [],
			inputErrorClass: T.fieldControlError || [],
			messagesClass: T.fieldErrors || [],
			messageClass: T.fieldError || []
		}) : null;
		if (b && E) {
			let e = b;
			e.formieValidation = E, ne.validation = E;
			let t = {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			};
			y(b, "formie:validator:ready", t), y(a, "formie:validator:ready", t);
		}
		b && (Qt(b), g.theme && g.theme !== "formie" && b.setAttribute("data-formie-frontend-theme", g.theme), (v || g.endpoint || a.dataset.formieEndpoint) && In(a, b, g), g.mode === "server-rendered" && Be(b) && (ze(b), Ve(b)), C(b)), Object.keys(T).length && y(a, "formie:theme:applied", { hasClasses: !0 });
		let D = await Xt(w, {
			registry: r,
			matchContext: {
				root: a,
				form: b,
				surface: w.surface
			},
			setupContext: {
				formId: p,
				root: a,
				form: b,
				target: a,
				scope: "form",
				surface: w.surface,
				state: ne,
				on: (e, t) => d.on(e, t),
				emit: (e, t) => (y(a, e, t), d.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && K.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		K.log("Module setup complete.", {
			target: Q(a),
			moduleInstances: D.instances.length
		});
		let O = {
			id: p,
			root: a,
			submit: async (e = "submit") => {
				if (K.log("Submit requested.", {
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
				let n = b.querySelector(`[data-formie-action="${e}"]`), r = await Lt({
					id: p,
					target: a,
					form: b,
					bus: d,
					validator: E,
					validateOnSubmit: X(b),
					action: e,
					submitter: n,
					waitForSubmitDelay: Yn,
					onRefreshTokensAfterSubmit: async () => {
						await tr(a, g, b);
					},
					dispatchSubmitResult: (e) => {
						y(a, "formie:submit:result", e);
					}
				});
				return K.log("Submit completed.", {
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
			validatorDetail: E ? {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			} : null,
			options: _,
			unbinds: f
		}), Oe({
			target: a,
			form: b,
			instance: O,
			options: _,
			unbinds: f
		})), b && (nr(a, b, g, d, E, f), E && (f.push(x(b, E, a)), f.push(gn(b))), await rr(a, g, b), b.dispatchEvent(new CustomEvent("formie:state:reset")), window.setTimeout(() => {
			b.dispatchEvent(new CustomEvent("formie:state:reset"));
		}, 350)), o.forEach((e) => {
			let t = d.on(`formie:stage:${e}:before`, async (t) => {
				y(a, `formie:stage:${e}:before`, t);
			}), n = d.on(`formie:stage:${e}:before`, async (t) => {
				let n = {
					core: "prepare",
					field: "prepare",
					address: "prepare",
					captcha: "challenge",
					payment: "payment"
				}, r = t;
				if (e === "prepare") try {
					D.assertReady();
				} catch {
					r.abort("A required form feature could not start. Reload the page or contact the site administrator.");
					return;
				}
				for (let t of D.instances) if (t.beforeSubmit && n[t.kind ?? "core"] === e) {
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
			}), r = d.on(`formie:stage:${e}:after`, async (t) => {
				y(a, `formie:stage:${e}:after`, t);
			});
			f.push(t, n, r);
		});
		let ie = d.on("formie:submit:before", async (e) => {
			y(a, "formie:submit:before", e);
		}), k = d.on("formie:submit:after", async (e) => {
			if (y(a, "formie:submit:after", e), !b) return;
			let t = e;
			if (t.code === "PREFLIGHT_COMPLETE") return;
			let n = {
				form: b,
				action: t.action ?? "submit",
				formData: new FormData(b)
			};
			for (let e of D.instances) await e.afterSubmit?.(n, t);
		}), ae = d.on("formie:submit:final:before", async (e) => {
			y(a, "formie:submit:final:before", e);
		}), oe = d.on("formie:submit:final:after", async (e) => {
			y(a, "formie:submit:final:after", e);
		});
		return f.push(ie, k, ae, oe), t.set(a, {
			options: g,
			bus: d,
			form: b,
			validator: E,
			modules: D,
			unbinds: f,
			instance: O
		}), y(a, "formie:mount:after", {
			id: p,
			mode: g.mode
		}), b instanceof HTMLFormElement && m(b), K.log("Mount complete.", {
			id: p,
			target: Q(a),
			mode: g.mode
		}), O;
	}, l = (e, n) => {
		if (!n.autoVisible || Zn(e) || typeof IntersectionObserver > "u") return c(e, n);
		if (t.has(e)) return Promise.resolve(t.get(e)?.instance || null);
		if (i.has(e)) return K.log("Mount deferred (already waiting visibility).", { target: Q(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((t) => {
			t.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), K.log("Visibility reached, proceeding mount.", { target: Q(e) }), c(e, {
				...n,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return r.observe(e), i.set(e, () => {
			r.disconnect();
		}), K.log("Mount deferred until visible.", { target: Q(e) }), Promise.resolve(null);
	};
	return {
		mount: c,
		unmount: s,
		update: async (e, n) => {
			let r = t.get(e);
			if (!r) return c(e, {
				...J(e),
				...n,
				mode: n.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...n
			};
			let i = n.payload?.theme || r.options.payload?.theme || Xn(e, r.form)?.theme, a = re(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && y(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => t.get(e)?.instance || null,
		refreshForCache: async (e) => {
			An("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let n = null;
			if (n = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !n) {
				K.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = t.get(n), i = kn(n), a = r?.options || J(n);
			if (!i) {
				K.warn("refreshForCache found no form element for target.", { target: Q(n) });
				return;
			}
			let o = a.formHandle || n.dataset.formieHandle || i.dataset.formieHandle, s = Pn(a, n), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				K.warn("refreshForCache found no form handle for target.", { target: Q(n) });
				return;
			}
			let l = await Ke(s, o, c, a, i?.querySelector("input[name=\"requestToken\"]")?.value);
			l && ($n(i, l), y(n, "formie:refresh-tokens:after", l));
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
			let n = e || document;
			K.log("Observer started.", { scope: n === document ? "document" : n });
			let r = new MutationObserver((e) => {
				e.forEach((e) => {
					e.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(G) && (K.log("Observer detected new root.", { target: Q(e) }), l(e, J(e))), e.querySelectorAll(G).forEach((e) => {
							K.log("Observer detected new nested root.", { target: Q(e) }), l(e, J(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (t.has(e) && (K.log("Observer detected removed root.", { target: Q(e) }), s(e)), e.querySelectorAll(G).forEach((e) => {
							t.has(e) && (K.log("Observer detected removed nested root.", { target: Q(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(n, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), K.log("Observer stopped."), i.forEach((e, t) => {
					Qn(t, n) && (e(), i.delete(t));
				});
				let e = [];
				n instanceof Element && n.matches(G) && e.push(n), n.querySelectorAll(G).forEach((t) => {
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
var ar = T("general", "module-hydrator");
async function or(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.surface ?? "cp-edit", i = e.modules ?? {
		contractVersion: 2,
		surface: r,
		entries: []
	}, a = e.registry ?? new B(), o = new Rt(), s = await Xt(i, {
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
	return ar.log("Hydrated module manifest.", {
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
function $(e) {
	return e instanceof Element;
}
function sr(e) {
	return e.ok;
}
function cr(e) {
	return typeof e == "string" ? `selector "${e}"` : $(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function lr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) $(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function ur(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : $(e) ? [e] : lr(e);
}
function dr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function fr(e) {
	let t = ur(e);
	return t.length > 0 || typeof e != "string" ? t : (await dr(), ur(e));
}
function pr(e) {
	return typeof e == "string" ? document : $(e) ? e.getRootNode() : document;
}
function mr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function hr(e, t, n, r) {
	let i = [], a = mr(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), c = [];
		if (e.onReady?.(s), c.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), sr(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
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
async function gr(e) {
	let t = e.client ?? ir(), n = /* @__PURE__ */ new Map(), r = await fr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${cr(e.element)}.`);
	await hr(e, t, n, r);
	let i = e.observe ? t.observe(pr(e.element)) : null;
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
			let r = ur(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : hr(e, t, n, r);
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
var _r = {
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
async function vr(t, n, r = zt) {
	for (let [e, t] of Object.entries(_r)) {
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
	let o = n.subscribe(a), s = await or({
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
export { l as FORMIE_HTML_EVENT_NAMES, dn as FormieValidator, xe as LEGACY_FORMIE_DOM_EVENT_BRIDGES, Se as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, B as ModuleRegistry, Oe as bindLegacyDomEventCompatibility, ke as bindLegacyValidatorCompatibility, ve as buildFieldValueRegistry, zt as clientRenderedModuleRegistry, T as createDebug, ir as createFormieClient, ne as debugLog, w as debugWarn, be as defineAddressModule, fe as defineCaptchaModule, de as definePassiveCaptchaModule, S as definePaymentModule, he as fieldKeyToInputName, gr as formie, c as getFieldModuleEventName, ue as getFormieTranslations, s as getGlobalModuleLifecycleEventName, i as getScopedModuleLifecycleEventName, or as hydrateFormieModules, pe as inputNameToFieldKey, te as isFormieDebugEnabled, ce as mergeFormieTranslations, vr as mountClientRenderedModules, me as normalizeFieldKey, u as normalizeFormieEventName, ge as parseFieldReference, ye as resolveFieldReferenceFromFormData, _e as resolveFieldReferenceLive, Ce as resolveLegacyCompatibilityOptions, ee as setFormieDebugEnabled, le as setFormieTranslations, se as t, a as toDomEventName, oe as translate };
