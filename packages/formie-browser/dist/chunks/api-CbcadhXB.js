import { a as e, d as t, u as n } from "./event-names-BCI2FLD8.js";
import { t as r } from "./debug-BV0DvdHx.js";
import { a as i, i as a, t as o } from "./theme-classes-DAQuEqdP.js";
import { n as s, t as c } from "./async-2GdXNFCE.js";
import { i as l } from "./i18n-BY1ds1BL.js";
import { r as u, t as d } from "./field-references.keys-58ZSTrCW.js";
import { n as f, r as p } from "./field-references.resolver-DC7vOSqg.js";
//#region src/js/core/dom-events.ts
function m(e, n, r) {
	e.dispatchEvent(new CustomEvent(t(n), {
		bubbles: !0,
		detail: r
	}));
}
//#endregion
//#region src/js/core/page-client-event.ts
var h = r("general", "page-client-event"), g = "data-formie-client-event", _ = "data-formie-pending-client-events";
function ee(e) {
	return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
}
function te(e) {
	return e.querySelector("input[name=\"pageId\"]")?.value?.trim() || e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim() || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function ne(e) {
	if (!e?.trim()) return null;
	try {
		let t = JSON.parse(e);
		return t && typeof t == "object" ? t : null;
	} catch {
		return h.warn("Invalid data-formie-client-event JSON.", { rawPreview: e.slice(0, 80) }), null;
	}
}
function re(e) {
	let t = {};
	return e.forEach((e) => {
		let n = typeof e.label == "string" ? e.label.trim() : "";
		n && (t[n] = typeof e.value == "string" ? e.value : "");
	}), t;
}
function ie(e) {
	return Array.isArray(e) ? e.map((e) => {
		if (!e || typeof e != "object") return null;
		let t = e, n = typeof t.event == "string" ? t.event.trim() : "", r = t.payload && typeof t.payload == "object" ? t.payload : null;
		return !n || !r ? null : {
			event: n,
			payload: r
		};
	}).filter((e) => e !== null) : [];
}
function v(e, t) {
	if (!t.length) return;
	let n = window;
	n.dataLayer = n.dataLayer || [], t.forEach((t) => {
		n.dataLayer.push(t.payload), e.dispatchEvent(new CustomEvent("formie:client-event", {
			bubbles: !0,
			detail: {
				event: t.event,
				payload: t.payload
			}
		}));
	}), h.log("Dispatched resolved client events.", {
		count: t.length,
		events: t.map((e) => e.event)
	});
}
function ae(e) {
	let t = e.getAttribute(_);
	if (t?.trim()) try {
		let n = ie(JSON.parse(t));
		n.length && v(e, n);
	} catch {
		h.warn("Invalid pending client events JSON on form element.");
	} finally {
		e.removeAttribute(_);
	}
}
function y(e, t) {
	if (t !== "submit") return;
	let n = te(e);
	if (!n) {
		h.log("No submitted page id; skipping client event.");
		return;
	}
	let r = e.querySelector(`[data-formie-page][data-formie-page-id="${ee(n)}"]`);
	if (!r) {
		h.log("No page section for id; skipping client event.", { pageId: n });
		return;
	}
	let i = r.getAttribute(g);
	if (i === null) return;
	let a = ne(i);
	if (!a || !Array.isArray(a.fields)) return;
	let o = re(a.fields);
	v(e, [{
		event: typeof o.event == "string" && o.event !== "" ? o.event : "formPageSubmission",
		payload: o
	}]);
}
//#endregion
//#region src/js/core/page-tab-errors.ts
function oe(e, t) {
	if (i(e, e, "tabError", t), t) {
		e.setAttribute("data-formie-tab-error", "true");
		return;
	}
	e.removeAttribute("data-formie-tab-error");
}
function b(e) {
	let t = /* @__PURE__ */ new Set();
	e.querySelectorAll("[data-formie-page]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		r && n.querySelector("[data-formie-field-has-error]") && t.add(r);
	}), e.querySelectorAll("[data-formie-tab]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		oe(n, !!r && t.has(r));
	});
}
//#endregion
//#region src/js/validation/scope.ts
function x(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function S(e) {
	let t = x(e);
	if (!t.length) return {
		scope: e,
		final: !0
	};
	let n = t.find((e) => !e.hasAttribute("data-formie-page-hidden")) || t[t.length - 1];
	return {
		scope: n,
		final: n === t[t.length - 1]
	};
}
//#endregion
//#region src/js/utils/form-started-at.ts
var se = "formie:formStartedAt:";
function C(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${se}${n}` : null, i = ["submissionUid", "progressId"].some((t) => e.querySelector(`input[name="${t}"]`)?.value.trim()), a = t.value || (i && r ? sessionStorage.getItem(r) : null);
	a ||= String(Date.now()), r && sessionStorage.setItem(r, a), t.value = a;
}
function ce(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	t && (t.value = String(Date.now()), C(e));
}
//#endregion
//#region src/js/validation/submit-readiness.ts
var w = "data-formie-submit-validation-disabled", T = "data-formie-preserve-disabled", le = "data-formie-submit-ready";
function E(e) {
	let t = e.dataset.formieDisableSubmitUntilValid?.trim().toLowerCase();
	return t === "" || t === "true" || t === "1";
}
function ue(e) {
	return Array.from(e.querySelectorAll("button[data-formie-action=\"submit\"]")).filter((e) => e instanceof HTMLButtonElement);
}
function de(e) {
	return !e.hasAttribute("data-formie-conditionally-hidden") && !e.closest("[data-formie-conditionally-hidden]");
}
function D(e, t) {
	if (!E(e) || e.getAttribute("data-formie-loading") === "true") return;
	let { scope: n, final: r } = S(e), i = t.isValid(n, {
		includeHiddenPages: r,
		renderErrors: !1
	});
	e.setAttribute(le, i ? "true" : "false"), ue(e).forEach((e) => {
		if (de(e)) {
			if (i) {
				if (!e.hasAttribute(w)) return;
				e.hasAttribute(T) ? (e.disabled = !0, e.removeAttribute(T)) : e.disabled = !1, e.removeAttribute(w);
				return;
			}
			e.hasAttribute(w) || (e.disabled && e.setAttribute(T, "true"), e.setAttribute(w, "true")), e.disabled ||= !0;
		}
	});
}
function fe(e, t, n) {
	if (!E(e)) return () => {};
	let r = !1, i = () => {
		r || (r = !0, queueMicrotask(() => {
			r = !1, D(e, t);
		}));
	};
	i();
	let a = () => {
		i();
	};
	e.addEventListener("input", a, !0), e.addEventListener("change", a, !0);
	let o = () => {
		window.setTimeout(() => {
			i();
		}, 0);
	};
	e.addEventListener("reset", o);
	let s = () => {
		i();
	};
	n.addEventListener("formie:conditions:evaluated", s);
	let c = new MutationObserver((e) => {
		e.some((e) => {
			if (e.type === "attributes") {
				let t = e.attributeName || "";
				return t === "data-formie-page-hidden" || t === "data-formie-conditionally-hidden" || t === "data-formie-loading" || t === "disabled";
			}
			return e.type === "childList";
		}) && i();
	});
	return c.observe(e, {
		childList: !0,
		subtree: !0,
		attributes: !0,
		attributeFilter: [
			"data-formie-page-hidden",
			"data-formie-conditionally-hidden",
			"data-formie-loading",
			"disabled"
		]
	}), () => {
		e.removeEventListener("input", a, !0), e.removeEventListener("change", a, !0), e.removeEventListener("reset", o), n.removeEventListener("formie:conditions:evaluated", s), c.disconnect();
	};
}
//#endregion
//#region src/js/core/submit-result-state.ts
var pe = "STALE_SUBMISSION_STATE", O = /* @__PURE__ */ new WeakMap(), k = /* @__PURE__ */ new WeakMap(), A = r("general", "submit-result");
function j(e, t, n) {
	let r = e.querySelector(`input[name="${t}"]`);
	r || (r = document.createElement("input"), r.type = "hidden", r.name = t, e.appendChild(r)), r.value = n;
}
function M(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function N(e, t) {
	e.querySelector(`input[name="${t}"]`)?.remove();
}
function me(e, t) {
	try {
		let n = new URL(e, window.location.href);
		return n.searchParams.delete(t), n.toString();
	} catch {
		return e;
	}
}
function he(e) {
	try {
		return new URL(e, window.location.href).origin === window.location.origin;
	} catch {
		return !1;
	}
}
function P(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function ge(e) {
	return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function _e(e, t, n) {
	return t < 0 || n < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") == "page-position" ? Math.round((t + 1) / n * 100) : Math.round(t / n * 100);
}
function ve(e) {
	return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function ye(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function F(e, t) {
	return t.completion ? String(t.completion.behavior) : ye(e);
}
function I(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function L(e, t) {
	e.toggleAttribute("data-formie-form-hidden", t), [
		"[data-formie-form-header]",
		"[data-formie-form-navigation]",
		"[data-formie-form-body]",
		"[data-formie-form-footer]"
	].forEach((n) => {
		e.querySelectorAll(n).forEach((e) => {
			let n = e;
			n.hidden = !!t;
		});
	});
}
function R(e) {
	let t = O.get(e);
	typeof t == "number" && (window.clearTimeout(t), O.delete(e));
}
function be(e, t) {
	k.has(e) || k.set(e, e.innerHTML), e.textContent = t;
}
function z(e) {
	let t = k.get(e);
	t !== void 0 && (e.innerHTML = t, k.delete(e));
}
function xe(e, t) {
	let n = e.querySelector("[data-formie-progress-bar]"), r = e.querySelector("[data-formie-progress-value]");
	n && (n.style.width = `${t}%`, n.setAttribute("aria-valuenow", `${t}`), n.setAttribute("data-formie-progress-state", ve(t)), r && (r.textContent = `${t}%`, r.setAttribute("data-formie-progress-value", `${t}`)));
}
function Se(e, t) {
	if (!t) return;
	let n = (e.dataset.formieLoadingIndicator || "").trim();
	if (n) {
		if (t.setAttribute("data-formie-loading-indicator", n), n === "spinner") {
			i(t, e, "loading", !0), z(t), t.removeAttribute("data-formie-loading-text");
			return;
		}
		if (n === "text") {
			let n = (e.dataset.formieLoadingIndicatorText || "").trim(), r = t.textContent?.trim() || "", i = n || r;
			t.setAttribute("data-formie-loading-text", i), be(t, i);
			return;
		}
		z(t), t.removeAttribute("data-formie-loading-text");
	}
}
function B(e) {
	return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function Ce(e, t) {
	e.getAttribute("data-formie-loading") !== "true" && (e.setAttribute("data-formie-loading", "true"), B(e).forEach((e) => {
		"disabled" in e && (e.disabled ? e.setAttribute("data-formie-was-disabled", "true") : e.removeAttribute("data-formie-was-disabled"), e.disabled = !0);
	}), t && (t.setAttribute("data-formie-loading", "true"), Se(e, t)));
}
function we(e) {
	e.removeAttribute("data-formie-loading"), B(e).forEach((t) => {
		if ("disabled" in t) {
			let e = t;
			e.disabled = e.getAttribute("data-formie-was-disabled") === "true";
		}
		z(t), t.removeAttribute("data-formie-was-disabled"), t.removeAttribute("data-formie-loading"), i(t, e, "loading", !1), t.removeAttribute("data-formie-loading-indicator"), t.removeAttribute("data-formie-loading-text");
	});
	let t = e;
	t.formieValidation && D(e, t.formieValidation);
}
function V(e, t) {
	let n = P(e), r = ge(e), s = n.findIndex((e) => e.getAttribute("data-formie-page-id") === t);
	n.forEach((n) => {
		n.getAttribute("data-formie-page-id") === t ? (n.removeAttribute("data-formie-page-hidden"), a(n, e, "pageHidden")) : (n.setAttribute("data-formie-page-hidden", "true"), o(n, e, "pageHidden"));
	}), r.forEach((n, r) => {
		let c = n.getAttribute("data-formie-page-id") === t, l = s > -1 && r < s;
		i(n, e, "tabCurrent", c), i(n, e, "tabComplete", l);
		let u = n.querySelector("[data-formie-tab-link]");
		u && (i(u, e, "tabLinkCurrent", c), c ? a(u, e, "tabLinkInactive") : o(u, e, "tabLinkInactive")), c ? n.setAttribute("aria-current", "page") : n.removeAttribute("aria-current"), l ? n.setAttribute("data-formie-tab-complete", "true") : n.removeAttribute("data-formie-tab-complete");
	}), s > -1 && n.length > 0 && xe(e, _e(e, s, n.length)), j(e, "pageId", t), b(e);
	let c = e;
	c.formieValidation && D(e, c.formieValidation);
}
function Te(e, t) {
	if (t.meta?.resetSubmissionIdentity === !0) {
		N(e, "submissionId"), N(e, "submissionUid"), N(e, "progressId"), j(e, "expectedVersion", "0"), H(e);
		return;
	}
	let n = t.meta?.session?.version ?? t.meta?.version;
	typeof n == "number" && j(e, "expectedVersion", String(n));
	let r = t.meta?.submissionUid;
	typeof r == "string" && r.trim() !== "" && j(e, "submissionUid", r);
	let i = (t.meta?.session)?.continuation?.progressId;
	typeof i == "string" && i.trim() !== "" ? j(e, "progressId", i) : N(e, "progressId");
}
function H(e) {
	let t = e.getAttribute("action");
	t && e.setAttribute("action", me(t, "resumeToken"));
	try {
		let e = new URL(window.location.href);
		if (!e.searchParams.has("resumeToken")) return;
		e.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
	} catch {}
}
function Ee(e, t) {
	let n = t.meta?.resumeUrl;
	if (typeof n != "string" || n.trim() === "") return;
	let r = n.trim();
	if (he(r)) {
		e.getAttribute("action") && e.setAttribute("action", r);
		try {
			let e = new URL(r, window.location.href);
			window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
		} catch {}
	}
}
function U(t, n = {}) {
	let r = t.formieValidation, i = P(t)[0]?.getAttribute("data-formie-page-id");
	if (R(t), t.reset(), t.querySelectorAll("[data-formie-summary-token]").forEach((e) => {
		e instanceof HTMLInputElement ? e.remove() : e.removeAttribute("data-formie-summary-token");
	}), t.querySelectorAll("[data-formie-summary-blocks]").forEach((e) => e.replaceChildren()), n.preserveHiddenState || L(t, !1), N(t, "submissionId"), j(t, "expectedVersion", "0"), N(t, "submissionUid"), N(t, "progressId"), N(t, "pageId"), H(t), ce(t), r?.resetLiveState(), i) {
		V(t, i), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
		return;
	}
	b(t), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
}
function De(e) {
	return e.code === pe || e.meta?.resetState === !0;
}
function Oe(e, t) {
	let r = t.submitData, i = /* @__PURE__ */ new Set(), a = !1;
	if (Array.isArray(r) && r.length > 0) {
		let t = r.filter((e) => typeof e == "object" && !!e && "event" in e && typeof e.event == "string");
		for (let r of t) {
			let t = n(r.event);
			i.add(t), A.log("Dispatching submitData event.", { eventName: t }), t.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(t, {
				bubbles: !0,
				detail: { data: r.data }
			}));
		}
	}
	let o = t.payment?.action, s = o ? String(o.event || "") : "", c = o ? o.payload : void 0, l = n(s);
	return l && !i.has(l) && (l.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(l, {
		bubbles: !0,
		detail: { data: c }
	})), A.log("Dispatching fallback payment action event.", { eventName: l })), { hasPaymentFollowUpEvent: a };
}
function ke(e, t, n) {
	if (A.log("Applying submit result state.", {
		ok: t.ok,
		action: n,
		code: t.code,
		hasRedirect: !!t.redirect?.url,
		hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
	}), De(t)) {
		U(e), A.log("Resetting state due to stale/reset marker.");
		return;
	}
	t.payment?.action?.type === "redirect" && t.payment.action.url && M(e, "payment-redirect");
	let r = Oe(e, t);
	if (!t.ok && t.redirect?.url && !r.hasPaymentFollowUpEvent) {
		A.log("Applying redirect fallback for failed result.", {
			url: t.redirect.url,
			target: t.redirect.target
		}), R(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank", "noopener,noreferrer") : (M(e, "redirect"), window.location.href = t.redirect.url);
		return;
	}
	if (Te(e, t), !t.ok) {
		A.log("Non-redirect failure; keeping current form state."), R(e);
		return;
	}
	if (Array.isArray(t.clientEvents) && t.clientEvents.length > 0 ? v(e, t.clientEvents) : y(e, n), t.nextPage?.id) {
		R(e), e.formieValidation?.resetLiveState(), V(e, t.nextPage.id), m(e, "formie:page:navigate:after", { pageId: t.nextPage.id }), A.log("Advanced to next page.", { nextPageId: t.nextPage.id });
		return;
	}
	if (n === "save") {
		R(e), Ee(e, t), A.log("Applied save/resume token state.");
		return;
	}
	if (t.completion && n === "submit" && !t.redirect?.url) {
		let n = F(e, t), r = n === "message" && I(e);
		if (n === "reload") {
			R(e), M(e, "reload"), window.location.reload();
			return;
		}
		if (n === "reset") {
			U(e);
			return;
		}
		R(e), U(e, { preserveHiddenState: r });
		return;
	}
	if (n === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
		let n = F(e, t) === "message" && I(e);
		R(e), U(e, { preserveHiddenState: n });
		return;
	}
	R(e);
}
//#endregion
//#region src/js/modules/payments/constants.ts
var Ae = 2500, je = {
	bpoint: ["bpointToken"],
	stripe: ["stripePaymentIntentId"],
	paypal: ["paypalOrderId", "paypalAuthId"],
	payway: ["paywayTokenId"],
	opayo: ["opayoTokenId"],
	eway: ["ewayTokenData"],
	"go-cardless": ["goCardlessRedirectId"],
	mollie: ["molliePaymentId"],
	moneris: ["monerisTokenId"],
	paddle: ["paddleTransactionId"],
	square: ["squarePaymentId"]
};
//#endregion
//#region src/js/utils/fields.ts
function Me(e) {
	return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function Ne(e) {
	return `fields[${Me(e)}]`;
}
function Pe(e, t) {
	let n = Ne(t), r = Array.from(e.querySelectorAll(`[name="${n}"]`)), i = Array.from(e.querySelectorAll(`[name="${n}[]"]`));
	return (i.length ? i : r).filter((e) => e instanceof HTMLElement);
}
function W(e, t) {
	let n = Pe(e, t);
	for (let e of n) {
		let t = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
		if (t) return t;
	}
	return "";
}
function G(e) {
	let t = e.replace(/[^\d.,-]/g, ""), n = t.includes(","), r = t.includes(".");
	if (n && r) t = t.lastIndexOf(",") > t.lastIndexOf(".") ? t.replace(/\./g, "").replace(",", ".") : t.replace(/,/g, "");
	else if (n && !r) {
		let e = t.split(",");
		t = e.length === 2 && e[1].length === 3 && /^\d+$/.test(e[0]) && /^\d+$/.test(e[1]) ? e[0] + e[1] : t.replace(",", ".");
	} else t = t.replace(/,/g, "");
	return parseFloat(t);
}
//#endregion
//#region src/js/modules/payments/utils.ts
function K(e) {
	let t = e;
	return !t.closest("[data-formie-conditionally-hidden]") && !t.closest("[data-formie-row-hidden]") && !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function q(e, t) {
	let n = t.replace(/"/g, "\\\"");
	return e.querySelector(`input[name$="[${n}]"]`) || e.querySelector(`input[name$="${n}"]`);
}
function J(e, t) {
	let n = t.find((t) => {
		let n = q(e, t);
		return !n || String(n.value || "").trim() === "";
	});
	return {
		ok: !n,
		missingSuffix: n
	};
}
async function Y(e, t, n) {
	let r = J(e, t);
	if (r.ok) return r;
	let i = Date.now() + Math.max(n, 0);
	for (; Date.now() < i;) {
		await s(120);
		let n = J(e, t);
		if (n.ok) return n;
	}
	return J(e, t);
}
//#endregion
//#region src/js/modules/payments/host.ts
var Fe = /* @__PURE__ */ new Set([
	"handle",
	"requiredInputSuffixes",
	"waitForValueMs",
	"errorMessage"
]), X = "[data-payment-success]", Z = "[data-payment-error]";
function Ie(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Le(e, t, n) {
	let r = t || {}, i = Object.entries(r).reduce((e, [t, n]) => (Fe.has(t) || (e[t] = n), e), {}), a = Array.isArray(r.requiredInputSuffixes) ? r.requiredInputSuffixes.map(String).filter(Boolean) : n.defaultRequiredInputSuffixes || [], o = Number(r.waitForValueMs ?? n.defaultWaitForValueMs ?? 2500), s = typeof r.errorMessage == "string" && r.errorMessage.trim() !== "" ? r.errorMessage.trim() : "Payment authorization is incomplete.";
	return {
		handle: Ie(e, r),
		transport: {
			requiredInputSuffixes: a,
			waitForValueMs: Number.isFinite(o) ? o : Ae,
			errorMessage: s
		},
		provider: i
	};
}
function Q(e, t, n) {
	return e.addEventListener(t, n), () => {
		e.removeEventListener(t, n);
	};
}
function Re(e, t) {
	let n = e.target, r = e.form, i = e.root, a = r || i, s = t.transport.requiredInputSuffixes, m = () => p(r || i), h = (e) => {
		let t = f(e, m()).value;
		return Array.isArray(t) ? t[0] || "" : String(t || "");
	};
	return {
		root: i,
		form: r,
		field: n,
		updateInputs: (e, t) => {
			let r = Array.isArray(e) ? e : [e];
			for (let e of r) {
				let r = q(a, e) ?? n.querySelector(`input[name*="${e}"]`);
				r && (r.value = t);
			}
		},
		addError: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(Z);
			a && a.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-error", ""), s.textContent = e, o(s, r || i, "fieldError"), t.appendChild(s);
		},
		removeError: () => {
			n.querySelector(Z)?.remove();
		},
		addSuccess: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(X);
			a && a.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-success", ""), s.textContent = e, o(s, r || i, "successMessage"), t.appendChild(s);
		},
		removeSuccess: () => {
			n.querySelector(X)?.remove();
		},
		hasToken: () => J(a, s).ok,
		waitForToken: (e = t.transport.waitForValueMs) => Y(a, s, e).then((e) => e.ok),
		getFieldValue: (e, t = "string") => {
			let n = h(e);
			return t === "float" || t === "int" || t === "number" ? G(n) : n;
		},
		resolveAmount: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed), o = String(a ?? "").trim(), s = typeof a == "number" ? a : G(o);
			if (Number.isFinite(s) && s > 0) return {
				ok: !0,
				value: s
			};
			if (o !== "") {
				let e = h(o), n = G(e);
				if (Number.isFinite(n) && n > 0) return {
					ok: !0,
					value: n
				};
				let r = W(t, o);
				if (!e) return {
					ok: !1,
					error: r ? l("Provide a value for \"{label}\" to proceed.", { label: r }) : l("Provide a payment amount to proceed.")
				};
			}
			return {
				ok: !1,
				error: l("Payment amount must be greater than 0.")
			};
		},
		resolveCurrency: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed ?? e.defaultCurrency ?? ""), o = String(a ?? "").trim(), s = o.toUpperCase();
			if (/^[A-Z]{3}$/.test(s) && !n) return {
				ok: !0,
				value: s
			};
			if (o !== "") {
				let e = String(h(o) || "").trim(), n = e.toUpperCase();
				if (/^[A-Z]{3}$/.test(n)) return {
					ok: !0,
					value: n
				};
				let r = W(t, o);
				if (!e) return {
					ok: !1,
					error: r ? l("Provide a value for \"{label}\" to proceed.", { label: r }) : l("Provide a payment currency to proceed.")
				};
			}
			return {
				ok: !1,
				error: l("Payment currency must be a valid 3-letter code.")
			};
		},
		watchFieldValueChanges: (e, t, n = 600) => {
			let a = r || i, o = e.map((e) => String(e || "").trim()).filter(Boolean);
			if (o.length === 0) return () => {};
			let s = m(), l = /* @__PURE__ */ new Set();
			o.forEach((e) => {
				let t = u(e), n = s.get(t);
				if (n?.names?.length) {
					n.names.forEach((e) => {
						l.add(e);
					});
					return;
				}
				let r = d(t);
				r && (l.add(r), l.add(`${r}[]`));
			});
			let f = c(() => {
				t();
			}, n), p = (e) => {
				let t = e.target?.name || "";
				t && l.has(t) && f();
			};
			return a.addEventListener("input", p), a.addEventListener("change", p), () => {
				a.removeEventListener("input", p), a.removeEventListener("change", p);
			};
		},
		triggerSubmit: () => {
			r && r.setAttribute("data-formie-internal-resubmit", "true"), r && typeof r.requestSubmit == "function" ? r.requestSubmit() : r && r.submit();
		},
		releaseSubmitLoading: () => {
			r && (r.removeAttribute("data-formie-internal-resubmit"), we(r));
		},
		getBillingData: (e) => {
			let t = {};
			if (!e || typeof e != "object") return { billing_details: t };
			if (e.billingName) {
				let n = h(e.billingName);
				n && (t.name = n);
			}
			if (e.billingEmail) {
				let n = h(e.billingEmail);
				n && (t.email = n);
			}
			if (e.billingAddress) {
				let n = e.billingAddress, r = {}, i = h(`${n}.address1`), a = h(`${n}.address2`), o = h(`${n}.address3`), s = h(`${n}.city`), c = h(`${n}.zip`), l = h(`${n}.state`), u = h(`${n}.country`);
				i && (r.line1 = i), a && (r.line2 = a), o && (r.line3 = o), s && (r.city = s), c && (r.postal_code = c), l && (r.state = l), u && (r.country = u), Object.keys(r).length && (t.address = r);
			}
			return { billing_details: t };
		},
		events: {
			onForm: (e, t) => r ? Q(r, e, t) : () => {},
			onRoot: (e, t) => Q(i, e, t)
		}
	};
}
//#endregion
//#region src/js/modules/payments/factories.ts
var $ = r("payments");
function ze(e) {
	let t = e.defaultRequiredInputSuffixes ?? je[e.moduleId.split(":")[1]] ?? [];
	return {
		moduleId: e.moduleId,
		version: 2,
		surfaces: ["server-rendered", "client-rendered"],
		kind: "payment",
		match: (e) => !!(e.target.querySelector("[data-formie-field-type=\"payment\"]") || e.target.closest("[data-formie-field-type=\"payment\"]") || e.target.getAttribute?.("data-formie-field-type") === "payment"),
		setup: async (n) => {
			let r = n.target, i = r.__formiePaymentModuleRegistry || {};
			r.__formiePaymentModuleRegistry = i;
			let a = n.entryKey ?? e.moduleId, o = i[a];
			if (o?.destroy) {
				$.warn("Found stale payment module instance; destroying previous.", { moduleId: e.moduleId });
				try {
					await o.destroy();
				} catch {}
			}
			let s = Le(e.moduleId.split(":")[1], n.options || {}, { defaultRequiredInputSuffixes: t }), c = Re(n, s), l = {
				...n,
				options: s,
				services: c
			}, u = [], d = null, f = null, p = null, m = null, h = async () => (d ||= ($.log("Loading payment provider API.", { moduleId: e.moduleId }), e.load(l)), d), g = async () => {
				if (!e.mount || f || !K(n.target)) return;
				let t = await h();
				try {
					f = await e.mount({
						api: t,
						field: n.target,
						services: c,
						options: s,
						provider: s.provider
					}), $.log("Payment widget mounted.", {
						moduleId: e.moduleId,
						handle: s.handle
					});
				} catch (t) {
					throw $.warn("Payment widget mount failed.", {
						moduleId: e.moduleId,
						handle: s.handle
					}), t;
				}
			};
			if (u.push(n.on("formie:submit:before", () => {
				c.removeError(), c.removeSuccess();
			})), e.setup) {
				let t = n.root || n.form || n.target;
				p = await e.setup({
					...l,
					root: t
				}), p.destroy && u.push(p.destroy);
			}
			e.mount && K(n.target) && await g(), ["formie:page:navigate:after", "formie:submit:result"].forEach((e) => {
				let t = () => {
					g();
				};
				n.root.addEventListener(e, t), u.push(() => {
					n.root.removeEventListener(e, t);
				});
			}), u.push(n.on("formie:conditions:evaluated", () => {
				g();
			}));
			let _ = async () => {
				if ($.log("Destroying payment module.", {
					moduleId: e.moduleId,
					handle: s.handle
				}), u.forEach((e) => e()), f && e.unmount) {
					let t = await h();
					await e.unmount({
						api: t,
						widget: f,
						field: n.target,
						services: c,
						options: s,
						provider: s.provider
					}), $.log("Payment widget unmounted.", {
						moduleId: e.moduleId,
						handle: s.handle
					});
				}
				i[a]?.destroy === _ && delete i[a], $.log("Payment module destroy complete.", {
					moduleId: e.moduleId,
					handle: s.handle
				});
			};
			return i[a] = { destroy: _ }, {
				destroy: _,
				beforeSubmit: async (t) => {
					if (p?.beforeSubmit) {
						await p.beforeSubmit(t);
						return;
					}
					if (t.action !== "submit" || !K(n.target)) return;
					await g();
					let r = await h();
					if (e.onBeforePayment) {
						m ||= (async () => e.onBeforePayment({
							api: r,
							widget: f,
							field: n.target,
							services: c,
							options: s,
							provider: s.provider,
							stageCtx: t
						}))().finally(() => {
							m = null;
						});
						let i = await m;
						if ($.log("onBeforePayment resolved.", {
							moduleId: e.moduleId,
							handle: s.handle,
							ok: i
						}), !i) {
							t.abort(s.transport.errorMessage);
							return;
						}
						return;
					}
					if (s.transport.requiredInputSuffixes.length === 0) return;
					let i = await Y(n.form || n.root, s.transport.requiredInputSuffixes, s.transport.waitForValueMs);
					i.ok || ($.warn("Required payment input(s) missing.", {
						moduleId: e.moduleId,
						handle: s.handle,
						missingSuffix: i.missingSuffix
					}), t.abort(s.transport.errorMessage));
				},
				afterSubmit: async (t, r) => {
					if (e.onAfterSubmit && (await e.onAfterSubmit({
						field: n.target,
						services: c,
						options: s,
						provider: s.provider,
						result: r
					}))?.remount && e.mount) {
						if (f && e.unmount) {
							let t = await h();
							await e.unmount({
								api: t,
								widget: f,
								field: n.target,
								services: c,
								options: s,
								provider: s.provider
							});
						}
						f = null, await g();
					}
				}
			};
		}
	};
}
//#endregion
//#region src/js/modules/payments/api.ts
var Be = ze;
//#endregion
export { L as a, C as c, b as d, y as f, we as i, x as l, m, V as n, Ce as o, ae as p, ke as r, fe as s, Be as t, S as u };
