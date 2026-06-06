import { v as S } from "./index-CSO3KCTK.js";
const k = {
  940: "Invalid profile ID (tokenization request).",
  941: "Error generating token.",
  942: "Invalid Profile ID or source URL.",
  943: "Card data is invalid.",
  944: "Invalid expiration date.",
  945: "Invalid CVD."
}, I = (n) => {
  const i = (e) => {
    const r = String(e.dataKey ?? e.data_key ?? "").trim(), t = String(e.responseCode ?? e.response_code ?? "").trim(), d = String(e.responseMessage ?? e.response_message ?? "").trim(), a = String(e.error ?? e.errorMessage ?? e.message ?? "").trim();
    if (r)
      return { token: r, error: "" };
    if (t && t !== "001") {
      const s = k[t] || `Moneris response code ${t}.`;
      return { token: "", error: d || s };
    }
    return { token: "", error: a };
  };
  if (n && typeof n == "object")
    return i(n);
  if (typeof n != "string")
    return { token: "", error: "" };
  const o = n.trim();
  if (!o)
    return { token: "", error: "" };
  try {
    const e = JSON.parse(o);
    if (e && typeof e == "object")
      return i(e);
  } catch {
  }
  if (o.includes("=")) {
    const e = new URLSearchParams(o), r = (e.get("dataKey") || e.get("data_key") || "").trim(), t = (e.get("responseCode") || e.get("response_code") || "").trim(), d = (e.get("responseMessage") || e.get("response_message") || "").trim(), a = (e.get("error") || e.get("message") || "").trim();
    if (r)
      return { token: r, error: "" };
    if (t && t !== "001") {
      const s = k[t] || `Moneris response code ${t}.`;
      return { token: "", error: d || s };
    }
    return { token: "", error: a };
  }
  return /^[A-Za-z0-9._-]{8,}$/.test(o) ? { token: o, error: "" } : { token: "", error: "" };
}, z = S({
  id: "moneris",
  defaultRequiredInputSuffixes: ["monerisTokenId"],
  load: async () => null,
  onBeforeAuthorize: async (n) => {
    const { field: i, services: o, options: e } = n, r = i, d = (e.provider.endpointUrl || "").trim(), a = i.querySelector("[data-formie-moneris-frame]");
    let s = "";
    try {
      s = new URL(d, window.location.origin).origin;
    } catch {
      s = "";
    }
    if (!a?.contentWindow || !s)
      return o.addError("Moneris frame or endpoint is missing."), !1;
    r.__formieMonerisAuthorizeCleanup?.();
    const p = (r.__formieMonerisAuthorizeRequestId || 0) + 1;
    return r.__formieMonerisAuthorizeRequestId = p, new Promise((f) => {
      let u = !1, g = "", l = 0;
      const c = () => {
        window.removeEventListener("message", M), window.clearTimeout(l), r.__formieMonerisAuthorizeCleanup === c && (r.__formieMonerisAuthorizeCleanup = null);
      }, M = (_) => {
        if (u || p !== r.__formieMonerisAuthorizeRequestId || _.origin !== s)
          return;
        const m = I(_.data);
        m.token ? (u = !0, c(), o.updateInputs("monerisTokenId", m.token), f(!0)) : m.error && (g = m.error);
      };
      r.__formieMonerisAuthorizeCleanup = c, l = window.setTimeout(() => {
        u || (u = !0, c(), o.addError(g || "Moneris tokenization timed out. Please try again."), f(!1));
      }, 1e4), window.addEventListener("message", M);
      try {
        a.contentWindow?.postMessage("tokenize", s);
      } catch {
        u = !0, c(), o.addError("Moneris tokenization could not be started."), f(!1);
      }
    });
  },
  setup: async (n) => {
    const { services: i } = n, o = (r) => {
      if (!r.origin.includes("moneris"))
        return;
      const t = I(r.data);
      t.token && i.updateInputs("monerisTokenId", t.token);
    }, e = i.events.onRoot("message", o);
    return {
      destroy: () => {
        e();
        const r = n.target;
        r.__formieMonerisAuthorizeCleanup?.(), r.__formieMonerisAuthorizeCleanup = null;
      }
    };
  },
  onAfterSubmit: async ({ services: n }) => {
    n.updateInputs("monerisTokenId", "");
  }
});
export {
  z as monerisModule
};
