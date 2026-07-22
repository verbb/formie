import { j as W, A as K } from "./index-CZtn5KAB.js";
import { initFormieCombobox as Y } from "./combobox--QEnV0H4.js";
import { g as B, d as V } from "./shared-BDEKVuB5.js";
const _ = "[data-formie-address-state-dynamic]", $ = "[data-formie-address-state-input]", z = "[data-formie-address-state-autofill-anchor]", J = "formie-address-autofill-start", Q = K.country, X = "address-state", Z = "formie/address/subdivisions", S = ["formie-select", "formie-dropdown-input"], tt = [0, 100, 300], T = W("fields", "address-state"), A = /* @__PURE__ */ new Map(), w = /* @__PURE__ */ new Map();
function et(t) {
  return t.closest('[data-formie-field-type="address"]') || t.closest("[data-formie-address-field-layout]")?.closest("[data-formie-field]") || t.closest("[data-formie-field]");
}
function nt(t) {
  const e = t.querySelector(Q);
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement ? e : null;
}
function ot(t) {
  const e = t.querySelector(_);
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement ? e : null;
}
function q(t) {
  return t.querySelector("[data-formie-field-label]");
}
function rt(t) {
  t.dispatchEvent(new Event("input", { bubbles: !0 })), t.dispatchEvent(new Event("change", { bubbles: !0 }));
}
function D(t, e) {
  if (!(t instanceof HTMLSelectElement))
    return;
  const n = t._formieTomSelect;
  n && n.getValue() !== e && n.setValue(e, !0);
}
function C(t, e) {
  if (t.value !== e) {
    t.value = e, rt(t);
    return;
  }
  D(t, e);
}
function E(t, e) {
  t.autofillAnchor && (t.autofillAnchor.value = e);
}
function g(t) {
  const e = t.autofillAnchor?.value?.trim() || "", n = t.stateControl.value?.trim() || "", r = e || n;
  r && (t.pendingStateValue = r);
}
function M(t) {
  return t.pendingStateValue?.trim() || t.autofillAnchor?.value?.trim() || t.stateControl.value?.trim() || "";
}
function st(t) {
  if (t.autofillAnchor)
    return t.autofillAnchor;
  const e = t.addressRoot.querySelector(z);
  if (e instanceof HTMLInputElement)
    t.autofillAnchor = e;
  else {
    const n = document.createElement("input");
    n.type = "text", n.setAttribute("data-formie-address-state-autofill-anchor", "true"), n.setAttribute("autocomplete", "address-level1"), n.setAttribute("tabindex", "-1"), n.setAttribute("aria-hidden", "true"), n.className = "formie-sr-only", t.addressRoot.appendChild(n), t.autofillAnchor = n;
  }
  return t.stateControl.setAttribute("autocomplete", "off"), t.stateControl.value && !t.autofillAnchor.value && (t.autofillAnchor.value = t.stateControl.value), t.autofillAnchor;
}
function b(t) {
  g(t);
  const e = M(t);
  if (!e)
    return;
  const n = t.lastSubdivisions, r = n.length > 0 && F(e, n) || e;
  C(t.stateControl, r), D(t.stateControl, r), E(t, r), t.pendingStateValue = r;
}
function it(t, e, n, r) {
  const s = new URL(t.startsWith("/") ? t : `/actions/${t}`, window.location.origin);
  return s.searchParams.set("country", e), s.searchParams.set("optionLabel", n), s.searchParams.set("optionValue", r), s.toString();
}
function O(t, e, n, r) {
  return [t, e, n, r].join("|");
}
function at(t) {
  return A.has(t);
}
async function ut(t, e, n, r) {
  const s = O(t, e, n, r);
  return A.has(s) ? A.get(s) || null : (w.has(s) || w.set(s, (async () => {
    try {
      const o = await fetch(it(r, t, e, n), {
        headers: {
          Accept: "application/json"
        }
      });
      if (!o.ok)
        return A.set(s, null), null;
      const a = await o.json();
      return A.set(s, a), a;
    } catch (o) {
      return T.warn("Failed fetching subdivisions.", { country: t, error: o }), A.set(s, null), null;
    } finally {
      w.delete(s);
    }
  })()), w.get(s) || null);
}
function F(t, e) {
  const n = t.trim().toLowerCase();
  if (!n)
    return null;
  for (const r of e)
    if (r.value.toLowerCase() === n || (r.name || "").toLowerCase() === n || (r.short || "").toLowerCase() === n || r.label.toLowerCase() === n)
      return r.value;
  return null;
}
function R(t, e) {
  [
    "id",
    "name",
    "required",
    "disabled",
    "placeholder",
    "aria-describedby",
    "data-formie-input-id",
    "data-formie-input-type",
    "data-formie-input-error-state",
    "data-formie-address-state-dynamic",
    "data-formie-address-state-hide-when-unused",
    "data-formie-address-state-use-searchable",
    "data-formie-address-state-use-datalist",
    "data-formie-address-state-option-label",
    "data-formie-address-state-option-value"
  ].forEach((r) => {
    const s = t.getAttribute(r);
    if (s === null) {
      e.removeAttribute(r);
      return;
    }
    e.setAttribute(r, s);
  });
}
function lt(t, e) {
  e.className = t.className;
}
function U(t, e) {
  const n = [...S];
  t.classList.contains("formie-input-error") && n.push("formie-input-error"), e.className = n.join(" ");
}
function I(t, e) {
  return t.parentNode?.replaceChild(e, t), e;
}
function H(t, e) {
  const n = document.createElement("input");
  return n.type = "text", R(t, n), lt(t, n), n.setAttribute("data-formie-input-type", "text"), n.setAttribute("data-formie-address-state-input", "true"), n.setAttribute("data-formie-single-line-text-input", "true"), n.setAttribute("autocomplete", "off"), n.removeAttribute("data-formie-combobox-input"), n.value = e, n;
}
function P(t, e, n, r = "") {
  t.innerHTML = "";
  const s = document.createElement("option");
  s.value = "", s.textContent = n || "", t.appendChild(s), e.forEach((o) => {
    const a = document.createElement("option");
    a.value = o.value, a.textContent = o.label, t.appendChild(a);
  }), t.value = F(r, e) || r;
}
function ct(t, e, n, r) {
  const s = document.createElement("select");
  return R(t, s), U(t, s), s.setAttribute("data-formie-input-type", "select"), s.setAttribute("data-formie-address-state-input", "true"), s.setAttribute("data-formie-select", "true"), s.setAttribute("data-formie-dropdown-input", "true"), s.removeAttribute("data-formie-single-line-text-input"), s.setAttribute("autocomplete", "off"), P(s, n, r, e), s;
}
function dt(t, e, n, r) {
  const s = t.list;
  if (!r || n.length === 0) {
    t.removeAttribute("list"), s && s.remove();
    return;
  }
  let o = t.ownerDocument.getElementById(e);
  o || (o = t.ownerDocument.createElement("datalist"), o.id = e, t.insertAdjacentElement("afterend", o)), o.innerHTML = "", n.forEach((a) => {
    const l = t.ownerDocument.createElement("option");
    l.value = a.label, o?.appendChild(l);
  }), t.setAttribute("list", e);
}
function x(t, e) {
  const { stateField: n, stateControl: r, required: s } = t;
  if (n.classList.toggle("formie-conditionally-hidden", !e), n.toggleAttribute("data-formie-conditionally-hidden", !e), !e) {
    r.required = !1, r.disabled = !0;
    return;
  }
  r.disabled = !1, r.required = s;
}
function mt(t) {
  if (t.fetchingAnnouncementEl)
    return t.fetchingAnnouncementEl;
  const e = document.createElement("div");
  return e.className = "formie-sr-only", e.setAttribute("data-formie-address-state-fetching-announce", "true"), e.setAttribute("aria-live", "polite"), e.setAttribute("aria-atomic", "true"), t.addressRoot.appendChild(e), t.fetchingAnnouncementEl = e, e;
}
function ft() {
  const t = document.createElement("div");
  t.className = "formie-address-state-skeleton", t.setAttribute("data-formie-address-state-skeleton", "true"), t.setAttribute("aria-hidden", "true");
  const e = document.createElement("div");
  return e.className = "formie-address-state-skeleton-input", t.appendChild(e), t;
}
function pt(t) {
  t.addressRoot.setAttribute("data-formie-address-state-fetching", "true"), t.countryControl?.setAttribute("aria-busy", "true"), t.stateControl.setAttribute("aria-hidden", "true"), t.stateControl.setAttribute("tabindex", "-1"), t.stateField.classList.remove("formie-conditionally-hidden"), t.stateField.removeAttribute("data-formie-conditionally-hidden"), t.stateField.setAttribute("data-formie-address-state-skeleton-active", "true");
  const e = t.stateField.querySelector("[data-formie-field-control]");
  e instanceof HTMLElement && (t.skeletonEl || (t.skeletonEl = ft(), e.appendChild(t.skeletonEl)), t.skeletonEl.removeAttribute("hidden")), mt(t).textContent = "Loading state or province options for the selected country.";
}
function k(t) {
  t.addressRoot.removeAttribute("data-formie-address-state-fetching"), t.countryControl?.removeAttribute("aria-busy"), t.stateControl.removeAttribute("aria-hidden"), t.stateControl.removeAttribute("tabindex"), t.stateField.removeAttribute("data-formie-address-state-skeleton-active"), t.skeletonEl?.setAttribute("hidden", "hidden"), t.fetchingAnnouncementEl && (t.fetchingAnnouncementEl.textContent = "");
}
function N(t, e) {
  const n = q(t);
  if (!n)
    return;
  const r = n.querySelector("[data-formie-field-required]");
  n.textContent = e, r && n.appendChild(r);
}
function ht(t, e) {
  t.setAttribute("data-formie-combobox-input", "true"), V(t, "combobox", "before-init", {
    select: t,
    options: { placeholder: e }
  });
  const n = Y(
    t,
    { placeholder: e }
  );
  return V(t, "combobox", "after-init", {
    combobox: t._formieTomSelect,
    options: { placeholder: e }
  }), n;
}
async function L(t, e) {
  const {
    hideWhenUnused: n = !0,
    useSearchable: r = !0,
    useDatalist: s = !0,
    optionLabel: o = "name",
    optionValue: a = "name",
    placeholder: l = null,
    subdivisionsAction: p = Z
  } = e, v = t.countryControl?.value?.trim() || "";
  g(t);
  const u = M(t), d = ++t.fetchGeneration;
  if (t.comboboxCleanup?.(), t.comboboxCleanup = null, !v) {
    if (N(
      t.stateField,
      t.stateField.dataset.formieAddressStateDefaultLabel || "State / Province"
    ), k(t), n) {
      x(t, !1), C(t.stateControl, ""), E(t, ""), t.pendingStateValue = "", t.lastSubdivisions = [];
      return;
    }
    if (x(t, !0), t.stateControl instanceof HTMLSelectElement) {
      const m = H(t.stateControl, u);
      t.stateControl = I(t.stateControl, m);
    } else
      t.stateControl.disabled = !0, t.stateControl.placeholder = l || t.stateControl.placeholder;
    t.lastSubdivisions = [];
    return;
  }
  const i = O(v, o, a, p);
  !at(i) && pt(t);
  const f = await ut(v, o, a, p);
  if (d !== t.fetchGeneration)
    return;
  const h = f?.subdivisions || [], j = f?.administrativeAreaUsed ?? !0, G = f?.administrativeAreaLabel || "State / Province";
  if (t.lastSubdivisions = h, k(t), n && !j) {
    x(t, !1), C(t.stateControl, ""), E(t, ""), t.pendingStateValue = "";
    return;
  }
  if (x(t, !0), N(t.stateField, G), h.length > 0) {
    const m = t.stateControl instanceof HTMLSelectElement ? t.stateControl : ct(t.stateControl, u, h, l);
    t.stateControl !== m ? t.stateControl = I(t.stateControl, m) : (U(t.stateControl, m), P(m, h, l, m.value)), r ? t.comboboxCleanup = ht(m, l) : m.removeAttribute("data-formie-combobox-input"), C(m, F(u, h) || u), b(t);
    return;
  }
  const y = t.stateControl instanceof HTMLInputElement ? t.stateControl : H(t.stateControl, u);
  t.stateControl !== y && (t.stateControl = I(t.stateControl, y)), y.disabled = !1, dt(y, t.datalistId, h, s), C(y, u), b(t);
}
function Ct(t, e) {
  const n = () => {
    g(t), t.countryControl?.value?.trim() && (t.lastCountry = "", L(t, e).then(() => {
      b(t);
    }));
  };
  tt.forEach((r) => {
    const s = window.setTimeout(n, r);
    t.autofillSweepTimers.push(s);
  });
}
function bt(t) {
  t.countryChangeTimer !== null && (window.clearTimeout(t.countryChangeTimer), t.countryChangeTimer = null), t.autofillSweepTimers.forEach((e) => {
    window.clearTimeout(e);
  }), t.autofillSweepTimers = [];
}
function vt(t, e) {
  const n = et(t);
  if (!n)
    return T.warn("Address root not found; skipping field."), () => {
    };
  const r = ot(t);
  if (!r)
    return T.warn("Dynamic state control not found; skipping field."), () => {
    };
  const s = q(t);
  s && !t.dataset.formieAddressStateDefaultLabel && (t.dataset.formieAddressStateDefaultLabel = s.textContent?.trim() || "State / Province");
  const o = {
    addressRoot: n,
    stateField: t,
    stateControl: r,
    countryControl: nt(n),
    autofillAnchor: null,
    pendingStateValue: r.value?.trim() || "",
    datalistId: `formie-address-state-datalist-${r.getAttribute("data-formie-input-id") || Math.random().toString(36).slice(2)}`,
    comboboxCleanup: null,
    skeletonEl: null,
    fetchingAnnouncementEl: null,
    autofillSweepTimers: [],
    countryChangeTimer: null,
    lastCountry: "",
    fetchGeneration: 0,
    required: r.required,
    lastSubdivisions: []
  };
  st(o);
  const a = (d = !1) => {
    const i = o.countryControl?.value?.trim() || "";
    if (!d && i === o.lastCountry)
      return;
    const c = o.lastCountry, f = M(o);
    o.lastCountry = i, L(o, e).then(() => {
      if (b(o), !c || !i || c === i)
        return;
      !F(f, o.lastSubdivisions) && !M(o) && (C(o.stateControl, ""), E(o, ""));
    });
  }, l = () => {
    o.countryChangeTimer !== null && window.clearTimeout(o.countryChangeTimer), o.countryChangeTimer = window.setTimeout(() => {
      o.countryChangeTimer = null, g(o), a(!0);
    }, 50);
  }, p = (d) => {
    const i = d.target;
    if (!(i instanceof HTMLInputElement || i instanceof HTMLSelectElement))
      return;
    const c = i === o.autofillAnchor, f = i.matches(_) || i.matches($);
    if (!(!c && !f) && (f && !c && E(o, i.value), g(o), !!o.countryControl?.value?.trim())) {
      if (o.lastSubdivisions.length > 0) {
        b(o);
        return;
      }
      o.lastCountry = "", L(o, e).then(() => {
        b(o);
      });
    }
  }, v = (d) => {
    if (d.animationName !== J)
      return;
    const i = d.target;
    (i instanceof HTMLInputElement || i instanceof HTMLSelectElement) && n.contains(i) && (g(o), o.countryControl?.value?.trim() && (o.lastCountry = "", L(o, e).then(() => {
      b(o);
    })));
  }, u = (d) => {
    const c = d.detail?.state?.trim();
    if (!c) {
      a();
      return;
    }
    o.pendingStateValue = c, E(o, c), L(o, e).then(() => {
      C(o.stateControl, c), D(o.stateControl, c), o.lastCountry = o.countryControl?.value?.trim() || "";
    });
  };
  return o.countryControl?.addEventListener("change", l), o.countryControl?.addEventListener("input", l), n.addEventListener("input", p), n.addEventListener("change", p), n.addEventListener("animationstart", v), n.addEventListener("formie:address:google:populate", u), n.addEventListener("formie:address:address-finder:populate", u), n.addEventListener("formie:address:loqate:populate", u), n.addEventListener("formie:address:place-kit:populate", u), a(), Ct(o, e), () => {
    bt(o), k(o), o.skeletonEl?.remove(), o.fetchingAnnouncementEl?.remove(), o.autofillAnchor?.remove(), o.comboboxCleanup?.(), o.countryControl?.removeEventListener("change", l), o.countryControl?.removeEventListener("input", l), n.removeEventListener("input", p), n.removeEventListener("change", p), n.removeEventListener("animationstart", v), n.removeEventListener("formie:address:google:populate", u), n.removeEventListener("formie:address:address-finder:populate", u), n.removeEventListener("formie:address:loqate:populate", u), n.removeEventListener("formie:address:place-kit:populate", u);
  };
}
const yt = {
  id: X,
  kind: "field",
  match: (t) => !!t.target.querySelector(_),
  setup: async (t) => {
    const e = t.options || {}, n = B(t), r = n.map((s) => vt(s, e));
    return T.log("Module setup.", { fieldCount: n.length }), {
      destroy: () => {
        r.forEach((s) => s()), T.log("Module destroy.", { fieldCount: n.length });
      }
    };
  }
};
export {
  yt as addressStateModule
};
