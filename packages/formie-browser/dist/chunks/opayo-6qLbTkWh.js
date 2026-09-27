import { t as e } from "./request-profile-DhwkeCpS.js";
import { t } from "./dist-1mhMV4JB.js";
import { s as n } from "./event-names-BCI2FLD8.js";
import { t as r } from "./api-CCpJm3qd.js";
import { t as i } from "./debug-BV0DvdHx.js";
import { t as a } from "./csrf-DxHg_ZYt.js";
import { r as o } from "./scripts-CbQ7agX3.js";
import { t as s } from "./styles-BfoIZwJp.js";
//#endregion
//#region src/js/modules/payments/opayo.ts
s("opayo", ["@layer formie-theme{.formie-opayo-drop-in{box-sizing:border-box;width:100%;min-height:10rem}}"]);
var c = "FORMIE_OPAYO_SCRIPT", l = "https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js", u = "https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js", d = "[data-formie-opayo-drop-in]", f = i("payments", "opayo"), p = n("opayo", "challenge"), m = "formie:payment:opayo:challenge:response";
function h(e) {
	return e.checkoutMode === "dropIn";
}
async function g(n) {
	let { form: r, handle: i, sessionToken: o, services: s } = n, c = new FormData();
	a(c, r), c.set("action", "formie/payment-sessions/initialize"), c.append("handle", i), c.append("sessionToken", o);
	try {
		let i = await t(n.sessionEndpoint || r.action, {
			method: "POST",
			body: c
		}, e(r));
		return i.status < 200 || i.status >= 300 ? (s.addError(`${i.status}: ${i.statusText}`), f.warn("Merchant session request failed.", {
			status: i.status,
			statusText: i.statusText
		}), null) : (await i.json()).merchantSessionKey || (s.addError("Unable to get merchant session."), f.warn("merchantSessionKey missing in session response."), null);
	} catch {
		return s.addError("Network error. Please try again."), f.warn("Network error requesting merchant session."), null;
	}
}
function _(e) {
	return e.id ||= `formie-opayo-drop-in-${Math.random().toString(36).slice(2, 9)}`, e.id;
}
var v = r({
	moduleId: "formie:opayo",
	defaultRequiredInputSuffixes: ["opayoTokenId"],
	load: async (e) => {
		let { provider: t } = e.options, n = t.useSandbox ? u : l, r = h(t) ? "sagepayCheckout" : "sagepayOwnForm";
		return await o(r, {
			id: c,
			src: n,
			timeoutMs: 1e4
		}), null;
	},
	mount: async ({ field: e, services: t, provider: n }) => {
		if (!h(n)) return null;
		let r = t.form, i = window.sagepayCheckout, a = e.querySelector(d);
		if (!r?.action) return t.addError("Form action is missing."), f.warn("Missing form action before drop-in mount."), null;
		if (!i) return t.addError("Opayo script failed to load."), f.warn("sagepayCheckout global not available."), null;
		if (!a) return t.addError("Opayo drop-in container is missing."), f.warn("Drop-in container not found in payment field."), null;
		let o = n.handle || "opayo", s = await g({
			form: r,
			handle: o,
			sessionToken: n.sessionToken || "",
			sessionEndpoint: n.sessionEndpoint,
			services: t
		});
		if (!s) return null;
		let c = _(a), l = {
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
					f.warn("Drop-in tokenisation completed without a pending authorize step.");
					return;
				}
				if (e.success && e.cardIdentifier) {
					t.updateInputs("opayoTokenId", e.cardIdentifier), t.updateInputs("opayoSessionKey", l.merchantSessionKey), f.log("Drop-in tokenization succeeded.", { hasCardIdentifier: !!e.cardIdentifier }), i(!0);
					return;
				}
				if (!l.retriedTokenise) {
					l.retriedTokenise = !0, g({
						form: r,
						handle: o,
						sessionToken: n.sessionToken || "",
						sessionEndpoint: n.sessionEndpoint,
						services: t
					}).then((n) => {
						if (!n) {
							t.addError(e.errors?.[0]?.message || "Tokenization failed."), f.warn("Drop-in tokenization failed after session refresh.", e), i(!1);
							return;
						}
						l.merchantSessionKey = n, l.pendingAuthorize = i, l.checkout.tokenise({ newMerchantSessionKey: n });
					});
					return;
				}
				t.addError(e.errors?.[0]?.message || "Tokenization failed."), f.warn("Drop-in tokenization failed.", e), i(!1);
			}
		}), f.log("Drop-in checkout mounted.", { containerId: c }), l;
	},
	unmount: async ({ widget: e }) => {
		e?.checkout?.destroy?.();
	},
	onBeforePayment: async (e) => {
		let { field: t, services: n, options: r, provider: i, widget: a } = e, o = i.handle || "opayo", s = n.form;
		if (!s?.action) return n.addError("Form action is missing."), f.warn("Missing form action before authorize."), !1;
		if (h(i)) return a ? (a.retriedTokenise = !1, new Promise((e) => {
			a.pendingAuthorize = e, a.checkout.tokenise();
		})) : (n.addError("Opayo drop-in checkout is not ready."), f.warn("Drop-in authorize requested before widget mount."), !1);
		let c = window.sagepayOwnForm;
		if (!c) return n.addError("Opayo script failed to load."), f.warn("sagepayOwnForm global not available."), !1;
		let l = t.querySelector("[data-opayo-card=\"cardholder-name\"]")?.value ?? "", u = t.querySelector("[data-opayo-card=\"card-number\"]")?.value ?? "", d = t.querySelector("[data-opayo-card=\"expiry-date\"]")?.value ?? "", p = t.querySelector("[data-opayo-card=\"security-code\"]")?.value ?? "";
		u = u.replace(/[\s/]/g, ""), d = d.replace(/[\s/]/g, "");
		let m = await g({
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
					securityCode: p
				},
				onTokenised: (t) => {
					t.success && t.cardIdentifier ? (n.updateInputs("opayoTokenId", t.cardIdentifier), n.updateInputs("opayoSessionKey", m), f.log("Tokenization succeeded.", { hasCardIdentifier: !!t.cardIdentifier }), e(!0)) : (n.addError(t.errors?.[0]?.message || "Tokenization failed."), f.warn("Tokenization failed.", t), e(!1));
				}
			});
		}) : !1;
	},
	setup: async (e) => {
		let { services: t } = e;
		e.target;
		let n = null, r = !1, i = () => {
			n?.parentNode && n.parentNode.removeChild(n), n = null;
		}, a = t.events.onForm(p, ((e) => {
			let a = e.detail?.data;
			if (!a?.acsUrl || !a?.creq) return;
			r = !1, f.log("Received payment challenge event.", {
				hasAcsUrl: !!a.acsUrl,
				hasCreq: !!a.creq
			});
			let o = t.form?.querySelector("input[name*=\"opayoSessionKey\"]")?.value || "", s = document.createElement("div");
			s.className = "formie-modal", s.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`, s.innerHTML = "\n                <div class=\"formie-modal-backdrop\" data-dialog-close></div>\n                <div class=\"formie-modal-content\">\n                    <div class=\"formie-loading formie-loading-large\" style=\"--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;\"></div>\n                    <iframe width=\"100%\" height=\"100%\" style=\"width: 100%; height: 100%; position: relative; z-index: 1;\"></iframe>\n                </div>\n            ";
			let c = s.querySelector("iframe"), l = (e) => e.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;"), u = a.returnUrl || a.redirectUrl || "", d = `<form action="${l(a.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${l(a.creq || "")}" />
                <input type="hidden" name="threeDSSessionData" value="${l(a.threeDSSessionData || "")}" />
                <input type="hidden" name="MD" value="${l(o)}" />
                <input type="hidden" name="TermUrl" value="${l(u)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${l(u)}" />
            </form><script>document.forms[0].submit();<\/script>`;
			i(), document.body.appendChild(s), n = s, c?.contentWindow && (c.contentWindow.document.open(), c.contentWindow.document.write(d), c.contentWindow.document.close());
		})), o = (e) => {
			if (e.data?.message === m) {
				if (!n) {
					f.log("Ignoring 3DS response without active dialog.");
					return;
				}
				if (r) {
					f.warn("Ignoring duplicate 3DS response while processing.");
					return;
				}
				if (r = !0, f.log("Received payment challenge response message.", e.data?.value), i(), t.removeError(), e.data?.value?.error) {
					t.addError(e.data.value.error.message), t.releaseSubmitLoading(), r = !1;
					return;
				}
				t.updateInputs("opayo3DSComplete", e.data.value?.transactionId ?? ""), t.triggerSubmit();
			}
		};
		return window.addEventListener("message", o), { destroy: () => {
			a(), window.removeEventListener("message", o), i(), r = !1;
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
export { v as opayoModule };
