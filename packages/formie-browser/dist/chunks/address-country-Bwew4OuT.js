import { A as i, f as a, j as c } from "./index-CZtn5KAB.js";
import { g as l } from "./shared-BDEKVuB5.js";
import { f as d } from "./country-from-ip-DXdvu0Xm.js";
const f = "address-country", p = i.country, u = c("fields", "address-country");
function y(t, e, o) {
  return o === "full" && e ? e : t.toUpperCase();
}
function m(t, e = []) {
  if (!e.length)
    return !0;
  const o = t.toUpperCase();
  return e.some((r) => r.toUpperCase() === o);
}
function C(t, e) {
  return Array.from(t.options).some((o) => o.value === e);
}
async function g(t, e) {
  for (let o = 0; o < 20; o += 1) {
    const r = t._formieTomSelect;
    if (r || !t.hasAttribute("data-formie-combobox-input")) {
      r ? r.setValue(e, !0) : t.value = e, t.dispatchEvent(new Event("change", { bubbles: !0 }));
      return;
    }
    await new Promise((n) => {
      window.setTimeout(n, 50);
    });
  }
  u.warn("Timed out waiting for country combobox initialisation.");
}
async function w(t, e) {
  const o = a(t, "country");
  if (!(o instanceof HTMLSelectElement)) {
    u.warn("Country control not found or not a select; skipping preselect.");
    return;
  }
  if (o.value.trim())
    return;
  const r = await d(e.countryFromIpAction);
  if (!r?.countryCode)
    return;
  if (!m(r.countryCode, e.countryAllowed)) {
    u.log("Detected country is not in the allowed list; skipping preselect.", {
      countryCode: r.countryCode
    });
    return;
  }
  const n = e.countryOptionValue === "full" ? "full" : "short", s = y(
    r.countryCode,
    r.countryName,
    n
  );
  if (!C(o, s)) {
    u.warn("Detected country is not available in the country dropdown; skipping preselect.", {
      selectValue: s
    });
    return;
  }
  await g(o, s), u.log("Preselected country from IP.", { selectValue: s });
}
const S = {
  id: f,
  kind: "field",
  match: (t) => !!t.target.querySelector(p),
  setup: async (t) => {
    const e = t.options || {};
    if (!e.countryPreselectFromIp)
      return {
        destroy: () => {
        }
      };
    const o = l(t), r = o.map(async (n) => {
      const s = n.closest('[data-formie-field-type="address"]') || n.closest("[data-formie-address-field-layout]")?.closest("[data-formie-field]") || n;
      s instanceof HTMLElement && await w(s, e);
    });
    return await Promise.all(r), u.log("Module setup.", { fieldCount: o.length }), {
      destroy: () => {
        u.log("Module destroy.", { fieldCount: o.length });
      }
    };
  }
};
export {
  S as addressCountryModule
};
