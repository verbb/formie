import { c as e, d as t, l as n, o as r, r as i, t as a, u as o } from "./chunks/event-names-BCI2FLD8.js";
import { a as s, i as c, n as l, r as u, t as d } from "./chunks/debug-BV0DvdHx.js";
import { a as f, c as p, d as ee, i as m, l as h, n as g, o as _, r as v, s as te, t as ne, u as y } from "./chunks/api-CmwLRq_n.js";
import { n as re, r as b, t as x } from "./chunks/theme-classes-Tv7q7ToE.js";
import { t as S } from "./chunks/http-D-JExro7.js";
import { a as C, i as w, n as ie, r as ae, t as oe } from "./chunks/i18n-BY1ds1BL.js";
import { n as se, t as ce } from "./chunks/api-DMK8NSUI.js";
import { n as le, r as ue, t as de } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as fe, n as pe, r as me, t as he } from "./chunks/field-references.resolver-CHwn0G0L.js";
import { t as ge } from "./chunks/api-sPqGbOww.js";
//#region src/js/compatibility/event-map.ts
var _e = [
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
], ve = [
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
function ye(e) {
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
function be(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function xe(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent === "onFormieSubmitError" ? n?.ok === !1 : !0;
}
function Se(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function Ce(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? Se(r, t) : t;
}
function we({ target: e, form: n, instance: r, options: i, unbinds: a }) {
	i.legacyDomEvents && _e.forEach((i) => {
		let o = (t) => {
			!(t instanceof CustomEvent) || !xe(i, t.detail) || be(i.target === "document" ? document : n, i.legacyEvent, Ce(i, t.detail, e, n, r));
		};
		e.addEventListener(t(i.canonicalEvent), o), a.push(() => {
			e.removeEventListener(t(i.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function T(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Te(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function Ee({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	T(document, "formieValidatorInitialized", c);
	let l = (e) => {
		!(e instanceof CustomEvent) || !Te(e.detail, a) || T(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		!(n instanceof CustomEvent) || !Te(n.detail, a) || !(n.target instanceof Element) || t.contains(n.target) && T(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		!(n instanceof CustomEvent) || !Te(n.detail, a) || !(n.target instanceof Element) || t.contains(n.target) && T(n.target, "formieValidatorClearError", {
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
//#region src/js/core/dom-events.ts
function E(e, n, r) {
	e.dispatchEvent(new CustomEvent(t(n), {
		bubbles: !0,
		detail: r
	}));
}
//#endregion
//#region src/js/core/validation-focus.ts
function De(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Oe(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" ? !0 : e.querySelector("[data-formie-field-error]") !== null) || null;
}
function ke(e) {
	return e.querySelector("[aria-invalid=\"true\"]") || e.querySelector("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])");
}
function Ae(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function je(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t;
		if (!(n.getAttribute("data-formie-field-has-error") === "true" || n.querySelector("[data-formie-field-error]") !== null)) return;
		n.setAttribute("data-formie-field-has-error", "true"), x(n, e, "fieldLayoutError");
		let r = n.querySelector("[data-formie-field-errors]")?.id || "", i = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), x(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), r && De(n, r), i && n.setAttribute("aria-errormessage", i);
		});
	});
}
function Me(e) {
	return !!Oe(e) || !!Ae(e);
}
function Ne(e) {
	let t = Oe(e);
	if (t) {
		let e = ke(t);
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
	let n = Ae(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/events/event-bus.ts
var Pe = class {
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
}, D = d("general", "transport");
function Fe(e) {
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
function Ie(e, t = "", n = {}) {
	if (Array.isArray(e)) {
		let r = e.map((e) => typeof e == "string" ? e : String(e ?? "")).filter((e) => e.trim() !== "");
		return t && r.length && (n[t] = (n[t] || []).concat(r)), n;
	}
	return e && typeof e == "object" && Object.entries(e).forEach(([e, r]) => {
		Ie(r, t ? `${t}.${e}` : e, n);
	}), n;
}
function Le(e, t) {
	let n = e.success === !0, r = e.keepSubmitLoading === !0, i = e.errors, a = Ie(i || {}), o = a.form || [], s = {};
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
		meta: e
	};
}
async function Re(e, t, n = {}) {
	let r = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	D.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let i = await S(e, {
		method: "POST",
		body: r,
		headers: { "Content-Type": "application/json" }
	});
	return D.log("requestRender complete.", { hasHtml: !!i.html }), i;
}
async function ze(e, t, n = {}) {
	let r = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: Fe(n)
		}
	});
	D.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let i = await S(e, {
		method: "POST",
		body: r,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(i.errors) && i.errors.length > 0) throw Error(i.errors.map((e) => e.message || "Unknown GraphQL error").join("; "));
	if (!i.data?.formieHtmlForm) throw Error(`Form not found for handle "${t}".`);
	let a = i.data.formieHtmlForm;
	return D.log("requestGraphqlRender complete.", { hasHtml: !!a.html }), a;
}
async function Be(e, t, n) {
	let r = new URL(e, window.location.origin);
	r.searchParams.set("handle", t), n && r.searchParams.set("renderId", n), D.log("requestRefreshTokens start.", {
		endpoint: r.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let i = await S(r.toString());
	return D.log("requestRefreshTokens complete.", { hasRefreshTokens: !!i.refreshTokens }), i.refreshTokens || i;
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
	D.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await S(r.toString(), {
		method: "POST",
		body: i
	});
	return D.log("requestSetPage complete.", a), a;
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
	i && r.append("CRAFT_CSRF_TOKEN", i), D.log("clearSubmissionOnUnload start.", { requestUrl: n.toString() });
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
	D.log("submitForm start.", {
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
	if (!o.includes("application/json")) return a.ok ? (D.log("submitForm non-JSON success response.", {
		status: a.status,
		contentType: o
	}), {
		ok: !0,
		message: "Submission completed."
	}) : (D.warn("submitForm non-JSON HTTP error.", {
		status: a.status,
		contentType: o
	}), {
		ok: !1,
		code: "HTTP_ERROR",
		message: `Request failed (${a.status}).`,
		formErrors: [`Request failed (${a.status}).`]
	});
	let s = Le(await a.json(), i);
	return D.log("submitForm JSON response normalized.", {
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
], O = d("general", "pipeline");
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
	let n = p(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	return Xe(i, e), Qe(i, Ze(i, r)), i.set("submitAction", t), i;
}
function et(e, t) {
	if (t !== "submit") return !1;
	let n = p(e);
	return n.length ? (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1] : !0;
}
async function tt(e, t, n, r = {}) {
	O.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = et(e, t), c = {
		form: e,
		action: t,
		formData: $e(e, t),
		abort: (e) => {
			i = !0, a = e, O.warn("Pipeline aborted.", { reason: e });
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
		e.failed.length > 0 && O.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && O.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let u = r.preflightOnly ? Ge : We;
	for (let e of u) {
		if (O.log("Stage start.", {
			stage: e,
			action: t
		}), i) return O.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ke(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && O.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ke(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && O.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && O.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return O.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await l[e](c);
		O.log("Stage runner complete.", {
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
			t.failed.length > 0 && O.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ke(e, a);
			{
				let r = await n.emitSafe("formie:submit:after", t);
				r.failed.length > 0 && O.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			if (s) {
				let r = await n.emitSafe("formie:submit:final:after", t);
				r.failed.length > 0 && O.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: r.failed.length
				});
			}
			return O.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await n.emitSafe("formie:submit:after", r);
				t.failed.length > 0 && O.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await n.emitSafe("formie:submit:final:after", r);
				t.failed.length > 0 && O.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return O.warn("Pipeline short-circuited by failed stage.", {
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
		e.failed.length > 0 && O.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:after", d);
		e.failed.length > 0 && O.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return O.log("Pipeline completed.", {
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
}, k = d("general", "validator");
function A(e) {
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
		k.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, n("ready"), { validator: this });
	}
	inputs(e = null) {
		if (A(e)) return [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => A(e));
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
		}), k.log("Validation pass complete.", {
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
		a.id = a.id || `${o}-errors`, a.setAttribute("aria-live", "polite"), a.setAttribute("aria-atomic", "true");
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
			t: w,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? w("{label} is invalid.", { label: r.label });
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
			return k.warn("Invalid validation rules payload.", { formId: this.form.id || null }), t;
		}
		return Array.isArray(n) && n.forEach((e) => {
			if (!e || typeof e != "object" || Array.isArray(e)) return;
			let n = e, r = typeof n.type == "string" ? n.type.trim() : "";
			r && (t[r] = n);
		}), t;
	}
	destroy() {
		k.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, n("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		return e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]") ? !1 : e.closest("[data-formie-page-hidden]") ? !!t.includeHiddenPages : !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
	}
	blurHandler(e) {
		!(e.target instanceof HTMLElement) || !A(e.target) || !e.target.form?.isSameNode(this.form) || e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target));
	}
	changeHandler(e) {
		if (!(!(e.target instanceof HTMLElement) || !A(e.target) || !e.target.form?.isSameNode(this.form)) && !(e instanceof CustomEvent)) {
			if (e.target instanceof HTMLSelectElement) {
				this.activated.add(e.target), this.validate(e.target);
				return;
			}
			e.target instanceof HTMLInputElement && (e.target.type !== "file" && e.target.type !== "checkbox" && e.target.type !== "radio" || (this.activated.add(e.target), this.validate(e.target)));
		}
	}
	inputHandler(e) {
		!(e.target instanceof HTMLElement) || !A(e.target) || !e.target.form?.isSameNode(this.form) || e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target);
	}
	submit(e = null, { final: t = !1 } = {}) {
		return this.submitted = !0, k.log("Submit validation requested.", { final: t }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(e, { includeHiddenPages: t });
	}
	resetLiveState() {
		this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
	}
	addEventListeners() {
		this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, k.log("Event listeners attached."));
	}
	removeEventListeners() {
		this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, k.log("Event listeners removed.");
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
}, j = /* @__PURE__ */ new WeakMap();
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
function M(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function vt(e) {
	let t = j.get(e);
	typeof t == "number" && (window.clearTimeout(t), j.delete(e));
}
function yt(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function bt(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function xt(e, t) {
	return t === "bottom-form" ? bt(e) : yt(e);
}
function St(e, t) {
	return t === "top-form" ? yt(e) : t === "bottom-form" && !M(e) ? bt(e) : e;
}
function Ct(e) {
	let t = ht(e), n = xt(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), x(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function wt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), x(n, e, "message", "messageError"), n.setAttribute("role", "alert"), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true"), n;
}
function Tt(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = St(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), x(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function Et(e) {
	return rt(e, (t) => {
		x(t, e, "fieldErrors");
	});
}
function Dt(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => e !== t).join(" ").trim();
	if (r) {
		e.setAttribute("aria-describedby", r);
		return;
	}
	e.removeAttribute("aria-describedby");
}
function Ot(e, t) {
	e.setAttribute("aria-errormessage", t);
}
function kt(e, t) {
	e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
function At(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = r?.id || "", a = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		b(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), b(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), i && Dt(n, i), a.forEach((e) => {
				kt(n, e);
			});
		});
	}), y(e);
}
function jt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), b(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function N(e) {
	vt(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), b(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), mt(e) === "message" && M(e) || f(e, !1);
}
function Mt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function Nt(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Pt(e, t) {
	Object.entries(t).forEach(([t, n]) => {
		let r = e.querySelector(`[data-formie-field-handle="${t}"]`);
		if (!r) return;
		let i = Et(r), a = i.id && i.id.trim() ? i.id : `${t}-errors`;
		i.id = a, i.setAttribute("aria-live", "polite"), i.setAttribute("aria-atomic", "true"), x(r, e, "fieldLayoutError"), r.setAttribute("data-formie-field-has-error", "true"), n.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.setAttribute("role", "alert"), r.id = `${a}-${n + 1}`, x(r, e, "fieldError"), r.textContent = t, i.appendChild(r);
		});
		let o = i.querySelector("[data-formie-field-error]")?.id;
		r.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), x(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), Nt(n, a), o && Ot(n, o);
			let i = r.querySelector("[data-formie-instructions]");
			i?.id && Nt(n, i.id);
		});
	}), y(e);
}
function Ft(e, t) {
	let n = Ct(e), r = wt(e, n);
	x(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), n.setAttribute("role", "alert"), x(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function It(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" ? !0 : mt(e) === "message" && gt(e) !== "";
}
function Lt(e, t) {
	let n = gt(e);
	if (!n) return;
	let r = Tt(e, n);
	x(r, e, "message", "messageSuccess"), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-success", "true"), r.setAttribute("role", "status"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true");
	let i = document.createElement("div");
	i.setAttribute("data-formie-success", "true"), x(i, e, "success"), i.innerHTML = t, r.appendChild(i), M(e) && f(e, !0);
	let a = _t(e);
	if (a !== null) {
		let t = window.setTimeout(() => {
			j.delete(e), N(e);
		}, a);
		j.set(e, t);
	}
}
function P(e, t) {
	if (At(e), jt(e), N(e), Mt(e), t.ok) {
		It(e, t) && Lt(e, t.message || "");
		return;
	}
	t.fieldErrors && Pt(e, t.fieldErrors), t.formErrors?.length ? Ft(e, t.formErrors) : !t.fieldErrors && t.message && Ft(e, [t.message]), Ne(e);
}
//#endregion
//#region src/js/core/submit-flow.ts
var Rt = d("general", "submit-flow");
function zt(e) {
	return !(!e.ok && e.stage === "validate");
}
function Bt(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function Vt(e) {
	At(e), jt(e), N(e), Mt(e);
}
async function Ht(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	Vt(r), _(r, c || null);
	let f = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), f = await tt(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), P(r, f), v(r, f, s), zt(f) && await u(f), d(f);
	} catch (e) {
		f = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, P(r, f), d(f), Rt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		Bt(f) || m(r);
	}
	return f;
}
//#endregion
//#region src/js/modules/registry.ts
var F = class {
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
}, Ut = {
	"address-finder": () => import("./chunks/address-finder-yXU5yMjG.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-OhKilPWE.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-Cp2zvwpE.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BGHklIZ-.js").then((e) => e.placeKitModule)
}, Wt = {
	"captcha-eu": () => import("./chunks/captcha-eu-CWJb64_j.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-ByXzENYZ.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-BPdlmhQu.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-CdCd9oEi.js").then((e) => e.hcaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-BSwi8IVX.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-CnDQmsKa.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-Cz5uIlp-.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-BtbbP111.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-vz1RnZ15.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-D_Pi1CKM.js").then((e) => e.turnstileModule)
}, Gt = {
	calculations: () => import("./chunks/calculations-HAZ8wUrT.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-DQ0H67Tj.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-D-2b42wn.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-BiA_uANV.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-BsaSirEZ.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-CJ1-FjEM.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-DYW_pBid.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-5CA_-zGb.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-D49m8-DR.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-BpqWSHmU.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-D7_Ch-QN.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-D3ayr60L.js").then((e) => e.phoneCountryModule),
	"address-country": () => import("./chunks/address-country-Rx6GQTND.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-BQ0xGV9n.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-B5leVxZU.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-D0sb8bmX.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-xz4QO7CR.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-D3AjxpYN.js").then((e) => e.summaryModule),
	table: () => import("./chunks/table-C-lkQN6I.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-nSATKyhp.js").then((e) => e.textLimitModule)
}, Kt = {
	bpoint: () => import("./chunks/bpoint-DSjqRv63.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-D3ctTbEk.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-BwASSYSK.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-COG-ko2D.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-CMH8PlRc.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-D2jsR5vh.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-DxF88RfC.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-b5pnwrA2.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-BJYu9DIR.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-BP8RWAKB.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-MihN63uC.js").then((e) => e.stripeModule)
}, qt = {
	...Gt,
	...Ut,
	...Wt,
	...Kt
}, I = /* @__PURE__ */ new Map(), L = d("general", "loader"), Jt = Function("src", "return import(src);");
async function R(t, n, i, a) {
	await t(r(i), a), await t(e(n, i), a);
}
function Yt(e) {
	return !!e && typeof e == "object" && typeof e.id == "string" && typeof e.setup == "function" && typeof e.match == "function";
}
async function Xt(e, t) {
	let n = qt[e];
	return n ? (I.has(e) || I.set(e, (async () => {
		try {
			let e = await n();
			return Yt(e) ? (t.registry.register(e), e) : null;
		} catch (t) {
			return console.error("[formie] Failed to load builtin module:", e, t), L.warn("Failed loading builtin module.", {
				moduleId: e,
				error: t
			}), null;
		}
	})()), I.get(e) || null) : null;
}
async function Zt(e) {
	try {
		let t = await Jt(e), n = t?.default || t?.formieModule || null;
		return Yt(n) ? n : null;
	} catch (t) {
		return console.error("[formie] Failed to load module from src:", e, t), L.warn("Failed loading module from src.", {
			src: e,
			error: t
		}), null;
	}
}
async function Qt(e, t) {
	let n = t.registry.get(e.id);
	if (n) return n;
	let r = await Xt(e.id, t);
	if (r) return r;
	if (e.src) {
		let n = await Zt(e.src);
		if (n) return t.registry.register(n), n;
	}
	return null;
}
function z(e) {
	return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : e.replace(/["\\]/g, "\\$&");
}
function B(e, t) {
	return e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function $t(e, t) {
	let n = t.setupContext.root, r = t.setupContext.form, i = e.targetType, a = e.targetId;
	return i === "selector" ? B(n, a).map((e) => ({
		scope: i,
		element: e
	})) : i === "field" ? B(n, `[data-formie-field-handle="${z(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : i === "page" ? B(n, `[data-formie-page-id="${z(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : i === "button" ? B(n, `[data-formie-action="${z(a)}"]`).map((e) => ({
		scope: i,
		element: e
	})) : [{
		scope: "form",
		element: r || n
	}];
}
function en(e, t) {
	return (e.targets && e.targets.length > 0 ? e.targets : [{
		targetType: "form",
		targetId: "form"
	}]).flatMap((e) => $t(e, t));
}
async function tn(e, t) {
	let n = [];
	L.log("Loading module manifest.", { manifestCount: e.length });
	for (let r of e) {
		let e = await Qt(r, t);
		if (!e) {
			L.warn("Skipping manifest item (definition not resolved).", {
				moduleId: r.id,
				src: r.src
			});
			continue;
		}
		let i = en(r, t);
		L.log("Resolved module targets.", {
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
				e.kind === "address" && console.warn(`[formie] Address module "${e.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`), L.log("Module target did not match predicate.", {
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
			await R(t.setupContext.emit, s, "before-setup", c);
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
				console.error(`[formie] Module "${e.id}" setup failed:`, t), L.warn("Module setup failed.", {
					moduleId: e.id,
					scope: a.scope,
					error: t
				});
			}
			await R(t.setupContext.emit, s, "after-setup", {
				...c,
				instanceCreated: !!l
			}), l && (L.log("Module instance created.", {
				moduleId: e.id,
				scope: a.scope
			}), n.push({
				...l,
				destroy: async () => {
					L.log("Destroying module instance.", {
						moduleId: e.id,
						scope: a.scope
					}), await R(t.setupContext.emit, s, "before-destroy", c), await l.destroy(), await R(t.setupContext.emit, s, "after-destroy", c), L.log("Module instance destroyed.", {
						moduleId: e.id,
						scope: a.scope
					});
				}
			}));
		}
	}
	return L.log("Module manifest processing complete.", { instanceCount: n.length }), n;
}
//#endregion
//#region src/js/utils/unload-warning.ts
var nn = new Set([
	"CRAFT_CSRF_TOKEN",
	"action",
	"redirect",
	"requestToken",
	"renderId",
	"submitAction",
	"pageId",
	"draftContextToken",
	"draftContext",
	"continuationToken"
]);
function rn(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => rn(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${rn(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function an(e) {
	return rn(e, /* @__PURE__ */ new WeakSet());
}
function on(e) {
	if (!e) return !1;
	let t = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !nn.has(t);
}
function sn(e) {
	return an(Array.from(new FormData(e).entries()).filter(([e]) => on(String(e || ""))));
}
function cn(e, t = {}) {
	let n = null, r = !1, i = !1, a = null, o = null, s = null, c = () => {
		a !== null && (window.cancelAnimationFrame(a), a = null), o !== null && (window.clearTimeout(o), o = null), s !== null && (window.clearTimeout(s), s = null);
	}, l = () => r ? (i = sn(e) !== n, i) : !1, u = () => {
		n = sn(e), r = !0, i = !1;
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
function ln(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function un(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !ln(e));
}
function dn(e) {
	let { final: t } = h(e);
	return "submit";
}
function fn(e) {
	return !un(e, dn(e));
}
function pn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || fn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var V = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", mn = 300, hn = "/actions/formie/server/forms/render", gn = "/api", _n = "/actions/formie/server/forms/refresh-tokens", vn = "/actions/formie/server/submissions/submit", yn = "/actions/formie/server/submissions/set-page", bn = "/actions/formie/server/submissions/clear-submission", xn = "/actions/formie/file-upload/hydrate", H = d("general", "client"), Sn = /* @__PURE__ */ new Set();
function U(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return !(n === "false" || n === "0" || n === "off");
}
function Cn(e) {
	return e.formieRefreshTokens != null && e.formieRefreshTokens !== "" ? U(e.formieRefreshTokens, !1) : e.formieStaticCache != null && e.formieStaticCache !== "" ? U(e.formieStaticCache, !1) : !1;
}
function W(e) {
	let t = e instanceof HTMLElement ? e.dataset : {};
	return {
		mode: "server-rendered",
		transport: t.formieTransport || "rest",
		formHandle: t.formieHandle,
		endpoint: t.formieEndpoint,
		staticCache: Cn(t),
		autoVisible: U(t.formieAutoVisible, !0),
		compatibility: U(t.formieCompatibility, !1)
	};
}
function G(e) {
	return e || "server-rendered";
}
function K(e) {
	return e || "rest";
}
function q(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function wn(e, t) {
	Sn.has(e) || (Sn.add(e), H.warn(t));
}
function Tn(e, t) {
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
function J(e, t) {
	let n = (e || "").trim();
	return n ? n.includes("/actions/") ? n : Tn(t, n) : t;
}
function En(e, t) {
	return J(e.endpoint || t.dataset.formieEndpoint, hn);
}
function Dn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : Tn(gn, n) : gn;
}
function On(e, t) {
	return J(t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint, _n);
}
function kn(e, t) {
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
function An(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = J(r, vn), a = t.getAttribute("action");
	t.setAttribute("action", kn(a, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href"), n = J(r, yn);
		e.setAttribute("href", kn(t, n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", J(r, xn));
	});
}
function Y(e, t) {
	if (e === "graphql" && t !== "server-rendered") throw Error(`Formie ${t} mode does not support GraphQL transport yet.`);
}
function jn(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function Mn(e) {
	return U(e.dataset.formieAutomaticSubmissionState, !0);
}
function Nn(e, t, n) {
	return J(n.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint, bn);
}
function Pn(e) {
	return jn(e.dataset.formieUnloadWarning);
}
function Fn(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function In(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function Ln(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function Rn(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function zn(e) {
	return Rn(window.location.href, "resumeToken") || Rn(e.getAttribute("action"), "resumeToken");
}
function Bn(e) {
	return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function Vn(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function Hn(e) {
	return Math.max(0, Vn(e.dataset.formieSubmitDelay, mn));
}
function X(e) {
	return jn(e.dataset.formieValidationOnSubmit);
}
async function Un(e) {
	let t = Hn(e);
	t < 1 || await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Wn(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function Gn(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Wn(n, "data-formie-modules"), i = Wn(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function Kn(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function qn(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function Z(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function Jn(e, t) {
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
async function Yn(e, t) {
	let n = G(t.mode), r = K(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	Y(r, n);
	let i = !!q(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? Dn(t, e) : En(t, e), c = r === "graphql" ? await ze(s, a, o) : await Re(s, a, {
		...o,
		endpoint: s
	});
	return c?.html && (e.innerHTML = c.html), c;
}
async function Xn(e, t, n) {
	if (t.refreshTokens === !1) return;
	Y(K(t.transport), G(t.mode));
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await Be(On(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0);
	Jn(n, i), E(e, "formie:refresh-tokens:refreshed", i);
}
function Zn(e, t, n, r, i, a) {
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = Nn(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		if (e) {
			t.setAttribute("data-formie-pending-action", e);
			return;
		}
		t.removeAttribute("data-formie-pending-action");
	};
	if (Pn(t)) {
		let n = cn(t, { shouldWarn: () => !Ln(t) }), r = (e) => {
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
				Bn(n) && Fn(t, "set-page");
				return;
			}
			n.preventDefault();
			let r = n.currentTarget, i = r?.getAttribute("data-formie-page-id"), a = r?.getAttribute("href");
			if (!(!i || !a)) {
				g(t, i), E(e, "formie:page:navigate", {
					pageId: i,
					href: a
				});
				try {
					E(e, "formie:page:navigate:after", {
						pageId: i,
						href: a,
						response: await Ve(a, t, i)
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), E(e, "formie:page:navigate:error", {
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
	}), !Mn(t)) {
		let e = !1, n = () => {
			e || Ln(t) || zn(t) || (e = !0, He(s, t));
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
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), f = t.getAttribute("data-formie-pending-action"), p = t.querySelector("input[name=\"submitAction\"]"), g = d || f || p?.value || "submit", v = null, te = !1;
		try {
			if (s) v = await Ht({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: X(t),
				action: g,
				submitter: l,
				waitForSubmitDelay: Un,
				onRefreshTokensAfterSubmit: async () => {
					await Xn(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					E(e, "formie:submit:result", t);
				}
			});
			else {
				if (Vt(t), _(t, l), await Un(t), v = await tt(t, g, r, {
					validator: i,
					validateOnSubmit: X(t),
					preflightOnly: !0
				}), v.ok) {
					ee(t, g), c = !0, Fn(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, In(t), m(t), i && X(t)) {
							let { scope: e, final: n } = h(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && P(t, {
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
					te = !0;
					return;
				}
				P(t, v), E(e, "formie:submit:result", v), In(t);
			}
		} catch (n) {
			c = !1, v = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, P(t, v), E(e, "formie:submit:result", v), In(t);
		} finally {
			u(null), !s && !te && !Bt(v) && m(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	});
}
async function Qn(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	Y(K(t.transport), G(t.mode));
	let r = t.formHandle || e.dataset.formieHandle, i = On(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await Be(i, r, a);
	!o || !n || (Jn(n, o), E(e, "formie:refresh-tokens:after", o));
}
function $n() {
	let e = /* @__PURE__ */ new Map(), t = new F(), n = /* @__PURE__ */ new Map(), r = /* @__PURE__ */ new Map(), i = [
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
			H.log("Unmount requested.", { target: Z(t) });
			let r = n.get(t);
			r && (r(), n.delete(t));
			let i = e.get(t);
			if (!i) {
				H.log("Unmount skipped (no mounted state).", { target: Z(t) });
				return;
			}
			E(t, "formie:unmount:before", { id: i.instance.id }), i.unbinds.forEach((e) => {
				e();
			}), i.unbinds = [], i.validator?.destroy(), i.validator = null;
			for (let e of i.modules) await e.destroy();
			i.modules = [], i.bus.clear(), e.delete(t), E(t, "formie:unmount:after", { id: i.instance.id }), H.log("Unmount complete.", {
				id: i.instance.id,
				target: Z(t)
			});
		})().finally(() => {
			r.delete(t);
		});
		r.set(t, a), await a;
	}, o = async (r, o) => {
		H.log("Mount requested.", {
			target: Z(r),
			mode: o.mode,
			autoVisible: o.autoVisible
		});
		let s = n.get(r);
		s && (s(), n.delete(r));
		let c = e.get(r);
		if (c) return H.log("Mount skipped (already mounted).", {
			id: c.instance.id,
			target: Z(r)
		}), c.instance;
		let l = new Pe(), u = [], d = r?.id || `formie-${e.size + 1}`, f = W(r), p = {
			...f,
			...o,
			mode: G(o.mode ?? f.mode),
			transport: K(o.transport ?? f.transport)
		}, ee = ye(p.compatibility);
		if (p.mode !== "server-rendered" && !q(r)) throw Error(`Formie ${p.mode} mode is not implemented yet in the browser client.`);
		let m = await Yn(r, p), h = q(r);
		p.staticCache = o.staticCache ?? Cn(h ? h.dataset : r.dataset);
		let g = Gn(r, h), _ = m || g ? {
			...m || {},
			...g || {}
		} : null, v = _?.theme, ne = {}, b = (_?.modules || []).filter((e) => !!e?.id && !!e?.type);
		H.log("Resolved mount payload.", {
			target: Z(r),
			hasRenderPayload: !!m,
			hasEmbeddedPayload: !!g,
			moduleCount: b.length
		});
		let x = re(r, v, h), S = h ? new pt(h, {
			live: jn(h.dataset.formieValidationOnFocus),
			errorMessage: h.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: x.fieldLayoutError || [],
			inputErrorClass: x.fieldControlError || [],
			messagesClass: x.fieldErrors || [],
			messageClass: x.fieldError || []
		}) : null;
		if (h && S) {
			let e = h;
			e.formieValidation = S, ne.validation = S;
			let t = {
				validator: S,
				addValidator: S.addValidator.bind(S),
				removeValidator: S.removeValidator.bind(S)
			};
			E(h, "formie:validator:ready", t), E(r, "formie:validator:ready", t);
		}
		h && ((m || p.endpoint || r.dataset.formieEndpoint) && An(r, h, p), p.mode === "server-rendered" && Me(h) && (je(h), Ne(h)), y(h)), Object.keys(x).length && E(r, "formie:theme:applied", { hasClasses: !0 });
		let C = await tn(b, {
			registry: t,
			matchContext: {
				root: r,
				form: h,
				mode: p.mode
			},
			setupContext: {
				formId: d,
				root: r,
				form: h,
				target: r,
				scope: "form",
				state: ne,
				on: (e, t) => l.on(e, t),
				emit: (e, t) => (E(r, e, t), l.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && H.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		H.log("Module setup complete.", {
			target: Z(r),
			moduleInstances: C.length
		});
		let w = {
			id: d,
			root: r,
			submit: async (e = "submit") => {
				if (H.log("Submit requested.", {
					id: d,
					target: Z(r),
					action: e
				}), !h) return {
					ok: !1,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				let t = h.querySelector("input[name=\"submitAction\"]");
				if (t && (t.value = e), h.getAttribute("data-formie-loading") === "true") return {
					ok: !1,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				let n = h.querySelector(`[data-formie-action="${e}"]`), i = await Ht({
					id: d,
					target: r,
					form: h,
					bus: l,
					validator: S,
					validateOnSubmit: X(h),
					action: e,
					submitter: n,
					waitForSubmitDelay: Un,
					onRefreshTokensAfterSubmit: async () => {
						await Xn(r, p, h);
					},
					dispatchSubmitResult: (e) => {
						E(r, "formie:submit:result", e);
					}
				});
				return H.log("Submit completed.", {
					id: d,
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
		h && (Ee({
			target: r,
			form: h,
			validatorDetail: S ? {
				validator: S,
				addValidator: S.addValidator.bind(S),
				removeValidator: S.removeValidator.bind(S)
			} : null,
			options: ee,
			unbinds: u
		}), we({
			target: r,
			form: h,
			instance: w,
			options: ee,
			unbinds: u
		})), h && (Zn(r, h, p, l, S, u), S && (u.push(te(h, S, r)), u.push(pn(h))), await Qn(r, p, h)), i.forEach((e) => {
			let t = l.on(`formie:stage:${e}:before`, async (t) => {
				E(r, `formie:stage:${e}:before`, t);
			}), n = l.on(`formie:stage:${e}:before`, async (e) => {
				for (let t of C) t.onBeforeStage && await t.onBeforeStage(e);
			}), i = l.on(`formie:stage:${e}:after`, async (t) => {
				E(r, `formie:stage:${e}:after`, t);
			}), a = l.on(`formie:stage:${e}:after`, async (e) => {
				let t = e;
				for (let e of C) e.onAfterStage && await e.onAfterStage(t, t.result);
			});
			u.push(t, n, i, a);
		});
		let ie = l.on("formie:submit:before", async (e) => {
			E(r, "formie:submit:before", e);
		}), ae = l.on("formie:submit:after", async (e) => {
			E(r, "formie:submit:after", e);
		}), oe = l.on("formie:submit:final:before", async (e) => {
			E(r, "formie:submit:final:before", e);
		}), se = l.on("formie:submit:final:after", async (e) => {
			E(r, "formie:submit:final:after", e);
		});
		return u.push(ie, ae, oe, se), e.set(r, {
			options: p,
			bus: l,
			form: h,
			validator: S,
			modules: C,
			unbinds: u,
			instance: w
		}), E(r, "formie:mount:after", {
			id: d,
			mode: p.mode
		}), H.log("Mount complete.", {
			id: d,
			target: Z(r),
			mode: p.mode
		}), w;
	}, s = (t, r) => {
		if (!r.autoVisible || Kn(t) || typeof IntersectionObserver > "u") return o(t, r);
		if (e.has(t)) return Promise.resolve(e.get(t)?.instance || null);
		if (n.has(t)) return H.log("Mount deferred (already waiting visibility).", { target: Z(t) }), Promise.resolve(null);
		let i = new IntersectionObserver((e) => {
			e.some((e) => e.target === t && e.isIntersecting) && (i.disconnect(), n.delete(t), H.log("Visibility reached, proceeding mount.", { target: Z(t) }), o(t, {
				...r,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return i.observe(t), n.set(t, () => {
			i.disconnect();
		}), H.log("Mount deferred until visible.", { target: Z(t) }), Promise.resolve(null);
	};
	return {
		mount: o,
		unmount: a,
		update: async (t, n) => {
			let r = e.get(t);
			if (!r) return o(t, {
				...W(t),
				...n,
				mode: n.mode || "server-rendered"
			});
			r.options = {
				...r.options,
				...n
			};
			let i = re(t, n.payload?.theme || r.options.payload?.theme || Gn(t, r.form)?.theme, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = i.fieldLayoutError || [], r.validator.config.inputErrorClass = i.fieldControlError || [], r.validator.config.messagesClass = i.fieldErrors || [], r.validator.config.messageClass = i.fieldError || []), Object.keys(i).length && E(t, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (t) => e.get(t)?.instance || null,
		refreshForCache: async (t) => {
			wn("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let n = null;
			if (n = typeof t == "string" ? document.getElementById(t) || document.querySelector(`[data-formie-form-id="${t}"]`) : t, !n) {
				H.warn("refreshForCache target not found.", { targetOrId: t });
				return;
			}
			let r = e.get(n), i = q(n), a = r?.options || W(n);
			if (!i) {
				H.warn("refreshForCache found no form element for target.", { target: Z(n) });
				return;
			}
			let o = a.formHandle || n.dataset.formieHandle || i.dataset.formieHandle, s = On(a, n), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				H.warn("refreshForCache found no form handle for target.", { target: Z(n) });
				return;
			}
			let l = await Be(s, o, c);
			l && (Jn(i, l), E(n, "formie:refresh-tokens:after", l));
		},
		registerModule: (e, n) => t.register(e, n),
		unregisterModule: (e) => {
			t.unregister(e);
		},
		getRegisteredModules: () => t.getAll(),
		scan: async (e) => {
			let t = e || document, n = Array.from(t.querySelectorAll(V));
			H.log("Scan started.", {
				scope: t === document ? "document" : t,
				targetCount: n.length
			});
			let r = (await Promise.all(n.map((e) => s(e, W(e))))).filter((e) => !!e);
			return H.log("Scan finished.", {
				mountedCount: r.length,
				deferredCount: n.length - r.length
			}), r;
		},
		observe: (t) => {
			if (typeof MutationObserver > "u") return () => {};
			let r = t || document;
			H.log("Observer started.", { scope: r === document ? "document" : r });
			let i = new MutationObserver((t) => {
				t.forEach((t) => {
					t.addedNodes.forEach((e) => {
						e instanceof Element && (e.matches(V) && (H.log("Observer detected new root.", { target: Z(e) }), s(e, W(e))), e.querySelectorAll(V).forEach((e) => {
							H.log("Observer detected new nested root.", { target: Z(e) }), s(e, W(e));
						}));
					}), t.removedNodes.forEach((t) => {
						t instanceof Element && (e.has(t) && (H.log("Observer detected removed root.", { target: Z(t) }), a(t)), t.querySelectorAll(V).forEach((t) => {
							e.has(t) && (H.log("Observer detected removed nested root.", { target: Z(t) }), a(t));
						}));
					});
				});
			});
			return i.observe(r, {
				childList: !0,
				subtree: !0
			}), () => {
				i.disconnect(), H.log("Observer stopped."), n.forEach((e, t) => {
					qn(t, r) && (e(), n.delete(t));
				});
				let t = [];
				r instanceof Element && r.matches(V) && t.push(r), r.querySelectorAll(V).forEach((e) => {
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
var er = d("general", "module-hydrator");
async function tr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form")), r = e.modules ?? [], i = e.mode ?? "server-rendered", a = e.registry ?? new F(), o = new Pe(), s = await tn(r, {
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
	return er.log("Hydrated module manifest.", {
		moduleCount: r.length,
		instanceCount: s.length,
		mode: i
	}), {
		destroy: async () => {
			await nr(s), o.clear();
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
async function nr(e) {
	for (let t of e) try {
		await t.destroy();
	} catch (e) {
		console.error("[formie] Failed to destroy module instance.", e), er.warn("Failed destroying module instance.", { error: e });
	}
}
//#endregion
//#region src/js/core/formie.ts
function Q(e) {
	return e instanceof Element;
}
function rr(e) {
	return e.ok;
}
function ir(e) {
	return typeof e == "string" ? `selector "${e}"` : Q(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function ar(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) !Q(r) || t.has(r) || (t.add(r), n.push(r));
	return n;
}
function $(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : Q(e) ? [e] : ar(e);
}
function or() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function sr(e) {
	let t = $(e);
	return t.length > 0 || typeof e != "string" ? t : (await or(), $(e));
}
function cr(e) {
	return typeof e == "string" ? document : Q(e) ? e.getRootNode() : document;
}
function lr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function ur(e, t, n, r) {
	let i = [], o = lr(e);
	for (let s of r) {
		let r = n.get(s);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let c = await t.mount(s, o), l = [];
		if (e.onReady?.(c), l.push(c.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, c), rr(n) ? e.onSuccess?.(n, c) : e.onError?.(n, c);
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
async function dr(e) {
	let t = e.client ?? $n(), n = /* @__PURE__ */ new Map(), r = await sr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${ir(e.element)}.`);
	await ur(e, t, n, r);
	let i = e.observe ? t.observe(cr(e.element)) : null;
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
			let r = $(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : ur(e, t, n, r);
		},
		async destroy() {
			i?.();
			let e = Array.from(n.entries());
			for (let [r, i] of e) i.unsubs.forEach((e) => e()), await t.unmount(r), n.delete(r);
		}
	};
}
//#endregion
export { a as FORMIE_HTML_EVENT_NAMES, pt as FormieValidator, _e as LEGACY_FORMIE_DOM_EVENT_BRIDGES, ve as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, F as ModuleRegistry, we as bindLegacyDomEventCompatibility, Ee as bindLegacyValidatorCompatibility, me as buildFieldValueRegistry, d as createDebug, $n as createFormieClient, l as debugLog, u as debugWarn, ge as defineAddressModule, ce as defineCaptchaModule, se as definePassiveCaptchaModule, ne as definePaymentModule, de as fieldKeyToInputName, dr as formie, i as getFieldModuleEventName, oe as getFormieTranslations, r as getGlobalModuleLifecycleEventName, e as getScopedModuleLifecycleEventName, tr as hydrateFormieModules, le as inputNameToFieldKey, c as isFormieDebugEnabled, ie as mergeFormieTranslations, ue as normalizeFieldKey, o as normalizeFormieEventName, fe as parseFieldReference, he as resolveFieldReferenceFromFormData, pe as resolveFieldReferenceLive, ye as resolveLegacyCompatibilityOptions, s as setFormieDebugEnabled, ae as setFormieTranslations, w as t, t as toDomEventName, C as translate };
