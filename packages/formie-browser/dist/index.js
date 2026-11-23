import { n as e } from "./chunks/request-profile-DhwkeCpS.js";
import { c as t, n, o as r } from "./chunks/dist-B4m_9Rxd.js";
import { c as i, d as a, l as o, o as s, r as c, t as l, u } from "./chunks/event-names-BCI2FLD8.js";
import { _ as d, a as f, b as p, c as m, d as h, f as g, g as _, h as v, i as y, l as b, m as x, o as S, p as ee, s as te, t as ne, u as re, v as ie, y as C } from "./chunks/api-C0EExO1E.js";
import { a as w, i as T, n as E, r as D, t as O } from "./chunks/debug-rhzqP1j2.js";
import { i as k, r as ae, t as A } from "./chunks/theme-classes-DAQuEqdP.js";
import { n as j } from "./chunks/skip-CEW89ExY.js";
import { i as oe, t as se } from "./chunks/csrf-DxHg_ZYt.js";
import { n as ce, t as le } from "./chunks/http-8DCiu7P8.js";
import { i as ue } from "./chunks/async-2GdXNFCE.js";
import { a as de, i as fe, n as pe, r as me, t as he } from "./chunks/i18n-BY1ds1BL.js";
import { n as ge, t as _e } from "./chunks/api-BPtH5RBA.js";
import { n as ve, r as ye, t as be } from "./chunks/field-references.keys-58ZSTrCW.js";
import { i as xe, n as Se, r as Ce, t as we } from "./chunks/field-references.resolver-Bu3jDHC2.js";
import { t as Te } from "./chunks/api-CmuOZ0i3.js";
//#region src/js/compatibility/event-map.ts
var Ee = [
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
], De = [
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
function Oe(e) {
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
function ke(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Ae(e, t) {
	if (e.canonicalEvent !== "formie:submit:result") return !0;
	let n = t;
	return e.legacyEvent === "onAfterFormieSubmit" ? !!n?.ok : e.legacyEvent !== "onFormieSubmitError" || n?.ok === !1;
}
function je(e, t) {
	let n = t && typeof t == "object" ? t : {}, r = typeof n.pageId == "string" ? n.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]"));
	return { data: {
		nextPageId: r,
		nextPageIndex: i.findIndex((e) => e.getAttribute("data-formie-page-id") === r),
		totalPages: i.length
	} };
}
function Me(e, t, n, r, i) {
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
	} : e.legacyEvent === "onFormiePageToggle" ? je(r, t) : t;
}
function Ne({ target: e, form: t, instance: n, options: r, unbinds: i }) {
	r.legacyDomEvents && Ee.forEach((r) => {
		let o = (i) => {
			i instanceof CustomEvent && Ae(r, i.detail) && ke(r.target === "document" ? document : t, r.legacyEvent, Me(r, i.detail, e, t, n));
		};
		e.addEventListener(a(r.canonicalEvent), o), i.push(() => {
			e.removeEventListener(a(r.canonicalEvent), o);
		});
	});
}
//#endregion
//#region src/js/compatibility/validator-adapter.ts
function M(e, t, n) {
	e.dispatchEvent(new CustomEvent(t, {
		bubbles: !0,
		detail: n
	}));
}
function Pe(e, t) {
	return !!e && typeof e == "object" && e.validator === t;
}
function Fe({ target: e, form: t, validatorDetail: n, options: r, unbinds: i }) {
	if (!r.legacyValidatorEvents || !n) return;
	let { validator: a, addValidator: o, removeValidator: s } = n, c = {
		...n,
		form: t,
		target: e
	};
	M(document, "formieValidatorInitialized", c);
	let l = (e) => {
		e instanceof CustomEvent && Pe(e.detail, a) && M(document, "formieValidatorDestroyed", {
			...c,
			...e.detail
		});
	}, u = (n) => {
		n instanceof CustomEvent && Pe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && M(n.target, "formieValidatorShowError", {
			...n.detail,
			addValidator: o,
			removeValidator: s,
			form: t,
			target: e
		});
	}, d = (n) => {
		n instanceof CustomEvent && Pe(n.detail, a) && n.target instanceof Element && t.contains(n.target) && M(n.target, "formieValidatorClearError", {
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
function Ie(e, t) {
	return e === "off" ? null : t ? e : "polite";
}
function Le(e) {
	return e === "off" ? null : e;
}
function Re(e, t) {
	t ? (e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true")) : (e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic"));
}
//#endregion
//#region src/js/core/field-error-aria.ts
function ze(e, t) {
	let n = (e.getAttribute("aria-describedby") || "").trim(), r = n ? n.split(/\s+/) : [];
	r.includes(t) || r.push(t), e.setAttribute("aria-describedby", r.join(" ").trim());
}
function Be(e, t = document) {
	let n = (e.getAttribute("aria-describedby") || "").trim();
	if (!n) return;
	let r = n.split(/\s+/).filter((e) => !!e && !!t.getElementById(e));
	r.length ? e.setAttribute("aria-describedby", r.join(" ")) : e.removeAttribute("aria-describedby");
}
function Ve(e, t) {
	e.setAttribute("aria-errormessage", t), ze(e, t);
}
function He(e, t = []) {
	t.forEach((t) => {
		e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
	}), !t.length && e.hasAttribute("aria-errormessage") && e.removeAttribute("aria-errormessage"), Be(e);
}
//#endregion
//#region src/js/core/validation-focus.ts
function Ue(e) {
	return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((e) => e.getAttribute("data-formie-field-has-error") === "true" || e.querySelector("[data-formie-field-error]") !== null) || null;
}
function We(e) {
	if (e instanceof HTMLTextAreaElement) {
		let t = e.richText;
		if (t) return t.content;
	}
	return e;
}
function Ge(e) {
	for (let t of e) {
		let e = We(t);
		if (!(e instanceof HTMLInputElement && e.type === "hidden") && e.getClientRects().length > 0) return e;
	}
	return null;
}
function Ke(e) {
	return Ge(Array.from(e.querySelectorAll("[aria-invalid=\"true\"]")).filter((e) => !j(e))) || Ge(Array.from(e.querySelectorAll("input:not([type=\"hidden\"]):not([disabled]), select:not([disabled]), textarea:not([disabled])")));
}
function qe(e) {
	return e.querySelector("[data-formie-message-error], [data-formie-error-container], [data-formie-errors]");
}
function Je(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = Array.from(n.querySelectorAll("[data-formie-field-error]")), i = r.some((e) => e.closest("[data-formie-field-handle]") === n), a = n.getAttribute("data-formie-field-has-error") === "true" && r.length === 0;
		if (!i && !a) return;
		n.setAttribute("data-formie-field-has-error", "true"), A(n, e, "fieldLayoutError");
		let o = n.querySelector("[data-formie-field-error]")?.id || "";
		n.querySelectorAll("input, select, textarea").forEach((t) => {
			let r = t;
			if (j(r)) return;
			r.setAttribute("aria-invalid", "true"), A(r, e, "fieldControlError"), r.setAttribute("data-formie-input-has-error", "true"), o && Ve(r, o);
			let i = n.querySelector("[data-formie-instructions]");
			i?.id && ze(r, i.id);
		});
	});
}
function Ye(e) {
	return !!Ue(e) || !!qe(e);
}
function Xe(e) {
	let t = document.activeElement;
	(!t || t === document.body || t.getClientRects().length === 0) && P(e);
}
function P(e) {
	let t = Ue(e);
	if (t) {
		let e = Ke(t);
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
	let n = qe(e);
	return n ? (n.scrollIntoView({
		behavior: "smooth",
		block: "center"
	}), !0) : !1;
}
//#endregion
//#region src/js/core/field-error-container.ts
function Ze(e) {
	return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function Qe(e, t) {
	let n = e.querySelector("[data-formie-field-errors]");
	if (n) return n;
	let r = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), a = Ze(e), o = document.createElement("div");
	return o.setAttribute("data-formie-field-errors", "true"), t?.(o), r && i ? a === "above" ? r.insertBefore(o, i) : r.appendChild(o) : e.appendChild(o), o;
}
//#endregion
//#region src/js/theme/message-attributes.ts
function $e(e, t, n, r = !1) {
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
var F = /* @__PURE__ */ new WeakMap();
function et(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function tt(e) {
	return (e.dataset.formieErrorMessagePosition ?? "top-form").trim();
}
function nt(e) {
	return (e.dataset.formieSuccessMessagePosition || e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function rt(e) {
	let t = (e.dataset.formieSuccessMessageTimeout || e.dataset.formieSubmitActionMessageTimeout || "").trim();
	if (!t) return null;
	let n = Number.parseFloat(t);
	return !Number.isFinite(n) || n < 0 ? null : Math.round(n * 1e3);
}
function it(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function at(e) {
	let t = F.get(e);
	typeof t == "number" && (window.clearTimeout(t), F.delete(e));
}
function ot(e) {
	return e.querySelector("[data-formie-form-messages-top]") || e;
}
function st(e) {
	return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function ct(e, t) {
	return t === "bottom-form" ? st(e) : ot(e);
}
function lt(e, t) {
	return t === "top-form" ? ot(e) : t === "bottom-form" && !it(e) ? st(e) : e;
}
function ut(e) {
	let t = tt(e), n = ct(e, t), r = n.querySelector("[data-formie-error-container], [data-formie-errors]");
	return r || (r = document.createElement("div"), r.setAttribute("data-formie-errors", "true"), A(r, e, "errors")), r.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function dt(e, t) {
	let n = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-error-message-container", "true"), t.appendChild(n)), $e(n, e, "messageError"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-error", "true"), A(n, e, "message", "messageError"), n.setAttribute("role", "alert"), Re(n, Le(N(e))), n;
}
function ft(e, t) {
	let n = e.querySelector("[data-formie-success-container]"), r = lt(e, t);
	return n || (n = document.createElement("div"), n.setAttribute("data-formie-success-container", "true"), A(n, e, "successes")), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function pt(e) {
	return Qe(e, (t) => {
		A(t, e, "fieldErrors");
	});
}
function mt(e) {
	e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
		let n = t, r = n.querySelector("[data-formie-field-errors]"), i = Array.from(n.querySelectorAll("[data-formie-field-error]")).map((e) => e.id).filter(Boolean);
		k(n, e, "fieldLayoutError"), n.removeAttribute("data-formie-field-has-error"), n.querySelectorAll("[data-formie-field-error]").forEach((e) => {
			e.remove();
		}), r && !r.querySelector("[data-formie-field-error]") && (r.innerHTML = ""), n.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.removeAttribute("aria-invalid"), k(n, e, "fieldControlError"), n.removeAttribute("data-formie-input-has-error"), He(n, i);
		});
	}), v(e);
}
function ht(e) {
	e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-error]").forEach((e) => {
			e.remove();
		}), k(n, e, "message", "messageError"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-error"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-error]") || (n.innerHTML = "");
	});
}
function gt(e) {
	at(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((e) => {
		e.remove();
	}), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
		let n = t;
		n.querySelectorAll("[data-formie-success]").forEach((e) => {
			e.remove();
		}), $e(n, e, "messageSuccess", !0), k(n, e, "message", "messageSuccess"), n.removeAttribute("data-formie-message"), n.removeAttribute("data-formie-message-success"), n.removeAttribute("role"), n.removeAttribute("aria-live"), n.removeAttribute("aria-atomic"), n.querySelector("[data-formie-success]") || (n.innerHTML = "");
	}), et(e) === "message" && it(e) || m(e, !1);
}
function _t(e) {
	e.querySelectorAll("[aria-invalid=\"true\"]").forEach((e) => {
		e.removeAttribute("aria-invalid");
	});
}
function vt(e, t) {
	let n = Le(N(e));
	Object.entries(t).forEach(([t, r]) => {
		let i = `fields[${t.split(".").join("][")}]`, a = e.querySelector(`[name="${CSS.escape(i)}"], [name="${CSS.escape(i + "[]")}"]`)?.closest("[data-formie-field-handle]") || e.querySelector(`[data-formie-field-handle="${CSS.escape(t)}"]`);
		if (!a) return;
		let o = pt(a), s = o.id && o.id.trim() ? o.id : `${t}-errors`;
		o.id = s, Re(o, n), A(a, e, "fieldLayoutError"), a.setAttribute("data-formie-field-has-error", "true"), r.forEach((t, n) => {
			let r = document.createElement("div");
			r.setAttribute("data-formie-field-error", "true"), r.id = `${s}-${n + 1}`, A(r, e, "fieldError"), r.textContent = t, o.appendChild(r);
		});
		let c = o.querySelector("[data-formie-field-error]")?.id;
		a.querySelectorAll("input, select, textarea").forEach((t) => {
			let n = t;
			n.setAttribute("aria-invalid", "true"), A(n, e, "fieldControlError"), n.setAttribute("data-formie-input-has-error", "true"), c && Ve(n, c);
			let r = a.querySelector("[data-formie-instructions]");
			r?.id && ze(n, r.id);
		});
	}), v(e);
}
function yt(e, t) {
	if (!tt(e)) return;
	let n = ut(e), r = dt(e, n);
	A(n, e, "errors"), t.forEach((t) => {
		let n = document.createElement("div");
		n.setAttribute("data-formie-error", "true"), A(n, e, "error"), n.innerHTML = t, r.appendChild(n);
	});
}
function bt(e) {
	if (e.ok || e.keepSubmitLoading !== !0) return !1;
	let t = e.payment?.status;
	return t === "requiresAction" || t === "pending" || t === "unknown";
}
function xt(e, t) {
	let n = ut(e), r = dt(e, n);
	A(n, e, "errors");
	let i = document.createElement("div");
	i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), A(i, e, "message"), i.textContent = t, r.appendChild(i);
}
function St(e, t) {
	return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" || et(e) === "message" && nt(e) !== "";
}
function Ct(e, t, n = {}) {
	let r = nt(e);
	if (!r) return;
	let i = ft(e, r);
	$e(i, e, "messageSuccess"), A(i, e, "message", "messageSuccess"), i.setAttribute("data-formie-message", "true"), i.setAttribute("data-formie-message-success", "true"), i.setAttribute("role", "status"), i.setAttribute("aria-live", "polite"), i.setAttribute("aria-atomic", "true");
	let a = document.createElement("div");
	a.setAttribute("data-formie-success", "true"), A(a, e, "success"), a.innerHTML = t, i.appendChild(a), n.hideForm !== !1 && it(e) && m(e, !0);
	let o = e.dataset.formieScrollToTop?.trim().toLowerCase();
	o !== void 0 && [
		"",
		"true",
		"1"
	].includes(o) && i.scrollIntoView({
		behavior: "smooth",
		block: "start"
	});
	let s = rt(e);
	if (s !== null) {
		let t = window.setTimeout(() => {
			F.delete(e), gt(e);
		}, s);
		F.set(e, t);
	}
}
function I(e, t) {
	if (mt(e), ht(e), gt(e), _t(e), t.ok) {
		let n = t.meta?.summaryTokens;
		n && typeof n == "object" && e.querySelectorAll("[data-formie-field-uid]").forEach((e) => {
			let t = n[e.dataset.formieFieldUid || ""];
			typeof t == "string" && t && e.querySelector("[data-formie-summary-container]")?.setAttribute("data-formie-summary-token", t);
		}), St(e, t) && Ct(e, t.message || "", { hideForm: t.action !== "save" });
	} else if (!t.ok) {
		if (bt(t)) {
			let n = String(t.payment?.message || "").trim();
			n && xt(e, n);
			return;
		}
		t.fieldErrors && vt(e, t.fieldErrors), t.formErrors?.length ? yt(e, t.formErrors) : !t.fieldErrors && t.message && yt(e, [t.message]), P(e);
	}
}
//#endregion
//#region src/js/core/revise-pages.ts
function L(e) {
	return e.hasAttribute("data-formie-revise");
}
function wt(e) {
	return h(e).filter((e) => !e.hasAttribute("data-formie-conditionally-hidden"));
}
function Tt(e) {
	return h(e).find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
}
function Et(e) {
	return !!e.querySelector("[data-formie-field-has-error]");
}
function Dt(e, t, n, r) {
	if (!r || !n) return !0;
	let i = n.submit(t, { final: !1 });
	return !i.length || (I(e, {
		ok: !1,
		stage: "validate",
		code: "VALIDATION_FAILED",
		message: n.config.errorMessage || "Validation failed.",
		fieldErrors: n.getFieldErrors(i),
		formErrors: [n.config.errorMessage || "Validation failed."]
	}), P(e), !1);
}
function Ot(e, t, n) {
	let r = t.getAttribute("data-formie-page-id");
	r && (B(e), n?.resetLiveState(), y(e, r), te(e), C(e, "formie:page:navigate:after", { pageId: r }));
}
function kt(e, t, n, r) {
	if (t !== "submit" && t !== "back") return !1;
	let i = wt(e), a = Tt(e), o = a ? i.indexOf(a) : -1;
	if (!a || o === -1) return !1;
	if (t === "back") return o > 0 && Ot(e, i[o - 1], n), !0;
	let s = i[o + 1];
	return s ? (Dt(e, a, n, r) && (_(e, t), Ot(e, s, n)), !0) : !1;
}
function At(e, t, n, r) {
	let i = wt(e), a = Tt(e), o = i.find((e) => e.getAttribute("data-formie-page-id") === t);
	o && o !== a && (a && i.indexOf(o) > i.indexOf(a) && !Dt(e, a, n, r) || Ot(e, o, n));
}
function jt(e) {
	let t = Tt(e);
	if (t && Et(t)) return;
	let n = wt(e).find(Et)?.getAttribute("data-formie-page-id");
	n && (y(e, n), P(e));
}
//#endregion
//#region src/js/transport/forms-api.ts
var R = O("general", "transport");
function Mt(e) {
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
function Nt(e, t) {
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
async function Pt(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		handle: t,
		renderOptions: n
	});
	R.log("requestRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await ce(e, {
		...r,
		method: "POST",
		body: i,
		headers: { "Content-Type": "application/json" }
	});
	return R.log("requestRender complete.", { hasHtml: !!a.html }), a;
}
async function Ft(e, t, n = {}, r = {}) {
	let i = JSON.stringify({
		query: "\nquery FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {\n  formieHtmlForm(handle: $handle, input: $input) {\n    html\n  }\n}",
		variables: {
			handle: t,
			input: Mt(n)
		}
	});
	R.log("requestGraphqlRender start.", {
		endpoint: e,
		handle: t
	});
	let a = await ce(e, {
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
async function It(e, t, n, r = {}, i, a) {
	let o = new URL(e, window.location.origin);
	o.searchParams.set("handle", t), a !== void 0 && a !== "" && o.searchParams.set("siteId", String(a)), n && o.searchParams.set("renderId", n), i && o.searchParams.set("requestToken", i), R.log("requestRefreshTokens start.", {
		endpoint: o.toString(),
		handle: t,
		hasRenderId: !!n
	});
	let s = await ce(o.toString(), r);
	return R.log("requestRefreshTokens complete.", { hasRefreshTokens: !!s.refreshTokens }), s.refreshTokens || s;
}
async function Lt(e, t, n) {
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
		}), se(i, t);
		for (let [e, n] of new FormData(t)) e.startsWith("fields[") && i.append(e, n);
	}
	R.log("requestSetPage start.", {
		requestUrl: r.toString(),
		pageId: n || null
	});
	let a = await (await le(r.toString(), {
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
function Rt(e, n) {
	let r = new URL(e, window.location.origin), i = new FormData();
	[
		"handle",
		"renderId",
		"draftContextToken",
		"draftContext"
	].forEach((e) => {
		let t = n.querySelector(`input[name="${e}"]`)?.value?.trim();
		t && i.append(e, t);
	}), se(i, n), R.log("clearSubmissionOnUnload start.", { requestUrl: r.toString() });
	try {
		if (n.dataset.formieRequestProfile !== "cross-origin-public" && typeof navigator.sendBeacon == "function" && navigator.sendBeacon(r.toString(), i)) return;
	} catch {}
	t(r.toString(), {
		method: "POST",
		body: i,
		keepalive: !0,
		headers: { Accept: "application/json" }
	}, { profile: n.dataset.formieRequestProfile });
}
async function zt(e, n) {
	let r = (e.getAttribute("method") || "POST").toUpperCase(), i = e.getAttribute("action") || window.location.href, a = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
	R.log("submitForm start.", {
		method: r,
		action: i,
		submitAction: n.get("submitAction")
	});
	let o = await t(i, {
		method: r,
		body: n,
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
	let c = Nt(await o.json(), a);
	return R.log("submitForm JSON response normalized.", {
		ok: c.ok,
		code: c.code,
		hasRedirect: !!c.redirect?.url,
		hasSubmitData: Array.isArray(c.submitData) && c.submitData.length > 0
	}), c;
}
//#endregion
//#region src/js/submit/pipeline.ts
var Bt = [
	"prepare",
	"validate",
	"challenge",
	"payment",
	"send",
	"result"
], Vt = [
	"prepare",
	"validate",
	"challenge",
	"payment"
], z = O("general", "pipeline");
function Ht(e, t) {
	return {
		ok: !1,
		stage: e,
		code: "ABORTED",
		message: t || "Submission aborted.",
		formErrors: [t || "Submission aborted."]
	};
}
function Ut(e) {
	return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function Wt(e) {
	return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function Gt(e, t) {
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
function Kt(e, t) {
	t.querySelectorAll("input, select, textarea").forEach((t) => {
		let n = Ut(t) ? t : null;
		n && !n.closest("[data-formie-page]") && Wt(n) && Gt(e, n);
	});
}
function qt(e, t) {
	let n = /* @__PURE__ */ new Set();
	return t.querySelectorAll("input, select, textarea").forEach((t) => {
		let r = Ut(t) ? t : null;
		r && r.name && !r.disabled && (r instanceof HTMLInputElement && (r.type === "submit" || r.type === "button" || r.type === "reset" || r.type === "image") || (r.name.startsWith("fields[") && n.add(r.name), Wt(r) && Gt(e, r)));
	}), n;
}
function Jt(e, t) {
	t.forEach((t) => {
		e.has(t) || e.append(t, "");
	});
}
function Yt(e, t) {
	let n = h(e), r = n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || null;
	if (!n.length || !r) {
		let n = new FormData(e);
		return n.set("submitAction", t), n;
	}
	let i = new FormData();
	Kt(i, e);
	let a = /* @__PURE__ */ new Set();
	return (L(e) ? n : [r]).forEach((e) => {
		qt(i, e).forEach((e) => {
			a.add(e);
		});
	}), Jt(i, a), i.set("submitAction", t), i;
}
function Xt(e, t) {
	if (t !== "submit") return !1;
	let n = h(e);
	return !n.length || (n.find((e) => !e.hasAttribute("data-formie-page-hidden")) || n[n.length - 1]) === n[n.length - 1];
}
async function Zt(e, t, n, r = {}) {
	z.log("Starting submit pipeline.", {
		action: t,
		preflightOnly: r.preflightOnly === !0
	});
	let i = !1, a, o = null, s = Xt(e, t), c = p(e, {
		form: e,
		action: t,
		formData: Yt(e, t),
		abort: (e) => {
			i = !0, a = e, z.warn("Pipeline aborted.", { reason: e });
		},
		isAborted: () => i,
		abortReason: () => a
	}), l = (t, r) => n.emitSafe(t, p(e, r)), u = {
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
			x(e.form), e.formData = Yt(e.form, e.action);
			let t = await zt(e.form, e.formData);
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
	let d = r.preflightOnly ? Vt : Bt;
	for (let e of d) {
		if (z.log("Stage start.", {
			stage: e,
			action: t
		}), i) return z.warn("Stage skipped due to abort.", {
			stage: e,
			reason: a
		}), Ht(e, a);
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
			let t = Ht(e, a);
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
			let t = Ht(e, a);
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
//#region src/js/core/submit-flow.ts
var Qt = O("general", "submit-flow");
function $t(e) {
	return !(!e.ok && e.stage === "validate");
}
function en(e) {
	return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function B(e) {
	mt(e), ht(e), gt(e), _t(e);
}
async function tn(e) {
	let { id: t, target: n, form: r, bus: i, validator: a, validateOnSubmit: o, action: s, submitter: c, waitForSubmitDelay: l, onRefreshTokensAfterSubmit: u, dispatchSubmitResult: d } = e;
	B(r), b(r, c || null);
	let p = {
		ok: !1,
		code: "SUBMIT_ERROR",
		message: "Submission failed.",
		formErrors: ["Submission failed."]
	};
	try {
		await l(r), p = await Zt(r, s, i, {
			validator: a,
			validateOnSubmit: o
		}), I(r, p), d(p), f(r, p, s), $t(p) && await u(p);
	} catch (e) {
		p = {
			ok: !1,
			code: "SUBMIT_ERROR",
			message: e instanceof Error ? e.message : "Submission failed.",
			formErrors: [e instanceof Error ? e.message : "Submission failed."]
		}, I(r, p), d(p), Qt.warn("Submit failed with exception.", {
			id: t,
			action: s,
			target: n,
			error: e instanceof Error ? e.message : e
		});
	} finally {
		en(p) || S(r);
	}
	return p;
}
//#endregion
//#region src/js/events/event-bus.ts
var nn = class {
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
}, V = class {
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
}, rn = new V(), an = {
	"address-finder": () => import("./chunks/address-finder-ChSNCcm_.js").then((e) => e.addressFinderModule),
	"google-address": () => import("./chunks/google-address-D_pduOIB.js").then((e) => e.googleAddressModule),
	loqate: () => import("./chunks/loqate-BdJ6dlN4.js").then((e) => e.loqateModule),
	"place-kit": () => import("./chunks/place-kit-BUOGAm6N.js").then((e) => e.placeKitModule)
}, on = {
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
}, sn = {
	calculations: () => import("./chunks/calculations-BGfybpew.js").then((e) => e.calculationsModule),
	"checkbox-radio": () => import("./chunks/checkbox-radio-CSXo6svm.js").then((e) => e.checkboxRadioModule),
	combobox: () => import("./chunks/combobox-By3jpAIt.js").then((e) => e.comboboxModule),
	conditions: () => import("./chunks/conditions-D3SeC6op.js").then((e) => e.conditionsModule),
	"custom-google-maps": () => import("./chunks/custom-google-maps-rNm5VFbb.js").then((e) => e.customGoogleMapsModule),
	"custom-link": () => import("./chunks/custom-link-CLqFCssQ.js").then((e) => e.customLinkModule),
	"custom-maps": () => import("./chunks/custom-maps-uV8HdZBv.js").then((e) => e.customMapsModule),
	"date-picker": () => import("./chunks/date-picker-CGPnDsea.js").then((e) => e.datePickerModule),
	"file-upload": () => import("./chunks/file-upload-BUT8jB8U.js").then((e) => e.fileUploadModule),
	"upload-manager": () => import("./chunks/upload-manager-BnCurBhH.js").then((e) => e.uploadManagerModule),
	hidden: () => import("./chunks/hidden-Bc2TpcgF.js").then((e) => e.hiddenModule),
	"phone-country": () => import("./chunks/phone-country-D_by3T1w.js").then((e) => e.phoneCountryModule),
	"password-validation": () => import("./chunks/password-validation-CZ1Dn8qS.js").then((e) => e.passwordValidationModule),
	"address-country": () => import("./chunks/address-country-BS0Px1Pp.js").then((e) => e.addressCountryModule),
	"address-state": () => import("./chunks/address-state-cB-z4bAY.js").then((e) => e.addressStateModule),
	repeater: () => import("./chunks/repeater-Lk2Gl3jn.js").then((e) => e.repeaterModule),
	"rich-text": () => import("./chunks/rich-text-BKkG6TDM.js").then((e) => e.richTextModule),
	signature: () => import("./chunks/signature-BbCtOQuJ.js").then((e) => e.signatureModule),
	summary: () => import("./chunks/summary-CkKF-y3X.js").then((e) => e.summaryModule),
	"survey-likert": () => import("./chunks/survey-likert-Cs6ZyCzO.js").then((e) => e.surveyLikertModule),
	"survey-rank": () => import("./chunks/survey-rank-BR3gY0Wx.js").then((e) => e.surveyRankModule),
	"survey-rating": () => import("./chunks/survey-rating-CHgNcPVS.js").then((e) => e.surveyRatingModule),
	table: () => import("./chunks/table-cRBJYL_P.js").then((e) => e.tableModule),
	"text-limit": () => import("./chunks/text-limit-CrSb7asC.js").then((e) => e.textLimitModule)
}, cn = {
	bpoint: () => import("./chunks/bpoint-DtqI_x0p.js").then((e) => e.bpointModule),
	eway: () => import("./chunks/eway-Cy22gLQl.js").then((e) => e.ewayModule),
	"go-cardless": () => import("./chunks/go-cardless-8dmgNUnM.js").then((e) => e.goCardlessModule),
	mollie: () => import("./chunks/mollie-opPpqo72.js").then((e) => e.mollieModule),
	moneris: () => import("./chunks/moneris-DisE2Ywg.js").then((e) => e.monerisModule),
	opayo: () => import("./chunks/opayo-tCnvFawZ.js").then((e) => e.opayoModule),
	paddle: () => import("./chunks/paddle-h8-tUdNs.js").then((e) => e.paddleModule),
	paypal: () => import("./chunks/paypal-Bs8DzhHE.js").then((e) => e.paypalModule),
	payway: () => import("./chunks/payway-Do5DZ2q8.js").then((e) => e.paywayModule),
	square: () => import("./chunks/square-BpwafOr_.js").then((e) => e.squareModule),
	stripe: () => import("./chunks/stripe-BozcPsgz.js").then((e) => e.stripeModule)
}, ln = {
	...sn,
	...an,
	...on,
	...cn
}, H = /* @__PURE__ */ new Map();
function un(e) {
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
function dn(e, t, n) {
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
async function fn(e) {
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
async function pn(e, t) {
	let n = t.get(e);
	if (n) return n;
	let r = e.startsWith("formie:") && Object.prototype.hasOwnProperty.call(ln, e.slice(7)) ? ln[e.slice(7)] : void 0;
	if (!r) throw Error(`Browser module ${e} is not registered.`);
	H.has(e) || H.set(e, r().catch((t) => {
		throw H.delete(e), t;
	}));
	let i = await H.get(e);
	if (i.moduleId !== e) throw Error(`Module definition does not match ${e}.`);
	return t.register(i), i;
}
function mn(e, t, n) {
	return [...new Set(e.targets.flatMap((e) => {
		if (e.type === "form") return [n || t];
		let r = e.type === "selector" ? e.selector : e.type === "field" ? `[data-formie-field-uid="${CSS.escape(e.uid)}"]` : e.type === "page" ? `[data-formie-page-id="${CSS.escape(e.id)}"]` : `[data-formie-action="${CSS.escape(e.action)}"]`;
		return [...t.matches(r) ? [t] : [], ...t.querySelectorAll(r)];
	}))].filter((e) => !e.closest("[hidden], [data-formie-hidden=\"true\"], [data-formie-conditionally-hidden], [data-formie-page-hidden]"));
}
async function hn(e, t) {
	r(e);
	let n = e.surface;
	if (t.matchContext.surface !== n || t.setupContext.surface !== n) throw Error(`Browser module manifest surface ${n} cannot mount as ${t.matchContext.surface}.`);
	let { root: i, form: a } = t.setupContext, o = /* @__PURE__ */ new Map(), s = /* @__PURE__ */ new Map(), c = /* @__PURE__ */ new Map(), l = [], u = !1, d = Promise.resolve(), f = !1, p = async (e, r) => {
		let i = {
			key: e.key,
			moduleId: e.moduleId,
			required: e.required,
			surface: n,
			code: "MODULE_UNAVAILABLE",
			message: "A form feature could not start. Reload the page or contact the site administrator."
		};
		s.set(e.key, i), c.set(e.key, un(r)), console.error("[formie] Browser module failure", i, r), await t.setupContext.emit("formie:browser:module:error", i);
	}, m = async (e) => {
		try {
			await e.destroy();
		} catch (r) {
			console.error("[formie] Browser module disposal failed", r), await t.setupContext.emit("formie:browser:module:error", {
				key: e.key,
				moduleId: e.moduleId,
				surface: n,
				code: "MODULE_DISPOSE_FAILED",
				message: "A form feature could not clean up. Reload the page before continuing."
			});
		}
	}, h = () => [...s.values()].some((e) => e.required), g = () => {
		if (!a) return;
		let e = a.querySelector("[data-formie-module-error]");
		if (!e) {
			let t = document.createElement("p");
			t.textContent = n === "cp-edit" ? "Reload the page. If the problem continues, copy the technical details below." : "Reload the page or contact the site administrator.";
			let r = document.createElement("pre");
			if (r.dataset.formieModuleErrorDetails = "true", r.tabIndex = 0, n === "cp-edit") e = document.createElement("pk-alert"), e.className = "formie-module-error", e.setAttribute("variant", "error"), e.setAttribute("announce", "assertive"), e.setAttribute("heading", "A required form feature could not start."), e.setAttribute("details-label", "Details"), e.setAttribute("copy-label", "Copy details"), e.setAttribute("copied-label", "Details copied."), e.setAttribute("copyable", ""), r.slot = "details", e.append(t, r);
			else {
				e = document.createElement("div"), e.className = "formie-module-error formie-module-error--fallback";
				let n = document.createElement("div");
				n.className = "formie-module-error__notice", n.setAttribute("role", "alert");
				let i = document.createElement("span");
				i.className = "formie-module-error__indicator", i.setAttribute("aria-hidden", "true"), i.textContent = "!";
				let a = document.createElement("div");
				a.className = "formie-module-error__content";
				let o = document.createElement("strong");
				o.className = "formie-module-error__title", o.textContent = "A required form feature could not start.", a.append(o, t), n.append(i, a);
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
						await fn(r.textContent || ""), d.textContent = "Details copied.";
					} catch {
						d.textContent = "Copy failed. Select the details above and copy them manually.";
						let e = document.createRange();
						e.selectNodeContents(r), window.getSelection()?.removeAllRanges(), window.getSelection()?.addRange(e);
					}
				}), l.append(u, d), s.append(c, r, l), e.append(n, s);
			}
			e.dataset.formieModuleError = "true", i !== a && a.contains(i) ? i.before(e) : a.prepend(e);
		}
		let t = e.querySelector("[data-formie-module-error-details]"), r = dn(n, s.values(), c);
		t && t.textContent !== r && (t.textContent = r);
	}, _ = (e) => {
		h() && (e.preventDefault(), e.stopImmediatePropagation(), g());
	}, v = async () => {
		let r = new Set(e.entries.map((e) => e.key));
		for (let e of s.keys()) r.has(e) || (s.delete(e), c.delete(e));
		let d = [];
		for (let [e, t] of o) r.has(e) || (d.push(...Array.from(t.values(), ({ instance: e }) => e)), o.delete(e), s.delete(e), c.delete(e));
		for (let e of d.reverse()) await m(e), l.splice(l.indexOf(e), 1);
		for (let r of e.entries) {
			if (u) continue;
			let e = s.get(r.key);
			e && s.set(r.key, {
				...e,
				required: r.required
			});
			let d;
			try {
				d = mn(r, i, a);
			} catch (e) {
				s.has(r.key) || await p(r, e);
				continue;
			}
			let f = o.get(r.key) ?? /* @__PURE__ */ new Map();
			o.set(r.key, f);
			for (let [e, t] of Array.from(f.entries()).reverse()) d.includes(e) || (await m(t.instance), f.delete(e), l.splice(l.indexOf(t.instance), 1));
			let h;
			try {
				h = await pn(r.moduleId, t.registry);
			} catch (e) {
				s.has(r.key) || await p(r, e);
				continue;
			}
			let g = !1, _ = !1;
			for (let e of d) {
				if (u) return;
				let a = JSON.stringify([
					r.moduleId,
					r.config,
					r.required
				]), o = f.get(e);
				if (o?.config === a) continue;
				let c = {
					...t.setupContext,
					target: e,
					entryKey: r.key,
					surface: n,
					scope: r.targets[0]?.type ?? "form",
					options: r.config
				};
				try {
					if (o) {
						if (o.instance.update && o.moduleId === r.moduleId && o.required === r.required) {
							await o.instance.update(c), o.config = a;
							continue;
						}
						await m(o.instance), f.delete(e), l.splice(l.indexOf(o.instance), 1);
					}
					if (h.surfaces && !h.surfaces.includes(n)) throw Error(`Module ${r.moduleId} does not support ${n}.`);
					if (h.kind !== r.kind) throw Error(`Module ${r.moduleId} is registered as ${h.kind}, not ${r.kind}.`);
					if (!h.match({
						...t.matchContext,
						target: e,
						scope: c.scope,
						manifestItem: r
					})) {
						if (h.kind === "field" || h.kind === "core") {
							_ = !0;
							continue;
						}
						throw Error(`Module ${r.moduleId} does not support the rendered target.`);
					}
					let s = await h.setup(c);
					if (!s) throw Error(`Module ${r.moduleId} did not initialize.`);
					if (u || !i.contains(e) && e !== i) {
						await m(s);
						continue;
					}
					s.key = r.key, s.moduleId = r.moduleId, s.kind = r.kind, s.target = e;
					let d = s.assertReady;
					s.assertReady = () => {
						try {
							d?.();
						} catch (e) {
							if (p(r, e), r.required) throw Error("A required form feature could not start.");
						}
					};
					let g = s.beforeSubmit, v = s.afterSubmit;
					s.beforeSubmit = async (e) => {
						try {
							await g?.(e);
						} catch (t) {
							await p(r, t), r.required && e.abort("A required form feature could not complete. Reload the page or contact the site administrator.");
						}
					}, s.afterSubmit = async (e, t) => {
						try {
							await v?.(e, t);
						} catch (e) {
							await p(r, e);
						}
					}, f.set(e, {
						instance: s,
						config: a,
						moduleId: r.moduleId,
						required: r.required
					}), l.push(s), _ = !0, await t.setupContext.emit("formie:browser:module:mount", {
						key: r.key,
						moduleId: r.moduleId,
						target: e
					});
				} catch (e) {
					g = !0, s.has(r.key) || await p(r, e);
				}
			}
			!g && (_ || d.length === 0) && (s.delete(r.key), c.delete(r.key));
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
			if (r(t), t.surface !== n) throw Error("A mounted browser module runtime cannot change surfaces.");
			e = t, d = d.then(v), await d;
		}
	};
}
//#endregion
//#region src/js/utils/unload-warning.ts
var gn = /* @__PURE__ */ new Set([
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
function _n(e) {
	let t = /* @__PURE__ */ new Set(), n = !0;
	return C(e, "formie:unload-warning:collect-exclusions", {
		form: e,
		exclude: (...e) => {
			n && e.forEach((e) => {
				typeof e == "string" && e !== "" && t.add(e);
			});
		}
	}), n = !1, t;
}
function vn(e, t) {
	if (e == null) return String(e);
	if (typeof e == "string") return JSON.stringify(e);
	if (typeof e == "number" || typeof e == "boolean") return String(e);
	if (typeof e == "function") return "[function]";
	if (typeof File < "u" && e instanceof File) return `[file:${e.name}:${e.size}:${e.type}]`;
	if (typeof Blob < "u" && e instanceof Blob) return `[blob:${e.size}:${e.type}]`;
	if (Array.isArray(e)) return `[${e.map((e) => vn(e, t)).join(",")}]`;
	if (typeof e == "object") {
		if (t.has(e)) return "[circular]";
		t.add(e);
		let n = Object.entries(e).sort(([e], [t]) => e.localeCompare(t)).map(([e, n]) => `${JSON.stringify(e)}:${vn(n, t)}`);
		return t.delete(e), `{${n.join(",")}}`;
	}
	return JSON.stringify(String(e));
}
function yn(e) {
	return vn(e, /* @__PURE__ */ new WeakSet());
}
function bn(e, t) {
	if (!e) return !1;
	let n = e.endsWith("[]") ? e.slice(0, -2) : e;
	return !oe(n, t) && !gn.has(n);
}
function xn(e, t, n) {
	return yn(e.filter(([e]) => !n.has(e) && bn(String(e || ""), t)));
}
function Sn(e, t = {}) {
	let n = [], r = !1, i = !1, a = !1, o = null, s = null, c = null, l = () => {
		o !== null && (window.cancelAnimationFrame(o), o = null), s !== null && (window.clearTimeout(s), s = null), c !== null && (window.clearTimeout(c), c = null);
	}, u = () => {
		if (!r) return !1;
		let t = _n(e);
		return a = xn(Array.from(new FormData(e).entries()), e, t) !== xn(n, e, t), a;
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
var Cn = {
	rule: ({ input: e, getRule: t }) => {
		let r = t("email");
		return !r || n(e.value, {
			...typeof r == "object" ? r : {},
			type: "email"
		}) === null;
	},
	message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? n("{label} is not a valid email address.", { label: t })
};
//#endregion
//#region src/js/validation/rules/shared.ts
function wn(e) {
	return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function Tn(e) {
	let t = e.getRule("match");
	if (!t || t === !0 || typeof t != "object" || !e.field) return null;
	let n = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
	if (!n) return null;
	let r = e.form.querySelector(`[data-formie-field-handle="${n}"]`);
	return r ? Array.from(r.querySelectorAll(e.config.fieldsSelector)).find((e) => (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && !j(e)) ?? null : null;
}
//#endregion
//#region src/js/validation/rules.ts
var En = {
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
			return n(e.value, { type: "required" }) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? n("{label} cannot be blank.", { label: t })
	},
	email: Cn,
	url: {
		rule: ({ input: e, getRule: t }) => {
			let r = t("url");
			return !r || n(e.value, {
				...typeof r == "object" ? r : {},
				type: "url"
			}) === null;
		},
		message: ({ input: e, label: t, t: n }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? n("{label} is not a valid URL.", { label: t })
	},
	number: {
		rule: ({ input: e, getRule: t }) => {
			let r = t("number");
			return !r || n(e.value, {
				...typeof r == "object" ? r : {},
				type: "number"
			}) === null;
		},
		message: ({ input: e, label: t, getRule: r, t: i }) => {
			let a = r("number");
			return n(e.value, {
				...typeof a == "object" ? a : {},
				type: "number"
			}, { label: t }) ?? i("{label} is not a valid number.", { label: t });
		}
	},
	match: {
		rule: (e) => {
			let t = Tn(e);
			return !t || n(e.input.value, { type: "match" }, { comparison: t.value }) === null;
		},
		message: (e) => {
			let t = Tn(e)?.closest("[data-formie-field-handle]"), n = wn(t);
			return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
				label: e.label,
				value: n
			});
		}
	}
}, Dn = {
	email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
	url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
	number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
	color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
	date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
	time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
	month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, U = O("general", "validator");
function W(e) {
	return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement) && (!!e.name || e.hasAttribute("data-formie-date-datepicker-input") || e.hasAttribute("data-formie-upload-manager-status"));
}
function G(e) {
	return !!(e.offsetWidth || e.offsetHeight || e.getClientRects().length);
}
var On = class {
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
			patterns: Dn,
			...t
		}, Object.entries(En).forEach(([e, t]) => {
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
		if (W(e)) return j(e) ? [] : [e];
		let t = e || this.form;
		return Array.from(t.querySelectorAll(this.config.fieldsSelector)).filter((e) => W(e) && !j(e));
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
			t.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && t.classList.remove(...this.config.inputErrorClass), t.removeAttribute("data-formie-input-has-error"), He(t, r);
		});
		for (let e = t; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.remove(...this.config.fieldContainerErrorClass), e.removeAttribute("data-formie-field-has-error");
		this.emitEvent(e, o("clear-error"), { validator: this }), v(this.form);
	}
	showError(e, t, n) {
		let r = e.closest("[data-formie-field-handle]");
		if (!r) return;
		let i = r.querySelector("[data-formie-field-errors]");
		i ||= Qe(r, (e) => {
			this.config.messagesClass.length && e.classList.add(...this.config.messagesClass);
		}), this.config.messagesClass.length && i.classList.add(...this.config.messagesClass), i.innerHTML = "";
		let a = r.getAttribute("data-formie-field-handle") || "field";
		i.id = i.id || `${e.id || `${this.form.id}-${a}`}-errors`;
		let s = `${i.id}-1`;
		Re(i, Ie(this.config.errorAriaLive, this.submitted));
		let c = document.createElement("div");
		c.setAttribute("data-formie-field-error", "true"), c.setAttribute(`data-formie-field-error-${t}`, "true"), c.setAttribute("id", s), this.config.messageClass.length && c.classList.add(...this.config.messageClass), c.textContent = n, i.appendChild(c), r.setAttribute("data-formie-field-has-error", "true"), r.querySelectorAll("input, select, textarea").forEach((e) => {
			let t = e;
			j(t) || (t.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && t.classList.add(...this.config.inputErrorClass), t.setAttribute("data-formie-input-has-error", "true"), Ve(t, s));
		});
		for (let e = r; e; e = e.parentElement?.closest("[data-formie-field-handle]")) this.config.fieldContainerErrorClass.length && e.classList.add(...this.config.fieldContainerErrorClass), e.setAttribute("data-formie-field-has-error", "true");
		this.emitEvent(e, o("show-error"), {
			validator: this,
			validatorName: t,
			errorMessage: n
		}), v(this.form);
	}
	getValidatorCallbackOptions(e) {
		let t = e.closest("[data-formie-field-handle]"), n = t?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", r = this.parseValidationRules(t?.getAttribute("data-formie-validation"));
		return {
			t: fe,
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
		return (typeof n.errorMessage == "function" ? n.errorMessage(r) : n.errorMessage) ?? fe("{label} is invalid.", { label: r.label });
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
		let n = e.closest("[data-formie-field-handle]"), r = n?.querySelector("[data-formie-rich-text]");
		if (r instanceof HTMLElement) return G(r);
		if (e instanceof HTMLSelectElement && e.hasAttribute("data-formie-combobox-input")) {
			let t = e.nextElementSibling;
			if (t instanceof HTMLElement && t.classList.contains("formie-combobox")) return G(t);
		}
		return e instanceof HTMLSelectElement && n instanceof HTMLElement ? G(e) || G(n) : G(e);
	}
	blurHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !j(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && e.target.type === "file" || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || (this.isDirty(e.target) && this.activated.add(e.target), this.shouldShowError(e.target) && this.validate(e.target)));
	}
	changeHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !j(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || (e.target instanceof HTMLSelectElement || e.target instanceof HTMLInputElement && (e.target.type === "file" || e.target.type === "checkbox" || e.target.type === "radio")) && (this.activated.add(e.target), this.validate(e.target)));
	}
	inputHandler(e) {
		e.target instanceof HTMLElement && W(e.target) && !j(e.target) && e.target.form?.isSameNode(this.form) && (e instanceof CustomEvent || e.target instanceof HTMLInputElement && (e.target.type === "checkbox" || e.target.type === "radio") || this.shouldShowError(e.target) && this.validate(e.target));
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
function kn(e) {
	return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function An(e, t) {
	let n = e.querySelectorAll(`[data-formie-action="${t}"]`);
	return Array.from(n).some((e) => !kn(e));
}
function jn(e) {
	let { final: t } = g(e);
	return "submit";
}
function Mn(e) {
	return !An(e, jn(e));
}
function Nn(e) {
	let t = (t) => {
		if (t.key !== "Enter" || t.defaultPrevented) return;
		let n = t.target;
		(n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || Mn(e) && t.preventDefault());
	};
	return e.addEventListener("keydown", t, !0), () => {
		e.removeEventListener("keydown", t, !0);
	};
}
//#endregion
//#region src/js/core/create-formie-client.ts
var K = "[data-formie]:not([data-formie-init=\"false\"]), [data-formie-form]:not([data-formie-init=\"false\"])", Pn = 300, Fn = "/actions/formie/server/forms/render", In = "/api", Ln = "/actions/formie/server/forms/refresh-tokens", Rn = "/actions/formie/server/submissions/submit", zn = "/actions/formie/server/submissions/set-page", Bn = "/actions/formie/server/submissions/clear-submission", Vn = "/actions/formie/file-upload/hydrate", q = O("general", "client"), Hn = /* @__PURE__ */ new Set();
function J(e, t) {
	if (e == null || e === "") return t;
	let n = e.toLowerCase();
	return n !== "false" && n !== "0" && n !== "off";
}
function Un(e) {
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
		staticCache: Un(t),
		autoVisible: J(t.formieAutoVisible, !0),
		compatibility: J(t.formieCompatibility, !1)
	};
}
function Wn(e) {
	if (e && e !== "server-rendered") throw Error("@verbb/formie-browser enhances server-rendered HTML only. Use @verbb/formie-core for client-rendered forms.");
	return "server-rendered";
}
function Gn(e) {
	return e || "rest";
}
function Kn(e) {
	return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function qn(e, t) {
	Hn.has(e) || (Hn.add(e), q.warn(t));
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
function Jn(e, t) {
	return Z(e.endpoint || t.dataset.formieEndpoint, Fn);
}
function Yn(e, t) {
	let n = (e.endpoint || t.dataset.formieEndpoint || "").trim();
	return n ? n.includes("/graphql") || n.endsWith("/api") || n.includes("/actions/graphql/") ? n : X(In, n) : In;
}
function Xn(e, t) {
	let n = t.dataset.formieRefreshTokensEndpoint || Kn(t)?.dataset.formieRefreshTokensEndpoint;
	return n ? X(n, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, Ln);
}
function Zn(e, t, n) {
	let r = n.endpoint || e.dataset.formieEndpoint, i = t.getAttribute("action") || t.dataset.formieSubmitEndpoint;
	t.setAttribute("action", i ? X(i, r) : Z(r, Rn)), t.querySelectorAll("[data-formie-tab-link]").forEach((e) => {
		let t = e.getAttribute("href");
		e.setAttribute("href", t ? X(t, r) : Z(r, zn));
	}), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((e) => {
		e.setAttribute("data-formie-file-upload-hydrate-endpoint", X(e.getAttribute("data-formie-file-upload-hydrate-endpoint") || Vn, r));
	});
}
function Qn(e) {
	if (e == null) return !1;
	let t = e.trim().toLowerCase();
	return t === "true" || t === "1" || t === "";
}
function $n(e) {
	return J(e.dataset.formieAutomaticSubmissionState, !0);
}
function er(e, t, n) {
	return n.dataset.formieClearSubmissionEndpoint ? X(n.dataset.formieClearSubmissionEndpoint, e.endpoint || t.dataset.formieEndpoint) : Z(e.endpoint || t.dataset.formieEndpoint, Bn);
}
function tr(e) {
	return Qn(e.dataset.formieUnloadWarning);
}
function nr(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function rr(e) {
	e.removeAttribute("data-formie-internal-navigation");
}
function ir(e) {
	return e.getAttribute("data-formie-internal-navigation") !== null;
}
function ar(e, t) {
	if (!e) return !1;
	try {
		return new URL(e, window.location.origin).searchParams.has(t);
	} catch {
		return !1;
	}
}
function or(e) {
	return ar(window.location.href, "resumeToken") || ar(e.getAttribute("action"), "resumeToken");
}
function sr(e, t = 0) {
	if (!e) return t;
	let n = Number.parseInt(e, 10);
	return Number.isFinite(n) ? n : t;
}
function cr(e) {
	return Math.max(0, sr(e.dataset.formieSubmitDelay, Pn));
}
function Q(e) {
	return Qn(e.dataset.formieValidationOnSubmit);
}
async function lr(e) {
	let t = cr(e);
	await new Promise((e) => {
		window.setTimeout(e, t);
	});
}
function ur(e, t) {
	let n = e?.getAttribute(t)?.trim();
	if (!n) return null;
	try {
		return JSON.parse(n);
	} catch (e) {
		if (t === "data-formie-modules") throw Error("Invalid browser-module manifest JSON. Update Formie and its browser packages together.");
		return console.error(`[formie] Failed to parse ${t}.`, e), null;
	}
}
function dr(e, t) {
	let n = t || (e instanceof HTMLFormElement ? e : null);
	if (!n) return null;
	let r = ur(n, "data-formie-modules"), i = ur(n, "data-formie-theme-classes") || ur(n, "data-formie-theme");
	return !r && !i ? null : {
		modules: r || void 0,
		theme: i || void 0
	};
}
function fr(e) {
	if (!(e instanceof HTMLElement)) return !0;
	if (!e.isConnected || e.hidden || e.closest("[hidden]")) return !1;
	let t = window.getComputedStyle(e);
	return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function pr(e, t) {
	return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function $(e) {
	let t = e, n = t.id ? `#${t.id}` : "", r = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
	return `${t.tagName ? t.tagName.toLowerCase() : "element"}${n}${r}`;
}
function mr(e, t) {
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
async function hr(e, t) {
	let n = Wn(t.mode), r = Gn(t.transport);
	if (n !== "server-rendered") return null;
	if (t.payload) return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
	let i = !!Kn(e), a = t.formHandle || e.dataset.formieHandle;
	if (i || !a) return null;
	let o = {
		mode: n,
		endpoint: t.endpoint,
		locale: t.locale,
		siteId: t.siteId,
		theme: t.theme,
		themeConfig: t.themeConfig
	}, s = r === "graphql" ? Yn(t, e) : Jn(t, e), c = r === "graphql" ? await Ft(s, a, o, t) : await Pt(s, a, {
		...o,
		endpoint: s
	}, t);
	return c?.html && (e.innerHTML = c.html), c;
}
async function gr(e, t, n) {
	if (t.refreshTokens === !1) return;
	let r = t.formHandle || e.dataset.formieHandle;
	if (!r) return;
	let i = await It(Xn(t, e), r, n.querySelector("input[name=\"renderId\"]")?.value || void 0, t, n.querySelector("input[name=\"requestToken\"]")?.value, n.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	mr(n, i), C(e, "formie:refresh-tokens:refreshed", i);
}
function _r(e, t, n, r, i, a) {
	t.dataset.formieRequestProfile = n.profile ?? "same-origin-browser", n.profile === "cross-origin-public" && (t.dataset.formieSubmitMethod = "ajax");
	let o = String(t.dataset.formieSubmitMethod || "").trim().toLowerCase(), s = er(n, e, t), c = !1, l = t.querySelectorAll("[data-formie-action]"), u = (e) => {
		e ? t.setAttribute("data-formie-pending-action", e) : t.removeAttribute("data-formie-pending-action");
	};
	if (tr(t)) {
		let n = Sn(t, { shouldWarn: () => !ir(t) }), r = (e) => {
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
				if (C(e, "formie:page:navigate", {
					pageId: a,
					href: o
				}), L(t)) At(t, a, i, Q(t));
				else try {
					let n = await Lt(o, t, a);
					if (n.pageId && y(t, String(n.pageId)), !n.success) {
						let { form: e = [], ...r } = n.errors ?? {};
						B(t), vt(t, r), yt(t, e), P(t);
						return;
					}
					B(t), te(t), C(e, "formie:page:navigate:after", {
						pageId: a,
						href: o,
						response: n
					});
				} catch (t) {
					console.error("[formie] Failed to persist page navigation state.", t), C(e, "formie:page:navigate:error", {
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
	}), !$n(t)) {
		let e = !1, n = () => {
			e || ir(t) || or(t) || (e = !0, Rt(s, t));
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
		let l = a.submitter, d = l?.getAttribute("data-formie-action"), f = t.getAttribute("data-formie-pending-action"), p = t.querySelector("input[name=\"submitAction\"]"), m = d || f || p?.value || "submit";
		if (L(t) && kt(t, m, i, Q(t))) {
			u(null);
			return;
		}
		let h = null, v = !1;
		try {
			if (s) h = await tn({
				target: e,
				form: t,
				bus: r,
				validator: i,
				validateOnSubmit: Q(t),
				action: m,
				submitter: l,
				waitForSubmitDelay: lr,
				onRefreshTokensAfterSubmit: async () => {
					await gr(e, n, t);
				},
				dispatchSubmitResult: (t) => {
					C(e, "formie:submit:result", t);
				}
			}), L(t) && !h.ok && jt(t);
			else {
				if (B(t), b(t, l), await lr(t), h = await Zt(t, m, r, {
					validator: i,
					validateOnSubmit: Q(t),
					preflightOnly: !0
				}), h.ok) {
					_(t, m), c = !0, nr(t, "submit"), u(null);
					let e = !1, n = () => {
						if (e = !0, c = !1, rr(t), S(t), i && Q(t)) {
							let { scope: e, final: n } = g(t), r = i.submit(n ? t : e, { final: n });
							r.length > 0 && I(t, {
								ok: !1,
								stage: "validate",
								code: "VALIDATION_FAILED",
								message: i.config.errorMessage || "Validation failed.",
								fieldErrors: i.getFieldErrors(r),
								formErrors: [i.config.errorMessage || "Validation failed."]
							});
						}
					};
					if (x(t), typeof t.requestSubmit == "function") {
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
				I(t, h), L(t) && jt(t), C(e, "formie:submit:result", h), rr(t);
			}
		} catch (n) {
			c = !1, h = {
				ok: !1,
				code: "SUBMIT_ERROR",
				message: n instanceof Error ? n.message : "Submission failed.",
				formErrors: [n instanceof Error ? n.message : "Submission failed."]
			}, I(t, h), C(e, "formie:submit:result", h), rr(t);
		} finally {
			u(null), !s && !v && !en(h) && S(t);
		}
	};
	t.addEventListener("submit", d), a.push(() => {
		t.removeEventListener("submit", d);
	}), L(t) && jt(t);
}
async function vr(e, t, n) {
	if (t.refreshTokens === !1 || !t.staticCache) return;
	let r = t.formHandle || e.dataset.formieHandle, i = Xn(t, e), a = n?.querySelector("input[name=\"renderId\"]")?.value || void 0;
	if (!r) return;
	let o = await It(i, r, a, t, n?.querySelector("input[name=\"requestToken\"]")?.value, n?.querySelector("input[name=\"siteId\"]")?.value || t.siteId);
	o && n && (mr(n, o), C(e, "formie:refresh-tokens:after", o));
}
function yr() {
	let t = /* @__PURE__ */ new Map(), n = new V(), i = /* @__PURE__ */ new Map(), a = /* @__PURE__ */ new Map(), o = [
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
			q.log("Unmount requested.", { target: $(e) });
			let n = i.get(e);
			n && (n(), i.delete(e));
			let r = t.get(e);
			r ? (C(e, "formie:unmount:before", { id: r.instance.id }), r.unbinds.forEach((e) => {
				e();
			}), r.unbinds = [], r.validator?.destroy(), r.validator = null, await r.modules.destroy(), r.bus.clear(), t.delete(e), C(e, "formie:unmount:after", { id: r.instance.id }), r.unbindEventContext(), q.log("Unmount complete.", {
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
		let u = t.get(a);
		if (u) return q.log("Mount skipped (already mounted).", {
			id: u.instance.id,
			target: $(a)
		}), u.instance;
		let f = new nn(), p = [], m = a?.id || `formie-${t.size + 1}`, h = Y(a), g = {
			...h,
			...c,
			mode: Wn(c.mode ?? h.mode),
			transport: Gn(c.transport ?? h.transport)
		}, _ = await hr(a, g), y = Kn(a);
		c.compatibility === void 0 && y?.hasAttribute("data-formie-compatibility") && (g.compatibility = Y(y).compatibility);
		let b = Oe(g.compatibility);
		y && e(y, g), g.staticCache = c.staticCache ?? Un(y ? y.dataset : a.dataset);
		let x;
		try {
			x = dr(a, y), _?.modules && r(_.modules), x?.modules && r(x.modules);
		} catch (e) {
			if (y) {
				y.addEventListener("submit", (e) => {
					e.preventDefault(), e.stopImmediatePropagation();
				}, !0);
				let e = document.createElement("div");
				e.setAttribute("role", "alert"), e.textContent = "This form requires a compatible Formie browser package. Update Formie and its browser packages together.", y.prepend(e);
			}
			throw e;
		}
		let S = _ || x ? {
			..._ || {},
			...x || {}
		} : null, te = S?.theme, ne = {}, w = S?.modules ?? {
			contractVersion: 2,
			surface: "server-rendered",
			entries: []
		};
		r(w), q.log("Resolved mount payload.", {
			target: $(a),
			hasRenderPayload: !!_,
			hasEmbeddedPayload: !!x,
			moduleCount: w.entries.length
		});
		let T = ae(a, te, y), E = y ? new On(y, {
			live: Qn(y.dataset.formieValidationOnFocus),
			errorAriaLive: N(y),
			errorMessage: y.dataset.formieErrorMessage || "",
			fieldContainerErrorClass: T.fieldLayoutError || [],
			inputErrorClass: T.fieldControlError || [],
			messagesClass: T.fieldErrors || [],
			messageClass: T.fieldError || []
		}) : null;
		if (y && E) {
			let e = y;
			e.formieValidation = E, ne.validation = E;
			let t = {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			};
			C(y, "formie:validator:ready", t), C(a, "formie:validator:ready", t);
		}
		y && (ee(y), (_ || g.endpoint || a.dataset.formieEndpoint) && Zn(a, y, g), g.mode === "server-rendered" && Ye(y) && (Je(y), P(y)), v(y)), Object.keys(T).length && C(a, "formie:theme:applied", { hasClasses: !0 }), y?.querySelector("craft-csrf-input") && await ue("craft-csrf-input", y);
		let D = await hn(w, {
			registry: n,
			matchContext: {
				root: a,
				form: y,
				surface: w.surface
			},
			setupContext: {
				formId: m,
				root: a,
				form: y,
				target: a,
				scope: "form",
				surface: w.surface,
				state: ne,
				on: (e, t) => f.on(e, t),
				emit: (e, t) => (C(a, e, t), f.emitSafe(e, t).then((t) => {
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
			id: m,
			root: a,
			submit: async (e = "submit") => {
				if (q.log("Submit requested.", {
					id: m,
					target: $(a),
					action: e
				}), !y) return {
					ok: !1,
					code: "FORM_NOT_FOUND",
					message: "No form element found for mount target.",
					formErrors: ["No form element found for mount target."]
				};
				let t = y.querySelector("input[name=\"submitAction\"]");
				if (t && (t.value = e), y.getAttribute("data-formie-loading") === "true") return {
					ok: !1,
					code: "SUBMIT_IN_PROGRESS",
					message: "Submission already in progress.",
					formErrors: []
				};
				let n = y.querySelector(`[data-formie-action="${e}"]`), r = await tn({
					id: m,
					target: a,
					form: y,
					bus: f,
					validator: E,
					validateOnSubmit: Q(y),
					action: e,
					submitter: n,
					waitForSubmitDelay: lr,
					onRefreshTokensAfterSubmit: async () => {
						await gr(a, g, y);
					},
					dispatchSubmitResult: (e) => {
						C(a, "formie:submit:result", e);
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
			on: (e, t) => f.on(e, t)
		}, k = ie(a, y, O);
		if (y && (Fe({
			target: a,
			form: y,
			validatorDetail: E ? {
				validator: E,
				addValidator: E.addValidator.bind(E),
				removeValidator: E.removeValidator.bind(E)
			} : null,
			options: b,
			unbinds: p
		}), Ne({
			target: a,
			form: y,
			instance: O,
			options: b,
			unbinds: p
		})), y) {
			_r(a, y, g, f, E, p), E && (p.push(re(y, E, a)), p.push(Nn(y)));
			try {
				await vr(a, g, y);
			} catch (e) {
				throw k(), e;
			}
		}
		o.forEach((e) => {
			let t = f.on(`formie:stage:${e}:before`, async (t) => {
				C(a, `formie:stage:${e}:before`, t);
			}), n = f.on(`formie:stage:${e}:before`, async (t) => {
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
			}), r = f.on(`formie:stage:${e}:after`, async (t) => {
				C(a, `formie:stage:${e}:after`, t);
			});
			p.push(t, n, r);
		});
		let A = f.on("formie:submit:before", async (e) => {
			C(a, "formie:submit:before", e);
		}), j = f.on("formie:submit:after", async (e) => {
			if (C(a, "formie:submit:after", e), !y) return;
			let t = e;
			if (t.code === "PREFLIGHT_COMPLETE") return;
			let n = {
				form: y,
				action: t.action ?? "submit",
				formData: new FormData(y)
			};
			for (let e of D.instances) await e.afterSubmit?.(n, t);
		}), oe = f.on("formie:submit:final:before", async (e) => {
			C(a, "formie:submit:final:before", e);
		}), se = f.on("formie:submit:final:after", async (e) => {
			C(a, "formie:submit:final:after", e);
		});
		return p.push(A, j, oe, se), t.set(a, {
			options: g,
			bus: f,
			form: y,
			validator: E,
			modules: D,
			unbinds: p,
			instance: O,
			unbindEventContext: k
		}), y instanceof HTMLFormElement && g.mode === "server-rendered" && Ye(y) && Xe(y), C(a, "formie:mount:after", {
			id: m,
			mode: g.mode,
			instance: O
		}), y instanceof HTMLFormElement && d(y), q.log("Mount complete.", {
			id: m,
			target: $(a),
			mode: g.mode
		}), O;
	}, l = (e, n) => {
		if (!n.autoVisible || fr(e) || typeof IntersectionObserver > "u") return c(e, n);
		if (t.has(e)) return Promise.resolve(t.get(e)?.instance || null);
		if (i.has(e)) return q.log("Mount deferred (already waiting visibility).", { target: $(e) }), Promise.resolve(null);
		let r = new IntersectionObserver((t) => {
			t.some((t) => t.target === e && t.isIntersecting) && (r.disconnect(), i.delete(e), q.log("Visibility reached, proceeding mount.", { target: $(e) }), c(e, {
				...n,
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
			let i = n.payload?.theme || r.options.payload?.theme || dr(e, r.form)?.theme, a = ae(e, i, r.form);
			return r.validator && (r.validator.config.fieldContainerErrorClass = a.fieldLayoutError || [], r.validator.config.inputErrorClass = a.fieldControlError || [], r.validator.config.messagesClass = a.fieldErrors || [], r.validator.config.messageClass = a.fieldError || []), Object.keys(a).length && C(e, "formie:theme:applied", {
				hasClasses: !0,
				reason: "update"
			}), r.instance;
		},
		getInstance: (e) => t.get(e)?.instance || null,
		refreshForCache: async (e) => {
			qn("refreshForCache", "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.");
			let n = null;
			if (n = typeof e == "string" ? document.getElementById(e) || document.querySelector(`[data-formie-form-id="${e}"]`) : e, !n) {
				q.warn("refreshForCache target not found.", { targetOrId: e });
				return;
			}
			let r = t.get(n), i = Kn(n), a = r?.options || Y(n);
			if (!i) {
				q.warn("refreshForCache found no form element for target.", { target: $(n) });
				return;
			}
			let o = a.formHandle || n.dataset.formieHandle || i.dataset.formieHandle, s = Xn(a, n), c = i.querySelector("input[name=\"renderId\"]")?.value || void 0;
			if (!o) {
				q.warn("refreshForCache found no form handle for target.", { target: $(n) });
				return;
			}
			let l = await It(s, o, c, a, i.querySelector("input[name=\"requestToken\"]")?.value, i.querySelector("input[name=\"siteId\"]")?.value || a.siteId);
			l && (mr(i, l), C(n, "formie:refresh-tokens:after", l));
		},
		registerModule: (e, t) => n.register(e, t),
		unregisterModule: (e) => {
			n.unregister(e);
		},
		getRegisteredModules: () => n.getAll(),
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
						e instanceof Element && (e.matches(K) && (q.log("Observer detected new root.", { target: $(e) }), l(e, Y(e))), e.querySelectorAll(K).forEach((e) => {
							q.log("Observer detected new nested root.", { target: $(e) }), l(e, Y(e));
						}));
					}), e.removedNodes.forEach((e) => {
						e instanceof Element && (t.has(e) && (q.log("Observer detected removed root.", { target: $(e) }), s(e)), e.querySelectorAll(K).forEach((e) => {
							t.has(e) && (q.log("Observer detected removed nested root.", { target: $(e) }), s(e));
						}));
					});
				});
			});
			return r.observe(n, {
				childList: !0,
				subtree: !0
			}), () => {
				r.disconnect(), q.log("Observer stopped."), i.forEach((e, t) => {
					pr(t, n) && (e(), i.delete(t));
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
var br = O("general", "module-hydrator");
async function xr(e) {
	let t = e.root, n = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form") ?? t.querySelector("form")), r = e.surface ?? "cp-edit", i = e.modules ?? {
		contractVersion: 2,
		surface: r,
		entries: []
	}, a = e.registry ?? new V(), o = new nn(), s = await hn(i, {
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
	return br.log("Hydrated module manifest.", {
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
function Sr(e) {
	return e instanceof Element;
}
function Cr(e) {
	return e.ok;
}
function wr(e) {
	return typeof e == "string" ? `selector "${e}"` : Sr(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function Tr(e) {
	let t = /* @__PURE__ */ new Set(), n = [];
	for (let r of e) Sr(r) && !t.has(r) && (t.add(r), n.push(r));
	return n;
}
function Er(e) {
	return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : Sr(e) ? [e] : Tr(e);
}
function Dr() {
	return document.readyState === "loading" ? new Promise((e) => {
		document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
	}) : Promise.resolve();
}
async function Or(e) {
	let t = Er(e);
	return t.length > 0 || typeof e != "string" ? t : (await Dr(), Er(e));
}
function kr(e) {
	return typeof e == "string" ? document : Sr(e) ? e.getRootNode() : document;
}
function Ar(e) {
	let { element: t, observe: n, allowEmpty: r, client: i, onReady: a, onResult: o, onSuccess: s, onError: c, onEvent: l, ...u } = e;
	return {
		mode: "server-rendered",
		...u
	};
}
async function jr(e, t, n, r) {
	let i = [], a = Ar(e);
	for (let o of r) {
		let r = n.get(o);
		if (r) {
			i.push(r.instance);
			continue;
		}
		let s = await t.mount(o, a), c = [];
		if (e.onReady?.(s), c.push(s.on("formie:submit:result", (t) => {
			let n = t;
			e.onResult?.(n, s), Cr(n) ? e.onSuccess?.(n, s) : e.onError?.(n, s);
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
async function Mr(e) {
	let t = e.client ?? yr(), n = /* @__PURE__ */ new Map(), r = await Or(e.element);
	if (r.length === 0 && !e.allowEmpty) throw Error(`Formie could not find any elements for ${wr(e.element)}.`);
	await jr(e, t, n, r);
	let i = e.observe ? t.observe(kr(e.element)) : null;
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
			let r = Er(e.element);
			return r.length === 0 ? Array.from(n.values()).map(({ instance: e }) => e) : jr(e, t, n, r);
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
var Nr = "form[data-formie-craft-form]:not([data-formie-init=\"false\"])", Pr = /* @__PURE__ */ new WeakMap();
function Fr(e) {
	let t = Pr.get(e);
	if (t) return t;
	let n = JSON.parse(e.dataset.formieThemeClasses || "{}"), r = ae(e, n), i = e.getAttribute("novalidate"), a = new On(e, {
		live: e.dataset.formieValidationOnFocus === "true",
		errorAriaLive: N(e),
		fieldContainerErrorClass: r.fieldLayoutError || [],
		inputErrorClass: r.fieldControlError || [],
		messagesClass: r.fieldErrors || [],
		messageClass: r.fieldError || []
	}), o = (t) => {
		let n = t.submitter;
		t.defaultPrevented || i !== null || e.dataset.formieValidationOnSubmit === "false" || n?.hasAttribute("formnovalidate") || a.submit().length && (t.preventDefault(), P(e));
	}, s = (t) => {
		queueMicrotask(() => {
			!t.defaultPrevented && Pr.has(e) && a.resetLiveState();
		});
	};
	e.addEventListener("submit", o), e.addEventListener("reset", s);
	let c = {
		validator: a,
		owners: /* @__PURE__ */ new Set(),
		destroy: () => {
			e.removeEventListener("submit", o), e.removeEventListener("reset", s), a.removeEventListeners(), a.resetLiveState(), i === null ? e.removeAttribute("novalidate") : e.setAttribute("novalidate", i), Pr.delete(e);
		}
	};
	return Pr.set(e, c), e.dispatchEvent(new CustomEvent("formie:validator:ready", {
		bubbles: !0,
		detail: {
			validator: a,
			addValidator: a.addValidator.bind(a),
			removeValidator: a.removeValidator.bind(a)
		}
	})), c;
}
function Ir({ root: e = document, observe: t = !1 } = {}) {
	let n = Symbol("craftForms"), r = /* @__PURE__ */ new Map(), i = !1, a = (e, t) => {
		t.owners.delete(n), t.owners.size || t.destroy(), r.delete(e);
	}, o = () => {
		if (i) return;
		let t = new Set(e.querySelectorAll(Nr));
		e instanceof HTMLFormElement && e.matches(Nr) && t.add(e);
		for (let [e, n] of r) t.has(e) || a(e, n);
		for (let e of t) if (!r.has(e)) try {
			let t = Fr(e);
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
var Lr = {
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
async function Rr(t, n, r = rn) {
	for (let [e, t] of Object.entries(Lr)) {
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
		let r = t.session.tokens, a = t.session.continuation ?? {}, o = Object.fromEntries(Object.entries({
			renderId: r.render,
			uploadCreateToken: r.uploadCreate,
			draftContext: a.draftContext,
			draftContextToken: a.draftContextToken
		}).filter((e) => typeof e[1] == "string" && e[1] !== ""));
		for (let [e, n] of Object.entries({
			handle: t.definition.handle,
			...o,
			...t.session.tokens.csrf ? { [t.session.tokens.csrf.name]: t.session.tokens.csrf.value } : {}
		})) {
			let t = i.querySelector(`input[type="hidden"][name="${CSS.escape(e)}"]`);
			t || (t = document.createElement("input"), t.type = "hidden", t.name = e, i.append(t)), t.value = n;
		}
	};
	a();
	let o = n.subscribe(a), s = await xr({
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
		}).then(() => {
			let e = typeof t.payment?.action?.event == "string" ? t.payment.action.event : "";
			i && e && i.dispatchEvent(new CustomEvent(e, {
				bubbles: !0,
				detail: { data: t.payment?.action?.payload }
			}));
			let n = t.payment?.action?.type === "redirect" && typeof t.payment.action.url == "string" ? t.payment.action.url : null, r = !t.success && !e.startsWith("formie:payment:") ? n ?? t.redirect?.url ?? null : null;
			r && (t.redirect?.target === "new-tab" ? window.open(r, "_blank", "noopener,noreferrer") : window.location.assign(r));
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
export { l as FORMIE_HTML_EVENT_NAMES, On as FormieValidator, Ee as LEGACY_FORMIE_DOM_EVENT_BRIDGES, De as LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES, V as ModuleRegistry, Ne as bindLegacyDomEventCompatibility, Fe as bindLegacyValidatorCompatibility, Ce as buildFieldValueRegistry, rn as clientRenderedModuleRegistry, Ir as craftForms, O as createDebug, yr as createFormieClient, E as debugLog, D as debugWarn, Te as defineAddressModule, _e as defineCaptchaModule, ge as definePassiveCaptchaModule, ne as definePaymentModule, be as fieldKeyToInputName, Mr as formie, c as getFieldModuleEventName, he as getFormieTranslations, s as getGlobalModuleLifecycleEventName, i as getScopedModuleLifecycleEventName, xr as hydrateFormieModules, ve as inputNameToFieldKey, T as isFormieDebugEnabled, pe as mergeFormieTranslations, Rr as mountClientRenderedModules, ye as normalizeFieldKey, u as normalizeFormieEventName, xe as parseFieldReference, we as resolveFieldReferenceFromFormData, Se as resolveFieldReferenceLive, Oe as resolveLegacyCompatibilityOptions, w as setFormieDebugEnabled, me as setFormieTranslations, fe as t, a as toDomEventName, de as translate };
