import { t as e } from "./request-profile-DhwkeCpS.js";
import { t } from "./dist-ZLeW0zZ3.js";
import { s as n } from "./event-names-BCI2FLD8.js";
import { t as r } from "./api-CBnjN24r.js";
import { t as i } from "./debug-BV0DvdHx.js";
import { a } from "./theme-classes-DAQuEqdP.js";
import { t as o } from "./csrf-DxHg_ZYt.js";
import { r as s } from "./scripts-CbQ7agX3.js";
import { t as c } from "./styles-BfoIZwJp.js";
//#endregion
//#region src/js/modules/payments/opayo.ts
c("opayo", ["@layer formie-theme{.formie-opayo-drop-in{box-sizing:border-box;width:100%;min-height:10rem}}"]);
var l = "FORMIE_OPAYO_SCRIPT", u = "https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js", d = "https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js", f = "[data-formie-opayo-drop-in]", p = i("payments", "opayo"), m = n("opayo", "challenge"), h = "formie:payment:opayo:challenge:response";
function g(e) {
	return e.checkoutMode === "dropIn";
}
async function _(n) {
	let { form: r, handle: i, sessionToken: a, services: s } = n, c = new FormData();
	o(c, r), c.set("action", "formie/payment-sessions/initialize"), c.append("handle", i), c.append("sessionToken", a);
	try {
		let i = await t(n.sessionEndpoint || r.action, {
			method: "POST",
			body: c
		}, e(r));
		return i.status < 200 || i.status >= 300 ? (s.addError(`${i.status}: ${i.statusText}`), p.warn("Merchant session request failed.", {
			status: i.status,
			statusText: i.statusText
		}), null) : (await i.json()).merchantSessionKey || (s.addError("Unable to get merchant session."), p.warn("merchantSessionKey missing in session response."), null);
	} catch {
		return s.addError("Network error. Please try again."), p.warn("Network error requesting merchant session."), null;
	}
}
function v(e) {
	return e.id ||= `formie-opayo-drop-in-${Math.random().toString(36).slice(2, 9)}`, e.id;
}
var y = r({
	moduleId: "formie:opayo",
	defaultRequiredInputSuffixes: ["opayoTokenId"],
	load: async (e) => {
		let { provider: t } = e.options, n = t.useSandbox ? d : u, r = g(t) ? "sagepayCheckout" : "sagepayOwnForm";
		return await s(r, {
			id: l,
			src: n,
			timeoutMs: 1e4
		}), null;
	},
	mount: async ({ field: e, services: t, provider: n }) => {
		if (!g(n)) return null;
		let r = t.form, i = window.sagepayCheckout, a = e.querySelector(f);
		if (!r?.action) return t.addError("Form action is missing."), p.warn("Missing form action before drop-in mount."), null;
		if (!i) return t.addError("Opayo script failed to load."), p.warn("sagepayCheckout global not available."), null;
		if (!a) return t.addError("Opayo drop-in container is missing."), p.warn("Drop-in container not found in payment field."), null;
		let o = n.handle || "opayo", s = await _({
			form: r,
			handle: o,
			sessionToken: n.sessionToken || "",
			sessionEndpoint: n.sessionEndpoint,
			services: t
		});
		if (!s) return null;
		let c = v(a), l = {
			checkout: null,
			merchantSessionKey: s,
			pendingAuthorize: null,
			retriedTokenise: !1
		};
		return l.checkout = i({
			merchantSessionKey: s,
			containerSelector: `#${c}`,
			onTokenise: (e) => {
				let i = l.pendingAuthorize;
				if (l.pendingAuthorize = null, !i) {
					p.warn("Drop-in tokenisation completed without a pending authorize step.");
					return;
				}
				if (e.success && e.cardIdentifier) {
					t.updateInputs("opayoTokenId", e.cardIdentifier), t.updateInputs("opayoSessionKey", l.merchantSessionKey), p.log("Drop-in tokenization succeeded.", { hasCardIdentifier: !!e.cardIdentifier }), i(!0);
					return;
				}
				if (!l.retriedTokenise) {
					l.retriedTokenise = !0, _({
						form: r,
						handle: o,
						sessionToken: n.sessionToken || "",
						sessionEndpoint: n.sessionEndpoint,
						services: t
					}).then((n) => {
						if (!n) {
							t.addError(e.errors?.[0]?.message || "Tokenization failed."), p.warn("Drop-in tokenization failed after session refresh.", e), i(!1);
							return;
						}
						l.merchantSessionKey = n, l.pendingAuthorize = i, l.checkout.tokenise({ newMerchantSessionKey: n });
					});
					return;
				}
				t.addError(e.errors?.[0]?.message || "Tokenization failed."), p.warn("Drop-in tokenization failed.", e), i(!1);
			}
		}), p.log("Drop-in checkout mounted.", { containerId: c }), l;
	},
	unmount: async ({ widget: e }) => {
		e?.checkout?.destroy?.();
	},
	onBeforePayment: async (e) => {
		let { field: t, services: n, options: r, provider: i, widget: a } = e, o = i.handle || "opayo", s = n.form;
		if (!s?.action) return n.addError("Form action is missing."), p.warn("Missing form action before authorize."), !1;
		if (g(i)) return a ? (a.retriedTokenise = !1, new Promise((e) => {
			a.pendingAuthorize = e, a.checkout.tokenise();
		})) : (n.addError("Opayo drop-in checkout is not ready."), p.warn("Drop-in authorize requested before widget mount."), !1);
		let c = window.sagepayOwnForm;
		if (!c) return n.addError("Opayo script failed to load."), p.warn("sagepayOwnForm global not available."), !1;
		let l = t.querySelector("[data-opayo-card=\"cardholder-name\"]")?.value ?? "", u = t.querySelector("[data-opayo-card=\"card-number\"]")?.value ?? "", d = t.querySelector("[data-opayo-card=\"expiry-date\"]")?.value ?? "", f = t.querySelector("[data-opayo-card=\"security-code\"]")?.value ?? "";
		u = u.replace(/[\s/]/g, ""), d = d.replace(/[\s/]/g, "");
		let m = await _({
			form: s,
			handle: o,
			sessionToken: i.sessionToken || "",
			sessionEndpoint: i.sessionEndpoint,
			services: n
		});
		return m ? new Promise((e) => {
			c({ merchantSessionKey: m }).tokeniseCardDetails({
				cardDetails: {
					cardholderName: l,
					cardNumber: u,
					expiryDate: d,
					securityCode: f
				},
				onTokenised: (t) => {
					t.success && t.cardIdentifier ? (n.updateInputs("opayoTokenId", t.cardIdentifier), n.updateInputs("opayoSessionKey", m), p.log("Tokenization succeeded.", { hasCardIdentifier: !!t.cardIdentifier }), e(!0)) : (n.addError(t.errors?.[0]?.message || "Tokenization failed."), p.warn("Tokenization failed.", t), e(!1));
				}
			});
		}) : !1;
	},
	setup: async (e) => {
		let { services: t } = e;
		e.target;
		let n = null, r = !1, i = () => {
			n?.parentNode && n.parentNode.removeChild(n), n = null;
		}, o = t.events.onForm(m, ((e) => {
			let o = e.detail?.data;
			if (!o?.acsUrl || !o?.creq) return;
			r = !1, p.log("Received payment challenge event.", {
				hasAcsUrl: !!o.acsUrl,
				hasCreq: !!o.creq
			});
			let s = t.form, c = s?.querySelector("input[name*=\"opayoSessionKey\"]")?.value || "", l = document.createElement("div");
			l.className = "formie-modal", l.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`, l.innerHTML = "\n                <div class=\"formie-modal-backdrop\" data-dialog-close></div>\n                <div class=\"formie-modal-content\">\n                    <div data-formie-opayo-loading style=\"--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;\"></div>\n                    <iframe width=\"100%\" height=\"100%\" style=\"width: 100%; height: 100%; position: relative; z-index: 1;\"></iframe>\n                </div>\n            ";
			let u = l.querySelector("[data-formie-opayo-loading]");
			u && a(u, s, "loading", !0);
			let d = l.querySelector("iframe"), f = (e) => e.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;"), m = o.returnUrl || o.redirectUrl || "", h = `<form action="${f(o.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${f(o.creq || "")}" />
                <input type="hidden" name="threeDSSessionData" value="${f(o.threeDSSessionData || "")}" />
                <input type="hidden" name="MD" value="${f(c)}" />
                <input type="hidden" name="TermUrl" value="${f(m)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${f(m)}" />
            </form><script>document.forms[0].submit();<\/script>`;
			i(), document.body.appendChild(l), n = l, d?.contentWindow && (d.contentWindow.document.open(), d.contentWindow.document.write(h), d.contentWindow.document.close());
		})), s = (e) => {
			if (e.data?.message === h) {
				if (!n) {
					p.log("Ignoring 3DS response without active dialog.");
					return;
				}
				if (r) {
					p.warn("Ignoring duplicate 3DS response while processing.");
					return;
				}
				if (r = !0, p.log("Received payment challenge response message.", e.data?.value), i(), t.removeError(), e.data?.value?.error) {
					t.addError(e.data.value.error.message), t.releaseSubmitLoading(), r = !1;
					return;
				}
				t.updateInputs("opayo3DSComplete", e.data.value?.transactionId ?? ""), t.triggerSubmit();
			}
		};
		return window.addEventListener("message", s), { destroy: () => {
			o(), window.removeEventListener("message", s), i(), r = !1;
		} };
	},
	onAfterSubmit: async ({ services: e }) => {
		e.updateInputs([
			"opayoTokenId",
			"opayoSessionKey",
			"opayo3DSComplete"
		], "");
	}
});
//#endregion
export { y as opayoModule };
