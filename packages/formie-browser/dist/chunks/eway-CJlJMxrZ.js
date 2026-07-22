import { x as f, w } from "./index-CZtn5KAB.js";
import { l as m } from "./scripts--tQDv1Kx.js";
const s = [
  {
    id: "FORMIE_EWAY_SCRIPT_MIN",
    src: "https://secure.ewaypayments.com/scripts/eCrypt.min.js"
  },
  {
    id: "FORMIE_EWAY_SCRIPT",
    src: "https://secure.ewaypayments.com/scripts/eCrypt.js"
  }
];
async function i() {
  let t = null;
  for (const r of s) {
    try {
      return await m("eCrypt", {
        id: r.id,
        src: r.src,
        timeoutMs: 1e4
      }), await w(() => {
        const o = window.eCrypt;
        return o && typeof o.encryptValue == "function" ? o : null;
      }, {
        timeoutMs: 1e4,
        intervalMs: 50
      });
    } catch (e) {
      t = e instanceof Error ? e : new Error("Unknown eWay script load error.");
    }
    s.forEach(({ id: e }) => {
      document.getElementById(e)?.remove();
    });
  }
  throw t || new Error("Eway encryption script failed to load.");
}
const C = f({
  id: "eway",
  defaultRequiredInputSuffixes: ["ewayTokenData"],
  load: async (t) => {
    const { provider: r } = t.options;
    return r.cseKey?.trim() ? i() : (console.error("[formie] Missing cseKey for Eway."), null);
  },
  onBeforeAuthorize: async (t) => {
    const { field: r, services: e, provider: o, api: y } = t, n = o.cseKey;
    if (!n?.trim())
      return e.addError("Missing cseKey for Eway."), !1;
    let c = y;
    if (!c?.encryptValue)
      try {
        c = await i();
      } catch (a) {
        return e.addError(a instanceof Error ? a.message : "Eway encryption script failed to load."), !1;
      }
    const d = r.querySelector('[data-eway-card="cardholder-name"]')?.value ?? "", u = r.querySelector('[data-eway-card="card-number"]')?.value ?? "", l = r.querySelector('[data-eway-card="expiry-date"]')?.value ?? "", p = r.querySelector('[data-eway-card="security-code"]')?.value ?? "";
    try {
      const a = {
        cardholderName: d,
        cardNumber: c.encryptValue(u, n),
        expiryDate: l,
        securityCode: c.encryptValue(p, n)
      };
      return e.updateInputs("ewayTokenData", JSON.stringify(a)), !0;
    } catch (a) {
      return e.addError(a instanceof Error ? a.message : "Failed to encrypt card details."), !1;
    }
  },
  onAfterSubmit: async ({ services: t }) => {
    t.updateInputs("ewayTokenData", "");
  }
});
export {
  C as ewayModule
};
