import { x as I, y as T, j as b } from "./index-CZtn5KAB.js";
import { e as E } from "./styles-C3aqgtek.js";
import { l as v } from "./scripts--tQDv1Kx.js";
const D = "@layer formie-theme{.formie-opayo-drop-in{width:100%;min-height:10rem;box-sizing:border-box}}";
E("opayo", [D]);
const C = "FORMIE_OPAYO_SCRIPT", z = "https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js", q = "https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js", N = "[data-formie-opayo-drop-in]", a = b("payments", "opayo"), O = T("opayo", "challenge"), A = "formie:payment:opayo:challenge:response";
function h(n) {
  return n.checkoutMode === "dropIn";
}
async function S(n) {
  const { form: e, handle: o, sessionToken: c, services: r } = n, i = new FormData();
  i.append("action", "formie/payment-webhooks/process-callback"), i.append("merchantSessionKey", "true"), i.append("handle", o), i.append("sessionToken", c);
  try {
    const s = await fetch(e.action, {
      method: "POST",
      body: i
    });
    if (s.status < 200 || s.status >= 300)
      return r.addError(`${s.status}: ${s.statusText}`), a.warn("Merchant session request failed.", {
        status: s.status,
        statusText: s.statusText
      }), null;
    const t = (await s.json()).merchantSessionKey;
    return t || (r.addError("Unable to get merchant session."), a.warn("merchantSessionKey missing in callback response."), null);
  } catch {
    return r.addError("Network error. Please try again."), a.warn("Network error requesting merchant session."), null;
  }
}
function R(n) {
  return n.id || (n.id = `formie-opayo-drop-in-${Math.random().toString(36).slice(2, 9)}`), n.id;
}
const _ = I({
  id: "opayo",
  defaultRequiredInputSuffixes: ["opayoTokenId"],
  load: async (n) => {
    const { provider: e } = n.options, c = !!e.useSandbox ? q : z, r = h(e) ? "sagepayCheckout" : "sagepayOwnForm";
    return await v(r, {
      id: C,
      src: c,
      timeoutMs: 1e4
    }), null;
  },
  mount: async ({ field: n, services: e, provider: o }) => {
    if (!h(o))
      return null;
    const c = e.form, r = window.sagepayCheckout, i = n.querySelector(N);
    if (!c?.action)
      return e.addError("Form action is missing."), a.warn("Missing form action before drop-in mount."), null;
    if (!r)
      return e.addError("Opayo script failed to load."), a.warn("sagepayCheckout global not available."), null;
    if (!i)
      return e.addError("Opayo drop-in container is missing."), a.warn("Drop-in container not found in payment field."), null;
    const s = o.handle || "opayo", d = await S({
      form: c,
      handle: s,
      sessionToken: o.sessionToken || "",
      services: e
    });
    if (!d)
      return null;
    const t = R(i), l = {
      checkout: null,
      merchantSessionKey: d,
      pendingAuthorize: null,
      retriedTokenise: !1
    };
    return l.checkout = r({
      merchantSessionKey: d,
      containerSelector: `#${t}`,
      onTokenise: (u) => {
        const m = l.pendingAuthorize;
        if (l.pendingAuthorize = null, !m) {
          a.warn("Drop-in tokenisation completed without a pending authorize step.");
          return;
        }
        if (u.success && u.cardIdentifier) {
          e.updateInputs("opayoTokenId", u.cardIdentifier), e.updateInputs("opayoSessionKey", l.merchantSessionKey), a.log("Drop-in tokenization succeeded.", {
            hasCardIdentifier: !!u.cardIdentifier
          }), m(!0);
          return;
        }
        if (!l.retriedTokenise) {
          l.retriedTokenise = !0, S({
            form: c,
            handle: s,
            sessionToken: o.sessionToken || "",
            services: e
          }).then((p) => {
            if (!p) {
              e.addError(u.errors?.[0]?.message || "Tokenization failed."), a.warn("Drop-in tokenization failed after session refresh.", u), m(!1);
              return;
            }
            l.merchantSessionKey = p, l.pendingAuthorize = m, l.checkout.tokenise({ newMerchantSessionKey: p });
          });
          return;
        }
        e.addError(u.errors?.[0]?.message || "Tokenization failed."), a.warn("Drop-in tokenization failed.", u), m(!1);
      }
    }), a.log("Drop-in checkout mounted.", { containerId: t }), l;
  },
  unmount: async ({ widget: n }) => {
    n?.checkout?.destroy?.();
  },
  onBeforeAuthorize: async (n) => {
    const { field: e, services: o, options: c, provider: r, widget: i } = n, s = r.handle || "opayo", d = o.form;
    if (!d?.action)
      return o.addError("Form action is missing."), a.warn("Missing form action before authorize."), !1;
    if (h(r))
      return i ? (i.retriedTokenise = !1, new Promise((f) => {
        i.pendingAuthorize = f, i.checkout.tokenise();
      })) : (o.addError("Opayo drop-in checkout is not ready."), a.warn("Drop-in authorize requested before widget mount."), !1);
    const t = window.sagepayOwnForm;
    if (!t)
      return o.addError("Opayo script failed to load."), a.warn("sagepayOwnForm global not available."), !1;
    const l = e.querySelector('[data-opayo-card="cardholder-name"]')?.value ?? "";
    let u = e.querySelector('[data-opayo-card="card-number"]')?.value ?? "", m = e.querySelector('[data-opayo-card="expiry-date"]')?.value ?? "";
    const p = e.querySelector('[data-opayo-card="security-code"]')?.value ?? "";
    u = u.replace(/[\s/]/g, ""), m = m.replace(/[\s/]/g, "");
    const g = await S({
      form: d,
      handle: s,
      sessionToken: r.sessionToken || "",
      services: o
    });
    return g ? new Promise((f) => {
      t({ merchantSessionKey: g }).tokeniseCardDetails({
        cardDetails: {
          cardholderName: l,
          cardNumber: u,
          expiryDate: m,
          securityCode: p
        },
        onTokenised: (y) => {
          y.success && y.cardIdentifier ? (o.updateInputs("opayoTokenId", y.cardIdentifier), o.updateInputs("opayoSessionKey", g), a.log("Tokenization succeeded.", {
            hasCardIdentifier: !!y.cardIdentifier
          }), f(!0)) : (o.addError(y.errors?.[0]?.message || "Tokenization failed."), a.warn("Tokenization failed.", y), f(!1));
        }
      });
    }) : !1;
  },
  setup: async (n) => {
    const { services: e } = n;
    n.target;
    let o = null, c = !1;
    const r = () => {
      o?.parentNode && o.parentNode.removeChild(o), o = null;
    }, i = e.events.onForm(O, ((d) => {
      const t = d.detail?.data;
      if (!t?.acsUrl || !t?.creq) return;
      c = !1, a.log("Received payment challenge event.", {
        hasAcsUrl: !!t.acsUrl,
        hasCreq: !!t.creq
      });
      const m = e.form?.querySelector('input[name*="opayoSessionKey"]')?.value || "", p = document.createElement("div");
      p.className = "formie-modal", p.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`, p.innerHTML = `
                <div class="formie-modal-backdrop" data-dialog-close></div>
                <div class="formie-modal-content">
                    <div class="formie-loading formie-loading-large" style="--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;"></div>
                    <iframe width="100%" height="100%" style="width: 100%; height: 100%; position: relative; z-index: 1;"></iframe>
                </div>
            `;
      const g = p.querySelector("iframe"), f = (w) => w.replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;"), y = t.returnUrl || t.redirectUrl || "", k = `<form action="${f(t.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${f(t.creq || "")}" />
                <input type="hidden" name="threeDSSessionData" value="${f(t.threeDSSessionData || "")}" />
                <input type="hidden" name="MD" value="${f(m)}" />
                <input type="hidden" name="TermUrl" value="${f(y)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${f(y)}" />
            </form><script>document.forms[0].submit();<\/script>`;
      r(), document.body.appendChild(p), o = p, g?.contentWindow && (g.contentWindow.document.open(), g.contentWindow.document.write(k), g.contentWindow.document.close());
    })), s = (d) => {
      if (d.data?.message === A) {
        if (!o) {
          a.log("Ignoring 3DS response without active dialog.");
          return;
        }
        if (c) {
          a.warn("Ignoring duplicate 3DS response while processing.");
          return;
        }
        if (c = !0, a.log("Received payment challenge response message.", d.data?.value), r(), e.removeError(), d.data?.value?.error) {
          e.addError(d.data.value.error.message), e.releaseSubmitLoading(), c = !1;
          return;
        }
        e.updateInputs("opayo3DSComplete", d.data.value?.transactionId ?? ""), e.triggerSubmit();
      }
    };
    return window.addEventListener("message", s), {
      destroy: () => {
        i(), window.removeEventListener("message", s), r(), c = !1;
      }
    };
  },
  onAfterSubmit: async ({ services: n }) => {
    n.updateInputs(["opayoTokenId", "opayoSessionKey", "opayo3DSComplete"], "");
  }
});
export {
  _ as opayoModule
};
