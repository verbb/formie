import { a as e, u as t } from "./event-names-BzJlD9YG.js";
import { t as n } from "./debug-JxLdQzL0.js";
import { i as r, r as i, t as a } from "./theme-classes-BQeZGsT6.js";
import { i as o } from "./i18n-CuNHSAl8.js";
import { n as s, t as c } from "./async-CTgbK8eG.js";
import { r as l, t as u } from "./field-references.keys-BGhkWxVZ.js";
import { n as d, r as f } from "./field-references.resolver-D3zB6rj6.js";
//#region src/js/core/page-client-event.ts
var p = n("general", "page-client-event"), m = "data-formie-client-event";
function h(e) {
	return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
}
function g(e) {
	return e.querySelector("input[name=\"pageId\"]")?.value?.trim() || e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim() || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function ee(e) {
	if (!e?.trim()) return null;
	try {
		let t = JSON.parse(e);
		return t && typeof t == "object" ? t : null;
	} catch {
		return p.warn("Invalid data-formie-client-event JSON.", { rawPreview: e.slice(0, 80) }), null;
	}
}
function te(e) {
	let t = {};
	return e.forEach((e) => {
		let n = typeof e.label == "string" ? e.label.trim() : "";
		n && (t[n] = typeof e.value == "string" ? e.value : "");
	}), t;
}
function _(e, t) {
	if (t !== "submit") return;
	let n = g(e);
	if (!n) {
		p.log("No submitted page id; skipping client event.");
		return;
	}
	let r = e.querySelector(`[data-formie-page][data-formie-page-id="${h(n)}"]`);
	if (!r) {
		p.log("No page section for id; skipping client event.", { pageId: n });
		return;
	}
	let i = r.getAttribute(m);
	if (i === null) return;
	let a = ee(i);
	if (!a || !Array.isArray(a.fields)) return;
	let o = te(a.fields), s = window;
	s.dataLayer = s.dataLayer || [], s.dataLayer.push(o), e.dispatchEvent(new CustomEvent("formie:client-event", {
		bubbles: !0,
		detail: { payload: o }
	})), p.log("Dispatched page client event.", {
		pageId: n,
		keys: Object.keys(o)
	});
}
//#endregion
//#region src/js/core/page-tab-errors.ts
function v(e, t) {
	if (r(e, e, "tabError", t), t) {
		e.setAttribute("data-formie-tab-error", "true");
		return;
	}
	e.removeAttribute("data-formie-tab-error");
}
function y(e) {
	let t = /* @__PURE__ */ new Set();
	e.querySelectorAll("[data-formie-page]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		r && n.querySelector("[data-formie-field-has-error]") && t.add(r);
	}), e.querySelectorAll("[data-formie-tab]").forEach((e) => {
		let n = e, r = n.getAttribute("data-formie-page-id");
		v(n, !!r && t.has(r));
	});
}
//#endregion
//#region src/js/core/submit-result-state.ts
var ne = "STALE_SUBMISSION_STATE", b = /* @__PURE__ */ new WeakMap(), x = /* @__PURE__ */ new WeakMap(), S = n("general", "submit-result");
function C(e, t, n) {
	let r = e.querySelector(`input[name="${t}"]`);
	r || (r = document.createElement("input"), r.type = "hidden", r.name = t, e.appendChild(r)), r.value = n;
}
function w(e, t) {
	e.setAttribute("data-formie-internal-navigation", t);
}
function T(e, t) {
	e.querySelector(`input[name="${t}"]`)?.remove();
}
function re(e, t) {
	try {
		let n = new URL(e, window.location.href);
		return n.searchParams.delete(t), n.toString();
	} catch {
		return e;
	}
}
function E(e) {
	try {
		return new URL(e, window.location.href).origin === window.location.origin;
	} catch {
		return !1;
	}
}
function D(e) {
	return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function ie(e) {
	return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function ae(e, t, n) {
	return t < 0 || n < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") == "page-position" ? Math.round((t + 1) / n * 100) : Math.round(t / n * 100);
}
function O(e) {
	return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function k(e) {
	return (e.dataset.formieSubmitAction || "").trim();
}
function A(e) {
	let t = e.dataset.formieSubmitActionFormHide;
	if (t === void 0) return !1;
	let n = t.trim().toLowerCase();
	return n === "true" || n === "1" || n === "";
}
function j(e, t) {
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
function M(e) {
	let t = b.get(e);
	typeof t == "number" && (window.clearTimeout(t), b.delete(e));
}
function oe(e, t) {
	x.has(e) || x.set(e, e.innerHTML), e.textContent = t;
}
function N(e) {
	let t = x.get(e);
	t !== void 0 && (e.innerHTML = t, x.delete(e));
}
function se(e, t) {
	let n = e.querySelector("[data-formie-progress-bar]"), r = e.querySelector("[data-formie-progress-value]");
	n && (n.style.width = `${t}%`, n.setAttribute("aria-valuenow", `${t}`), n.setAttribute("data-formie-progress-state", O(t)), r && (r.textContent = `${t}%`, r.setAttribute("data-formie-progress-value", `${t}`)));
}
function ce(e, t) {
	if (!t) return;
	let n = (e.dataset.formieLoadingIndicator || "").trim();
	if (n) {
		if (t.setAttribute("data-formie-loading-indicator", n), n === "spinner") {
			r(t, e, "loading", !0), N(t), t.removeAttribute("data-formie-loading-text");
			return;
		}
		if (n === "text") {
			let n = (e.dataset.formieLoadingIndicatorText || "").trim(), r = t.textContent?.trim() || "", i = n || r;
			t.setAttribute("data-formie-loading-text", i), oe(t, i);
			return;
		}
		N(t), t.removeAttribute("data-formie-loading-text");
	}
}
function P(e) {
	return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function le(e, t) {
	e.getAttribute("data-formie-loading") !== "true" && (e.setAttribute("data-formie-loading", "true"), P(e).forEach((e) => {
		"disabled" in e && (e.disabled ? e.setAttribute("data-formie-was-disabled", "true") : e.removeAttribute("data-formie-was-disabled"), e.disabled = !0);
	}), t && (t.setAttribute("data-formie-loading", "true"), ce(e, t)));
}
function F(e) {
	e.removeAttribute("data-formie-loading"), P(e).forEach((t) => {
		if ("disabled" in t) {
			let e = t;
			e.disabled = e.getAttribute("data-formie-was-disabled") === "true";
		}
		N(t), t.removeAttribute("data-formie-was-disabled"), t.removeAttribute("data-formie-loading"), r(t, e, "loading", !1), t.removeAttribute("data-formie-loading-indicator"), t.removeAttribute("data-formie-loading-text");
	});
}
function I(e, t) {
	let n = D(e), o = ie(e), s = n.findIndex((e) => e.getAttribute("data-formie-page-id") === t);
	n.forEach((n) => {
		n.getAttribute("data-formie-page-id") === t ? (n.removeAttribute("data-formie-page-hidden"), i(n, e, "pageHidden")) : (n.setAttribute("data-formie-page-hidden", "true"), a(n, e, "pageHidden"));
	}), o.forEach((n, i) => {
		let a = n.getAttribute("data-formie-page-id") === t, o = s > -1 && i < s;
		r(n, e, "tabCurrent", a), r(n, e, "tabComplete", o), a ? n.setAttribute("aria-current", "page") : n.removeAttribute("aria-current"), o ? n.setAttribute("data-formie-tab-complete", "true") : n.removeAttribute("data-formie-tab-complete");
	}), s > -1 && n.length > 0 && se(e, ae(e, s, n.length)), C(e, "pageId", t), y(e);
}
function L(e, t) {
	let n = t.meta?.submissionUid;
	typeof n == "string" && n.trim() !== "" && C(e, "submissionUid", n);
	let r = (t.meta?.session)?.continuation?.continuationToken;
	typeof r == "string" && r.trim() !== "" ? C(e, "continuationToken", r) : T(e, "continuationToken");
}
function R(e) {
	let t = e.getAttribute("action");
	t && e.setAttribute("action", re(t, "resumeToken"));
	try {
		let e = new URL(window.location.href);
		if (!e.searchParams.has("resumeToken")) return;
		e.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
	} catch {}
}
function z(e, t) {
	let n = t.meta?.resumeUrl;
	if (typeof n != "string" || n.trim() === "") return;
	let r = n.trim();
	if (E(r)) {
		e.getAttribute("action") && e.setAttribute("action", r);
		try {
			let e = new URL(r, window.location.href);
			window.history.replaceState({}, document.title, `${e.pathname}${e.search}${e.hash}`);
		} catch {}
	}
}
function B(t, n = {}) {
	let r = t.formieValidation, i = D(t)[0]?.getAttribute("data-formie-page-id");
	if (M(t), t.reset(), n.preserveHiddenState || j(t, !1), T(t, "submissionId"), T(t, "submissionUid"), T(t, "continuationToken"), T(t, "pageId"), R(t), r?.resetLiveState(), i) {
		I(t, i), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
		return;
	}
	y(t), t.dispatchEvent(new CustomEvent(e("reset"), { bubbles: !0 }));
}
function V(e) {
	return e.code === ne || e.meta?.resetState === !0;
}
function H(e, n) {
	let r = n.submitData, i = /* @__PURE__ */ new Set(), a = !1;
	if (Array.isArray(r) && r.length > 0) {
		let n = r.filter((e) => typeof e == "object" && !!e && "event" in e && typeof e.event == "string");
		for (let r of n) {
			let n = t(r.event);
			i.add(n), S.log("Dispatching submitData event.", { eventName: n }), n.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(n, {
				bubbles: !0,
				detail: { data: r.data }
			}));
		}
	}
	let o = n.meta || {}, s = (o.paymentAction && typeof o.paymentAction == "object" ? o.paymentAction : null) || (o.paymentDecision && typeof o.paymentDecision == "object" ? o.paymentDecision.action : null), c = s ? String(s.event || "") : "", l = s ? s.payload : void 0, u = t(c);
	return u && !i.has(u) && (u.startsWith("formie:payment:") && (a = !0), e.dispatchEvent(new CustomEvent(u, {
		bubbles: !0,
		detail: { data: l }
	})), S.log("Dispatching fallback payment action event.", { eventName: u })), { hasPaymentFollowUpEvent: a };
}
function U(e, t, n) {
	if (S.log("Applying submit result state.", {
		ok: t.ok,
		action: n,
		code: t.code,
		hasRedirect: !!t.redirect?.url,
		hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
	}), V(t)) {
		B(e), S.log("Resetting state due to stale/reset marker.");
		return;
	}
	let r = H(e, t);
	if (!t.ok && t.redirect?.url && !r.hasPaymentFollowUpEvent) {
		S.log("Applying redirect fallback for failed result.", {
			url: t.redirect.url,
			target: t.redirect.target
		}), M(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank") : (w(e, "redirect"), window.location.href = t.redirect.url);
		return;
	}
	if (L(e, t), !t.ok) {
		S.log("Non-redirect failure; keeping current form state."), M(e);
		return;
	}
	if (_(e, n), t.nextPage?.id) {
		M(e), e.formieValidation?.resetLiveState(), I(e, t.nextPage.id), S.log("Advanced to next page.", { nextPageId: t.nextPage.id });
		return;
	}
	if (n === "save") {
		M(e), z(e, t), S.log("Applied save/resume token state.");
		return;
	}
	if (n === "submit" && !t.redirect?.url) {
		let t = k(e), n = t === "message" && A(e);
		if (t === "reload") {
			M(e), w(e, "reload"), window.location.reload();
			return;
		}
		if (t === "reset") {
			B(e);
			return;
		}
		M(e), B(e, { preserveHiddenState: n });
		return;
	}
	if (n === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
		let t = k(e) === "message" && A(e);
		M(e), B(e, { preserveHiddenState: t });
		return;
	}
	M(e);
}
//#endregion
//#region src/js/modules/payments/constants.ts
var ue = 2500, de = {
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
function fe(e) {
	return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function pe(e) {
	return `fields[${fe(e)}]`;
}
function me(e, t) {
	let n = pe(t), r = Array.from(e.querySelectorAll(`[name="${n}"]`)), i = Array.from(e.querySelectorAll(`[name="${n}[]"]`));
	return (i.length ? i : r).filter((e) => e instanceof HTMLElement);
}
function W(e, t) {
	let n = me(e, t);
	for (let e of n) {
		let t = e.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
		if (t) return t;
	}
	return "";
}
function G(e) {
	let t = e.replace(/[^\d.,-]/g, ""), n = t.includes(","), r = t.includes(".");
	return t = n && r ? t.replace(/\./g, "").replace(/,/, ".") : n && !r ? t.replace(/,/, ".") : t.replace(/,/g, ""), parseFloat(t);
}
//#endregion
//#region src/js/modules/payments/utils.ts
function K(e, t) {
	let n = t.replace(/"/g, "\\\"");
	return e.querySelector(`input[name$="[${n}]"]`) || e.querySelector(`input[name$="${n}"]`);
}
function q(e, t) {
	let n = t.find((t) => {
		let n = K(e, t);
		return !n || String(n.value || "").trim() === "";
	});
	return {
		ok: !n,
		missingSuffix: n
	};
}
async function J(e, t, n) {
	let r = q(e, t);
	if (r.ok) return r;
	let i = Date.now() + Math.max(n, 0);
	for (; Date.now() < i;) {
		await s(120);
		let n = q(e, t);
		if (n.ok) return n;
	}
	return q(e, t);
}
//#endregion
//#region src/js/modules/payments/host.ts
var he = new Set([
	"handle",
	"requiredInputSuffixes",
	"waitForValueMs",
	"errorMessage"
]), Y = "[data-payment-success]", X = "[data-payment-error]";
function ge(e, t) {
	return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function _e(e, t, n) {
	let r = t || {}, i = Object.entries(r).reduce((e, [t, n]) => (he.has(t) || (e[t] = n), e), {}), a = Array.isArray(r.requiredInputSuffixes) ? r.requiredInputSuffixes.map(String).filter(Boolean) : n.defaultRequiredInputSuffixes || [], o = Number(r.waitForValueMs ?? n.defaultWaitForValueMs ?? 2500), s = typeof r.errorMessage == "string" && r.errorMessage.trim() !== "" ? r.errorMessage.trim() : "Payment authorization is incomplete.";
	return {
		handle: ge(e, r),
		transport: {
			requiredInputSuffixes: a,
			waitForValueMs: Number.isFinite(o) ? o : ue,
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
function ve(e, t) {
	let n = e.target, r = e.form, i = e.root, s = r || i, p = t.transport.requiredInputSuffixes, m = () => f(r || i), h = (e) => {
		let t = d(e, m()).value;
		return Array.isArray(t) ? t[0] || "" : String(t || "");
	};
	return {
		root: i,
		form: r,
		field: n,
		updateInputs: (e, t) => {
			let r = Array.isArray(e) ? e : [e];
			for (let e of r) {
				let r = K(s, e) ?? n.querySelector(`input[name*="${e}"]`);
				r && (r.value = t);
			}
		},
		addError: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, o = t.querySelector(X);
			o && o.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-error", ""), s.textContent = e, a(s, r || i, "fieldError"), t.appendChild(s);
		},
		removeError: () => {
			n.querySelector(X)?.remove();
		},
		addSuccess: (e) => {
			let t = n.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || n, o = t.querySelector(Y);
			o && o.remove();
			let s = document.createElement("div");
			s.setAttribute("data-payment-success", ""), s.textContent = e, a(s, r || i, "successMessage"), t.appendChild(s);
		},
		removeSuccess: () => {
			n.querySelector(Y)?.remove();
		},
		hasToken: () => q(s, p).ok,
		waitForToken: (e = t.transport.waitForValueMs) => J(s, p, e).then((e) => e.ok),
		getFieldValue: (e, t = "string") => {
			let n = h(e);
			return t === "float" || t === "int" || t === "number" ? G(n) : n;
		},
		resolveAmount: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed), s = String(a ?? "").trim(), c = typeof a == "number" ? a : G(s);
			if (Number.isFinite(c) && c > 0) return {
				ok: !0,
				value: c
			};
			if (s !== "") {
				let e = h(s), n = G(e);
				if (Number.isFinite(n) && n > 0) return {
					ok: !0,
					value: n
				};
				let r = W(t, s);
				if (!e) return {
					ok: !1,
					error: r ? o("Provide a value for \"{label}\" to proceed.", { label: r }) : o("Provide a payment amount to proceed.")
				};
			}
			return {
				ok: !1,
				error: o("Payment amount must be greater than 0.")
			};
		},
		resolveCurrency: (e) => {
			let t = r || i, n = String(e.type || "").toLowerCase() === "dynamic" && typeof e.variable == "string" && e.variable.trim() !== "", a = e.value ?? (n ? e.variable : e.fixed ?? e.defaultCurrency ?? ""), s = String(a ?? "").trim(), c = s.toUpperCase();
			if (/^[A-Z]{3}$/.test(c) && !n) return {
				ok: !0,
				value: c
			};
			if (s !== "") {
				let e = String(h(s) || "").trim(), n = e.toUpperCase();
				if (/^[A-Z]{3}$/.test(n)) return {
					ok: !0,
					value: n
				};
				let r = W(t, s);
				if (!e) return {
					ok: !1,
					error: r ? o("Provide a value for \"{label}\" to proceed.", { label: r }) : o("Provide a payment currency to proceed.")
				};
			}
			return {
				ok: !1,
				error: o("Payment currency must be a valid 3-letter code.")
			};
		},
		watchFieldValueChanges: (e, t, n = 600) => {
			let a = r || i, o = e.map((e) => String(e || "").trim()).filter(Boolean);
			if (o.length === 0) return () => {};
			let s = m(), d = /* @__PURE__ */ new Set();
			o.forEach((e) => {
				let t = l(e), n = s.get(t);
				if (n?.names?.length) {
					n.names.forEach((e) => {
						d.add(e);
					});
					return;
				}
				let r = u(t);
				r && (d.add(r), d.add(`${r}[]`));
			});
			let f = c(() => {
				t();
			}, n), p = (e) => {
				let t = e.target?.name || "";
				!t || !d.has(t) || f();
			};
			return a.addEventListener("input", p), a.addEventListener("change", p), () => {
				a.removeEventListener("input", p), a.removeEventListener("change", p);
			};
		},
		triggerSubmit: () => {
			r && r.setAttribute("data-formie-internal-resubmit", "true"), r && typeof r.requestSubmit == "function" ? r.requestSubmit() : r && r.submit();
		},
		releaseSubmitLoading: () => {
			r && (r.removeAttribute("data-formie-internal-resubmit"), F(r));
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
var Q = n("payments");
function $(e) {
	let t = e;
	return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function ye(e) {
	let t = e.defaultRequiredInputSuffixes ?? de[e.id] ?? [];
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
			let o = _e(e.id, n.options || {}, { defaultRequiredInputSuffixes: t }), s = ve(n, o), c = {
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
					let i = await J(n.form || n.root, o.transport.requiredInputSuffixes, o.transport.waitForValueMs);
					i.ok || (Q.warn("Required payment input(s) missing.", {
						moduleId: e.id,
						handle: o.handle,
						missingSuffix: i.missingSuffix
					}), t.abort(o.transport.errorMessage));
				},
				onAfterStage: async (t, r) => {
					t.stage !== "dispatch" || !e.onAfterSubmit || await e.onAfterSubmit({
						field: n.target,
						services: s,
						options: o,
						provider: o.provider,
						result: r
					});
				}
			};
		}
	};
}
//#endregion
//#region src/js/modules/payments/api.ts
var be = ye;
//#endregion
export { j as a, _ as c, F as i, I as n, le as o, U as r, y as s, be as t };
