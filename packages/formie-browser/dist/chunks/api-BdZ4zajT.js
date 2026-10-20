import { a as e, d as t, t as n, u as r } from "./event-names-BCI2FLD8.js";
import { t as i } from "./debug-BV0DvdHx.js";
import { a, i as o, t as s } from "./theme-classes-DAQuEqdP.js";
import { n as c, t as l } from "./async-2GdXNFCE.js";
import { i as u } from "./i18n-BY1ds1BL.js";
import { r as d, t as f } from "./field-references.keys-58ZSTrCW.js";
import { n as p, r as m } from "./field-references.resolver-DC7vOSqg.js";
//#region src/js/core/dom-events.ts
var h = /* @__PURE__ */ new WeakMap(), g = /* @__PURE__ */ new Set([...n, "formie:state:reset"]);
function _(e, t, n) {
	let r = new Set(t ? [e, t] : [e]);
	return r.forEach((e) => h.set(e, n)), () => {
		r.forEach((e) => {
			h.get(e) === n && h.delete(e);
		});
	};
}
function v(e, t) {
	let n = h.get(e);
	return n ? Object.assign(t, { instance: n }) : t;
}
function y(e, n, r) {
	(g.has(n) || n.startsWith("formie:stage:")) && (r ??= h.has(e) ? {} : r, r && typeof r == "object" && !Array.isArray(r) && (r = v(e, r))), e.dispatchEvent(new CustomEvent(t(n), {
		bubbles: !0,
		detail: r
	}));
}
//#endregion
//#region src/js/core/page-client-event.ts
var b = i("general", "page-client-event"), ee = "data-formie-client-event", x = "data-formie-pending-client-events";
function te(e) {
	return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
}
function ne(e) {
	return e.querySelector("input[name=\"pageId\"]")?.value?.trim() || e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim() || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function re(e) {
	if (!e?.trim()) return null;
	try {
		let t = JSON.parse(e);
		return t && typeof t == "object" ? t : null;
	} catch {
		return b.warn("Invalid data-formie-client-event JSON.", { rawPreview: e.slice(0, 80) }), null;
	}
}
function ie(e) {
	let t = {};
	return e.forEach((e) => {
		let n = typeof e.label == "string" ? e.label.trim() : "";
		n && (t[n] = typeof e.value == "string" ? e.value : "");
	}), t;
}
function ae(e) {
	return Array.isArray(e) ? e.map((e) => {
		if (!e || typeof e != "object") return null;
		let t = e, n = typeof t.event == "string" ? t.event.trim() : "", r = t.payload && typeof t.payload == "object" ? t.payload : null;
		return !n || !r ? null : {
			event: n,
			payload: r
		};
	}).filter((e) => e !== null) : [];
}
function S(e, t) {
	if (!t.length) return;
	let n = window;
	n.dataLayer = n.dataLayer || [], t.forEach((t) => {
		n.dataLayer.push(t.payload), y(e, "formie:client-event", {
			event: t.event,
			payload: t.payload
		});
	}), b.log("Dispatched resolved client events.", {
		count: t.length,
		events: t.map((e) => e.event)
	});
}
function oe(e) {
	let t = e.getAttribute(x);
	if (t?.trim()) try {
		let n = ae(JSON.parse(t));
		n.length && S(e, n);
	} catch {
		b.warn("Invalid pending client events JSON on form element.");
	} finally {
		e.removeAttribute(x);
	}
}
function se(e, t) {
	if (t !== "submit") return;
	let n = ne(e);
	if (!n) {
		b.log("No submitted page id; skipping client event.");
		return;
	}
	let r = e.querySelector(`[data-formie-page][data-formie-page-id="${te(n)}"]`);
	if (!r) {
		b.log("No page section for id; skipping client event.", { pageId: n });
		return;
	}
	let i = r.getAttribute(ee);
	if (i === null) return;
	let a = re(i);
	if (!a || !Array.isArray(a.fields)) return;
	let o = ie(a.fields);
	S(e, [{
		event: typeof o.event == "string" && o.event !== "" ? o.event : "formPageSubmission",
		payload: o
	}]);
}
//#endregion
//#region src/js/core/page-tab-errors.ts
function ce(e, t) {
	if (a(e, e, "tabError", t), t) {
		e.setAttribute("data-formie-tab-error", "true");
		return;
	}
	e.removeAttribute("data-formie-tab-error");
}
function C(e) {
	let t = /* @__PURE__ */ new Set();
	e.querySelectorAll("[data-formie-page]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		r && n.querySelector("[data-formie-field-has-error]") && t.add(r);
	}), e.querySelectorAll("[data-formie-tab]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		ce(n, !!r && t.has(r));
	});
}
//#endregion
//#region src/js/validation/scope.ts
function w(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function T(e) {
	let t = w(e);
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
var le = "formie:formStartedAt:";
function E(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	if (!t) return;
	let n = e.querySelector("input[name=\"renderId\"]")?.value?.trim() ?? "", r = n ? `${le}${n}` : null, i = ["submissionUid", "progressId"].some((t) => e.querySelector(`input[name="${t}"]`)?.value.trim()), a = t.value || (i && r ? sessionStorage.getItem(r) : null);
	a ||= String(Date.now()), r && sessionStorage.setItem(r, a), t.value = a;
}
function ue(e) {
	let t = e.querySelector("input[name=\"formStartedAt\"]");
	t && (t.value = String(Date.now()), E(e));
}
//#endregion
//#region src/js/validation/submit-readiness.ts
var D = "data-formie-submit-validation-disabled", O = "data-formie-preserve-disabled", de = "data-formie-submit-ready";
function k(e) {
	let t = e.dataset.formieDisableSubmitUntilValid?.trim().toLowerCase();
	return t === "" || t === "true" || t === "1";
}
function fe(e) {
	return Array.from(e.querySelectorAll("button[data-formie-action=\"submit\"]")).filter((e) => e instanceof HTMLButtonElement);
}
function pe(e) {
	return !e.hasAttribute("data-formie-conditionally-hidden") && !e.closest("[data-formie-conditionally-hidden]");
}
function A(e, t) {
	if (!k(e) || e.getAttribute("data-formie-loading") === "true") return;
	let { scope: n, final: r } = T(e), i = t.isValid(n, {
		includeHiddenPages: r,
		renderErrors: !1
	});
	e.setAttribute(de, i ? "true" : "false"), fe(e).forEach((e) => {
		if (pe(e)) {
			if (i) {
				if (!e.hasAttribute(D)) return;
				e.hasAttribute(O) ? (e.disabled = !0, e.removeAttribute(O)) : e.disabled = !1, e.removeAttribute(D);
				return;
			}
			e.hasAttribute(D) || (e.disabled && e.setAttribute(O, "true"), e.setAttribute(D, "true")), e.disabled ||= !0;
		}
	});
}
function me(e, t, n) {
	if (!k(e)) return () => {};
	let r = !1, i = () => {
		r || (r = !0, queueMicrotask(() => {
			r = !1, A(e, t);
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
var he = "STALE_SUBMISSION_STATE", j = /* @__PURE__ */ new WeakMap(), M = /* @__PURE__ */ new WeakMap(), N = i("general", "submit-result");
function P(e, t, n) {
	let r = e.querySelector(`input[name="${t}"]`);
	r || (r = document.createElement("input"), r.type = "hidden", r.name = t, e.appendChild(r)), r.value = n;
}
function F(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function I(e, t) {
	e.querySelector(`input[name="${t}"]`)?.remove();
}
function ge(e, t) {
	try {
		let n = new URL(e, window.location.href);
		return n.searchParams.delete(t), n.toString();
	} catch {
		return e;
	}
}
function _e(e) {
	try {
		return new URL(e, window.location.href).origin === window.location.origin;
	} catch {
		return !1;
	}
}
function L(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function ve(e) {
	return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function ye(e, t, n) {
	return t < 0 || n < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") == "page-position" ? Math.round((t + 1) / n * 100) : Math.round(t / n * 100);
}
function be(e) {
	return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function xe(e) {
	return (e.dataset.formieCompletionBehavior || e.dataset.formieSubmitAction || "").trim();
}
function R(e, t) {
	return t.completion ? String(t.completion.behavior) : xe(e);
}
function z(e) {
	let t = e.dataset.formieHideFormAfterSubmit ?? e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function B(e, t) {
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
function V(e) {
	let t = j.get(e);
	typeof t == "number" && (window.clearTimeout(t), j.delete(e));
}
function Se(e, t) {
	M.has(e) || M.set(e, e.innerHTML), e.textContent = t;
}
function H(e) {
	let t = M.get(e);
	t !== void 0 && (e.innerHTML = t, M.delete(e));
}
function Ce(e, t) {
	let n = e.querySelector("[data-formie-progress-bar]"), r = e.querySelector("[data-formie-progress-value]");
	n && (n.style.width = `${t}%`, n.setAttribute("aria-valuenow", `${t}`), n.setAttribute("data-formie-progress-state", be(t)), r && (r.textContent = `${t}%`, r.setAttribute("data-formie-progress-value", `${t}`)));
}
function we(e, t) {
	if (!t) return;
	let n = (e.dataset.formieLoadingIndicator || "").trim();
	if (n) {
		if (t.setAttribute("data-formie-loading-indicator", n), n === "spinner") {
			a(t, e, "loading", !0), H(t), t.removeAttribute("data-formie-loading-text");
			return;
		}
		if (n === "text") {
			let n = (e.dataset.formieLoadingIndicatorText || "").trim(), r = t.textContent?.trim() || "", i = n || r;
			t.setAttribute("data-formie-loading-text", i), Se(t, i);
			return;
		}
		H(t), t.removeAttribute("data-formie-loading-text");
	}
}
function U(e) {
	return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function Te(e, t) {
	e.getAttribute("data-formie-loading") !== "true" && (e.setAttribute("data-formie-loading", "true"), U(e).forEach((e) => {
		"disabled" in e && (e.disabled ? e.setAttribute("data-formie-was-disabled", "true") : e.removeAttribute("data-formie-was-disabled"), e.disabled = !0);
	}), t && (t.setAttribute("data-formie-loading", "true"), we(e, t)));
}
function W(e) {
	e.removeAttribute("data-formie-loading"), U(e).forEach((t) => {
		if ("disabled" in t) {
			let e = t;
			e.disabled = e.getAttribute("data-formie-was-disabled") === "true";
		}
		H(t), t.removeAttribute("data-formie-was-disabled"), t.removeAttribute("data-formie-loading"), a(t, e, "loading", !1), t.removeAttribute("data-formie-loading-indicator"), t.removeAttribute("data-formie-loading-text");
	});
	let t = e;
	t.formieValidation && A(e, t.formieValidation);
}
function G(e, t) {
	let n = L(e), r = ve(e), i = n.findIndex((e) => e.getAttribute("data-formie-page-id") === t);
	n.forEach((n) => {
		n.getAttribute("data-formie-page-id") === t ? (n.removeAttribute("data-formie-page-hidden"), o(n, e, "pageHidden")) : (n.setAttribute("data-formie-page-hidden", "true"), s(n, e, "pageHidden"));
	}), r.forEach((n, r) => {
		let c = n.getAttribute("data-formie-page-id") === t, l = i > -1 && r < i;
		a(n, e, "tabCurrent", c), a(n, e, "tabComplete", l);
		let u = n.querySelector("[data-formie-tab-link]");
		u && (a(u, e, "tabLinkCurrent", c), c ? o(u, e, "tabLinkInactive") : s(u, e, "tabLinkInactive")), c ? n.setAttribute("aria-current", "page") : n.removeAttribute("aria-current"), l ? n.setAttribute("data-formie-tab-complete", "true") : n.removeAttribute("data-formie-tab-complete");
	}), i > -1 && n.length > 0 && Ce(e, ye(e, i, n.length)), P(e, "pageId", t), C(e);
	let c = e;
	c.formieValidation && A(e, c.formieValidation);
}
function Ee(e, t) {
	if (t.meta?.resetSubmissionIdentity === !0) {
		I(e, "submissionId"), I(e, "submissionUid"), I(e, "progressId"), P(e, "expectedVersion", "0"), K(e);
		return;
	}
	let n = t.meta?.session?.version ?? t.meta?.version;
	typeof n == "number" && P(e, "expectedVersion", String(n));
	let r = t.meta?.submissionUid;
	typeof r == "string" && r.trim() !== "" && P(e, "submissionUid", r);
	let i = (t.meta?.session)?.continuation?.progressId;
	typeof i == "string" && i.trim() !== "" ? P(e, "progressId", i) : I(e, "progressId");
}
function K(e) {
	let t = e.getAttribute("action");
	t && e.setAttribute("action", ge(t, "resumeToken"));
	try {
		let e = new URL(window.location.href);
		if (!e.searchParams.has("resumeToken")) return;
		e.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
	} catch {}
}
function De(e, t) {
	let n = t.meta?.resumeUrl;
	if (typeof n != "string" || n.trim() === "") return;
	let r = n.trim();
	if (_e(r)) {
		e.getAttribute("action") && e.setAttribute("action", r);
		try {
			let e = new URL(r, window.location.href);
			window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
		} catch {}
	}
}
function q(t, n = {}) {
	let r = t.formieValidation, i = L(t)[0]?.getAttribute("data-formie-page-id");
	if (V(t), t.reset(), t.querySelectorAll("[data-formie-summary-token]").forEach((e) => {
		e instanceof HTMLInputElement ? e.remove() : e.removeAttribute("data-formie-summary-token");
	}), t.querySelectorAll("[data-formie-summary-blocks]").forEach((e) => e.replaceChildren()), n.preserveHiddenState || B(t, !1), I(t, "submissionId"), P(t, "expectedVersion", "0"), I(t, "submissionUid"), I(t, "progressId"), I(t, "pageId"), K(t), ue(t), r?.resetLiveState(), i) {
		G(t, i), y(t, e("reset"), void 0);
		return;
	}
	C(t), y(t, e("reset"), void 0);
}
function Oe(e) {
	return e.code === he || e.meta?.resetState === !0;
}
function ke(e, t) {
	let n = t.submitData, i = /* @__PURE__ */ new Set(), a = !1;
	if (Array.isArray(n) && n.length > 0) {
		let t = n.filter((e) => typeof e == "object" && !!e && "event" in e && typeof e.event == "string");
		for (let n of t) {
			let t = r(n.event);
			i.add(t), N.log("Dispatching submitData event.", { eventName: t }), t.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(t, {
				bubbles: !0,
				detail: { data: n.data }
			}));
		}
	}
	let o = t.payment?.action, s = o ? String(o.event || "") : "", c = o ? o.payload : void 0, l = r(s);
	return l && !i.has(l) && (l.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(l, {
		bubbles: !0,
		detail: { data: c }
	})), N.log("Dispatching fallback payment action event.", { eventName: l })), { hasPaymentFollowUpEvent: a };
}
function Ae(e, t, n) {
	if (N.log("Applying submit result state.", {
		ok: t.ok,
		action: n,
		code: t.code,
		hasRedirect: !!t.redirect?.url,
		hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
	}), Oe(t)) {
		q(e), N.log("Resetting state due to stale/reset marker.");
		return;
	}
	t.payment?.action?.type === "redirect" && t.payment.action.url && F(e, "payment-redirect");
	let r = ke(e, t);
	if (!t.ok && t.redirect?.url && !r.hasPaymentFollowUpEvent) {
		N.log("Applying redirect fallback for failed result.", {
			url: t.redirect.url,
			target: t.redirect.target
		}), V(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank", "noopener,noreferrer") : (F(e, "redirect"), window.location.href = t.redirect.url);
		return;
	}
	if (Ee(e, t), !t.ok) {
		N.log("Non-redirect failure; keeping current form state."), V(e);
		return;
	}
	if (Array.isArray(t.clientEvents) && t.clientEvents.length > 0 ? S(e, t.clientEvents) : se(e, n), t.nextPage?.id) {
		V(e), e.formieValidation?.resetLiveState(), G(e, t.nextPage.id), y(e, "formie:page:navigate:after", { pageId: t.nextPage.id }), N.log("Advanced to next page.", { nextPageId: t.nextPage.id });
		return;
	}
	if (n === "save") {
		V(e), De(e, t), N.log("Applied save/resume token state.");
		return;
	}
	if (t.completion && n === "submit" && !t.redirect?.url) {
		let n = R(e, t), r = n === "message" && z(e);
		if (n === "reload") {
			V(e), F(e, "reload"), window.location.reload();
			return;
		}
		if (n === "reset") {
			q(e);
			return;
		}
		V(e), q(e, { preserveHiddenState: r });
		return;
	}
	if (n === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
		let n = R(e, t) === "message" && z(e);
		V(e), q(e, { preserveHiddenState: n });
		return;
	}
	V(e);
}
//#endregion
//#region src/js/modules/payments/constants.ts
var je = 2500, Me = {
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
function Ne(e) {
	return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function Pe(e) {
	return `fields[${Ne(e)}]`;
}
function Fe(e, t) {
	let n = Pe(t), r = Array.from(e.querySelectorAll(`[name="${n}"]`)), i = Array.from(e.querySelectorAll(`[name="${n}[]"]`));
	return (i.length ? i : r).filter((e) => e instanceof HTMLElement);
}
function J(e, t) {
	let n = Fe(e, t);
	for (let e of n) {
		let t = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
		if (t) return t;
	}
	return "";
}
function Y(e) {
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
function X(e) {
	let t = e;
	return !t.closest("[data-formie-conditionally-hidden]") && !t.closest("[data-formie-row-hidden]") && !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function Ie(e, t) {
	let n = t.replace(/"/g, "\\\"");
	return e.querySelector(`input[name$="[${n}]"]`) || e.querySelector(`input[name$="${n}"]`);
}
function Z(e, t) {
	let n = t.find((t) => {
		let n = Ie(e, t);
		return !n || String(n.value || "").trim() === "";
	});
	return {
		ok: !n,
		missingSuffix: n
	};
}
async function Le(e, t, n) {
	let r = Z(e, t);
	if (r.ok) return r;
	let i = Date.now() + Math.max(n, 0);
	for (; Date.now() < i;) {
		await c(120);
		let n = Z(e, t);
		if (n.ok) return n;
	}
	return Z(e, t);
}
//#endregion
//#region src/js/modules/payments/host.ts
var Re = /* @__PURE__ */ new Set([
	"handle",
	"requiredInputSuffixes",
	"waitForValueMs",
	"errorMessage"
]), ze = "[data-payment-success]", Be = "[data-payment-error]";
function Ve(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function He(e, t, n) {
	let r = t || {}, i = Object.entries(r).reduce((e, [t, n]) => (Re.has(t) || (e[t] = n), e), {}), a = Array.isArray(r.requiredInputSuffixes) ? r.requiredInputSuffixes.map(String).filter(Boolean) : n.defaultRequiredInputSuffixes || [], o = Number(r.waitForValueMs ?? n.defaultWaitForValueMs ?? 2500), s = typeof r.errorMessage == "string" && r.errorMessage.trim() !== "" ? r.errorMessage.trim() : "Payment authorization is incomplete.";
	return {
		handle: Ve(e, r),
		transport: {
			requiredInputSuffixes: a,
			waitForValueMs: Number.isFinite(o) ? o : je,
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
function Ue(e, t) {
	let n = e.target, r = e.form, i = e.root, a = r || i, o = t.transport.requiredInputSuffixes, c = () => m(r || i), h = (e) => {
		let t = p(e, c()).value;
		return Array.isArray(t) ? t[0] || "" : String(t || "");
	};
	return {
		root: i,
		form: r,
		field: n,
		updateInputs: (e, t) => {
			let r = Array.isArray(e) ? e : [e];
			for (let e of r) {
				let r = Ie(a, e) ?? n.querySelector(`input[name*="${e}"]`);
				r && (r.value = t);
			}
		},
		addError: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(Be);
			a && a.remove();
			let o = document.createElement("div");
			o.setAttribute("data-payment-error", ""), o.textContent = e, s(o, r || i, "fieldError"), t.appendChild(o);
		},
		removeError: () => {
			n.querySelector(Be)?.remove();
		},
		addSuccess: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(ze);
			a && a.remove();
			let o = document.createElement("div");
			o.setAttribute("data-payment-success", ""), o.textContent = e, s(o, r || i, "successMessage"), t.appendChild(o);
		},
		removeSuccess: () => {
			n.querySelector(ze)?.remove();
		},
		hasToken: () => Z(a, o).ok,
		waitForToken: (e = t.transport.waitForValueMs) => Le(a, o, e).then((e) => e.ok),
		getFieldValue: (e, t = "string") => {
			let n = h(e);
			return t === "float" || t === "int" || t === "number" ? Y(n) : n;
		},
		resolveAmount: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed), o = String(a ?? "").trim(), s = typeof a == "number" ? a : Y(o);
			if (Number.isFinite(s) && s > 0) return {
				ok: !0,
				value: s
			};
			if (o !== "") {
				let e = h(o), n = Y(e);
				if (Number.isFinite(n) && n > 0) return {
					ok: !0,
					value: n
				};
				let r = J(t, o);
				if (!e) return {
					ok: !1,
					error: r ? u("Provide a value for \"{label}\" to proceed.", { label: r }) : u("Provide a payment amount to proceed.")
				};
			}
			return {
				ok: !1,
				error: u("Payment amount must be greater than 0.")
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
				let r = J(t, o);
				if (!e) return {
					ok: !1,
					error: r ? u("Provide a value for \"{label}\" to proceed.", { label: r }) : u("Provide a payment currency to proceed.")
				};
			}
			return {
				ok: !1,
				error: u("Payment currency must be a valid 3-letter code.")
			};
		},
		watchFieldValueChanges: (e, t, n = 600) => {
			let a = r || i, o = e.map((e) => String(e || "").trim()).filter(Boolean);
			if (o.length === 0) return () => {};
			let s = c(), u = /* @__PURE__ */ new Set();
			o.forEach((e) => {
				let t = d(e), n = s.get(t);
				if (n?.names?.length) {
					n.names.forEach((e) => {
						u.add(e);
					});
					return;
				}
				let r = f(t);
				r && (u.add(r), u.add(`${r}[]`));
			});
			let p = l(() => {
				t();
			}, n), m = (e) => {
				let t = e.target?.name || "";
				t && u.has(t) && p();
			};
			return a.addEventListener("input", m), a.addEventListener("change", m), () => {
				a.removeEventListener("input", m), a.removeEventListener("change", m);
			};
		},
		triggerSubmit: () => {
			r && r.setAttribute("data-formie-internal-resubmit", "true"), r && typeof r.requestSubmit == "function" ? r.requestSubmit() : r && r.submit();
		},
		releaseSubmitLoading: () => {
			r && (r.removeAttribute("data-formie-internal-resubmit"), W(r));
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
var $ = i("payments");
function We(e) {
	let t = e.defaultRequiredInputSuffixes ?? Me[e.moduleId.split(":")[1]] ?? [];
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
			let s = He(e.moduleId.split(":")[1], n.options || {}, { defaultRequiredInputSuffixes: t }), c = Ue(n, s), l = {
				...n,
				options: s,
				services: c
			}, u = [], d = null, f = null, p = null, m = null, h = async () => (d ||= ($.log("Loading payment provider API.", { moduleId: e.moduleId }), e.load(l)), d), g = async () => {
				if (!e.mount || f || !X(n.target)) return;
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
			e.mount && X(n.target) && await g(), ["formie:page:navigate:after", "formie:submit:result"].forEach((e) => {
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
					if (t.action !== "submit" || !X(n.target)) return;
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
					let i = await Le(n.form || n.root, s.transport.requiredInputSuffixes, s.transport.waitForValueMs);
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
var Ge = We;
//#endregion
export { B as a, E as c, C as d, se as f, v as g, y as h, W as i, w as l, _ as m, G as n, Te as o, oe as p, Ae as r, me as s, Ge as t, T as u };
