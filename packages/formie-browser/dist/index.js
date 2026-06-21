import { c as e, d as t, l as n, o as r, r as i, t as a, u as o } from "./chunks/event-names-BCI2FLD8.js";
import { a as s, c, d as l, f as u, i as d, l as f, n as p, o as m, p as h, r as g, s as _, t as v, u as y } from "./chunks/api-DslEDLxm.js";
import { a as b, i as x, n as S, r as ee, t as C } from "./chunks/debug-BV0DvdHx.js";
import { n as te, r as w, t as T } from "./chunks/theme-classes-Tv7q7ToE.js";
import { t as E } from "./chunks/http-D-JExro7.js";
import { a as D, i as O, n as ne, r as re, t as ie } from "./chunks/i18n-BY1ds1BL.js";
import { n as ae, t as oe } from "./chunks/api-BUb6P-xu.js";
import { n as se, r as ce, t as le } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as ue, n as de, r as fe, t as pe } from "./chunks/field-references.resolver-CHwn0G0L.js";
import { t as me } from "./chunks/api-sPqGbOww.js";
//#region src/js/compatibility/event-map.ts
var he = [
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
], ge = [
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
function _e(e) {
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
function ve(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function ye(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent === "onFormieSubmitError" ? n?.ok === !1 : !0;
}
function be(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function xe(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? be(r, t) : t;
}
function Se({ target: e, form: n, instance: r, options: i, unbinds: a }) {
	i.legacyDomEvents && he.forEach((i) => {
		let o = (t) => {
			!(t instanceof CustomEvent) || !ye(i, t.detail) || ve(i.target === "document" ? document : n, i.legacyEvent, xe(i, t.detail, e, n, r));
		};
		e.addEventListener(t(i.canonicalEvent), o), a.push(() => {
			e.removeEventListener(t(i.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function k(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function A(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function Ce({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	k(document, "formieValidatorInitialized", c);
	let l = (e) => {
		!(e instanceof CustomEvent) || !A(e.detail, a) || k(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		!(n instanceof CustomEvent) || !A(n.detail, a) || !(n.target instanceof Element) || t.contains(n.target) && k(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		!(n instanceof CustomEvent) || !A(n.detail, a) || !(n.target instanceof Element) || t.contains(n.target) && k(n.target, "formieValidatorClearError", {
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
function we(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function Te(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Ee(e) {
	return e === "off" ? null : e;
}
function De(e, t) {
	if (t) {
		e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true");
		return;
	}
	e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic");
}
//#endregion
//#region src/js/core/validation-focus.ts
function Oe(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function ke(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" ? !0 : e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Ae(e) {
	return e.querySelector("[aria-invalid=\"true\"]") || e.querySelector("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])");
}
function je(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function Me(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t;
		if (!(n.getAttribute("data-formie-field-has-error") === "true" || n.querySelector("[data-formie-field-error]") !== null)) return;
		n.setAttribute("data-formie-field-has-error", "true"), T(n, e, "fieldLayoutError");
		let r = n.querySelector("[data-formie-field-errors]")?.id || "", i = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), T(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), r && Oe(n, r), i && n.setAttribute("aria-errormessage", i);
		});
	});
}
function Ne(e) {
	return !!ke(e) || !!je(e);
}
function Pe(e) {
	let t = ke(e);
	if (t) {
		let e = Ae(t);
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
	let n = je(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/events/event-bus.ts
var Fe = class {
	constructor() {
		this.listeners = /* @__PURE__ */ new Map();
	}
	on(e, t) {
		return this.listeners.has(e) || this.listeners.set(e, /* @__PURE__ */ new Set()), this.listeners.get(e)?.add(t), () => {
			this.listeners.get(e)?.delete(t);
		};
	}
	async emit(e, t) {
		let n = this.listeners.get(e);
		if (!(!n || n.size === 0)) for (let e of n) await e(t);
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
}, j = C("general", "transport");
function Ie(e) {
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
function Le(e, t = "", n = {}) {
	if (Array.isArray(e)) {
		let r = e.map((e) => typeof e == "string" ? e : String(e ?? "")).filter((e) => e.trim() !== "");
		return t && r.length && (n[t] = (n[t] || []).concat(r)), n;
	}
	return e && typeof e == "object" && Object.entries(e).forEach(([e, r]) => {
		Le(r, t ? `${t}.${e}` : e, n);
	}), n;
}
function Re(e, t) {
	let n = e.success === !0, r = e.keepSubmitLoading === !0, i = e.errors, a = Le(i || {}), o = a.form || [], s = {};
	Object.entries(a).forEach(([e, t]) => {
		if (e === "form") return;
		let n = e.split(".")[0];
		s[n] = (s[n] || []).concat(t);
	});
	let c = !n && o.length === 0 && Object.keys(s).length > 0 ? [t || "Submission failed."] : o, l = !n && r && c.length === 0 && Object.keys(s).length === 0;
	return {
		ok: n,
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
async function ze(e, t, n = {}) {
	let r = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	j.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let i = await E(e, {
		method: "POST",
		body: r,
		headers: { "Content-Type": "application/json" }
	});
	return j.log("requestRender complete.", { hasHtml: !!i.html }), i;
}
async function Be(e, t, n = {}) {
	let r = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: Ie(n)
		}
	});
	j.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let i = await E(e, {
		method: "POST",
		body: r,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(i.errors) && i.errors.length > 0) throw Error(i.errors.map((e) => e.message || "Unknown GraphQL error").join("; "));
	if (!i.data?.formieHtmlForm) throw Error(`Form not found for handle "${t}".`);
	let a = i.data.formieHtmlForm;
	return j.log("requestGraphqlRender complete.", { hasHtml: !!a.html }), a;
}
async function M(e, t, n) {
	let r = new URL(e, window.location.origin);
	r.searchParams.set("handle", t), n && r.searchParams.set("renderId", n), j.log("requestRefreshTokens start.", {
		endpoint: r.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let i = await E(r.toString());
	return j.log("requestRefreshTokens complete.", { hasRefreshTokens: !!i.refreshTokens }), i.refreshTokens || i;
}
async function Ve(e, t, n) {
	let r = new URL(e, window.location.origin), i = new FormData();
	if (n && i.append("pageId", n), t) {
		[
			"handle",
			"renderId",
			"draftContextToken",
			"draftContext",
			"continuationToken"
		].forEach((e) => {
			let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
			n && i.append(e, n);
		});
		let e = t.querySelector("input[name=\"CRAFT_CSRF_TOKEN\"]")?.value?.trim();
		e && i.append("CRAFT_CSRF_TOKEN", e);
	}
	j.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await E(r.toString(), {
		method: "POST",
		body: i
	});
	return j.log("requestSetPage complete.", a), a;
}
function He(e, t) {
	let n = new URL(e, window.location.origin), r = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && r.append(e, n);
	});
	let i = t.querySelector("input[name=\"CRAFT_CSRF_TOKEN\"]")?.value?.trim();
	i && r.append("CRAFT_CSRF_TOKEN", i), j.log("clearSubmissionOnUnload start.", { requestUrl: n.toString() });
	try {
		if (typeof navigator.sendBeacon == "function" && navigator.sendBeacon(n.toString(), r)) return;
	} catch {}
	fetch(n.toString(), {
		method: "POST",
		body: r,
		credentials: "include",
		keepalive: !0,
		headers: { Accept: "application/json" }
	});
}
async function Ue(e, t) {
	let n = (e.getAttribute("method") || "POST").toUpperCase(), r = e.getAttribute("action") || window.location.href, i = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	j.log("submitForm start.", {
		method: n,
		action: r,
		submitAction: t.get("submitAction")
	});
	let a = await fetch(r, {
		method: n,
		body: t,
		credentials: "include",
		headers: { Accept: "application/json" }
	}), o = a.headers.get("content-type") || "";
	if (!o.includes("application/json")) return a.ok ? (j.log("submitForm non-JSON success response.", {
		status: a.status,
		contentType: o
	}), {
		ok: !0,
		message: "Submission completed."
	}) : (j.warn("submitForm non-JSON HTTP error.", {
		status: a.status,
		contentType: o
	}), {
		ok: !1,
		code: "HTTP_ERROR",
		message: `Request failed (${a.status}).`,
		formErrors: [`Request failed (${a.status}).`]
	});
	let s = Re(await a.json(), i);
	return j.log("submitForm JSON response normalized.", {
		ok: s.ok,
		code: s.code,
		hasRedirect: !!s.redirect?.url,
		hasSubmitData: Array.isArray(s.submitData) && s.submitData.length > 0
	}), s;
}
//#endregion
//#region src/js/submit/pipeline.ts
var We = [
	"prepare",
	"normalize",
	"validate",
	"screen",
	"authorize",
	"dispatch",
	"finalize"
], Ge = [
	"prepare",
	"normalize",
	"validate",
	"screen",
	"authorize"
], N = C("general", "pipeline");
function Ke(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function qe(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function Je(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function Ye(e, t) {
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
function Xe(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = qe(t) ? t : null;
		!n || n.closest("[data-formie-page]") || Je(n) && Ye(e, n);
	});
}
function Ze(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = qe(t) ? t : null;
		!r || !r.name || r.disabled || r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), Je(r) && Ye(e, r));
	}), n;
}
function Qe(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function $e(e, t) {
	let n = c(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return Xe(i, e), Qe(i, Ze(i, r)), i.set("submitAction", t), i;
}
function et(e, t) {
	if (t !== "submit") return !1;
	let n = c(e);
	return n.length ? (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1] : !0;
}
async function tt(e, t, n, r = {}) {
	N.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = et(e, t), c = {
		form: e,
		action: t,
		formData: $e(e, t),
		abort: (e) => {
			i = !0, a = e, N.warn("Pipeline aborted.", { reason: e });
		},
		isAborted: () => i,
		abortReason: () => a
	}, l = {
		prepare: async (e) => {
			let t = e.form.querySelector("input[name=\"submitAction\"]");
			return t && (t.value = e.action), e.formData.set("submitAction", e.action), null;
		},
		normalize: async () => null,
		validate: async (e) => {
			if (e.action !== "submit" || r.validateOnSubmit === !1) return null;
			if (r.validator) {
				let { scope: t, final: n } = f(e.form), i = r.validator.submit(n ? e.form : t, { final: n });
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
		screen: async () => null,
		authorize: async () => null,
		dispatch: async (e) => {
			e.formData = $e(e.form, e.action);
			let t = await Ue(e.form, e.formData);
			return o = t, t;
		},
		finalize: async (e) => (o && o.ok && o.redirect?.url && (o.redirect.target === "new-tab" ? window.open(o.redirect.url, "_blank") : window.location.href = o.redirect.url), null)
	};
	{
		let e = await n.emitSafe("formie:submit:before", c);
		e.failed.length > 0 && N.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && N.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let u = r.preflightOnly ? Ge : We;
	for (let e of u) {
		if (N.log("Stage start.", {
			stage: e,
			action: t
		}), i) return N.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ke(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && N.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ke(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && N.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && N.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return N.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await l[e](c);
		N.log("Stage runner complete.", {
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
			t.failed.length > 0 && N.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ke(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && N.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && N.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return N.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await n.emitSafe("formie:submit:after", r);
				t.failed.length > 0 && N.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await n.emitSafe("formie:submit:final:after", r);
				t.failed.length > 0 && N.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return N.warn("Pipeline short-circuited by failed stage.", {
				stage: e,
				code: r.code,
				message: r.message
			}), r;
		}
	}
	let d = o || {
		ok: !0,
		stage: r.preflightOnly ? "authorize" : "finalize",
		message: r.preflightOnly ? "Submission preflight completed." : "Submission completed."
	};
	{
		let e = await n.emitSafe("formie:submit:after", d);
		e.failed.length > 0 && N.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:after", d);
		e.failed.length > 0 && N.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return N.log("Pipeline completed.", {
		ok: d.ok,
		stage: d.stage,
		code: d.code
	}), d;
}
//#endregion
//#region src/js/core/field-error-container.ts
function nt(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function rt(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = nt(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/validation/rules/email.ts
var it = {
	rule: ({ input: e, getRule: t }) => !t("email") || !e.value || e.value.length < 1 ? !0 : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function at(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function ot(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? r.querySelector(e.config.fieldsSelector) : null;
}
//#endregion
//#region src/js/validation/rules.ts
var st = {
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
	email: it,
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
			return a !== null && o !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? r("{label} must be between {min} and {max}.", {
				label: t,
				min: a,
				max: o
			}) : a === null ? o === null ? e.getAttribute("data-formie-validation-number-message") ?? e.getAttribute("data-formie-pattern-number-message") ?? e.getAttribute("data-pattern-number-message") ?? r("{label} is not a valid number.", { label: t }) : e.getAttribute("data-formie-validation-number-max-message") ?? r("{label} must be no greater than {max}.", {
				label: t,
				max: o
			}) : e.getAttribute("data-formie-validation-number-min-message") ?? r("{label} must be no less than {min}.", {
				label: t,
				min: a
			});
		}
	},
	match: {
		rule: (e) => {
			let t = ot(e);
			return t ? t.value === e.input.value : !0;
		},
		message: (e) => {
			let t = ot(e)?.closest("[data-formie-field-handle]"), n = at(t);
			return e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, ct = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, P = C("general", "validator");
function F(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function lt(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => e !== t);
	if (r.length) {
		e.setAttribute("aria-describedby", r.join(" "));
		return;
	}
	e.removeAttribute("aria-describedby");
}
function ut(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function dt(e, t) {
	e.setAttribute("aria-errormessage", t);
}
function ft(e, t) {
	e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
var pt = class {
	constructor(e, t = {}) {
		this.errors = [], this.validators = {}, this.boundListeners = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.submitted = !1, this.initialValues = /* @__PURE__ */ new WeakMap(), this.form = e, this.onBlur = this.blurHandler.bind(this), this.onChange = this.changeHandler.bind(this), this.onInput = this.inputHandler.bind(this), this.config = {
			live: !1,
			errorAriaLive: "polite",
			errorMessage: "",
			fieldContainerErrorClass: [],
			inputErrorClass: [],
			messagesClass: [],
			messageClass: [],
			fieldsSelector: "input:not([type=\"hidden\"]):not([type=\"submit\"]):not([type=\"button\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])",
			patterns: ct,
			...t
		}, Object.entries(st).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		P.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, n("ready"), { validator: this });
	}
	inputs(e = null) {
		if (F(e)) return [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => F(e));
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
		}), P.log("Validation pass complete.", {
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
		let r = t.querySelector("[data-formie-field-errors]"), i = r?.id || "";
		t.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && (r.innerHTML = ""), t.querySelectorAll("input, select, textarea").forEach((e) => {
			let n = e;
			n.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && n.classList.remove(...this.config.inputErrorClass), n.removeAttribute("data-formie-input-has-error"), i && lt(n, i), t.querySelectorAll("[data-formie-field-error]").forEach((e) => {
				let t = e.id;
				t && ft(n, t);
			});
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, n("clear-error"), { validator: this }), y(this.form);
	}
	showError(e, t, r) {
		let i = e.closest("[data-formie-field-handle]");
		if (!i) return;
		let a = i.querySelector("[data-formie-field-errors]");
		a ||= rt(i, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && a.classList.add(...this.config.messagesClass), a.innerHTML = "";
		let o = i.getAttribute("data-formie-field-handle") || "field", s = `${o}-error`;
		a.id = a.id || `${o}-errors`, De(a, Te(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), c.setAttribute("role", "alert"), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = r, a.appendChild(c), i.setAttribute("data-formie-field-has-error", "true"), i.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), ut(t, a.id), dt(t, s);
		});
		for (let e = i; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, n("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: r
		}), y(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: O,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? O("{label} is invalid.", { label: r.label });
	}
	getErrors() {
		return this.errors;
	}
	getFieldErrors(e = this.errors) {
		let t = {};
		return e.forEach((e) => {
			!e.handle || t[e.handle]?.length || (t[e.handle] = [e.message]);
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
			return P.warn("Invalid validation rules payload.", { formId: this.form.id || null }), t;
		}
		return Array.isArray(n) && n.forEach((e) => {
			if (!e || typeof e != "object" || Array.isArray(e)) return;
			let n = e, r = typeof n.type == "string" ? n.type.trim() : "";
			r && (t[r] = n);
		}), t;
	}
	destroy() {
		P.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, n("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		return e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]") ? !1 : e.closest("[data-formie-page-hidden]") ? !!t.includeHiddenPages : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
	}
	blurHandler(e) {
		!(e.target instanceof HTMLElement) || !F(e.target) || !e.target.form?.isSameNode(this.form) || e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target));
	}
	changeHandler(e) {
		if (!(!(e.target instanceof HTMLElement) || !F(e.target) || !e.target.form?.isSameNode(this.form)) && !(e instanceof CustomEvent)) {
			if (e.target instanceof HTMLSelectElement) {
				this.activated.add(e.target), this.validate(e.target);
				return;
			}
			e.target instanceof HTMLInputElement && (e.target.type !== "file" && e.target.type !== "checkbox" && e.target.type !== "radio" || (this.activated.add(e.target), this.validate(e.target)));
		}
	}
	inputHandler(e) {
		!(e.target instanceof HTMLElement) || !F(e.target) || !e.target.form?.isSameNode(this.form) || e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target);
	}
	submit(e = null, { final: t = !1 } = {}) {
		return this.submitted = !0, P.log("Submit validation requested.", { final: t }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(e, { includeHiddenPages: t });
	}
	resetLiveState() {
		this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
	}
	addEventListeners() {
		this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, P.log("Event listeners attached."));
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, P.log("Event listeners removed.");
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
}, I = /* @__PURE__ */ new WeakMap();
function mt(e) {
	return (e.dataset.formieSubmitAction || "").trim();
}
function ht(e) {
	return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function gt(e) {
	return (e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function _t(e) {
	let t = (e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function vt(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function yt(e) {
	let t = I.get(e);
	typeof t == "number" && (window.clearTimeout(t), I.delete(e));
}
function bt(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function xt(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function St(e, t) {
	return t === "bottom-form" ? xt(e) : bt(e);
}
function Ct(e, t) {
	return t === "top-form" ? bt(e) : t === "bottom-form" && !vt(e) ? xt(e) : e;
}
function wt(e) {
	let t = ht(e), n = St(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), T(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function Tt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), T(n, e, "message", "messageError"), n.setAttribute("role", "alert"), De(n, Ee(we(e))), n;
}
function Et(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = Ct(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), T(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function Dt(e) {
	return rt(e, (t) => {
		T(t, e, "fieldErrors");
	});
}
function Ot(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => e !== t).join(" ").trim();
	if (r) {
		e.setAttribute("aria-describedby", r);
		return;
	}
	e.removeAttribute("aria-describedby");
}
function kt(e, t) {
	e.setAttribute("aria-errormessage", t);
}
function At(e, t) {
	e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
function jt(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = r?.id || "", a = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		w(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), w(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), i && Ot(n, i), a.forEach((e) => {
				At(n, e);
			});
		});
	}), y(e);
}
function Mt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), w(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function Nt(e) {
	yt(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), w(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), mt(e) === "message" && vt(e) || s(e, !1);
}
function Pt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Ft(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function It(e, t) {
	let n = Ee(we(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = e.querySelector(`[data-formie-field-handle="${t}"]`);
		if (!i) return;
		let a = Dt(i), o = a.id && a.id.trim() ? a.id : `${t}-errors`;
		a.id = o, De(a, n), T(i, e, "fieldLayoutError"), i.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.setAttribute("role", "alert"), r.id = `${o}-${n + 1}`, T(r, e, "fieldError"), r.textContent = t, a.appendChild(r);
		});
		let s = a.querySelector("[data-formie-field-error]")?.id;
		i.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), T(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), Ft(n, o), s && kt(n, s);
			let r = i.querySelector("[data-formie-instructions]");
			r?.id && Ft(n, r.id);
		});
	}), y(e);
}
function Lt(e, t) {
	let n = wt(e), r = Tt(e, n);
	T(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), n.setAttribute("role", "alert"), T(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function Rt(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" ? !0 : mt(e) === "message" && gt(e) !== "";
}
function zt(e, t) {
	let n = gt(e);
	if (!n) return;
	let r = Et(e, n);
	T(r, e, "message", "messageSuccess"), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-success", "true"), r.setAttribute("role", "status"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true");
	let i = document.createElement("div");
	i.setAttribute("data-formie-success", "true"), T(i, e, "success"), i.innerHTML = t, r.appendChild(i), vt(e) && s(e, !0);
	let a = _t(e);
	if (a !== null) {
		let t = window.setTimeout(() => {
			I.delete(e), Nt(e);
		}, a);
		I.set(e, t);
	}
}
function L(e, t) {
	if (jt(e), Mt(e), Nt(e), Pt(e), t.ok) {
		Rt(e, t) && zt(e, t.message || "");
		return;
	}
	t.fieldErrors && It(e, t.fieldErrors), t.formErrors?.length ? Lt(e, t.formErrors) : !t.fieldErrors && t.message && Lt(e, [t.message]), Pe(e);
}
//#endregion
//#region src/js/core/submit-flow.ts
var Bt = C("general", "submit-flow");
function Vt(e) {
	return !(!e.ok && e.stage === "validate");
}
function Ht(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function Ut(e) {
	jt(e), Mt(e), Nt(e), Pt(e);
}
async function Wt(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: f } = e;
	Ut(r), m(r, c || null);
	let p = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), p = await tt(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), L(r, p), g(r, p, s), Vt(p) && await u(p), f(p);
	} catch (e) {
		p = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, L(r, p), f(p), Bt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		Ht(p) || d(r);
	}
	return p;
}
//#endregion
//#region src/js/modules/registry.ts
var R = class {
	constructor() {
		this.modules = /* @__PURE__ */ new Map();
	}
	register(e, t = {}) {
		let n = this.modules.get(e.id);
		return n === e ? !0 : n && !t.replace ? (console.warn(`[formie] Module "${e.id}" is already registered. Pass { replace: true } to override the existing definition.`), !1) : (this.modules.set(e.id, e), !0);
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
}, Gt = {
	"address-finder": () => import("./chunks/address-finder-k7_BXaRe.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-BH3Zhi06.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-CJPzBmSm.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BGHklIZ-.js").then((e) => e.placeKitModule)
}, Kt = {
	"captcha-eu": () => import("./chunks/captcha-eu-CGm8Lbrj.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-C_2reOXr.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-CLSAbkqx.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-CGLAmgjO.js").then((e) => e.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-XPId5-bS.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-Cy-0PEPc.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-BwRH540O.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-No4Ykrhk.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-DKx98oon.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-DJakPzQD.js").then((e) => e.turnstileModule)
}, qt = {
	calculations: () => import("./chunks/calculations-CeDgB9jt.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-DQ0H67Tj.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-C0EuExSI.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-B6_nzpeE.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-BsaSirEZ.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-CJ1-FjEM.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-DYW_pBid.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-5CA_-zGb.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-rqfZNqlH.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-W-QDgxKD.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-D7_Ch-QN.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-D3ayr60L.js").then((e) => e.phoneCountryModule),
	"address-country": () => import("./chunks/address-country-Rx6GQTND.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-BwE01m2_.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-BVnPUqTj.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-Bg_--4TG.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-xz4QO7CR.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-Dks0gXS3.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-DkE6bR7B.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-DN6x4IZM.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-BnsNL9U5.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-CAjCJHrL.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-DbgEFHLO.js").then((e) => e.textLimitModule)
}, Jt = {
	bpoint: () => import("./chunks/bpoint-DmFR2OLY.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-m7DYJywD.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-BZCDj3CL.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-BEakK0Nv.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-f3Wxmwo4.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-DN5dea3Z.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-CzwMW1X3.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-BpJUvnGV.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-C9IRy3e0.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-B3dzIg6E.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-QwkUA5gZ.js").then((e) => e.stripeModule)
}, Yt = {
	...qt,
	...Gt,
	...Kt,
	...Jt
}, z = /* @__PURE__ */ new Map(), B = C("general", "loader"), Xt = Function("src", "return import(src);");
async function V(t, n, i, a) {
	await t(r(i), a), await t(e(n, i), a);
}
function Zt(e) {
	return !!e && typeof e == "object" && typeof e.id == "string" && typeof e.setup == "function" && typeof e.match == "function";
}
async function Qt(e, t) {
	let n = Yt[e];
	return n ? (z.has(e) || z.set(e, (async () => {
		try {
			let e = await n();
			return Zt(e) ? (t.registry.register(e), e) : null;
		} catch (t) {
			return console.error("[formie] Failed to load builtin module:", e, t), B.warn("Failed loading builtin module.", {
				moduleId: e,
				error: t
			}), null;
		}
	})()), z.get(e) || null) : null;
}
async function $t(e) {
	try {
		let t = await Xt(e), n = t?.default || t?.formieModule || null;
		return Zt(n) ? n : null;
	} catch (t) {
		return console.error("[formie] Failed to load module from src:", e, t), B.warn("Failed loading module from src.", {
			src: e,
			error: t
		}), null;
	}
}
async function en(e, t) {
	let n = t.registry.get(e.id);
	if (n) return n;
	let r = await Qt(e.id, t);
	if (r) return r;
	if (e.src) {
		let n = await $t(e.src);
		if (n) return t.registry.register(n), n;
	}
	return null;
}
function tn(e) {
	return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : e.replace(/["\\]/g, "\\$&");
}
function H(e, t) {
	return e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function nn(e, t) {
	let n = t.setupContext.root, r = t.setupContext.form, i = e.targetType, a = e.targetId;
	return i === "selector" ? H(n, a).map((e) => ({
		scope: i,
		element: e
	})) : i === "field" ? H(n, `[data-formie-field-handle="${tn(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : i === "page" ? H(n, `[data-formie-page-id="${tn(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : i === "button" ? H(n, `[data-formie-action="${tn(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : [{
		scope: "form",
		element: r || n
	}];
}
function rn(e, t) {
	return (e.targets && e.targets.length > 0 ? e.targets : [{
		targetType: "form",
		targetId: "form"
	}]).flatMap((e) => nn(e, t));
}
async function an(e, t) {
	let n = [];
	B.log("Loading module manifest.", { manifestCount: e.length });
	for (let r of e) {
		let e = await en(r, t);
		if (!e) {
			B.warn("Skipping manifest item (definition not resolved).", {
				moduleId: r.id,
				src: r.src
			});
			continue;
		}
		let i = rn(r, t);
		B.log("Resolved module targets.", {
			moduleId: e.id,
			targets: r.targets || [],
			targetCount: i.length
		}), i.length === 0 && e.kind === "address" && console.warn(`[formie] Address module "${r.id}" skipped: no target element found for fieldHandle="${r.targets?.find((e) => e.targetType === "field")?.targetId ?? "?"}". Check that the Address field exists in the rendered form.`);
		for (let a of i) {
			let i = {
				...t.matchContext,
				target: a.element,
				scope: a.scope,
				manifestItem: r
			};
			if (!e.match(i)) {
				e.kind === "address" && console.warn(`[formie] Address module "${e.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`), B.log("Module target did not match predicate.", {
					moduleId: e.id,
					scope: a.scope
				});
				continue;
			}
			let o = r.config || t.setupContext.options, s = e.id, c = {
				moduleId: e.id,
				moduleKind: e.kind,
				target: a.element,
				scope: a.scope,
				options: o,
				manifestItem: r
			};
			await V(t.setupContext.emit, s, "before-setup", c);
			let l = null;
			try {
				let n = await e.setup({
					...t.setupContext,
					target: a.element,
					scope: a.scope,
					options: o
				});
				n && (l = n);
			} catch (t) {
				console.error(`[formie] Module "${e.id}" setup failed:`, t), B.warn("Module setup failed.", {
					moduleId: e.id,
					scope: a.scope,
					error: t
				});
			}
			await V(t.setupContext.emit, s, "after-setup", {
				...c,
				instanceCreated: !!l
			}), l && (B.log("Module instance created.", {
				moduleId: e.id,
				scope: a.scope
			}), n.push({
				...l,
				destroy: async () => {
					B.log("Destroying module instance.", {
						moduleId: e.id,
						scope: a.scope
					}), await V(t.setupContext.emit, s, "before-destroy", c), await l.destroy(), await V(t.setupContext.emit, s, "after-destroy", c), B.log("Module instance destroyed.", {
						moduleId: e.id,
						scope: a.scope
					});
				}
			}));
		}
	}
	return B.log("Module manifest processing complete.", { instanceCount: n.length }), n;
}
//#endregion
//#region src/js/utils/form-started-at.ts
var on = "formie:formStartedAt:";
function sn(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${on}${n}` : null, i = r ? sessionStorage.getItem(r) : null;
	i || (i = String(Date.now()), r && sessionStorage.setItem(r, i)), t.value = i;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var cn = new Set([
	"CRAFT_CSRF_TOKEN",
	"action",
	"redirect",
	"requestToken",
	"renderId",
	"formStartedAt",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"continuationToken"
]);
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
function dn(e) {
	if (!e) return !1;
	let t = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !cn.has(t);
}
function fn(e) {
	return un(Array.from(new FormData(e).entries()).filter(([e]) => dn(String(e || ""))));
}
function pn(e, t = {}) {
	let n = null, r = !1, i = !1, a = null, o = null, s = null, c = () => {
		a !== null && (window.cancelAnimationFrame(a), a = null), o !== null && (window.clearTimeout(o), o = null), s !== null && (window.clearTimeout(s), s = null);
	}, l = () => r ? (i = fn(e) !== n, i) : !1, u = () => {
		n = fn(e), r = !0, i = !1;
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
		t.shouldWarn && !t.shouldWarn() || l() && (e.preventDefault(), e.returnValue = "");
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
//#region src/js/validation/enter-key-guard.ts
function mn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function hn(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !mn(e));
}
function gn(e) {
	let { final: t } = f(e);
	return "submit";
}
function _n(e) {
	return !hn(e, gn(e));
}
function vn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || _n(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var U = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", yn = 300, bn = "/actions/formie/server/forms/render", xn = "/api", Sn = "/actions/formie/server/forms/refresh-tokens", Cn = "/actions/formie/server/submissions/submit", wn = "/actions/formie/server/submissions/set-page", Tn = "/actions/formie/server/submissions/clear-submission", En = "/actions/formie/file-upload/hydrate", W = C("general", "client"), Dn = /* @__PURE__ */ new Set();
function G(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return !(n === "false" || n === "0" || n === "off");
}
function On(e) {
	return e.formieRefreshTokens != null && e.formieRefreshTokens !== "" ? G(e.formieRefreshTokens, !1) : e.formieStaticCache != null && e.formieStaticCache !== "" ? G(e.formieStaticCache, !1) : !1;
}
function K(e) {
	let t = e instanceof HTMLElement ? e.dataset : {};
	return {
		mode: "server-rendered",
		transport: t.formieTransport || "rest",
		formHandle: t.formieHandle,
		endpoint: t.formieEndpoint,
		staticCache: On(t),
		autoVisible: G(t.formieAutoVisible, !0),
		compatibility: G(t.formieCompatibility, !1)
	};
}
function q(e) {
	return e || "server-rendered";
}
function J(e) {
	return e || "rest";
}
function Y(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function kn(e, t) {
	Dn.has(e) || (Dn.add(e), W.warn(t));
}
function An(e, t) {
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
	return n ? n.includes("/actions/") ? n : An(t, n) : t;
}
function jn(e, t) {
	return X(e.endpoint || t.dataset.formieEndpoint, bn);
}
function Mn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : An(xn, n) : xn;
}
function Nn(e, t) {
	return X(t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint, Sn);
}
function Pn(e, t) {
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
function Fn(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = X(r, Cn), a = t.getAttribute("action");
	t.setAttribute("action", Pn(a, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href"), n = X(r, wn);
		e.setAttribute("href", Pn(t, n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(r, En));
	});
}
function In(e, t) {
	if (e === "graphql" && t !== "server-rendered") throw Error(`Formie ${t} mode does not support GraphQL transport yet.`);
}
function Ln(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Rn(e) {
	return G(e.dataset.formieAutomaticSubmissionState, !0);
}
function zn(e, t, n) {
	return X(n.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint, Tn);
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
	return Math.max(0, qn(e.dataset.formieSubmitDelay, yn));
}
function Z(e) {
	return Ln(e.dataset.formieValidationOnSubmit);
}
async function Yn(e) {
	let t = Jn(e);
	t < 1 || await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Xn(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function Zn(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Xn(n, "data-formie-modules"), i = Xn(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function Qn(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function $n(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function Q(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function er(e, t) {
	if (t) {
		if (t.csrf?.param && t.csrf?.token) {
			let n = e.querySelector(`input[name="${t.csrf.param}"]`);
			n && (n.value = t.csrf.token);
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
async function tr(e, t) {
	let n = q(t.mode), r = J(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	In(r, n);
	let i = !!Y(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? Mn(t, e) : jn(t, e), c = r === "graphql" ? await Be(s, a, o) : await ze(s, a, {
		...o,
		endpoint: s
	});
	return c?.html && (e.innerHTML = c.html), c;
}
async function nr(e, t, n) {
	if (t.refreshTokens === !1) return;
	In(J(t.transport), q(t.mode));
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await M(Nn(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0);
	er(n, i), h(e, "formie:refresh-tokens:refreshed", i);
}
function rr(e, t, n, r, i, a) {
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = zn(n, e, t), c = !1, u = t.querySelectorAll("[data-formie-action]"), g = (e) => {
		if (e) {
			t.setAttribute("data-formie-pending-action", e);
			return;
		}
		t.removeAttribute("data-formie-pending-action");
	};
	if (Bn(t)) {
		let n = pn(t, { shouldWarn: () => !Un(t) }), r = (e) => {
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
	if (u.forEach((e) => {
		let n = (e) => {
			let n = e.currentTarget.getAttribute("data-formie-action"), r = t.querySelector("input[name=\"submitAction\"]");
			g(n), n && r && (r.value = n);
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
			if (!(!i || !a)) {
				p(t, i), h(e, "formie:page:navigate", {
					pageId: i,
					href: a
				});
				try {
					h(e, "formie:page:navigate:after", {
						pageId: i,
						href: a,
						response: await Ve(a, t, i)
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), h(e, "formie:page:navigate:error", {
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
			e || Un(t) || Gn(t) || (e = !0, He(s, t));
		};
		window.addEventListener("pagehide", n), window.addEventListener("beforeunload", n), a.push(() => {
			window.removeEventListener("pagehide", n), window.removeEventListener("beforeunload", n);
		});
	}
	let _ = async (a) => {
		if (c) return;
		let s = o === "ajax";
		if (a.preventDefault(), t.getAttribute("data-formie-loading") === "true") {
			if (t.getAttribute("data-formie-internal-resubmit") !== "true") return;
			t.removeAttribute("data-formie-internal-resubmit");
		} else t.removeAttribute("data-formie-internal-resubmit");
		let u = a.submitter, p = u?.getAttribute("data-formie-action"), _ = t.getAttribute("data-formie-pending-action"), v = t.querySelector("input[name=\"submitAction\"]"), y = p || _ || v?.value || "submit", b = null, x = !1;
		try {
			if (s) b = await Wt({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Z(t),
				action: y,
				submitter: u,
				waitForSubmitDelay: Yn,
				onRefreshTokensAfterSubmit: async () => {
					await nr(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					h(e, "formie:submit:result", t);
				}
			});
			else {
				if (Ut(t), m(t, u), await Yn(t), b = await tt(t, y, r, {
					validator: i,
					validateOnSubmit: Z(t),
					preflightOnly: !0
				}), b.ok) {
					l(t, y), c = !0, Vn(t, "submit"), g(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, Hn(t), d(t), i && Z(t)) {
							let { scope: e, final: n } = f(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && L(t, {
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
				L(t, b), h(e, "formie:submit:result", b), Hn(t);
			}
		} catch (n) {
			c = !1, b = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, L(t, b), h(e, "formie:submit:result", b), Hn(t);
		} finally {
			g(null), !s && !x && !Ht(b) && d(t);
		}
	};
	t.addEventListener("submit", _), a.push(() => {
		t.removeEventListener("submit", _);
	});
}
async function ir(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	In(J(t.transport), q(t.mode));
	let r = t.formHandle || e.dataset.formieHandle, i = Nn(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await M(i, r, a);
	!o || !n || (er(n, o), h(e, "formie:refresh-tokens:after", o));
}
function ar() {
	let e = /* @__PURE__ */ new Map(), t = new R(), n = /* @__PURE__ */ new Map(), r = /* @__PURE__ */ new Map(), i = [
		"prepare",
		"normalize",
		"validate",
		"screen",
		"authorize",
		"dispatch",
		"finalize"
	], a = async (t) => {
		let i = r.get(t);
		if (i) {
			await i;
			return;
		}
		let a = (async () => {
			W.log("Unmount requested.", { target: Q(t) });
			let r = n.get(t);
			r && (r(), n.delete(t));
			let i = e.get(t);
			if (!i) {
				W.log("Unmount skipped (no mounted state).", { target: Q(t) });
				return;
			}
			h(t, "formie:unmount:before", { id: i.instance.id }), i.unbinds.forEach((e) => {
				e();
			}), i.unbinds = [], i.validator?.destroy(), i.validator = null;
			for (let e of i.modules) await e.destroy();
			i.modules = [], i.bus.clear(), e.delete(t), h(t, "formie:unmount:after", { id: i.instance.id }), W.log("Unmount complete.", {
				id: i.instance.id,
				target: Q(t)
			});
		})().finally(() => {
			r.delete(t);
		});
		r.set(t, a), await a;
	}, o = async (r, o) => {
		W.log("Mount requested.", {
			target: Q(r),
			mode: o.mode,
			autoVisible: o.autoVisible
		});
		let s = n.get(r);
		s && (s(), n.delete(r));
		let c = e.get(r);
		if (c) return W.log("Mount skipped (already mounted).", {
			id: c.instance.id,
			target: Q(r)
		}), c.instance;
		let l = new Fe(), d = [], f = r?.id || `formie-${e.size + 1}`, p = K(r), m = {
			...p,
			...o,
			mode: q(o.mode ?? p.mode),
			transport: J(o.transport ?? p.transport)
		}, g = _e(m.compatibility);
		if (m.mode !== "server-rendered" && !Y(r)) throw Error(`Formie ${m.mode} mode is not implemented yet in the browser client.`);
		let v = await tr(r, m), b = Y(r);
		m.staticCache = o.staticCache ?? On(b ? b.dataset : r.dataset);
		let x = Zn(r, b), S = v || x ? {
			...v || {},
			...x || {}
		} : null, ee = S?.theme, C = {}, w = (S?.modules || []).filter((e) => !!e?.id && !!e?.type);
		W.log("Resolved mount payload.", {
			target: Q(r),
			hasRenderPayload: !!v,
			hasEmbeddedPayload: !!x,
			moduleCount: w.length
		});
		let T = te(r, ee, b), E = b ? new pt(b, {
			live: Ln(b.dataset.formieValidationOnFocus),
			errorAriaLive: we(b),
			errorMessage: b.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: T.fieldLayoutError || [],
			inputErrorClass: T.fieldControlError || [],
			messagesClass: T.fieldErrors || [],
			messageClass: T.fieldError || []
		}) : null;
		if (b && E) {
			let e = b;
			e.formieValidation = E, C.validation = E;
			let t = {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			};
			h(b, "formie:validator:ready", t), h(r, "formie:validator:ready", t);
		}
		b && (sn(b), m.themeConfig && typeof m.themeConfig == "object" && b.setAttribute("data-formie-theme-config", JSON.stringify(m.themeConfig)), m.theme && m.theme !== "formie" && b.setAttribute("data-formie-frontend-theme", m.theme), (v || m.endpoint || r.dataset.formieEndpoint) && Fn(r, b, m), m.mode === "server-rendered" && Ne(b) && (Me(b), Pe(b)), y(b)), Object.keys(T).length && h(r, "formie:theme:applied", { hasClasses: !0 });
		let D = await an(w, {
			registry: t,
			matchContext: {
				root: r,
				form: b,
				mode: m.mode
			},
			setupContext: {
				formId: f,
				root: r,
				form: b,
				target: r,
				scope: "form",
				state: C,
				on: (e, t) => l.on(e, t),
				emit: (e, t) => (h(r, e, t), l.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && W.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		W.log("Module setup complete.", {
			target: Q(r),
			moduleInstances: D.length
		});
		let O = {
			id: f,
			root: r,
			submit: async (e = "submit") => {
				if (W.log("Submit requested.", {
					id: f,
					target: Q(r),
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
				let n = b.querySelector(`[data-formie-action="${e}"]`), i = await Wt({
					id: f,
					target: r,
					form: b,
					bus: l,
					validator: E,
					validateOnSubmit: Z(b),
					action: e,
					submitter: n,
					waitForSubmitDelay: Yn,
					onRefreshTokensAfterSubmit: async () => {
						await nr(r, m, b);
					},
					dispatchSubmitResult: (e) => {
						h(r, "formie:submit:result", e);
					}
				});
				return W.log("Submit completed.", {
					id: f,
					action: e,
					ok: i.ok,
					code: i.code,
					message: i.message
				}), i;
			},
			destroy: async () => {
				await a(r);
			},
			on: (e, t) => l.on(e, t)
		};
		b && (Ce({
			target: r,
			form: b,
			validatorDetail: E ? {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			} : null,
			options: g,
			unbinds: d
		}), Se({
			target: r,
			form: b,
			instance: O,
			options: g,
			unbinds: d
		})), b && (rr(r, b, m, l, E, d), E && (d.push(_(b, E, r)), d.push(vn(b))), await ir(r, m, b)), i.forEach((e) => {
			let t = l.on(`formie:stage:${e}:before`, async (t) => {
				h(r, `formie:stage:${e}:before`, t);
			}), n = l.on(`formie:stage:${e}:before`, async (e) => {
				for (let t of D) t.onBeforeStage && await t.onBeforeStage(e);
			}), i = l.on(`formie:stage:${e}:after`, async (t) => {
				h(r, `formie:stage:${e}:after`, t);
			}), a = l.on(`formie:stage:${e}:after`, async (e) => {
				let t = e;
				for (let e of D) e.onAfterStage && await e.onAfterStage(t, t.result);
			});
			d.push(t, n, i, a);
		});
		let ne = l.on("formie:submit:before", async (e) => {
			h(r, "formie:submit:before", e);
		}), re = l.on("formie:submit:after", async (e) => {
			h(r, "formie:submit:after", e);
		}), ie = l.on("formie:submit:final:before", async (e) => {
			h(r, "formie:submit:final:before", e);
		}), ae = l.on("formie:submit:final:after", async (e) => {
			h(r, "formie:submit:final:after", e);
		});
		return d.push(ne, re, ie, ae), e.set(r, {
			options: m,
			bus: l,
			form: b,
			validator: E,
			modules: D,
			unbinds: d,
			instance: O
		}), h(r, "formie:mount:after", {
			id: f,
			mode: m.mode
		}), b instanceof HTMLFormElement && u(b), W.log("Mount complete.", {
			id: f,
			target: Q(r),
			mode: m.mode
		}), O;
	}, s = (t, r) => {
		if (!r.autoVisible || Qn(t) || typeof IntersectionObserver > "u") return o(t, r);
		if (e.has(t)) return Promise.resolve(e.get(t)?.instance || null);
		if (n.has(t)) return W.log("Mount deferred (already waiting visibility).", { target: Q(t) }), Promise.resolve(null);
		let i = new IntersectionObserver((e) => {
			e.some((e) => e.target === t && e.isIntersecting) && (i.disconnect(), n.delete(t), W.log("Visibility reached, proceeding mount.", { target: Q(t) }), o(t, {
				...r,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return i.observe(t), n.set(t, () => {
			i.disconnect();
		}), W.log("Mount deferred until visible.", { target: Q(t) }), Promise.resolve(null);
	};
	return {
		mount: o,
		unmount: a,
		update: async (t, n) => {
			let r = e.get(t);
			if (!r) return o(t, {
				...K(t),
				...n,
				mode: n.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...n
			};
			let i = te(t, n.payload?.theme || r.options.payload?.theme || Zn(t, r.form)?.theme, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = i.fieldLayoutError || [], r.validator.config.inputErrorClass = i.fieldControlError || [], r.validator.config.messagesClass = i.fieldErrors || [], r.validator.config.messageClass = i.fieldError || []), Object.keys(i).length && h(t, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (t) => e.get(t)?.instance || null,
		refreshForCache: async (t) => {
			kn("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let n = null;
			if (n = typeof t == "string" ? document.getElementById(t) || document.querySelector(`[data-formie-form-id="${t}"]`) : t, !n) {
				W.warn("refreshForCache target not found.", { targetOrId: t });
				return;
			}
			let r = e.get(n), i = Y(n), a = r?.options || K(n);
			if (!i) {
				W.warn("refreshForCache found no form element for target.", { target: Q(n) });
				return;
			}
			let o = a.formHandle || n.dataset.formieHandle || i.dataset.formieHandle, s = Nn(a, n), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				W.warn("refreshForCache found no form handle for target.", { target: Q(n) });
				return;
			}
			let l = await M(s, o, c);
			l && (er(i, l), h(n, "formie:refresh-tokens:after", l));
		},
		registerModule: (e, n) => t.register(e, n),
		unregisterModule: (e) => {
			t.unregister(e);
		},
		getRegisteredModules: () => t.getAll(),
		scan: async (e) => {
			let t = e || document, n = Array.from(t.querySelectorAll(U));
			W.log("Scan started.", {
				scope: t === document ? "document" : t,
				targetCount: n.length
			});
			let r = (await Promise.all(n.map((e) => s(e, K(e))))).filter((e) => !!e);
			return W.log("Scan finished.", {
				mountedCount: r.length,
				deferredCount: n.length - r.length
			}), r;
		},
		observe: (t) => {
			if (typeof MutationObserver > "u") return () => {};
			let r = t || document;
			W.log("Observer started.", { scope: r === document ? "document" : r });
			let i = new MutationObserver((t) => {
				t.forEach((t) => {
					t.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(U) && (W.log("Observer detected new root.", { target: Q(e) }), s(e, K(e))), e.querySelectorAll(U).forEach((e) => {
							W.log("Observer detected new nested root.", { target: Q(e) }), s(e, K(e));
						}));
					}), t.removedNodes.forEach((t) => {
						t instanceof Element && (e.has(t) && (W.log("Observer detected removed root.", { target: Q(t) }), a(t)), t.querySelectorAll(U).forEach((t) => {
							e.has(t) && (W.log("Observer detected removed nested root.", { target: Q(t) }), a(t));
						}));
					});
				});
			});
			return i.observe(r, {
				childList: !0,
				subtree: !0
			}), () => {
				i.disconnect(), W.log("Observer stopped."), n.forEach((e, t) => {
					$n(t, r) && (e(), n.delete(t));
				});
				let t = [];
				r instanceof Element && r.matches(U) && t.push(r), r.querySelectorAll(U).forEach((e) => {
					t.push(e);
				}), t.forEach((t) => {
					e.has(t) && a(t);
				});
			};
		}
	};
}
//#endregion
//#region src/js/core/hydrate-modules.ts
var or = C("general", "module-hydrator");
async function sr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form")), r = e.modules ?? [], i = e.mode ?? "server-rendered", a = e.registry ?? new R(), o = new Fe(), s = await an(r, {
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
			emit: async (e, t) => {
				await o.emit(e, t);
			}
		},
		matchContext: {
			root: t,
			form: n,
			mode: i
		}
	});
	return or.log("Hydrated module manifest.", {
		moduleCount: r.length,
		instanceCount: s.length,
		mode: i
	}), {
		destroy: async () => {
			await cr(s), o.clear();
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
async function cr(e) {
	for (let t of e) try {
		await t.destroy();
	} catch (e) {
		console.error("[formie] Failed to destroy module instance.", e), or.warn("Failed destroying module instance.", { error: e });
	}
}
//#endregion
//#region src/js/core/formie.ts
function $(e) {
	return e instanceof Element;
}
function lr(e) {
	return e.ok;
}
function ur(e) {
	return typeof e == "string" ? `selector "${e}"` : $(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function dr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) !$(r) || t.has(r) || (t.add(r), n.push(r));
	return n;
}
function fr(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : $(e) ? [e] : dr(e);
}
function pr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function mr(e) {
	let t = fr(e);
	return t.length > 0 || typeof e != "string" ? t : (await pr(), fr(e));
}
function hr(e) {
	return typeof e == "string" ? document : $(e) ? e.getRootNode() : document;
}
function gr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function _r(e, t, n, r) {
	let i = [], o = gr(e);
	for (let s of r) {
		let r = n.get(s);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let c = await t.mount(s, o), l = [];
		if (e.onReady?.(c), l.push(c.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, c), lr(n) ? e.onSuccess?.(n, c) : e.onError?.(n, c);
		})), e.onEvent) for (let t of a) l.push(c.on(t, (n) => {
			e.onEvent?.({
				name: t,
				payload: n
			}, c);
		}));
		n.set(s, {
			instance: c,
			unsubs: l
		}), i.push(c);
	}
	return i;
}
async function vr(e) {
	let t = e.client ?? ar(), n = /* @__PURE__ */ new Map(), r = await mr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${ur(e.element)}.`);
	await _r(e, t, n, r);
	let i = e.observe ? t.observe(hr(e.element)) : null;
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
			let r = fr(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : _r(e, t, n, r);
		},
		async destroy() {
			i?.();
			let e = Array.from(n.entries());
			for (let [r, i] of e) i.unsubs.forEach((e) => e()), await t.unmount(r), n.delete(r);
		}
	};
}
//#endregion
export { a as FORMIE_HTML_EVENT_NAMES, pt as FormieValidator, he as LEGACY_FORMIE_DOM_EVENT_BRIDGES, ge as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, R as ModuleRegistry, Se as bindLegacyDomEventCompatibility, Ce as bindLegacyValidatorCompatibility, fe as buildFieldValueRegistry, C as createDebug, ar as createFormieClient, S as debugLog, ee as debugWarn, me as defineAddressModule, oe as defineCaptchaModule, ae as definePassiveCaptchaModule, v as definePaymentModule, le as fieldKeyToInputName, vr as formie, i as getFieldModuleEventName, ie as getFormieTranslations, r as getGlobalModuleLifecycleEventName, e as getScopedModuleLifecycleEventName, sr as hydrateFormieModules, se as inputNameToFieldKey, x as isFormieDebugEnabled, ne as mergeFormieTranslations, ce as normalizeFieldKey, o as normalizeFormieEventName, ue as parseFieldReference, pe as resolveFieldReferenceFromFormData, de as resolveFieldReferenceLive, _e as resolveLegacyCompatibilityOptions, b as setFormieDebugEnabled, re as setFormieTranslations, O as t, t as toDomEventName, D as translate };
