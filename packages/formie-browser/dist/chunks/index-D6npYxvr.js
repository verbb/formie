const Dr = [
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
], oa = [
  { legacyEvent: "formieValidatorInitialized", canonicalEvent: "formie:validator:ready", disposition: "safe" },
  { legacyEvent: "formieValidatorDestroyed", canonicalEvent: "formie:validator:destroy", disposition: "safe" },
  { legacyEvent: "formieValidatorShowError", canonicalEvent: "formie:validator:show-error", disposition: "safe" },
  { legacyEvent: "formieValidatorClearError", canonicalEvent: "formie:validator:clear-error", disposition: "safe" }
];
function xr(e) {
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
const Nr = [
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
function Be(e) {
  return e;
}
function aa(e) {
  return e;
}
function sa(e, t) {
  return `formie:field:${e}:${t}`;
}
function pe(e) {
  return `formie:validator:${e}`;
}
function la(e, t) {
  return `formie:address:${e}:${t}`;
}
function ua(e) {
  return `formie:file-upload:${e}`;
}
function ca(e, t) {
  return `formie:payment:${e}:${t}`;
}
function je(e) {
  return `formie:state:${e}`;
}
function Ur(e, t) {
  return `formie:module:${e}:${t}`;
}
function Br(e) {
  return `formie:module:${e}`;
}
function jr(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function zr(e, t) {
  if (e.canonicalEvent !== "formie:submit:result")
    return !0;
  const r = t;
  return e.legacyEvent === "onAfterFormieSubmit" ? !!r?.ok : e.legacyEvent === "onFormieSubmitError" ? r?.ok === !1 : !0;
}
function Wr(e, t) {
  const r = t && typeof t == "object" ? t : {}, n = typeof r.pageId == "string" ? r.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]")), o = i.findIndex((a) => a.getAttribute("data-formie-page-id") === n);
  return {
    data: {
      nextPageId: n,
      nextPageIndex: o,
      totalPages: i.length
    }
  };
}
function Kr(e, t, r, n, i) {
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
  } : e.legacyEvent === "onFormiePageToggle" ? Wr(n, t) : t;
}
function Gr({
  target: e,
  form: t,
  instance: r,
  options: n,
  unbinds: i
}) {
  n.legacyDomEvents && Dr.forEach((o) => {
    const a = (s) => {
      if (!(s instanceof CustomEvent) || !zr(o, s.detail))
        return;
      const l = o.target === "document" ? document : t;
      jr(l, o.legacyEvent, Kr(o, s.detail, e, t, r));
    };
    e.addEventListener(Be(o.canonicalEvent), a), i.push(() => {
      e.removeEventListener(Be(o.canonicalEvent), a);
    });
  });
}
function ge(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function Oe(e, t) {
  return !!e && typeof e == "object" && e.validator === t;
}
function Jr({
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
  ge(document, "formieValidatorInitialized", l);
  const d = (u) => {
    !(u instanceof CustomEvent) || !Oe(u.detail, o) || ge(document, "formieValidatorDestroyed", {
      ...l,
      ...u.detail
    });
  }, p = (u) => {
    !(u instanceof CustomEvent) || !Oe(u.detail, o) || !(u.target instanceof Element) || t.contains(u.target) && ge(u.target, "formieValidatorShowError", {
      ...u.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  }, h = (u) => {
    !(u instanceof CustomEvent) || !Oe(u.detail, o) || !(u.target instanceof Element) || t.contains(u.target) && ge(u.target, "formieValidatorClearError", {
      ...u.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  };
  document.addEventListener("formie:validator:destroy", d), document.addEventListener("formie:validator:show-error", p), document.addEventListener("formie:validator:clear-error", h), i.push(() => {
    document.removeEventListener("formie:validator:destroy", d), document.removeEventListener("formie:validator:show-error", p), document.removeEventListener("formie:validator:clear-error", h);
  });
}
function I(e, t, r) {
  e.dispatchEvent(new CustomEvent(Be(t), {
    bubbles: !0,
    detail: r
  }));
}
function zt() {
  return globalThis;
}
function Wt() {
  return zt().__FORMIE_DEBUG__ === !0;
}
function da(e) {
  zt().__FORMIE_DEBUG__ = e;
}
function Yr(e, t, r) {
  if (Wt()) {
    if (typeof r > "u") {
      console.log(`[formie:${e}] ${t}`);
      return;
    }
    console.log(`[formie:${e}] ${t}`, r);
  }
}
function Zr(e, t, r) {
  if (Wt()) {
    if (typeof r > "u") {
      console.warn(`[formie:${e}] ${t}`);
      return;
    }
    console.warn(`[formie:${e}] ${t}`, r);
  }
}
function _(e, t) {
  const r = t ? `${e}:${t}` : e;
  return {
    log: (n, i) => {
      Yr(r, n, i);
    },
    warn: (n, i) => {
      Zr(r, n, i);
    }
  };
}
const Ee = _("general", "page-client-event"), Qr = "data-formie-client-event";
function Xr(e) {
  return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
}
function en(e) {
  const r = e.querySelector('input[name="pageId"]')?.value?.trim();
  if (r)
    return r;
  const i = e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim();
  return i || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function tn(e) {
  if (!e?.trim())
    return null;
  try {
    const t = JSON.parse(e);
    return t && typeof t == "object" ? t : null;
  } catch {
    return Ee.warn("Invalid data-formie-client-event JSON.", {
      rawPreview: e.slice(0, 80)
    }), null;
  }
}
function rn(e) {
  const t = {};
  return e.forEach((r) => {
    const n = typeof r.label == "string" ? r.label.trim() : "";
    n && (t[n] = typeof r.value == "string" ? r.value : "");
  }), t;
}
function Kt(e, t) {
  if (t !== "submit")
    return;
  const r = en(e);
  if (!r) {
    Ee.log("No submitted page id; skipping client event.");
    return;
  }
  const n = e.querySelector(
    `[data-formie-page][data-formie-page-id="${Xr(r)}"]`
  );
  if (!n) {
    Ee.log("No page section for id; skipping client event.", { pageId: r });
    return;
  }
  const i = n.getAttribute(Qr);
  if (i === null)
    return;
  const o = tn(i);
  if (!o || !Array.isArray(o.fields))
    return;
  const a = rn(o.fields), s = window;
  s.dataLayer = s.dataLayer || [], s.dataLayer.push(a), e.dispatchEvent(new CustomEvent("formie:client-event", {
    bubbles: !0,
    detail: { payload: a }
  })), Ee.log("Dispatched page client event.", {
    pageId: r,
    keys: Object.keys(a)
  });
}
const we = /* @__PURE__ */ new WeakMap(), nn = "[data-formie-form], [data-formie], form";
function on(e) {
  return e ? (Array.isArray(e) ? e : [e]).flatMap((r) => String(r).split(/\s+/)).map((r) => r.trim()).filter(Boolean) : [];
}
function tt(e) {
  return Array.from(new Set(e));
}
function an(e) {
  if (!e)
    return {};
  const t = we.get(e);
  if (t)
    return t;
  const r = e.closest(nn);
  return r ? we.get(r) || {} : {};
}
function sn(e) {
  const t = {};
  return Object.entries(e || {}).forEach(([r, n]) => {
    const i = tt(on(n));
    i.length && (t[r] = i);
  }), t;
}
function yt(e, t, r) {
  const n = sn(t), i = r || (e instanceof HTMLFormElement ? e : e.querySelector("form"));
  return we.set(e, n), i && we.set(i, n), n;
}
function rt(e, t) {
  return an(e)[t] || [];
}
function O(e, t, ...r) {
  const n = tt(r.flatMap((i) => rt(t, i)));
  n.length && e.classList.add(...n);
}
function le(e, t, ...r) {
  const n = tt(r.flatMap((i) => rt(t, i)));
  n.length && e.classList.remove(...n);
}
function ue(e, t, r, n) {
  rt(t, r).forEach((i) => {
    e.classList.toggle(i, n);
  });
}
function ln(e, t) {
  if (ue(e, e, "tabError", t), t) {
    e.setAttribute("data-formie-tab-error", "true");
    return;
  }
  e.removeAttribute("data-formie-tab-error");
}
function J(e) {
  const t = /* @__PURE__ */ new Set();
  e.querySelectorAll("[data-formie-page]").forEach((r) => {
    const n = r, i = n.getAttribute("data-formie-page-id");
    i && n.querySelector("[data-formie-field-has-error]") && t.add(i);
  }), e.querySelectorAll("[data-formie-tab]").forEach((r) => {
    const n = r, i = n.getAttribute("data-formie-page-id");
    ln(n, !!i && t.has(i));
  });
}
class Gt {
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
async function Jt(e, t = {}) {
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
async function Re(e, t = {}) {
  const r = await Jt(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.json();
}
async function fa(e, t = {}) {
  const r = await Jt(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.text();
}
const H = _("general", "transport");
function un(e) {
  const t = {};
  return ["theme", "themeConfig", "locale", "siteId"].forEach((r) => {
    e[r] !== void 0 && (t[r] = e[r]);
  }), t;
}
function Yt(e, t = "", r = {}) {
  if (Array.isArray(e)) {
    const n = e.map((i) => typeof i == "string" ? i : String(i ?? "")).filter((i) => i.trim() !== "");
    return t && n.length && (r[t] = (r[t] || []).concat(n)), r;
  }
  return e && typeof e == "object" && Object.entries(e).forEach(([n, i]) => {
    const o = t ? `${t}.${n}` : n;
    Yt(i, o, r);
  }), r;
}
function cn(e, t) {
  const r = e.success === !0, n = e.keepSubmitLoading === !0, i = e.errors, o = Yt(i || {}), a = o.form || [], s = {};
  Object.entries(o).forEach(([h, u]) => {
    if (h === "form")
      return;
    const c = h.split(".")[0];
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
    meta: e
  };
}
async function dn(e, t, r = {}) {
  const n = JSON.stringify({
    handle: t,
    renderOptions: r
  });
  H.log("requestRender start.", { endpoint: e, handle: t });
  const i = await Re(e, {
    method: "POST",
    body: n,
    headers: {
      "Content-Type": "application/json"
    }
  });
  return H.log("requestRender complete.", {
    hasHtml: !!i.html
  }), i;
}
async function fn(e, t, r = {}) {
  const i = JSON.stringify({
    query: `
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,
    variables: {
      handle: t,
      input: un(r)
    }
  });
  H.log("requestGraphqlRender start.", { endpoint: e, handle: t });
  const o = await Re(e, {
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
  return H.log("requestGraphqlRender complete.", {
    hasHtml: !!a.html
  }), a;
}
async function nt(e, t, r) {
  const n = new URL(e, window.location.origin);
  n.searchParams.set("handle", t), r && n.searchParams.set("renderId", r), H.log("requestRefreshTokens start.", {
    endpoint: n.toString(),
    handle: t,
    hasRenderId: !!r
  });
  const i = await Re(n.toString());
  return H.log("requestRefreshTokens complete.", {
    hasRefreshTokens: !!i.refreshTokens
  }), i.refreshTokens || i;
}
async function mn(e, t, r) {
  const n = new URL(e, window.location.origin), i = new FormData();
  if (r && i.append("pageId", r), t) {
    ["handle", "renderId", "draftContextToken", "draftContext", "continuationToken"].forEach((d) => {
      const h = t.querySelector(`input[name="${d}"]`)?.value?.trim();
      h && i.append(d, h);
    });
    const l = t.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value?.trim();
    l && i.append("CRAFT_CSRF_TOKEN", l);
  }
  H.log("requestSetPage start.", {
    requestUrl: n.toString(),
    pageId: r || null
  });
  const o = await Re(n.toString(), {
    method: "POST",
    body: i
  });
  return H.log("requestSetPage complete.", o), o;
}
function pn(e, t) {
  const r = new URL(e, window.location.origin), n = new FormData();
  ["handle", "renderId", "draftContextToken", "draftContext"].forEach((s) => {
    const d = t.querySelector(`input[name="${s}"]`)?.value?.trim();
    d && n.append(s, d);
  });
  const a = t.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value?.trim();
  a && n.append("CRAFT_CSRF_TOKEN", a), H.log("clearSubmissionOnUnload start.", {
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
async function gn(e, t) {
  const r = (e.getAttribute("method") || "POST").toUpperCase(), n = e.getAttribute("action") || window.location.href, i = e.dataset.formieErrorMessage?.trim() || "Submission failed.";
  H.log("submitForm start.", {
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
    return o.ok ? (H.log("submitForm non-JSON success response.", {
      status: o.status,
      contentType: a
    }), {
      ok: !0,
      message: "Submission completed."
    }) : (H.warn("submitForm non-JSON HTTP error.", {
      status: o.status,
      contentType: a
    }), {
      ok: !1,
      code: "HTTP_ERROR",
      message: `Request failed (${o.status}).`,
      formErrors: [`Request failed (${o.status}).`]
    });
  const s = await o.json(), l = cn(s, i);
  return H.log("submitForm JSON response normalized.", {
    ok: l.ok,
    code: l.code,
    hasRedirect: !!l.redirect?.url,
    hasSubmitData: Array.isArray(l.submitData) && l.submitData.length > 0
  }), l;
}
function it(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function Zt(e) {
  const t = it(e);
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
const hn = ["prepare", "normalize", "validate", "screen", "authorize", "dispatch", "finalize"], bn = ["prepare", "normalize", "validate", "screen", "authorize"], F = _("general", "pipeline");
function $e(e, t) {
  return {
    ok: !1,
    stage: e,
    code: "ABORTED",
    message: t || "Submission aborted.",
    formErrors: [t || "Submission aborted."]
  };
}
function Qt(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function Xt(e) {
  return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function er(e, t) {
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
function yn(e, t) {
  t.querySelectorAll("input, select, textarea").forEach((r) => {
    const n = Qt(r) ? r : null;
    !n || n.closest("[data-formie-page]") || Xt(n) && er(e, n);
  });
}
function vn(e, t) {
  const r = /* @__PURE__ */ new Set();
  return t.querySelectorAll("input, select, textarea").forEach((n) => {
    const i = Qt(n) ? n : null;
    !i || !i.name || i.disabled || i instanceof HTMLInputElement && (i.type === "submit" || i.type === "button" || i.type === "reset" || i.type === "image") || (i.name.startsWith("fields[") && r.add(i.name), Xt(i) && er(e, i));
  }), r;
}
function En(e, t) {
  t.forEach((r) => {
    e.has(r) || e.append(r, "");
  });
}
function vt(e, t) {
  const r = it(e), n = r.find((a) => !a.hasAttribute("data-formie-page-hidden")) || null;
  if (!r.length || !n) {
    const a = new FormData(e);
    return a.set("submitAction", t), a;
  }
  const i = new FormData();
  yn(i, e);
  const o = vn(i, n);
  return En(i, o), i.set("submitAction", t), i;
}
function Sn(e, t) {
  if (t !== "submit")
    return !1;
  const r = it(e);
  return r.length ? (r.find((i) => !i.hasAttribute("data-formie-page-hidden")) || r[r.length - 1]) === r[r.length - 1] : !0;
}
async function tr(e, t, r, n = {}) {
  F.log("Starting submit pipeline.", {
    action: t,
    preflightOnly: n.preflightOnly === !0
  });
  let i = !1, o, a = null;
  const s = Sn(e, t), l = {
    form: e,
    action: t,
    formData: vt(e, t),
    abort: (u) => {
      i = !0, o = u, F.warn("Pipeline aborted.", { reason: u });
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
        const { scope: c, final: b } = Zt(u.form), A = n.validator.submit(b ? u.form : c, { final: b });
        return A.length > 0 ? (A[0]?.input.focus(), {
          ok: !1,
          stage: "validate",
          code: "VALIDATION_FAILED",
          message: n.validator.config.errorMessage || "Validation failed.",
          fieldErrors: n.validator.getFieldErrors(A),
          formErrors: [n.validator.config.errorMessage || "Validation failed."]
        }) : null;
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
      u.formData = vt(u.form, u.action);
      const c = await gn(u.form, u.formData);
      return a = c, c;
    },
    finalize: async (u) => (a && a.ok && a.redirect?.url && (a.redirect.target === "new-tab" ? window.open(a.redirect.url, "_blank") : window.location.href = a.redirect.url), null)
  };
  {
    const u = await r.emitSafe("formie:submit:before", l);
    u.failed.length > 0 && F.warn("Submit before listeners failed.", {
      eventName: u.eventName,
      failed: u.failed.length
    });
  }
  if (s) {
    const u = await r.emitSafe("formie:submit:final:before", l);
    u.failed.length > 0 && F.warn("Final submit before listeners failed.", {
      eventName: u.eventName,
      failed: u.failed.length
    });
  }
  const p = n.preflightOnly ? bn : hn;
  for (const u of p) {
    if (F.log("Stage start.", { stage: u, action: t }), i)
      return F.warn("Stage skipped due to abort.", { stage: u, reason: o }), $e(u, o);
    {
      const b = await r.emitSafe(`formie:stage:${u}:before`, {
        ...l,
        stage: u
      });
      b.failed.length > 0 && F.warn("Stage before listeners failed.", {
        stage: u,
        failed: b.failed.length
      });
    }
    if (i) {
      const b = $e(u, o);
      {
        const A = await r.emitSafe("formie:submit:after", b);
        A.failed.length > 0 && F.warn("Submit after listeners failed (abort before stage).", {
          stage: u,
          failed: A.failed.length
        });
      }
      if (s) {
        const A = await r.emitSafe("formie:submit:final:after", b);
        A.failed.length > 0 && F.warn("Final submit after listeners failed (abort before stage).", {
          stage: u,
          failed: A.failed.length
        });
      }
      return F.warn("Aborted after stage before-hooks.", { stage: u, reason: o }), b;
    }
    const c = await d[u](l);
    F.log("Stage runner complete.", {
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
      b.failed.length > 0 && F.warn("Stage after listeners failed.", {
        stage: u,
        failed: b.failed.length
      });
    }
    if (i) {
      const b = $e(u, o);
      {
        const A = await r.emitSafe("formie:submit:after", b);
        A.failed.length > 0 && F.warn("Submit after listeners failed (abort after stage).", {
          stage: u,
          failed: A.failed.length
        });
      }
      if (s) {
        const A = await r.emitSafe("formie:submit:final:after", b);
        A.failed.length > 0 && F.warn("Final submit after listeners failed (abort after stage).", {
          stage: u,
          failed: A.failed.length
        });
      }
      return F.warn("Aborted after stage after-hooks.", { stage: u, reason: o }), b;
    }
    if (c && !c.ok) {
      {
        const b = await r.emitSafe("formie:submit:after", c);
        b.failed.length > 0 && F.warn("Submit after listeners failed (failed stage).", {
          stage: u,
          failed: b.failed.length
        });
      }
      if (s) {
        const b = await r.emitSafe("formie:submit:final:after", c);
        b.failed.length > 0 && F.warn("Final submit after listeners failed (failed stage).", {
          stage: u,
          failed: b.failed.length
        });
      }
      return F.warn("Pipeline short-circuited by failed stage.", {
        stage: u,
        code: c.code,
        message: c.message
      }), c;
    }
  }
  const h = a || {
    ok: !0,
    stage: n.preflightOnly ? "authorize" : "finalize",
    message: n.preflightOnly ? "Submission preflight completed." : "Submission completed."
  };
  {
    const u = await r.emitSafe("formie:submit:after", h);
    u.failed.length > 0 && F.warn("Submit after listeners failed (success).", {
      failed: u.failed.length
    });
  }
  if (s) {
    const u = await r.emitSafe("formie:submit:final:after", h);
    u.failed.length > 0 && F.warn("Final submit after listeners failed (success).", {
      failed: u.failed.length
    });
  }
  return F.log("Pipeline completed.", {
    ok: h.ok,
    stage: h.stage,
    code: h.code
  }), h;
}
function An(e) {
  return e.querySelector("[data-formie-field-layout]")?.getAttribute("data-formie-error-position")?.trim() === "above" ? "above" : "below";
}
function rr(e, t) {
  const r = e.querySelector("[data-formie-field-errors]");
  if (r)
    return r;
  const n = e.querySelector("[data-formie-field-content]"), i = e.querySelector("[data-formie-field-control]"), o = An(e), a = document.createElement("div");
  return a.setAttribute("data-formie-field-errors", "true"), t?.(a), n && i ? o === "above" ? n.insertBefore(a, i) : n.appendChild(a) : e.appendChild(a), a;
}
const wn = {
  rule: ({ input: e, getRule: t }) => !t("email") || !e.value || e.value.length < 1 ? !0 : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-validation-email-message") ?? e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? r("{label} is not a valid email address.", { label: t })
};
function Tn(e) {
  return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function Et(e) {
  const t = e.getRule("match");
  if (!t || t === !0 || typeof t != "object" || !e.field)
    return null;
  const r = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
  if (!r)
    return null;
  const n = e.form.querySelector(`[data-formie-field-handle="${r}"]`);
  return n ? n.querySelector(e.config.fieldsSelector) : null;
}
const Mn = {
  rule: (e) => {
    const t = Et(e);
    return t ? t.value === e.input.value : !0;
  },
  message: (e) => {
    const r = Et(e)?.closest("[data-formie-field-handle]"), n = Tn(r);
    return e.t("{label} must match {value}.", {
      label: e.label,
      value: n
    });
  }
}, Cn = {
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
    return o !== null && a !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? n("{label} must be between {min} and {max}.", { label: t, min: o, max: a }) : o !== null ? e.getAttribute("data-formie-validation-number-min-message") ?? n("{label} must be no less than {min}.", { label: t, min: o }) : a !== null ? e.getAttribute("data-formie-validation-number-max-message") ?? n("{label} must be no greater than {max}.", { label: t, max: a }) : e.getAttribute("data-formie-validation-number-message") ?? e.getAttribute("data-formie-pattern-number-message") ?? e.getAttribute("data-pattern-number-message") ?? n("{label} is not a valid number.", { label: t });
  }
}, Ln = {
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
}, In = {
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
}, Rn = {
  // Keep the core validator registry centralized so FormieValidator can extend
  // it at runtime while still shipping one predictable predefined rule surface.
  required: Ln,
  email: wn,
  url: In,
  number: Cn,
  match: Mn
};
function nr() {
  return window.FormieTranslations || {};
}
function Fn() {
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
          ...t ?? nr(),
          ...i
        };
      } catch {
        continue;
      }
  }
  t && (window.FormieTranslations = t);
}
function ot() {
  return Fn(), nr();
}
function ir() {
  return { ...ot() };
}
function ma(e) {
  return window.FormieTranslations = { ...e }, ir();
}
function pa(e) {
  return window.FormieTranslations = {
    ...ot(),
    ...e
  }, ir();
}
function kn(e) {
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
function Pn(e, t) {
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
function qn(e, t) {
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
function Vn(e, t) {
  let r = "", n = 0;
  for (; n < e.length; ) {
    if (e[n] !== "{") {
      r += e[n], n++;
      continue;
    }
    const i = qn(e, n);
    if (!i) {
      r += e[n], n++;
      continue;
    }
    const o = t[i.param], a = typeof o == "number" ? o : Number.parseInt(String(o ?? ""), 10) || 0, s = kn(i.body);
    let l = Pn(a, s);
    l = l.replace(/#/g, String(a)), r += l, n = i.endIndex + 1;
  }
  return r;
}
function On(e, t) {
  return e.replace(/\{(\w+),\s*number\}/g, (r, n) => {
    if (!Object.prototype.hasOwnProperty.call(t, n))
      return r;
    const i = t[n];
    return typeof i == "number" ? i.toLocaleString() : String(i);
  });
}
function $n(e, t) {
  return e.replace(/\{(\w+)\}/g, (r, n) => Object.prototype.hasOwnProperty.call(t, n) ? String(t[n]) : r);
}
function x(e, t = {}) {
  let r = ot()[e] || e;
  return r = Vn(r, t), r = On(r, t), r = $n(r, t), r;
}
const ga = x, Hn = {
  // eslint-disable-next-line
  email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
  url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
  number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
  color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
  date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
  time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
  month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/
}, K = _("general", "validator");
function oe(e) {
  return !!e && (e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement);
}
function _n(e, t) {
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
function Dn(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function xn(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function Nn(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
class Un {
  constructor(t, r = {}) {
    this.errors = [], this.validators = {}, this.boundListeners = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.submitted = !1, this.initialValues = /* @__PURE__ */ new WeakMap(), this.form = t, this.onBlur = this.blurHandler.bind(this), this.onChange = this.changeHandler.bind(this), this.onInput = this.inputHandler.bind(this), this.config = {
      live: !1,
      errorMessage: "",
      fieldContainerErrorClass: [],
      inputErrorClass: [],
      messagesClass: [],
      messageClass: [],
      fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
      patterns: Hn,
      ...r
    }, Object.entries(Rn).forEach(([n, i]) => {
      this.addValidator(n, i.rule, i.message);
    }), this.init();
  }
  init() {
    K.log("Initializing validator.", {
      formId: this.form.id || null,
      live: this.config.live
    }), this.form.setAttribute("novalidate", "true"), this.inputs().forEach((t) => {
      this.initialValues.set(t, this.getInputValue(t));
    }), this.config.live && this.addEventListeners(), this.emitEvent(document, pe("ready"), {
      validator: this
    });
  }
  inputs(t = null) {
    if (oe(t))
      return [t];
    const r = t || this.form;
    return Array.from(r.querySelectorAll(this.config.fieldsSelector)).filter((n) => oe(n));
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
      Object.entries(this.validators).forEach(([d, p]) => {
        if (!p.validate(l)) {
          const u = this.getErrorMessage(i, d, p, l);
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
    }), K.log("Validation pass complete.", {
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
      a.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && a.classList.remove(...this.config.inputErrorClass), a.removeAttribute("data-formie-input-has-error"), i && _n(a, i), r.querySelectorAll("[data-formie-field-error]").forEach((s) => {
        const l = s.id;
        l && Nn(a, l);
      });
    });
    for (let o = r; o; o = o.parentElement?.closest("[data-formie-field-handle]"))
      this.config.fieldContainerErrorClass.length && o.classList.remove(...this.config.fieldContainerErrorClass), o.removeAttribute("data-formie-field-has-error");
    this.emitEvent(t, pe("clear-error"), {
      validator: this
    }), J(this.form);
  }
  showError(t, r, n) {
    const i = t.closest("[data-formie-field-handle]");
    if (!i)
      return;
    let o = i.querySelector("[data-formie-field-errors]");
    o || (o = rr(i, (d) => {
      this.config.messagesClass.length && d.classList.add(...this.config.messagesClass);
    })), this.config.messagesClass.length && o.classList.add(...this.config.messagesClass), o.innerHTML = "";
    const a = i.getAttribute("data-formie-field-handle") || "field", s = `${a}-error`;
    o.id = o.id || `${a}-errors`, o.setAttribute("aria-live", "polite"), o.setAttribute("aria-atomic", "true");
    const l = document.createElement("div");
    l.setAttribute("data-formie-field-error", "true"), l.setAttribute(`data-formie-field-error-${r}`, "true"), l.setAttribute("id", s), l.setAttribute("role", "alert"), this.config.messageClass.length && l.classList.add(...this.config.messageClass), l.textContent = n, o.appendChild(l), i.setAttribute("data-formie-field-has-error", "true"), i.querySelectorAll("input, select, textarea").forEach((d) => {
      const p = d;
      p.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && p.classList.add(...this.config.inputErrorClass), p.setAttribute("data-formie-input-has-error", "true"), Dn(p, o.id), xn(p, s);
    });
    for (let d = i; d; d = d.parentElement?.closest("[data-formie-field-handle]"))
      this.config.fieldContainerErrorClass.length && d.classList.add(...this.config.fieldContainerErrorClass), d.setAttribute("data-formie-field-has-error", "true");
    this.emitEvent(t, pe("show-error"), {
      validator: this,
      validatorName: r,
      errorMessage: n
    }), J(this.form);
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
      return K.warn("Invalid validation rules payload.", {
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
    K.log("Destroying validator.", {
      formId: this.form.id || null
    }), this.removeEventListeners(), this.form.removeAttribute("novalidate"), this.emitEvent(document, pe("destroy"), {
      validator: this
    });
  }
  isVisible(t, r = {}) {
    return t.closest("[data-formie-conditionally-hidden]") ? !1 : t.closest("[data-formie-page-hidden]") ? !!r.includeHiddenPages : !!(t.offsetWidth || t.offsetHeight || t.getClientRects().length);
  }
  blurHandler(t) {
    !(t.target instanceof HTMLElement) || !oe(t.target) || !t.target.form?.isSameNode(this.form) || t instanceof CustomEvent || t.target instanceof HTMLInputElement && t.target.type === "file" || t.target instanceof HTMLInputElement && (t.target.type === "checkbox" || t.target.type === "radio") || (this.isDirty(t.target) && this.activated.add(t.target), this.shouldShowError(t.target) && this.validate(t.target));
  }
  changeHandler(t) {
    if (!(!(t.target instanceof HTMLElement) || !oe(t.target) || !t.target.form?.isSameNode(this.form)) && !(t instanceof CustomEvent)) {
      if (t.target instanceof HTMLSelectElement) {
        this.activated.add(t.target), this.validate(t.target);
        return;
      }
      t.target instanceof HTMLInputElement && (t.target.type !== "file" && t.target.type !== "checkbox" && t.target.type !== "radio" || (this.activated.add(t.target), this.validate(t.target)));
    }
  }
  inputHandler(t) {
    !(t.target instanceof HTMLElement) || !oe(t.target) || !t.target.form?.isSameNode(this.form) || t instanceof CustomEvent || t.target instanceof HTMLInputElement && (t.target.type === "checkbox" || t.target.type === "radio") || this.shouldShowError(t.target) && this.validate(t.target);
  }
  submit(t = null, { final: r = !1 } = {}) {
    return this.submitted = !0, K.log("Submit validation requested.", {
      final: r
    }), this.boundListeners || this.addEventListeners(), this.removeAllErrors(), this.validate(t, {
      includeHiddenPages: r
    });
  }
  resetLiveState() {
    this.submitted = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.errors = [], this.removeAllErrors();
  }
  addEventListeners() {
    this.boundListeners || (this.form.addEventListener("blur", this.onBlur, !0), this.form.addEventListener("change", this.onChange, !1), this.form.addEventListener("input", this.onInput, !1), this.boundListeners = !0, K.log("Event listeners attached."));
  }
  removeEventListeners() {
    this.form.removeEventListener("blur", this.onBlur, !0), this.form.removeEventListener("change", this.onChange, !1), this.form.removeEventListener("input", this.onInput, !1), this.boundListeners = !1, K.log("Event listeners removed.");
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
const he = "data-formie-submit-validation-disabled", He = "data-formie-preserve-disabled", Bn = "data-formie-submit-ready";
function or(e) {
  return e.dataset.formieDisableSubmitUntilValid === "true";
}
function jn(e) {
  return Array.from(e.querySelectorAll('button[data-formie-action="submit"]')).filter((t) => t instanceof HTMLButtonElement);
}
function zn(e) {
  return !e.hasAttribute("data-formie-conditionally-hidden") && !e.closest("[data-formie-conditionally-hidden]");
}
function at(e, t) {
  if (!or(e) || e.getAttribute("data-formie-loading") === "true")
    return;
  const { scope: r, final: n } = Zt(e), i = t.isValid(r, {
    includeHiddenPages: n
  });
  e.setAttribute(Bn, i ? "true" : "false"), jn(e).forEach((o) => {
    if (zn(o)) {
      if (i) {
        if (!o.hasAttribute(he))
          return;
        o.hasAttribute(He) ? (o.disabled = !0, o.removeAttribute(He)) : o.disabled = !1, o.removeAttribute(he);
        return;
      }
      o.hasAttribute(he) || (o.disabled && o.setAttribute(He, "true"), o.setAttribute(he, "true")), o.disabled = !0;
    }
  });
}
function Wn(e, t, r) {
  if (!or(e))
    return () => {
    };
  let n = !1;
  const i = () => {
    n || (n = !0, queueMicrotask(() => {
      n = !1, at(e, t);
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
    d.some((h) => {
      if (h.type === "attributes") {
        const u = h.attributeName || "";
        return u === "data-formie-page-hidden" || u === "data-formie-conditionally-hidden" || u === "data-formie-loading" || u === "disabled";
      }
      return h.type === "childList";
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
const Kn = "STALE_SUBMISSION_STATE", St = /* @__PURE__ */ new WeakMap(), Te = /* @__PURE__ */ new WeakMap(), j = _("general", "submit-result");
function ze(e, t, r) {
  let n = e.querySelector(`input[name="${t}"]`);
  n || (n = document.createElement("input"), n.type = "hidden", n.name = t, e.appendChild(n)), n.value = r;
}
function At(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function ae(e, t) {
  e.querySelector(`input[name="${t}"]`)?.remove();
}
function Gn(e, t) {
  try {
    const r = new URL(e, window.location.href);
    return r.searchParams.delete(t), r.toString();
  } catch {
    return e;
  }
}
function Jn(e) {
  try {
    return new URL(e, window.location.href).origin === window.location.origin;
  } catch {
    return !1;
  }
}
function ar(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function Yn(e) {
  return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function Zn(e, t, r) {
  return t < 0 || r < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") === "page-position" ? Math.round((t + 1) / r * 100) : Math.round(t / r * 100);
}
function Qn(e) {
  return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function Xn(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function wt(e, t) {
  const r = t.meta?.effectiveSubmitAction;
  return typeof r == "string" && r.trim() !== "" ? r.trim() : Xn(e);
}
function Tt(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function st(e, t) {
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
function B(e) {
  const t = St.get(e);
  typeof t == "number" && (window.clearTimeout(t), St.delete(e));
}
function ei(e, t) {
  Te.has(e) || Te.set(e, e.innerHTML), e.textContent = t;
}
function We(e) {
  const t = Te.get(e);
  t !== void 0 && (e.innerHTML = t, Te.delete(e));
}
function ti(e, t) {
  const r = e.querySelector("[data-formie-progress-bar]"), n = e.querySelector("[data-formie-progress-value]");
  r && (r.style.width = `${t}%`, r.setAttribute("aria-valuenow", `${t}`), r.setAttribute("data-formie-progress-state", Qn(t)), n && (n.textContent = `${t}%`, n.setAttribute("data-formie-progress-value", `${t}`)));
}
function ri(e, t) {
  if (!t)
    return;
  const r = (e.dataset.formieLoadingIndicator || "").trim();
  if (r) {
    if (t.setAttribute("data-formie-loading-indicator", r), r === "spinner") {
      ue(t, e, "loading", !0), We(t), t.removeAttribute("data-formie-loading-text");
      return;
    }
    if (r === "text") {
      const n = (e.dataset.formieLoadingIndicatorText || "").trim(), i = t.textContent?.trim() || "", o = n || i;
      t.setAttribute("data-formie-loading-text", o), ei(t, o);
      return;
    }
    We(t), t.removeAttribute("data-formie-loading-text");
  }
}
function sr(e) {
  return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function lr(e, t) {
  if (e.getAttribute("data-formie-loading") === "true")
    return;
  e.setAttribute("data-formie-loading", "true"), sr(e).forEach((n) => {
    "disabled" in n && (n.disabled ? n.setAttribute("data-formie-was-disabled", "true") : n.removeAttribute("data-formie-was-disabled"), n.disabled = !0);
  }), t && (t.setAttribute("data-formie-loading", "true"), ri(e, t));
}
function Me(e) {
  if (e.removeAttribute("data-formie-loading"), sr(e).forEach((r) => {
    if ("disabled" in r) {
      const n = r, i = n.getAttribute("data-formie-was-disabled") === "true";
      n.disabled = i;
    }
    We(r), r.removeAttribute("data-formie-was-disabled"), r.removeAttribute("data-formie-loading"), ue(r, e, "loading", !1), r.removeAttribute("data-formie-loading-indicator"), r.removeAttribute("data-formie-loading-text");
  }), e.dataset.formieDisableSubmitUntilValid === "true") {
    const r = e;
    r.formieValidation && at(e, r.formieValidation);
  }
}
function lt(e, t) {
  const r = ar(e), n = Yn(e), i = r.findIndex((o) => o.getAttribute("data-formie-page-id") === t);
  if (r.forEach((o) => {
    o.getAttribute("data-formie-page-id") === t ? (o.removeAttribute("data-formie-page-hidden"), le(o, e, "pageHidden")) : (o.setAttribute("data-formie-page-hidden", "true"), O(o, e, "pageHidden"));
  }), n.forEach((o, a) => {
    const s = o.getAttribute("data-formie-page-id") === t, l = i > -1 && a < i;
    ue(o, e, "tabCurrent", s), ue(o, e, "tabComplete", l), s ? o.setAttribute("aria-current", "page") : o.removeAttribute("aria-current"), l ? o.setAttribute("data-formie-tab-complete", "true") : o.removeAttribute("data-formie-tab-complete");
  }), i > -1 && r.length > 0) {
    const o = Zn(e, i, r.length);
    ti(e, o);
  }
  if (ze(e, "pageId", t), J(e), e.dataset.formieDisableSubmitUntilValid === "true") {
    const o = e;
    o.formieValidation && at(e, o.formieValidation);
  }
}
function ni(e, t) {
  const r = t.meta?.submissionUid;
  typeof r == "string" && r.trim() !== "" && ze(e, "submissionUid", r);
  const n = t.meta?.session?.continuation?.continuationToken;
  typeof n == "string" && n.trim() !== "" ? ze(e, "continuationToken", n) : ae(e, "continuationToken");
}
function ii(e) {
  const t = e.getAttribute("action");
  t && e.setAttribute("action", Gn(t, "resumeToken"));
  try {
    const r = new URL(window.location.href);
    if (!r.searchParams.has("resumeToken"))
      return;
    r.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${r.pathname}${r.search}${r.hash}`);
  } catch {
  }
}
function oi(e, t) {
  const r = t.meta?.resumeUrl;
  if (typeof r != "string" || r.trim() === "")
    return;
  const n = r.trim();
  if (!Jn(n))
    return;
  e.getAttribute("action") && e.setAttribute("action", n);
  try {
    const o = new URL(n, window.location.href);
    window.history.replaceState({}, document.title, `${o.pathname}${o.search}${o.hash}`);
  } catch {
  }
}
function be(e, t = {}) {
  const n = e.formieValidation, i = ar(e)[0]?.getAttribute("data-formie-page-id");
  if (B(e), e.reset(), t.preserveHiddenState || st(e, !1), ae(e, "submissionId"), ae(e, "submissionUid"), ae(e, "continuationToken"), ae(e, "pageId"), ii(e), n?.resetLiveState(), i) {
    lt(e, i), e.dispatchEvent(new CustomEvent(je("reset"), { bubbles: !0 }));
    return;
  }
  J(e), e.dispatchEvent(new CustomEvent(je("reset"), { bubbles: !0 }));
}
function ai(e) {
  return e.code === Kn || e.meta?.resetState === !0;
}
function si(e, t) {
  const r = t.submitData, n = /* @__PURE__ */ new Set();
  let i = !1;
  if (Array.isArray(r) && r.length > 0) {
    const p = r.filter(
      (h) => typeof h == "object" && h !== null && "event" in h && typeof h.event == "string"
    );
    for (const h of p) {
      const u = h.event;
      n.add(u), j.log("Dispatching submitData event.", {
        eventName: u
      }), u.startsWith("formie:payment:") && (i = !0), e.dispatchEvent(new CustomEvent(u, {
        bubbles: !0,
        detail: { data: h.data }
      }));
    }
  }
  const o = t.meta || {}, a = (o.paymentAction && typeof o.paymentAction == "object" ? o.paymentAction : null) || (o.paymentDecision && typeof o.paymentDecision == "object" ? o.paymentDecision.action : null), s = a ? String(a.event || "") : "", l = a ? a.payload : void 0, d = s;
  return d && !n.has(d) && (d.startsWith("formie:payment:") && (i = !0), e.dispatchEvent(new CustomEvent(d, {
    bubbles: !0,
    detail: { data: l }
  })), j.log("Dispatching fallback payment action event.", {
    eventName: d
  })), { hasPaymentFollowUpEvent: i };
}
function li(e, t, r) {
  if (j.log("Applying submit result state.", {
    ok: t.ok,
    action: r,
    code: t.code,
    hasRedirect: !!t.redirect?.url,
    hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
  }), ai(t)) {
    be(e), j.log("Resetting state due to stale/reset marker.");
    return;
  }
  const n = si(e, t);
  if (!t.ok && t.redirect?.url && !n.hasPaymentFollowUpEvent) {
    j.log("Applying redirect fallback for failed result.", {
      url: t.redirect.url,
      target: t.redirect.target
    }), B(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank") : (At(e, "redirect"), window.location.href = t.redirect.url);
    return;
  }
  if (ni(e, t), !t.ok) {
    j.log("Non-redirect failure; keeping current form state."), B(e);
    return;
  }
  if (Kt(e, r), t.nextPage?.id) {
    B(e), e.formieValidation?.resetLiveState(), lt(e, t.nextPage.id), j.log("Advanced to next page.", {
      nextPageId: t.nextPage.id
    });
    return;
  }
  if (r === "save") {
    B(e), oi(e, t), j.log("Applied save/resume token state.");
    return;
  }
  if (r === "submit" && !t.redirect?.url) {
    const i = wt(e, t), o = i === "message" && Tt(e);
    if (i === "reload") {
      B(e), At(e, "reload"), window.location.reload();
      return;
    }
    if (i === "reset") {
      be(e);
      return;
    }
    B(e), be(e, { preserveHiddenState: o });
    return;
  }
  if (r === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
    const o = wt(e, t) === "message" && Tt(e);
    B(e), be(e, { preserveHiddenState: o });
    return;
  }
  B(e);
}
const Ce = /* @__PURE__ */ new WeakMap();
function ur(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function ui(e) {
  return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function cr(e) {
  return (e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function ci(e) {
  const t = (e.dataset.formieSubmitActionMessageTimeout || "").trim();
  if (!t)
    return null;
  const r = Number.parseFloat(t);
  return !Number.isFinite(r) || r < 0 ? null : Math.round(r * 1e3);
}
function ut(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function di(e) {
  const t = Ce.get(e);
  typeof t == "number" && (window.clearTimeout(t), Ce.delete(e));
}
function dr(e) {
  return e.querySelector("[data-formie-form-messages-top]") || e;
}
function fr(e) {
  return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function fi(e, t) {
  return t === "bottom-form" ? fr(e) : dr(e);
}
function mi(e, t) {
  return t === "top-form" ? dr(e) : t === "bottom-form" && !ut(e) ? fr(e) : e;
}
function pi(e) {
  const t = ui(e), r = fi(e, t);
  let n = r.querySelector("[data-formie-error-container], [data-formie-errors]");
  return n || (n = document.createElement("div"), n.setAttribute("data-formie-errors", "true"), O(n, e, "errors")), n.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function gi(e, t) {
  let r = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-error-message-container", "true"), t.appendChild(r)), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-error", "true"), O(r, e, "message", "messageError"), r.setAttribute("role", "alert"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true"), r;
}
function hi(e, t) {
  let r = e.querySelector("[data-formie-success-container]");
  const n = mi(e, t);
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-success-container", "true"), O(r, e, "successes")), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function bi(e) {
  return rr(e, (t) => {
    O(t, e, "fieldErrors");
  });
}
function yi(e, t) {
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
function vi(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function Ei(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
function mr(e) {
  e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
    const r = t, n = r.querySelector("[data-formie-field-errors]"), i = n?.id || "", o = Array.from(r.querySelectorAll("[data-formie-field-error]")).map((a) => a.id).filter(Boolean);
    le(r, e, "fieldLayoutError"), r.removeAttribute("data-formie-field-has-error"), r.querySelectorAll("[data-formie-field-error]").forEach((a) => {
      a.remove();
    }), n && !n.querySelector("[data-formie-field-error]") && (n.innerHTML = ""), r.querySelectorAll("input, select, textarea").forEach((a) => {
      const s = a;
      s.removeAttribute("aria-invalid"), le(s, e, "fieldControlError"), s.removeAttribute("data-formie-input-has-error"), i && yi(s, i), o.forEach((l) => {
        Ei(s, l);
      });
    });
  }), J(e);
}
function pr(e) {
  e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-error]").forEach((n) => {
      n.remove();
    }), le(r, e, "message", "messageError"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-error"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-error]") || (r.innerHTML = "");
  });
}
function ct(e) {
  di(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((t) => {
    t.remove();
  }), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-success]").forEach((n) => {
      n.remove();
    }), le(r, e, "message", "messageSuccess"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-success"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-success]") || (r.innerHTML = "");
  }), ur(e) === "message" && ut(e) || st(e, !1);
}
function gr(e) {
  e.querySelectorAll('[aria-invalid="true"]').forEach((t) => {
    t.removeAttribute("aria-invalid");
  });
}
function Mt(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function Si(e, t) {
  Object.entries(t).forEach(([r, n]) => {
    const i = e.querySelector(`[data-formie-field-handle="${r}"]`);
    if (!i)
      return;
    const o = bi(i), a = o.id && o.id.trim() ? o.id : `${r}-errors`;
    o.id = a, o.setAttribute("aria-live", "polite"), o.setAttribute("aria-atomic", "true"), O(i, e, "fieldLayoutError"), i.setAttribute("data-formie-field-has-error", "true"), n.forEach((l, d) => {
      const p = document.createElement("div");
      p.setAttribute("data-formie-field-error", "true"), p.setAttribute("role", "alert"), p.id = `${a}-${d + 1}`, O(p, e, "fieldError"), p.textContent = l, o.appendChild(p);
    });
    const s = o.querySelector("[data-formie-field-error]")?.id;
    i.querySelectorAll("input, select, textarea").forEach((l) => {
      const d = l;
      d.setAttribute("aria-invalid", "true"), O(d, e, "fieldControlError"), d.setAttribute("data-formie-input-has-error", "true"), Mt(d, a), s && vi(d, s);
      const p = i.querySelector("[data-formie-instructions]");
      p?.id && Mt(d, p.id);
    });
  }), J(e);
}
function Ct(e, t) {
  const r = pi(e), n = gi(e, r);
  O(r, e, "errors"), t.forEach((i) => {
    const o = document.createElement("div");
    o.setAttribute("data-formie-error", "true"), o.setAttribute("role", "alert"), O(o, e, "error"), o.innerHTML = i, n.appendChild(o);
  });
}
function Ai(e, t) {
  return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" ? !0 : ur(e) === "message" && cr(e) !== "";
}
function wi(e, t) {
  const r = cr(e);
  if (!r)
    return;
  const n = hi(e, r);
  O(n, e, "message", "messageSuccess"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-success", "true"), n.setAttribute("role", "status"), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true");
  const i = document.createElement("div");
  i.setAttribute("data-formie-success", "true"), O(i, e, "success"), i.innerHTML = t, n.appendChild(i), ut(e) && st(e, !0);
  const o = ci(e);
  if (o !== null) {
    const a = window.setTimeout(() => {
      Ce.delete(e), ct(e);
    }, o);
    Ce.set(e, a);
  }
}
function Le(e, t) {
  if (mr(e), pr(e), ct(e), gr(e), t.ok) {
    Ai(e, t) && wi(e, t.message || "");
    return;
  }
  if (t.fieldErrors && Si(e, t.fieldErrors), t.formErrors?.length) {
    Ct(e, t.formErrors);
    return;
  }
  !t.fieldErrors && t.message && Ct(e, [t.message]);
}
const Ti = _("general", "submit-flow");
function Mi(e) {
  return !(!e.ok && e.stage === "validate");
}
function hr(e) {
  return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function br(e) {
  mr(e), pr(e), ct(e), gr(e);
}
async function yr(e) {
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
    onRefreshTokensAfterSubmit: p,
    dispatchSubmitResult: h
  } = e;
  br(n), lr(n, l || null);
  let u = {
    ok: !1,
    code: "SUBMIT_ERROR",
    message: "Submission failed.",
    formErrors: ["Submission failed."]
  };
  try {
    await d(n), u = await tr(n, s, i, {
      validator: o,
      validateOnSubmit: a
    }), Le(n, u), li(n, u, s), Mi(u) && await p(u), h(u);
  } catch (c) {
    u = {
      ok: !1,
      code: "SUBMIT_ERROR",
      message: c instanceof Error ? c.message : "Submission failed.",
      formErrors: [c instanceof Error ? c.message : "Submission failed."]
    }, Le(n, u), h(u), Ti.warn("Submit failed with exception.", {
      id: t,
      action: s,
      target: r,
      error: c instanceof Error ? c.message : c
    });
  } finally {
    hr(u) || Me(n);
  }
  return u;
}
class vr {
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
const Ci = {
  // Address providers stay behind lazy importer entries because their SDKs are
  // optional and often much heavier than the base form client.
  "address-finder": () => import("./address-finder-BU4roxT6.js").then((e) => e.addressFinderModule),
  "google-address": () => import("./google-address-BD8IZ86V.js").then((e) => e.googleAddressModule),
  loqate: () => import("./loqate-B-ZNS65B.js").then((e) => e.loqateModule),
  "place-kit": () => import("./place-kit-DizZWV_e.js").then((e) => e.placeKitModule)
}, Li = {
  // Module ids map directly to importer functions so the loader can fetch only
  // the captcha chunks required by the current form manifest.
  "captcha-eu": () => import("./captcha-eu-BAPzHNts.js").then((e) => e.captchaEuModule),
  "friendly-captcha-v1": () => import("./friendly-captcha-v1-DdlcGiFY.js").then((e) => e.friendlyCaptchaV1Module),
  "friendly-captcha-v2": () => import("./friendly-captcha-v2-76w-YnFm.js").then((e) => e.friendlyCaptchaV2Module),
  hcaptcha: () => import("./hcaptcha-BNNTL6uf.js").then((e) => e.hcaptchaModule),
  "recaptcha-enterprise": () => import("./recaptcha-enterprise-BbOvO7JZ.js").then((e) => e.recaptchaEnterpriseModule),
  "recaptcha-v2-checkbox": () => import("./recaptcha-v2-checkbox-SY3ti5iQ.js").then((e) => e.recaptchaV2CheckboxModule),
  "recaptcha-v2-invisible": () => import("./recaptcha-v2-invisible-DSmQnn6c.js").then((e) => e.recaptchaV2InvisibleModule),
  "recaptcha-v3": () => import("./recaptcha-v3-DNh7YCnR.js").then((e) => e.recaptchaV3Module),
  snaptcha: () => import("./snaptcha-VuOUOaLj.js").then((e) => e.snaptchaModule),
  turnstile: () => import("./turnstile-9tM7q_LK.js").then((e) => e.turnstileModule)
}, Ii = {
  // Keep the predefined map flat and explicit so manifest ids remain the source of
  // truth for lazy-loading first-party field enhancements.
  calculations: () => import("./calculations-C9t6Zop9.js").then((e) => e.calculationsModule),
  "checkbox-radio": () => import("./checkbox-radio-CHeLNVIK.js").then((e) => e.checkboxRadioModule),
  conditions: () => import("./conditions-DKxVCsjD.js").then((e) => e.conditionsModule),
  "date-picker": () => import("./date-picker-B3iQ797Z.js").then((e) => e.datePickerModule),
  "file-upload": () => import("./file-upload-DP2TOUYV.js").then((e) => e.fileUploadModule),
  hidden: () => import("./hidden-CW6Rcf1N.js").then((e) => e.hiddenModule),
  "phone-country": () => import("./phone-country-Cm5YUeXI.js").then((e) => e.phoneCountryModule),
  repeater: () => import("./repeater-DM0vuFNQ.js").then((e) => e.repeaterModule),
  "rich-text": () => import("./rich-text-C8aGYp70.js").then((e) => e.richTextModule),
  signature: () => import("./signature-pFhZfH7d.js").then((e) => e.signatureModule),
  summary: () => import("./summary-Da7Ktg3N.js").then((e) => e.summaryModule),
  table: () => import("./table-C0PmR9bI.js").then((e) => e.tableModule),
  "text-limit": () => import("./text-limit-mLEwZDIa.js").then((e) => e.textLimitModule)
}, Ri = {
  // Keep payment providers lazy and separately addressable so forms only ship
  // the payment SDK wrapper code they actually declare in their manifest.
  bpoint: () => import("./bpoint-uNg5j6rt.js").then((e) => e.bpointModule),
  eway: () => import("./eway-DCielIML.js").then((e) => e.ewayModule),
  "go-cardless": () => import("./go-cardless-rwCkzbNK.js").then((e) => e.goCardlessModule),
  mollie: () => import("./mollie-BDAZ-1X4.js").then((e) => e.mollieModule),
  moneris: () => import("./moneris-B1b0uEtY.js").then((e) => e.monerisModule),
  opayo: () => import("./opayo-CC5hraHo.js").then((e) => e.opayoModule),
  paddle: () => import("./paddle-B4GmrDsq.js").then((e) => e.paddleModule),
  paypal: () => import("./paypal-BFS6XsIS.js").then((e) => e.paypalModule),
  payway: () => import("./payway-DekoJead.js").then((e) => e.paywayModule),
  square: () => import("./square-BR9pvToy.js").then((e) => e.squareModule),
  stripe: () => import("./stripe-OHUwoV9g.js").then((e) => e.stripeModule)
}, Fi = {
  ...Ii,
  ...Ci,
  ...Li,
  ...Ri
}, _e = /* @__PURE__ */ new Map(), D = _("general", "loader"), ki = new Function("src", "return import(src);");
async function ye(e, t, r, n) {
  await e(Br(r), n), await e(Ur(t, r), n);
}
function Er(e) {
  return !!e && typeof e == "object" && typeof e.id == "string" && typeof e.setup == "function" && typeof e.match == "function";
}
async function Pi(e, t) {
  const r = Fi[e];
  return r ? (_e.has(e) || _e.set(e, (async () => {
    try {
      const n = await r();
      return Er(n) ? (t.registry.register(n), n) : null;
    } catch (n) {
      return console.error("[formie] Failed to load predefined module:", e, n), D.warn("Failed loading predefined module.", { moduleId: e, error: n }), null;
    }
  })()), _e.get(e) || null) : null;
}
async function qi(e) {
  try {
    const t = await ki(e), r = t?.default || t?.formieModule || null;
    return Er(r) ? r : null;
  } catch (t) {
    return console.error("[formie] Failed to load module from src:", e, t), D.warn("Failed loading module from src.", { src: e, error: t }), null;
  }
}
async function Vi(e, t) {
  const r = t.registry.get(e.id);
  if (r)
    return r;
  const n = await Pi(e.id, t);
  if (n)
    return n;
  if (e.src) {
    const i = await qi(e.src);
    if (i)
      return t.registry.register(i), i;
  }
  return null;
}
function De(e) {
  return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : e.replace(/["\\]/g, "\\$&");
}
function ve(e, t) {
  return e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function Oi(e, t) {
  const r = t.setupContext.root, n = t.setupContext.form, i = e.targetType, o = e.targetId;
  return i === "selector" ? ve(r, o).map((a) => ({ scope: i, element: a })) : i === "field" ? ve(r, `[data-formie-field-handle="${De(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "page" ? ve(r, `[data-formie-page-id="${De(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "button" ? ve(r, `[data-formie-action="${De(o)}"]`).map((a) => ({ scope: i, element: a })) : [{
    scope: "form",
    element: n || r
  }];
}
function $i(e, t) {
  return (e.targets && e.targets.length > 0 ? e.targets : [{
    targetType: "form",
    targetId: "form"
  }]).flatMap((n) => Oi(n, t));
}
async function Sr(e, t) {
  const r = [];
  D.log("Loading module manifest.", {
    manifestCount: e.length
  });
  for (const n of e) {
    const i = await Vi(n, t);
    if (!i) {
      D.warn("Skipping manifest item (definition not resolved).", {
        moduleId: n.id,
        src: n.src
      });
      continue;
    }
    const o = $i(n, t);
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
      const l = n.config || t.setupContext.options, d = i.id, p = {
        moduleId: i.id,
        moduleKind: i.kind,
        target: a.element,
        scope: a.scope,
        options: l,
        manifestItem: n
      };
      await ye(t.setupContext.emit, d, "before-setup", p);
      let h = null;
      try {
        const u = await i.setup({
          ...t.setupContext,
          target: a.element,
          scope: a.scope,
          options: l
        });
        u && (h = u);
      } catch (u) {
        console.error(`[formie] Module "${i.id}" setup failed:`, u), D.warn("Module setup failed.", {
          moduleId: i.id,
          scope: a.scope,
          error: u
        });
      }
      await ye(t.setupContext.emit, d, "after-setup", {
        ...p,
        instanceCreated: !!h
      }), h && (D.log("Module instance created.", {
        moduleId: i.id,
        scope: a.scope
      }), r.push({
        ...h,
        destroy: async () => {
          D.log("Destroying module instance.", {
            moduleId: i.id,
            scope: a.scope
          }), await ye(t.setupContext.emit, d, "before-destroy", p), await h.destroy(), await ye(t.setupContext.emit, d, "after-destroy", p), D.log("Module instance destroyed.", {
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
const Hi = /* @__PURE__ */ new Set([
  "CRAFT_CSRF_TOKEN",
  "action",
  "redirect",
  "requestToken",
  "renderId",
  "submitAction",
  "pageId",
  "draftContextToken",
  "draftContext",
  "continuationToken"
]);
function Ke(e, t) {
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
    return `[${e.map((r) => Ke(r, t)).join(",")}]`;
  if (typeof e == "object") {
    if (t.has(e))
      return "[circular]";
    t.add(e);
    const r = Object.entries(e).sort(([n], [i]) => n.localeCompare(i)).map(([n, i]) => `${JSON.stringify(n)}:${Ke(i, t)}`);
    return t.delete(e), `{${r.join(",")}}`;
  }
  return JSON.stringify(String(e));
}
function _i(e) {
  return Ke(e, /* @__PURE__ */ new WeakSet());
}
function Di(e) {
  if (!e)
    return !1;
  const t = e.endsWith("[]") ? e.slice(0, -2) : e;
  return !Hi.has(t);
}
function Lt(e) {
  const t = Array.from(new FormData(e).entries()).filter(([r]) => Di(String(r || "")));
  return _i(t);
}
function xi(e, t = {}) {
  let r = null, n = !1, i = !1, o = null, a = null, s = null;
  const l = () => {
    o !== null && (window.cancelAnimationFrame(o), o = null), a !== null && (window.clearTimeout(a), a = null), s !== null && (window.clearTimeout(s), s = null);
  }, d = () => n ? (i = Lt(e) !== r, i) : !1, p = () => {
    r = Lt(e), n = !0, i = !1;
  }, h = () => {
    l(), n = !1, o = window.requestAnimationFrame(() => {
      o = null, s = window.setTimeout(() => {
        s = null, p();
      }, 0);
    });
  }, u = () => {
    a !== null && window.clearTimeout(a), a = window.setTimeout(() => {
      a = null, d();
    }, 120);
  }, c = (b) => {
    t.shouldWarn && !t.shouldWarn() || d() && (b.preventDefault(), b.returnValue = "");
  };
  return e.addEventListener("input", u), e.addEventListener("change", u), window.addEventListener("beforeunload", c), h(), {
    captureBaseline: p,
    scheduleBaselineCapture: h,
    refreshDirtyState: d,
    destroy: () => {
      l(), e.removeEventListener("input", u), e.removeEventListener("change", u), window.removeEventListener("beforeunload", c);
    }
  };
}
const X = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])', Ni = 300, Ui = "/actions/formie/server/forms/render", It = "/api", Bi = "/actions/formie/server/forms/refresh-tokens", ji = "/actions/formie/server/submissions/submit", zi = "/actions/formie/server/submissions/set-page", Wi = "/actions/formie/server/submissions/clear-submission", Ki = "/actions/formie/file-upload/hydrate", L = _("general", "client"), Rt = /* @__PURE__ */ new Set();
function ce(e, t) {
  if (e == null || e === "")
    return t;
  const r = e.toLowerCase();
  return !(r === "false" || r === "0" || r === "off");
}
function Ge(e) {
  return e.formieRefreshTokens != null && e.formieRefreshTokens !== "" ? ce(e.formieRefreshTokens, !1) : e.formieStaticCache != null && e.formieStaticCache !== "" ? ce(e.formieStaticCache, !1) : !1;
}
function ee(e) {
  const t = e instanceof HTMLElement ? e.dataset : {};
  return {
    mode: "server-rendered",
    transport: t.formieTransport || "rest",
    formHandle: t.formieHandle,
    endpoint: t.formieEndpoint,
    staticCache: Ge(t),
    autoVisible: ce(t.formieAutoVisible, !0),
    compatibility: ce(t.formieCompatibility, !1)
  };
}
function Fe(e) {
  return e || "server-rendered";
}
function ke(e) {
  return e || "rest";
}
function Se(e) {
  return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function Gi(e, t) {
  Rt.has(e) || (Rt.add(e), L.warn(t));
}
function Ar(e, t) {
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
function re(e, t) {
  const r = (e || "").trim();
  return r ? r.includes("/actions/") ? r : Ar(t, r) : t;
}
function Ji(e, t) {
  return re(e.endpoint || t.dataset.formieEndpoint, Ui);
}
function Yi(e, t) {
  const r = (e.endpoint || t.dataset.formieEndpoint || "").trim();
  return r ? r.includes("/graphql") || r.endsWith("/api") || r.includes("/actions/graphql/") ? r : Ar(It, r) : It;
}
function dt(e, t) {
  return re(
    t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint,
    Bi
  );
}
function Ft(e, t) {
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
function Zi(e, t, r) {
  const n = r.endpoint || e.dataset.formieEndpoint, i = re(n, ji), o = t.getAttribute("action");
  t.setAttribute("action", Ft(o, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((a) => {
    const s = a.getAttribute("href"), l = re(n, zi);
    a.setAttribute("href", Ft(s, l));
  }), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((a) => {
    a.setAttribute(
      "data-formie-file-upload-hydrate-endpoint",
      re(n, Ki)
    );
  });
}
function ft(e, t) {
  if (e === "graphql" && t !== "server-rendered")
    throw new Error(`Formie ${t} mode does not support GraphQL transport yet.`);
}
function mt(e) {
  if (e == null)
    return !1;
  const t = e.trim().toLowerCase();
  return t === "true" || t === "1" || t === "";
}
function Qi(e) {
  return ce(e.dataset.formieAutomaticSubmissionState, !0);
}
function Xi(e, t, r) {
  return re(
    r.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint,
    Wi
  );
}
function eo(e) {
  return mt(e.dataset.formieUnloadWarning);
}
function kt(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function xe(e) {
  e.removeAttribute("data-formie-internal-navigation");
}
function Pt(e) {
  return e.getAttribute("data-formie-internal-navigation") !== null;
}
function qt(e, t) {
  if (!e)
    return !1;
  try {
    return new URL(e, window.location.origin).searchParams.has(t);
  } catch {
    return !1;
  }
}
function to(e) {
  return qt(window.location.href, "resumeToken") || qt(e.getAttribute("action"), "resumeToken");
}
function ro(e) {
  return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function no(e, t = 0) {
  if (!e)
    return t;
  const r = Number.parseInt(e, 10);
  return Number.isFinite(r) ? r : t;
}
function io(e) {
  return Math.max(0, no(e.dataset.formieSubmitDelay, Ni));
}
function Je(e) {
  return mt(e.dataset.formieValidationOnSubmit);
}
async function Ye(e) {
  const t = io(e);
  t < 1 || await new Promise((r) => {
    window.setTimeout(r, t);
  });
}
function Vt(e, t) {
  const r = e?.getAttribute(t)?.trim();
  if (!r)
    return null;
  try {
    return JSON.parse(r);
  } catch (n) {
    return console.error(`[formie] Failed to parse ${t}.`, n), null;
  }
}
function Ot(e, t) {
  const r = t || (e instanceof HTMLFormElement ? e : null);
  if (!r)
    return null;
  const n = Vt(r, "data-formie-modules"), i = Vt(r, "data-formie-theme");
  return !n && !i ? null : {
    modules: n || void 0,
    theme: i || void 0
  };
}
function oo(e) {
  if (!(e instanceof HTMLElement))
    return !0;
  if (!e.isConnected || e.hidden || e.closest("[hidden]"))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function ao(e, t) {
  return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function V(e) {
  const t = e, r = t.id ? `#${t.id}` : "", n = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
  return `${t.tagName ? t.tagName.toLowerCase() : "element"}${r}${n}`;
}
function pt(e, t) {
  if (t) {
    if (t.csrf?.param && t.csrf?.token) {
      const r = e.querySelector(`input[name="${t.csrf.param}"]`);
      r && (r.value = t.csrf.token);
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
async function so(e, t) {
  const r = Fe(t.mode), n = ke(t.transport);
  if (r !== "server-rendered")
    return null;
  if (t.payload)
    return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
  ft(n, r);
  const i = !!Se(e), o = t.formHandle || e.dataset.formieHandle;
  if (i || !o)
    return null;
  const a = {
    mode: r,
    endpoint: t.endpoint,
    locale: t.locale,
    siteId: t.siteId,
    theme: t.theme,
    themeConfig: t.themeConfig
  }, s = n === "graphql" ? Yi(t, e) : Ji(t, e), l = n === "graphql" ? await fn(s, o, a) : await dn(s, o, {
    ...a,
    endpoint: s
  });
  return l?.html && (e.innerHTML = l.html), l;
}
async function wr(e, t, r) {
  if (t.refreshTokens === !1)
    return;
  ft(ke(t.transport), Fe(t.mode));
  const n = t.formHandle || e.dataset.formieHandle;
  if (!n)
    return;
  const i = dt(t, e), a = r.querySelector('input[name="renderId"]')?.value || void 0, s = await nt(i, n, a);
  pt(r, s), I(e, "formie:refresh-tokens:refreshed", s);
}
function lo(e, t, r, n, i, o) {
  const a = String(
    t.dataset.formieSubmitMethod || ""
  ).trim().toLowerCase(), s = Xi(r, e, t);
  let l = !1;
  const d = t.querySelectorAll("[data-formie-action]"), p = (c) => {
    if (c) {
      t.setAttribute("data-formie-pending-action", c);
      return;
    }
    t.removeAttribute("data-formie-pending-action");
  };
  if (eo(t)) {
    const c = xi(t, {
      shouldWarn: () => !Pt(t)
    }), b = (f) => {
      if (!(f instanceof CustomEvent))
        return;
      const v = f.detail;
      v?.ok && v.action === "save" && c.scheduleBaselineCapture();
    }, A = () => {
      c.scheduleBaselineCapture();
    };
    e.addEventListener("formie:submit:result", b), t.addEventListener("formie:state:reset", A), o.push(() => {
      e.removeEventListener("formie:submit:result", b), t.removeEventListener("formie:state:reset", A), c.destroy();
    });
  }
  if (d.forEach((c) => {
    const b = (A) => {
      const f = A.currentTarget.getAttribute("data-formie-action"), v = t.querySelector('input[name="submitAction"]');
      p(f), f && v && (v.value = f);
    };
    c.addEventListener("click", b), o.push(() => {
      c.removeEventListener("click", b);
    });
  }), t.querySelectorAll("[data-formie-tab-link]").forEach((c) => {
    const b = async (A) => {
      if (a !== "ajax") {
        ro(A) && kt(t, "set-page");
        return;
      }
      A.preventDefault();
      const f = A.currentTarget, v = f?.getAttribute("data-formie-page-id"), E = f?.getAttribute("href");
      if (!(!v || !E)) {
        lt(t, v), I(e, "formie:page:navigate", {
          pageId: v,
          href: E
        });
        try {
          const y = await mn(E, t, v);
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
  }), !Qi(t)) {
    let c = !1;
    const b = () => {
      c || Pt(t) || to(t) || (c = !0, pn(s, t));
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
    const f = c.submitter, v = f?.getAttribute("data-formie-action"), E = t.getAttribute("data-formie-pending-action"), y = t.querySelector('input[name="submitAction"]'), m = v || E || y?.value || "submit";
    let g = null, S = !1;
    try {
      if (b)
        g = await yr({
          target: e,
          form: t,
          bus: n,
          validator: i,
          validateOnSubmit: Je(t),
          action: m,
          submitter: f,
          waitForSubmitDelay: Ye,
          onRefreshTokensAfterSubmit: async () => {
            await wr(e, r, t);
          },
          dispatchSubmitResult: (M) => {
            I(e, "formie:submit:result", M);
          }
        });
      else {
        if (br(t), lr(t, f), await Ye(t), g = await tr(t, m, n, {
          validator: i,
          validateOnSubmit: Je(t),
          preflightOnly: !0
        }), g.ok) {
          Kt(t, m), l = !0, kt(t, "submit"), p(null);
          let M = !1;
          const T = () => {
            M = !0, l = !1, xe(t), Me(t);
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
          if (M)
            return;
          S = !0;
          return;
        }
        Le(t, g), I(e, "formie:submit:result", g), xe(t);
      }
    } catch (M) {
      l = !1, g = {
        ok: !1,
        code: "SUBMIT_ERROR",
        message: M instanceof Error ? M.message : "Submission failed.",
        formErrors: [M instanceof Error ? M.message : "Submission failed."]
      }, Le(t, g), I(e, "formie:submit:result", g), xe(t);
    } finally {
      p(null), !b && !S && !hr(g) && Me(t);
    }
  };
  t.addEventListener("submit", u), o.push(() => {
    t.removeEventListener("submit", u);
  });
}
async function uo(e, t, r) {
  if (t.refreshTokens === !1 || !t.staticCache)
    return;
  ft(ke(t.transport), Fe(t.mode));
  const n = t.formHandle || e.dataset.formieHandle, i = dt(t, e), a = r?.querySelector('input[name="renderId"]')?.value || void 0;
  if (!n)
    return;
  const s = await nt(i, n, a);
  !s || !r || (pt(r, s), I(e, "formie:refresh-tokens:after", s));
}
function co() {
  const e = /* @__PURE__ */ new Map(), t = new vr(), r = /* @__PURE__ */ new Map(), n = /* @__PURE__ */ new Map(), i = [
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
      L.log("Unmount requested.", { target: V(f) });
      const y = r.get(f);
      y && (y(), r.delete(f));
      const m = e.get(f);
      if (!m) {
        L.log("Unmount skipped (no mounted state).", { target: V(f) });
        return;
      }
      I(f, "formie:unmount:before", {
        id: m.instance.id
      }), m.unbinds.forEach((g) => {
        g();
      }), m.unbinds = [], m.validator?.destroy(), m.validator = null;
      for (const g of m.modules)
        await g.destroy();
      m.modules = [], m.bus.clear(), e.delete(f), I(f, "formie:unmount:after", {
        id: m.instance.id
      }), L.log("Unmount complete.", { id: m.instance.id, target: V(f) });
    })().finally(() => {
      n.delete(f);
    });
    n.set(f, E), await E;
  }, a = async (f, v) => {
    L.log("Mount requested.", {
      target: V(f),
      mode: v.mode,
      autoVisible: v.autoVisible
    });
    const E = r.get(f);
    E && (E(), r.delete(f));
    const y = e.get(f);
    if (y)
      return L.log("Mount skipped (already mounted).", {
        id: y.instance.id,
        target: V(f)
      }), y.instance;
    const m = new Gt(), g = [], S = f?.id || `formie-${e.size + 1}`, M = ee(f), T = {
      ...M,
      ...v,
      mode: Fe(v.mode ?? M.mode),
      transport: ke(v.transport ?? M.transport)
    }, R = xr(T.compatibility);
    if (T.mode !== "server-rendered" && !Se(f))
      throw new Error(`Formie ${T.mode} mode is not implemented yet in the browser client.`);
    const P = await so(f, T), w = Se(f);
    T.staticCache = v.staticCache ?? Ge(w ? w.dataset : f.dataset);
    const q = Ot(f, w), N = P || q ? {
      ...P || {},
      ...q || {}
    } : null, z = N?.theme, Y = {}, fe = (N?.modules || []).filter((C) => !!C?.id && !!C?.type);
    L.log("Resolved mount payload.", {
      target: V(f),
      hasRenderPayload: !!P,
      hasEmbeddedPayload: !!q,
      moduleCount: fe.length
    });
    const ne = yt(f, z, w), k = w ? new Un(w, {
      live: mt(w.dataset.formieValidationOnFocus),
      errorMessage: w.dataset.formieErrorMessage || "",
      fieldContainerErrorClass: ne.fieldLayoutError || [],
      inputErrorClass: ne.fieldControlError || [],
      messagesClass: ne.fieldErrors || [],
      messageClass: ne.fieldError || []
    }) : null;
    if (w && k) {
      const C = w;
      C.formieValidation = k, Y.validation = k;
      const $ = {
        validator: k,
        addValidator: k.addValidator.bind(k),
        removeValidator: k.removeValidator.bind(k)
      };
      I(w, "formie:validator:ready", $), I(f, "formie:validator:ready", $);
    }
    w && ((P || T.endpoint || f.dataset.formieEndpoint) && Zi(f, w, T), J(w)), Object.keys(ne).length && I(f, "formie:theme:applied", {
      hasClasses: !0
    });
    const me = await Sr(fe, {
      registry: t,
      matchContext: {
        root: f,
        form: w,
        mode: T.mode
      },
      setupContext: {
        formId: S,
        root: f,
        form: w,
        target: f,
        scope: "form",
        state: Y,
        on: (C, $) => m.on(C, $),
        emit: (C, $) => (I(f, C, $), m.emitSafe(C, $).then((Z) => {
          Z.failed.length > 0 && L.warn("Lifecycle listeners failed.", {
            eventName: C,
            failed: Z.failed.length
          });
        }))
      }
    });
    L.log("Module setup complete.", {
      target: V(f),
      moduleInstances: me.length
    });
    const qe = {
      id: S,
      root: f,
      submit: async (C = "submit") => {
        if (L.log("Submit requested.", {
          id: S,
          target: V(f),
          action: C
        }), !w)
          return {
            ok: !1,
            code: "FORM_NOT_FOUND",
            message: "No form element found for mount target.",
            formErrors: ["No form element found for mount target."]
          };
        const $ = w.querySelector('input[name="submitAction"]');
        if ($ && ($.value = C), w.getAttribute("data-formie-loading") === "true")
          return {
            ok: !1,
            code: "SUBMIT_IN_PROGRESS",
            message: "Submission already in progress.",
            formErrors: []
          };
        const Z = w.querySelector(`[data-formie-action="${C}"]`), Q = await yr({
          id: S,
          target: f,
          form: w,
          bus: m,
          validator: k,
          validateOnSubmit: Je(w),
          action: C,
          submitter: Z,
          waitForSubmitDelay: Ye,
          onRefreshTokensAfterSubmit: async () => {
            await wr(f, T, w);
          },
          dispatchSubmitResult: (Ve) => {
            I(f, "formie:submit:result", Ve);
          }
        });
        return L.log("Submit completed.", {
          id: S,
          action: C,
          ok: Q.ok,
          code: Q.code,
          message: Q.message
        }), Q;
      },
      destroy: async () => {
        await o(f);
      },
      on: (C, $) => m.on(C, $)
    };
    w && (Jr({
      target: f,
      form: w,
      validatorDetail: k ? {
        validator: k,
        addValidator: k.addValidator.bind(k),
        removeValidator: k.removeValidator.bind(k)
      } : null,
      options: R,
      unbinds: g
    }), Gr({
      target: f,
      form: w,
      instance: qe,
      options: R,
      unbinds: g
    })), w && (lo(f, w, T, m, k, g), k && g.push(Wn(w, k, f)), await uo(f, T, w)), i.forEach((C) => {
      const $ = m.on(`formie:stage:${C}:before`, async (W) => {
        I(f, `formie:stage:${C}:before`, W);
      }), Z = m.on(`formie:stage:${C}:before`, async (W) => {
        for (const ie of me)
          ie.onBeforeStage && await ie.onBeforeStage(W);
      }), Q = m.on(`formie:stage:${C}:after`, async (W) => {
        I(f, `formie:stage:${C}:after`, W);
      }), Ve = m.on(`formie:stage:${C}:after`, async (W) => {
        const ie = W;
        for (const bt of me)
          bt.onAfterStage && await bt.onAfterStage(ie, ie.result);
      });
      g.push($, Z, Q, Ve);
    });
    const Or = m.on("formie:submit:before", async (C) => {
      I(f, "formie:submit:before", C);
    }), $r = m.on("formie:submit:after", async (C) => {
      I(f, "formie:submit:after", C);
    }), Hr = m.on("formie:submit:final:before", async (C) => {
      I(f, "formie:submit:final:before", C);
    }), _r = m.on("formie:submit:final:after", async (C) => {
      I(f, "formie:submit:final:after", C);
    });
    return g.push(
      Or,
      $r,
      Hr,
      _r
    ), e.set(f, {
      options: T,
      bus: m,
      form: w,
      validator: k,
      modules: me,
      unbinds: g,
      instance: qe
    }), I(f, "formie:mount:after", {
      id: S,
      mode: T.mode
    }), L.log("Mount complete.", {
      id: S,
      target: V(f),
      mode: T.mode
    }), qe;
  }, s = (f, v) => {
    if (!v.autoVisible || oo(f) || typeof IntersectionObserver > "u")
      return a(f, v);
    if (e.has(f))
      return Promise.resolve(e.get(f)?.instance || null);
    if (r.has(f))
      return L.log("Mount deferred (already waiting visibility).", {
        target: V(f)
      }), Promise.resolve(null);
    const E = new IntersectionObserver((y) => {
      y.some((g) => g.target === f && g.isIntersecting) && (E.disconnect(), r.delete(f), L.log("Visibility reached, proceeding mount.", {
        target: V(f)
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
      target: V(f)
    }), Promise.resolve(null);
  };
  return {
    mount: a,
    unmount: o,
    update: async (f, v) => {
      const E = e.get(f);
      if (!E)
        return a(f, {
          ...ee(f),
          ...v,
          mode: v.mode || "server-rendered"
        });
      E.options = {
        ...E.options,
        ...v
      };
      const y = v.payload?.theme || E.options.payload?.theme || Ot(f, E.form)?.theme, m = yt(f, y, E.form);
      return E.validator && (E.validator.config.fieldContainerErrorClass = m.fieldLayoutError || [], E.validator.config.inputErrorClass = m.fieldControlError || [], E.validator.config.messagesClass = m.fieldErrors || [], E.validator.config.messageClass = m.fieldError || []), Object.keys(m).length && I(f, "formie:theme:applied", {
        hasClasses: !0,
        reason: "update"
      }), E.instance;
    },
    getInstance: (f) => e.get(f)?.instance || null,
    refreshForCache: async (f) => {
      Gi(
        "refreshForCache",
        "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead."
      );
      let v = null;
      if (typeof f == "string") {
        const P = document.getElementById(f);
        P ? v = P : v = document.querySelector(`[data-formie-form-id="${f}"]`);
      } else
        v = f;
      if (!v) {
        L.warn("refreshForCache target not found.", {
          targetOrId: f
        });
        return;
      }
      const E = e.get(v), y = Se(v), m = E?.options || ee(v);
      if (!y) {
        L.warn("refreshForCache found no form element for target.", {
          target: V(v)
        });
        return;
      }
      const g = m.formHandle || v.dataset.formieHandle || y.dataset.formieHandle, S = dt(m, v), T = y.querySelector('input[name="renderId"]')?.value || void 0;
      if (!g) {
        L.warn("refreshForCache found no form handle for target.", {
          target: V(v)
        });
        return;
      }
      const R = await nt(S, g, T);
      R && (pt(y, R), I(v, "formie:refresh-tokens:after", R));
    },
    registerModule: (f, v) => t.register(f, v),
    unregisterModule: (f) => {
      t.unregister(f);
    },
    getRegisteredModules: () => t.getAll(),
    scan: async (f) => {
      const v = f || document, E = Array.from(v.querySelectorAll(X));
      L.log("Scan started.", {
        scope: v === document ? "document" : v,
        targetCount: E.length
      });
      const m = (await Promise.all(E.map((g) => {
        const S = ee(g);
        return s(g, S);
      }))).filter((g) => !!g);
      return L.log("Scan finished.", {
        mountedCount: m.length,
        deferredCount: E.length - m.length
      }), m;
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
        y.forEach((m) => {
          m.addedNodes.forEach((g) => {
            g instanceof Element && (g.matches(X) && (L.log("Observer detected new root.", {
              target: V(g)
            }), s(g, ee(g))), g.querySelectorAll(X).forEach((S) => {
              L.log("Observer detected new nested root.", {
                target: V(S)
              }), s(S, ee(S));
            }));
          }), m.removedNodes.forEach((g) => {
            g instanceof Element && (e.has(g) && (L.log("Observer detected removed root.", {
              target: V(g)
            }), o(g)), g.querySelectorAll(X).forEach((S) => {
              e.has(S) && (L.log("Observer detected removed nested root.", {
                target: V(S)
              }), o(S));
            }));
          });
        });
      });
      return E.observe(v, {
        childList: !0,
        subtree: !0
      }), () => {
        E.disconnect(), L.log("Observer stopped."), r.forEach((m, g) => {
          ao(g, v) && (m(), r.delete(g));
        });
        const y = [];
        v instanceof Element && v.matches(X) && y.push(v), v.querySelectorAll(X).forEach((m) => {
          y.push(m);
        }), y.forEach((m) => {
          e.has(m) && o(m);
        });
      };
    }
  };
}
const Tr = _("general", "module-hydrator");
async function ha(e) {
  const t = e.root, r = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form")), n = e.modules ?? [], i = e.mode ?? "server-rendered", o = e.registry ?? new vr(), a = new Gt(), s = await Sr(n, {
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
  return Tr.log("Hydrated module manifest.", {
    moduleCount: n.length,
    instanceCount: s.length,
    mode: i
  }), {
    destroy: async () => {
      await fo(s), a.clear();
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
async function fo(e) {
  for (const t of e)
    try {
      await t.destroy();
    } catch (r) {
      console.error("[formie] Failed to destroy module instance.", r), Tr.warn("Failed destroying module instance.", { error: r });
    }
}
function Pe(e) {
  return e instanceof Element;
}
function mo(e) {
  return e.ok;
}
function po(e) {
  return typeof e == "string" ? `selector "${e}"` : Pe(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function go(e) {
  const t = /* @__PURE__ */ new Set(), r = [];
  for (const n of e)
    !Pe(n) || t.has(n) || (t.add(n), r.push(n));
  return r;
}
function Ze(e) {
  return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : Pe(e) ? [e] : go(e);
}
function ho() {
  return document.readyState !== "loading" ? Promise.resolve() : new Promise((e) => {
    document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
  });
}
async function bo(e) {
  const t = Ze(e);
  return t.length > 0 || typeof e != "string" ? t : (await ho(), Ze(e));
}
function yo(e) {
  return typeof e == "string" ? document : Pe(e) ? e.getRootNode() : document;
}
function vo(e) {
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
    ...p
  } = e;
  return {
    mode: "server-rendered",
    ...p
  };
}
async function $t(e, t, r, n) {
  const i = [], o = vo(e);
  for (const a of n) {
    const s = r.get(a);
    if (s) {
      i.push(s.instance);
      continue;
    }
    const l = await t.mount(a, o), d = [];
    if (e.onReady?.(l), d.push(l.on("formie:submit:result", (p) => {
      const h = p;
      e.onResult?.(h, l), mo(h) ? e.onSuccess?.(h, l) : e.onError?.(h, l);
    })), e.onEvent)
      for (const p of Nr)
        d.push(l.on(p, (h) => {
          e.onEvent?.({
            name: p,
            payload: h
          }, l);
        }));
    r.set(a, {
      instance: l,
      unsubs: d
    }), i.push(l);
  }
  return i;
}
async function ba(e) {
  const t = e.client ?? co(), r = /* @__PURE__ */ new Map(), n = await bo(e.element);
  if (n.length === 0 && !e.allowEmpty)
    throw new Error(`Formie could not find any elements for ${po(e.element)}.`);
  await $t(e, t, r, n);
  const i = e.observe ? t.observe(yo(e.element)) : null;
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
      const o = Ze(e.element);
      return o.length === 0 ? Array.from(r.values()).map(({ instance: a }) => a) : $t(e, t, r, o);
    },
    async destroy() {
      i?.();
      const o = Array.from(r.entries());
      for (const [a, s] of o)
        s.unsubs.forEach((l) => l()), await t.unmount(a), r.delete(a);
    }
  };
}
const gt = 2e3, ya = 5e3, va = 5e3, Ea = 12e4;
async function ht(e) {
  await new Promise((t) => {
    window.setTimeout(t, Math.max(e, 0));
  });
}
async function Sa(e, {
  timeoutMs: t = 5e3,
  intervalMs: r = 30
} = {}) {
  const n = Date.now();
  for (; Date.now() - n < t; ) {
    const i = e();
    if (i)
      return i;
    await ht(r);
  }
  throw new Error("Timed out waiting for async condition.");
}
function Mr(e, t) {
  let r = null;
  return (...n) => {
    r !== null && window.clearTimeout(r), r = window.setTimeout(() => {
      e(...n);
    }, Math.max(t, 0));
  };
}
function Aa(e) {
  const t = String(e || "asyncDefer").toLowerCase();
  return {
    async: t.includes("async"),
    defer: t.includes("defer")
  };
}
function Cr(e, t) {
  const r = Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`));
  for (const n of r) {
    const i = String(n.value || "").trim();
    if (i !== "")
      return i;
  }
  return "";
}
function Qe(e, t) {
  return t.some((r) => Cr(e, r) !== "");
}
function Eo(e, t) {
  t.forEach((r) => {
    Array.from(e.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach((i) => {
      i.value = "";
    });
  });
}
function Lr(e, t, {
  value: r = "",
  container: n
} = {}) {
  let i = e.querySelector(`input[name="${t}"]`);
  return i || (i = document.createElement("input"), i.type = "hidden", i.name = t, (n || (e instanceof HTMLElement ? e : null))?.appendChild(i)), i.value = r, i;
}
async function Ir(e, t, r) {
  if (Qe(e, t))
    return !0;
  const n = Date.now() + Math.max(r, 0);
  for (; Date.now() < n; )
    if (await ht(120), Qe(e, t))
      return !0;
  return !1;
}
const So = /* @__PURE__ */ new Set([
  "handle",
  "placeholderSelector",
  "errorMessage",
  "sessionKey",
  "value"
]), Ao = "[data-formie-captcha-error-container]", wo = [
  "formie:page:navigate",
  "formie:page:navigate:after",
  "formie:submit:result"
];
function se(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function Ie(e, t) {
  return e instanceof HTMLElement && e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function Xe(e) {
  if (!(e instanceof HTMLElement) || !e.isConnected || e.hidden || e.closest("[hidden]") || e.closest("[data-formie-page-hidden]") || e.closest('[aria-hidden="true"]'))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display !== "none" && t.visibility !== "hidden";
}
function Ne(e, t) {
  const r = Ie(e, t);
  return r.find((n) => Xe(n)) || r[0] || null;
}
function To(e) {
  e.innerHTML = "";
  const t = document.createElement("div");
  return e.appendChild(t), t;
}
function et(e) {
  e?.querySelector(Ao)?.remove();
}
function Mo(e, t, r) {
  if (!e)
    return;
  et(e);
  const n = document.createElement("div");
  n.setAttribute("data-formie-captcha-error-container", ""), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true"), O(n, r || e, "fieldErrors");
  const i = document.createElement("div");
  i.setAttribute("data-formie-captcha-error", ""), i.setAttribute("role", "alert"), O(i, r || e, "fieldError"), i.textContent = t, n.appendChild(i), e.appendChild(n);
}
function Co(e) {
  const t = e instanceof CustomEvent ? e.detail : null;
  return !t || typeof t != "object" ? null : t;
}
function Lo(e, t) {
  if (!e?.captchas || typeof e.captchas != "object")
    return null;
  const r = e.captchas[t];
  return !r || typeof r != "object" ? null : r;
}
function Io(e, t, r, n) {
  const i = /* @__PURE__ */ new Set(), o = () => {
    const d = Ie(e, t), p = new Set(d.filter((h) => Xe(h)));
    d.forEach((h) => {
      p.has(h) && !i.has(h) && (i.add(h), r(h));
    }), Array.from(i).forEach((h) => {
      p.has(h) || (i.delete(h), n(h));
    });
  }, a = Mr(o, 20), s = new MutationObserver(() => {
    a();
  });
  s.observe(e, {
    childList: !0,
    subtree: !0,
    attributes: !0,
    attributeFilter: ["class", "style", "hidden", "aria-hidden", "data-formie-page-hidden"]
  });
  const l = [
    se(window, "resize", () => {
      a();
    }),
    ...wo.map((d) => se(e, d, () => {
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
    getVisible: () => Ie(e, t).filter((d) => Xe(d))
  };
}
function Ro(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Fo(e, t, {
  defaultPlaceholderSelector: r,
  defaultTokenFieldNames: n = [],
  defaultWaitForValueMs: i = gt
}) {
  const o = t || {}, a = Object.entries(o).reduce((c, [b, A]) => (So.has(b) || (c[b] = A), c), {}), s = n.map(String).filter(Boolean), l = Number(i), d = typeof o.placeholderSelector == "string" && o.placeholderSelector.trim() !== "" ? o.placeholderSelector.trim() : r, p = typeof o.errorMessage == "string" && o.errorMessage.trim() !== "" ? o.errorMessage.trim() : x("Captcha challenge must be completed."), h = typeof o.sessionKey == "string" && o.sessionKey.trim() !== "" ? o.sessionKey.trim() : null, u = typeof o.value == "string" ? o.value : null;
  return {
    handle: Ro(e, o),
    ui: {
      placeholderSelector: d,
      errorMessage: p
    },
    transport: {
      tokenFieldNames: s,
      waitForValueMs: Number.isFinite(l) ? l : i,
      sessionKey: h,
      value: u
    },
    provider: a
  };
}
function ko(e, t) {
  const r = e.form || e.root, n = t.ui.placeholderSelector, i = t.handle;
  return {
    form: e.form,
    root: e.root,
    placeholder: {
      query: () => Ie(e.root, n),
      getPrimary: () => Ne(e.root, n),
      observe: (o, a) => Io(e.root, n, o, a),
      createContainer: (o) => To(o),
      clear: (o) => {
        o && (et(o), o.innerHTML = "");
      }
    },
    errors: {
      getDefaultMessage: () => t.ui.errorMessage,
      show: (o, a) => {
        Mo(a || Ne(e.root, n), o || t.ui.errorMessage, e.form || e.root);
      },
      clear: (o) => {
        et(o || Ne(e.root, n));
      }
    },
    tokens: {
      names: t.transport.tokenFieldNames,
      has: (o = t.transport.tokenFieldNames, a = r) => Qe(a, o),
      read: (o = t.transport.tokenFieldNames[0], a = r) => o ? Cr(a, o) : "",
      write: (o, {
        names: a = t.transport.tokenFieldNames,
        root: s = r,
        container: l = e.form
      } = {}) => {
        a.forEach((d) => {
          Lr(s, d, {
            value: o,
            container: l
          });
        });
      },
      clear: (o = t.transport.tokenFieldNames, a = r) => {
        Eo(a, o);
      },
      wait: (o = t.transport.waitForValueMs, a = t.transport.tokenFieldNames, s = r) => Ir(s, a, o)
    },
    refresh: {
      providerHandle: i,
      onTokensRefreshed: (o) => {
        const a = ["formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"].map((s) => se(e.root, s, (l) => {
          const d = Co(l), p = Lo(d, i);
          p && o(p);
        }));
        return () => {
          a.forEach((s) => {
            s();
          });
        };
      }
    },
    events: {
      onRoot: (o, a) => se(e.root, o, a),
      onForm: (o, a) => e.form ? se(e.form, o, a) : () => {
      }
    }
  };
}
const G = _("captchas");
function Rr({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = gt,
  setup: i
}) {
  return {
    id: e,
    kind: "captcha",
    match: () => !0,
    setup: async (o) => {
      const a = Fo(e, o.options || {}, {
        defaultPlaceholderSelector: t,
        defaultTokenFieldNames: r,
        defaultWaitForValueMs: n
      });
      G.log("Setup module.", {
        moduleId: e,
        placeholderSelector: a.ui.placeholderSelector,
        tokenFieldNames: a.transport.tokenFieldNames
      });
      const s = ko(o, a);
      return i({
        ...o,
        options: a,
        services: s
      });
    }
  };
}
function Po({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = gt
}) {
  return Rr({
    id: e,
    defaultPlaceholderSelector: t,
    defaultTokenFieldNames: r,
    defaultWaitForValueMs: n,
    setup: async ({ services: i, options: o, root: a }) => {
      const s = [];
      let l = i.placeholder.getPrimary(), d = o.transport.sessionKey, p = o.transport.value || "";
      const h = (c) => {
        !c || !d || (c.innerHTML = "", Lr(c, d, {
          value: p,
          container: c
        }));
      }, u = i.placeholder.observe(
        (c) => {
          l = c, G.log("Passive placeholder visible.", {
            moduleId: e
          }), h(c);
        },
        (c) => {
          l === c && (l = i.placeholder.getPrimary()), c.innerHTML = "";
        }
      );
      return s.push(u.cleanup), h(l), s.push(i.refresh.onTokensRefreshed((c) => {
        d = typeof c.sessionKey == "string" && c.sessionKey.trim() !== "" ? c.sessionKey.trim() : d, p = typeof c.value == "string" ? c.value : "";
        const b = i.placeholder.getPrimary() || l;
        l = b, h(b);
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
          if (!await Ir(a, b, o.transport.waitForValueMs)) {
            const f = i.errors.getDefaultMessage();
            i.errors.show(f, l), G.warn("Passive captcha missing token.", {
              moduleId: e,
              tokenFieldNames: b
            }), c.abort(f);
          }
        }
      };
    }
  });
}
function qo(e) {
  return Rr({
    id: e.id,
    defaultPlaceholderSelector: e.defaultPlaceholderSelector,
    defaultTokenFieldNames: e.defaultTokenFieldNames,
    setup: async (t) => {
      const r = [], n = /* @__PURE__ */ new Map(), i = /* @__PURE__ */ new Map();
      let o = t.services.placeholder.getPrimary(), a = !1, s = null;
      const l = async () => (s || (G.log("Loading captcha provider API.", {
        moduleId: e.id
      }), s = e.load(t)), s), d = async (c) => {
        const b = n.get(c);
        if (t.services.errors.clear(c), !b) {
          c.innerHTML = "";
          return;
        }
        const A = await l();
        e.unmount && await e.unmount({
          api: A,
          widget: b,
          placeholder: c,
          services: t.services,
          options: t.options,
          provider: t.options.provider
        }), n.delete(c), c.innerHTML = "", t.services.tokens.clear(), G.log("Unmounted captcha placeholder widget.", {
          moduleId: e.id
        }), o === c && (o = t.services.placeholder.getPrimary());
      }, p = async (c) => {
        if (a || n.has(c) || i.has(c))
          return;
        const b = (async () => {
          const A = await l();
          if (a || n.has(c))
            return;
          const f = t.services.placeholder.createContainer(c), v = await e.mount({
            api: A,
            placeholder: c,
            container: f,
            services: t.services,
            options: t.options,
            provider: t.options.provider
          });
          n.set(c, v), o = c, G.log("Mounted captcha placeholder widget.", {
            moduleId: e.id
          });
        })().finally(() => {
          i.delete(c);
        });
        i.set(c, b), await b;
      }, h = t.services.placeholder.observe(
        (c) => {
          o = c, p(c);
        },
        (c) => {
          d(c);
        }
      );
      r.push(h.cleanup);
      const u = async (c) => {
        const A = h.getVisible();
        if (e.reset) {
          const f = await l();
          for (const v of A) {
            const E = n.get(v);
            if (!E) {
              await p(v);
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
          h.reconcile();
          return;
        }
        for (const f of Array.from(n.keys()))
          await d(f);
        for (const f of A)
          await p(f);
        h.reconcile();
      };
      return r.push(t.services.events.onRoot("formie:submit:result", (c) => {
        const b = c instanceof CustomEvent ? c.detail : null;
        b?.stage !== "validate" && (b?.ok === !1 && b?.stage === "screen" || b?.ok !== !0 && u("submit-result"));
      })), t.form && r.push(t.services.events.onForm(je("reset"), () => {
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
          const b = h.getVisible();
          if (b.length === 0)
            return;
          let A = b.find((E) => E === o) || b[0];
          await p(A), A = o || A, t.services.errors.clear(A);
          const f = n.get(A);
          if (!f) {
            const E = t.services.errors.getDefaultMessage();
            t.services.errors.show(E, A), G.warn("Captcha widget unavailable at screen stage.", {
              moduleId: e.id
            }), c.abort(E);
            return;
          }
          const v = await l();
          await e.screen({
            api: v,
            widget: f,
            placeholder: A,
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
const wa = qo, Ta = Po, Ht = 2500, Vo = {
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
function Oo(e) {
  return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function $o(e) {
  return `fields[${Oo(e)}]`;
}
function Ho(e, t) {
  const r = $o(t), n = Array.from(e.querySelectorAll(`[name="${r}"]`)), i = Array.from(e.querySelectorAll(`[name="${r}[]"]`));
  return (i.length ? i : n).filter((o) => o instanceof HTMLElement);
}
function _t(e, t) {
  const r = Ho(e, t);
  for (const n of r) {
    const o = n.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
    if (o)
      return o;
  }
  return "";
}
function Ue(e) {
  let t = e.replace(/[^\d.,-]/g, "");
  const r = t.includes(","), n = t.includes(".");
  return r && n ? t = t.replace(/\./g, "").replace(/,/, ".") : r && !n ? t = t.replace(/,/, ".") : t = t.replace(/,/g, ""), parseFloat(t);
}
function _o(e) {
  return e.replace(/^\{field:/, "").replace(/^\{/, "").replace(/\}$/, "").trim();
}
function de(e) {
  return _o(e).replace(/\]/g, "").split("[").join(".").replace(/\.+/g, ".").replace(/^\./, "").replace(/\.$/, "");
}
function Fr(e) {
  const r = de(e).split(".").filter(Boolean);
  if (!r.length)
    return "";
  const [n, ...i] = r;
  return `fields[${n}]${i.map((o) => `[${o}]`).join("")}`;
}
function Do(e) {
  const r = String(e || "").trim().match(/^fields\[([^\]]+)\](.*)$/);
  if (!r)
    return "";
  const n = r[1] || "", i = r[2] || "", o = Array.from(i.matchAll(/\[([^\]]+)\]/g)).map((a) => a[1] || "").filter(Boolean);
  return [n, ...o].join(".");
}
function xo(e) {
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
function kr(e) {
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
      key: de(t),
      selector: "",
      defaultValue: "",
      transforms: [],
      isToken: !1,
      isValid: !0
    };
  const n = (r[1] || "").trim().toLowerCase(), i = (r[2] || "").trim(), [o, a = ""] = i.split("|", 2), { source: s, transforms: l } = xo(o || "");
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
  const d = s.indexOf(":"), p = d === -1 ? s : s.slice(0, d), h = d === -1 ? "" : s.slice(d + 1), u = de(p);
  return {
    raw: t,
    target: "field",
    key: u,
    selector: h.trim(),
    defaultValue: a.trim(),
    transforms: l,
    isToken: !0,
    isValid: u !== ""
  };
}
function No(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement || e instanceof HTMLSelectElement;
}
function Uo(e, t, r) {
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
function Bo(e) {
  const t = /* @__PURE__ */ new Map();
  return Array.from(e.querySelectorAll("[name]")).filter((n) => No(n)).forEach((n) => {
    const i = Do(n.name);
    i && Uo(t, i, n);
  }), t;
}
function jo(e) {
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
function Pr(e, t) {
  return e.get(de(t)) || null;
}
function zo(e, t) {
  const r = kr(e), n = r.key, i = Pr(t, n);
  if (!i)
    return {
      key: n,
      value: r.defaultValue,
      found: !1
    };
  const o = jo(i.inputs);
  return {
    key: n,
    value: o === "" && r.defaultValue !== "" ? r.defaultValue : o,
    found: !0
  };
}
function Ma(e, t, r) {
  const n = kr(e), i = n.key;
  if (!i)
    return {
      key: i,
      value: n.defaultValue,
      found: !1
    };
  const o = r ? Pr(r, i) : null, s = (o?.names?.length ? o.names : [Fr(i)]).flatMap((l) => {
    const d = t.getAll(l).map((p) => String(p ?? ""));
    return d.length ? d : t.getAll(`${l}[]`).map((p) => String(p ?? ""));
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
function qr(e, t) {
  const r = t.replace(/"/g, '\\"');
  return e.querySelector(`input[name$="[${r}]"]`) || e.querySelector(`input[name$="${r}"]`);
}
function Ae(e, t) {
  const r = t.find((n) => {
    const i = qr(e, n);
    return !i || String(i.value || "").trim() === "";
  });
  return {
    ok: !r,
    missingSuffix: r
  };
}
async function Vr(e, t, r) {
  const n = Ae(e, t);
  if (n.ok)
    return n;
  const i = Date.now() + Math.max(r, 0);
  for (; Date.now() < i; ) {
    await ht(120);
    const o = Ae(e, t);
    if (o.ok)
      return o;
  }
  return Ae(e, t);
}
const Wo = /* @__PURE__ */ new Set([
  "handle",
  "requiredInputSuffixes",
  "waitForValueMs",
  "errorMessage"
]), Dt = "[data-payment-success]", xt = "[data-payment-error]";
function Ko(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function Go(e, t, r) {
  const n = t || {}, i = Object.entries(n).reduce((l, [d, p]) => (Wo.has(d) || (l[d] = p), l), {}), o = Array.isArray(n.requiredInputSuffixes) ? n.requiredInputSuffixes.map(String).filter(Boolean) : r.defaultRequiredInputSuffixes || [], a = Number(n.waitForValueMs ?? r.defaultWaitForValueMs ?? Ht), s = typeof n.errorMessage == "string" && n.errorMessage.trim() !== "" ? n.errorMessage.trim() : "Payment authorization is incomplete.";
  return {
    handle: Ko(e, n),
    transport: {
      requiredInputSuffixes: o,
      waitForValueMs: Number.isFinite(a) ? a : Ht,
      errorMessage: s
    },
    provider: i
  };
}
function Nt(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function Jo(e, t) {
  const r = e.target, n = e.form, i = e.root, o = n || i, a = t.transport.requiredInputSuffixes, s = () => Bo(n || i), l = (y) => {
    const g = zo(y, s()).value;
    return Array.isArray(g) ? g[0] || "" : String(g || "");
  };
  return {
    root: i,
    form: n,
    field: r,
    updateInputs: (y, m) => {
      const g = Array.isArray(y) ? y : [y];
      for (const S of g) {
        const M = qr(o, S) ?? r.querySelector(`input[name*="${S}"]`);
        M && (M.value = m);
      }
    },
    addError: (y) => {
      const m = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = m.querySelector(xt);
      g && g.remove();
      const S = document.createElement("div");
      S.setAttribute("data-payment-error", ""), S.textContent = y, O(S, n || i, "fieldError"), m.appendChild(S);
    },
    removeError: () => {
      r.querySelector(xt)?.remove();
    },
    addSuccess: (y) => {
      const m = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = m.querySelector(Dt);
      g && g.remove();
      const S = document.createElement("div");
      S.setAttribute("data-payment-success", ""), S.textContent = y, O(S, n || i, "successMessage"), m.appendChild(S);
    },
    removeSuccess: () => {
      r.querySelector(Dt)?.remove();
    },
    hasToken: () => Ae(o, a).ok,
    waitForToken: (y = t.transport.waitForValueMs) => Vr(o, a, y).then((m) => m.ok),
    getFieldValue: (y, m = "string") => {
      const g = l(y);
      return m === "float" || m === "int" || m === "number" ? Ue(g) : g;
    },
    resolveAmount: (y) => {
      const m = n || i, S = String(y.type || "").toLowerCase() === "dynamic" && typeof y.variable == "string" && y.variable.trim() !== "", M = y.value ?? (S ? y.variable : y.fixed), T = String(M ?? "").trim(), R = typeof M == "number" ? M : Ue(T);
      if (Number.isFinite(R) && R > 0)
        return { ok: !0, value: R };
      if (T !== "") {
        const P = l(T), w = Ue(P);
        if (Number.isFinite(w) && w > 0)
          return { ok: !0, value: w };
        const q = _t(m, T);
        if (!P)
          return {
            ok: !1,
            error: q ? x('Provide a value for "{label}" to proceed.', { label: q }) : x("Provide a payment amount to proceed.")
          };
      }
      return {
        ok: !1,
        error: x("Payment amount must be greater than 0.")
      };
    },
    resolveCurrency: (y) => {
      const m = n || i, S = String(y.type || "").toLowerCase() === "dynamic" && typeof y.variable == "string" && y.variable.trim() !== "", M = y.value ?? (S ? y.variable : y.fixed ?? y.defaultCurrency ?? ""), T = String(M ?? "").trim(), R = T.toUpperCase();
      if (/^[A-Z]{3}$/.test(R) && !S)
        return { ok: !0, value: R };
      if (T !== "") {
        const P = String(l(T) || "").trim(), w = P.toUpperCase();
        if (/^[A-Z]{3}$/.test(w))
          return { ok: !0, value: w };
        const q = _t(m, T);
        if (!P)
          return {
            ok: !1,
            error: q ? x('Provide a value for "{label}" to proceed.', { label: q }) : x("Provide a payment currency to proceed.")
          };
      }
      return {
        ok: !1,
        error: x("Payment currency must be a valid 3-letter code.")
      };
    },
    watchFieldValueChanges: (y, m, g = 600) => {
      const S = n || i, M = y.map((q) => String(q || "").trim()).filter(Boolean);
      if (M.length === 0)
        return () => {
        };
      const T = s(), R = /* @__PURE__ */ new Set();
      M.forEach((q) => {
        const N = de(q), z = T.get(N);
        if (z?.names?.length) {
          z.names.forEach((fe) => {
            R.add(fe);
          });
          return;
        }
        const Y = Fr(N);
        Y && (R.add(Y), R.add(`${Y}[]`));
      });
      const P = Mr(() => {
        m();
      }, g), w = (q) => {
        const z = q.target?.name || "";
        !z || !R.has(z) || P();
      };
      return S.addEventListener("input", w), S.addEventListener("change", w), () => {
        S.removeEventListener("input", w), S.removeEventListener("change", w);
      };
    },
    triggerSubmit: () => {
      n && n.setAttribute("data-formie-internal-resubmit", "true"), n && typeof n.requestSubmit == "function" ? n.requestSubmit() : n && n.submit();
    },
    releaseSubmitLoading: () => {
      n && (n.removeAttribute("data-formie-internal-resubmit"), Me(n));
    },
    getBillingData: (y) => {
      const m = {};
      if (!y || typeof y != "object")
        return { billing_details: m };
      if (y.billingName) {
        const g = l(y.billingName);
        g && (m.name = g);
      }
      if (y.billingEmail) {
        const g = l(y.billingEmail);
        g && (m.email = g);
      }
      if (y.billingAddress) {
        const g = y.billingAddress, S = {}, M = l(`${g}.address1`), T = l(`${g}.address2`), R = l(`${g}.address3`), P = l(`${g}.city`), w = l(`${g}.zip`), q = l(`${g}.state`), N = l(`${g}.country`);
        M && (S.line1 = M), T && (S.line2 = T), R && (S.line3 = R), P && (S.city = P), w && (S.postal_code = w), q && (S.state = q), N && (S.country = N), Object.keys(S).length && (m.address = S);
      }
      return { billing_details: m };
    },
    events: {
      onForm: (y, m) => n ? Nt(n, y, m) : () => {
      },
      onRoot: (y, m) => Nt(i, y, m)
    }
  };
}
const U = _("payments");
function Ut(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function Yo(e) {
  const t = e.defaultRequiredInputSuffixes ?? Vo[e.id] ?? [];
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
      const a = Go(
        e.id,
        r.options || {},
        {
          defaultRequiredInputSuffixes: t
        }
      ), s = Jo(r, a), l = {
        ...r,
        options: a,
        services: s
      }, d = [];
      let p = null, h = null, u = null, c = null;
      const b = async () => (p || (U.log("Loading payment provider API.", { moduleId: e.id }), p = e.load(l)), p), A = async () => {
        if (!e.mount || h || !Ut(r.target))
          return;
        const E = await b();
        try {
          h = await e.mount({
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
      e.mount && Ut(r.target) && await A(), ["formie:page:navigate:after", "formie:submit:result"].forEach((E) => {
        const y = () => {
          A();
        };
        r.root.addEventListener(E, y), d.push(() => {
          r.root.removeEventListener(E, y);
        });
      });
      const v = async () => {
        if (U.log("Destroying payment module.", {
          moduleId: e.id,
          handle: a.handle
        }), d.forEach((E) => E()), h && e.unmount) {
          const E = await b();
          await e.unmount({
            api: E,
            widget: h,
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
          await A();
          const g = await b();
          if (e.onBeforeAuthorize) {
            c || (c = (async () => e.onBeforeAuthorize({
              api: g,
              widget: h,
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
          const S = r.form || r.root, M = await Vr(
            S,
            a.transport.requiredInputSuffixes,
            a.transport.waitForValueMs
          );
          M.ok || (U.warn("Required payment input(s) missing.", {
            moduleId: e.id,
            handle: a.handle,
            missingSuffix: M.missingSuffix
          }), E.abort(a.transport.errorMessage));
        },
        onAfterStage: async (E, y) => {
          E.stage !== "dispatch" || !e.onAfterSubmit || await e.onAfterSubmit({
            field: r.target,
            services: s,
            options: a,
            provider: a.provider,
            result: y
          });
        }
      };
    }
  };
}
const Ca = Yo, Zo = "[data-formie-address-autocomplete-input]", Bt = "[data-formie-address-location]", Qo = {
  autoComplete: "[data-formie-address-autocomplete-input]",
  address1: "[data-formie-address-line1-input]",
  address2: "[data-formie-address-line2-input]",
  address3: "[data-formie-address-line3-input]",
  city: "[data-formie-address-city-input]",
  state: "[data-formie-address-state-input]",
  zip: "[data-formie-address-zip-input]",
  country: "[data-formie-address-country-input]"
}, Xo = /* @__PURE__ */ new Set(["handle"]);
function ea(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function ta(e, t) {
  const r = t || {}, n = Object.entries(r).reduce((i, [o, a]) => (Xo.has(o) || (i[o] = a), i), {});
  return {
    handle: ea(e, r),
    provider: n
  };
}
function ra(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function na(e) {
  const t = e.target, r = e.form, n = e.root, i = Zo;
  return {
    root: n,
    field: t,
    form: r,
    input: {
      getAutocomplete: () => t.querySelector(i),
      setValue: (o, a, s) => {
        const l = Qo[o], d = t.querySelector(l);
        d && (d.value = a || s || "");
      }
    },
    location: {
      getButton: () => t.querySelector(Bt),
      onUseLocation: (o) => {
        const a = t.querySelector(Bt);
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
      onField: (o, a) => ra(t, o, a)
    }
  };
}
const te = _("address");
function jt(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function ia(e) {
  return {
    id: e.id,
    kind: "address",
    match: (t) => !!t.target.querySelector("[data-formie-address-autocomplete-input]"),
    setup: async (t) => {
      const r = ta(e.id, t.options || {}), n = na(t);
      te.log("Setup module.", {
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
        ), te.warn("Autocomplete input missing; skipping module.", {
          moduleId: e.id
        }), {
          destroy: () => {
          }
        };
      const d = async () => (a || (te.log("Loading provider API.", {
        moduleId: e.id
      }), a = e.load(i)), a), p = async () => {
        if (s || !jt(t.target))
          return;
        const c = await d();
        s = await e.mount({
          api: c,
          field: t.target,
          services: n,
          options: r,
          provider: r.provider
        }), te.log("Widget mounted.", {
          moduleId: e.id
        });
      };
      jt(t.target) && await p(), ["formie:page:navigate:after", "formie:submit:result"].forEach((c) => {
        const b = () => {
          p();
        };
        t.root.addEventListener(c, b), o.push(() => {
          t.root.removeEventListener(c, b);
        });
      });
      const u = n.location.onUseLocation((c) => {
        e.onCurrentLocation && (async () => {
          if (await p(), !s)
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
          if (te.log("Destroying module.", {
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
            }), te.log("Widget unmounted.", {
              moduleId: e.id
            });
          }
        }
      };
    }
  };
}
const La = ia;
export {
  ga as $,
  Qo as A,
  Un as B,
  ya as C,
  oa as D,
  Gr as E,
  Nr as F,
  Jr as G,
  co as H,
  Yr as I,
  Zr as J,
  ba as K,
  Dr as L,
  vr as M,
  ir as N,
  Br as O,
  Ur as P,
  ha as Q,
  Do as R,
  Wt as S,
  pa as T,
  aa as U,
  kr as V,
  Ma as W,
  xr as X,
  da as Y,
  ma as Z,
  Be as _,
  wa as a,
  Aa as b,
  Ea as c,
  La as d,
  Ta as e,
  va as f,
  la as g,
  _ as h,
  Bo as i,
  Fr as j,
  sa as k,
  ua as l,
  je as m,
  de as n,
  Re as o,
  Mr as p,
  fa as q,
  zo as r,
  ht as s,
  ue as t,
  x as u,
  Ca as v,
  Sa as w,
  ca as x,
  O as y,
  le as z
};
