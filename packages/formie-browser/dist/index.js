import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { a as t, l as n, o as r, s as i } from "./chunks/dist-BLxJz5iJ.js";
import { c as a, d as o, l as s, o as c, r as l, t as u, u as d } from "./chunks/event-names-BCI2FLD8.js";
import { _ as f, a as p, b as m, c as h, d as g, f as _, g as v, h as y, i as b, l as ee, m as te, o as x, p as S, s as C, t as ne, u as w, v as re, x as T, y as ie } from "./chunks/api-Bqp2Tyky.js";
import { a as E, i as D, n as O, r as ae, t as k } from "./chunks/debug-rhzqP1j2.js";
import { i as A, r as oe, t as j } from "./chunks/theme-classes-DAQuEqdP.js";
import { n as M } from "./chunks/skip-CEW89ExY.js";
import { i as se, t as ce } from "./chunks/csrf-DxHg_ZYt.js";
import { n as le, t as ue } from "./chunks/http-BqbAssaU.js";
import { i as de } from "./chunks/async-2GdXNFCE.js";
import { a as fe, i as pe, n as me, r as he, t as ge } from "./chunks/i18n-BY1ds1BL.js";
import { n as _e, t as ve } from "./chunks/api-BPtH5RBA.js";
import { n as ye, r as be, t as xe } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as Se, n as Ce, r as we, t as Te } from "./chunks/field-references.resolver-Cv-4C2ls.js";
import { t as Ee } from "./chunks/api-CmuOZ0i3.js";
//#region src/js/compatibility/event-map.ts
var De = [
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
], Oe = [
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
function ke(e) {
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
function Ae(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function je(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function Me(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function Ne(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? Me(r, t) : t;
}
function Pe({ target: e, form: t, instance: n, options: r, unbinds: i }) {
	r.legacyDomEvents && De.forEach((r) => {
		let a = (i) => {
			i instanceof CustomEvent && je(r, i.detail) && Ae(r.target === "document" ? document : t, r.legacyEvent, Ne(r, i.detail, e, t, n));
		};
		e.addEventListener(o(r.canonicalEvent), a), i.push(() => {
			e.removeEventListener(o(r.canonicalEvent), a);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function N(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Fe(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function Ie({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	N(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && Fe(e.detail, a) && N(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && Fe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && N(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && Fe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && N(n.target, "formieValidatorClearError", {
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
function P(e) {
	let t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
	return t === "assertive" || t === "off" ? t : "polite";
}
function Le(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Re(e) {
	return e === "off" ? null : e;
}
function ze(e, t) {
	t ? (e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true")) : (e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic"));
}
//#endregion
//#region src/js/core/field-error-aria.ts
function Be(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Ve(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	r.length ? e.setAttribute("aria-describedby", r.join(" ")) : e.removeAttribute("aria-describedby");
}
function He(e, t) {
	e.setAttribute("aria-errormessage", t), Be(e, t);
}
function Ue(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), Ve(e);
}
//#endregion
//#region src/js/core/validation-focus.ts
function We(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function Ge(e) {
	if (e instanceof HTMLTextAreaElement) {
		let t = e.richText;
		if (t) return t.content;
	}
	return e;
}
function Ke(e) {
	for (let t of e) {
		let e = Ge(t);
		if (!(e instanceof HTMLInputElement && e.type === "hidden") && e.getClientRects().length > 0) return e;
	}
	return null;
}
function qe(e) {
	return Ke(Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).filter((e) => !M(e))) || Ke(Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")));
}
function Je(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function Ye(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = Array.from(n.querySelectorAll("[data-formie-field-error]")), i = r.some((e) => e.closest("[data-formie-field-handle]") === n), a = n.getAttribute("data-formie-field-has-error") === "true" && r.length === 0;
		if (!i && !a) return;
		n.setAttribute("data-formie-field-has-error", "true"), j(n, e, "fieldLayoutError");
		let o = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let r = t;
			if (M(r)) return;
			r.setAttribute("aria-invalid", "true"), j(r, e, "fieldControlError"), r.setAttribute("data-formie-input-has-error", "true"), o && He(r, o);
			let i = n.querySelector("[data-formie-instructions]");
			i?.id && Be(r, i.id);
		});
	});
}
function Xe(e) {
	return !!We(e) || !!Je(e);
}
function Ze(e) {
	let t = document.activeElement;
	(!t || t === document.body || t.getClientRects().length === 0) && F(e);
}
function F(e) {
	let t = We(e);
	if (t) {
		let e = qe(t);
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
	let n = Je(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/core/field-error-container.ts
function Qe(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function $e(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = Qe(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/theme/message-attributes.ts
function et(e, t, n, r = !1) {
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
var I = /* @__PURE__ */ new WeakMap();
function tt(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function nt(e) {
	return (e.dataset.formieErrorMessagePosition ?? "top-form").trim();
}
function rt(e) {
	return (e.dataset.formieSuccessMessagePosition || e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function it(e) {
	let t = (e.dataset.formieSuccessMessageTimeout || e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function at(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function ot(e) {
	let t = I.get(e);
	typeof t == "number" && (window.clearTimeout(t), I.delete(e));
}
function st(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function ct(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function lt(e, t) {
	return t === "bottom-form" ? ct(e) : st(e);
}
function ut(e, t) {
	return t === "top-form" ? st(e) : t === "bottom-form" && !at(e) ? ct(e) : e;
}
function dt(e) {
	let t = nt(e), n = lt(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), j(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function ft(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), et(n, e, "messageError"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), j(n, e, "message", "messageError"), n.setAttribute("role", "alert"), ze(n, Re(P(e))), n;
}
function pt(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = ut(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), j(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function mt(e) {
	return $e(e, (t) => {
		j(t, e, "fieldErrors");
	});
}
function ht(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		A(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), A(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), Ue(n, i);
		});
	}), v(e);
}
function gt(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), A(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function _t(e) {
	ot(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), et(n, e, "messageSuccess", !0), A(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), tt(e) === "message" && at(e) || ee(e, !1);
}
function vt(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function yt(e, t) {
	let n = Re(P(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = `fields[${t.split(".").join("][")}]`, a = e.querySelector(`[name="${CSS.escape(i)}"], [name="${CSS.escape(i + "[]")}"]`)?.closest("[data-formie-field-handle]") || e.querySelector(`[data-formie-field-handle="${CSS.escape(t)}"]`);
		if (!a) return;
		let o = mt(a), s = o.id && o.id.trim() ? o.id : `${t}-errors`;
		o.id = s, ze(o, n), j(a, e, "fieldLayoutError"), a.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${s}-${n + 1}`, j(r, e, "fieldError"), r.textContent = t, o.appendChild(r);
		});
		let c = o.querySelector("[data-formie-field-error]")?.id;
		a.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), j(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), c && He(n, c);
			let r = a.querySelector("[data-formie-instructions]");
			r?.id && Be(n, r.id);
		});
	}), v(e);
}
function bt(e, t) {
	if (!nt(e)) return;
	let n = dt(e), r = ft(e, n);
	j(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), j(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function xt(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.payment?.status;
	return t === "requiresAction" || t === "pending" || t === "unknown";
}
function St(e, t) {
	let n = dt(e), r = ft(e, n);
	j(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), j(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function Ct(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || tt(e) === "message" && rt(e) !== "";
}
function wt(e, t, n = {}) {
	let r = rt(e);
	if (!r) return;
	let i = pt(e, r);
	et(i, e, "messageSuccess"), j(i, e, "message", "messageSuccess"), i.setAttribute("data-formie-message", "true"), i.setAttribute("data-formie-message-success", "true"), i.setAttribute("role", "status"), i.setAttribute("aria-live", "polite"), i.setAttribute("aria-atomic", "true");
	let a = document.createElement("div");
	a.setAttribute("data-formie-success", "true"), j(a, e, "success"), a.innerHTML = t, i.appendChild(a), n.hideForm !== !1 && at(e) && ee(e, !0);
	let o = e.dataset.formieScrollToTop?.trim().toLowerCase();
	o !== void 0 && [
		"",
		"true",
		"1"
	].includes(o) && i.scrollIntoView({
		behavior: "smooth",
		block: "start"
	});
	let s = it(e);
	if (s !== null) {
		let t = window.setTimeout(() => {
			I.delete(e), _t(e);
		}, s);
		I.set(e, t);
	}
}
function L(e, t) {
	if (ht(e), gt(e), _t(e), vt(e), t.ok) {
		let n = t.meta?.summaryTokens;
		n && typeof n == "object" && e.querySelectorAll("[data-formie-field-uid]").forEach((e) => {
			let t = n[e.dataset.formieFieldUid || ""];
			typeof t == "string" && t && e.querySelector("[data-formie-summary-container]")?.setAttribute("data-formie-summary-token", t);
		}), Ct(e, t) && wt(e, t.message || "", { hideForm: t.action !== "save" });
	} else if (!t.ok) {
		if (xt(t)) {
			let n = String(t.payment?.message || "").trim();
			n && St(e, n);
			return;
		}
		t.fieldErrors && yt(e, t.fieldErrors), t.formErrors?.length ? bt(e, t.formErrors) : !t.fieldErrors && t.message && bt(e, [t.message]), F(e);
	}
}
//#endregion
//#region src/js/core/revise-pages.ts
function R(e) {
	return e.hasAttribute("data-formie-revise");
}
function Tt(e) {
	return _(e).filter((e) => !e.hasAttribute("data-formie-conditionally-hidden"));
}
function Et(e) {
	return _(e).find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
}
function Dt(e) {
	return !!e.querySelector("[data-formie-field-has-error]");
}
function Ot(e, t, n, r) {
	if (!r || !n) return !0;
	let i = n.submit(t, { final: !1 });
	return !i.length || (L(e, {
		ok: !1,
		stage: "validate",
		code: "VALIDATION_FAILED",
		message: n.config.errorMessage || "Validation failed.",
		fieldErrors: n.getFieldErrors(i),
		formErrors: [n.config.errorMessage || "Validation failed."]
	}), F(e), !1);
}
function kt(e, t, n) {
	let r = t.getAttribute("data-formie-page-id");
	r && (V(e), n?.resetLiveState(), p(e, r), h(e), m(e, "formie:page:navigate:after", { pageId: r }));
}
function At(e, t, n, r) {
	if (t !== "submit" && t !== "back") return !1;
	let i = Tt(e), a = Et(e), o = a ? i.indexOf(a) : -1;
	if (!a || o === -1) return !1;
	if (t === "back") return o > 0 && kt(e, i[o - 1], n), !0;
	let s = i[o + 1];
	return s ? (Ot(e, a, n, r) && (f(e, t), kt(e, s, n)), !0) : !1;
}
function jt(e, t, n, r) {
	let i = Tt(e), a = Et(e), o = i.find((e) => e.getAttribute("data-formie-page-id") === t);
	o && o !== a && (a && i.indexOf(o) > i.indexOf(a) && !Ot(e, a, n, r) || kt(e, o, n));
}
function Mt(e) {
	let t = Et(e);
	if (t && Dt(t)) return;
	let n = Tt(e).find(Dt)?.getAttribute("data-formie-page-id");
	n && (p(e, n), F(e));
}
//#endregion
//#region src/js/transport/forms-api.ts
var z = k("general", "transport");
function Nt(e) {
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
function Pt(e, t) {
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
async function Ft(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	z.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await le(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	return z.log("requestRender complete.", { hasHtml: !!a.html }), a;
}
async function It(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: Nt(n)
		}
	});
	z.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await le(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	if (Array.isArray(a.errors) && a.errors.length > 0) throw Error(a.errors.map((e) => e.message || "Unknown GraphQL error").join("; "));
	if (!a.data?.formieHtmlForm) throw Error(`Form not found for handle "${t}".`);
	let o = a.data.formieHtmlForm;
	return z.log("requestGraphqlRender complete.", { hasHtml: !!o.html }), o;
}
async function Lt(e, t, n, r = {}, i, a) {
	let o = new URL(e, window.location.origin);
	o.searchParams.set("handle", t), a !== void 0 && a !== "" && o.searchParams.set("siteId", String(a)), n && o.searchParams.set("renderId", n), i && o.searchParams.set("requestToken", i), z.log("requestRefreshTokens start.", {
		endpoint: o.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let s = await le(o.toString(), r);
	return z.log("requestRefreshTokens complete.", { hasRefreshTokens: !!s.refreshTokens }), s.refreshTokens || s;
}
async function Rt(e, t, n) {
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
		}), ce(i, t);
		for (let [e, n] of new FormData(t)) e.startsWith("fields[") && i.append(e, n);
	}
	z.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await (await ue(r.toString(), {
		method: "POST",
		body: i,
		profile: t?.dataset.formieRequestProfile
	})).json();
	if (t && a.session) {
		let e = a.session, n = t.querySelector("input[name=\"expectedVersion\"]"), r = t.querySelector("input[name=\"requestToken\"]");
		n && (n.value = String(e.version)), r && e.tokens?.request && (r.value = e.tokens.request);
	}
	return z.log("requestSetPage complete.", a), a;
}
function zt(e, t) {
	let n = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let n = t.querySelector(`input[name="${e}"]`)?.value?.trim();
		n && i.append(e, n);
	}), ce(i, t), z.log("clearSubmissionOnUnload start.", { requestUrl: n.toString() });
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
async function Bt(e, t) {
	let n = (e.getAttribute("method") || "POST").toUpperCase(), i = e.getAttribute("action") || window.location.href, a = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	z.log("submitForm start.", {
		method: n,
		action: i,
		submitAction: t.get("submitAction")
	});
	let o = await r(i, {
		method: n,
		body: t,
		headers: { Accept: "application/json" }
	}, { profile: e.dataset.formieRequestProfile }), s = o.headers.get("content-type") || "";
	if (!s.includes("application/json")) return o.ok ? (z.log("submitForm non-JSON success response.", {
		status: o.status,
		contentType: s
	}), {
		ok: !0,
		message: "Submission completed."
	}) : (z.warn("submitForm non-JSON HTTP error.", {
		status: o.status,
		contentType: s
	}), {
		ok: !1,
		code: "HTTP_ERROR",
		message: `Request failed (${o.status}).`,
		formErrors: [`Request failed (${o.status}).`]
	});
	let c = Pt(await o.json(), a);
	return z.log("submitForm JSON response normalized.", {
		ok: c.ok,
		code: c.code,
		hasRedirect: !!c.redirect?.url,
		hasSubmitData: Array.isArray(c.submitData) && c.submitData.length > 0
	}), c;
}
//#endregion
//#region src/js/submit/pipeline.ts
var Vt = [
	"prepare",
	"validate",
	"challenge",
	"payment",
	"send",
	"result"
], Ht = [
	"prepare",
	"validate",
	"challenge",
	"payment"
], B = k("general", "pipeline");
function Ut(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function Wt(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function Gt(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function Kt(e, t) {
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
function qt(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = Wt(t) ? t : null;
		n && !n.closest("[data-formie-page]") && Gt(n) && Kt(e, n);
	});
}
function Jt(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = Wt(t) ? t : null;
		r && r.name && !r.disabled && (r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), Gt(r) && Kt(e, r)));
	}), n;
}
function Yt(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function Xt(e, t) {
	let n = _(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	qt(i, e);
	let a = /* @__PURE__ */ new Set();
	return (R(e) ? n : [r]).forEach((e) => {
		Jt(i, e).forEach((e) => {
			a.add(e);
		});
	}), Yt(i, a), i.set("submitAction", t), i;
}
function Zt(e, t) {
	if (t !== "submit") return !1;
	let n = _(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function Qt(e, t, n, r = {}) {
	B.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = Zt(e, t), c = T(e, {
		form: e,
		action: t,
		formData: Xt(e, t),
		abort: (e) => {
			i = !0, a = e, B.warn("Pipeline aborted.", { reason: e });
		},
		isAborted: () => i,
		abortReason: () => a
	}), l = (t, r) => n.emitSafe(t, T(e, r)), u = {
		prepare: async (e) => {
			let t = e.form.querySelector("input[name=\"submitAction\"]");
			return t && (t.value = e.action), e.formData.set("submitAction", e.action), null;
		},
		validate: async (e) => {
			if (e.action !== "submit" || r.validateOnSubmit === !1) return null;
			if (r.validator) {
				let { scope: t, final: n } = S(e.form), i = r.validator.submit(n ? e.form : t, { final: n });
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
			y(e.form), e.formData = Xt(e.form, e.action);
			let t = await Bt(e.form, e.formData);
			return o = t, t;
		},
		result: async (e) => (o && o.ok && o.redirect?.url && (o.redirect.target === "new-tab" ? window.open(o.redirect.url, "_blank", "noopener,noreferrer") : (e.form.setAttribute("data-formie-internal-navigation", "redirect"), window.location.href = o.redirect.url)), null)
	};
	{
		let e = await n.emitSafe("formie:submit:before", c);
		e.failed.length > 0 && B.warn("Submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	if (s) {
		let e = await n.emitSafe("formie:submit:final:before", c);
		e.failed.length > 0 && B.warn("Final submit before listeners failed.", {
			eventName: e.eventName,
			failed: e.failed.length
		});
	}
	let d = r.preflightOnly ? Ht : Vt;
	for (let e of d) {
		if (B.log("Stage start.", {
			stage: e,
			action: t
		}), i) return B.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ut(e, a);
		{
			let t = await n.emitSafe(`formie:stage:${e}:before`, {
				...c,
				stage: e
			});
			t.failed.length > 0 && B.warn("Stage before listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ut(e, a);
			{
				let n = await l("formie:submit:after", t);
				n.failed.length > 0 && B.warn("Submit after listeners failed (abort before stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			if (s) {
				let n = await l("formie:submit:final:after", t);
				n.failed.length > 0 && B.warn("Final submit after listeners failed (abort before stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			return B.warn("Aborted after stage before-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		let r = await u[e](c);
		B.log("Stage runner complete.", {
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
			t.failed.length > 0 && B.warn("Stage after listeners failed.", {
				stage: e,
				failed: t.failed.length
			});
		}
		if (i) {
			let t = Ut(e, a);
			{
				let n = await l("formie:submit:after", t);
				n.failed.length > 0 && B.warn("Submit after listeners failed (abort after stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			if (s) {
				let n = await l("formie:submit:final:after", t);
				n.failed.length > 0 && B.warn("Final submit after listeners failed (abort after stage).", {
					stage: e,
					failed: n.failed.length
				});
			}
			return B.warn("Aborted after stage after-hooks.", {
				stage: e,
				reason: a
			}), t;
		}
		if (r && !r.ok) {
			{
				let t = await l("formie:submit:after", r);
				t.failed.length > 0 && B.warn("Submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			if (s) {
				let t = await l("formie:submit:final:after", r);
				t.failed.length > 0 && B.warn("Final submit after listeners failed (failed stage).", {
					stage: e,
					failed: t.failed.length
				});
			}
			return B.warn("Pipeline short-circuited by failed stage.", {
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
		e.failed.length > 0 && B.warn("Submit after listeners failed (success).", { failed: e.failed.length });
	}
	if (s) {
		let e = await l("formie:submit:final:after", f);
		e.failed.length > 0 && B.warn("Final submit after listeners failed (success).", { failed: e.failed.length });
	}
	return B.log("Pipeline completed.", {
		ok: f.ok,
		stage: f.stage,
		code: f.code
	}), f;
}
//#endregion
//#region src/js/core/submit-flow.ts
var $t = k("general", "submit-flow");
function en(e) {
	return !(!e.ok && e.stage === "validate");
}
function tn(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function V(e) {
	ht(e), gt(e), _t(e), vt(e);
}
async function nn(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	V(r), w(r, c || null);
	let f = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), f = await Qt(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), L(r, f), d(f), x(r, f, s), en(f) && await u(f);
	} catch (e) {
		f = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, L(r, f), d(f), $t.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		tn(f) || C(r);
	}
	return f;
}
//#endregion
//#region src/js/events/event-bus.ts
var rn = class {
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
}, H = class {
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
}, an = new H(), on = {
	"address-finder": () => import("./chunks/address-finder-ChSNCcm_.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-D7Ctfq2i.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-BdJ6dlN4.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BUOGAm6N.js").then((e) => e.placeKitModule)
}, sn = {
	"captcha-eu": () => import("./chunks/captcha-eu-D9AClU5R.js").then((e) => e.captchaEuModule),
	"friendly-captcha-v1": () => import("./chunks/friendly-captcha-v1-roLHwqvH.js").then((e) => e.friendlyCaptchaV1Module),
	"friendly-captcha-v2": () => import("./chunks/friendly-captcha-v2-DtaBEiuN.js").then((e) => e.friendlyCaptchaV2Module),
	hcaptcha: () => import("./chunks/hcaptcha-O_4uECEr.js").then((e) => e.hcaptchaModule),
	"question-captcha": () => import("./chunks/question-CaxSRftK.js").then((e) => e.questionCaptchaModule),
	"recaptcha-enterprise": () => import("./chunks/recaptcha-enterprise-BFUGhUaC.js").then((e) => e.recaptchaEnterpriseModule),
	"recaptcha-v2-checkbox": () => import("./chunks/recaptcha-v2-checkbox-CeRtAdE6.js").then((e) => e.recaptchaV2CheckboxModule),
	"recaptcha-v2-invisible": () => import("./chunks/recaptcha-v2-invisible-BLA8baiY.js").then((e) => e.recaptchaV2InvisibleModule),
	"recaptcha-v3": () => import("./chunks/recaptcha-v3-DYQD60Os.js").then((e) => e.recaptchaV3Module),
	snaptcha: () => import("./chunks/snaptcha-P3EoSQud.js").then((e) => e.snaptchaModule),
	turnstile: () => import("./chunks/turnstile-DQF_MuTJ.js").then((e) => e.turnstileModule)
}, cn = {
	calculations: () => import("./chunks/calculations-BdjPub0T.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-CjN5R4ag.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-BbVhQgqc.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-DrYwACuE.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-C40-YCBv.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-DKvWIulB.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-Zu-jYSvi.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-BwDikhcw.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-SElYH2YK.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-CC-MAlmq.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-BAXPyKuF.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-CQ0mNqhj.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-B4yPlr5r.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-BoFE7C6F.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-xDZCZK6x.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-CTB3FkTL.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-DpLZkVj_.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-DbAgNbAk.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-BPPTDkG_.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-Cs6ZyCzO.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-Du6kuO4T.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-DgZuY18y.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-Byo-2UU-.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-BdM3kWFH.js").then((e) => e.textLimitModule)
}, ln = {
	bpoint: () => import("./chunks/bpoint-Dz1Ei2Gy.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-DYOhpjux.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-ntf1q7uI.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-kugeA4CF.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-BOtB4bf3.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-1YQ_bsZ0.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-BkgFPpak.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-UGh5Ojym.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-D-6YOwJU.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-1tjWKCY8.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-C28l6CMl.js").then((e) => e.stripeModule)
}, un = {
	...cn,
	...on,
	...sn,
	...ln
}, dn = /* @__PURE__ */ new Map();
function fn(e) {
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
function pn(e, t, n) {
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
async function mn(e) {
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
async function hn(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(un, e.slice(7)) ? un[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	dn.has(e) || dn.set(e, r().catch((t) => {
		throw dn.delete(e), t;
	}));
	let i = await dn.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function gn(e, t, n) {
	return [...new Set(e.targets.flatMap((e) => {
		if (e.type === "form") return [n || t];
		let r = e.type === "selector" ? e.selector : e.type === "field" ? `[data-formie-field-uid="${CSS.escape(e.uid)}"]` : e.type === "page" ? `[data-formie-page-id="${CSS.escape(e.id)}"]` : `[data-formie-action="${CSS.escape(e.action)}"]`;
		return [...t.matches(r) ? [t] : [], ...t.querySelectorAll(r)];
	}))].filter((e) => !e.closest("[hidden], [data-formie-hidden=\"true\"], [data-formie-conditionally-hidden], [data-formie-page-hidden]"));
}
async function _n(e, n) {
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
		s.set(e.key, i), c.set(e.key, fn(t)), console.error("[formie] Browser module failure", i, t), await n.setupContext.emit("formie:browser:module:error", i);
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
						await mn(n.textContent || ""), d.textContent = "Details copied.";
					} catch {
						d.textContent = "Copy failed. Select the details above and copy them manually.";
						let e = document.createRange();
						e.selectNodeContents(n), window.getSelection()?.removeAllRanges(), window.getSelection()?.addRange(e);
					}
				}), l.append(u, d), s.append(c, n, l), e.append(r, s);
			}
			e.dataset.formieModuleError = "true", i !== a && a.contains(i) ? i.before(e) : a.prepend(e);
		}
		let t = e.querySelector("[data-formie-module-error-details]"), n = pn(r, s.values(), c);
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
				d = gn(t, i, a);
			} catch (e) {
				s.has(t.key) || await p(t, e);
				continue;
			}
			let f = o.get(t.key) ?? /* @__PURE__ */ new Map();
			o.set(t.key, f);
			for (let [e, t] of Array.from(f.entries()).reverse()) d.includes(e) || (await m(t.instance), f.delete(e), l.splice(l.indexOf(t.instance), 1));
			let h;
			try {
				h = await hn(t.moduleId, n.registry);
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
var vn = /* @__PURE__ */ new Set([
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
function yn(e) {
	let t = /* @__PURE__ */ new Set(), n = !0;
	return m(e, "formie:unload-warning:collect-exclusions", {
		form: e,
		exclude: (...e) => {
			n && e.forEach((e) => {
				typeof e == "string" && e !== "" && t.add(e);
			});
		}
	}), n = !1, t;
}
function bn(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => bn(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${bn(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function xn(e) {
	return bn(e, /* @__PURE__ */ new WeakSet());
}
function Sn(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !se(n, t) && !vn.has(n);
}
function Cn(e, t, n) {
	return xn(e.filter(([e]) => !n.has(e) && Sn(String(e || ""), t)));
}
function wn(e, t = {}) {
	let n = [], r = !1, i = !1, a = !1, o = null, s = null, c = null, l = () => {
		o !== null && (window.cancelAnimationFrame(o), o = null), s !== null && (window.clearTimeout(s), s = null), c !== null && (window.clearTimeout(c), c = null);
	}, u = () => {
		if (!r) return !1;
		let t = yn(e);
		return a = Cn(Array.from(new FormData(e).entries()), e, t) !== Cn(n, e, t), a;
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
//#region src/js/validation/rules/date-parts.ts
var Tn = [
	"year",
	"month",
	"day"
];
function En(e) {
	try {
		let t = JSON.parse(e.getAttribute("data-formie-validation") || "[]");
		return Array.isArray(t) ? t.find((e) => e && typeof e == "object" && e.type === "dateParts") ?? null : null;
	} catch {
		return null;
	}
}
function Dn(e) {
	let t = (e?.closest("[data-formie-field-handle]"))?.parentElement?.closest("[data-formie-field-handle]");
	if (!t) return null;
	let n = En(t);
	return n ? {
		field: t,
		rule: n
	} : null;
}
function On(e, t) {
	return e.querySelector(`input[name$="[${t}]"]:not([disabled]), select[name$="[${t}]"]:not([disabled])`);
}
function kn(e) {
	if (e instanceof HTMLSelectElement) {
		let t = Array.from(e.options);
		return (t.find((e) => e.defaultSelected) ?? t[0])?.value ?? "";
	}
	return e.defaultValue;
}
function An(e) {
	return Tn.map((t) => On(e, t)).filter((e) => !!e).sort((e, t) => e.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1).pop() ?? null;
}
function jn(e, t) {
	let n = On(e, t)?.value.trim() ?? "";
	return n === "" ? null : Number.parseInt(n, 10);
}
function Mn(e) {
	let [t, n, r] = Tn.map((t) => jn(e, t));
	if (t === null || n === null || r === null) return null;
	let i = jn(e, "hour") ?? 0, a = On(e, "ampm")?.value.toUpperCase();
	a === "AM" && i === 12 ? i = 0 : a === "PM" && i < 12 && (i += 12);
	let o = new Date(t, n - 1, r, i, jn(e, "minute") ?? 0, jn(e, "second") ?? 0);
	return o.getFullYear() !== t || o.getMonth() !== n - 1 || o.getDate() !== r ? null : o;
}
function Nn(e, t) {
	return t.querySelector("input[name=\"submissionId\"]")?.value ? Array.from(e.querySelectorAll("input[name], select[name]")).every((e) => e.value === kn(e)) : !1;
}
function Pn({ field: e, rule: t }, n) {
	let r = Mn(e);
	if (!r || Nn(e, n)) return null;
	let i = typeof t.minDate == "string" ? new Date(t.minDate) : null, a = typeof t.maxDate == "string" ? new Date(t.maxDate) : null;
	return i && Number.isFinite(i.getTime()) && r < i ? "dateMin" : a && Number.isFinite(a.getTime()) && r > a ? "dateMax" : null;
}
function Fn(e) {
	let t = Dn(e.input);
	return t && An(t.field) === e.input ? t : null;
}
var In = {
	rule: (e) => {
		let t = Fn(e);
		return !t || Pn(t, e.form) === null;
	},
	message: (e) => {
		let t = Fn(e), n = t ? Pn(t, e.form) : null, r = t?.rule.messages ?? {}, i = n === "dateMax" ? t?.rule.maxDate : t?.rule.minDate;
		return n && r[n] ? r[n] : e.t(n === "dateMax" ? "The date must be on or before {date}." : "The date must be on or after {date}.", { date: typeof i == "string" ? new Date(i).toLocaleDateString() : "" });
	}
}, Ln = {
	rule: ({ input: e, getRule: t }) => {
		let n = t("email");
		return !n || i(e.value, {
			...typeof n == "object" ? n : {},
			type: "email"
		}) === null;
	},
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function Rn(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function zn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !M(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var Bn = {
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
			return i(e.value, { type: "required" }) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? n("{label} cannot be blank.", { label: t })
	},
	email: Ln,
	url: {
		rule: ({ input: e, getRule: t }) => {
			let n = t("url");
			return !n || i(e.value, {
				...typeof n == "object" ? n : {},
				type: "url"
			}) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? n("{label} is not a valid URL.", { label: t })
	},
	number: {
		rule: ({ input: e, getRule: t }) => {
			let n = t("number");
			return !n || i(e.value, {
				...typeof n == "object" ? n : {},
				type: "number"
			}) === null;
		},
		message: ({ input: e, label: t, getRule: n, t: r }) => {
			let a = n("number");
			return i(e.value, {
				...typeof a == "object" ? a : {},
				type: "number"
			}, { label: t }) ?? r("{label} is not a valid number.", { label: t });
		}
	},
	match: {
		rule: (e) => {
			let t = zn(e);
			return !t || i(e.input.value, { type: "match" }, { comparison: t.value }) === null;
		},
		message: (e) => {
			let t = zn(e)?.closest("[data-formie-field-handle]"), n = Rn(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	},
	dateParts: In
}, Vn = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, U = k("general", "validator");
function W(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && (!!e.name || e.hasAttribute("data-formie-date-datepicker-input") || e.hasAttribute("data-formie-upload-manager-status"));
}
function G(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var Hn = class {
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
			patterns: Vn,
			...t
		}, Object.entries(Bn).forEach(([e, t]) => {
			this.addValidator(e, t.rule, t.message);
		}), this.init();
	}
	init() {
		U.log("Initializing validator.", {
			formId: this.form.id || null,
			live: this.config.live
		}), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((e) => {
			this.initialValues.set(e, this.getInputValue(e));
		}), this.config.live && this.addEventListeners(), this.emitEvent(document, s("ready"), { validator: this });
	}
	inputs(e = null) {
		if (W(e)) return M(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => W(e) && !M(e));
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
		this.errors = [], W(e) && (e = Dn(e)?.field ?? e);
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
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), Ue(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, s("clear-error"), { validator: this }), v(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= $e(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let a = r.getAttribute("data-formie-field-handle") || "field";
		i.id = i.id || `${e.id || `${this.form.id}-${a}`}-errors`;
		let o = `${i.id}-1`;
		ze(i, Le(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", o), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			M(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), He(t, o));
		});
		for (let e = r; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, s("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: n
		}), v(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: pe,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? pe("{label} is invalid.", { label: r.label });
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
		U.log("Destroying validator.", { formId: this.form.id || null }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, s("destroy"), { validator: this });
	}
	isVisible(e, t = {}) {
		if (e.disabled || e.hasAttribute("data-formie-conditions-disabled") || e.closest("[data-formie-conditions-disabled]") || e.closest("[data-formie-conditionally-hidden]")) return !1;
		if (e.closest("[data-formie-page-hidden]")) return !!t.includeHiddenPages;
		let n = e.closest("[data-formie-field-handle]"), r = n?.querySelector("[data-formie-rich-text]");
		if (r instanceof HTMLElement) return G(r);
		if (e instanceof HTMLSelectElement && e.hasAttribute("data-formie-combobox-input")) {
			let t = e.nextElementSibling;
			if (t instanceof HTMLElement && t.classList.contains("formie-combobox")) return G(t);
		}
		return e instanceof HTMLSelectElement && n instanceof HTMLElement ? G(e) || G(n) : G(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !M(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !M(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || (e.target instanceof HTMLSelectElement || e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio")) && (this.activated.add(e.target), this.validate(e.target)));
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !M(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
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
function Un(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function Wn(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !Un(e));
}
function Gn(e) {
	let { final: t } = S(e);
	return "submit";
}
function Kn(e) {
	return !Wn(e, Gn(e));
}
function qn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || Kn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var K = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", Jn = 300, Yn = "/actions/formie/server/forms/render", Xn = "/api", Zn = "/actions/formie/server/forms/refresh-tokens", Qn = "/actions/formie/server/submissions/submit", $n = "/actions/formie/server/submissions/set-page", er = "/actions/formie/server/submissions/clear-submission", tr = "/actions/formie/file-upload/hydrate", nr = "/actions/formie/fields/get-summary-html", q = k("general", "client"), rr = /* @__PURE__ */ new Set();
function J(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function ir(e) {
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
		staticCache: ir(t),
		autoVisible: J(t.formieAutoVisible, !0),
		compatibility: J(t.formieCompatibility, !1)
	};
}
function ar(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function or(e) {
	return e || "rest";
}
function sr(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function cr(e, t) {
	rr.has(e) || (rr.add(e), q.warn(t));
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
function lr(e, t) {
	return Z(e.endpoint || t.dataset.formieEndpoint, Yn);
}
function ur(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : X(Xn, n) : Xn;
}
function dr(e, t) {
	let n = t.dataset.formieRefreshTokensEndpoint || sr(t)?.dataset.formieRefreshTokensEndpoint;
	return n ? X(n, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, Zn);
}
function fr(e) {
	try {
		return new URL(e, window.location.href).origin !== window.location.origin;
	} catch {
		return !0;
	}
}
function pr(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = t.getAttribute("action") || t.dataset.formieSubmitEndpoint;
	t.setAttribute("action", i ? X(i, r) : Z(r, Qn)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href");
		e.setAttribute("href", t ? X(t, r) : Z(r, $n));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(e.getAttribute("data-formie-file-upload-hydrate-endpoint") || tr, r));
	});
	let a = t.getAttribute("data-formie-summary-endpoint");
	(a || r && fr(r)) && t.setAttribute("data-formie-summary-endpoint", a ? X(a, r) : Z(r, nr));
}
function mr(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function hr(e) {
	return J(e.dataset.formieAutomaticSubmissionState, !0);
}
function gr(e, t, n) {
	return n.dataset.formieClearSubmissionEndpoint ? X(n.dataset.formieClearSubmissionEndpoint, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, er);
}
function _r(e) {
	return mr(e.dataset.formieUnloadWarning);
}
function vr(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function yr(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function br(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function xr(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function Sr(e) {
	return xr(window.location.href, "resumeToken") || xr(e.getAttribute("action"), "resumeToken");
}
function Cr(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function wr(e) {
	return Math.max(0, Cr(e.dataset.formieSubmitDelay, Jn));
}
function Q(e) {
	return mr(e.dataset.formieValidationOnSubmit);
}
async function Tr(e) {
	let t = wr(e);
	await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function Er(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function Dr(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = Er(n, "data-formie-modules"), i = Er(n, "data-formie-theme-classes") || Er(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function Or(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function kr(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function $(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function Ar(e, t) {
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
async function jr(e, t) {
	let n = ar(t.mode), r = or(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!sr(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? ur(t, e) : lr(t, e), c = r === "graphql" ? await It(s, a, o, t) : await Ft(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function Mr(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await Lt(dr(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n.querySelector("input[name=\"requestToken\"]")?.value, n.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	Ar(n, i), m(e, "formie:refresh-tokens:refreshed", i);
}
function Nr(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = gr(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		e ? t.setAttribute("data-formie-pending-action", e) : t.removeAttribute("data-formie-pending-action");
	};
	if (_r(t)) {
		let n = wn(t, { shouldWarn: () => !br(t) }), r = (e) => {
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
			n.preventDefault();
			let r = n.currentTarget, a = r?.getAttribute("data-formie-page-id"), o = r?.getAttribute("href");
			if (a && o) {
				if (m(e, "formie:page:navigate", {
					pageId: a,
					href: o
				}), R(t)) jt(t, a, i, Q(t));
				else try {
					let n = await Rt(o, t, a);
					if (n.pageId && p(t, String(n.pageId)), !n.success) {
						let { form: e = [], ...r } = n.errors ?? {};
						V(t), yt(t, r), bt(t, e), F(t);
						return;
					}
					V(t), h(t), m(e, "formie:page:navigate:after", {
						pageId: a,
						href: o,
						response: n
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), m(e, "formie:page:navigate:error", {
						pageId: a,
						href: o,
						error: t
					});
				}
			}
		};
		n.addEventListener("click", r), a.push(() => {
			n.removeEventListener("click", r);
		});
	}), !hr(t)) {
		let e = !1, n = () => {
			e || br(t) || Sr(t) || (e = !0, zt(s, t));
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
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), p = t.getAttribute("data-formie-pending-action"), h = t.querySelector("input[name=\"submitAction\"]"), g = d || p || h?.value || "submit";
		if (R(t) && At(t, g, i, Q(t))) {
			u(null);
			return;
		}
		let _ = null, v = !1;
		try {
			if (s) _ = await nn({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Q(t),
				action: g,
				submitter: l,
				waitForSubmitDelay: Tr,
				onRefreshTokensAfterSubmit: async () => {
					await Mr(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					m(e, "formie:submit:result", t);
				}
			}), R(t) && !_.ok && Mt(t);
			else {
				if (V(t), w(t, l), await Tr(t), _ = await Qt(t, g, r, {
					validator: i,
					validateOnSubmit: Q(t),
					preflightOnly: !0
				}), _.ok) {
					f(t, g), c = !0, vr(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, yr(t), C(t), i && Q(t)) {
							let { scope: e, final: n } = S(t), r = i.submit(n ? t : e, { final: n });
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
					if (y(t), typeof t.requestSubmit == "function") {
						t.addEventListener("invalid", n, !0);
						try {
							t.requestSubmit();
						} finally {
							t.removeEventListener("invalid", n, !0);
						}
					} else t.submit();
					if (e) return;
					v = !0;
					return;
				}
				L(t, _), R(t) && Mt(t), m(e, "formie:submit:result", _), yr(t);
			}
		} catch (n) {
			c = !1, _ = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, L(t, _), m(e, "formie:submit:result", _), yr(t);
		} finally {
			u(null), !s && !v && !tn(_) && C(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	}), R(t) && Mt(t);
}
async function Pr(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = dr(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await Lt(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value, n?.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	o && n && (Ar(n, o), m(e, "formie:refresh-tokens:after", o));
}
function Fr() {
	let n = /* @__PURE__ */ new Map(), r = new H(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
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
			q.log("Unmount requested.", { target: $(e) });
			let t = i.get(e);
			t && (t(), i.delete(e));
			let r = n.get(e);
			r ? (m(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null, await r.modules.destroy(), r.bus.clear(), n.delete(e), m(e, "formie:unmount:after", { id: r.instance.id }), r.unbindEventContext(), q.log("Unmount complete.", {
				id: r.instance.id,
				target: $(e)
			})) : q.log("Unmount skipped (no mounted state).", { target: $(e) });
		})().finally(() => {
			a.delete(e);
		});
		a.set(e, r), await r;
	}, c = async (a, c) => {
		q.log("Mount requested.", {
			target: $(a),
			mode: c.mode,
			autoVisible: c.autoVisible
		});
		let l = i.get(a);
		l && (l(), i.delete(a));
		let u = n.get(a);
		if (u) return q.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: $(a)
		}), u.instance;
		let d = new rn(), f = [], p = a?.id || `formie-${n.size + 1}`, h = Y(a), _ = {
			...h,
			...c,
			mode: ar(c.mode ?? h.mode),
			transport: or(c.transport ?? h.transport)
		}, y = await jr(a, _), b = sr(a);
		c.compatibility === void 0 && b?.hasAttribute("data-formie-compatibility") && (_.compatibility = Y(b).compatibility);
		let ee = ke(_.compatibility);
		b && e(b, _), _.staticCache = c.staticCache ?? ir(b ? b.dataset : a.dataset);
		let x;
		try {
			x = Dr(a, b), y?.modules && t(y.modules), x?.modules && t(x.modules);
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
		let S = y || x ? {
			...y || {},
			...x || {}
		} : null, C = S?.theme, ne = {}, w = S?.modules ?? {
			contractVersion: 2,
			surface: "server-rendered",
			entries: []
		};
		t(w), q.log("Resolved mount payload.", {
			target: $(a),
			hasRenderPayload: !!y,
			hasEmbeddedPayload: !!x,
			moduleCount: w.entries.length
		});
		let T = oe(a, C, b), E = b ? new Hn(b, {
			live: mr(b.dataset.formieValidationOnFocus),
			errorAriaLive: P(b),
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
			m(b, "formie:validator:ready", t), m(a, "formie:validator:ready", t);
		}
		b && (te(b), (y || _.endpoint || a.dataset.formieEndpoint) && pr(a, b, _), _.mode === "server-rendered" && Xe(b) && (Ye(b), F(b)), v(b)), Object.keys(T).length && m(a, "formie:theme:applied", { hasClasses: !0 }), b?.querySelector("craft-csrf-input") && await de("craft-csrf-input", b);
		let D = await _n(w, {
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
				emit: (e, t) => (m(a, e, t), d.emitSafe(e, t).then((t) => {
					t.failed.length > 0 && q.warn("Lifecycle listeners failed.", {
						eventName: e,
						failed: t.failed.length
					});
				}))
			}
		});
		q.log("Module setup complete.", {
			target: $(a),
			moduleInstances: D.instances.length
		});
		let O = {
			id: p,
			root: a,
			submit: async (e = "submit") => {
				if (q.log("Submit requested.", {
					id: p,
					target: $(a),
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
				let n = b.querySelector(`[data-formie-action="${e}"]`), r = await nn({
					id: p,
					target: a,
					form: b,
					bus: d,
					validator: E,
					validateOnSubmit: Q(b),
					action: e,
					submitter: n,
					waitForSubmitDelay: Tr,
					onRefreshTokensAfterSubmit: async () => {
						await Mr(a, _, b);
					},
					dispatchSubmitResult: (e) => {
						m(a, "formie:submit:result", e);
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
		}, ae = ie(a, b, O);
		if (b && (Ie({
			target: a,
			form: b,
			validatorDetail: E ? {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			} : null,
			options: ee,
			unbinds: f
		}), Pe({
			target: a,
			form: b,
			instance: O,
			options: ee,
			unbinds: f
		})), b) {
			Nr(a, b, _, d, E, f), E && (f.push(g(b, E, a)), f.push(qn(b)));
			try {
				await Pr(a, _, b);
			} catch (e) {
				throw ae(), e;
			}
		}
		o.forEach((e) => {
			let t = d.on(`formie:stage:${e}:before`, async (t) => {
				m(a, `formie:stage:${e}:before`, t);
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
				m(a, `formie:stage:${e}:after`, t);
			});
			f.push(t, n, r);
		});
		let k = d.on("formie:submit:before", async (e) => {
			m(a, "formie:submit:before", e);
		}), A = d.on("formie:submit:after", async (e) => {
			if (m(a, "formie:submit:after", e), !b) return;
			let t = e;
			if (t.code === "PREFLIGHT_COMPLETE") return;
			let n = {
				form: b,
				action: t.action ?? "submit",
				formData: new FormData(b)
			};
			for (let e of D.instances) await e.afterSubmit?.(n, t);
		}), j = d.on("formie:submit:final:before", async (e) => {
			m(a, "formie:submit:final:before", e);
		}), M = d.on("formie:submit:final:after", async (e) => {
			m(a, "formie:submit:final:after", e);
		});
		return f.push(k, A, j, M), n.set(a, {
			options: _,
			bus: d,
			form: b,
			validator: E,
			modules: D,
			unbinds: f,
			instance: O,
			unbindEventContext: ae
		}), b instanceof HTMLFormElement && _.mode === "server-rendered" && Xe(b) && Ze(b), m(a, "formie:mount:after", {
			id: p,
			mode: _.mode,
			instance: O
		}), b instanceof HTMLFormElement && re(b), q.log("Mount complete.", {
			id: p,
			target: $(a),
			mode: _.mode
		}), O;
	}, l = (e, t) => {
		if (!t.autoVisible || Or(e) || typeof IntersectionObserver > "u") return c(e, t);
		if (n.has(e)) return Promise.resolve(n.get(e)?.instance || null);
		if (i.has(e)) return q.log("Mount deferred (already waiting visibility).", { target: $(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((n) => {
			n.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), q.log("Visibility reached, proceeding mount.", { target: $(e) }), c(e, {
				...t,
				autoVisible: !1
			}));
		}, { threshold: .01 });
		return r.observe(e), i.set(e, () => {
			r.disconnect();
		}), q.log("Mount deferred until visible.", { target: $(e) }), Promise.resolve(null);
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
			let i = t.payload?.theme || r.options.payload?.theme || Dr(e, r.form)?.theme, a = oe(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && m(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => n.get(e)?.instance || null,
		refreshForCache: async (e) => {
			cr("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let t = null;
			if (t = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !t) {
				q.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = n.get(t), i = sr(t), a = r?.options || Y(t);
			if (!i) {
				q.warn("refreshForCache found no form element for target.", { target: $(t) });
				return;
			}
			let o = a.formHandle || t.dataset.formieHandle || i.dataset.formieHandle, s = dr(a, t), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				q.warn("refreshForCache found no form handle for target.", { target: $(t) });
				return;
			}
			let l = await Lt(s, o, c, a, i.querySelector("input[name=\"requestToken\"]")?.value, i.querySelector("input[name=\"siteId\"]")?.value || a.siteId);
			l && (Ar(i, l), m(t, "formie:refresh-tokens:after", l));
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
						e instanceof Element && (e.matches(K) && (q.log("Observer detected new root.", { target: $(e) }), l(e, Y(e))), e.querySelectorAll(K).forEach((e) => {
							q.log("Observer detected new nested root.", { target: $(e) }), l(e, Y(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (n.has(e) && (q.log("Observer detected removed root.", { target: $(e) }), s(e)), e.querySelectorAll(K).forEach((e) => {
							n.has(e) && (q.log("Observer detected removed nested root.", { target: $(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(t, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), q.log("Observer stopped."), i.forEach((e, n) => {
					kr(n, t) && (e(), i.delete(n));
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
var Ir = k("general", "module-hydrator");
async function Lr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.surface ?? "cp-edit", i = e.modules ?? {
		contractVersion: 2,
		surface: r,
		entries: []
	}, a = e.registry ?? new H(), o = new rn(), s = await _n(i, {
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
	return Ir.log("Hydrated module manifest.", {
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
function Rr(e) {
	return e instanceof Element;
}
function zr(e) {
	return e.ok;
}
function Br(e) {
	return typeof e == "string" ? `selector "${e}"` : Rr(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function Vr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) Rr(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function Hr(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : Rr(e) ? [e] : Vr(e);
}
function Ur() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function Wr(e) {
	let t = Hr(e);
	return t.length > 0 || typeof e != "string" ? t : (await Ur(), Hr(e));
}
function Gr(e) {
	return typeof e == "string" ? document : Rr(e) ? e.getRootNode() : document;
}
function Kr(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function qr(e, t, n, r) {
	let i = [], a = Kr(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), c = [];
		if (e.onReady?.(s), c.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), zr(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
		})), e.onEvent) for (let t of u) c.push(s.on(t, (n) => {
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
async function Jr(e) {
	let t = e.client ?? Fr(), n = /* @__PURE__ */ new Map(), r = await Wr(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${Br(e.element)}.`);
	await qr(e, t, n, r);
	let i = e.observe ? t.observe(Gr(e.element)) : null;
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
			let r = Hr(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : qr(e, t, n, r);
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
var Yr = "form[data-formie-craft-form]:not([data-formie-init=\"false\"])", Xr = /* @__PURE__ */ new WeakMap();
function Zr(e) {
	let t = Xr.get(e);
	if (t) return t;
	let n = JSON.parse(e.dataset.formieThemeClasses || "{}"), r = oe(e, n), i = e.getAttribute("novalidate"), a = new Hn(e, {
		live: e.dataset.formieValidationOnFocus === "true",
		errorAriaLive: P(e),
		fieldContainerErrorClass: r.fieldLayoutError || [],
		inputErrorClass: r.fieldControlError || [],
		messagesClass: r.fieldErrors || [],
		messageClass: r.fieldError || []
	}), o = (t) => {
		let n = t.submitter;
		t.defaultPrevented || i !== null || e.dataset.formieValidationOnSubmit === "false" || n?.hasAttribute("formnovalidate") || a.submit().length && (t.preventDefault(), F(e));
	}, s = (t) => {
		queueMicrotask(() => {
			!t.defaultPrevented && Xr.has(e) && a.resetLiveState();
		});
	};
	e.addEventListener("submit", o), e.addEventListener("reset", s);
	let c = {
		validator: a,
		owners: /* @__PURE__ */ new Set(),
		destroy: () => {
			e.removeEventListener("submit", o), e.removeEventListener("reset", s), a.removeEventListeners(), a.resetLiveState(), i === null ? e.removeAttribute("novalidate") : e.setAttribute("novalidate", i), Xr.delete(e);
		}
	};
	return Xr.set(e, c), e.dispatchEvent(new CustomEvent("formie:validator:ready", {
		bubbles: !0,
		detail: {
			validator: a,
			addValidator: a.addValidator.bind(a),
			removeValidator: a.removeValidator.bind(a)
		}
	})), c;
}
function Qr({ root: e = document, observe: t = !1 } = {}) {
	let n = Symbol("craftForms"), r = /* @__PURE__ */ new Map(), i = !1, a = (e, t) => {
		t.owners.delete(n), t.owners.size || t.destroy(), r.delete(e);
	}, o = () => {
		if (i) return;
		let t = new Set(e.querySelectorAll(Yr));
		e instanceof HTMLFormElement && e.matches(Yr) && t.add(e);
		for (let [e, n] of r) t.has(e) || a(e, n);
		for (let e of t) if (!r.has(e)) try {
			let t = Zr(e);
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
var $r = {
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
	"checkbox-radio": {
		kind: "field",
		reason: "Framework controls own checked state."
	},
	"text-limit": {
		kind: "field",
		reason: "Core checks the limits and adapters show the counter."
	},
	calculations: {
		kind: "field",
		reason: "Core works out calculated answers from the form values."
	},
	table: {
		kind: "field",
		reason: "The adapter owns table rows and core owns row values."
	}
};
async function ei(t, r, i = an) {
	for (let [e, t] of Object.entries($r)) {
		let n = `formie:${e}`;
		i.get(n) || i.register({
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
	let a = t instanceof HTMLFormElement ? t : t.querySelector("form"), o = () => {
		if (!a) return;
		let t = r.getState();
		e(a, r.getBrowserRequestOptions()), a.action = t.definition.submission.endpoint, t.session.tokens.csrf && a.setAttribute("data-formie-csrf-param", t.session.tokens.csrf.name);
		let n = t.session.tokens, i = t.session.continuation ?? {}, o = Object.fromEntries(Object.entries({
			renderId: n.render,
			uploadCreateToken: n.uploadCreate,
			draftContext: i.draftContext,
			draftContextToken: i.draftContextToken
		}).filter((e) => typeof e[1] == "string" && e[1] !== ""));
		for (let [e, n] of Object.entries({
			handle: t.definition.handle,
			...o,
			...t.session.tokens.csrf ? { [t.session.tokens.csrf.name]: t.session.tokens.csrf.value } : {}
		})) {
			let t = a.querySelector(`input[type="hidden"][name="${CSS.escape(e)}"]`);
			t || (t = document.createElement("input"), t.type = "hidden", t.name = e, a.append(t)), t.value = n;
		}
	};
	o();
	let s = r.subscribe(o), c = {
		read: (e) => n(r.getState(), e),
		subscribe: (e) => r.subscribe(() => e())
	}, l = [t, a].filter((e) => !!e);
	l.forEach((e) => b(e, c));
	let u = await Lr({
		root: t,
		modules: r.getState().definition.modules,
		surface: "client-rendered",
		registry: i
	});
	r.setBrowserModuleGuard(u.assertReady), r.setBrowserModulePreparation(u.prepare);
	let d = r.on("formie:submit:result", (e) => {
		let t = e, n = t.completion;
		t.success && n && (n.behavior === "redirect" && typeof n.url == "string" ? n.target === "new-tab" ? window.open(n.url, "_blank", "noopener,noreferrer") : window.location.assign(n.url) : n.behavior === "reload" ? window.location.reload() : n.behavior === "reset" && r.reset()), u.result({
			ok: t.success,
			outcome: t.outcome,
			version: t.version,
			submissionUid: t.submissionUid,
			errors: t.errors,
			session: t.session,
			completion: t.completion,
			meta: t
		}).then(() => {
			let e = typeof t.payment?.action?.event == "string" ? t.payment.action.event : "";
			a && e && a.dispatchEvent(new CustomEvent(e, {
				bubbles: !0,
				detail: { data: t.payment?.action?.payload }
			}));
			let n = t.payment?.action?.type === "redirect" && typeof t.payment.action.url == "string" ? t.payment.action.url : null, r = !t.success && !e.startsWith("formie:payment:") ? n ?? t.redirect?.url ?? null : null;
			r && (t.redirect?.target === "new-tab" ? window.open(r, "_blank", "noopener,noreferrer") : window.location.assign(r));
		});
	});
	return {
		...u,
		get instances() {
			return u.instances;
		},
		get failures() {
			return u.failures;
		},
		destroy: async () => {
			d(), s(), l.forEach((e) => b(e, null)), await u.destroy();
		}
	};
}
//#endregion
export { u as FORMIE_HTML_EVENT_NAMES, Hn as FormieValidator, De as LEGACY_FORMIE_DOM_EVENT_BRIDGES, Oe as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, H as ModuleRegistry, Pe as bindLegacyDomEventCompatibility, Ie as bindLegacyValidatorCompatibility, we as buildFieldValueRegistry, an as clientRenderedModuleRegistry, Qr as craftForms, k as createDebug, Fr as createFormieClient, O as debugLog, ae as debugWarn, Ee as defineAddressModule, ve as defineCaptchaModule, _e as definePassiveCaptchaModule, ne as definePaymentModule, xe as fieldKeyToInputName, Jr as formie, l as getFieldModuleEventName, ge as getFormieTranslations, c as getGlobalModuleLifecycleEventName, a as getScopedModuleLifecycleEventName, Lr as hydrateFormieModules, ye as inputNameToFieldKey, D as isFormieDebugEnabled, me as mergeFormieTranslations, ei as mountClientRenderedModules, be as normalizeFieldKey, d as normalizeFormieEventName, Se as parseFieldReference, Te as resolveFieldReferenceFromFormData, Ce as resolveFieldReferenceLive, ke as resolveLegacyCompatibilityOptions, E as setFormieDebugEnabled, he as setFormieTranslations, pe as t, o as toDomEventName, fe as translate };
