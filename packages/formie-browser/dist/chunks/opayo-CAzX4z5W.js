import { v, x as w, h as b } from "./index-CSO3KCTK.js";
import { l as E } from "./scripts-ixdSNQlR.js";
const I = "FORMIE_OPAYO_SCRIPT", D = "https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js", T = "https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js", t = b("payments", "opayo"), k = w("opayo", "challenge"), C = "formie:payment:opayo:challenge:response", R = v({
  id: "opayo",
  defaultRequiredInputSuffixes: ["opayoTokenId"],
  load: async (c) => {
    const { provider: a } = c.options, d = !!a.useSandbox ? T : D;
    return await E("sagepayOwnForm", {
      id: I,
      src: d,
      timeoutMs: 1e4
    }), null;
  },
  onBeforeAuthorize: async (c) => {
    const { field: a, services: e, options: d } = c, p = d.provider, g = p.handle || "opayo", m = e.form;
    if (!m?.action)
      return e.addError("Form action is missing."), t.warn("Missing form action before authorize."), !1;
    const s = window.sagepayOwnForm;
    if (!s)
      return e.addError("Opayo script failed to load."), t.warn("sagepayOwnForm global not available."), !1;
    const r = a.querySelector('[data-opayo-card="cardholder-name"]')?.value ?? "";
    let y = a.querySelector('[data-opayo-card="card-number"]')?.value ?? "", f = a.querySelector('[data-opayo-card="expiry-date"]')?.value ?? "";
    const h = a.querySelector('[data-opayo-card="security-code"]')?.value ?? "";
    return y = y.replace(/[\s/]/g, ""), f = f.replace(/[\s/]/g, ""), new Promise((n) => {
      const i = new FormData();
      i.append("action", "formie/payment-webhooks/process-callback"), i.append("merchantSessionKey", "true"), i.append("handle", g), i.append("sessionToken", p.sessionToken || ""), fetch(m.action, {
        method: "POST",
        body: i
      }).then(async (o) => {
        if (o.status < 200 || o.status >= 300) {
          e.addError(`${o.status}: ${o.statusText}`), t.warn("Merchant session request failed.", {
            status: o.status,
            statusText: o.statusText
          }), n(!1);
          return;
        }
        try {
          const u = (await o.json()).merchantSessionKey;
          if (!u) {
            e.addError("Unable to get merchant session."), t.warn("merchantSessionKey missing in callback response."), n(!1);
            return;
          }
          s({ merchantSessionKey: u }).tokeniseCardDetails({
            cardDetails: {
              cardholderName: r,
              cardNumber: y,
              expiryDate: f,
              securityCode: h
            },
            onTokenised: (l) => {
              l.success && l.cardIdentifier ? (e.updateInputs("opayoTokenId", l.cardIdentifier), e.updateInputs("opayoSessionKey", u), t.log("Tokenization succeeded.", {
                hasCardIdentifier: !!l.cardIdentifier
              }), n(!0)) : (e.addError(l.errors?.[0]?.message || "Tokenization failed."), t.warn("Tokenization failed.", l), n(!1));
            }
          });
        } catch {
          e.addError("Unable to parse merchant session response."), t.warn("Failed to parse merchant session response."), n(!1);
        }
      }).catch(() => {
        e.addError("Network error. Please try again."), t.warn("Network error requesting merchant session."), n(!1);
      });
    });
  },
  setup: async (c) => {
    const { services: a } = c;
    c.target;
    let e = null, d = !1;
    const p = () => {
      e?.parentNode && e.parentNode.removeChild(e), e = null;
    }, g = a.events.onForm(k, ((s) => {
      const r = s.detail?.data;
      if (!r?.acsUrl || !r?.creq) return;
      d = !1, t.log("Received payment challenge event.", {
        hasAcsUrl: !!r.acsUrl,
        hasCreq: !!r.creq
      });
      const h = a.form?.querySelector('input[name*="opayoSessionKey"]')?.value || "", n = document.createElement("div");
      n.className = "formie-modal", n.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`, n.innerHTML = `
                <div class="formie-modal-backdrop" data-dialog-close></div>
                <div class="formie-modal-content">
                    <div class="formie-loading formie-loading-large" style="--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;"></div>
                    <iframe width="100%" height="100%" style="width: 100%; height: 100%; position: relative; z-index: 1;"></iframe>
                </div>
            `;
      const i = n.querySelector("iframe"), o = (l) => l.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;"), S = r.returnUrl || r.redirectUrl || "", u = `<form action="${o(r.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${o(r.creq || "")}" />
                <input type="hidden" name="threeDSSessionData" value="${o(r.threeDSSessionData || "")}" />
                <input type="hidden" name="MD" value="${o(h)}" />
                <input type="hidden" name="TermUrl" value="${o(S)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${o(S)}" />
            </form><script>document.forms[0].submit();<\/script>`;
      p(), document.body.appendChild(n), e = n, i?.contentWindow && (i.contentWindow.document.open(), i.contentWindow.document.write(u), i.contentWindow.document.close());
    })), m = (s) => {
      if (s.data?.message === C) {
        if (!e) {
          t.log("Ignoring 3DS response without active dialog.");
          return;
        }
        if (d) {
          t.warn("Ignoring duplicate 3DS response while processing.");
          return;
        }
        if (d = !0, t.log("Received payment challenge response message.", s.data?.value), p(), a.removeError(), s.data?.value?.error) {
          a.addError(s.data.value.error.message), a.releaseSubmitLoading(), d = !1;
          return;
        }
        a.updateInputs("opayo3DSComplete", s.data.value?.transactionId ?? ""), a.triggerSubmit();
      }
    };
    return window.addEventListener("message", m), {
      destroy: () => {
        g(), window.removeEventListener("message", m), p(), d = !1;
      }
    };
  },
  onAfterSubmit: async ({ services: c }) => {
    c.updateInputs(["opayoTokenId", "opayoSessionKey", "opayo3DSComplete"], "");
  }
});
export {
  R as opayoModule
};
