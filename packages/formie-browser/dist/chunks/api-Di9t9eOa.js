import { a as e, d as t, u as n } from "./event-names-BCI2FLD8.js";
import { t as r } from "./debug-BV0DvdHx.js";
import { i, r as a, t as o } from "./theme-classes-Tv7q7ToE.js";
import { i as s } from "./i18n-BY1ds1BL.js";
import { n as c, t as l } from "./async-nPFRNQ06.js";
import { r as u, t as d } from "./field-references.keys-58ZSTrCW.js";
import { n as f, r as p } from "./field-references.resolver-CHwn0G0L.js";
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
//#region src/js/validation/submit-readiness.ts
var C = "data-formie-submit-validation-disabled", w = "data-formie-preserve-disabled", se = "data-formie-submit-ready";
function T(e) {
	return e.dataset.formieDisableSubmitUntilValid === "true";
}
function ce(e) {
	return Array.from(e.querySelectorAll("button[data-formie-action=\"submit\"]")).filter((e) => e instanceof HTMLButtonElement);
}
function le(e) {
	return !e.hasAttribute("data-formie-conditionally-hidden") && !e.closest("[data-formie-conditionally-hidden]");
}
function E(e, t) {
	if (!T(e) || e.getAttribute("data-formie-loading") === "true") return;
	let { scope: n, final: r } = S(e), i = t.isValid(n, { includeHiddenPages: r });
	e.setAttribute(se, i ? "true" : "false"), ce(e).forEach((e) => {
		if (le(e)) {
			if (i) {
				if (!e.hasAttribute(C)) return;
				e.hasAttribute(w) ? (e.disabled = !0, e.removeAttribute(w)) : e.disabled = !1, e.removeAttribute(C);
				return;
			}
			e.hasAttribute(C) || (e.disabled && e.setAttribute(w, "true"), e.setAttribute(C, "true")), e.disabled = !0;
		}
	});
}
function ue(e, t, n) {
	if (!T(e)) return () => {};
	let r = !1, i = () => {
		r || (r = !0, queueMicrotask(() => {
			r = !1, E(e, t);
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
var de = "STALE_SUBMISSION_STATE", D = /* @__PURE__ */ new WeakMap(), O = /* @__PURE__ */ new WeakMap(), k = r("general", "submit-result");
function A(e, t, n) {
	let r = e.querySelector(`input[name="${t}"]`);
	r || (r = document.createElement("input"), r.type = "hidden", r.name = t, e.appendChild(r)), r.value = n;
}
function j(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function M(e, t) {
	e.querySelector(`input[name="${t}"]`)?.remove();
}
function fe(e, t) {
	try {
		let n = new URL(e, window.location.href);
		return n.searchParams.delete(t), n.toString();
	} catch {
		return e;
	}
}
function pe(e) {
	try {
		return new URL(e, window.location.href).origin === window.location.origin;
	} catch {
		return !1;
	}
}
function N(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function me(e) {
	return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function he(e, t, n) {
	return t < 0 || n < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") == "page-position" ? Math.round((t + 1) / n * 100) : Math.round(t / n * 100);
}
function ge(e) {
	return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function _e(e) {
	return (e.dataset.formieSubmitAction || "").trim();
}
function P(e, t) {
	let n = t.meta?.effectiveSubmitAction;
	return typeof n == "string" && n.trim() !== "" ? n.trim() : _e(e);
}
function F(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function I(e, t) {
	e.toggleAttribute("data-formie-form-hidden", t), [
		"[data-formie-form-header]",
		"[data-formie-form-navigation]",
		"[data-formie-form-body]",
		"[data-formie-form-footer]"
	].forEach((n) => {
		e.querySelectorAll(n).forEach((e) => {
			let n = e;
			t ? n.hidden = !0 : n.hidden = !1;
		});
	});
}
function L(e) {
	let t = D.get(e);
	typeof t == "number" && (window.clearTimeout(t), D.delete(e));
}
function ve(e, t) {
	O.has(e) || O.set(e, e.innerHTML), e.textContent = t;
}
function R(e) {
	let t = O.get(e);
	t !== void 0 && (e.innerHTML = t, O.delete(e));
}
function ye(e, t) {
	let n = e.querySelector("[data-formie-progress-bar]"), r = e.querySelector("[data-formie-progress-value]");
	n && (n.style.width = `${t}%`, n.setAttribute("aria-valuenow", `${t}`), n.setAttribute("data-formie-progress-state", ge(t)), r && (r.textContent = `${t}%`, r.setAttribute("data-formie-progress-value", `${t}`)));
}
function be(e, t) {
	if (!t) return;
	let n = (e.dataset.formieLoadingIndicator || "").trim();
	if (n) {
		if (t.setAttribute("data-formie-loading-indicator", n), n === "spinner") {
			i(t, e, "loading", !0), R(t), t.removeAttribute("data-formie-loading-text");
			return;
		}
		if (n === "text") {
			let n = (e.dataset.formieLoadingIndicatorText || "").trim(), r = t.textContent?.trim() || "", i = n || r;
			t.setAttribute("data-formie-loading-text", i), ve(t, i);
			return;
		}
		R(t), t.removeAttribute("data-formie-loading-text");
	}
}
function z(e) {
	return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function xe(e, t) {
	e.getAttribute("data-formie-loading") !== "true" && (e.setAttribute("data-formie-loading", "true"), z(e).forEach((e) => {
		"disabled" in e && (e.disabled ? e.setAttribute("data-formie-was-disabled", "true") : e.removeAttribute("data-formie-was-disabled"), e.disabled = !0);
	}), t && (t.setAttribute("data-formie-loading", "true"), be(e, t)));
}
function B(e) {
	if (e.removeAttribute("data-formie-loading"), z(e).forEach((t) => {
		if ("disabled" in t) {
			let e = t;
			e.disabled = e.getAttribute("data-formie-was-disabled") === "true";
		}
		R(t), t.removeAttribute("data-formie-was-disabled"), t.removeAttribute("data-formie-loading"), i(t, e, "loading", !1), t.removeAttribute("data-formie-loading-indicator"), t.removeAttribute("data-formie-loading-text");
	}), e.dataset.formieDisableSubmitUntilValid === "true") {
		let t = e;
		t.formieValidation && E(e, t.formieValidation);
	}
}
function V(e, t) {
	let n = N(e), r = me(e), s = n.findIndex((e) => e.getAttribute("data-formie-page-id") === t);
	if (n.forEach((n) => {
		n.getAttribute("data-formie-page-id") === t ? (n.removeAttribute("data-formie-page-hidden"), a(n, e, "pageHidden")) : (n.setAttribute("data-formie-page-hidden", "true"), o(n, e, "pageHidden"));
	}), r.forEach((n, r) => {
		let c = n.getAttribute("data-formie-page-id") === t, l = s > -1 && r < s;
		i(n, e, "tabCurrent", c), i(n, e, "tabComplete", l);
		let u = n.querySelector("[data-formie-tab-link]");
		u && (i(u, e, "tabLinkCurrent", c), c ? a(u, e, "tabLinkInactive") : o(u, e, "tabLinkInactive")), c ? n.setAttribute("aria-current", "page") : n.removeAttribute("aria-current"), l ? n.setAttribute("data-formie-tab-complete", "true") : n.removeAttribute("data-formie-tab-complete");
	}), s > -1 && n.length > 0 && ye(e, he(e, s, n.length)), A(e, "pageId", t), b(e), e.dataset.formieDisableSubmitUntilValid === "true") {
		let t = e;
		t.formieValidation && E(e, t.formieValidation);
	}
}
function Se(e, t) {
	let n = t.meta?.submissionUid;
	typeof n == "string" && n.trim() !== "" && A(e, "submissionUid", n);
	let r = (t.meta?.session)?.continuation?.continuationToken;
	typeof r == "string" && r.trim() !== "" ? A(e, "continuationToken", r) : M(e, "continuationToken");
}
function Ce(e) {
	let t = e.getAttribute("action");
	t && e.setAttribute("action", fe(t, "resumeToken"));
	try {
		let e = new URL(window.location.href);
		if (!e.searchParams.has("resumeToken")) return;
		e.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
	} catch {}
}
function we(e, t) {
	let n = t.meta?.resumeUrl;
	if (typeof n != "string" || n.trim() === "") return;
	let r = n.trim();
	if (pe(r)) {
		e.getAttribute("action") && e.setAttribute("action", r);
		try {
			let e = new URL(r, window.location.href);
			window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
		} catch {}
	}
}
function H(t, n = {}) {
	let r = t.formieValidation, i = N(t)[0]?.getAttribute("data-formie-page-id");
	if (L(t), t.reset(), n.preserveHiddenState || I(t, !1), M(t, "submissionId"), M(t, "submissionUid"), M(t, "continuationToken"), M(t, "pageId"), Ce(t), r?.resetLiveState(), i) {
		V(t, i), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
		return;
	}
	b(t), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
}
function Te(e) {
	return e.code === de || e.meta?.resetState === !0;
}
function Ee(e, t) {
	let r = t.submitData, i = /* @__PURE__ */ new Set(), a = !1;
	if (Array.isArray(r) && r.length > 0) {
		let t = r.filter((e) => typeof e == "object" && !!e && "event" in e && typeof e.event == "string");
		for (let r of t) {
			let t = n(r.event);
			i.add(t), k.log("Dispatching submitData event.", { eventName: t }), t.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(t, {
				bubbles: !0,
				detail: { data: r.data }
			}));
		}
	}
	let o = t.meta || {}, s = (o.paymentAction && typeof o.paymentAction == "object" ? o.paymentAction : null) || (o.paymentDecision && typeof o.paymentDecision == "object" ? o.paymentDecision.action : null), c = s ? String(s.event || "") : "", l = s ? s.payload : void 0, u = n(c);
	return u && !i.has(u) && (u.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(u, {
		bubbles: !0,
		detail: { data: l }
	})), k.log("Dispatching fallback payment action event.", { eventName: u })), { hasPaymentFollowUpEvent: a };
}
function De(e, t, n) {
	if (k.log("Applying submit result state.", {
		ok: t.ok,
		action: n,
		code: t.code,
		hasRedirect: !!t.redirect?.url,
		hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
	}), Te(t)) {
		H(e), k.log("Resetting state due to stale/reset marker.");
		return;
	}
	let r = Ee(e, t);
	if (!t.ok && t.redirect?.url && !r.hasPaymentFollowUpEvent) {
		k.log("Applying redirect fallback for failed result.", {
			url: t.redirect.url,
			target: t.redirect.target
		}), L(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank") : (j(e, "redirect"), window.location.href = t.redirect.url);
		return;
	}
	if (Se(e, t), !t.ok) {
		k.log("Non-redirect failure; keeping current form state."), L(e);
		return;
	}
	if (Array.isArray(t.clientEvents) && t.clientEvents.length > 0 ? v(e, t.clientEvents) : y(e, n), t.nextPage?.id) {
		L(e), e.formieValidation?.resetLiveState(), V(e, t.nextPage.id), m(e, "formie:page:navigate:after", { pageId: t.nextPage.id }), k.log("Advanced to next page.", { nextPageId: t.nextPage.id });
		return;
	}
	if (n === "save") {
		L(e), we(e, t), k.log("Applied save/resume token state.");
		return;
	}
	if (n === "submit" && !t.redirect?.url) {
		let n = P(e, t), r = n === "message" && F(e);
		if (n === "reload") {
			L(e), j(e, "reload"), window.location.reload();
			return;
		}
		if (n === "reset") {
			H(e);
			return;
		}
		L(e), H(e, { preserveHiddenState: r });
		return;
	}
	if (n === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
		let n = P(e, t) === "message" && F(e);
		L(e), H(e, { preserveHiddenState: n });
		return;
	}
	L(e);
}
//#endregion
//#region src/js/modules/payments/constants.ts
var Oe = 2500, ke = {
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
function Ae(e) {
	return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function je(e) {
	return `fields[${Ae(e)}]`;
}
function Me(e, t) {
	let n = je(t), r = Array.from(e.querySelectorAll(`[name="${n}"]`)), i = Array.from(e.querySelectorAll(`[name="${n}[]"]`));
	return (i.length ? i : r).filter((e) => e instanceof HTMLElement);
}
function U(e, t) {
	let n = Me(e, t);
	for (let e of n) {
		let t = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
		if (t) return t;
	}
	return "";
}
function W(e) {
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
function G(e, t) {
	let n = t.replace(/"/g, "\\\"");
	return e.querySelector(`input[name$="[${n}]"]`) || e.querySelector(`input[name$="${n}"]`);
}
function K(e, t) {
	let n = t.find((t) => {
		let n = G(e, t);
		return !n || String(n.value || "").trim() === "";
	});
	return {
		ok: !n,
		missingSuffix: n
	};
}
async function q(e, t, n) {
	let r = K(e, t);
	if (r.ok) return r;
	let i = Date.now() + Math.max(n, 0);
	for (; Date.now() < i;) {
		await c(120);
		let n = K(e, t);
		if (n.ok) return n;
	}
	return K(e, t);
}
//#endregion
//#region src/js/modules/payments/host.ts
var Ne = /* @__PURE__ */ new Set([
	"handle",
	"requiredInputSuffixes",
	"waitForValueMs",
	"errorMessage"
]), J = "[data-payment-success]", Y = "[data-payment-error]";
function X(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Pe(e, t, n) {
	let r = t || {}, i = Object.entries(r).reduce((e, [t, n]) => (Ne.has(t) || (e[t] = n), e), {}), a = Array.isArray(r.requiredInputSuffixes) ? r.requiredInputSuffixes.map(String).filter(Boolean) : n.defaultRequiredInputSuffixes || [], o = Number(r.waitForValueMs ?? n.defaultWaitForValueMs ?? 2500), s = typeof r.errorMessage == "string" && r.errorMessage.trim() !== "" ? r.errorMessage.trim() : "Payment authorization is incomplete.";
	return {
		handle: X(e, r),
		transport: {
			requiredInputSuffixes: a,
			waitForValueMs: Number.isFinite(o) ? o : Oe,
			errorMessage: s
		},
		provider: i
	};
}
function Z(e, t, n) {
	return e.addEventListener(t, n), () => {
		e.removeEventListener(t, n);
	};
}
function Fe(e, t) {
	let n = e.target, r = e.form, i = e.root, a = r || i, c = t.transport.requiredInputSuffixes, m = () => p(r || i), h = (e) => {
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
				let r = G(a, e) ?? n.querySelector(`input[name*="${e}"]`);
				r && (r.value = t);
			}
		},
		addError: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(Y);
			a && a.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-error", ""), s.textContent = e, o(s, r || i, "fieldError"), t.appendChild(s);
		},
		removeError: () => {
			n.querySelector(Y)?.remove();
		},
		addSuccess: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, a = t.querySelector(J);
			a && a.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-success", ""), s.textContent = e, o(s, r || i, "successMessage"), t.appendChild(s);
		},
		removeSuccess: () => {
			n.querySelector(J)?.remove();
		},
		hasToken: () => K(a, c).ok,
		waitForToken: (e = t.transport.waitForValueMs) => q(a, c, e).then((e) => e.ok),
		getFieldValue: (e, t = "string") => {
			let n = h(e);
			return t === "float" || t === "int" || t === "number" ? W(n) : n;
		},
		resolveAmount: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed), o = String(a ?? "").trim(), c = typeof a == "number" ? a : W(o);
			if (Number.isFinite(c) && c > 0) return {
				ok: !0,
				value: c
			};
			if (o !== "") {
				let e = h(o), n = W(e);
				if (Number.isFinite(n) && n > 0) return {
					ok: !0,
					value: n
				};
				let r = U(t, o);
				if (!e) return {
					ok: !1,
					error: r ? s("Provide a value for \"{label}\" to proceed.", { label: r }) : s("Provide a payment amount to proceed.")
				};
			}
			return {
				ok: !1,
				error: s("Payment amount must be greater than 0.")
			};
		},
		resolveCurrency: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed ?? e.defaultCurrency ?? ""), o = String(a ?? "").trim(), c = o.toUpperCase();
			if (/^[A-Z]{3}$/.test(c) && !n) return {
				ok: !0,
				value: c
			};
			if (o !== "") {
				let e = String(h(o) || "").trim(), n = e.toUpperCase();
				if (/^[A-Z]{3}$/.test(n)) return {
					ok: !0,
					value: n
				};
				let r = U(t, o);
				if (!e) return {
					ok: !1,
					error: r ? s("Provide a value for \"{label}\" to proceed.", { label: r }) : s("Provide a payment currency to proceed.")
				};
			}
			return {
				ok: !1,
				error: s("Payment currency must be a valid 3-letter code.")
			};
		},
		watchFieldValueChanges: (e, t, n = 600) => {
			let a = r || i, o = e.map((e) => String(e || "").trim()).filter(Boolean);
			if (o.length === 0) return () => {};
			let s = m(), c = /* @__PURE__ */ new Set();
			o.forEach((e) => {
				let t = u(e), n = s.get(t);
				if (n?.names?.length) {
					n.names.forEach((e) => {
						c.add(e);
					});
					return;
				}
				let r = d(t);
				r && (c.add(r), c.add(`${r}[]`));
			});
			let f = l(() => {
				t();
			}, n), p = (e) => {
				let t = e.target?.name || "";
				!t || !c.has(t) || f();
			};
			return a.addEventListener("input", p), a.addEventListener("change", p), () => {
				a.removeEventListener("input", p), a.removeEventListener("change", p);
			};
		},
		triggerSubmit: () => {
			r && r.setAttribute("data-formie-internal-resubmit", "true"), r && typeof r.requestSubmit == "function" ? r.requestSubmit() : r && r.submit();
		},
		releaseSubmitLoading: () => {
			r && (r.removeAttribute("data-formie-internal-resubmit"), B(r));
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
			onForm: (e, t) => r ? Z(r, e, t) : () => {},
			onRoot: (e, t) => Z(i, e, t)
		}
	};
}
//#endregion
//#region src/js/modules/payments/factories.ts
var Q = r("payments");
function $(e) {
	let t = e;
	return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function Ie(e) {
	let t = e.defaultRequiredInputSuffixes ?? ke[e.id] ?? [];
	return {
		id: e.id,
		kind: "payment",
		match: (e) => !!(e.target.querySelector("[data-formie-field-type=\"payment\"]") || e.target.closest("[data-formie-field-type=\"payment\"]") || e.target.getAttribute?.("data-formie-field-type") === "payment"),
		setup: async (n) => {
			let r = n.target, i = r.__formiePaymentModuleRegistry || {};
			r.__formiePaymentModuleRegistry = i;
			let a = i[e.id];
			if (a?.destroy) {
				Q.warn("Found stale payment module instance; destroying previous.", { moduleId: e.id });
				try {
					await a.destroy();
				} catch {}
			}
			let o = Pe(e.id, n.options || {}, { defaultRequiredInputSuffixes: t }), s = Fe(n, o), c = {
				...n,
				options: o,
				services: s
			}, l = [], u = null, d = null, f = null, p = null, m = async () => (u ||= (Q.log("Loading payment provider API.", { moduleId: e.id }), e.load(c)), u), h = async () => {
				if (!e.mount || d || !$(n.target)) return;
				let t = await m();
				try {
					d = await e.mount({
						api: t,
						field: n.target,
						services: s,
						options: o,
						provider: o.provider
					}), Q.log("Payment widget mounted.", {
						moduleId: e.id,
						handle: o.handle
					});
				} catch {
					Q.warn("Payment widget mount failed.", {
						moduleId: e.id,
						handle: o.handle
					});
				}
			};
			if (l.push(n.on("formie:submit:before", () => {
				s.removeError(), s.removeSuccess();
			})), e.setup) {
				let t = n.root || n.form || n.target;
				f = await e.setup({
					...c,
					root: t
				}), f.destroy && l.push(f.destroy);
			}
			e.mount && $(n.target) && await h(), ["formie:page:navigate:after", "formie:submit:result"].forEach((e) => {
				let t = () => {
					h();
				};
				n.root.addEventListener(e, t), l.push(() => {
					n.root.removeEventListener(e, t);
				});
			});
			let g = async () => {
				if (Q.log("Destroying payment module.", {
					moduleId: e.id,
					handle: o.handle
				}), l.forEach((e) => e()), d && e.unmount) {
					let t = await m();
					await e.unmount({
						api: t,
						widget: d,
						field: n.target,
						services: s,
						options: o,
						provider: o.provider
					}), Q.log("Payment widget unmounted.", {
						moduleId: e.id,
						handle: o.handle
					});
				}
				i[e.id]?.destroy === g && delete i[e.id], Q.log("Payment module destroy complete.", {
					moduleId: e.id,
					handle: o.handle
				});
			};
			return i[e.id] = { destroy: g }, {
				destroy: g,
				onBeforeStage: async (t) => {
					if (f?.onBeforeStage) {
						await f.onBeforeStage(t);
						return;
					}
					if (t.stage !== "authorize" || t.action !== "submit" || n.target.closest("[data-formie-page]")?.hasAttribute("data-formie-page-hidden")) return;
					await h();
					let r = await m();
					if (e.onBeforeAuthorize) {
						p ||= (async () => e.onBeforeAuthorize({
							api: r,
							widget: d,
							field: n.target,
							services: s,
							options: o,
							provider: o.provider,
							stageCtx: t
						}))().finally(() => {
							p = null;
						});
						let i = await p;
						if (Q.log("onBeforeAuthorize resolved.", {
							moduleId: e.id,
							handle: o.handle,
							ok: i
						}), !i) {
							t.abort(o.transport.errorMessage);
							return;
						}
						return;
					}
					if (o.transport.requiredInputSuffixes.length === 0) return;
					let i = await q(n.form || n.root, o.transport.requiredInputSuffixes, o.transport.waitForValueMs);
					i.ok || (Q.warn("Required payment input(s) missing.", {
						moduleId: e.id,
						handle: o.handle,
						missingSuffix: i.missingSuffix
					}), t.abort(o.transport.errorMessage));
				},
				onAfterStage: async (t, r) => {
					if (!(t.stage !== "dispatch" || !e.onAfterSubmit) && !(!(await e.onAfterSubmit({
						field: n.target,
						services: s,
						options: o,
						provider: o.provider,
						result: r
					}))?.remount || !e.mount)) {
						if (d && e.unmount) {
							let t = await m();
							await e.unmount({
								api: t,
								widget: d,
								field: n.target,
								services: s,
								options: o,
								provider: o.provider
							});
						}
						d = null, await h();
					}
				}
			};
		}
	};
}
//#endregion
//#region src/js/modules/payments/api.ts
var Le = Ie;
//#endregion
export { I as a, x as c, y as d, ae as f, B as i, S as l, V as n, xe as o, m as p, De as r, ue as s, Le as t, b as u };
