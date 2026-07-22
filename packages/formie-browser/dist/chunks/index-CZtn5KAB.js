const Zr = [
  { legacyEvent: "onFormieLoaded", canonicalEvent: "formie:mount:after", disposition: "approximate", target: "document" },
  { legacyEvent: "onFormieInit", canonicalEvent: "formie:mount:after", disposition: "approximate", target: "document" },
  { legacyEvent: "onFormieReady", canonicalEvent: "formie:mount:after", disposition: "safe" },
  { legacyEvent: "onAfterFormieSubmit", canonicalEvent: "formie:submit:result", disposition: "safe" },
  { legacyEvent: "onFormieSubmitError", canonicalEvent: "formie:submit:result", disposition: "safe" },
  { legacyEvent: "onFormiePageToggle", canonicalEvent: "formie:page:navigate:after", disposition: "safe" },
  { legacyEvent: "onBeforeFormieSubmit", canonicalEvent: "formie:submit:before", disposition: "approximate" },
  { legacyEvent: "onFormieValidate", canonicalEvent: "formie:stage:validate:before", disposition: "approximate" },
  { legacyEvent: "onAfterFormieValidate", canonicalEvent: "formie:stage:validate:after", disposition: "approximate" },
  { legacyEvent: "onFormieSubmit", canonicalEvent: "formie:submit:after", disposition: "approximate" }
], Pa = [
  { legacyEvent: "formieValidatorInitialized", canonicalEvent: "formie:validator:ready", disposition: "safe" },
  { legacyEvent: "formieValidatorDestroyed", canonicalEvent: "formie:validator:destroy", disposition: "safe" },
  { legacyEvent: "formieValidatorShowError", canonicalEvent: "formie:validator:show-error", disposition: "safe" },
  { legacyEvent: "formieValidatorClearError", canonicalEvent: "formie:validator:clear-error", disposition: "safe" }
];
function Qr(e) {
  if (!e)
    return {
      enabled: !1,
      legacyDomEvents: !1,
      legacyValidatorEvents: !1
    };
  if (e === !0)
    return {
      enabled: !0,
      legacyDomEvents: !0,
      legacyValidatorEvents: !0
    };
  const t = e.legacyDomEvents ?? !0, r = e.legacyValidatorEvents ?? !0;
  return {
    enabled: t || r,
    legacyDomEvents: t,
    legacyValidatorEvents: r
  };
}
const Xr = [
  "formie:mount:after",
  "formie:unmount:before",
  "formie:unmount:after",
  "formie:validator:ready",
  "formie:theme:applied",
  "formie:page:navigate",
  "formie:page:navigate:after",
  "formie:page:navigate:error",
  "formie:submit:before",
  "formie:submit:after",
  "formie:submit:final:before",
  "formie:submit:final:after",
  "formie:submit:result",
  "formie:client-event",
  "formie:refresh-tokens:after",
  "formie:refresh-tokens:refreshed"
];
function Ke(e) {
  return e;
}
function Va(e) {
  return e;
}
function Oa(e, t) {
  return `formie:field:${e}:${t}`;
}
function ye(e) {
  return `formie:validator:${e}`;
}
function _a(e, t) {
  return `formie:address:${e}:${t}`;
}
function $a(e) {
  return `formie:file-upload:${e}`;
}
function Ha(e, t) {
  return `formie:payment:${e}:${t}`;
}
function Ge(e) {
  return `formie:state:${e}`;
}
function en(e, t) {
  return `formie:module:${e}:${t}`;
}
function tn(e) {
  return `formie:module:${e}`;
}
function rn(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function nn(e, t) {
  if (e.canonicalEvent !== "formie:submit:result")
    return !0;
  const r = t;
  return e.legacyEvent === "onAfterFormieSubmit" ? !!r?.ok : e.legacyEvent === "onFormieSubmitError" ? r?.ok === !1 : !0;
}
function on(e, t) {
  const r = t && typeof t == "object" ? t : {}, n = typeof r.pageId == "string" ? r.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]")), o = i.findIndex((a) => a.getAttribute("data-formie-page-id") === n);
  return {
    data: {
      nextPageId: n,
      nextPageIndex: o,
      totalPages: i.length
    }
  };
}
function an(e, t, r, n, i) {
  const o = globalThis.Formie || i;
  return e.legacyEvent === "onFormieLoaded" ? {
    formie: o
  } : e.legacyEvent === "onFormieInit" ? {
    formie: o,
    form: i,
    $form: n,
    formId: i.id
  } : e.legacyEvent === "onFormieReady" ? {
    ...t && typeof t == "object" ? t : {},
    form: n,
    target: r,
    instance: i
  } : e.legacyEvent === "onFormiePageToggle" ? on(n, t) : t;
}
function sn({
  target: e,
  form: t,
  instance: r,
  options: n,
  unbinds: i
}) {
  n.legacyDomEvents && Zr.forEach((o) => {
    const a = (s) => {
      if (!(s instanceof CustomEvent) || !nn(o, s.detail))
        return;
      const l = o.target === "document" ? document : t;
      rn(l, o.legacyEvent, an(o, s.detail, e, t, r));
    };
    e.addEventListener(Ke(o.canonicalEvent), a), i.push(() => {
      e.removeEventListener(Ke(o.canonicalEvent), a);
    });
  });
}
function ve(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function De(e, t) {
  return !!e && typeof e == "object" && e.validator === t;
}
function ln({
  target: e,
  form: t,
  validatorDetail: r,
  options: n,
  unbinds: i
}) {
  if (!n.legacyValidatorEvents || !r)
    return;
  const { validator: o, addValidator: a, removeValidator: s } = r, l = {
    ...r,
    form: t,
    target: e
  };
  ve(document, "formieValidatorInitialized", l);
  const d = (u) => {
    !(u instanceof CustomEvent) || !De(u.detail, o) || ve(document, "formieValidatorDestroyed", {
      ...l,
      ...u.detail
    });
  }, h = (u) => {
    !(u instanceof CustomEvent) || !De(u.detail, o) || !(u.target instanceof Element) || t.contains(u.target) && ve(u.target, "formieValidatorShowError", {
      ...u.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  }, m = (u) => {
    !(u instanceof CustomEvent) || !De(u.detail, o) || !(u.target instanceof Element) || t.contains(u.target) && ve(u.target, "formieValidatorClearError", {
      ...u.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  };
  document.addEventListener("formie:validator:destroy", d), document.addEventListener("formie:validator:show-error", h), document.addEventListener("formie:validator:clear-error", m), i.push(() => {
    document.removeEventListener("formie:validator:destroy", d), document.removeEventListener("formie:validator:show-error", h), document.removeEventListener("formie:validator:clear-error", m);
  });
}
function I(e, t, r) {
  e.dispatchEvent(new CustomEvent(Ke(t), {
    bubbles: !0,
    detail: r
  }));
}
function it(e) {
  const t = (e.dataset.formieErrorAriaLive || "polite").trim().toLowerCase();
  return t === "assertive" || t === "off" ? t : "polite";
}
function un(e, t) {
  return e === "off" ? null : t ? e : "polite";
}
function Qt(e) {
  return e === "off" ? null : e;
}
function ot(e, t) {
  if (t) {
    e.setAttribute("aria-live", t), e.setAttribute("aria-atomic", "true");
    return;
  }
  e.removeAttribute("aria-live"), e.removeAttribute("aria-atomic");
}
function Xt() {
  return globalThis;
}
function er() {
  return Xt().__FORMIE_DEBUG__ === !0;
}
function Da(e) {
  Xt().__FORMIE_DEBUG__ = e;
}
function cn(e, t, r) {
  if (er()) {
    if (typeof r > "u") {
      console.log(`[formie:${e}] ${t}`);
      return;
    }
    console.log(`[formie:${e}] ${t}`, r);
  }
}
function dn(e, t, r) {
  if (er()) {
    if (typeof r > "u") {
      console.warn(`[formie:${e}] ${t}`);
      return;
    }
    console.warn(`[formie:${e}] ${t}`, r);
  }
}
function H(e, t) {
  const r = t ? `${e}:${t}` : e;
  return {
    log: (n, i) => {
      cn(r, n, i);
    },
    warn: (n, i) => {
      dn(r, n, i);
    }
  };
}
const me = H("general", "page-client-event"), fn = "data-formie-client-event", Tt = "data-formie-pending-client-events";
function mn(e) {
  return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
}
function pn(e) {
  const r = e.querySelector('input[name="pageId"]')?.value?.trim();
  if (r)
    return r;
  const i = e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim();
  return i || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function gn(e) {
  if (!e?.trim())
    return null;
  try {
    const t = JSON.parse(e);
    return t && typeof t == "object" ? t : null;
  } catch {
    return me.warn("Invalid data-formie-client-event JSON.", {
      rawPreview: e.slice(0, 80)
    }), null;
  }
}
function hn(e) {
  const t = {};
  return e.forEach((r) => {
    const n = typeof r.label == "string" ? r.label.trim() : "";
    n && (t[n] = typeof r.value == "string" ? r.value : "");
  }), t;
}
function bn(e) {
  return Array.isArray(e) ? e.map((t) => {
    if (!t || typeof t != "object")
      return null;
    const r = t, n = typeof r.event == "string" ? r.event.trim() : "", i = r.payload && typeof r.payload == "object" ? r.payload : null;
    return !n || !i ? null : {
      event: n,
      payload: i
    };
  }).filter((t) => t !== null) : [];
}
function at(e, t) {
  if (!t.length)
    return;
  const r = window;
  r.dataLayer = r.dataLayer || [], t.forEach((n) => {
    r.dataLayer.push(n.payload), e.dispatchEvent(new CustomEvent("formie:client-event", {
      bubbles: !0,
      detail: {
        event: n.event,
        payload: n.payload
      }
    }));
  }), me.log("Dispatched resolved client events.", {
    count: t.length,
    events: t.map((n) => n.event)
  });
}
function yn(e) {
  const t = e.getAttribute(Tt);
  if (t?.trim())
    try {
      const r = JSON.parse(t), n = bn(r);
      n.length && at(e, n);
    } catch {
      me.warn("Invalid pending client events JSON on form element.");
    } finally {
      e.removeAttribute(Tt);
    }
}
function tr(e, t) {
  if (t !== "submit")
    return;
  const r = pn(e);
  if (!r) {
    me.log("No submitted page id; skipping client event.");
    return;
  }
  const n = e.querySelector(
    `[data-formie-page][data-formie-page-id="${mn(r)}"]`
  );
  if (!n) {
    me.log("No page section for id; skipping client event.", { pageId: r });
    return;
  }
  const i = n.getAttribute(fn);
  if (i === null)
    return;
  const o = gn(i);
  if (!o || !Array.isArray(o.fields))
    return;
  const a = hn(o.fields);
  at(e, [{
    event: typeof a.event == "string" && a.event !== "" ? a.event : "formPageSubmission",
    payload: a
  }]);
}
const Le = /* @__PURE__ */ new WeakMap(), vn = "[data-formie-form], [data-formie], form";
function En(e) {
  return e ? (Array.isArray(e) ? e : [e]).flatMap((r) => String(r).split(/\s+/)).map((r) => r.trim()).filter(Boolean) : [];
}
function st(e) {
  return Array.from(new Set(e));
}
function Sn(e) {
  if (!e)
    return {};
  const t = Le.get(e);
  if (t)
    return t;
  const r = e.closest(vn);
  return r ? Le.get(r) || {} : {};
}
function An(e) {
  const t = {};
  return Object.entries(e || {}).forEach(([r, n]) => {
    const i = st(En(n));
    i.length && (t[r] = i);
  }), t;
}
function Ct(e, t, r) {
  const n = An(t), i = r || (e instanceof HTMLFormElement ? e : e.querySelector("form"));
  return Le.set(e, n), i && Le.set(i, n), n;
}
function lt(e, t) {
  return Sn(e)[t] || [];
}
function q(e, t, ...r) {
  const n = st(r.flatMap((i) => lt(t, i)));
  n.length && e.classList.add(...n);
}
function ae(e, t, ...r) {
  const n = st(r.flatMap((i) => lt(t, i)));
  n.length && e.classList.remove(...n);
}
function ie(e, t, r, n) {
  lt(t, r).forEach((i) => {
    e.classList.toggle(i, n);
  });
}
function wn(e, t) {
  if (ie(e, e, "tabError", t), t) {
    e.setAttribute("data-formie-tab-error", "true");
    return;
  }
  e.removeAttribute("data-formie-tab-error");
}
function Z(e) {
  const t = /* @__PURE__ */ new Set();
  e.querySelectorAll("[data-formie-page]").forEach((r) => {
    const n = r, i = n.getAttribute("data-formie-page-id");
    i && n.querySelector("[data-formie-field-has-error]") && t.add(i);
  }), e.querySelectorAll("[data-formie-tab]").forEach((r) => {
    const n = r, i = n.getAttribute("data-formie-page-id");
    wn(n, !!i && t.has(i));
  });
}
function Tn(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function rr(e) {
  return Array.from(e.querySelectorAll("[data-formie-field-handle]")).find((r) => r.getAttribute("data-formie-field-has-error") === "true" ? !0 : r.querySelector("[data-formie-field-error]") !== null) || null;
}
function Cn(e) {
  const t = e.querySelector('[aria-invalid="true"]');
  return t || e.querySelector(
    'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])'
  );
}
function nr(e) {
  return e.querySelector(
    "[data-formie-message-error], [data-formie-error-container], [data-formie-errors]"
  );
}
function Mn(e) {
  e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
    const r = t;
    if (!(r.getAttribute("data-formie-field-has-error") === "true" || r.querySelector("[data-formie-field-error]") !== null))
      return;
    r.setAttribute("data-formie-field-has-error", "true"), q(r, e, "fieldLayoutError");
    const o = r.querySelector("[data-formie-field-errors]")?.id || "", s = r.querySelector("[data-formie-field-error]")?.id || "";
    r.querySelectorAll("input, select, textarea").forEach((l) => {
      const d = l;
      d.setAttribute("aria-invalid", "true"), q(d, e, "fieldControlError"), d.setAttribute("data-formie-input-has-error", "true"), o && Tn(d, o), s && d.setAttribute("aria-errormessage", s);
    });
  });
}
function Ln(e) {
  return !!rr(e) || !!nr(e);
}
function ir(e) {
  const t = rr(e);
  if (t) {
    const n = Cn(t);
    if (n) {
      if (n.scrollIntoView({ behavior: "smooth", block: "center" }), typeof n.focus == "function")
        try {
          n.focus({ preventScroll: !0 });
        } catch {
          n.focus();
        }
      return !0;
    }
    return t.scrollIntoView({ behavior: "smooth", block: "center" }), !0;
  }
  const r = nr(e);
  return r ? (r.scrollIntoView({ behavior: "smooth", block: "center" }), !0) : !1;
}
class or {
  constructor() {
    this.listeners = /* @__PURE__ */ new Map();
  }
  on(t, r) {
    return this.listeners.has(t) || this.listeners.set(t, /* @__PURE__ */ new Set()), this.listeners.get(t)?.add(r), () => {
      this.listeners.get(t)?.delete(r);
    };
  }
  async emit(t, r) {
    const n = this.listeners.get(t);
    if (!(!n || n.size === 0))
      for (const i of n)
        await i(r);
  }
  async emitSafe(t, r) {
    const n = this.listeners.get(t), i = {
      eventName: t,
      total: n?.size || 0,
      succeeded: 0,
      failed: []
    };
    if (!n || n.size === 0)
      return i;
    let o = 0;
    for (const a of n) {
      try {
        await a(r), i.succeeded += 1;
      } catch (s) {
        i.failed.push({
          index: o,
          error: s
        });
      }
      o += 1;
    }
    return i;
  }
  async emitParallelSafe(t, r) {
    const n = this.listeners.get(t), i = {
      eventName: t,
      total: n?.size || 0,
      succeeded: 0,
      failed: []
    };
    return !n || n.size === 0 || (await Promise.allSettled(Array.from(n).map(async (a) => a(r)))).forEach((a, s) => {
      if (a.status === "fulfilled") {
        i.succeeded += 1;
        return;
      }
      i.failed.push({
        index: s,
        error: a.reason
      });
    }), i;
  }
  clear() {
    this.listeners.clear();
  }
}
async function ar(e, t = {}) {
  const r = {
    Accept: "application/json",
    ...t.headers || {}
  };
  return delete r["X-Requested-With"], delete r["x-requested-with"], fetch(String(e), {
    method: t.method || "GET",
    body: t.body ?? null,
    signal: t.signal,
    // Avoid sending `Cache-Control`: not CORS-safelisted; use fetch cache mode instead.
    cache: "no-store",
    headers: r,
    // `include` + `Access-Control-Allow-Origin: *` is invalid; many Craft GraphQL setups use `*`.
    // `same-origin` keeps cookies for same-host deployments and avoids credentialed cross-origin
    // fetches (e.g. Vite on localhost → ddev HTTPS) so wildcard CORS can succeed.
    credentials: "same-origin"
  });
}
async function qe(e, t = {}) {
  const r = await ar(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.json();
}
async function xa(e, t = {}) {
  const r = await ar(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.text();
}
const $ = H("general", "transport");
function In(e) {
  const t = {};
  return ["theme", "themeConfig", "locale", "siteId"].forEach((r) => {
    e[r] !== void 0 && (t[r] = e[r]);
  }), t;
}
function sr(e, t = "", r = {}) {
  if (Array.isArray(e)) {
    const n = e.map((i) => typeof i == "string" ? i : String(i ?? "")).filter((i) => i.trim() !== "");
    return t && n.length && (r[t] = (r[t] || []).concat(n)), r;
  }
  return e && typeof e == "object" && Object.entries(e).forEach(([n, i]) => {
    const o = t ? `${t}.${n}` : n;
    sr(i, o, r);
  }), r;
}
function Rn(e, t) {
  const r = e.success === !0, n = e.keepSubmitLoading === !0, i = e.errors, o = sr(i || {}), a = o.form || [], s = {};
  Object.entries(o).forEach(([m, u]) => {
    if (m === "form")
      return;
    const c = m.split(".")[0];
    s[c] = (s[c] || []).concat(u);
  });
  const l = !r && a.length === 0 && Object.keys(s).length > 0 ? [t || "Submission failed."] : a, d = !r && n && l.length === 0 && Object.keys(s).length === 0;
  return {
    ok: r,
    action: e.submitAction === "back" || e.submitAction === "save" || e.submitAction === "submit" ? e.submitAction : void 0,
    message: e.submitActionMessage || (r ? "Submission completed." : d ? "" : l[0] || "Submission failed."),
    code: r ? void 0 : String(e.code || "SUBMIT_ERROR"),
    keepSubmitLoading: n,
    fieldErrors: Object.keys(s).length ? s : void 0,
    formErrors: l.length ? l : void 0,
    nextPage: e.nextPageId ? {
      id: String(e.nextPageId)
    } : null,
    redirect: e.redirectUrl ? {
      url: String(e.redirectUrl),
      target: e.submitActionTab === "new-tab" ? "new-tab" : "same-tab"
    } : null,
    submitData: Array.isArray(e.submitData) ? e.submitData : void 0,
    clientEvents: Array.isArray(e.clientEvents) ? e.clientEvents : void 0,
    meta: e
  };
}
async function Fn(e, t, r = {}) {
  const n = JSON.stringify({
    handle: t,
    renderOptions: r
  });
  $.log("requestRender start.", { endpoint: e, handle: t });
  const i = await qe(e, {
    method: "POST",
    body: n,
    headers: {
      "Content-Type": "application/json"
    }
  });
  return $.log("requestRender complete.", {
    hasHtml: !!i.html
  }), i;
}
async function kn(e, t, r = {}) {
  const i = JSON.stringify({
    query: `
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,
    variables: {
      handle: t,
      input: In(r)
    }
  });
  $.log("requestGraphqlRender start.", { endpoint: e, handle: t });
  const o = await qe(e, {
    method: "POST",
    body: i,
    headers: {
      "Content-Type": "application/json"
    }
  });
  if (Array.isArray(o.errors) && o.errors.length > 0)
    throw new Error(o.errors.map((s) => s.message || "Unknown GraphQL error").join("; "));
  if (!o.data?.formieHtmlForm)
    throw new Error(`Form not found for handle "${t}".`);
  const a = o.data.formieHtmlForm;
  return $.log("requestGraphqlRender complete.", {
    hasHtml: !!a.html
  }), a;
}
async function ut(e, t, r) {
  const n = new URL(e, window.location.origin);
  n.searchParams.set("handle", t), r && n.searchParams.set("renderId", r), $.log("requestRefreshTokens start.", {
    endpoint: n.toString(),
    handle: t,
    hasRenderId: !!r
  });
  const i = await qe(n.toString());
  return $.log("requestRefreshTokens complete.", {
    hasRefreshTokens: !!i.refreshTokens
  }), i.refreshTokens || i;
}
async function qn(e, t, r) {
  const n = new URL(e, window.location.origin), i = new FormData();
  if (r && i.append("pageId", r), t) {
    ["handle", "renderId", "draftContextToken", "draftContext", "continuationToken"].forEach((d) => {
      const m = t.querySelector(`input[name="${d}"]`)?.value?.trim();
      m && i.append(d, m);
    });
    const l = t.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value?.trim();
    l && i.append("CRAFT_CSRF_TOKEN", l);
  }
  $.log("requestSetPage start.", {
    requestUrl: n.toString(),
    pageId: r || null
  });
  const o = await qe(n.toString(), {
    method: "POST",
    body: i
  });
  return $.log("requestSetPage complete.", o), o;
}
function Pn(e, t) {
  const r = new URL(e, window.location.origin), n = new FormData();
  ["handle", "renderId", "draftContextToken", "draftContext"].forEach((s) => {
    const d = t.querySelector(`input[name="${s}"]`)?.value?.trim();
    d && n.append(s, d);
  });
  const a = t.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value?.trim();
  a && n.append("CRAFT_CSRF_TOKEN", a), $.log("clearSubmissionOnUnload start.", {
    requestUrl: r.toString()
  });
  try {
    if (typeof navigator.sendBeacon == "function" && navigator.sendBeacon(r.toString(), n))
      return;
  } catch {
  }
  fetch(r.toString(), {
    method: "POST",
    body: n,
    credentials: "include",
    keepalive: !0,
    headers: {
      Accept: "application/json"
    }
  });
}
async function Vn(e, t) {
  const r = (e.getAttribute("method") || "POST").toUpperCase(), n = e.getAttribute("action") || window.location.href, i = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
  $.log("submitForm start.", {
    method: r,
    action: n,
    submitAction: t.get("submitAction")
  });
  const o = await fetch(n, {
    method: r,
    body: t,
    credentials: "include",
    headers: {
      Accept: "application/json"
    }
  }), a = o.headers.get("content-type") || "";
  if (!a.includes("application/json"))
    return o.ok ? ($.log("submitForm non-JSON success response.", {
      status: o.status,
      contentType: a
    }), {
      ok: !0,
      message: "Submission completed."
    }) : ($.warn("submitForm non-JSON HTTP error.", {
      status: o.status,
      contentType: a
    }), {
      ok: !1,
      code: "HTTP_ERROR",
      message: `Request failed (${o.status}).`,
      formErrors: [`Request failed (${o.status}).`]
    });
  const s = await o.json(), l = Rn(s, i);
  return $.log("submitForm JSON response normalized.", {
    ok: l.ok,
    code: l.code,
    hasRedirect: !!l.redirect?.url,
    hasSubmitData: Array.isArray(l.submitData) && l.submitData.length > 0
  }), l;
}
function ct(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function Pe(e) {
  const t = ct(e);
  if (!t.length)
    return {
      scope: e,
      final: !0
    };
  const r = t.find((n) => !n.hasAttribute("data-formie-page-hidden")) || t[t.length - 1];
  return {
    scope: r,
    final: r === t[t.length - 1]
  };
}
const On = ["prepare", "normalize", "validate", "screen", "authorize", "dispatch", "finalize"], _n = ["prepare", "normalize", "validate", "screen", "authorize"], k = H("general", "pipeline");
function xe(e, t) {
  return {
    ok: !1,
    stage: e,
    code: "ABORTED",
    message: t || "Submission aborted.",
    formErrors: [t || "Submission aborted."]
  };
}
function lr(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function ur(e) {
  return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function cr(e, t) {
  if (t instanceof HTMLInputElement) {
    if (t.type === "file") {
      Array.from(t.files || []).forEach((r) => {
        e.append(t.name, r);
      });
      return;
    }
    e.append(t.name, t.value);
    return;
  }
  if (t instanceof HTMLSelectElement && t.multiple) {
    Array.from(t.selectedOptions).forEach((r) => {
      e.append(t.name, r.value);
    });
    return;
  }
  e.append(t.name, t.value);
}
function $n(e, t) {
  t.querySelectorAll("input, select, textarea").forEach((r) => {
    const n = lr(r) ? r : null;
    !n || n.closest("[data-formie-page]") || ur(n) && cr(e, n);
  });
}
function Hn(e, t) {
  const r = /* @__PURE__ */ new Set();
  return t.querySelectorAll("input, select, textarea").forEach((n) => {
    const i = lr(n) ? n : null;
    !i || !i.name || i.disabled || i instanceof HTMLInputElement && (i.type === "submit" || i.type === "button" || i.type === "reset" || i.type === "image") || (i.name.startsWith("fields[") && r.add(i.name), ur(i) && cr(e, i));
  }), r;
}
function Dn(e, t) {
  t.forEach((r) => {
    e.has(r) || e.append(r, "");
  });
}
function Mt(e, t) {
  const r = ct(e), n = r.find((a) => !a.hasAttribute("data-formie-page-hidden")) || null;
  if (!r.length || !n) {
    const a = new FormData(e);
    return a.set("submitAction", t), a;
  }
  const i = new FormData();
  $n(i, e);
  const o = Hn(i, n);
  return Dn(i, o), i.set("submitAction", t), i;
}
function xn(e, t) {
  if (t !== "submit")
    return !1;
  const r = ct(e);
  return r.length ? (r.find((i) => !i.hasAttribute("data-formie-page-hidden")) || r[r.length - 1]) === r[r.length - 1] : !0;
}
async function dr(e, t, r, n = {}) {
  k.log("Starting submit pipeline.", {
    action: t,
    preflightOnly: n.preflightOnly === !0
  });
  let i = !1, o, a = null;
  const s = xn(e, t), l = {
    form: e,
    action: t,
    formData: Mt(e, t),
    abort: (u) => {
      i = !0, o = u, k.warn("Pipeline aborted.", { reason: u });
    },
    isAborted: () => i,
    abortReason: () => o
  }, d = {
    prepare: async (u) => {
      const c = u.form.querySelector('input[name="submitAction"]');
      return c && (c.value = u.action), u.formData.set("submitAction", u.action), null;
    },
    normalize: async () => null,
    validate: async (u) => {
      if (u.action !== "submit" || n.validateOnSubmit === !1)
        return null;
      if (n.validator) {
        const { scope: c, final: b } = Pe(u.form), w = n.validator.submit(b ? u.form : c, { final: b });
        if (w.length > 0) {
          const f = w[0]?.input;
          if (f) {
            f.scrollIntoView({ behavior: "smooth", block: "center" });
            try {
              f.focus({ preventScroll: !0 });
            } catch {
              f.focus();
            }
          }
          return {
            ok: !1,
            stage: "validate",
            code: "VALIDATION_FAILED",
            message: n.validator.config.errorMessage || "Validation failed.",
            fieldErrors: n.validator.getFieldErrors(w),
            formErrors: [n.validator.config.errorMessage || "Validation failed."]
          };
        }
        return null;
      }
      return u.form.checkValidity() ? null : (u.form.querySelector(":invalid")?.focus(), {
        ok: !1,
        stage: "validate",
        code: "VALIDATION_FAILED",
        message: "Validation failed.",
        formErrors: ["Validation failed."]
      });
    },
    screen: async () => null,
    authorize: async () => null,
    dispatch: async (u) => {
      u.formData = Mt(u.form, u.action);
      const c = await Vn(u.form, u.formData);
      return a = c, c;
    },
    finalize: async (u) => (a && a.ok && a.redirect?.url && (a.redirect.target === "new-tab" ? window.open(a.redirect.url, "_blank") : window.location.href = a.redirect.url), null)
  };
  {
    const u = await r.emitSafe("formie:submit:before", l);
    u.failed.length > 0 && k.warn("Submit before listeners failed.", {
      eventName: u.eventName,
      failed: u.failed.length
    });
  }
  if (s) {
    const u = await r.emitSafe("formie:submit:final:before", l);
    u.failed.length > 0 && k.warn("Final submit before listeners failed.", {
      eventName: u.eventName,
      failed: u.failed.length
    });
  }
  const h = n.preflightOnly ? _n : On;
  for (const u of h) {
    if (k.log("Stage start.", { stage: u, action: t }), i)
      return k.warn("Stage skipped due to abort.", { stage: u, reason: o }), xe(u, o);
    {
      const b = await r.emitSafe(`formie:stage:${u}:before`, {
        ...l,
        stage: u
      });
      b.failed.length > 0 && k.warn("Stage before listeners failed.", {
        stage: u,
        failed: b.failed.length
      });
    }
    if (i) {
      const b = xe(u, o);
      {
        const w = await r.emitSafe("formie:submit:after", b);
        w.failed.length > 0 && k.warn("Submit after listeners failed (abort before stage).", {
          stage: u,
          failed: w.failed.length
        });
      }
      if (s) {
        const w = await r.emitSafe("formie:submit:final:after", b);
        w.failed.length > 0 && k.warn("Final submit after listeners failed (abort before stage).", {
          stage: u,
          failed: w.failed.length
        });
      }
      return k.warn("Aborted after stage before-hooks.", { stage: u, reason: o }), b;
    }
    const c = await d[u](l);
    k.log("Stage runner complete.", {
      stage: u,
      hasResult: !!c,
      ok: c ? c.ok : void 0,
      code: c?.code
    });
    {
      const b = await r.emitSafe(`formie:stage:${u}:after`, {
        ...l,
        stage: u,
        result: c
      });
      b.failed.length > 0 && k.warn("Stage after listeners failed.", {
        stage: u,
        failed: b.failed.length
      });
    }
    if (i) {
      const b = xe(u, o);
      {
        const w = await r.emitSafe("formie:submit:after", b);
        w.failed.length > 0 && k.warn("Submit after listeners failed (abort after stage).", {
          stage: u,
          failed: w.failed.length
        });
      }
      if (s) {
        const w = await r.emitSafe("formie:submit:final:after", b);
        w.failed.length > 0 && k.warn("Final submit after listeners failed (abort after stage).", {
          stage: u,
          failed: w.failed.length
        });
      }
      return k.warn("Aborted after stage after-hooks.", { stage: u, reason: o }), b;
    }
    if (c && !c.ok) {
      {
        const b = await r.emitSafe("formie:submit:after", c);
        b.failed.length > 0 && k.warn("Submit after listeners failed (failed stage).", {
          stage: u,
          failed: b.failed.length
        });
      }
      if (s) {
        const b = await r.emitSafe("formie:submit:final:after", c);
        b.failed.length > 0 && k.warn("Final submit after listeners failed (failed stage).", {
          stage: u,
          failed: b.failed.length
        });
      }
      return k.warn("Pipeline short-circuited by failed stage.", {
        stage: u,
        code: c.code,
        message: c.message
      }), c;
    }
  }
  const m = a || {
    ok: !0,
    stage: n.preflightOnly ? "authorize" : "finalize",
    message: n.preflightOnly ? "Submission preflight completed." : "Submission completed."
  };
  {
    const u = await r.emitSafe("formie:submit:after", m);
    u.failed.length > 0 && k.warn("Submit after listeners failed (success).", {
      failed: u.failed.length
    });
  }
  if (s) {
    const u = await r.emitSafe("formie:submit:final:after", m);
    u.failed.length > 0 && k.warn("Final submit after listeners failed (success).", {
      failed: u.failed.length
    });
  }
  return k.log("Pipeline completed.", {
    ok: m.ok,
    stage: m.stage,
    code: m.code
  }), m;
}
function Nn(e) {
  return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function fr(e, t) {
  const r = e.querySelector("[data-formie-field-errors]");
  if (r)
    return r;
  const n = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), o = Nn(e), a = document.createElement("div");
  return a.setAttribute("data-formie-field-errors", "true"), t?.(a), n && i ? o === "above" ? n.insertBefore(a, i) : n.appendChild(a) : e.appendChild(a), a;
}
const Un = {
  rule: ({ input: e, getRule: t }) => !t("email") || !e.value || e.value.length < 1 ? !0 : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? r("{label} is not a valid email address.", { label: t })
};
function zn(e) {
  return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function Lt(e) {
  const t = e.getRule("match");
  if (!t || t === !0 || typeof t != "object" || !e.field)
    return null;
  const r = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
  if (!r)
    return null;
  const n = e.form.querySelector(`[data-formie-field-handle="${r}"]`);
  return n ? n.querySelector(e.config.fieldsSelector) : null;
}
const Bn = {
  rule: (e) => {
    const t = Lt(e);
    return t ? t.value === e.input.value : !0;
  },
  message: (e) => {
    const r = Lt(e)?.closest("[data-formie-field-handle]"), n = zn(r);
    return e.input.getAttribute("data-formie-validation-match-message") ?? e.t("{label} must match {value}.", {
      label: e.label,
      value: n
    });
  }
}, jn = {
  rule: ({ input: e, getRule: t }) => {
    const r = t("number");
    if (!r || !e.value || e.value.trim() === "")
      return !0;
    const n = parseFloat(e.value);
    if (Number.isNaN(n))
      return !1;
    if (r !== !0 && typeof r == "object") {
      const i = typeof r.min == "number" ? r.min : null, o = typeof r.max == "number" ? r.max : null;
      if (i !== null && n < i || o !== null && n > o)
        return !1;
    }
    return !0;
  },
  message: ({ input: e, label: t, getRule: r, t: n }) => {
    const i = r("number"), o = i !== !0 && i && typeof i == "object" && typeof i.min == "number" ? i.min : null, a = i !== !0 && i && typeof i == "object" && typeof i.max == "number" ? i.max : null;
    return o !== null && a !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? n("{label} must be no less than {min}.", { label: t, min: o }) : o !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? n("{label} must be no less than {min}.", { label: t, min: o }) : a !== null ? e.getAttribute("data-formie-validation-number-max-message") ?? n("{label} must be no greater than {max}.", { label: t, max: a }) : e.getAttribute("data-formie-validation-number-message") ?? e.getAttribute("data-formie-pattern-number-message") ?? e.getAttribute("data-pattern-number-message") ?? n("{label} is not a valid number.", { label: t });
  }
}, Wn = {
  rule: ({ input: e, getRule: t }) => {
    if (!t("required") || e.type === "hidden")
      return !0;
    if (e.type === "checkbox" || e.type === "radio") {
      const r = e.form?.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`) || [];
      return r.length ? Array.from(r).some((n) => n instanceof HTMLInputElement && n.checked) : e instanceof HTMLInputElement ? e.checked : !0;
    }
    return e.value.trim() !== "";
  },
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? r("{label} cannot be blank.", { label: t })
}, Kn = {
  rule: ({ input: e, getRule: t }) => {
    if (!t("url") || !e.value || e.value.length < 1)
      return !0;
    try {
      return new URL(e.value), !0;
    } catch {
      return !1;
    }
  },
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? r("{label} is not a valid URL.", { label: t })
}, Gn = {
  // Keep the core validator registry centralized so FormieValidator can extend
  // it at runtime while still shipping one predictable builtin rule surface.
  required: Wn,
  email: Un,
  url: Kn,
  number: jn,
  match: Bn
};
function mr() {
  return window.FormieTranslations || {};
}
function Jn() {
  if (typeof document > "u")
    return;
  const e = Array.from(document.querySelectorAll('script[type="application/json"][data-formie-translations]:not([data-formie-translations-loaded="true"])'));
  if (e.length === 0)
    return;
  let t = null;
  for (const r of e) {
    r.dataset.formieTranslationsLoaded = "true";
    const n = r.textContent?.trim();
    if (n)
      try {
        const i = JSON.parse(n);
        if (!i || Array.isArray(i) || typeof i != "object")
          continue;
        t = {
          ...t ?? mr(),
          ...i
        };
      } catch {
        continue;
      }
  }
  t && (window.FormieTranslations = t);
}
function dt() {
  return Jn(), mr();
}
function pr() {
  return { ...dt() };
}
function Na(e) {
  return window.FormieTranslations = { ...e }, pr();
}
function Ua(e) {
  return window.FormieTranslations = {
    ...dt(),
    ...e
  }, pr();
}
function Yn(e) {
  const t = {};
  let r = 0;
  for (; r < e.length; ) {
    for (; r < e.length && /\s/.test(e[r]); )
      r++;
    if (r >= e.length)
      break;
    const n = e.slice(r).match(/^(\w+|=\d+)\{/);
    if (!n)
      break;
    const i = n[1];
    r += n[0].length;
    let o = 1;
    const a = r;
    for (; r < e.length && o > 0; )
      e[r] === "{" ? o++ : e[r] === "}" && o--, o > 0 && r++;
    t[i] = e.slice(a, r), r++;
  }
  return t;
}
function Zn(e, t) {
  const r = `=${e}`;
  if (Object.prototype.hasOwnProperty.call(t, r))
    return t[r];
  if (typeof Intl < "u" && typeof Intl.PluralRules == "function") {
    const i = new Intl.PluralRules().select(e);
    if (Object.prototype.hasOwnProperty.call(t, i))
      return t[i];
  }
  if (e === 1 && Object.prototype.hasOwnProperty.call(t, "one"))
    return t.one;
  if (Object.prototype.hasOwnProperty.call(t, "other"))
    return t.other;
  const n = Object.keys(t)[0];
  return n ? t[n] : "";
}
function Qn(e, t) {
  const r = e.slice(t).match(/^\{(\w+),\s*plural,\s*/);
  if (!r)
    return null;
  const n = r[1], i = t + r[0].length;
  let o = i;
  for (; o < e.length; ) {
    for (; o < e.length && /\s/.test(e[o]); )
      o++;
    if (o >= e.length || e[o] === "}")
      break;
    const a = e.slice(o).match(/^(\w+|=\d+)\{/);
    if (!a)
      return null;
    o += a[0].length;
    let s = 1;
    for (; o < e.length && s > 0; )
      e[o] === "{" ? s++ : e[o] === "}" && s--, s > 0 && o++;
    o++;
  }
  return o >= e.length || e[o] !== "}" ? null : {
    param: n,
    body: e.slice(i, o),
    endIndex: o
  };
}
function Xn(e, t) {
  let r = "", n = 0;
  for (; n < e.length; ) {
    if (e[n] !== "{") {
      r += e[n], n++;
      continue;
    }
    const i = Qn(e, n);
    if (!i) {
      r += e[n], n++;
      continue;
    }
    const o = t[i.param], a = typeof o == "number" ? o : Number.parseInt(String(o ?? ""), 10) || 0, s = Yn(i.body);
    let l = Zn(a, s);
    l = l.replace(/#/g, String(a)), r += l, n = i.endIndex + 1;
  }
  return r;
}
function ei(e, t) {
  return e.replace(/\{(\w+),\s*number\}/g, (r, n) => {
    if (!Object.prototype.hasOwnProperty.call(t, n))
      return r;
    const i = t[n];
    return typeof i == "number" ? i.toLocaleString() : String(i);
  });
}
function ti(e, t) {
  return e.replace(/\{(\w+)\}/g, (r, n) => Object.prototype.hasOwnProperty.call(t, n) ? String(t[n]) : r);
}
function x(e, t = {}) {
  let r = dt()[e] || e;
  return r = Xn(r, t), r = ei(r, t), r = ti(r, t), r;
}
const za = x, ri = {
  // eslint-disable-next-line
  email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
  url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
  number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
  color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
  date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
  time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
  month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, J = H("general", "validator");
function ue(e) {
  return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function ni(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim();
  if (!r)
    return;
  const n = r.split(/\s+/).filter((i) => i !== t);
  if (n.length) {
    e.setAttribute("aria-describedby", n.join(" "));
    return;
  }
  e.removeAttribute("aria-describedby");
}
function ii(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function oi(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function ai(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
class si {
  constructor(t, r = {}) {
    this.errors = [], this.validators = {}, this.boundListeners = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.submitted = !1, this.initialValues = /* @__PURE__ */ new WeakMap(), this.form = t, this.onBlur = this.blurHandler.bind(this), this.onChange = this.changeHandler.bind(this), this.onInput = this.inputHandler.bind(this), this.config = {
      live: !1,
      errorAriaLive: "polite",
      errorMessage: "",
      fieldContainerErrorClass: [],
      inputErrorClass: [],
      messagesClass: [],
      messageClass: [],
      fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
      patterns: ri,
      ...r
    }, Object.entries(Gn).forEach(([n, i]) => {
      this.addValidator(n, i.rule, i.message);
    }), this.init();
  }
  init() {
    J.log("Initializing validator.", {
      formId: this.form.id || null,
      live: this.config.live
    }), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((t) => {
      this.initialValues.set(t, this.getInputValue(t));
    }), this.config.live && this.addEventListeners(), this.emitEvent(document, ye("ready"), {
      validator: this
    });
  }
  inputs(t = null) {
    if (ue(t))
      return [t];
    const r = t || this.form;
    return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter((n) => ue(n));
  }
  getInputValue(t) {
    return t instanceof HTMLInputElement && (t.type === "checkbox" || t.type === "radio") ? t.checked : t instanceof HTMLInputElement && t.type === "file" ? t.files?.length ? Array.from(t.files).map((r) => r.name).join("|") : "" : t.value ?? "";
  }
  isDirty(t) {
    return this.initialValues.has(t) ? this.getInputValue(t) !== this.initialValues.get(t) : (this.initialValues.set(t, this.getInputValue(t)), !1);
  }
  shouldShowError(t) {
    return this.submitted || this.activated.has(t);
  }
  isValid(t = null, r = {}) {
    return this.validate(t, r).length === 0;
  }
  validate(t = null, r = {}) {
    this.errors = [];
    const n = /* @__PURE__ */ new Set();
    return this.inputs(t).forEach((i) => {
      let o = !1;
      if (!this.isVisible(i, r))
        return;
      const a = i.closest("[data-formie-field-handle]"), s = i instanceof HTMLInputElement && (i.type === "checkbox" || i.type === "radio") ? `${a?.getAttribute("data-formie-field-handle") || ""}:${i.name}` : null;
      if (s) {
        if (n.has(s))
          return;
        n.add(s);
      }
      this.shouldShowError(i) && this.removeError(i);
      const l = this.getValidatorCallbackOptions(i);
      Object.entries(this.validators).forEach(([d, h]) => {
        if (!h.validate(l)) {
          const u = this.getErrorMessage(i, d, h, l);
          this.shouldShowError(i) && !o && this.showError(i, d, u), this.errors.push({
            input: i,
            field: l.field,
            validator: d,
            message: u,
            handle: l.field?.getAttribute("data-formie-field-handle") || null,
            result: !1
          }), o = !0;
        }
      }), !o && this.shouldShowError(i) && this.removeError(i);
    }), J.log("Validation pass complete.", {
      errorCount: this.errors.length,
      includeHiddenPages: r.includeHiddenPages === !0
    }), this.errors;
  }
  removeAllErrors() {
    this.inputs().forEach((t) => {
      this.removeError(t);
    });
  }
  removeError(t) {
    const r = t.closest("[data-formie-field-handle]");
    if (!r) {
      t.removeAttribute("aria-invalid");
      return;
    }
    const n = r.querySelector("[data-formie-field-errors]"), i = n?.id || "";
    r.querySelectorAll("[data-formie-field-error]").forEach((o) => {
      o.remove();
    }), n && (n.innerHTML = ""), r.querySelectorAll("input, select, textarea").forEach((o) => {
      const a = o;
      a.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && a.classList.remove(...this.config.inputErrorClass), a.removeAttribute("data-formie-input-has-error"), i && ni(a, i), r.querySelectorAll("[data-formie-field-error]").forEach((s) => {
        const l = s.id;
        l && ai(a, l);
      });
    });
    for (let o = r; o; o = o.parentElement?.closest("[data-formie-field-handle]"))
      this.config.fieldContainerErrorClass.length && o.classList.remove(...this.config.fieldContainerErrorClass), o.removeAttribute("data-formie-field-has-error");
    this.emitEvent(t, ye("clear-error"), {
      validator: this
    }), Z(this.form);
  }
  showError(t, r, n) {
    const i = t.closest("[data-formie-field-handle]");
    if (!i)
      return;
    let o = i.querySelector("[data-formie-field-errors]");
    o || (o = fr(i, (d) => {
      this.config.messagesClass.length && d.classList.add(...this.config.messagesClass);
    })), this.config.messagesClass.length && o.classList.add(...this.config.messagesClass), o.innerHTML = "";
    const a = i.getAttribute("data-formie-field-handle") || "field", s = `${a}-error`;
    o.id = o.id || `${a}-errors`, ot(
      o,
      un(this.config.errorAriaLive, this.submitted)
    );
    const l = document.createElement("div");
    l.setAttribute("data-formie-field-error", "true"), l.setAttribute(`data-formie-field-error-${r}`, "true"), l.setAttribute("id", s), l.setAttribute("role", "alert"), this.config.messageClass.length && l.classList.add(...this.config.messageClass), l.textContent = n, o.appendChild(l), i.setAttribute("data-formie-field-has-error", "true"), i.querySelectorAll("input, select, textarea").forEach((d) => {
      const h = d;
      h.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && h.classList.add(...this.config.inputErrorClass), h.setAttribute("data-formie-input-has-error", "true"), ii(h, o.id), oi(h, s);
    });
    for (let d = i; d; d = d.parentElement?.closest("[data-formie-field-handle]"))
      this.config.fieldContainerErrorClass.length && d.classList.add(...this.config.fieldContainerErrorClass), d.setAttribute("data-formie-field-has-error", "true");
    this.emitEvent(t, ye("show-error"), {
      validator: this,
      validatorName: r,
      errorMessage: n
    }), Z(this.form);
  }
  getValidatorCallbackOptions(t) {
    const r = t.closest("[data-formie-field-handle]"), n = r?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() ?? "", i = this.parseValidationRules(r?.getAttribute("data-formie-validation"));
    return {
      t: x,
      input: t,
      label: n,
      field: r,
      form: this.form,
      config: this.config,
      rules: i,
      getRule: (o) => this.getRule(r, o)
    };
  }
  getErrorMessage(t, r, n, i) {
    return (typeof n.errorMessage == "function" ? n.errorMessage(i) : n.errorMessage) ?? x("{label} is invalid.", { label: i.label });
  }
  getErrors() {
    return this.errors;
  }
  getFieldErrors(t = this.errors) {
    const r = {};
    return t.forEach((n) => {
      !n.handle || r[n.handle]?.length || (r[n.handle] = [n.message]);
    }), r;
  }
  getRule(t, r) {
    if (!t)
      return !1;
    const n = this.parseValidationRules(t.getAttribute("data-formie-validation"));
    return Object.prototype.hasOwnProperty.call(n, r) ? n[r] : !1;
  }
  parseValidationRules(t) {
    const r = {};
    if (!t)
      return r;
    let n = null;
    try {
      n = JSON.parse(t);
    } catch {
      return J.warn("Invalid validation rules payload.", {
        formId: this.form.id || null
      }), r;
    }
    return Array.isArray(n) && n.forEach((i) => {
      if (!i || typeof i != "object" || Array.isArray(i))
        return;
      const o = i, a = typeof o.type == "string" ? o.type.trim() : "";
      a && (r[a] = o);
    }), r;
  }
  destroy() {
    J.log("Destroying validator.", {
      formId: this.form.id || null
    }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, ye("destroy"), {
      validator: this
    });
  }
  isVisible(t, r = {}) {
    return t.disabled || t.hasAttribute("data-formie-conditions-disabled") || t.closest("[data-formie-conditions-disabled]") || t.closest("[data-formie-conditionally-hidden]") ? !1 : t.closest("[data-formie-page-hidden]") ? !!r.includeHiddenPages : !!(t.offsetWidth || t.offsetHeight || t.getClientRects().length);
  }
  blurHandler(t) {
    !(t.target instanceof HTMLElement) || !ue(t.target) || !t.target.form?.isSameNode(this.form) || t instanceof CustomEvent || t.target instanceof HTMLInputElement && t.target.type === "file" || t.target instanceof HTMLInputElement && (t.target.type === "checkbox" || t.target.type === "radio") || (this.isDirty(t.target) && this.activated.add(t.target), this.shouldShowError(t.target) && this.validate(t.target));
  }
  changeHandler(t) {
    if (!(!(t.target instanceof HTMLElement) || !ue(t.target) || !t.target.form?.isSameNode(this.form)) && !(t instanceof CustomEvent)) {
      if (t.target instanceof HTMLSelectElement) {
        this.activated.add(t.target), this.validate(t.target);
        return;
      }
      t.target instanceof HTMLInputElement && (t.target.type !== "file" && t.target.type !== "checkbox" && t.target.type !== "radio" || (this.activated.add(t.target), this.validate(t.target)));
    }
  }
  inputHandler(t) {
    !(t.target instanceof HTMLElement) || !ue(t.target) || !t.target.form?.isSameNode(this.form) || t instanceof CustomEvent || t.target instanceof HTMLInputElement && (t.target.type === "checkbox" || t.target.type === "radio") || this.shouldShowError(t.target) && this.validate(t.target);
  }
  submit(t = null, { final: r = !1 } = {}) {
    return this.submitted = !0, J.log("Submit validation requested.", {
      final: r
    }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(t, {
      includeHiddenPages: r
    });
  }
  resetLiveState() {
    this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
  }
  addEventListeners() {
    this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, J.log("Event listeners attached."));
  }
  removeEventListeners() {
    this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, J.log("Event listeners removed.");
  }
  emitEvent(t, r, n = {}) {
    t.dispatchEvent(new CustomEvent(r, {
      bubbles: !0,
      detail: n
    }));
  }
  addValidator(t, r, n) {
    this.validators[t] = {
      validate: r,
      errorMessage: n
    };
  }
  removeValidator(t) {
    delete this.validators[t];
  }
}
const Ee = "data-formie-submit-validation-disabled", Ne = "data-formie-preserve-disabled", li = "data-formie-submit-ready";
function gr(e) {
  return e.dataset.formieDisableSubmitUntilValid === "true";
}
function ui(e) {
  return Array.from(e.querySelectorAll('button[data-formie-action="submit"]')).filter((t) => t instanceof HTMLButtonElement);
}
function ci(e) {
  return !e.hasAttribute("data-formie-conditionally-hidden") && !e.closest("[data-formie-conditionally-hidden]");
}
function ft(e, t) {
  if (!gr(e) || e.getAttribute("data-formie-loading") === "true")
    return;
  const { scope: r, final: n } = Pe(e), i = t.isValid(r, {
    includeHiddenPages: n
  });
  e.setAttribute(li, i ? "true" : "false"), ui(e).forEach((o) => {
    if (ci(o)) {
      if (i) {
        if (!o.hasAttribute(Ee))
          return;
        o.hasAttribute(Ne) ? (o.disabled = !0, o.removeAttribute(Ne)) : o.disabled = !1, o.removeAttribute(Ee);
        return;
      }
      o.hasAttribute(Ee) || (o.disabled && o.setAttribute(Ne, "true"), o.setAttribute(Ee, "true")), o.disabled = !0;
    }
  });
}
function di(e, t, r) {
  if (!gr(e))
    return () => {
    };
  let n = !1;
  const i = () => {
    n || (n = !0, queueMicrotask(() => {
      n = !1, ft(e, t);
    }));
  };
  i();
  const o = () => {
    i();
  };
  e.addEventListener("input", o, !0), e.addEventListener("change", o, !0);
  const a = () => {
    window.setTimeout(() => {
      i();
    }, 0);
  };
  e.addEventListener("reset", a);
  const s = () => {
    i();
  };
  r.addEventListener("formie:conditions:evaluated", s);
  const l = new MutationObserver((d) => {
    d.some((m) => {
      if (m.type === "attributes") {
        const u = m.attributeName || "";
        return u === "data-formie-page-hidden" || u === "data-formie-conditionally-hidden" || u === "data-formie-loading" || u === "disabled";
      }
      return m.type === "childList";
    }) && i();
  });
  return l.observe(e, {
    childList: !0,
    subtree: !0,
    attributes: !0,
    attributeFilter: [
      "data-formie-page-hidden",
      "data-formie-conditionally-hidden",
      "data-formie-loading",
      "disabled"
    ]
  }), () => {
    e.removeEventListener("input", o, !0), e.removeEventListener("change", o, !0), e.removeEventListener("reset", a), r.removeEventListener("formie:conditions:evaluated", s), l.disconnect();
  };
}
const fi = "STALE_SUBMISSION_STATE", It = /* @__PURE__ */ new WeakMap(), Ie = /* @__PURE__ */ new WeakMap(), W = H("general", "submit-result");
function Je(e, t, r) {
  let n = e.querySelector(`input[name="${t}"]`);
  n || (n = document.createElement("input"), n.type = "hidden", n.name = t, e.appendChild(n)), n.value = r;
}
function Rt(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function ce(e, t) {
  e.querySelector(`input[name="${t}"]`)?.remove();
}
function mi(e, t) {
  try {
    const r = new URL(e, window.location.href);
    return r.searchParams.delete(t), r.toString();
  } catch {
    return e;
  }
}
function pi(e) {
  try {
    return new URL(e, window.location.href).origin === window.location.origin;
  } catch {
    return !1;
  }
}
function hr(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function gi(e) {
  return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function hi(e, t, r) {
  return t < 0 || r < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") === "page-position" ? Math.round((t + 1) / r * 100) : Math.round(t / r * 100);
}
function bi(e) {
  return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function yi(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function Ft(e, t) {
  const r = t.meta?.effectiveSubmitAction;
  return typeof r == "string" && r.trim() !== "" ? r.trim() : yi(e);
}
function kt(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function mt(e, t) {
  const r = [
    "[data-formie-form-header]",
    "[data-formie-form-navigation]",
    "[data-formie-form-body]",
    "[data-formie-form-footer]"
  ];
  e.toggleAttribute("data-formie-form-hidden", t), r.forEach((n) => {
    e.querySelectorAll(n).forEach((i) => {
      const o = i;
      t ? o.hidden = !0 : o.hidden = !1;
    });
  });
}
function z(e) {
  const t = It.get(e);
  typeof t == "number" && (window.clearTimeout(t), It.delete(e));
}
function vi(e, t) {
  Ie.has(e) || Ie.set(e, e.innerHTML), e.textContent = t;
}
function Ye(e) {
  const t = Ie.get(e);
  t !== void 0 && (e.innerHTML = t, Ie.delete(e));
}
function Ei(e, t) {
  const r = e.querySelector("[data-formie-progress-bar]"), n = e.querySelector("[data-formie-progress-value]");
  r && (r.style.width = `${t}%`, r.setAttribute("aria-valuenow", `${t}`), r.setAttribute("data-formie-progress-state", bi(t)), n && (n.textContent = `${t}%`, n.setAttribute("data-formie-progress-value", `${t}`)));
}
function Si(e, t) {
  if (!t)
    return;
  const r = (e.dataset.formieLoadingIndicator || "").trim();
  if (r) {
    if (t.setAttribute("data-formie-loading-indicator", r), r === "spinner") {
      ie(t, e, "loading", !0), Ye(t), t.removeAttribute("data-formie-loading-text");
      return;
    }
    if (r === "text") {
      const n = (e.dataset.formieLoadingIndicatorText || "").trim(), i = t.textContent?.trim() || "", o = n || i;
      t.setAttribute("data-formie-loading-text", o), vi(t, o);
      return;
    }
    Ye(t), t.removeAttribute("data-formie-loading-text");
  }
}
function br(e) {
  return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function yr(e, t) {
  if (e.getAttribute("data-formie-loading") === "true")
    return;
  e.setAttribute("data-formie-loading", "true"), br(e).forEach((n) => {
    "disabled" in n && (n.disabled ? n.setAttribute("data-formie-was-disabled", "true") : n.removeAttribute("data-formie-was-disabled"), n.disabled = !0);
  }), t && (t.setAttribute("data-formie-loading", "true"), Si(e, t));
}
function Re(e) {
  if (e.removeAttribute("data-formie-loading"), br(e).forEach((r) => {
    if ("disabled" in r) {
      const n = r, i = n.getAttribute("data-formie-was-disabled") === "true";
      n.disabled = i;
    }
    Ye(r), r.removeAttribute("data-formie-was-disabled"), r.removeAttribute("data-formie-loading"), ie(r, e, "loading", !1), r.removeAttribute("data-formie-loading-indicator"), r.removeAttribute("data-formie-loading-text");
  }), e.dataset.formieDisableSubmitUntilValid === "true") {
    const r = e;
    r.formieValidation && ft(e, r.formieValidation);
  }
}
function pt(e, t) {
  const r = hr(e), n = gi(e), i = r.findIndex((o) => o.getAttribute("data-formie-page-id") === t);
  if (r.forEach((o) => {
    o.getAttribute("data-formie-page-id") === t ? (o.removeAttribute("data-formie-page-hidden"), ae(o, e, "pageHidden")) : (o.setAttribute("data-formie-page-hidden", "true"), q(o, e, "pageHidden"));
  }), n.forEach((o, a) => {
    const s = o.getAttribute("data-formie-page-id") === t, l = i > -1 && a < i;
    ie(o, e, "tabCurrent", s), ie(o, e, "tabComplete", l);
    const d = o.querySelector("[data-formie-tab-link]");
    d && (ie(d, e, "tabLinkCurrent", s), s ? ae(d, e, "tabLinkInactive") : q(d, e, "tabLinkInactive")), s ? o.setAttribute("aria-current", "page") : o.removeAttribute("aria-current"), l ? o.setAttribute("data-formie-tab-complete", "true") : o.removeAttribute("data-formie-tab-complete");
  }), i > -1 && r.length > 0) {
    const o = hi(e, i, r.length);
    Ei(e, o);
  }
  if (Je(e, "pageId", t), Z(e), e.dataset.formieDisableSubmitUntilValid === "true") {
    const o = e;
    o.formieValidation && ft(e, o.formieValidation);
  }
}
function Ai(e, t) {
  const r = t.meta?.submissionUid;
  typeof r == "string" && r.trim() !== "" && Je(e, "submissionUid", r);
  const n = t.meta?.session?.continuation?.continuationToken;
  typeof n == "string" && n.trim() !== "" ? Je(e, "continuationToken", n) : ce(e, "continuationToken");
}
function wi(e) {
  const t = e.getAttribute("action");
  t && e.setAttribute("action", mi(t, "resumeToken"));
  try {
    const r = new URL(window.location.href);
    if (!r.searchParams.has("resumeToken"))
      return;
    r.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${r.pathname}${r.search}${r.hash}`);
  } catch {
  }
}
function Ti(e, t) {
  const r = t.meta?.resumeUrl;
  if (typeof r != "string" || r.trim() === "")
    return;
  const n = r.trim();
  if (!pi(n))
    return;
  e.getAttribute("action") && e.setAttribute("action", n);
  try {
    const o = new URL(n, window.location.href);
    window.history.replaceState({}, document.title, `${o.pathname}${o.search}${o.hash}`);
  } catch {
  }
}
function Se(e, t = {}) {
  const n = e.formieValidation, i = hr(e)[0]?.getAttribute("data-formie-page-id");
  if (z(e), e.reset(), t.preserveHiddenState || mt(e, !1), ce(e, "submissionId"), ce(e, "submissionUid"), ce(e, "continuationToken"), ce(e, "pageId"), wi(e), n?.resetLiveState(), i) {
    pt(e, i), e.dispatchEvent(new CustomEvent(Ge("reset"), { bubbles: !0 }));
    return;
  }
  Z(e), e.dispatchEvent(new CustomEvent(Ge("reset"), { bubbles: !0 }));
}
function Ci(e) {
  return e.code === fi || e.meta?.resetState === !0;
}
function Mi(e, t) {
  const r = t.submitData, n = /* @__PURE__ */ new Set();
  let i = !1;
  if (Array.isArray(r) && r.length > 0) {
    const h = r.filter(
      (m) => typeof m == "object" && m !== null && "event" in m && typeof m.event == "string"
    );
    for (const m of h) {
      const u = m.event;
      n.add(u), W.log("Dispatching submitData event.", {
        eventName: u
      }), u.startsWith("formie:payment:") && (i = !0), e.dispatchEvent(new CustomEvent(u, {
        bubbles: !0,
        detail: { data: m.data }
      }));
    }
  }
  const o = t.meta || {}, a = (o.paymentAction && typeof o.paymentAction == "object" ? o.paymentAction : null) || (o.paymentDecision && typeof o.paymentDecision == "object" ? o.paymentDecision.action : null), s = a ? String(a.event || "") : "", l = a ? a.payload : void 0, d = s;
  return d && !n.has(d) && (d.startsWith("formie:payment:") && (i = !0), e.dispatchEvent(new CustomEvent(d, {
    bubbles: !0,
    detail: { data: l }
  })), W.log("Dispatching fallback payment action event.", {
    eventName: d
  })), { hasPaymentFollowUpEvent: i };
}
function Li(e, t, r) {
  if (W.log("Applying submit result state.", {
    ok: t.ok,
    action: r,
    code: t.code,
    hasRedirect: !!t.redirect?.url,
    hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
  }), Ci(t)) {
    Se(e), W.log("Resetting state due to stale/reset marker.");
    return;
  }
  const n = Mi(e, t);
  if (!t.ok && t.redirect?.url && !n.hasPaymentFollowUpEvent) {
    W.log("Applying redirect fallback for failed result.", {
      url: t.redirect.url,
      target: t.redirect.target
    }), z(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank") : (Rt(e, "redirect"), window.location.href = t.redirect.url);
    return;
  }
  if (Ai(e, t), !t.ok) {
    W.log("Non-redirect failure; keeping current form state."), z(e);
    return;
  }
  if (Array.isArray(t.clientEvents) && t.clientEvents.length > 0 ? at(e, t.clientEvents) : tr(e, r), t.nextPage?.id) {
    z(e), e.formieValidation?.resetLiveState(), pt(e, t.nextPage.id), I(e, "formie:page:navigate:after", {
      pageId: t.nextPage.id
    }), W.log("Advanced to next page.", {
      nextPageId: t.nextPage.id
    });
    return;
  }
  if (r === "save") {
    z(e), Ti(e, t), W.log("Applied save/resume token state.");
    return;
  }
  if (r === "submit" && !t.redirect?.url) {
    const i = Ft(e, t), o = i === "message" && kt(e);
    if (i === "reload") {
      z(e), Rt(e, "reload"), window.location.reload();
      return;
    }
    if (i === "reset") {
      Se(e);
      return;
    }
    z(e), Se(e, { preserveHiddenState: o });
    return;
  }
  if (r === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
    const o = Ft(e, t) === "message" && kt(e);
    z(e), Se(e, { preserveHiddenState: o });
    return;
  }
  z(e);
}
const Fe = /* @__PURE__ */ new WeakMap();
function vr(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function Ii(e) {
  return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function Er(e) {
  return (e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function Ri(e) {
  const t = (e.dataset.formieSubmitActionMessageTimeout || "").trim();
  if (!t)
    return null;
  const r = Number.parseFloat(t);
  return !Number.isFinite(r) || r < 0 ? null : Math.round(r * 1e3);
}
function gt(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function Fi(e) {
  const t = Fe.get(e);
  typeof t == "number" && (window.clearTimeout(t), Fe.delete(e));
}
function Sr(e) {
  return e.querySelector("[data-formie-form-messages-top]") || e;
}
function Ar(e) {
  return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function ki(e, t) {
  return t === "bottom-form" ? Ar(e) : Sr(e);
}
function qi(e, t) {
  return t === "top-form" ? Sr(e) : t === "bottom-form" && !gt(e) ? Ar(e) : e;
}
function wr(e) {
  const t = Ii(e), r = ki(e, t);
  let n = r.querySelector("[data-formie-error-container], [data-formie-errors]");
  return n || (n = document.createElement("div"), n.setAttribute("data-formie-errors", "true"), q(n, e, "errors")), n.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function Tr(e, t) {
  let r = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-error-message-container", "true"), t.appendChild(r)), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-error", "true"), q(r, e, "message", "messageError"), r.setAttribute("role", "alert"), ot(
    r,
    Qt(it(e))
  ), r;
}
function Pi(e, t) {
  let r = e.querySelector("[data-formie-success-container]");
  const n = qi(e, t);
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-success-container", "true"), q(r, e, "successes")), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function Vi(e) {
  return fr(e, (t) => {
    q(t, e, "fieldErrors");
  });
}
function Oi(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim();
  if (!r)
    return;
  const n = r.split(/\s+/).filter((i) => i !== t).join(" ").trim();
  if (n) {
    e.setAttribute("aria-describedby", n);
    return;
  }
  e.removeAttribute("aria-describedby");
}
function _i(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function $i(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
function Cr(e) {
  e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
    const r = t, n = r.querySelector("[data-formie-field-errors]"), i = n?.id || "", o = Array.from(r.querySelectorAll("[data-formie-field-error]")).map((a) => a.id).filter(Boolean);
    ae(r, e, "fieldLayoutError"), r.removeAttribute("data-formie-field-has-error"), r.querySelectorAll("[data-formie-field-error]").forEach((a) => {
      a.remove();
    }), n && !n.querySelector("[data-formie-field-error]") && (n.innerHTML = ""), r.querySelectorAll("input, select, textarea").forEach((a) => {
      const s = a;
      s.removeAttribute("aria-invalid"), ae(s, e, "fieldControlError"), s.removeAttribute("data-formie-input-has-error"), i && Oi(s, i), o.forEach((l) => {
        $i(s, l);
      });
    });
  }), Z(e);
}
function Mr(e) {
  e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-error]").forEach((n) => {
      n.remove();
    }), ae(r, e, "message", "messageError"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-error"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-error]") || (r.innerHTML = "");
  });
}
function ht(e) {
  Fi(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((t) => {
    t.remove();
  }), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-success]").forEach((n) => {
      n.remove();
    }), ae(r, e, "message", "messageSuccess"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-success"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-success]") || (r.innerHTML = "");
  }), vr(e) === "message" && gt(e) || mt(e, !1);
}
function Lr(e) {
  e.querySelectorAll('[aria-invalid="true"]').forEach((t) => {
    t.removeAttribute("aria-invalid");
  });
}
function qt(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function Hi(e, t) {
  const r = Qt(it(e));
  Object.entries(t).forEach(([n, i]) => {
    const o = e.querySelector(`[data-formie-field-handle="${n}"]`);
    if (!o)
      return;
    const a = Vi(o), s = a.id && a.id.trim() ? a.id : `${n}-errors`;
    a.id = s, ot(a, r), q(o, e, "fieldLayoutError"), o.setAttribute("data-formie-field-has-error", "true"), i.forEach((d, h) => {
      const m = document.createElement("div");
      m.setAttribute("data-formie-field-error", "true"), m.setAttribute("role", "alert"), m.id = `${s}-${h + 1}`, q(m, e, "fieldError"), m.textContent = d, a.appendChild(m);
    });
    const l = a.querySelector("[data-formie-field-error]")?.id;
    o.querySelectorAll("input, select, textarea").forEach((d) => {
      const h = d;
      h.setAttribute("aria-invalid", "true"), q(h, e, "fieldControlError"), h.setAttribute("data-formie-input-has-error", "true"), qt(h, s), l && _i(h, l);
      const m = o.querySelector("[data-formie-instructions]");
      m?.id && qt(h, m.id);
    });
  }), Z(e);
}
function Pt(e, t) {
  const r = wr(e), n = Tr(e, r);
  q(r, e, "errors"), t.forEach((i) => {
    const o = document.createElement("div");
    o.setAttribute("data-formie-error", "true"), o.setAttribute("role", "alert"), q(o, e, "error"), o.innerHTML = i, n.appendChild(o);
  });
}
function Di(e) {
  if (e.ok || e.keepSubmitLoading !== !0)
    return !1;
  const t = e.meta || {}, r = String(t.paymentStatus || "");
  return r === "actionRequired" || r === "pending";
}
function xi(e, t) {
  const r = wr(e), n = Tr(e, r);
  q(r, e, "errors");
  const i = document.createElement("div");
  i.setAttribute("data-formie-notice", "true"), i.setAttribute("role", "status"), q(i, e, "message"), i.textContent = t, n.appendChild(i);
}
function Ni(e, t) {
  return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" ? !0 : vr(e) === "message" && Er(e) !== "";
}
function Ui(e, t) {
  const r = Er(e);
  if (!r)
    return;
  const n = Pi(e, r);
  q(n, e, "message", "messageSuccess"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-success", "true"), n.setAttribute("role", "status"), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true");
  const i = document.createElement("div");
  i.setAttribute("data-formie-success", "true"), q(i, e, "success"), i.innerHTML = t, n.appendChild(i), gt(e) && mt(e, !0);
  const o = Ri(e);
  if (o !== null) {
    const a = window.setTimeout(() => {
      Fe.delete(e), ht(e);
    }, o);
    Fe.set(e, a);
  }
}
function de(e, t) {
  if (Cr(e), Mr(e), ht(e), Lr(e), t.ok) {
    Ni(e, t) && Ui(e, t.message || "");
    return;
  }
  if (!t.ok) {
    if (Di(t)) {
      const r = t.meta || {}, n = String(r.paymentMessage || "").trim();
      n && xi(e, n);
      return;
    }
    t.fieldErrors && Hi(e, t.fieldErrors), t.formErrors?.length ? Pt(e, t.formErrors) : !t.fieldErrors && t.message && Pt(e, [t.message]), ir(e);
  }
}
const zi = H("general", "submit-flow");
function Bi(e) {
  return !(!e.ok && e.stage === "validate");
}
function Ir(e) {
  return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function Rr(e) {
  Cr(e), Mr(e), ht(e), Lr(e);
}
async function Fr(e) {
  const {
    id: t,
    target: r,
    form: n,
    bus: i,
    validator: o,
    validateOnSubmit: a,
    action: s,
    submitter: l,
    waitForSubmitDelay: d,
    onRefreshTokensAfterSubmit: h,
    dispatchSubmitResult: m
  } = e;
  Rr(n), yr(n, l || null);
  let u = {
    ok: !1,
    code: "SUBMIT_ERROR",
    message: "Submission failed.",
    formErrors: ["Submission failed."]
  };
  try {
    await d(n), u = await dr(n, s, i, {
      validator: o,
      validateOnSubmit: a
    }), de(n, u), m(u), Li(n, u, s), Bi(u) && await h(u);
  } catch (c) {
    u = {
      ok: !1,
      code: "SUBMIT_ERROR",
      message: c instanceof Error ? c.message : "Submission failed.",
      formErrors: [c instanceof Error ? c.message : "Submission failed."]
    }, de(n, u), m(u), zi.warn("Submit failed with exception.", {
      id: t,
      action: s,
      target: r,
      error: c instanceof Error ? c.message : c
    });
  } finally {
    Ir(u) || Re(n);
  }
  return u;
}
class kr {
  constructor() {
    this.modules = /* @__PURE__ */ new Map();
  }
  register(t, r = {}) {
    const n = this.modules.get(t.id);
    return n === t ? !0 : n && !r.replace ? (console.warn(
      `[formie] Module "${t.id}" is already registered. Pass { replace: true } to override the existing definition.`
    ), !1) : (this.modules.set(t.id, t), !0);
  }
  unregister(t) {
    this.modules.delete(t);
  }
  get(t) {
    return this.modules.get(t) || null;
  }
  getAll() {
    return Array.from(this.modules.values());
  }
}
const ji = {
  // Address providers stay behind lazy importer entries because their SDKs are
  // optional and often much heavier than the base form client.
  "address-finder": () => import("./address-finder-WK26R0JC.js").then((e) => e.addressFinderModule),
  "google-address": () => import("./google-address-dH_3aehb.js").then((e) => e.googleAddressModule),
  loqate: () => import("./loqate-ad0AYTwc.js").then((e) => e.loqateModule),
  "place-kit": () => import("./place-kit-CBC5rbiq.js").then((e) => e.placeKitModule)
}, Wi = {
  // Module ids map directly to importer functions so the loader can fetch only
  // the captcha chunks required by the current form manifest.
  "captcha-eu": () => import("./captcha-eu-BbAwVGLZ.js").then((e) => e.captchaEuModule),
  "friendly-captcha-v1": () => import("./friendly-captcha-v1-DVpzrEUc.js").then((e) => e.friendlyCaptchaV1Module),
  "friendly-captcha-v2": () => import("./friendly-captcha-v2-DNa4_1M6.js").then((e) => e.friendlyCaptchaV2Module),
  hcaptcha: () => import("./hcaptcha-DoUcyXrF.js").then((e) => e.hcaptchaModule),
  "recaptcha-enterprise": () => import("./recaptcha-enterprise-InP5KN6d.js").then((e) => e.recaptchaEnterpriseModule),
  "recaptcha-v2-checkbox": () => import("./recaptcha-v2-checkbox-BCC6OWEj.js").then((e) => e.recaptchaV2CheckboxModule),
  "recaptcha-v2-invisible": () => import("./recaptcha-v2-invisible-bbeguGz-.js").then((e) => e.recaptchaV2InvisibleModule),
  "recaptcha-v3": () => import("./recaptcha-v3-Ck7-ay4E.js").then((e) => e.recaptchaV3Module),
  snaptcha: () => import("./snaptcha-79zR2lYe.js").then((e) => e.snaptchaModule),
  turnstile: () => import("./turnstile-DsmdVVBh.js").then((e) => e.turnstileModule)
}, Ki = {
  // Keep the builtin map flat and explicit so manifest ids remain the source of
  // truth for lazy-loading first-party field enhancements.
  calculations: () => import("./calculations-sH41T4z7.js").then((e) => e.calculationsModule),
  "checkbox-radio": () => import("./checkbox-radio-B35i42qg.js").then((e) => e.checkboxRadioModule),
  combobox: () => import("./combobox--QEnV0H4.js").then((e) => e.comboboxModule),
  conditions: () => import("./conditions-BC4Sh8b8.js").then((e) => e.conditionsModule),
  "custom-google-maps": () => import("./custom-google-maps-CKKs97wc.js").then((e) => e.customGoogleMapsModule),
  "custom-link": () => import("./custom-link-NUfQXQoH.js").then((e) => e.customLinkModule),
  "custom-maps": () => import("./custom-maps-DxYVNl8n.js").then((e) => e.customMapsModule),
  "date-picker": () => import("./date-picker-6yi2Fe4O.js").then((e) => e.datePickerModule),
  "file-upload": () => import("./file-upload-Dg_T092t.js").then((e) => e.fileUploadModule),
  "upload-manager": () => import("./upload-manager-BtHWNyPw.js").then((e) => e.uploadManagerModule),
  hidden: () => import("./hidden-CPAgc0xx.js").then((e) => e.hiddenModule),
  "phone-country": () => import("./phone-country-BXXkec7c.js").then((e) => e.phoneCountryModule),
  "password-validation": () => import("./password-validation-CK2yFtve.js").then((e) => e.passwordValidationModule),
  "address-country": () => import("./address-country-Bwew4OuT.js").then((e) => e.addressCountryModule),
  "address-state": () => import("./address-state-CvePDt3q.js").then((e) => e.addressStateModule),
  repeater: () => import("./repeater-z-XYNaYx.js").then((e) => e.repeaterModule),
  "rich-text": () => import("./rich-text-ttv48-yG.js").then((e) => e.richTextModule),
  signature: () => import("./signature-B6xGh-wW.js").then((e) => e.signatureModule),
  summary: () => import("./summary-BTqluPAr.js").then((e) => e.summaryModule),
  "survey-likert": () => import("./survey-likert-kNJh-Mwx.js").then((e) => e.surveyLikertModule),
  "survey-rank": () => import("./survey-rank-BsKk3IWc.js").then((e) => e.surveyRankModule),
  "survey-rating": () => import("./survey-rating-CpBJ9NcS.js").then((e) => e.surveyRatingModule),
  table: () => import("./table-DCLHKJ0j.js").then((e) => e.tableModule),
  "text-limit": () => import("./text-limit-Coz8qaSK.js").then((e) => e.textLimitModule)
}, Gi = {
  // Keep payment providers lazy and separately addressable so forms only ship
  // the payment SDK wrapper code they actually declare in their manifest.
  bpoint: () => import("./bpoint-CeV6aBQn.js").then((e) => e.bpointModule),
  eway: () => import("./eway-CJlJMxrZ.js").then((e) => e.ewayModule),
  "go-cardless": () => import("./go-cardless-Ltat2TJH.js").then((e) => e.goCardlessModule),
  mollie: () => import("./mollie-BixqPSyI.js").then((e) => e.mollieModule),
  moneris: () => import("./moneris-BDq4ycm0.js").then((e) => e.monerisModule),
  opayo: () => import("./opayo-CmOHXLGC.js").then((e) => e.opayoModule),
  paddle: () => import("./paddle-CaKKwBA6.js").then((e) => e.paddleModule),
  paypal: () => import("./paypal-Cddy8eD5.js").then((e) => e.paypalModule),
  payway: () => import("./payway-VTZB0FMK.js").then((e) => e.paywayModule),
  square: () => import("./square-Dd8CgOGH.js").then((e) => e.squareModule),
  stripe: () => import("./stripe-BZdz9y6J.js").then((e) => e.stripeModule)
}, Ji = {
  ...Ki,
  ...ji,
  ...Wi,
  ...Gi
}, Ue = /* @__PURE__ */ new Map(), D = H("general", "loader"), Yi = new Function("src", "return import(src);");
async function Ae(e, t, r, n) {
  await e(tn(r), n), await e(en(t, r), n);
}
function qr(e) {
  return !!e && typeof e == "object" && typeof e.id == "string" && typeof e.setup == "function" && typeof e.match == "function";
}
async function Zi(e, t) {
  const r = Ji[e];
  return r ? (Ue.has(e) || Ue.set(e, (async () => {
    try {
      const n = await r();
      return qr(n) ? (t.registry.register(n), n) : null;
    } catch (n) {
      return console.error("[formie] Failed to load builtin module:", e, n), D.warn("Failed loading builtin module.", { moduleId: e, error: n }), null;
    }
  })()), Ue.get(e) || null) : null;
}
async function Qi(e) {
  try {
    const t = await Yi(e), r = t?.default || t?.formieModule || null;
    return qr(r) ? r : null;
  } catch (t) {
    return console.error("[formie] Failed to load module from src:", e, t), D.warn("Failed loading module from src.", { src: e, error: t }), null;
  }
}
async function Xi(e, t) {
  const r = t.registry.get(e.id);
  if (r)
    return r;
  const n = await Zi(e.id, t);
  if (n)
    return n;
  if (e.src) {
    const i = await Qi(e.src);
    if (i)
      return t.registry.register(i), i;
  }
  return null;
}
function ze(e) {
  return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : e.replace(/["\\]/g, "\\$&");
}
function we(e, t) {
  return e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function eo(e, t) {
  const r = t.setupContext.root, n = t.setupContext.form, i = e.targetType, o = e.targetId;
  return i === "selector" ? we(r, o).map((a) => ({ scope: i, element: a })) : i === "field" ? we(r, `[data-formie-field-handle="${ze(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "page" ? we(r, `[data-formie-page-id="${ze(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "button" ? we(r, `[data-formie-action="${ze(o)}"]`).map((a) => ({ scope: i, element: a })) : [{
    scope: "form",
    element: n || r
  }];
}
function to(e, t) {
  return (e.targets && e.targets.length > 0 ? e.targets : [{
    targetType: "form",
    targetId: "form"
  }]).flatMap((n) => eo(n, t));
}
async function Pr(e, t) {
  const r = [];
  D.log("Loading module manifest.", {
    manifestCount: e.length
  });
  for (const n of e) {
    const i = await Xi(n, t);
    if (!i) {
      D.warn("Skipping manifest item (definition not resolved).", {
        moduleId: n.id,
        src: n.src
      });
      continue;
    }
    const o = to(n, t);
    D.log("Resolved module targets.", {
      moduleId: i.id,
      targets: n.targets || [],
      targetCount: o.length
    }), o.length === 0 && i.kind === "address" && console.warn(
      `[formie] Address module "${n.id}" skipped: no target element found for fieldHandle="${n.targets?.find((a) => a.targetType === "field")?.targetId ?? "?"}". Check that the Address field exists in the rendered form.`
    );
    for (const a of o) {
      const s = {
        ...t.matchContext,
        target: a.element,
        scope: a.scope,
        manifestItem: n
      };
      if (!i.match(s)) {
        i.kind === "address" && console.warn(
          `[formie] Address module "${i.id}" skipped: target element does not contain [data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.`
        ), D.log("Module target did not match predicate.", {
          moduleId: i.id,
          scope: a.scope
        });
        continue;
      }
      const l = n.config || t.setupContext.options, d = i.id, h = {
        moduleId: i.id,
        moduleKind: i.kind,
        target: a.element,
        scope: a.scope,
        options: l,
        manifestItem: n
      };
      await Ae(t.setupContext.emit, d, "before-setup", h);
      let m = null;
      try {
        const u = await i.setup({
          ...t.setupContext,
          target: a.element,
          scope: a.scope,
          options: l
        });
        u && (m = u);
      } catch (u) {
        console.error(`[formie] Module "${i.id}" setup failed:`, u), D.warn("Module setup failed.", {
          moduleId: i.id,
          scope: a.scope,
          error: u
        });
      }
      await Ae(t.setupContext.emit, d, "after-setup", {
        ...h,
        instanceCreated: !!m
      }), m && (D.log("Module instance created.", {
        moduleId: i.id,
        scope: a.scope
      }), r.push({
        ...m,
        destroy: async () => {
          D.log("Destroying module instance.", {
            moduleId: i.id,
            scope: a.scope
          }), await Ae(t.setupContext.emit, d, "before-destroy", h), await m.destroy(), await Ae(t.setupContext.emit, d, "after-destroy", h), D.log("Module instance destroyed.", {
            moduleId: i.id,
            scope: a.scope
          });
        }
      }));
    }
  }
  return D.log("Module manifest processing complete.", {
    instanceCount: r.length
  }), r;
}
const ro = "formie:formStartedAt:";
function no(e) {
  const t = e.querySelector('input[name="formStartedAt"]');
  if (!t)
    return;
  const n = e.querySelector('input[name="renderId"]')?.value?.trim() ?? "", i = n ? `${ro}${n}` : null;
  let o = i ? sessionStorage.getItem(i) : null;
  o || (o = String(Date.now()), i && sessionStorage.setItem(i, o)), t.value = o;
}
const io = /* @__PURE__ */ new Set([
  "CRAFT_CSRF_TOKEN",
  "action",
  "redirect",
  "requestToken",
  "renderId",
  "formStartedAt",
  "submitAction",
  "pageId",
  "draftContextToken",
  "draftContext",
  "continuationToken"
]);
function Ze(e, t) {
  if (e == null)
    return String(e);
  if (typeof e == "string")
    return JSON.stringify(e);
  if (typeof e == "number" || typeof e == "boolean")
    return String(e);
  if (typeof e == "function")
    return "[function]";
  if (typeof File < "u" && e instanceof File)
    return `[file:${e.name}:${e.size}:${e.type}]`;
  if (typeof Blob < "u" && e instanceof Blob)
    return `[blob:${e.size}:${e.type}]`;
  if (Array.isArray(e))
    return `[${e.map((r) => Ze(r, t)).join(",")}]`;
  if (typeof e == "object") {
    if (t.has(e))
      return "[circular]";
    t.add(e);
    const r = Object.entries(e).sort(([n], [i]) => n.localeCompare(i)).map(([n, i]) => `${JSON.stringify(n)}:${Ze(i, t)}`);
    return t.delete(e), `{${r.join(",")}}`;
  }
  return JSON.stringify(String(e));
}
function oo(e) {
  return Ze(e, /* @__PURE__ */ new WeakSet());
}
function ao(e) {
  if (!e)
    return !1;
  const t = e.endsWith("[]") ? e.slice(0, -2) : e;
  return !io.has(t);
}
function Vt(e) {
  const t = Array.from(new FormData(e).entries()).filter(([r]) => ao(String(r || "")));
  return oo(t);
}
function so(e, t = {}) {
  let r = null, n = !1, i = !1, o = null, a = null, s = null;
  const l = () => {
    o !== null && (window.cancelAnimationFrame(o), o = null), a !== null && (window.clearTimeout(a), a = null), s !== null && (window.clearTimeout(s), s = null);
  }, d = () => n ? (i = Vt(e) !== r, i) : !1, h = () => {
    r = Vt(e), n = !0, i = !1;
  }, m = () => {
    l(), n = !1, o = window.requestAnimationFrame(() => {
      o = null, s = window.setTimeout(() => {
        s = null, h();
      }, 0);
    });
  }, u = () => {
    a !== null && window.clearTimeout(a), a = window.setTimeout(() => {
      a = null, d();
    }, 120);
  }, c = (b) => {
    t.shouldWarn && !t.shouldWarn() || d() && (b.preventDefault(), b.returnValue = "");
  };
  return e.addEventListener("input", u), e.addEventListener("change", u), window.addEventListener("beforeunload", c), m(), {
    captureBaseline: h,
    scheduleBaselineCapture: m,
    refreshDirtyState: d,
    destroy: () => {
      l(), e.removeEventListener("input", u), e.removeEventListener("change", u), window.removeEventListener("beforeunload", c);
    }
  };
}
function lo(e) {
  return e.hasAttribute("data-formie-conditionally-hidden") || !!e.closest("[data-formie-conditionally-hidden]") || e.hasAttribute("data-formie-page-hidden") || !!e.closest("[data-formie-page-hidden]");
}
function uo(e, t) {
  const r = e.querySelectorAll(`[data-formie-action="${t}"]`);
  return Array.from(r).some((n) => !lo(n));
}
function co(e) {
  const { final: t } = Pe(e);
  return "submit";
}
function fo(e) {
  const t = co(e);
  return !uo(e, t);
}
function mo(e) {
  const t = (r) => {
    if (r.key !== "Enter" || r.defaultPrevented)
      return;
    const n = r.target;
    (n instanceof HTMLInputElement || n instanceof HTMLSelectElement) && (n instanceof HTMLInputElement && (n.type === "button" || n.type === "submit" || n.type === "reset" || n.type === "file") || fo(e) && r.preventDefault());
  };
  return e.addEventListener("keydown", t, !0), () => {
    e.removeEventListener("keydown", t, !0);
  };
}
const te = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])', po = 300, go = "/actions/formie/server/forms/render", Ot = "/api", ho = "/actions/formie/server/forms/refresh-tokens", bo = "/actions/formie/server/submissions/submit", yo = "/actions/formie/server/submissions/set-page", vo = "/actions/formie/server/submissions/clear-submission", Eo = "/actions/formie/file-upload/hydrate", L = H("general", "client"), _t = /* @__PURE__ */ new Set();
function pe(e, t) {
  if (e == null || e === "")
    return t;
  const r = e.toLowerCase();
  return !(r === "false" || r === "0" || r === "off");
}
function Qe(e) {
  return e.formieRefreshTokens != null && e.formieRefreshTokens !== "" ? pe(e.formieRefreshTokens, !1) : e.formieStaticCache != null && e.formieStaticCache !== "" ? pe(e.formieStaticCache, !1) : !1;
}
function re(e) {
  const t = e instanceof HTMLElement ? e.dataset : {};
  return {
    mode: "server-rendered",
    transport: t.formieTransport || "rest",
    formHandle: t.formieHandle,
    endpoint: t.formieEndpoint,
    staticCache: Qe(t),
    autoVisible: pe(t.formieAutoVisible, !0),
    compatibility: pe(t.formieCompatibility, !1)
  };
}
function Ve(e) {
  return e || "server-rendered";
}
function Oe(e) {
  return e || "rest";
}
function Te(e) {
  return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function So(e, t) {
  _t.has(e) || (_t.add(e), L.warn(t));
}
function Vr(e, t) {
  if (!e)
    return e;
  try {
    return new URL(e).toString();
  } catch {
  }
  if (!t)
    return e;
  try {
    return new URL(e, t).toString();
  } catch {
    return e;
  }
}
function oe(e, t) {
  const r = (e || "").trim();
  return r ? r.includes("/actions/") ? r : Vr(t, r) : t;
}
function Ao(e, t) {
  return oe(e.endpoint || t.dataset.formieEndpoint, go);
}
function wo(e, t) {
  const r = (e.endpoint || t.dataset.formieEndpoint || "").trim();
  return r ? r.includes("/graphql") || r.endsWith("/api") || r.includes("/actions/graphql/") ? r : Vr(Ot, r) : Ot;
}
function bt(e, t) {
  return oe(
    t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint,
    ho
  );
}
function $t(e, t) {
  if (!e)
    return t;
  try {
    const r = new URL(e, window.location.origin), n = new URL(t, window.location.origin);
    return r.searchParams.forEach((i, o) => {
      n.searchParams.has(o) || n.searchParams.set(o, i);
    }), n.toString();
  } catch {
    return t;
  }
}
function To(e, t, r) {
  const n = r.endpoint || e.dataset.formieEndpoint, i = oe(n, bo), o = t.getAttribute("action");
  t.setAttribute("action", $t(o, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((a) => {
    const s = a.getAttribute("href"), l = oe(n, yo);
    a.setAttribute("href", $t(s, l));
  }), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((a) => {
    a.setAttribute(
      "data-formie-file-upload-hydrate-endpoint",
      oe(n, Eo)
    );
  });
}
function yt(e, t) {
  if (e === "graphql" && t !== "server-rendered")
    throw new Error(`Formie ${t} mode does not support GraphQL transport yet.`);
}
function vt(e) {
  if (e == null)
    return !1;
  const t = e.trim().toLowerCase();
  return t === "true" || t === "1" || t === "";
}
function Co(e) {
  return pe(e.dataset.formieAutomaticSubmissionState, !0);
}
function Mo(e, t, r) {
  return oe(
    r.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint,
    vo
  );
}
function Lo(e) {
  return vt(e.dataset.formieUnloadWarning);
}
function Ht(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function Be(e) {
  e.removeAttribute("data-formie-internal-navigation");
}
function Dt(e) {
  return e.getAttribute("data-formie-internal-navigation") !== null;
}
function xt(e, t) {
  if (!e)
    return !1;
  try {
    return new URL(e, window.location.origin).searchParams.has(t);
  } catch {
    return !1;
  }
}
function Io(e) {
  return xt(window.location.href, "resumeToken") || xt(e.getAttribute("action"), "resumeToken");
}
function Ro(e) {
  return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function Fo(e, t = 0) {
  if (!e)
    return t;
  const r = Number.parseInt(e, 10);
  return Number.isFinite(r) ? r : t;
}
function ko(e) {
  return Math.max(0, Fo(e.dataset.formieSubmitDelay, po));
}
function Ce(e) {
  return vt(e.dataset.formieValidationOnSubmit);
}
async function Xe(e) {
  const t = ko(e);
  t < 1 || await new Promise((r) => {
    window.setTimeout(r, t);
  });
}
function Nt(e, t) {
  const r = e?.getAttribute(t)?.trim();
  if (!r)
    return null;
  try {
    return JSON.parse(r);
  } catch (n) {
    return console.error(`[formie] Failed to parse ${t}.`, n), null;
  }
}
function Ut(e, t) {
  const r = t || (e instanceof HTMLFormElement ? e : null);
  if (!r)
    return null;
  const n = Nt(r, "data-formie-modules"), i = Nt(r, "data-formie-theme");
  return !n && !i ? null : {
    modules: n || void 0,
    theme: i || void 0
  };
}
function qo(e) {
  if (!(e instanceof HTMLElement))
    return !0;
  if (!e.isConnected || e.hidden || e.closest("[hidden]"))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function Po(e, t) {
  return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function O(e) {
  const t = e, r = t.id ? `#${t.id}` : "", n = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
  return `${t.tagName ? t.tagName.toLowerCase() : "element"}${r}${n}`;
}
function Et(e, t) {
  if (t) {
    if (t.csrf?.param && t.csrf?.token) {
      let r = e.querySelector(`input[name="${t.csrf.param}"]`);
      r ? r.value = t.csrf.token : (r = document.createElement("input"), r.type = "hidden", r.name = t.csrf.param, r.value = t.csrf.token, r.setAttribute("autocomplete", "off"), e.prepend(r));
    }
    if (t.requestToken) {
      const r = e.querySelector('input[name="requestToken"]');
      r && (r.value = t.requestToken);
    }
    if (t.renderId) {
      const r = e.querySelector('input[name="renderId"]');
      r && (r.value = t.renderId);
    }
    t.captchas && typeof t.captchas == "object" && Object.values(t.captchas).forEach((r) => {
      if (!r || typeof r != "object")
        return;
      const n = r;
      if (!n.sessionKey)
        return;
      const i = e.querySelector(`input[name="${n.sessionKey}"]`);
      i && typeof n.value == "string" && (i.value = n.value);
    });
  }
}
async function Vo(e, t) {
  const r = Ve(t.mode), n = Oe(t.transport);
  if (r !== "server-rendered")
    return null;
  if (t.payload)
    return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
  yt(n, r);
  const i = !!Te(e), o = t.formHandle || e.dataset.formieHandle;
  if (i || !o)
    return null;
  const a = {
    mode: r,
    endpoint: t.endpoint,
    locale: t.locale,
    siteId: t.siteId,
    theme: t.theme,
    themeConfig: t.themeConfig
  }, s = n === "graphql" ? wo(t, e) : Ao(t, e), l = n === "graphql" ? await kn(s, o, a) : await Fn(s, o, {
    ...a,
    endpoint: s
  });
  return l?.html && (e.innerHTML = l.html), l;
}
async function Or(e, t, r) {
  if (t.refreshTokens === !1)
    return;
  yt(Oe(t.transport), Ve(t.mode));
  const n = t.formHandle || e.dataset.formieHandle;
  if (!n)
    return;
  const i = bt(t, e), a = r.querySelector('input[name="renderId"]')?.value || void 0, s = await ut(i, n, a);
  Et(r, s), I(e, "formie:refresh-tokens:refreshed", s);
}
function Oo(e, t, r, n, i, o) {
  const a = String(
    t.dataset.formieSubmitMethod || ""
  ).trim().toLowerCase(), s = Mo(r, e, t);
  let l = !1;
  const d = t.querySelectorAll("[data-formie-action]"), h = (c) => {
    if (c) {
      t.setAttribute("data-formie-pending-action", c);
      return;
    }
    t.removeAttribute("data-formie-pending-action");
  };
  if (Lo(t)) {
    const c = so(t, {
      shouldWarn: () => !Dt(t)
    }), b = (f) => {
      if (!(f instanceof CustomEvent))
        return;
      const v = f.detail;
      v?.ok && v.action === "save" && c.scheduleBaselineCapture();
    }, w = () => {
      c.scheduleBaselineCapture();
    };
    e.addEventListener("formie:submit:result", b), t.addEventListener("formie:state:reset", w), o.push(() => {
      e.removeEventListener("formie:submit:result", b), t.removeEventListener("formie:state:reset", w), c.destroy();
    });
  }
  if (d.forEach((c) => {
    const b = (w) => {
      const f = w.currentTarget.getAttribute("data-formie-action"), v = t.querySelector('input[name="submitAction"]');
      h(f), f && v && (v.value = f);
    };
    c.addEventListener("click", b), o.push(() => {
      c.removeEventListener("click", b);
    });
  }), t.querySelectorAll("[data-formie-tab-link]").forEach((c) => {
    const b = async (w) => {
      if (a !== "ajax") {
        Ro(w) && Ht(t, "set-page");
        return;
      }
      w.preventDefault();
      const f = w.currentTarget, v = f?.getAttribute("data-formie-page-id"), E = f?.getAttribute("href");
      if (!(!v || !E)) {
        pt(t, v), I(e, "formie:page:navigate", {
          pageId: v,
          href: E
        });
        try {
          const y = await qn(E, t, v);
          I(e, "formie:page:navigate:after", {
            pageId: v,
            href: E,
            response: y
          });
        } catch (y) {
          console.error("[formie] Failed to persist page navigation state.", y), I(e, "formie:page:navigate:error", {
            pageId: v,
            href: E,
            error: y
          });
        }
      }
    };
    c.addEventListener("click", b), o.push(() => {
      c.removeEventListener("click", b);
    });
  }), !Co(t)) {
    let c = !1;
    const b = () => {
      c || Dt(t) || Io(t) || (c = !0, Pn(s, t));
    };
    window.addEventListener("pagehide", b), window.addEventListener("beforeunload", b), o.push(() => {
      window.removeEventListener("pagehide", b), window.removeEventListener("beforeunload", b);
    });
  }
  const u = async (c) => {
    if (l)
      return;
    const b = a === "ajax";
    if (c.preventDefault(), t.getAttribute("data-formie-loading") === "true") {
      if (!(t.getAttribute("data-formie-internal-resubmit") === "true"))
        return;
      t.removeAttribute("data-formie-internal-resubmit");
    } else
      t.removeAttribute("data-formie-internal-resubmit");
    const f = c.submitter, v = f?.getAttribute("data-formie-action"), E = t.getAttribute("data-formie-pending-action"), y = t.querySelector('input[name="submitAction"]'), p = v || E || y?.value || "submit";
    let g = null, A = !1;
    try {
      if (b)
        g = await Fr({
          target: e,
          form: t,
          bus: n,
          validator: i,
          validateOnSubmit: Ce(t),
          action: p,
          submitter: f,
          waitForSubmitDelay: Xe,
          onRefreshTokensAfterSubmit: async () => {
            await Or(e, r, t);
          },
          dispatchSubmitResult: (C) => {
            I(e, "formie:submit:result", C);
          }
        });
      else {
        if (Rr(t), yr(t, f), await Xe(t), g = await dr(t, p, n, {
          validator: i,
          validateOnSubmit: Ce(t),
          preflightOnly: !0
        }), g.ok) {
          tr(t, p), l = !0, Ht(t, "submit"), h(null);
          let C = !1;
          const T = () => {
            if (C = !0, l = !1, Be(t), Re(t), i && Ce(t)) {
              const { scope: R, final: F } = Pe(t), S = i.submit(F ? t : R, { final: F });
              S.length > 0 && de(t, {
                ok: !1,
                stage: "validate",
                code: "VALIDATION_FAILED",
                message: i.config.errorMessage || "Validation failed.",
                fieldErrors: i.getFieldErrors(S),
                formErrors: [i.config.errorMessage || "Validation failed."]
              });
            }
          };
          if (typeof t.requestSubmit == "function") {
            t.addEventListener("invalid", T, !0);
            try {
              t.requestSubmit();
            } finally {
              t.removeEventListener("invalid", T, !0);
            }
          } else
            t.submit();
          if (C)
            return;
          A = !0;
          return;
        }
        de(t, g), I(e, "formie:submit:result", g), Be(t);
      }
    } catch (C) {
      l = !1, g = {
        ok: !1,
        code: "SUBMIT_ERROR",
        message: C instanceof Error ? C.message : "Submission failed.",
        formErrors: [C instanceof Error ? C.message : "Submission failed."]
      }, de(t, g), I(e, "formie:submit:result", g), Be(t);
    } finally {
      h(null), !b && !A && !Ir(g) && Re(t);
    }
  };
  t.addEventListener("submit", u), o.push(() => {
    t.removeEventListener("submit", u);
  });
}
async function _o(e, t, r) {
  if (t.refreshTokens === !1 || !t.staticCache)
    return;
  yt(Oe(t.transport), Ve(t.mode));
  const n = t.formHandle || e.dataset.formieHandle, i = bt(t, e), a = r?.querySelector('input[name="renderId"]')?.value || void 0;
  if (!n)
    return;
  const s = await ut(i, n, a);
  !s || !r || (Et(r, s), I(e, "formie:refresh-tokens:after", s));
}
function $o() {
  const e = /* @__PURE__ */ new Map(), t = new kr(), r = /* @__PURE__ */ new Map(), n = /* @__PURE__ */ new Map(), i = [
    "prepare",
    "normalize",
    "validate",
    "screen",
    "authorize",
    "dispatch",
    "finalize"
  ], o = async (f) => {
    const v = n.get(f);
    if (v) {
      await v;
      return;
    }
    const E = (async () => {
      L.log("Unmount requested.", { target: O(f) });
      const y = r.get(f);
      y && (y(), r.delete(f));
      const p = e.get(f);
      if (!p) {
        L.log("Unmount skipped (no mounted state).", { target: O(f) });
        return;
      }
      I(f, "formie:unmount:before", {
        id: p.instance.id
      }), p.unbinds.forEach((g) => {
        g();
      }), p.unbinds = [], p.validator?.destroy(), p.validator = null;
      for (const g of p.modules)
        await g.destroy();
      p.modules = [], p.bus.clear(), e.delete(f), I(f, "formie:unmount:after", {
        id: p.instance.id
      }), L.log("Unmount complete.", { id: p.instance.id, target: O(f) });
    })().finally(() => {
      n.delete(f);
    });
    n.set(f, E), await E;
  }, a = async (f, v) => {
    L.log("Mount requested.", {
      target: O(f),
      mode: v.mode,
      autoVisible: v.autoVisible
    });
    const E = r.get(f);
    E && (E(), r.delete(f));
    const y = e.get(f);
    if (y)
      return L.log("Mount skipped (already mounted).", {
        id: y.instance.id,
        target: O(f)
      }), y.instance;
    const p = new or(), g = [], A = f?.id || `formie-${e.size + 1}`, C = re(f), T = {
      ...C,
      ...v,
      mode: Ve(v.mode ?? C.mode),
      transport: Oe(v.transport ?? C.transport)
    }, R = Qr(T.compatibility);
    if (T.mode !== "server-rendered" && !Te(f))
      throw new Error(`Formie ${T.mode} mode is not implemented yet in the browser client.`);
    const F = await Vo(f, T), S = Te(f);
    T.staticCache = v.staticCache ?? Qe(S ? S.dataset : f.dataset);
    const V = Ut(f, S), N = F || V ? {
      ...F || {},
      ...V || {}
    } : null, K = N?.theme, Q = {}, he = (N?.modules || []).filter((M) => !!M?.id && !!M?.type);
    L.log("Resolved mount payload.", {
      target: O(f),
      hasRenderPayload: !!F,
      hasEmbeddedPayload: !!V,
      moduleCount: he.length
    });
    const se = Ct(f, K, S), P = S ? new si(S, {
      live: vt(S.dataset.formieValidationOnFocus),
      errorAriaLive: it(S),
      errorMessage: S.dataset.formieErrorMessage || "",
      fieldContainerErrorClass: se.fieldLayoutError || [],
      inputErrorClass: se.fieldControlError || [],
      messagesClass: se.fieldErrors || [],
      messageClass: se.fieldError || []
    }) : null;
    if (S && P) {
      const M = S;
      M.formieValidation = P, Q.validation = P;
      const _ = {
        validator: P,
        addValidator: P.addValidator.bind(P),
        removeValidator: P.removeValidator.bind(P)
      };
      I(S, "formie:validator:ready", _), I(f, "formie:validator:ready", _);
    }
    S && (no(S), T.themeConfig && typeof T.themeConfig == "object" && S.setAttribute("data-formie-theme-config", JSON.stringify(T.themeConfig)), T.theme && T.theme !== "formie" && S.setAttribute("data-formie-frontend-theme", T.theme), (F || T.endpoint || f.dataset.formieEndpoint) && To(f, S, T), T.mode === "server-rendered" && Ln(S) && (Mn(S), ir(S)), Z(S)), Object.keys(se).length && I(f, "formie:theme:applied", {
      hasClasses: !0
    });
    const be = await Pr(he, {
      registry: t,
      matchContext: {
        root: f,
        form: S,
        mode: T.mode
      },
      setupContext: {
        formId: A,
        root: f,
        form: S,
        target: f,
        scope: "form",
        state: Q,
        on: (M, _) => p.on(M, _),
        emit: (M, _) => (I(f, M, _), p.emitSafe(M, _).then((X) => {
          X.failed.length > 0 && L.warn("Lifecycle listeners failed.", {
            eventName: M,
            failed: X.failed.length
          });
        }))
      }
    });
    L.log("Module setup complete.", {
      target: O(f),
      moduleInstances: be.length
    });
    const $e = {
      id: A,
      root: f,
      submit: async (M = "submit") => {
        if (L.log("Submit requested.", {
          id: A,
          target: O(f),
          action: M
        }), !S)
          return {
            ok: !1,
            code: "FORM_NOT_FOUND",
            message: "No form element found for mount target.",
            formErrors: ["No form element found for mount target."]
          };
        const _ = S.querySelector('input[name="submitAction"]');
        if (_ && (_.value = M), S.getAttribute("data-formie-loading") === "true")
          return {
            ok: !1,
            code: "SUBMIT_IN_PROGRESS",
            message: "Submission already in progress.",
            formErrors: []
          };
        const X = S.querySelector(`[data-formie-action="${M}"]`), ee = await Fr({
          id: A,
          target: f,
          form: S,
          bus: p,
          validator: P,
          validateOnSubmit: Ce(S),
          action: M,
          submitter: X,
          waitForSubmitDelay: Xe,
          onRefreshTokensAfterSubmit: async () => {
            await Or(f, T, S);
          },
          dispatchSubmitResult: (He) => {
            I(f, "formie:submit:result", He);
          }
        });
        return L.log("Submit completed.", {
          id: A,
          action: M,
          ok: ee.ok,
          code: ee.code,
          message: ee.message
        }), ee;
      },
      destroy: async () => {
        await o(f);
      },
      on: (M, _) => p.on(M, _)
    };
    S && (ln({
      target: f,
      form: S,
      validatorDetail: P ? {
        validator: P,
        addValidator: P.addValidator.bind(P),
        removeValidator: P.removeValidator.bind(P)
      } : null,
      options: R,
      unbinds: g
    }), sn({
      target: f,
      form: S,
      instance: $e,
      options: R,
      unbinds: g
    })), S && (Oo(f, S, T, p, P, g), P && (g.push(di(S, P, f)), g.push(mo(S))), await _o(f, T, S), S.dispatchEvent(new CustomEvent("formie:state:reset")), window.setTimeout(() => {
      S.dispatchEvent(new CustomEvent("formie:state:reset"));
    }, 350)), i.forEach((M) => {
      const _ = p.on(`formie:stage:${M}:before`, async (G) => {
        I(f, `formie:stage:${M}:before`, G);
      }), X = p.on(`formie:stage:${M}:before`, async (G) => {
        for (const le of be)
          le.onBeforeStage && await le.onBeforeStage(G);
      }), ee = p.on(`formie:stage:${M}:after`, async (G) => {
        I(f, `formie:stage:${M}:after`, G);
      }), He = p.on(`formie:stage:${M}:after`, async (G) => {
        const le = G;
        for (const wt of be)
          wt.onAfterStage && await wt.onAfterStage(le, le.result);
      });
      g.push(_, X, ee, He);
    });
    const Kr = p.on("formie:submit:before", async (M) => {
      I(f, "formie:submit:before", M);
    }), Gr = p.on("formie:submit:after", async (M) => {
      I(f, "formie:submit:after", M);
    }), Jr = p.on("formie:submit:final:before", async (M) => {
      I(f, "formie:submit:final:before", M);
    }), Yr = p.on("formie:submit:final:after", async (M) => {
      I(f, "formie:submit:final:after", M);
    });
    return g.push(
      Kr,
      Gr,
      Jr,
      Yr
    ), e.set(f, {
      options: T,
      bus: p,
      form: S,
      validator: P,
      modules: be,
      unbinds: g,
      instance: $e
    }), I(f, "formie:mount:after", {
      id: A,
      mode: T.mode
    }), S instanceof HTMLFormElement && yn(S), L.log("Mount complete.", {
      id: A,
      target: O(f),
      mode: T.mode
    }), $e;
  }, s = (f, v) => {
    if (!v.autoVisible || qo(f) || typeof IntersectionObserver > "u")
      return a(f, v);
    if (e.has(f))
      return Promise.resolve(e.get(f)?.instance || null);
    if (r.has(f))
      return L.log("Mount deferred (already waiting visibility).", {
        target: O(f)
      }), Promise.resolve(null);
    const E = new IntersectionObserver((y) => {
      y.some((g) => g.target === f && g.isIntersecting) && (E.disconnect(), r.delete(f), L.log("Visibility reached, proceeding mount.", {
        target: O(f)
      }), a(f, {
        ...v,
        autoVisible: !1
      }));
    }, {
      threshold: 0.01
    });
    return E.observe(f), r.set(f, () => {
      E.disconnect();
    }), L.log("Mount deferred until visible.", {
      target: O(f)
    }), Promise.resolve(null);
  };
  return {
    mount: a,
    unmount: o,
    update: async (f, v) => {
      const E = e.get(f);
      if (!E)
        return a(f, {
          ...re(f),
          ...v,
          mode: v.mode || "server-rendered"
        });
      E.options = {
        ...E.options,
        ...v
      };
      const y = v.payload?.theme || E.options.payload?.theme || Ut(f, E.form)?.theme, p = Ct(f, y, E.form);
      return E.validator && (E.validator.config.fieldContainerErrorClass = p.fieldLayoutError || [], E.validator.config.inputErrorClass = p.fieldControlError || [], E.validator.config.messagesClass = p.fieldErrors || [], E.validator.config.messageClass = p.fieldError || []), Object.keys(p).length && I(f, "formie:theme:applied", {
        hasClasses: !0,
        reason: "update"
      }), E.instance;
    },
    getInstance: (f) => e.get(f)?.instance || null,
    refreshForCache: async (f) => {
      So(
        "refreshForCache",
        "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead."
      );
      let v = null;
      if (typeof f == "string") {
        const F = document.getElementById(f);
        F ? v = F : v = document.querySelector(`[data-formie-form-id="${f}"]`);
      } else
        v = f;
      if (!v) {
        L.warn("refreshForCache target not found.", {
          targetOrId: f
        });
        return;
      }
      const E = e.get(v), y = Te(v), p = E?.options || re(v);
      if (!y) {
        L.warn("refreshForCache found no form element for target.", {
          target: O(v)
        });
        return;
      }
      const g = p.formHandle || v.dataset.formieHandle || y.dataset.formieHandle, A = bt(p, v), T = y.querySelector('input[name="renderId"]')?.value || void 0;
      if (!g) {
        L.warn("refreshForCache found no form handle for target.", {
          target: O(v)
        });
        return;
      }
      const R = await ut(A, g, T);
      R && (Et(y, R), I(v, "formie:refresh-tokens:after", R));
    },
    registerModule: (f, v) => t.register(f, v),
    unregisterModule: (f) => {
      t.unregister(f);
    },
    getRegisteredModules: () => t.getAll(),
    scan: async (f) => {
      const v = f || document, E = Array.from(v.querySelectorAll(te));
      L.log("Scan started.", {
        scope: v === document ? "document" : v,
        targetCount: E.length
      });
      const p = (await Promise.all(E.map((g) => {
        const A = re(g);
        return s(g, A);
      }))).filter((g) => !!g);
      return L.log("Scan finished.", {
        mountedCount: p.length,
        deferredCount: E.length - p.length
      }), p;
    },
    observe: (f) => {
      if (typeof MutationObserver > "u")
        return () => {
        };
      const v = f || document;
      L.log("Observer started.", {
        scope: v === document ? "document" : v
      });
      const E = new MutationObserver((y) => {
        y.forEach((p) => {
          p.addedNodes.forEach((g) => {
            g instanceof Element && (g.matches(te) && (L.log("Observer detected new root.", {
              target: O(g)
            }), s(g, re(g))), g.querySelectorAll(te).forEach((A) => {
              L.log("Observer detected new nested root.", {
                target: O(A)
              }), s(A, re(A));
            }));
          }), p.removedNodes.forEach((g) => {
            g instanceof Element && (e.has(g) && (L.log("Observer detected removed root.", {
              target: O(g)
            }), o(g)), g.querySelectorAll(te).forEach((A) => {
              e.has(A) && (L.log("Observer detected removed nested root.", {
                target: O(A)
              }), o(A));
            }));
          });
        });
      });
      return E.observe(v, {
        childList: !0,
        subtree: !0
      }), () => {
        E.disconnect(), L.log("Observer stopped."), r.forEach((p, g) => {
          Po(g, v) && (p(), r.delete(g));
        });
        const y = [];
        v instanceof Element && v.matches(te) && y.push(v), v.querySelectorAll(te).forEach((p) => {
          y.push(p);
        }), y.forEach((p) => {
          e.has(p) && o(p);
        });
      };
    }
  };
}
const _r = H("general", "module-hydrator");
async function Ba(e) {
  const t = e.root, r = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form")), n = e.modules ?? [], i = e.mode ?? "server-rendered", o = e.registry ?? new kr(), a = new or(), s = await Pr(n, {
    registry: o,
    setupContext: {
      formId: r?.id || t.id || "formie-modules",
      root: t,
      form: r,
      target: t,
      scope: "form",
      state: {},
      options: {},
      on: (l, d) => a.on(l, d),
      emit: async (l, d) => {
        await a.emit(l, d);
      }
    },
    matchContext: {
      root: t,
      form: r,
      mode: i
    }
  });
  return _r.log("Hydrated module manifest.", {
    moduleCount: n.length,
    instanceCount: s.length,
    mode: i
  }), {
    destroy: async () => {
      await Ho(s), a.clear();
    },
    on: (l, d) => a.on(l, d),
    emit: async (l, d) => {
      await a.emit(l, d);
    },
    registerModule: (l, d = {}) => o.register(l, d),
    unregisterModule: (l) => {
      o.unregister(l);
    },
    getRegisteredModules: () => o.getAll()
  };
}
async function Ho(e) {
  for (const t of e)
    try {
      await t.destroy();
    } catch (r) {
      console.error("[formie] Failed to destroy module instance.", r), _r.warn("Failed destroying module instance.", { error: r });
    }
}
function _e(e) {
  return e instanceof Element;
}
function Do(e) {
  return e.ok;
}
function xo(e) {
  return typeof e == "string" ? `selector "${e}"` : _e(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function No(e) {
  const t = /* @__PURE__ */ new Set(), r = [];
  for (const n of e)
    !_e(n) || t.has(n) || (t.add(n), r.push(n));
  return r;
}
function et(e) {
  return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : _e(e) ? [e] : No(e);
}
function Uo() {
  return document.readyState !== "loading" ? Promise.resolve() : new Promise((e) => {
    document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
  });
}
async function zo(e) {
  const t = et(e);
  return t.length > 0 || typeof e != "string" ? t : (await Uo(), et(e));
}
function Bo(e) {
  return typeof e == "string" ? document : _e(e) ? e.getRootNode() : document;
}
function jo(e) {
  const {
    element: t,
    observe: r,
    allowEmpty: n,
    client: i,
    onReady: o,
    onResult: a,
    onSuccess: s,
    onError: l,
    onEvent: d,
    ...h
  } = e;
  return {
    mode: "server-rendered",
    ...h
  };
}
async function zt(e, t, r, n) {
  const i = [], o = jo(e);
  for (const a of n) {
    const s = r.get(a);
    if (s) {
      i.push(s.instance);
      continue;
    }
    const l = await t.mount(a, o), d = [];
    if (e.onReady?.(l), d.push(l.on("formie:submit:result", (h) => {
      const m = h;
      e.onResult?.(m, l), Do(m) ? e.onSuccess?.(m, l) : e.onError?.(m, l);
    })), e.onEvent)
      for (const h of Xr)
        d.push(l.on(h, (m) => {
          e.onEvent?.({
            name: h,
            payload: m
          }, l);
        }));
    r.set(a, {
      instance: l,
      unsubs: d
    }), i.push(l);
  }
  return i;
}
async function ja(e) {
  const t = e.client ?? $o(), r = /* @__PURE__ */ new Map(), n = await zo(e.element);
  if (n.length === 0 && !e.allowEmpty)
    throw new Error(`Formie could not find any elements for ${xo(e.element)}.`);
  await zt(e, t, r, n);
  const i = e.observe ? t.observe(Bo(e.element)) : null;
  return {
    client: t,
    get instances() {
      return Array.from(r.values()).map(({ instance: o }) => o);
    },
    get(o) {
      const a = typeof o == "string" ? document.querySelector(o) : o;
      return a ? r.get(a)?.instance ?? t.getInstance(a) : null;
    },
    async rescan() {
      const o = et(e.element);
      return o.length === 0 ? Array.from(r.values()).map(({ instance: a }) => a) : zt(e, t, r, o);
    },
    async destroy() {
      i?.();
      const o = Array.from(r.entries());
      for (const [a, s] of o)
        s.unsubs.forEach((l) => l()), await t.unmount(a), r.delete(a);
    }
  };
}
const St = 2e3, Wa = 5e3, Ka = 5e3, Ga = 12e4;
async function At(e) {
  await new Promise((t) => {
    window.setTimeout(t, Math.max(e, 0));
  });
}
async function Ja(e, {
  timeoutMs: t = 5e3,
  intervalMs: r = 30
} = {}) {
  const n = Date.now();
  for (; Date.now() - n < t; ) {
    const i = e();
    if (i)
      return i;
    await At(r);
  }
  throw new Error("Timed out waiting for async condition.");
}
function $r(e, t) {
  let r = null;
  return (...n) => {
    r !== null && window.clearTimeout(r), r = window.setTimeout(() => {
      e(...n);
    }, Math.max(t, 0));
  };
}
function Ya(e) {
  const t = String(e || "asyncDefer").toLowerCase();
  return {
    async: t.includes("async"),
    defer: t.includes("defer")
  };
}
function Hr(e, t) {
  const r = Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`));
  for (const n of r) {
    const i = String(n.value || "").trim();
    if (i !== "")
      return i;
  }
  return "";
}
function tt(e, t) {
  return t.some((r) => Hr(e, r) !== "");
}
function Wo(e, t) {
  t.forEach((r) => {
    Array.from(e.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach((i) => {
      i.value = "";
    });
  });
}
function Dr(e, t, {
  value: r = "",
  container: n
} = {}) {
  let i = e.querySelector(`input[name="${t}"]`);
  return i || (i = document.createElement("input"), i.type = "hidden", i.name = t, (n || (e instanceof HTMLElement ? e : null))?.appendChild(i)), i.value = r, i;
}
async function xr(e, t, r) {
  if (tt(e, t))
    return !0;
  const n = Date.now() + Math.max(r, 0);
  for (; Date.now() < n; )
    if (await At(120), tt(e, t))
      return !0;
  return !1;
}
const Ko = /* @__PURE__ */ new Set([
  "handle",
  "placeholderSelector",
  "errorMessage",
  "sessionKey",
  "value"
]), Go = "[data-formie-captcha-error-container]", Jo = [
  "formie:page:navigate",
  "formie:page:navigate:after",
  "formie:submit:result"
], Yo = /* @__PURE__ */ new Set([
  "formie:page:navigate",
  "formie:page:navigate:after"
]);
function fe(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function ke(e, t) {
  return e instanceof HTMLElement && e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function rt(e) {
  if (!(e instanceof HTMLElement) || !e.isConnected || e.hidden || e.closest("[hidden]") || e.closest("[data-formie-page-hidden]") || e.closest('[aria-hidden="true"]'))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display !== "none" && t.visibility !== "hidden" && e.getClientRects().length > 0;
}
function je(e, t) {
  const r = ke(e, t);
  return r.find((n) => rt(n)) || r[0] || null;
}
function Zo(e) {
  e.innerHTML = "";
  const t = document.createElement("div");
  return e.appendChild(t), t;
}
function nt(e) {
  e?.querySelector(Go)?.remove();
}
function Qo(e, t, r) {
  if (!e)
    return;
  nt(e);
  const n = document.createElement("div");
  n.setAttribute("data-formie-captcha-error-container", ""), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true"), q(n, r || e, "fieldErrors");
  const i = document.createElement("div");
  i.setAttribute("data-formie-captcha-error", ""), i.setAttribute("role", "alert"), q(i, r || e, "fieldError"), i.textContent = t, n.appendChild(i), e.appendChild(n);
}
function Xo(e) {
  const t = e instanceof CustomEvent ? e.detail : null;
  return !t || typeof t != "object" ? null : t;
}
function ea(e, t) {
  if (!e?.captchas || typeof e.captchas != "object")
    return null;
  const r = e.captchas[t];
  return !r || typeof r != "object" ? null : r;
}
function ta(e, t, r, n) {
  const i = /* @__PURE__ */ new Set(), o = () => {
    const d = ke(e, t), h = new Set(d.filter((m) => rt(m)));
    d.forEach((m) => {
      h.has(m) && !i.has(m) && (i.add(m), r(m));
    }), Array.from(i).forEach((m) => {
      h.has(m) || (i.delete(m), n(m));
    });
  }, a = $r(o, 20), s = new MutationObserver(() => {
    a();
  });
  s.observe(e, {
    childList: !0,
    subtree: !0,
    attributes: !0,
    attributeFilter: ["class", "style", "hidden", "aria-hidden", "data-formie-page-hidden"]
  });
  const l = [
    fe(window, "resize", () => {
      a();
    }),
    ...Jo.map((d) => fe(e, d, () => {
      if (Yo.has(d)) {
        o();
        return;
      }
      a();
    }))
  ];
  return o(), {
    cleanup: () => {
      s.disconnect(), l.forEach((d) => {
        d();
      }), Array.from(i).forEach((d) => {
        n(d);
      }), i.clear();
    },
    reconcile: a,
    reconcileImmediate: o,
    getVisible: () => ke(e, t).filter((d) => rt(d))
  };
}
function ra(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function na(e, t, {
  defaultPlaceholderSelector: r,
  defaultTokenFieldNames: n = [],
  defaultWaitForValueMs: i = St
}) {
  const o = t || {}, a = Object.entries(o).reduce((c, [b, w]) => (Ko.has(b) || (c[b] = w), c), {}), s = n.map(String).filter(Boolean), l = Number(i), d = typeof o.placeholderSelector == "string" && o.placeholderSelector.trim() !== "" ? o.placeholderSelector.trim() : r, h = typeof o.errorMessage == "string" && o.errorMessage.trim() !== "" ? o.errorMessage.trim() : x("Captcha challenge must be completed."), m = typeof o.sessionKey == "string" && o.sessionKey.trim() !== "" ? o.sessionKey.trim() : null, u = typeof o.value == "string" ? o.value : null;
  return {
    handle: ra(e, o),
    ui: {
      placeholderSelector: d,
      errorMessage: h
    },
    transport: {
      tokenFieldNames: s,
      waitForValueMs: Number.isFinite(l) ? l : i,
      sessionKey: m,
      value: u
    },
    provider: a
  };
}
function ia(e, t) {
  const r = e.form || e.root, n = t.ui.placeholderSelector, i = t.handle;
  return {
    form: e.form,
    root: e.root,
    placeholder: {
      query: () => ke(e.root, n),
      getPrimary: () => je(e.root, n),
      observe: (o, a) => ta(e.root, n, o, a),
      createContainer: (o) => Zo(o),
      clear: (o) => {
        o && (nt(o), o.innerHTML = "");
      }
    },
    errors: {
      getDefaultMessage: () => t.ui.errorMessage,
      show: (o, a) => {
        Qo(a || je(e.root, n), o || t.ui.errorMessage, e.form || e.root);
      },
      clear: (o) => {
        nt(o || je(e.root, n));
      }
    },
    tokens: {
      names: t.transport.tokenFieldNames,
      has: (o = t.transport.tokenFieldNames, a = r) => tt(a, o),
      read: (o = t.transport.tokenFieldNames[0], a = r) => o ? Hr(a, o) : "",
      write: (o, {
        names: a = t.transport.tokenFieldNames,
        root: s = r,
        container: l = e.form
      } = {}) => {
        a.forEach((d) => {
          Dr(s, d, {
            value: o,
            container: l
          });
        });
      },
      clear: (o = t.transport.tokenFieldNames, a = r) => {
        Wo(a, o);
      },
      wait: (o = t.transport.waitForValueMs, a = t.transport.tokenFieldNames, s = r) => xr(s, a, o)
    },
    refresh: {
      providerHandle: i,
      onTokensRefreshed: (o) => {
        const a = ["formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"].map((s) => fe(e.root, s, (l) => {
          const d = Xo(l), h = ea(d, i);
          h && o(h);
        }));
        return () => {
          a.forEach((s) => {
            s();
          });
        };
      }
    },
    events: {
      onRoot: (o, a) => fe(e.root, o, a),
      onForm: (o, a) => e.form ? fe(e.form, o, a) : () => {
      }
    }
  };
}
const Y = H("captchas");
function Nr({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = St,
  setup: i
}) {
  return {
    id: e,
    kind: "captcha",
    match: () => !0,
    setup: async (o) => {
      const a = na(e, o.options || {}, {
        defaultPlaceholderSelector: t,
        defaultTokenFieldNames: r,
        defaultWaitForValueMs: n
      });
      Y.log("Setup module.", {
        moduleId: e,
        placeholderSelector: a.ui.placeholderSelector,
        tokenFieldNames: a.transport.tokenFieldNames
      });
      const s = ia(o, a);
      return i({
        ...o,
        options: a,
        services: s
      });
    }
  };
}
function oa({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = St
}) {
  return Nr({
    id: e,
    defaultPlaceholderSelector: t,
    defaultTokenFieldNames: r,
    defaultWaitForValueMs: n,
    setup: async ({ services: i, options: o, root: a }) => {
      const s = [];
      let l = i.placeholder.getPrimary(), d = o.transport.sessionKey, h = o.transport.value || "";
      const m = (c) => {
        !c || !d || (c.innerHTML = "", Dr(c, d, {
          value: h,
          container: c
        }));
      }, u = i.placeholder.observe(
        (c) => {
          l = c, Y.log("Passive placeholder visible.", {
            moduleId: e
          }), m(c);
        },
        (c) => {
          l === c && (l = i.placeholder.getPrimary()), c.innerHTML = "";
        }
      );
      return s.push(u.cleanup), m(l), s.push(i.refresh.onTokensRefreshed((c) => {
        d = typeof c.sessionKey == "string" && c.sessionKey.trim() !== "" ? c.sessionKey.trim() : d, h = typeof c.value == "string" ? c.value : "";
        const b = i.placeholder.getPrimary() || l;
        l = b, m(b);
      })), {
        destroy: () => {
          s.forEach((c) => {
            c();
          });
        },
        onBeforeStage: async (c) => {
          if (c.stage !== "screen" || c.action !== "submit")
            return;
          const b = d ? [d] : o.transport.tokenFieldNames;
          if (b.length === 0)
            return;
          if (!await xr(a, b, o.transport.waitForValueMs)) {
            const f = i.errors.getDefaultMessage();
            i.errors.show(f, l), Y.warn("Passive captcha missing token.", {
              moduleId: e,
              tokenFieldNames: b
            }), c.abort(f);
          }
        }
      };
    }
  });
}
function aa(e) {
  return Nr({
    id: e.id,
    defaultPlaceholderSelector: e.defaultPlaceholderSelector,
    defaultTokenFieldNames: e.defaultTokenFieldNames,
    setup: async (t) => {
      const r = [], n = /* @__PURE__ */ new Map(), i = /* @__PURE__ */ new Map();
      let o = t.services.placeholder.getPrimary(), a = !1, s = null;
      const l = async () => (s || (Y.log("Loading captcha provider API.", {
        moduleId: e.id
      }), s = e.load(t)), s), d = async (c) => {
        const b = n.get(c);
        if (t.services.errors.clear(c), !b) {
          c.innerHTML = "";
          return;
        }
        const w = await l();
        e.unmount && await e.unmount({
          api: w,
          widget: b,
          placeholder: c,
          services: t.services,
          options: t.options,
          provider: t.options.provider
        }), n.delete(c), c.innerHTML = "", t.services.tokens.clear(), Y.log("Unmounted captcha placeholder widget.", {
          moduleId: e.id
        }), o === c && (o = t.services.placeholder.getPrimary());
      }, h = async (c) => {
        if (a || n.has(c) || i.has(c))
          return;
        const b = (async () => {
          const w = await l();
          if (a || n.has(c))
            return;
          const f = t.services.placeholder.createContainer(c), v = await e.mount({
            api: w,
            placeholder: c,
            container: f,
            services: t.services,
            options: t.options,
            provider: t.options.provider
          });
          n.set(c, v), o = c, Y.log("Mounted captcha placeholder widget.", {
            moduleId: e.id
          });
        })().finally(() => {
          i.delete(c);
        });
        i.set(c, b), await b;
      }, m = t.services.placeholder.observe(
        (c) => {
          o = c, h(c);
        },
        (c) => {
          d(c);
        }
      );
      r.push(m.cleanup);
      const u = async (c) => {
        const w = m.getVisible();
        if (e.reset) {
          const f = await l();
          for (const v of w) {
            const E = n.get(v);
            if (!E) {
              await h(v);
              continue;
            }
            await e.reset({
              api: f,
              widget: E,
              placeholder: v,
              services: t.services,
              options: t.options,
              provider: t.options.provider,
              reason: c
            }), t.services.tokens.clear(), t.services.errors.clear(v);
          }
          m.reconcile();
          return;
        }
        for (const f of Array.from(n.keys()))
          await d(f);
        for (const f of w)
          await h(f);
        m.reconcile();
      };
      return r.push(t.services.events.onRoot("formie:submit:result", (c) => {
        const b = c instanceof CustomEvent ? c.detail : null;
        b?.stage !== "validate" && (b?.ok === !1 && b?.stage === "screen" || b?.ok !== !0 && u("submit-result"));
      })), t.form && r.push(t.services.events.onForm(Ge("reset"), () => {
        o = t.services.placeholder.getPrimary() || o, window.setTimeout(() => {
          u("reset-state");
        }, 0);
      })), {
        destroy: async () => {
          a = !0, r.forEach((c) => {
            c();
          });
          for (const c of Array.from(n.keys()))
            await d(c);
        },
        onBeforeStage: async (c) => {
          if (c.stage !== "screen" || c.action !== "submit")
            return;
          m.reconcileImmediate();
          const b = m.getVisible();
          if (b.length === 0)
            return;
          let w = b.find((E) => E === o) || b[0];
          await h(w), w = o || w, t.services.errors.clear(w);
          const f = n.get(w);
          if (!f) {
            const E = t.services.errors.getDefaultMessage();
            t.services.errors.show(E, w), Y.warn("Captcha widget unavailable at screen stage.", {
              moduleId: e.id
            }), c.abort(E);
            return;
          }
          const v = await l();
          await e.screen({
            api: v,
            widget: f,
            placeholder: w,
            services: t.services,
            options: t.options,
            provider: t.options.provider,
            stageCtx: c
          });
        }
      };
    }
  });
}
const Za = aa, Qa = oa, Bt = 2500, sa = {
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
function la(e) {
  return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function ua(e) {
  return `fields[${la(e)}]`;
}
function ca(e, t) {
  const r = ua(t), n = Array.from(e.querySelectorAll(`[name="${r}"]`)), i = Array.from(e.querySelectorAll(`[name="${r}[]"]`));
  return (i.length ? i : n).filter((o) => o instanceof HTMLElement);
}
function jt(e, t) {
  const r = ca(e, t);
  for (const n of r) {
    const o = n.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
    if (o)
      return o;
  }
  return "";
}
function We(e) {
  let t = e.replace(/[^\d.,-]/g, "");
  const r = t.includes(","), n = t.includes(".");
  if (r && n)
    t.lastIndexOf(",") > t.lastIndexOf(".") ? t = t.replace(/\./g, "").replace(",", ".") : t = t.replace(/,/g, "");
  else if (r && !n) {
    const i = t.split(",");
    i.length === 2 && i[1].length === 3 && /^\d+$/.test(i[0]) && /^\d+$/.test(i[1]) ? t = i[0] + i[1] : t = t.replace(",", ".");
  } else
    t = t.replace(/,/g, "");
  return parseFloat(t);
}
function da(e) {
  return e.replace(/^\{field:/, "").replace(/^\{/, "").replace(/\}$/, "").trim();
}
function ge(e) {
  return da(e).replace(/\]/g, "").split("[").join(".").replace(/\.+/g, ".").replace(/^\./, "").replace(/\.$/, "");
}
function Ur(e) {
  const r = ge(e).split(".").filter(Boolean);
  if (!r.length)
    return "";
  const [n, ...i] = r;
  return `fields[${n}]${i.map((o) => `[${o}]`).join("")}`;
}
function fa(e) {
  const r = String(e || "").trim().match(/^fields\[([^\]]+)\](.*)$/);
  if (!r)
    return "";
  const n = r[1] || "", i = r[2] || "", o = Array.from(i.matchAll(/\[([^\]]+)\]/g)).map((a) => a[1] || "").filter(Boolean);
  return [n, ...o].join(".");
}
function ma(e) {
  const t = e.split(";").map((a) => a.trim()).filter(Boolean);
  if (!t.length)
    return {
      source: "",
      transforms: []
    };
  const [r, ...n] = t, i = [];
  let o = null;
  return n.forEach((a) => {
    if (a.startsWith("transform=")) {
      o && i.push(o), o = {
        id: decodeURIComponent(a.slice(10) || "").trim(),
        params: {}
      };
      return;
    }
    if (!o || !a.includes("="))
      return;
    const [s, l] = a.split("=", 2), d = (s || "").trim();
    !d || d === "transform" || (o.params[d] = decodeURIComponent(l || "").trim());
  }), o && i.push(o), {
    source: r || "",
    transforms: i
  };
}
function zr(e) {
  const t = String(e || "").trim();
  if (!t)
    return {
      raw: t,
      target: "",
      key: "",
      selector: "",
      defaultValue: "",
      transforms: [],
      isToken: !1,
      isValid: !1
    };
  const r = t.match(/^\{([a-zA-Z]+)(?::(.*))?\}$/);
  if (!r)
    return {
      raw: t,
      target: "",
      key: ge(t),
      selector: "",
      defaultValue: "",
      transforms: [],
      isToken: !1,
      isValid: !0
    };
  const n = (r[1] || "").trim().toLowerCase(), i = (r[2] || "").trim(), [o, a = ""] = i.split("|", 2), { source: s, transforms: l } = ma(o || "");
  if (n !== "field")
    return {
      raw: t,
      target: "",
      key: "",
      selector: "",
      defaultValue: a.trim(),
      transforms: l,
      isToken: !0,
      isValid: !1
    };
  const d = s.indexOf(":"), h = d === -1 ? s : s.slice(0, d), m = d === -1 ? "" : s.slice(d + 1), u = ge(h);
  return {
    raw: t,
    target: "field",
    key: u,
    selector: m.trim(),
    defaultValue: a.trim(),
    transforms: l,
    isToken: !0,
    isValid: u !== ""
  };
}
function pa(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement || e instanceof HTMLSelectElement;
}
function ga(e, t, r) {
  const n = t.trim(), i = String(r.name || "").trim();
  if (!n || !i)
    return;
  const o = e.get(n) || {
    key: n,
    names: [],
    inputs: []
  };
  o.names.includes(i) || o.names.push(i), o.inputs.includes(r) || o.inputs.push(r), e.set(n, o);
}
function ha(e) {
  const t = /* @__PURE__ */ new Map();
  return Array.from(e.querySelectorAll("[name]")).filter((n) => pa(n)).forEach((n) => {
    const i = fa(n.name);
    i && ga(t, i, n);
  }), t;
}
function ba(e) {
  if (!e.length)
    return "";
  const t = e[0];
  if (t instanceof HTMLSelectElement && t.multiple)
    return Array.from(t.selectedOptions).map((n) => n.value);
  if (e.some((n) => n instanceof HTMLInputElement && (n.type === "checkbox" || n.type === "radio"))) {
    const n = e.flatMap((i) => !(i instanceof HTMLInputElement) || !i.checked ? [] : [i.value]);
    return n.length > 1 ? n : n[0] || "";
  }
  return t.value;
}
function Br(e, t) {
  return e.get(ge(t)) || null;
}
function ya(e, t) {
  const r = zr(e), n = r.key, i = Br(t, n);
  if (!i)
    return {
      key: n,
      value: r.defaultValue,
      found: !1
    };
  const o = ba(i.inputs);
  return {
    key: n,
    value: o === "" && r.defaultValue !== "" ? r.defaultValue : o,
    found: !0
  };
}
function Xa(e, t, r) {
  const n = zr(e), i = n.key;
  if (!i)
    return {
      key: i,
      value: n.defaultValue,
      found: !1
    };
  const o = r ? Br(r, i) : null, s = (o?.names?.length ? o.names : [Ur(i)]).flatMap((l) => {
    const d = t.getAll(l).map((h) => String(h ?? ""));
    return d.length ? d : t.getAll(`${l}[]`).map((h) => String(h ?? ""));
  }).filter((l) => l !== "");
  return s.length ? {
    key: i,
    value: s.length > 1 ? s : s[0],
    found: !0
  } : {
    key: i,
    value: n.defaultValue,
    found: !1
  };
}
function jr(e, t) {
  const r = t.replace(/"/g, '\\"');
  return e.querySelector(`input[name$="[${r}]"]`) || e.querySelector(`input[name$="${r}"]`);
}
function Me(e, t) {
  const r = t.find((n) => {
    const i = jr(e, n);
    return !i || String(i.value || "").trim() === "";
  });
  return {
    ok: !r,
    missingSuffix: r
  };
}
async function Wr(e, t, r) {
  const n = Me(e, t);
  if (n.ok)
    return n;
  const i = Date.now() + Math.max(r, 0);
  for (; Date.now() < i; ) {
    await At(120);
    const o = Me(e, t);
    if (o.ok)
      return o;
  }
  return Me(e, t);
}
const va = /* @__PURE__ */ new Set([
  "handle",
  "requiredInputSuffixes",
  "waitForValueMs",
  "errorMessage"
]), Wt = "[data-payment-success]", Kt = "[data-payment-error]";
function Ea(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Sa(e, t, r) {
  const n = t || {}, i = Object.entries(n).reduce((l, [d, h]) => (va.has(d) || (l[d] = h), l), {}), o = Array.isArray(n.requiredInputSuffixes) ? n.requiredInputSuffixes.map(String).filter(Boolean) : r.defaultRequiredInputSuffixes || [], a = Number(n.waitForValueMs ?? r.defaultWaitForValueMs ?? Bt), s = typeof n.errorMessage == "string" && n.errorMessage.trim() !== "" ? n.errorMessage.trim() : "Payment authorization is incomplete.";
  return {
    handle: Ea(e, n),
    transport: {
      requiredInputSuffixes: o,
      waitForValueMs: Number.isFinite(a) ? a : Bt,
      errorMessage: s
    },
    provider: i
  };
}
function Gt(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function Aa(e, t) {
  const r = e.target, n = e.form, i = e.root, o = n || i, a = t.transport.requiredInputSuffixes, s = () => ha(n || i), l = (y) => {
    const g = ya(y, s()).value;
    return Array.isArray(g) ? g[0] || "" : String(g || "");
  };
  return {
    root: i,
    form: n,
    field: r,
    updateInputs: (y, p) => {
      const g = Array.isArray(y) ? y : [y];
      for (const A of g) {
        const C = jr(o, A) ?? r.querySelector(`input[name*="${A}"]`);
        C && (C.value = p);
      }
    },
    addError: (y) => {
      const p = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = p.querySelector(Kt);
      g && g.remove();
      const A = document.createElement("div");
      A.setAttribute("data-payment-error", ""), A.textContent = y, q(A, n || i, "fieldError"), p.appendChild(A);
    },
    removeError: () => {
      r.querySelector(Kt)?.remove();
    },
    addSuccess: (y) => {
      const p = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = p.querySelector(Wt);
      g && g.remove();
      const A = document.createElement("div");
      A.setAttribute("data-payment-success", ""), A.textContent = y, q(A, n || i, "successMessage"), p.appendChild(A);
    },
    removeSuccess: () => {
      r.querySelector(Wt)?.remove();
    },
    hasToken: () => Me(o, a).ok,
    waitForToken: (y = t.transport.waitForValueMs) => Wr(o, a, y).then((p) => p.ok),
    getFieldValue: (y, p = "string") => {
      const g = l(y);
      return p === "float" || p === "int" || p === "number" ? We(g) : g;
    },
    resolveAmount: (y) => {
      const p = n || i, A = String(y.type || "").toLowerCase() === "dynamic" && typeof y.variable == "string" && y.variable.trim() !== "", C = y.value ?? (A ? y.variable : y.fixed), T = String(C ?? "").trim(), R = typeof C == "number" ? C : We(T);
      if (Number.isFinite(R) && R > 0)
        return { ok: !0, value: R };
      if (T !== "") {
        const F = l(T), S = We(F);
        if (Number.isFinite(S) && S > 0)
          return { ok: !0, value: S };
        const V = jt(p, T);
        if (!F)
          return {
            ok: !1,
            error: V ? x('Provide a value for "{label}" to proceed.', { label: V }) : x("Provide a payment amount to proceed.")
          };
      }
      return {
        ok: !1,
        error: x("Payment amount must be greater than 0.")
      };
    },
    resolveCurrency: (y) => {
      const p = n || i, A = String(y.type || "").toLowerCase() === "dynamic" && typeof y.variable == "string" && y.variable.trim() !== "", C = y.value ?? (A ? y.variable : y.fixed ?? y.defaultCurrency ?? ""), T = String(C ?? "").trim(), R = T.toUpperCase();
      if (/^[A-Z]{3}$/.test(R) && !A)
        return { ok: !0, value: R };
      if (T !== "") {
        const F = String(l(T) || "").trim(), S = F.toUpperCase();
        if (/^[A-Z]{3}$/.test(S))
          return { ok: !0, value: S };
        const V = jt(p, T);
        if (!F)
          return {
            ok: !1,
            error: V ? x('Provide a value for "{label}" to proceed.', { label: V }) : x("Provide a payment currency to proceed.")
          };
      }
      return {
        ok: !1,
        error: x("Payment currency must be a valid 3-letter code.")
      };
    },
    watchFieldValueChanges: (y, p, g = 600) => {
      const A = n || i, C = y.map((V) => String(V || "").trim()).filter(Boolean);
      if (C.length === 0)
        return () => {
        };
      const T = s(), R = /* @__PURE__ */ new Set();
      C.forEach((V) => {
        const N = ge(V), K = T.get(N);
        if (K?.names?.length) {
          K.names.forEach((he) => {
            R.add(he);
          });
          return;
        }
        const Q = Ur(N);
        Q && (R.add(Q), R.add(`${Q}[]`));
      });
      const F = $r(() => {
        p();
      }, g), S = (V) => {
        const K = V.target?.name || "";
        !K || !R.has(K) || F();
      };
      return A.addEventListener("input", S), A.addEventListener("change", S), () => {
        A.removeEventListener("input", S), A.removeEventListener("change", S);
      };
    },
    triggerSubmit: () => {
      n && n.setAttribute("data-formie-internal-resubmit", "true"), n && typeof n.requestSubmit == "function" ? n.requestSubmit() : n && n.submit();
    },
    releaseSubmitLoading: () => {
      n && (n.removeAttribute("data-formie-internal-resubmit"), Re(n));
    },
    getBillingData: (y) => {
      const p = {};
      if (!y || typeof y != "object")
        return { billing_details: p };
      if (y.billingName) {
        const g = l(y.billingName);
        g && (p.name = g);
      }
      if (y.billingEmail) {
        const g = l(y.billingEmail);
        g && (p.email = g);
      }
      if (y.billingAddress) {
        const g = y.billingAddress, A = {}, C = l(`${g}.address1`), T = l(`${g}.address2`), R = l(`${g}.address3`), F = l(`${g}.city`), S = l(`${g}.zip`), V = l(`${g}.state`), N = l(`${g}.country`);
        C && (A.line1 = C), T && (A.line2 = T), R && (A.line3 = R), F && (A.city = F), S && (A.postal_code = S), V && (A.state = V), N && (A.country = N), Object.keys(A).length && (p.address = A);
      }
      return { billing_details: p };
    },
    events: {
      onForm: (y, p) => n ? Gt(n, y, p) : () => {
      },
      onRoot: (y, p) => Gt(i, y, p)
    }
  };
}
const U = H("payments");
function Jt(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function wa(e) {
  const t = e.defaultRequiredInputSuffixes ?? sa[e.id] ?? [];
  return {
    id: e.id,
    kind: "payment",
    match: (r) => !!(r.target.querySelector('[data-formie-field-type="payment"]') || r.target.closest('[data-formie-field-type="payment"]') || r.target.getAttribute?.("data-formie-field-type") === "payment"),
    setup: async (r) => {
      const n = r.target, i = n.__formiePaymentModuleRegistry || {};
      n.__formiePaymentModuleRegistry = i;
      const o = i[e.id];
      if (o?.destroy) {
        U.warn("Found stale payment module instance; destroying previous.", {
          moduleId: e.id
        });
        try {
          await o.destroy();
        } catch {
        }
      }
      const a = Sa(
        e.id,
        r.options || {},
        {
          defaultRequiredInputSuffixes: t
        }
      ), s = Aa(r, a), l = {
        ...r,
        options: a,
        services: s
      }, d = [];
      let h = null, m = null, u = null, c = null;
      const b = async () => (h || (U.log("Loading payment provider API.", { moduleId: e.id }), h = e.load(l)), h), w = async () => {
        if (!e.mount || m || !Jt(r.target))
          return;
        const E = await b();
        try {
          m = await e.mount({
            api: E,
            field: r.target,
            services: s,
            options: a,
            provider: a.provider
          }), U.log("Payment widget mounted.", {
            moduleId: e.id,
            handle: a.handle
          });
        } catch {
          U.warn("Payment widget mount failed.", {
            moduleId: e.id,
            handle: a.handle
          });
        }
      };
      if (d.push(r.on("formie:submit:before", () => {
        s.removeError(), s.removeSuccess();
      })), e.setup) {
        const E = r.root || r.form || r.target;
        u = await e.setup({ ...l, root: E }), u.destroy && d.push(u.destroy);
      }
      e.mount && Jt(r.target) && await w(), ["formie:page:navigate:after", "formie:submit:result"].forEach((E) => {
        const y = () => {
          w();
        };
        r.root.addEventListener(E, y), d.push(() => {
          r.root.removeEventListener(E, y);
        });
      });
      const v = async () => {
        if (U.log("Destroying payment module.", {
          moduleId: e.id,
          handle: a.handle
        }), d.forEach((E) => E()), m && e.unmount) {
          const E = await b();
          await e.unmount({
            api: E,
            widget: m,
            field: r.target,
            services: s,
            options: a,
            provider: a.provider
          }), U.log("Payment widget unmounted.", {
            moduleId: e.id,
            handle: a.handle
          });
        }
        i[e.id]?.destroy === v && delete i[e.id], U.log("Payment module destroy complete.", {
          moduleId: e.id,
          handle: a.handle
        });
      };
      return i[e.id] = { destroy: v }, {
        destroy: v,
        onBeforeStage: async (E) => {
          if (u?.onBeforeStage) {
            await u.onBeforeStage(E);
            return;
          }
          if (E.stage !== "authorize" || E.action !== "submit" || r.target.closest("[data-formie-page]")?.hasAttribute("data-formie-page-hidden"))
            return;
          await w();
          const g = await b();
          if (e.onBeforeAuthorize) {
            c || (c = (async () => e.onBeforeAuthorize({
              api: g,
              widget: m,
              field: r.target,
              services: s,
              options: a,
              provider: a.provider,
              stageCtx: E
            }))().finally(() => {
              c = null;
            }));
            const T = await c;
            if (U.log("onBeforeAuthorize resolved.", {
              moduleId: e.id,
              handle: a.handle,
              ok: T
            }), !T) {
              E.abort(a.transport.errorMessage);
              return;
            }
            return;
          }
          if (a.transport.requiredInputSuffixes.length === 0)
            return;
          const A = r.form || r.root, C = await Wr(
            A,
            a.transport.requiredInputSuffixes,
            a.transport.waitForValueMs
          );
          C.ok || (U.warn("Required payment input(s) missing.", {
            moduleId: e.id,
            handle: a.handle,
            missingSuffix: C.missingSuffix
          }), E.abort(a.transport.errorMessage));
        },
        onAfterStage: async (E, y) => {
          if (!(E.stage !== "dispatch" || !e.onAfterSubmit || !(await e.onAfterSubmit({
            field: r.target,
            services: s,
            options: a,
            provider: a.provider,
            result: y
          }))?.remount || !e.mount)) {
            if (m && e.unmount) {
              const g = await b();
              await e.unmount({
                api: g,
                widget: m,
                field: r.target,
                services: s,
                options: a,
                provider: a.provider
              });
            }
            m = null, await w();
          }
        }
      };
    }
  };
}
const es = wa, Ta = "[data-formie-address-autocomplete-input]", Yt = "[data-formie-address-location]", B = {
  autoComplete: "[data-formie-address-autocomplete-input]",
  address1: "[data-formie-address-line1-input]",
  address2: "[data-formie-address-line2-input]",
  address3: "[data-formie-address-line3-input]",
  city: "[data-formie-address-city-input]",
  state: "[data-formie-address-state-input]",
  zip: "[data-formie-address-zip-input]",
  country: "[data-formie-address-country-input]"
}, j = {
  autoComplete: "[data-formie-address-autocomplete-input]",
  address1: "[data-address1]",
  address2: "[data-address2]",
  address3: "[data-address3]",
  city: "[data-city]",
  state: "[data-state]",
  zip: "[data-zip]",
  country: "[data-country]"
}, Ca = {
  autoComplete: [
    B.autoComplete,
    j.autoComplete
  ],
  address1: [
    B.address1,
    j.address1
  ],
  address2: [
    B.address2,
    j.address2
  ],
  address3: [
    B.address3,
    j.address3
  ],
  city: [
    B.city,
    j.city
  ],
  state: [
    B.state,
    j.state
  ],
  zip: [
    B.zip,
    j.zip
  ],
  country: [
    B.country,
    j.country
  ]
};
function Ma(e, t) {
  for (const r of Ca[t]) {
    const n = e.querySelector(r);
    if (n instanceof HTMLInputElement || n instanceof HTMLSelectElement)
      return n;
  }
  return null;
}
const La = /* @__PURE__ */ new Set(["handle"]);
function Ia(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Ra(e, t) {
  const r = t || {}, n = Object.entries(r).reduce((i, [o, a]) => (La.has(o) || (i[o] = a), i), {});
  return {
    handle: Ia(e, r),
    provider: n
  };
}
function Fa(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function ka(e) {
  const t = e.target, r = e.form, n = e.root, i = Ta;
  return {
    root: n,
    field: t,
    form: r,
    input: {
      getAutocomplete: () => t.querySelector(i),
      setValue: (o, a, s) => {
        const l = Ma(t, o);
        if (!l)
          return;
        const d = a || s || "";
        l.value !== d && (l.value = d, l.dispatchEvent(new Event("input", { bubbles: !0 })), l.dispatchEvent(new Event("change", { bubbles: !0 })));
      }
    },
    location: {
      getButton: () => t.querySelector(Yt),
      onUseLocation: (o) => {
        const a = t.querySelector(Yt);
        if (!a)
          return () => {
          };
        const s = (l) => {
          l.preventDefault(), navigator.geolocation && navigator.geolocation.getCurrentPosition(
            o,
            () => {
            },
            { enableHighAccuracy: !0 }
          );
        };
        return a.addEventListener("click", s), () => {
          a.removeEventListener("click", s);
        };
      }
    },
    events: {
      onField: (o, a) => Fa(t, o, a)
    }
  };
}
const ne = H("address");
function Zt(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function qa(e) {
  return {
    id: e.id,
    kind: "address",
    match: (t) => !!t.target.querySelector("[data-formie-address-autocomplete-input]"),
    setup: async (t) => {
      const r = Ra(e.id, t.options || {}), n = ka(t);
      ne.log("Setup module.", {
        moduleId: e.id
      });
      const i = {
        ...t,
        options: r,
        services: n
      }, o = [];
      let a = null, s = null;
      if (!n.input.getAutocomplete())
        return console.warn(
          `[formie] Address module "${e.id}" skipped: no autocomplete input found in target. Ensure the Address field has the Auto-Complete subfield enabled.`
        ), ne.warn("Autocomplete input missing; skipping module.", {
          moduleId: e.id
        }), {
          destroy: () => {
          }
        };
      const d = async () => (a || (ne.log("Loading provider API.", {
        moduleId: e.id
      }), a = e.load(i)), a), h = async () => {
        if (s || !Zt(t.target))
          return;
        const c = await d();
        s = await e.mount({
          api: c,
          field: t.target,
          services: n,
          options: r,
          provider: r.provider
        }), ne.log("Widget mounted.", {
          moduleId: e.id
        });
      };
      Zt(t.target) && await h(), ["formie:page:navigate:after", "formie:submit:result"].forEach((c) => {
        const b = () => {
          h();
        };
        t.root.addEventListener(c, b), o.push(() => {
          t.root.removeEventListener(c, b);
        });
      });
      const u = n.location.onUseLocation((c) => {
        e.onCurrentLocation && (async () => {
          if (await h(), !s)
            return;
          const b = await d();
          await e.onCurrentLocation?.(c, {
            api: b,
            widget: s,
            field: t.target,
            services: n,
            options: r,
            provider: r.provider
          });
        })();
      });
      return u && o.push(u), {
        destroy: async () => {
          if (ne.log("Destroying module.", {
            moduleId: e.id
          }), o.forEach((c) => c()), s && e.unmount) {
            const c = await d();
            await e.unmount({
              api: c,
              widget: s,
              field: t.target,
              services: n,
              options: r,
              provider: r.provider
            }), ne.log("Widget unmounted.", {
              moduleId: e.id
            });
          }
        }
      };
    }
  };
}
const ts = qa;
export {
  Ke as $,
  B as A,
  ae as B,
  Wa as C,
  si as D,
  Pa as E,
  Xr as F,
  sn as G,
  ln as H,
  $o as I,
  cn as J,
  dn as K,
  Zr as L,
  kr as M,
  ja as N,
  pr as O,
  tn as P,
  en as Q,
  Ba as R,
  fa as S,
  er as T,
  Ua as U,
  Va as V,
  zr as W,
  Xa as X,
  Qr as Y,
  Da as Z,
  Na as _,
  Za as a,
  za as a0,
  Ya as b,
  Ga as c,
  ts as d,
  Qa as e,
  Ma as f,
  _a as g,
  Ka as h,
  Ur as i,
  H as j,
  ha as k,
  Oa as l,
  $a as m,
  ge as n,
  Ge as o,
  qe as p,
  $r as q,
  ya as r,
  At as s,
  ie as t,
  xa as u,
  x as v,
  Ja as w,
  es as x,
  Ha as y,
  q as z
};
