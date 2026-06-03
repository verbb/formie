import { u as c } from "./index-C1ZOKiAi.js";
const p = c({
  id: "bpoint",
  defaultRequiredInputSuffixes: ["bpointToken"],
  load: async () => null,
  onBeforeAuthorize: async ({ field: e, services: t }) => {
    if ((e.querySelector('input[name$="[bpointToken]"]')?.value || "").trim() !== "")
      return !0;
    const o = e.querySelector('[data-bpoint-card="cardholder-name"]')?.value?.trim() || "", r = e.querySelector('[data-bpoint-card="card-number"]')?.value?.replace(/\s+/g, "") || "", u = e.querySelector('[data-bpoint-card="expiry-date"]')?.value || "", a = e.querySelector('[data-bpoint-card="security-code"]')?.value?.trim() || "", n = (u.match(/\d/g) || []).join("").slice(0, 4);
    if (!r || n.length !== 4 || !a)
      return t.addError("Please provide valid card details to continue."), !1;
    const i = {
      cardholderName: o,
      cardNumber: r,
      expiryDate: n,
      cvn: a
    };
    return t.updateInputs("bpointToken", JSON.stringify(i)), !0;
  },
  onAfterSubmit: async ({ services: e }) => {
    e.updateInputs("bpointToken", "");
  }
});
export {
  p as bpointModule
};
