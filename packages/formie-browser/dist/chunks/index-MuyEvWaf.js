const Pr = [
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
], Uo = [
  { legacyEvent: "formieValidatorInitialized", canonicalEvent: "formie:validator:ready", disposition: "safe" },
  { legacyEvent: "formieValidatorDestroyed", canonicalEvent: "formie:validator:destroy", disposition: "safe" },
  { legacyEvent: "formieValidatorShowError", canonicalEvent: "formie:validator:show-error", disposition: "safe" },
  { legacyEvent: "formieValidatorClearError", canonicalEvent: "formie:validator:clear-error", disposition: "safe" }
];
function $r(e) {
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
const Vr = [
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
function Ne(e) {
  return e;
}
function zo(e) {
  return e;
}
function jo(e, t) {
  return `formie:field:${e}:${t}`;
}
function pe(e) {
  return `formie:validator:${e}`;
}
function Bo(e, t) {
  return `formie:address:${e}:${t}`;
}
function Wo(e) {
  return `formie:file-upload:${e}`;
}
function Ko(e, t) {
  return `formie:payment:${e}:${t}`;
}
function Ue(e) {
  return `formie:state:${e}`;
}
function Or(e, t) {
  return `formie:module:${e}:${t}`;
}
function Hr(e) {
  return `formie:module:${e}`;
}
function _r(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function Dr(e, t) {
  if (e.canonicalEvent !== "formie:submit:result")
    return !0;
  const r = t;
  return e.legacyEvent === "onAfterFormieSubmit" ? !!r?.ok : e.legacyEvent === "onFormieSubmitError" ? r?.ok === !1 : !0;
}
function xr(e, t) {
  const r = t && typeof t == "object" ? t : {}, n = typeof r.pageId == "string" ? r.pageId : "", i = Array.from(e.querySelectorAll("[data-formie-page-id]")), o = i.findIndex((a) => a.getAttribute("data-formie-page-id") === n);
  return {
    data: {
      nextPageId: n,
      nextPageIndex: o,
      totalPages: i.length
    }
  };
}
function Nr(e, t, r, n, i) {
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
  } : e.legacyEvent === "onFormiePageToggle" ? xr(n, t) : t;
}
function Ur({
  target: e,
  form: t,
  instance: r,
  options: n,
  unbinds: i
}) {
  n.legacyDomEvents && Pr.forEach((o) => {
    const a = (s) => {
      if (!(s instanceof CustomEvent) || !Dr(o, s.detail))
        return;
      const l = o.target === "document" ? document : t;
      _r(l, o.legacyEvent, Nr(o, s.detail, e, t, r));
    };
    e.addEventListener(Ne(o.canonicalEvent), a), i.push(() => {
      e.removeEventListener(Ne(o.canonicalEvent), a);
    });
  });
}
function ge(e, t, r) {
  e.dispatchEvent(new CustomEvent(t, {
    bubbles: !0,
    detail: r
  }));
}
function $e(e, t) {
  return !!e && typeof e == "object" && e.validator === t;
}
function zr({
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
  const d = (c) => {
    !(c instanceof CustomEvent) || !$e(c.detail, o) || ge(document, "formieValidatorDestroyed", {
      ...l,
      ...c.detail
    });
  }, p = (c) => {
    !(c instanceof CustomEvent) || !$e(c.detail, o) || !(c.target instanceof Element) || t.contains(c.target) && ge(c.target, "formieValidatorShowError", {
      ...c.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  }, y = (c) => {
    !(c instanceof CustomEvent) || !$e(c.detail, o) || !(c.target instanceof Element) || t.contains(c.target) && ge(c.target, "formieValidatorClearError", {
      ...c.detail,
      addValidator: a,
      removeValidator: s,
      form: t,
      target: e
    });
  };
  document.addEventListener("formie:validator:destroy", d), document.addEventListener("formie:validator:show-error", p), document.addEventListener("formie:validator:clear-error", y), i.push(() => {
    document.removeEventListener("formie:validator:destroy", d), document.removeEventListener("formie:validator:show-error", p), document.removeEventListener("formie:validator:clear-error", y);
  });
}
function I(e, t, r) {
  e.dispatchEvent(new CustomEvent(Ne(t), {
    bubbles: !0,
    detail: r
  }));
}
function Ut() {
  return globalThis;
}
function zt() {
  return Ut().__FORMIE_DEBUG__ === !0;
}
function Go(e) {
  Ut().__FORMIE_DEBUG__ = e;
}
function jr(e, t, r) {
  if (zt()) {
    if (typeof r > "u") {
      console.log(`[formie:${e}] ${t}`);
      return;
    }
    console.log(`[formie:${e}] ${t}`, r);
  }
}
function Br(e, t, r) {
  if (zt()) {
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
      jr(r, n, i);
    },
    warn: (n, i) => {
      Br(r, n, i);
    }
  };
}
const ve = _("general", "page-client-event"), Wr = "data-formie-client-event";
function Kr(e) {
  return typeof window < "u" && window.CSS?.escape ? window.CSS.escape(e) : e.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
}
function Gr(e) {
  const r = e.querySelector('input[name="pageId"]')?.value?.trim();
  if (r)
    return r;
  const i = e.querySelector("[data-formie-page]:not([data-formie-page-hidden])")?.getAttribute("data-formie-page-id")?.trim();
  return i || e.querySelector("[data-formie-page]")?.getAttribute("data-formie-page-id")?.trim() || null;
}
function Jr(e) {
  if (!e?.trim())
    return null;
  try {
    const t = JSON.parse(e);
    return t && typeof t == "object" ? t : null;
  } catch {
    return ve.warn("Invalid data-formie-client-event JSON.", {
      rawPreview: e.slice(0, 80)
    }), null;
  }
}
function Yr(e) {
  const t = {};
  return e.forEach((r) => {
    const n = typeof r.label == "string" ? r.label.trim() : "";
    n && (t[n] = typeof r.value == "string" ? r.value : "");
  }), t;
}
function jt(e, t) {
  if (t !== "submit")
    return;
  const r = Gr(e);
  if (!r) {
    ve.log("No submitted page id; skipping client event.");
    return;
  }
  const n = e.querySelector(
    `[data-formie-page][data-formie-page-id="${Kr(r)}"]`
  );
  if (!n) {
    ve.log("No page section for id; skipping client event.", { pageId: r });
    return;
  }
  const i = n.getAttribute(Wr);
  if (i === null)
    return;
  const o = Jr(i);
  if (!o || !Array.isArray(o.fields))
    return;
  const a = Yr(o.fields), s = window;
  s.dataLayer = s.dataLayer || [], s.dataLayer.push(a), e.dispatchEvent(new CustomEvent("formie:client-event", {
    bubbles: !0,
    detail: { payload: a }
  })), ve.log("Dispatched page client event.", {
    pageId: r,
    keys: Object.keys(a)
  });
}
const Ae = /* @__PURE__ */ new WeakMap(), Zr = "[data-formie-form], [data-formie], form";
function Qr(e) {
  return e ? (Array.isArray(e) ? e : [e]).flatMap((r) => String(r).split(/\s+/)).map((r) => r.trim()).filter(Boolean) : [];
}
function Xe(e) {
  return Array.from(new Set(e));
}
function Xr(e) {
  if (!e)
    return {};
  const t = Ae.get(e);
  if (t)
    return t;
  const r = e.closest(Zr);
  return r ? Ae.get(r) || {} : {};
}
function en(e) {
  const t = {};
  return Object.entries(e || {}).forEach(([r, n]) => {
    const i = Xe(Qr(n));
    i.length && (t[r] = i);
  }), t;
}
function gt(e, t, r) {
  const n = en(t), i = r || (e instanceof HTMLFormElement ? e : e.querySelector("form"));
  return Ae.set(e, n), i && Ae.set(i, n), n;
}
function et(e, t) {
  return Xr(e)[t] || [];
}
function V(e, t, ...r) {
  const n = Xe(r.flatMap((i) => et(t, i)));
  n.length && e.classList.add(...n);
}
function le(e, t, ...r) {
  const n = Xe(r.flatMap((i) => et(t, i)));
  n.length && e.classList.remove(...n);
}
function ue(e, t, r, n) {
  et(t, r).forEach((i) => {
    e.classList.toggle(i, n);
  });
}
function tn(e, t) {
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
    tn(n, !!i && t.has(i));
  });
}
class Bt {
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
async function Wt(e, t = {}) {
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
async function Ie(e, t = {}) {
  const r = await Wt(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.json();
}
async function Jo(e, t = {}) {
  const r = await Wt(e, t);
  if (!r.ok)
    throw new Error(`Request failed (${r.status}) for ${String(e)}`);
  return r.text();
}
const H = _("general", "transport");
function rn(e) {
  const t = {};
  return ["theme", "themeConfig", "locale", "siteId"].forEach((r) => {
    e[r] !== void 0 && (t[r] = e[r]);
  }), t;
}
function Kt(e, t = "", r = {}) {
  if (Array.isArray(e)) {
    const n = e.map((i) => typeof i == "string" ? i : String(i ?? "")).filter((i) => i.trim() !== "");
    return t && n.length && (r[t] = (r[t] || []).concat(n)), r;
  }
  return e && typeof e == "object" && Object.entries(e).forEach(([n, i]) => {
    const o = t ? `${t}.${n}` : n;
    Kt(i, o, r);
  }), r;
}
function nn(e, t) {
  const r = e.success === !0, n = e.keepSubmitLoading === !0, i = e.errors, o = Kt(i || {}), a = o.form || [], s = {};
  Object.entries(o).forEach(([y, c]) => {
    if (y === "form")
      return;
    const u = y.split(".")[0];
    s[u] = (s[u] || []).concat(c);
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
async function on(e, t, r = {}) {
  const n = JSON.stringify({
    handle: t,
    renderOptions: r
  });
  H.log("requestRender start.", { endpoint: e, handle: t });
  const i = await Ie(e, {
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
async function an(e, t, r = {}) {
  const i = JSON.stringify({
    query: `
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`,
    variables: {
      handle: t,
      input: rn(r)
    }
  });
  H.log("requestGraphqlRender start.", { endpoint: e, handle: t });
  const o = await Ie(e, {
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
async function tt(e, t, r) {
  const n = new URL(e, window.location.origin);
  n.searchParams.set("handle", t), r && n.searchParams.set("renderId", r), H.log("requestRefreshTokens start.", {
    endpoint: n.toString(),
    handle: t,
    hasRenderId: !!r
  });
  const i = await Ie(n.toString());
  return H.log("requestRefreshTokens complete.", {
    hasRefreshTokens: !!i.refreshTokens
  }), i.refreshTokens || i;
}
async function sn(e, t, r) {
  const n = new URL(e, window.location.origin), i = new FormData();
  if (r && i.append("pageId", r), t) {
    ["handle", "renderId", "draftContextToken", "draftContext", "continuationToken"].forEach((d) => {
      const y = t.querySelector(`input[name="${d}"]`)?.value?.trim();
      y && i.append(d, y);
    });
    const l = t.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value?.trim();
    l && i.append("CRAFT_CSRF_TOKEN", l);
  }
  H.log("requestSetPage start.", {
    requestUrl: n.toString(),
    pageId: r || null
  });
  const o = await Ie(n.toString(), {
    method: "POST",
    body: i
  });
  return H.log("requestSetPage complete.", o), o;
}
function ln(e, t) {
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
async function un(e, t) {
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
  const s = await o.json(), l = nn(s, i);
  return H.log("submitForm JSON response normalized.", {
    ok: l.ok,
    code: l.code,
    hasRedirect: !!l.redirect?.url,
    hasSubmitData: Array.isArray(l.submitData) && l.submitData.length > 0
  }), l;
}
const cn = ["prepare", "normalize", "validate", "screen", "authorize", "dispatch", "finalize"], dn = ["prepare", "normalize", "validate", "screen", "authorize"], R = _("general", "pipeline");
function Ve(e, t) {
  return {
    ok: !1,
    stage: e,
    code: "ABORTED",
    message: t || "Submission aborted.",
    formErrors: [t || "Submission aborted."]
  };
}
function rt(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function fn(e) {
  const t = rt(e);
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
function Gt(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLSelectElement || e instanceof HTMLTextAreaElement;
}
function Jt(e) {
  return !(!e.name || e.disabled || e instanceof HTMLInputElement && (e.type === "submit" || e.type === "button" || e.type === "reset" || e.type === "image" || (e.type === "checkbox" || e.type === "radio") && !e.checked || e.type === "file" && (!e.files || e.files.length === 0)));
}
function Yt(e, t) {
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
function mn(e, t) {
  t.querySelectorAll("input, select, textarea").forEach((r) => {
    const n = Gt(r) ? r : null;
    !n || n.closest("[data-formie-page]") || Jt(n) && Yt(e, n);
  });
}
function pn(e, t) {
  const r = /* @__PURE__ */ new Set();
  return t.querySelectorAll("input, select, textarea").forEach((n) => {
    const i = Gt(n) ? n : null;
    !i || !i.name || i.disabled || i instanceof HTMLInputElement && (i.type === "submit" || i.type === "button" || i.type === "reset" || i.type === "image") || (i.name.startsWith("fields[") && r.add(i.name), Jt(i) && Yt(e, i));
  }), r;
}
function gn(e, t) {
  t.forEach((r) => {
    e.has(r) || e.append(r, "");
  });
}
function ht(e, t) {
  const r = rt(e), n = r.find((a) => !a.hasAttribute("data-formie-page-hidden")) || null;
  if (!r.length || !n) {
    const a = new FormData(e);
    return a.set("submitAction", t), a;
  }
  const i = new FormData();
  mn(i, e);
  const o = pn(i, n);
  return gn(i, o), i.set("submitAction", t), i;
}
function hn(e, t) {
  if (t !== "submit")
    return !1;
  const r = rt(e);
  return r.length ? (r.find((i) => !i.hasAttribute("data-formie-page-hidden")) || r[r.length - 1]) === r[r.length - 1] : !0;
}
async function Zt(e, t, r, n = {}) {
  R.log("Starting submit pipeline.", {
    action: t,
    preflightOnly: n.preflightOnly === !0
  });
  let i = !1, o, a = null;
  const s = hn(e, t), l = {
    form: e,
    action: t,
    formData: ht(e, t),
    abort: (c) => {
      i = !0, o = c, R.warn("Pipeline aborted.", { reason: c });
    },
    isAborted: () => i,
    abortReason: () => o
  }, d = {
    prepare: async (c) => {
      const u = c.form.querySelector('input[name="submitAction"]');
      return u && (u.value = c.action), c.formData.set("submitAction", c.action), null;
    },
    normalize: async () => null,
    validate: async (c) => {
      if (c.action !== "submit" || n.validateOnSubmit === !1)
        return null;
      if (n.validator) {
        const { scope: u, final: h } = fn(c.form), A = n.validator.submit(h ? c.form : u, { final: h });
        return A.length > 0 ? (A[0]?.input.focus(), {
          ok: !1,
          stage: "validate",
          code: "VALIDATION_FAILED",
          message: n.validator.config.errorMessage || "Validation failed.",
          fieldErrors: n.validator.getFieldErrors(A),
          formErrors: [n.validator.config.errorMessage || "Validation failed."]
        }) : null;
      }
      return c.form.checkValidity() ? null : (c.form.querySelector(":invalid")?.focus(), {
        ok: !1,
        stage: "validate",
        code: "VALIDATION_FAILED",
        message: "Validation failed.",
        formErrors: ["Validation failed."]
      });
    },
    screen: async () => null,
    authorize: async () => null,
    dispatch: async (c) => {
      c.formData = ht(c.form, c.action);
      const u = await un(c.form, c.formData);
      return a = u, u;
    },
    finalize: async (c) => (a && a.ok && a.redirect?.url && (a.redirect.target === "new-tab" ? window.open(a.redirect.url, "_blank") : window.location.href = a.redirect.url), null)
  };
  {
    const c = await r.emitSafe("formie:submit:before", l);
    c.failed.length > 0 && R.warn("Submit before listeners failed.", {
      eventName: c.eventName,
      failed: c.failed.length
    });
  }
  if (s) {
    const c = await r.emitSafe("formie:submit:final:before", l);
    c.failed.length > 0 && R.warn("Final submit before listeners failed.", {
      eventName: c.eventName,
      failed: c.failed.length
    });
  }
  const p = n.preflightOnly ? dn : cn;
  for (const c of p) {
    if (R.log("Stage start.", { stage: c, action: t }), i)
      return R.warn("Stage skipped due to abort.", { stage: c, reason: o }), Ve(c, o);
    {
      const h = await r.emitSafe(`formie:stage:${c}:before`, {
        ...l,
        stage: c
      });
      h.failed.length > 0 && R.warn("Stage before listeners failed.", {
        stage: c,
        failed: h.failed.length
      });
    }
    if (i) {
      const h = Ve(c, o);
      {
        const A = await r.emitSafe("formie:submit:after", h);
        A.failed.length > 0 && R.warn("Submit after listeners failed (abort before stage).", {
          stage: c,
          failed: A.failed.length
        });
      }
      if (s) {
        const A = await r.emitSafe("formie:submit:final:after", h);
        A.failed.length > 0 && R.warn("Final submit after listeners failed (abort before stage).", {
          stage: c,
          failed: A.failed.length
        });
      }
      return R.warn("Aborted after stage before-hooks.", { stage: c, reason: o }), h;
    }
    const u = await d[c](l);
    R.log("Stage runner complete.", {
      stage: c,
      hasResult: !!u,
      ok: u ? u.ok : void 0,
      code: u?.code
    });
    {
      const h = await r.emitSafe(`formie:stage:${c}:after`, {
        ...l,
        stage: c,
        result: u
      });
      h.failed.length > 0 && R.warn("Stage after listeners failed.", {
        stage: c,
        failed: h.failed.length
      });
    }
    if (i) {
      const h = Ve(c, o);
      {
        const A = await r.emitSafe("formie:submit:after", h);
        A.failed.length > 0 && R.warn("Submit after listeners failed (abort after stage).", {
          stage: c,
          failed: A.failed.length
        });
      }
      if (s) {
        const A = await r.emitSafe("formie:submit:final:after", h);
        A.failed.length > 0 && R.warn("Final submit after listeners failed (abort after stage).", {
          stage: c,
          failed: A.failed.length
        });
      }
      return R.warn("Aborted after stage after-hooks.", { stage: c, reason: o }), h;
    }
    if (u && !u.ok) {
      {
        const h = await r.emitSafe("formie:submit:after", u);
        h.failed.length > 0 && R.warn("Submit after listeners failed (failed stage).", {
          stage: c,
          failed: h.failed.length
        });
      }
      if (s) {
        const h = await r.emitSafe("formie:submit:final:after", u);
        h.failed.length > 0 && R.warn("Final submit after listeners failed (failed stage).", {
          stage: c,
          failed: h.failed.length
        });
      }
      return R.warn("Pipeline short-circuited by failed stage.", {
        stage: c,
        code: u.code,
        message: u.message
      }), u;
    }
  }
  const y = a || {
    ok: !0,
    stage: n.preflightOnly ? "authorize" : "finalize",
    message: n.preflightOnly ? "Submission preflight completed." : "Submission completed."
  };
  {
    const c = await r.emitSafe("formie:submit:after", y);
    c.failed.length > 0 && R.warn("Submit after listeners failed (success).", {
      failed: c.failed.length
    });
  }
  if (s) {
    const c = await r.emitSafe("formie:submit:final:after", y);
    c.failed.length > 0 && R.warn("Final submit after listeners failed (success).", {
      failed: c.failed.length
    });
  }
  return R.log("Pipeline completed.", {
    ok: y.ok,
    stage: y.stage,
    code: y.code
  }), y;
}
const bn = {
  rule: ({ input: e, getRule: t }) => !t("email") || !e.value || e.value.length < 1 ? !0 : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value),
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-pattern-email-message") ?? e.getAttribute("data-pattern-email-message") ?? r("{attribute} is not a valid email address.", { attribute: t })
};
function yn(e) {
  return e?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim() || "";
}
function bt(e) {
  const t = e.getRule("match");
  if (!t || t === !0 || typeof t != "object" || !e.field)
    return null;
  const r = typeof t.fieldHandle == "string" ? t.fieldHandle.trim() : "";
  if (!r)
    return null;
  const n = e.form.querySelector(`[data-formie-field-handle="${r}"]`);
  return n ? n.querySelector(e.config.fieldsSelector) : null;
}
const vn = {
  rule: (e) => {
    const t = bt(e);
    return t ? t.value === e.input.value : !0;
  },
  message: (e) => {
    const r = bt(e)?.closest("[data-formie-field-handle]"), n = yn(r);
    return e.t("{name} must match {value}.", {
      name: e.label,
      value: n
    });
  }
}, En = {
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
    return o !== null && a !== null ? n("{attribute} must be between {min} and {max}.", { attribute: t, min: o, max: a }) : o !== null ? n("{attribute} must be no less than {min}.", { attribute: t, min: o }) : a !== null ? n("{attribute} must be no greater than {max}.", { attribute: t, max: a }) : e.getAttribute("data-formie-pattern-number-message") ?? e.getAttribute("data-pattern-number-message") ?? n("{attribute} is not a valid number.", { attribute: t });
  }
}, Sn = {
  rule: ({ input: e, getRule: t }) => {
    if (!t("required") || e.type === "hidden")
      return !0;
    if (e.type === "checkbox" || e.type === "radio") {
      const r = e.form?.querySelectorAll(`[name="${e.name}"]:not([type="hidden"]):not([disabled])`) || [];
      return r.length ? Array.from(r).some((n) => n instanceof HTMLInputElement && n.checked) : e instanceof HTMLInputElement ? e.checked : !0;
    }
    return e.value.trim() !== "";
  },
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-required-message") ?? e.getAttribute("data-required-message") ?? r("{attribute} cannot be blank.", { attribute: t })
}, An = {
  rule: ({ input: e, getRule: t }) => {
    if (!t("url") || !e.value || e.value.length < 1)
      return !0;
    try {
      return new URL(e.value), !0;
    } catch {
      return !1;
    }
  },
  message: ({ input: e, label: t, t: r }) => e.getAttribute("data-formie-pattern-url-message") ?? e.getAttribute("data-pattern-url-message") ?? r("{attribute} is not a valid URL.", { attribute: t })
}, wn = {
  // Keep the core validator registry centralized so FormieValidator can extend
  // it at runtime while still shipping one predictable builtin rule surface.
  required: Sn,
  email: bn,
  url: An,
  number: En,
  match: vn
};
function Qt() {
  return window.FormieTranslations || {};
}
function Tn() {
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
          ...t ?? Qt(),
          ...i
        };
      } catch {
        continue;
      }
  }
  t && (window.FormieTranslations = t);
}
function nt() {
  return Tn(), Qt();
}
function Xt() {
  return { ...nt() };
}
function Yo(e) {
  return window.FormieTranslations = { ...e }, Xt();
}
function Zo(e) {
  return window.FormieTranslations = {
    ...nt(),
    ...e
  }, Xt();
}
function x(e, t = {}) {
  let r = nt()[e] || e;
  return r = r.replace(/{([a-zA-Z0-9]+)}/g, (n, i) => Object.prototype.hasOwnProperty.call(t, i) ? String(t[i]) : n), r;
}
const Qo = x, Cn = {
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
function Mn(e, t) {
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
function Ln(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function In(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function Fn(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
class Rn {
  constructor(t, r = {}) {
    this.errors = [], this.validators = {}, this.boundListeners = !1, this.activated = /* @__PURE__ */ new WeakSet(), this.submitted = !1, this.initialValues = /* @__PURE__ */ new WeakMap(), this.form = t, this.onBlur = this.blurHandler.bind(this), this.onChange = this.changeHandler.bind(this), this.onInput = this.inputHandler.bind(this), this.config = {
      live: !1,
      errorMessage: "",
      fieldContainerErrorClass: [],
      inputErrorClass: [],
      messagesClass: [],
      messageClass: [],
      fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
      patterns: Cn,
      ...r
    }, Object.entries(wn).forEach(([n, i]) => {
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
          const c = this.getErrorMessage(i, d, p, l);
          this.shouldShowError(i) && !o && this.showError(i, d, c), this.errors.push({
            input: i,
            field: l.field,
            validator: d,
            message: c,
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
      a.removeAttribute("aria-invalid"), this.config.inputErrorClass.length && a.classList.remove(...this.config.inputErrorClass), a.removeAttribute("data-formie-input-has-error"), i && Mn(a, i), r.querySelectorAll("[data-formie-field-error]").forEach((s) => {
        const l = s.id;
        l && Fn(a, l);
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
    o || (o = document.createElement("div"), o.setAttribute("data-formie-field-errors", "true"), this.config.messagesClass.length && o.classList.add(...this.config.messagesClass), i.appendChild(o)), this.config.messagesClass.length && o.classList.add(...this.config.messagesClass), o.innerHTML = "";
    const a = i.getAttribute("data-formie-field-handle") || "field", s = `${a}-error`;
    o.id = o.id || `${a}-errors`, o.setAttribute("aria-live", "polite"), o.setAttribute("aria-atomic", "true");
    const l = document.createElement("div");
    l.setAttribute("data-formie-field-error", "true"), l.setAttribute(`data-formie-field-error-${r}`, "true"), l.setAttribute("id", s), l.setAttribute("role", "alert"), this.config.messageClass.length && l.classList.add(...this.config.messageClass), l.textContent = n, o.appendChild(l), i.setAttribute("data-formie-field-has-error", "true"), i.querySelectorAll("input, select, textarea").forEach((d) => {
      const p = d;
      p.setAttribute("aria-invalid", "true"), this.config.inputErrorClass.length && p.classList.add(...this.config.inputErrorClass), p.setAttribute("data-formie-input-has-error", "true"), Ln(p, o.id), In(p, s);
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
    return (typeof n.errorMessage == "function" ? n.errorMessage(i) : n.errorMessage) ?? x("{attribute} is invalid.", { attribute: i.label });
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
const kn = "STALE_SUBMISSION_STATE", yt = /* @__PURE__ */ new WeakMap(), we = /* @__PURE__ */ new WeakMap(), j = _("general", "submit-result");
function ze(e, t, r) {
  let n = e.querySelector(`input[name="${t}"]`);
  n || (n = document.createElement("input"), n.type = "hidden", n.name = t, e.appendChild(n)), n.value = r;
}
function vt(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function ae(e, t) {
  e.querySelector(`input[name="${t}"]`)?.remove();
}
function qn(e, t) {
  try {
    const r = new URL(e, window.location.href);
    return r.searchParams.delete(t), r.toString();
  } catch {
    return e;
  }
}
function Pn(e) {
  try {
    return new URL(e, window.location.href).origin === window.location.origin;
  } catch {
    return !1;
  }
}
function er(e) {
  return Array.from(e.querySelectorAll("[data-formie-page]"));
}
function $n(e) {
  return Array.from(e.querySelectorAll("[data-formie-tab]"));
}
function Vn(e, t, r) {
  return t < 0 || r < 1 ? 0 : (e.dataset.formieProgressCalculation === "page-position" ? "page-position" : "completion") === "page-position" ? Math.round((t + 1) / r * 100) : Math.round(t / r * 100);
}
function On(e) {
  return e <= 0 ? "start" : e >= 100 ? "end" : "middle";
}
function Et(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function St(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function it(e, t) {
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
  const t = yt.get(e);
  typeof t == "number" && (window.clearTimeout(t), yt.delete(e));
}
function Hn(e, t) {
  we.has(e) || we.set(e, e.innerHTML), e.textContent = t;
}
function je(e) {
  const t = we.get(e);
  t !== void 0 && (e.innerHTML = t, we.delete(e));
}
function _n(e, t) {
  const r = e.querySelector("[data-formie-progress-bar]"), n = e.querySelector("[data-formie-progress-value]");
  r && (r.style.width = `${t}%`, r.setAttribute("aria-valuenow", `${t}`), r.setAttribute("data-formie-progress-state", On(t)), n && (n.textContent = `${t}%`, n.setAttribute("data-formie-progress-value", `${t}`)));
}
function Dn(e, t) {
  if (!t)
    return;
  const r = (e.dataset.formieLoadingIndicator || "").trim();
  if (r) {
    if (t.setAttribute("data-formie-loading-indicator", r), r === "spinner") {
      ue(t, e, "loading", !0), je(t), t.removeAttribute("data-formie-loading-text");
      return;
    }
    if (r === "text") {
      const n = (e.dataset.formieLoadingIndicatorText || "").trim(), i = t.textContent?.trim() || "", o = n || i;
      t.setAttribute("data-formie-loading-text", o), Hn(t, o);
      return;
    }
    je(t), t.removeAttribute("data-formie-loading-text");
  }
}
function tr(e) {
  return Array.from(e.querySelectorAll("[data-formie-action]"));
}
function rr(e, t) {
  if (e.getAttribute("data-formie-loading") === "true")
    return;
  e.setAttribute("data-formie-loading", "true"), tr(e).forEach((n) => {
    "disabled" in n && (n.disabled ? n.setAttribute("data-formie-was-disabled", "true") : n.removeAttribute("data-formie-was-disabled"), n.disabled = !0);
  }), t && (t.setAttribute("data-formie-loading", "true"), Dn(e, t));
}
function Te(e) {
  e.removeAttribute("data-formie-loading"), tr(e).forEach((r) => {
    if ("disabled" in r) {
      const n = r, i = n.getAttribute("data-formie-was-disabled") === "true";
      n.disabled = i;
    }
    je(r), r.removeAttribute("data-formie-was-disabled"), r.removeAttribute("data-formie-loading"), ue(r, e, "loading", !1), r.removeAttribute("data-formie-loading-indicator"), r.removeAttribute("data-formie-loading-text");
  });
}
function ot(e, t) {
  const r = er(e), n = $n(e), i = r.findIndex((o) => o.getAttribute("data-formie-page-id") === t);
  if (r.forEach((o) => {
    o.getAttribute("data-formie-page-id") === t ? (o.removeAttribute("data-formie-page-hidden"), le(o, e, "pageHidden")) : (o.setAttribute("data-formie-page-hidden", "true"), V(o, e, "pageHidden"));
  }), n.forEach((o, a) => {
    const s = o.getAttribute("data-formie-page-id") === t, l = i > -1 && a < i;
    ue(o, e, "tabCurrent", s), ue(o, e, "tabComplete", l), s ? o.setAttribute("aria-current", "page") : o.removeAttribute("aria-current"), l ? o.setAttribute("data-formie-tab-complete", "true") : o.removeAttribute("data-formie-tab-complete");
  }), i > -1 && r.length > 0) {
    const o = Vn(e, i, r.length);
    _n(e, o);
  }
  ze(e, "pageId", t), J(e);
}
function xn(e, t) {
  const r = t.meta?.submissionUid;
  typeof r == "string" && r.trim() !== "" && ze(e, "submissionUid", r);
  const n = t.meta?.session?.continuation?.continuationToken;
  typeof n == "string" && n.trim() !== "" ? ze(e, "continuationToken", n) : ae(e, "continuationToken");
}
function Nn(e) {
  const t = e.getAttribute("action");
  t && e.setAttribute("action", qn(t, "resumeToken"));
  try {
    const r = new URL(window.location.href);
    if (!r.searchParams.has("resumeToken"))
      return;
    r.searchParams.delete("resumeToken"), window.history.replaceState({}, document.title, `${r.pathname}${r.search}${r.hash}`);
  } catch {
  }
}
function Un(e, t) {
  const r = t.meta?.resumeUrl;
  if (typeof r != "string" || r.trim() === "")
    return;
  const n = r.trim();
  if (!Pn(n))
    return;
  e.getAttribute("action") && e.setAttribute("action", n);
  try {
    const o = new URL(n, window.location.href);
    window.history.replaceState({}, document.title, `${o.pathname}${o.search}${o.hash}`);
  } catch {
  }
}
function he(e, t = {}) {
  const n = e.formieValidation, i = er(e)[0]?.getAttribute("data-formie-page-id");
  if (z(e), e.reset(), t.preserveHiddenState || it(e, !1), ae(e, "submissionId"), ae(e, "submissionUid"), ae(e, "continuationToken"), ae(e, "pageId"), Nn(e), n?.resetLiveState(), i) {
    ot(e, i), e.dispatchEvent(new CustomEvent(Ue("reset"), { bubbles: !0 }));
    return;
  }
  J(e), e.dispatchEvent(new CustomEvent(Ue("reset"), { bubbles: !0 }));
}
function zn(e) {
  return e.code === kn || e.meta?.resetState === !0;
}
function jn(e, t) {
  const r = t.submitData, n = /* @__PURE__ */ new Set();
  let i = !1;
  if (Array.isArray(r) && r.length > 0) {
    const p = r.filter(
      (y) => typeof y == "object" && y !== null && "event" in y && typeof y.event == "string"
    );
    for (const y of p) {
      const c = y.event;
      n.add(c), j.log("Dispatching submitData event.", {
        eventName: c
      }), c.startsWith("formie:payment:") && (i = !0), e.dispatchEvent(new CustomEvent(c, {
        bubbles: !0,
        detail: { data: y.data }
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
function Bn(e, t, r) {
  if (j.log("Applying submit result state.", {
    ok: t.ok,
    action: r,
    code: t.code,
    hasRedirect: !!t.redirect?.url,
    hasSubmitData: Array.isArray(t.submitData) && t.submitData.length > 0
  }), zn(t)) {
    he(e), j.log("Resetting state due to stale/reset marker.");
    return;
  }
  const n = jn(e, t);
  if (!t.ok && t.redirect?.url && !n.hasPaymentFollowUpEvent) {
    j.log("Applying redirect fallback for failed result.", {
      url: t.redirect.url,
      target: t.redirect.target
    }), z(e), t.redirect.target === "new-tab" ? window.open(t.redirect.url, "_blank") : (vt(e, "redirect"), window.location.href = t.redirect.url);
    return;
  }
  if (xn(e, t), !t.ok) {
    j.log("Non-redirect failure; keeping current form state."), z(e);
    return;
  }
  if (jt(e, r), t.nextPage?.id) {
    z(e), e.formieValidation?.resetLiveState(), ot(e, t.nextPage.id), j.log("Advanced to next page.", {
      nextPageId: t.nextPage.id
    });
    return;
  }
  if (r === "save") {
    z(e), Un(e, t), j.log("Applied save/resume token state.");
    return;
  }
  if (r === "submit" && !t.redirect?.url) {
    const i = Et(e), o = i === "message" && St(e);
    if (i === "reload") {
      z(e), vt(e, "reload"), window.location.reload();
      return;
    }
    if (i === "reset") {
      he(e);
      return;
    }
    z(e), he(e, { preserveHiddenState: o });
    return;
  }
  if (r === "submit" && t.redirect?.url && t.redirect.target === "new-tab") {
    const o = Et(e) === "message" && St(e);
    z(e), he(e, { preserveHiddenState: o });
    return;
  }
  z(e);
}
const Ce = /* @__PURE__ */ new WeakMap();
function nr(e) {
  return (e.dataset.formieSubmitAction || "").trim();
}
function Wn(e) {
  return (e.dataset.formieErrorMessagePosition || "top-form").trim() || "top-form";
}
function ir(e) {
  return (e.dataset.formieSubmitActionMessagePosition || "").trim();
}
function Kn(e) {
  const t = (e.dataset.formieSubmitActionMessageTimeout || "").trim();
  if (!t)
    return null;
  const r = Number.parseFloat(t);
  return !Number.isFinite(r) || r < 0 ? null : Math.round(r * 1e3);
}
function at(e) {
  const t = e.dataset.formieSubmitActionFormHide;
  if (t === void 0)
    return !1;
  const r = t.trim().toLowerCase();
  return r === "true" || r === "1" || r === "";
}
function Gn(e) {
  const t = Ce.get(e);
  typeof t == "number" && (window.clearTimeout(t), Ce.delete(e));
}
function or(e) {
  return e.querySelector("[data-formie-form-messages-top]") || e;
}
function ar(e) {
  return e.querySelector("[data-formie-form-messages-bottom]") || e;
}
function Jn(e, t) {
  return t === "bottom-form" ? ar(e) : or(e);
}
function Yn(e, t) {
  return t === "top-form" ? or(e) : t === "bottom-form" && !at(e) ? ar(e) : e;
}
function Zn(e) {
  const t = Wn(e), r = Jn(e, t);
  let n = r.querySelector("[data-formie-error-container], [data-formie-errors]");
  return n || (n = document.createElement("div"), n.setAttribute("data-formie-errors", "true"), V(n, e, "errors")), n.setAttribute("data-formie-error-container", "true"), t === "bottom-form" ? r.append(n) : r.prepend(n), n;
}
function Qn(e, t) {
  let r = t.querySelector("[data-formie-error-message-container], [data-formie-message][data-formie-message-error]");
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-error-message-container", "true"), t.appendChild(r)), r.setAttribute("data-formie-message", "true"), r.setAttribute("data-formie-message-error", "true"), V(r, e, "message", "messageError"), r.setAttribute("role", "alert"), r.setAttribute("aria-live", "polite"), r.setAttribute("aria-atomic", "true"), r;
}
function Xn(e, t) {
  let r = e.querySelector("[data-formie-success-container]");
  const n = Yn(e, t);
  return r || (r = document.createElement("div"), r.setAttribute("data-formie-success-container", "true"), V(r, e, "successes")), t === "bottom-form" ? n.append(r) : n.prepend(r), r;
}
function ei(e) {
  let t = e.querySelector("[data-formie-field-errors]");
  return t || (t = document.createElement("div"), t.setAttribute("data-formie-field-errors", "true"), V(t, e, "fieldErrors"), e.appendChild(t)), t;
}
function ti(e, t) {
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
function ri(e, t) {
  e.setAttribute("aria-errormessage", t);
}
function ni(e, t) {
  e.getAttribute("aria-errormessage") === t && e.removeAttribute("aria-errormessage");
}
function sr(e) {
  e.querySelectorAll("[data-formie-field-handle]").forEach((t) => {
    const r = t, n = r.querySelector("[data-formie-field-errors]"), i = n?.id || "", o = Array.from(r.querySelectorAll("[data-formie-field-error]")).map((a) => a.id).filter(Boolean);
    le(r, e, "fieldLayoutError"), r.removeAttribute("data-formie-field-has-error"), r.querySelectorAll("[data-formie-field-error]").forEach((a) => {
      a.remove();
    }), n && !n.querySelector("[data-formie-field-error]") && (n.innerHTML = ""), r.querySelectorAll("input, select, textarea").forEach((a) => {
      const s = a;
      s.removeAttribute("aria-invalid"), le(s, e, "fieldControlError"), s.removeAttribute("data-formie-input-has-error"), i && ti(s, i), o.forEach((l) => {
        ni(s, l);
      });
    });
  }), J(e);
}
function lr(e) {
  e.querySelectorAll("[data-formie-error-container], [data-formie-errors]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-error]").forEach((n) => {
      n.remove();
    }), le(r, e, "message", "messageError"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-error"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-error]") || (r.innerHTML = "");
  });
}
function st(e) {
  Gn(e), e.querySelectorAll("[data-formie-message-success]:not([data-formie-success-container])").forEach((t) => {
    t.remove();
  }), e.querySelectorAll("[data-formie-success-container]").forEach((t) => {
    const r = t;
    r.querySelectorAll("[data-formie-success]").forEach((n) => {
      n.remove();
    }), le(r, e, "message", "messageSuccess"), r.removeAttribute("data-formie-message"), r.removeAttribute("data-formie-message-success"), r.removeAttribute("role"), r.removeAttribute("aria-live"), r.removeAttribute("aria-atomic"), r.querySelector("[data-formie-success]") || (r.innerHTML = "");
  }), nr(e) === "message" && at(e) || it(e, !1);
}
function ur(e) {
  e.querySelectorAll('[aria-invalid="true"]').forEach((t) => {
    t.removeAttribute("aria-invalid");
  });
}
function At(e, t) {
  const r = (e.getAttribute("aria-describedby") || "").trim(), n = r ? r.split(/\s+/) : [];
  n.includes(t) || n.push(t), e.setAttribute("aria-describedby", n.join(" ").trim());
}
function ii(e, t) {
  Object.entries(t).forEach(([r, n]) => {
    const i = e.querySelector(`[data-formie-field-handle="${r}"]`);
    if (!i)
      return;
    const o = ei(i), a = o.id && o.id.trim() ? o.id : `${r}-errors`;
    o.id = a, o.setAttribute("aria-live", "polite"), o.setAttribute("aria-atomic", "true"), V(i, e, "fieldLayoutError"), i.setAttribute("data-formie-field-has-error", "true"), n.forEach((l, d) => {
      const p = document.createElement("div");
      p.setAttribute("data-formie-field-error", "true"), p.setAttribute("role", "alert"), p.id = `${a}-${d + 1}`, V(p, e, "fieldError"), p.textContent = l, o.appendChild(p);
    });
    const s = o.querySelector("[data-formie-field-error]")?.id;
    i.querySelectorAll("input, select, textarea").forEach((l) => {
      const d = l;
      d.setAttribute("aria-invalid", "true"), V(d, e, "fieldControlError"), d.setAttribute("data-formie-input-has-error", "true"), At(d, a), s && ri(d, s);
      const p = i.querySelector("[data-formie-instructions]");
      p?.id && At(d, p.id);
    });
  }), J(e);
}
function wt(e, t) {
  const r = Zn(e), n = Qn(e, r);
  V(r, e, "errors"), t.forEach((i) => {
    const o = document.createElement("div");
    o.setAttribute("data-formie-error", "true"), o.setAttribute("role", "alert"), V(o, e, "error"), o.innerHTML = i, n.appendChild(o);
  });
}
function oi(e, t) {
  return !t.message || t.nextPage || t.redirect ? !1 : t.action === "save" ? !0 : nr(e) === "message" && ir(e) !== "";
}
function ai(e, t) {
  const r = ir(e);
  if (!r)
    return;
  const n = Xn(e, r);
  V(n, e, "message", "messageSuccess"), n.setAttribute("data-formie-message", "true"), n.setAttribute("data-formie-message-success", "true"), n.setAttribute("role", "status"), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true");
  const i = document.createElement("div");
  i.setAttribute("data-formie-success", "true"), V(i, e, "success"), i.innerHTML = t, n.appendChild(i), at(e) && it(e, !0);
  const o = Kn(e);
  if (o !== null) {
    const a = window.setTimeout(() => {
      Ce.delete(e), st(e);
    }, o);
    Ce.set(e, a);
  }
}
function Me(e, t) {
  if (sr(e), lr(e), st(e), ur(e), t.ok) {
    oi(e, t) && ai(e, t.message || "");
    return;
  }
  if (t.fieldErrors && ii(e, t.fieldErrors), t.formErrors?.length) {
    wt(e, t.formErrors);
    return;
  }
  !t.fieldErrors && t.message && wt(e, [t.message]);
}
const si = _("general", "submit-flow");
function li(e) {
  return !(!e.ok && e.stage === "validate");
}
function cr(e) {
  return e ? !!(e.keepSubmitLoading === !0 || e.ok && e.redirect?.url && e.redirect.target !== "new-tab") : !1;
}
function dr(e) {
  sr(e), lr(e), st(e), ur(e);
}
async function fr(e) {
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
    dispatchSubmitResult: y
  } = e;
  dr(n), rr(n, l || null);
  let c = {
    ok: !1,
    code: "SUBMIT_ERROR",
    message: "Submission failed.",
    formErrors: ["Submission failed."]
  };
  try {
    await d(n), c = await Zt(n, s, i, {
      validator: o,
      validateOnSubmit: a
    }), Me(n, c), Bn(n, c, s), li(c) && await p(c), y(c);
  } catch (u) {
    c = {
      ok: !1,
      code: "SUBMIT_ERROR",
      message: u instanceof Error ? u.message : "Submission failed.",
      formErrors: [u instanceof Error ? u.message : "Submission failed."]
    }, Me(n, c), y(c), si.warn("Submit failed with exception.", {
      id: t,
      action: s,
      target: r,
      error: u instanceof Error ? u.message : u
    });
  } finally {
    cr(c) || Te(n);
  }
  return c;
}
class mr {
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
const ui = {
  // Address providers stay behind lazy importer entries because their SDKs are
  // optional and often much heavier than the base form client.
  "address-finder": () => import("./address-finder-xoUqTeTb.js").then((e) => e.addressFinderModule),
  "google-address": () => import("./google-address-BcUcSkc4.js").then((e) => e.googleAddressModule),
  loqate: () => import("./loqate-BNLdRsdq.js").then((e) => e.loqateModule),
  "place-kit": () => import("./place-kit-CtHgOb4y.js").then((e) => e.placeKitModule)
}, ci = {
  // Module ids map directly to importer functions so the loader can fetch only
  // the captcha chunks required by the current form manifest.
  "captcha-eu": () => import("./captcha-eu-Djm4qvsr.js").then((e) => e.captchaEuModule),
  "friendly-captcha-v1": () => import("./friendly-captcha-v1-DXPla8Ka.js").then((e) => e.friendlyCaptchaV1Module),
  "friendly-captcha-v2": () => import("./friendly-captcha-v2-G9ARR3f7.js").then((e) => e.friendlyCaptchaV2Module),
  hcaptcha: () => import("./hcaptcha-Dr4k4PoI.js").then((e) => e.hcaptchaModule),
  "recaptcha-enterprise": () => import("./recaptcha-enterprise-CfFaBwEX.js").then((e) => e.recaptchaEnterpriseModule),
  "recaptcha-v2-checkbox": () => import("./recaptcha-v2-checkbox-bD8qajuZ.js").then((e) => e.recaptchaV2CheckboxModule),
  "recaptcha-v2-invisible": () => import("./recaptcha-v2-invisible-tKe4xlxx.js").then((e) => e.recaptchaV2InvisibleModule),
  "recaptcha-v3": () => import("./recaptcha-v3-CPkGpS1i.js").then((e) => e.recaptchaV3Module),
  snaptcha: () => import("./snaptcha-cKUMHAJY.js").then((e) => e.snaptchaModule),
  turnstile: () => import("./turnstile-5Kr6kuMX.js").then((e) => e.turnstileModule)
}, di = {
  // Keep the builtin map flat and explicit so manifest ids remain the source of
  // truth for lazy-loading first-party field enhancements.
  calculations: () => import("./calculations-Deu6WllX.js").then((e) => e.calculationsModule),
  "checkbox-radio": () => import("./checkbox-radio-DyS8HCN7.js").then((e) => e.checkboxRadioModule),
  conditions: () => import("./conditions-CYouLt14.js").then((e) => e.conditionsModule),
  "date-picker": () => import("./date-picker-BzG3CgwG.js").then((e) => e.datePickerModule),
  "file-upload": () => import("./file-upload-CO04rzej.js").then((e) => e.fileUploadModule),
  hidden: () => import("./hidden-DDOe7m-c.js").then((e) => e.hiddenModule),
  "phone-country": () => import("./phone-country-BTl-We2x.js").then((e) => e.phoneCountryModule),
  repeater: () => import("./repeater-Cv_llvwL.js").then((e) => e.repeaterModule),
  "rich-text": () => import("./rich-text-BaRLNV1f.js").then((e) => e.richTextModule),
  signature: () => import("./signature-C13ApwBy.js").then((e) => e.signatureModule),
  summary: () => import("./summary-Mo3OtApk.js").then((e) => e.summaryModule),
  table: () => import("./table-BO2hWOWi.js").then((e) => e.tableModule),
  "text-limit": () => import("./text-limit-C-Z3e1fY.js").then((e) => e.textLimitModule)
}, fi = {
  // Keep payment providers lazy and separately addressable so forms only ship
  // the payment SDK wrapper code they actually declare in their manifest.
  bpoint: () => import("./bpoint-DwzocVmT.js").then((e) => e.bpointModule),
  eway: () => import("./eway-lCuKLssl.js").then((e) => e.ewayModule),
  "go-cardless": () => import("./go-cardless-Bqaskrzo.js").then((e) => e.goCardlessModule),
  mollie: () => import("./mollie-Bc0b0v8m.js").then((e) => e.mollieModule),
  moneris: () => import("./moneris-VVeNFsYH.js").then((e) => e.monerisModule),
  opayo: () => import("./opayo-CGIuUVB6.js").then((e) => e.opayoModule),
  paddle: () => import("./paddle-C3U9s9EQ.js").then((e) => e.paddleModule),
  paypal: () => import("./paypal-CckPXpbk.js").then((e) => e.paypalModule),
  payway: () => import("./payway-UV4N6d4g.js").then((e) => e.paywayModule),
  square: () => import("./square-BPc0mZ1c.js").then((e) => e.squareModule),
  stripe: () => import("./stripe-zwbz939Y.js").then((e) => e.stripeModule)
}, mi = {
  ...di,
  ...ui,
  ...ci,
  ...fi
}, Oe = /* @__PURE__ */ new Map(), D = _("general", "loader"), pi = new Function("src", "return import(src);");
async function be(e, t, r, n) {
  await e(Hr(r), n), await e(Or(t, r), n);
}
function pr(e) {
  return !!e && typeof e == "object" && typeof e.id == "string" && typeof e.setup == "function" && typeof e.match == "function";
}
async function gi(e, t) {
  const r = mi[e];
  return r ? (Oe.has(e) || Oe.set(e, (async () => {
    try {
      const n = await r();
      return pr(n) ? (t.registry.register(n), n) : null;
    } catch (n) {
      return console.error("[formie] Failed to load builtin module:", e, n), D.warn("Failed loading builtin module.", { moduleId: e, error: n }), null;
    }
  })()), Oe.get(e) || null) : null;
}
async function hi(e) {
  try {
    const t = await pi(e), r = t?.default || t?.formieModule || null;
    return pr(r) ? r : null;
  } catch (t) {
    return console.error("[formie] Failed to load module from src:", e, t), D.warn("Failed loading module from src.", { src: e, error: t }), null;
  }
}
async function bi(e, t) {
  const r = t.registry.get(e.id);
  if (r)
    return r;
  const n = await gi(e.id, t);
  if (n)
    return n;
  if (e.src) {
    const i = await hi(e.src);
    if (i)
      return t.registry.register(i), i;
  }
  return null;
}
function He(e) {
  return typeof window.CSS?.escape == "function" ? window.CSS.escape(e) : e.replace(/["\\]/g, "\\$&");
}
function ye(e, t) {
  return e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function yi(e, t) {
  const r = t.setupContext.root, n = t.setupContext.form, i = e.targetType, o = e.targetId;
  return i === "selector" ? ye(r, o).map((a) => ({ scope: i, element: a })) : i === "field" ? ye(r, `[data-formie-field-handle="${He(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "page" ? ye(r, `[data-formie-page-id="${He(o)}"]`).map((a) => ({ scope: i, element: a })) : i === "button" ? ye(r, `[data-formie-action="${He(o)}"]`).map((a) => ({ scope: i, element: a })) : [{
    scope: "form",
    element: n || r
  }];
}
function vi(e, t) {
  return (e.targets && e.targets.length > 0 ? e.targets : [{
    targetType: "form",
    targetId: "form"
  }]).flatMap((n) => yi(n, t));
}
async function gr(e, t) {
  const r = [];
  D.log("Loading module manifest.", {
    manifestCount: e.length
  });
  for (const n of e) {
    const i = await bi(n, t);
    if (!i) {
      D.warn("Skipping manifest item (definition not resolved).", {
        moduleId: n.id,
        src: n.src
      });
      continue;
    }
    const o = vi(n, t);
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
      await be(t.setupContext.emit, d, "before-setup", p);
      let y = null;
      try {
        const c = await i.setup({
          ...t.setupContext,
          target: a.element,
          scope: a.scope,
          options: l
        });
        c && (y = c);
      } catch (c) {
        console.error(`[formie] Module "${i.id}" setup failed:`, c), D.warn("Module setup failed.", {
          moduleId: i.id,
          scope: a.scope,
          error: c
        });
      }
      await be(t.setupContext.emit, d, "after-setup", {
        ...p,
        instanceCreated: !!y
      }), y && (D.log("Module instance created.", {
        moduleId: i.id,
        scope: a.scope
      }), r.push({
        ...y,
        destroy: async () => {
          D.log("Destroying module instance.", {
            moduleId: i.id,
            scope: a.scope
          }), await be(t.setupContext.emit, d, "before-destroy", p), await y.destroy(), await be(t.setupContext.emit, d, "after-destroy", p), D.log("Module instance destroyed.", {
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
const Ei = /* @__PURE__ */ new Set([
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
function Be(e, t) {
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
    return `[${e.map((r) => Be(r, t)).join(",")}]`;
  if (typeof e == "object") {
    if (t.has(e))
      return "[circular]";
    t.add(e);
    const r = Object.entries(e).sort(([n], [i]) => n.localeCompare(i)).map(([n, i]) => `${JSON.stringify(n)}:${Be(i, t)}`);
    return t.delete(e), `{${r.join(",")}}`;
  }
  return JSON.stringify(String(e));
}
function Si(e) {
  return Be(e, /* @__PURE__ */ new WeakSet());
}
function Ai(e) {
  if (!e)
    return !1;
  const t = e.endsWith("[]") ? e.slice(0, -2) : e;
  return !Ei.has(t);
}
function Tt(e) {
  const t = Array.from(new FormData(e).entries()).filter(([r]) => Ai(String(r || "")));
  return Si(t);
}
function wi(e, t = {}) {
  let r = null, n = !1, i = !1, o = null, a = null, s = null;
  const l = () => {
    o !== null && (window.cancelAnimationFrame(o), o = null), a !== null && (window.clearTimeout(a), a = null), s !== null && (window.clearTimeout(s), s = null);
  }, d = () => n ? (i = Tt(e) !== r, i) : !1, p = () => {
    r = Tt(e), n = !0, i = !1;
  }, y = () => {
    l(), n = !1, o = window.requestAnimationFrame(() => {
      o = null, s = window.setTimeout(() => {
        s = null, p();
      }, 0);
    });
  }, c = () => {
    a !== null && window.clearTimeout(a), a = window.setTimeout(() => {
      a = null, d();
    }, 120);
  }, u = (h) => {
    t.shouldWarn && !t.shouldWarn() || d() && (h.preventDefault(), h.returnValue = "");
  };
  return e.addEventListener("input", c), e.addEventListener("change", c), window.addEventListener("beforeunload", u), y(), {
    captureBaseline: p,
    scheduleBaselineCapture: y,
    refreshDirtyState: d,
    destroy: () => {
      l(), e.removeEventListener("input", c), e.removeEventListener("change", c), window.removeEventListener("beforeunload", u);
    }
  };
}
const X = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])', Ti = 300, Ci = "/actions/formie/server/forms/render", Ct = "/api", Mi = "/actions/formie/server/forms/refresh-tokens", Li = "/actions/formie/server/submissions/submit", Ii = "/actions/formie/server/submissions/set-page", Fi = "/actions/formie/server/submissions/clear-submission", Ri = "/actions/formie/file-upload/hydrate", L = _("general", "client"), Mt = /* @__PURE__ */ new Set();
function ce(e, t) {
  if (e == null || e === "")
    return t;
  const r = e.toLowerCase();
  return !(r === "false" || r === "0" || r === "off");
}
function We(e) {
  return e.formieRefreshTokens != null && e.formieRefreshTokens !== "" ? ce(e.formieRefreshTokens, !1) : e.formieStaticCache != null && e.formieStaticCache !== "" ? ce(e.formieStaticCache, !1) : !1;
}
function ee(e) {
  const t = e instanceof HTMLElement ? e.dataset : {};
  return {
    mode: "server-rendered",
    transport: t.formieTransport || "rest",
    formHandle: t.formieHandle,
    endpoint: t.formieEndpoint,
    staticCache: We(t),
    autoVisible: ce(t.formieAutoVisible, !0),
    compatibility: ce(t.formieCompatibility, !1)
  };
}
function Fe(e) {
  return e || "server-rendered";
}
function Re(e) {
  return e || "rest";
}
function Ee(e) {
  return e instanceof HTMLFormElement ? e : e.querySelector("form");
}
function ki(e, t) {
  Mt.has(e) || (Mt.add(e), L.warn(t));
}
function hr(e, t) {
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
  return r ? r.includes("/actions/") ? r : hr(t, r) : t;
}
function qi(e, t) {
  return re(e.endpoint || t.dataset.formieEndpoint, Ci);
}
function Pi(e, t) {
  const r = (e.endpoint || t.dataset.formieEndpoint || "").trim();
  return r ? r.includes("/graphql") || r.endsWith("/api") || r.includes("/actions/graphql/") ? r : hr(Ct, r) : Ct;
}
function lt(e, t) {
  return re(
    t.dataset.formieRefreshTokensEndpoint || e.endpoint || t.dataset.formieEndpoint,
    Mi
  );
}
function Lt(e, t) {
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
function $i(e, t, r) {
  const n = r.endpoint || e.dataset.formieEndpoint, i = re(n, Li), o = t.getAttribute("action");
  t.setAttribute("action", Lt(o, i)), t.querySelectorAll("[data-formie-tab-link]").forEach((a) => {
    const s = a.getAttribute("href"), l = re(n, Ii);
    a.setAttribute("href", Lt(s, l));
  }), t.querySelectorAll("[data-formie-file-upload-hydrate-endpoint]").forEach((a) => {
    a.setAttribute(
      "data-formie-file-upload-hydrate-endpoint",
      re(n, Ri)
    );
  });
}
function ut(e, t) {
  if (e === "graphql" && t !== "server-rendered")
    throw new Error(`Formie ${t} mode does not support GraphQL transport yet.`);
}
function ct(e) {
  if (e == null)
    return !1;
  const t = e.trim().toLowerCase();
  return t === "true" || t === "1" || t === "";
}
function Vi(e) {
  return ce(e.dataset.formieAutomaticSubmissionState, !0);
}
function Oi(e, t, r) {
  return re(
    r.dataset.formieClearSubmissionEndpoint || e.endpoint || t.dataset.formieEndpoint,
    Fi
  );
}
function Hi(e) {
  return ct(e.dataset.formieUnloadWarning);
}
function It(e, t) {
  e.setAttribute("data-formie-internal-navigation", t);
}
function _e(e) {
  e.removeAttribute("data-formie-internal-navigation");
}
function Ft(e) {
  return e.getAttribute("data-formie-internal-navigation") !== null;
}
function Rt(e, t) {
  if (!e)
    return !1;
  try {
    return new URL(e, window.location.origin).searchParams.has(t);
  } catch {
    return !1;
  }
}
function _i(e) {
  return Rt(window.location.href, "resumeToken") || Rt(e.getAttribute("action"), "resumeToken");
}
function Di(e) {
  return e instanceof MouseEvent ? e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey : !0;
}
function xi(e, t = 0) {
  if (!e)
    return t;
  const r = Number.parseInt(e, 10);
  return Number.isFinite(r) ? r : t;
}
function Ni(e) {
  return Math.max(0, xi(e.dataset.formieSubmitDelay, Ti));
}
function Ke(e) {
  return ct(e.dataset.formieValidationOnSubmit);
}
async function Ge(e) {
  const t = Ni(e);
  t < 1 || await new Promise((r) => {
    window.setTimeout(r, t);
  });
}
function kt(e, t) {
  const r = e?.getAttribute(t)?.trim();
  if (!r)
    return null;
  try {
    return JSON.parse(r);
  } catch (n) {
    return console.error(`[formie] Failed to parse ${t}.`, n), null;
  }
}
function qt(e, t) {
  const r = t || (e instanceof HTMLFormElement ? e : null);
  if (!r)
    return null;
  const n = kt(r, "data-formie-modules"), i = kt(r, "data-formie-theme");
  return !n && !i ? null : {
    modules: n || void 0,
    theme: i || void 0
  };
}
function Ui(e) {
  if (!(e instanceof HTMLElement))
    return !0;
  if (!e.isConnected || e.hidden || e.closest("[hidden]"))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display === "none" || t.visibility === "hidden" ? !1 : e.getClientRects().length > 0;
}
function zi(e, t) {
  return t === document ? !0 : t instanceof Element ? t === e || t.contains(e) : !0;
}
function P(e) {
  const t = e, r = t.id ? `#${t.id}` : "", n = t.dataset?.formieHandle ? `[handle="${t.dataset.formieHandle}"]` : "";
  return `${t.tagName ? t.tagName.toLowerCase() : "element"}${r}${n}`;
}
function dt(e, t) {
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
async function ji(e, t) {
  const r = Fe(t.mode), n = Re(t.transport);
  if (r !== "server-rendered")
    return null;
  if (t.payload)
    return t.payload.html && (e.innerHTML = t.payload.html), t.payload;
  ut(n, r);
  const i = !!Ee(e), o = t.formHandle || e.dataset.formieHandle;
  if (i || !o)
    return null;
  const a = {
    mode: r,
    endpoint: t.endpoint,
    locale: t.locale,
    siteId: t.siteId,
    theme: t.theme,
    themeConfig: t.themeConfig
  }, s = n === "graphql" ? Pi(t, e) : qi(t, e), l = n === "graphql" ? await an(s, o, a) : await on(s, o, {
    ...a,
    endpoint: s
  });
  return l?.html && (e.innerHTML = l.html), l;
}
async function br(e, t, r) {
  if (t.refreshTokens === !1)
    return;
  ut(Re(t.transport), Fe(t.mode));
  const n = t.formHandle || e.dataset.formieHandle;
  if (!n)
    return;
  const i = lt(t, e), a = r.querySelector('input[name="renderId"]')?.value || void 0, s = await tt(i, n, a);
  dt(r, s), I(e, "formie:refresh-tokens:refreshed", s);
}
function Bi(e, t, r, n, i, o) {
  const a = String(
    t.dataset.formieSubmitMethod || ""
  ).trim().toLowerCase(), s = Oi(r, e, t);
  let l = !1;
  const d = t.querySelectorAll("[data-formie-action]"), p = (u) => {
    if (u) {
      t.setAttribute("data-formie-pending-action", u);
      return;
    }
    t.removeAttribute("data-formie-pending-action");
  };
  if (Hi(t)) {
    const u = wi(t, {
      shouldWarn: () => !Ft(t)
    }), h = (f) => {
      if (!(f instanceof CustomEvent))
        return;
      const v = f.detail;
      v?.ok && v.action === "save" && u.scheduleBaselineCapture();
    }, A = () => {
      u.scheduleBaselineCapture();
    };
    e.addEventListener("formie:submit:result", h), t.addEventListener("formie:state:reset", A), o.push(() => {
      e.removeEventListener("formie:submit:result", h), t.removeEventListener("formie:state:reset", A), u.destroy();
    });
  }
  if (d.forEach((u) => {
    const h = (A) => {
      const f = A.currentTarget.getAttribute("data-formie-action"), v = t.querySelector('input[name="submitAction"]');
      p(f), f && v && (v.value = f);
    };
    u.addEventListener("click", h), o.push(() => {
      u.removeEventListener("click", h);
    });
  }), t.querySelectorAll("[data-formie-tab-link]").forEach((u) => {
    const h = async (A) => {
      if (a !== "ajax") {
        Di(A) && It(t, "set-page");
        return;
      }
      A.preventDefault();
      const f = A.currentTarget, v = f?.getAttribute("data-formie-page-id"), E = f?.getAttribute("href");
      if (!(!v || !E)) {
        ot(t, v), I(e, "formie:page:navigate", {
          pageId: v,
          href: E
        });
        try {
          const b = await sn(E, t, v);
          I(e, "formie:page:navigate:after", {
            pageId: v,
            href: E,
            response: b
          });
        } catch (b) {
          console.error("[formie] Failed to persist page navigation state.", b), I(e, "formie:page:navigate:error", {
            pageId: v,
            href: E,
            error: b
          });
        }
      }
    };
    u.addEventListener("click", h), o.push(() => {
      u.removeEventListener("click", h);
    });
  }), !Vi(t)) {
    let u = !1;
    const h = () => {
      u || Ft(t) || _i(t) || (u = !0, ln(s, t));
    };
    window.addEventListener("pagehide", h), window.addEventListener("beforeunload", h), o.push(() => {
      window.removeEventListener("pagehide", h), window.removeEventListener("beforeunload", h);
    });
  }
  const c = async (u) => {
    if (l)
      return;
    const h = a === "ajax";
    if (u.preventDefault(), t.getAttribute("data-formie-loading") === "true") {
      if (!(t.getAttribute("data-formie-internal-resubmit") === "true"))
        return;
      t.removeAttribute("data-formie-internal-resubmit");
    } else
      t.removeAttribute("data-formie-internal-resubmit");
    const f = u.submitter, v = f?.getAttribute("data-formie-action"), E = t.getAttribute("data-formie-pending-action"), b = t.querySelector('input[name="submitAction"]'), m = v || E || b?.value || "submit";
    let g = null, S = !1;
    try {
      if (h)
        g = await fr({
          target: e,
          form: t,
          bus: n,
          validator: i,
          validateOnSubmit: Ke(t),
          action: m,
          submitter: f,
          waitForSubmitDelay: Ge,
          onRefreshTokensAfterSubmit: async () => {
            await br(e, r, t);
          },
          dispatchSubmitResult: (C) => {
            I(e, "formie:submit:result", C);
          }
        });
      else {
        if (dr(t), rr(t, f), await Ge(t), g = await Zt(t, m, n, {
          validator: i,
          validateOnSubmit: Ke(t),
          preflightOnly: !0
        }), g.ok) {
          jt(t, m), l = !0, It(t, "submit"), p(null);
          let C = !1;
          const T = () => {
            C = !0, l = !1, _e(t), Te(t);
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
          S = !0;
          return;
        }
        Me(t, g), I(e, "formie:submit:result", g), _e(t);
      }
    } catch (C) {
      l = !1, g = {
        ok: !1,
        code: "SUBMIT_ERROR",
        message: C instanceof Error ? C.message : "Submission failed.",
        formErrors: [C instanceof Error ? C.message : "Submission failed."]
      }, Me(t, g), I(e, "formie:submit:result", g), _e(t);
    } finally {
      p(null), !h && !S && !cr(g) && Te(t);
    }
  };
  t.addEventListener("submit", c), o.push(() => {
    t.removeEventListener("submit", c);
  });
}
async function Wi(e, t, r) {
  if (t.refreshTokens === !1 || !t.staticCache)
    return;
  ut(Re(t.transport), Fe(t.mode));
  const n = t.formHandle || e.dataset.formieHandle, i = lt(t, e), a = r?.querySelector('input[name="renderId"]')?.value || void 0;
  if (!n)
    return;
  const s = await tt(i, n, a);
  !s || !r || (dt(r, s), I(e, "formie:refresh-tokens:after", s));
}
function Ki() {
  const e = /* @__PURE__ */ new Map(), t = new mr(), r = /* @__PURE__ */ new Map(), n = /* @__PURE__ */ new Map(), i = [
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
      L.log("Unmount requested.", { target: P(f) });
      const b = r.get(f);
      b && (b(), r.delete(f));
      const m = e.get(f);
      if (!m) {
        L.log("Unmount skipped (no mounted state).", { target: P(f) });
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
      }), L.log("Unmount complete.", { id: m.instance.id, target: P(f) });
    })().finally(() => {
      n.delete(f);
    });
    n.set(f, E), await E;
  }, a = async (f, v) => {
    L.log("Mount requested.", {
      target: P(f),
      mode: v.mode,
      autoVisible: v.autoVisible
    });
    const E = r.get(f);
    E && (E(), r.delete(f));
    const b = e.get(f);
    if (b)
      return L.log("Mount skipped (already mounted).", {
        id: b.instance.id,
        target: P(f)
      }), b.instance;
    const m = new Bt(), g = [], S = f?.id || `formie-${e.size + 1}`, C = ee(f), T = {
      ...C,
      ...v,
      mode: Fe(v.mode ?? C.mode),
      transport: Re(v.transport ?? C.transport)
    }, F = $r(T.compatibility);
    if (T.mode !== "server-rendered" && !Ee(f))
      throw new Error(`Formie ${T.mode} mode is not implemented yet in the browser client.`);
    const k = await ji(f, T), w = Ee(f);
    T.staticCache = v.staticCache ?? We(w ? w.dataset : f.dataset);
    const q = qt(f, w), N = k || q ? {
      ...k || {},
      ...q || {}
    } : null, B = N?.theme, Y = {}, fe = (N?.modules || []).filter((M) => !!M?.id && !!M?.type);
    L.log("Resolved mount payload.", {
      target: P(f),
      hasRenderPayload: !!k,
      hasEmbeddedPayload: !!q,
      moduleCount: fe.length
    });
    const ne = gt(f, B, w), $ = w ? new Rn(w, {
      live: ct(w.dataset.formieValidationOnFocus),
      errorMessage: w.dataset.formieErrorMessage || "",
      fieldContainerErrorClass: ne.fieldLayoutError || [],
      inputErrorClass: ne.fieldControlError || [],
      messagesClass: ne.fieldErrors || [],
      messageClass: ne.fieldError || []
    }) : null;
    if (w && $) {
      const M = w;
      M.formieValidation = $, Y.validation = $;
      const O = {
        validator: $,
        addValidator: $.addValidator.bind($),
        removeValidator: $.removeValidator.bind($)
      };
      I(w, "formie:validator:ready", O), I(f, "formie:validator:ready", O);
    }
    w && ((k || T.endpoint || f.dataset.formieEndpoint) && $i(f, w, T), J(w)), Object.keys(ne).length && I(f, "formie:theme:applied", {
      hasClasses: !0
    });
    const me = await gr(fe, {
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
        on: (M, O) => m.on(M, O),
        emit: (M, O) => (I(f, M, O), m.emitSafe(M, O).then((Z) => {
          Z.failed.length > 0 && L.warn("Lifecycle listeners failed.", {
            eventName: M,
            failed: Z.failed.length
          });
        }))
      }
    });
    L.log("Module setup complete.", {
      target: P(f),
      moduleInstances: me.length
    });
    const qe = {
      id: S,
      root: f,
      submit: async (M = "submit") => {
        if (L.log("Submit requested.", {
          id: S,
          target: P(f),
          action: M
        }), !w)
          return {
            ok: !1,
            code: "FORM_NOT_FOUND",
            message: "No form element found for mount target.",
            formErrors: ["No form element found for mount target."]
          };
        const O = w.querySelector('input[name="submitAction"]');
        if (O && (O.value = M), w.getAttribute("data-formie-loading") === "true")
          return {
            ok: !1,
            code: "SUBMIT_IN_PROGRESS",
            message: "Submission already in progress.",
            formErrors: []
          };
        const Z = w.querySelector(`[data-formie-action="${M}"]`), Q = await fr({
          id: S,
          target: f,
          form: w,
          bus: m,
          validator: $,
          validateOnSubmit: Ke(w),
          action: M,
          submitter: Z,
          waitForSubmitDelay: Ge,
          onRefreshTokensAfterSubmit: async () => {
            await br(f, T, w);
          },
          dispatchSubmitResult: (Pe) => {
            I(f, "formie:submit:result", Pe);
          }
        });
        return L.log("Submit completed.", {
          id: S,
          action: M,
          ok: Q.ok,
          code: Q.code,
          message: Q.message
        }), Q;
      },
      destroy: async () => {
        await o(f);
      },
      on: (M, O) => m.on(M, O)
    };
    w && (zr({
      target: f,
      form: w,
      validatorDetail: $ ? {
        validator: $,
        addValidator: $.addValidator.bind($),
        removeValidator: $.removeValidator.bind($)
      } : null,
      options: F,
      unbinds: g
    }), Ur({
      target: f,
      form: w,
      instance: qe,
      options: F,
      unbinds: g
    })), w && (Bi(f, w, T, m, $, g), await Wi(f, T, w)), i.forEach((M) => {
      const O = m.on(`formie:stage:${M}:before`, async (W) => {
        I(f, `formie:stage:${M}:before`, W);
      }), Z = m.on(`formie:stage:${M}:before`, async (W) => {
        for (const ie of me)
          ie.onBeforeStage && await ie.onBeforeStage(W);
      }), Q = m.on(`formie:stage:${M}:after`, async (W) => {
        I(f, `formie:stage:${M}:after`, W);
      }), Pe = m.on(`formie:stage:${M}:after`, async (W) => {
        const ie = W;
        for (const pt of me)
          pt.onAfterStage && await pt.onAfterStage(ie, ie.result);
      });
      g.push(O, Z, Q, Pe);
    });
    const Fr = m.on("formie:submit:before", async (M) => {
      I(f, "formie:submit:before", M);
    }), Rr = m.on("formie:submit:after", async (M) => {
      I(f, "formie:submit:after", M);
    }), kr = m.on("formie:submit:final:before", async (M) => {
      I(f, "formie:submit:final:before", M);
    }), qr = m.on("formie:submit:final:after", async (M) => {
      I(f, "formie:submit:final:after", M);
    });
    return g.push(
      Fr,
      Rr,
      kr,
      qr
    ), e.set(f, {
      options: T,
      bus: m,
      form: w,
      validator: $,
      modules: me,
      unbinds: g,
      instance: qe
    }), I(f, "formie:mount:after", {
      id: S,
      mode: T.mode
    }), L.log("Mount complete.", {
      id: S,
      target: P(f),
      mode: T.mode
    }), qe;
  }, s = (f, v) => {
    if (!v.autoVisible || Ui(f) || typeof IntersectionObserver > "u")
      return a(f, v);
    if (e.has(f))
      return Promise.resolve(e.get(f)?.instance || null);
    if (r.has(f))
      return L.log("Mount deferred (already waiting visibility).", {
        target: P(f)
      }), Promise.resolve(null);
    const E = new IntersectionObserver((b) => {
      b.some((g) => g.target === f && g.isIntersecting) && (E.disconnect(), r.delete(f), L.log("Visibility reached, proceeding mount.", {
        target: P(f)
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
      target: P(f)
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
      const b = v.payload?.theme || E.options.payload?.theme || qt(f, E.form)?.theme, m = gt(f, b, E.form);
      return E.validator && (E.validator.config.fieldContainerErrorClass = m.fieldLayoutError || [], E.validator.config.inputErrorClass = m.fieldControlError || [], E.validator.config.messagesClass = m.fieldErrors || [], E.validator.config.messageClass = m.fieldError || []), Object.keys(m).length && I(f, "formie:theme:applied", {
        hasClasses: !0,
        reason: "update"
      }), E.instance;
    },
    getInstance: (f) => e.get(f)?.instance || null,
    refreshForCache: async (f) => {
      ki(
        "refreshForCache",
        "Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead."
      );
      let v = null;
      if (typeof f == "string") {
        const k = document.getElementById(f);
        k ? v = k : v = document.querySelector(`[data-formie-form-id="${f}"]`);
      } else
        v = f;
      if (!v) {
        L.warn("refreshForCache target not found.", {
          targetOrId: f
        });
        return;
      }
      const E = e.get(v), b = Ee(v), m = E?.options || ee(v);
      if (!b) {
        L.warn("refreshForCache found no form element for target.", {
          target: P(v)
        });
        return;
      }
      const g = m.formHandle || v.dataset.formieHandle || b.dataset.formieHandle, S = lt(m, v), T = b.querySelector('input[name="renderId"]')?.value || void 0;
      if (!g) {
        L.warn("refreshForCache found no form handle for target.", {
          target: P(v)
        });
        return;
      }
      const F = await tt(S, g, T);
      F && (dt(b, F), I(v, "formie:refresh-tokens:after", F));
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
      const E = new MutationObserver((b) => {
        b.forEach((m) => {
          m.addedNodes.forEach((g) => {
            g instanceof Element && (g.matches(X) && (L.log("Observer detected new root.", {
              target: P(g)
            }), s(g, ee(g))), g.querySelectorAll(X).forEach((S) => {
              L.log("Observer detected new nested root.", {
                target: P(S)
              }), s(S, ee(S));
            }));
          }), m.removedNodes.forEach((g) => {
            g instanceof Element && (e.has(g) && (L.log("Observer detected removed root.", {
              target: P(g)
            }), o(g)), g.querySelectorAll(X).forEach((S) => {
              e.has(S) && (L.log("Observer detected removed nested root.", {
                target: P(S)
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
          zi(g, v) && (m(), r.delete(g));
        });
        const b = [];
        v instanceof Element && v.matches(X) && b.push(v), v.querySelectorAll(X).forEach((m) => {
          b.push(m);
        }), b.forEach((m) => {
          e.has(m) && o(m);
        });
      };
    }
  };
}
const yr = _("general", "module-hydrator");
async function Xo(e) {
  const t = e.root, r = e.form ?? (t instanceof HTMLFormElement ? t : t.closest("form")), n = e.modules ?? [], i = e.mode ?? "server-rendered", o = e.registry ?? new mr(), a = new Bt(), s = await gr(n, {
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
  return yr.log("Hydrated module manifest.", {
    moduleCount: n.length,
    instanceCount: s.length,
    mode: i
  }), {
    destroy: async () => {
      await Gi(s), a.clear();
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
async function Gi(e) {
  for (const t of e)
    try {
      await t.destroy();
    } catch (r) {
      console.error("[formie] Failed to destroy module instance.", r), yr.warn("Failed destroying module instance.", { error: r });
    }
}
function ke(e) {
  return e instanceof Element;
}
function Ji(e) {
  return e.ok;
}
function Yi(e) {
  return typeof e == "string" ? `selector "${e}"` : ke(e) ? `element "${e.tagName.toLowerCase()}"` : "provided element collection";
}
function Zi(e) {
  const t = /* @__PURE__ */ new Set(), r = [];
  for (const n of e)
    !ke(n) || t.has(n) || (t.add(n), r.push(n));
  return r;
}
function Je(e) {
  return typeof e == "string" ? Array.from(document.querySelectorAll(e)) : ke(e) ? [e] : Zi(e);
}
function Qi() {
  return document.readyState !== "loading" ? Promise.resolve() : new Promise((e) => {
    document.addEventListener("DOMContentLoaded", () => e(), { once: !0 });
  });
}
async function Xi(e) {
  const t = Je(e);
  return t.length > 0 || typeof e != "string" ? t : (await Qi(), Je(e));
}
function eo(e) {
  return typeof e == "string" ? document : ke(e) ? e.getRootNode() : document;
}
function to(e) {
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
async function Pt(e, t, r, n) {
  const i = [], o = to(e);
  for (const a of n) {
    const s = r.get(a);
    if (s) {
      i.push(s.instance);
      continue;
    }
    const l = await t.mount(a, o), d = [];
    if (e.onReady?.(l), d.push(l.on("formie:submit:result", (p) => {
      const y = p;
      e.onResult?.(y, l), Ji(y) ? e.onSuccess?.(y, l) : e.onError?.(y, l);
    })), e.onEvent)
      for (const p of Vr)
        d.push(l.on(p, (y) => {
          e.onEvent?.({
            name: p,
            payload: y
          }, l);
        }));
    r.set(a, {
      instance: l,
      unsubs: d
    }), i.push(l);
  }
  return i;
}
async function ea(e) {
  const t = e.client ?? Ki(), r = /* @__PURE__ */ new Map(), n = await Xi(e.element);
  if (n.length === 0 && !e.allowEmpty)
    throw new Error(`Formie could not find any elements for ${Yi(e.element)}.`);
  await Pt(e, t, r, n);
  const i = e.observe ? t.observe(eo(e.element)) : null;
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
      const o = Je(e.element);
      return o.length === 0 ? Array.from(r.values()).map(({ instance: a }) => a) : Pt(e, t, r, o);
    },
    async destroy() {
      i?.();
      const o = Array.from(r.entries());
      for (const [a, s] of o)
        s.unsubs.forEach((l) => l()), await t.unmount(a), r.delete(a);
    }
  };
}
const ft = 2e3, ta = 5e3, ra = 5e3, na = 12e4;
async function mt(e) {
  await new Promise((t) => {
    window.setTimeout(t, Math.max(e, 0));
  });
}
async function ia(e, {
  timeoutMs: t = 5e3,
  intervalMs: r = 30
} = {}) {
  const n = Date.now();
  for (; Date.now() - n < t; ) {
    const i = e();
    if (i)
      return i;
    await mt(r);
  }
  throw new Error("Timed out waiting for async condition.");
}
function vr(e, t) {
  let r = null;
  return (...n) => {
    r !== null && window.clearTimeout(r), r = window.setTimeout(() => {
      e(...n);
    }, Math.max(t, 0));
  };
}
function oa(e) {
  const t = String(e || "asyncDefer").toLowerCase();
  return {
    async: t.includes("async"),
    defer: t.includes("defer")
  };
}
function Er(e, t) {
  const r = Array.from(e.querySelectorAll(`input[name="${t}"], textarea[name="${t}"]`));
  for (const n of r) {
    const i = String(n.value || "").trim();
    if (i !== "")
      return i;
  }
  return "";
}
function Ye(e, t) {
  return t.some((r) => Er(e, r) !== "");
}
function ro(e, t) {
  t.forEach((r) => {
    Array.from(e.querySelectorAll(`input[name="${r}"], textarea[name="${r}"]`)).forEach((i) => {
      i.value = "";
    });
  });
}
function Sr(e, t, {
  value: r = "",
  container: n
} = {}) {
  let i = e.querySelector(`input[name="${t}"]`);
  return i || (i = document.createElement("input"), i.type = "hidden", i.name = t, (n || (e instanceof HTMLElement ? e : null))?.appendChild(i)), i.value = r, i;
}
async function Ar(e, t, r) {
  if (Ye(e, t))
    return !0;
  const n = Date.now() + Math.max(r, 0);
  for (; Date.now() < n; )
    if (await mt(120), Ye(e, t))
      return !0;
  return !1;
}
const no = /* @__PURE__ */ new Set([
  "handle",
  "placeholderSelector",
  "errorMessage",
  "sessionKey",
  "value"
]), io = "[data-formie-captcha-error-container]", oo = [
  "formie:page:navigate",
  "formie:page:navigate:after",
  "formie:submit:result"
];
function se(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function Le(e, t) {
  return e instanceof HTMLElement && e.matches(t) ? [e, ...Array.from(e.querySelectorAll(t))] : Array.from(e.querySelectorAll(t));
}
function Ze(e) {
  if (!(e instanceof HTMLElement) || !e.isConnected || e.hidden || e.closest("[hidden]") || e.closest("[data-formie-page-hidden]") || e.closest('[aria-hidden="true"]'))
    return !1;
  const t = window.getComputedStyle(e);
  return t.display !== "none" && t.visibility !== "hidden";
}
function De(e, t) {
  const r = Le(e, t);
  return r.find((n) => Ze(n)) || r[0] || null;
}
function ao(e) {
  e.innerHTML = "";
  const t = document.createElement("div");
  return e.appendChild(t), t;
}
function Qe(e) {
  e?.querySelector(io)?.remove();
}
function so(e, t, r) {
  if (!e)
    return;
  Qe(e);
  const n = document.createElement("div");
  n.setAttribute("data-formie-captcha-error-container", ""), n.setAttribute("aria-live", "polite"), n.setAttribute("aria-atomic", "true"), V(n, r || e, "fieldErrors");
  const i = document.createElement("div");
  i.setAttribute("data-formie-captcha-error", ""), i.setAttribute("role", "alert"), V(i, r || e, "fieldError"), i.textContent = t, n.appendChild(i), e.appendChild(n);
}
function lo(e) {
  const t = e instanceof CustomEvent ? e.detail : null;
  return !t || typeof t != "object" ? null : t;
}
function uo(e, t) {
  if (!e?.captchas || typeof e.captchas != "object")
    return null;
  const r = e.captchas[t];
  return !r || typeof r != "object" ? null : r;
}
function co(e, t, r, n) {
  const i = /* @__PURE__ */ new Set(), o = () => {
    const d = Le(e, t), p = new Set(d.filter((y) => Ze(y)));
    d.forEach((y) => {
      p.has(y) && !i.has(y) && (i.add(y), r(y));
    }), Array.from(i).forEach((y) => {
      p.has(y) || (i.delete(y), n(y));
    });
  }, a = vr(o, 20), s = new MutationObserver(() => {
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
    ...oo.map((d) => se(e, d, () => {
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
    getVisible: () => Le(e, t).filter((d) => Ze(d))
  };
}
function fo(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function mo(e, t, {
  defaultPlaceholderSelector: r,
  defaultTokenFieldNames: n = [],
  defaultWaitForValueMs: i = ft
}) {
  const o = t || {}, a = Object.entries(o).reduce((u, [h, A]) => (no.has(h) || (u[h] = A), u), {}), s = n.map(String).filter(Boolean), l = Number(i), d = typeof o.placeholderSelector == "string" && o.placeholderSelector.trim() !== "" ? o.placeholderSelector.trim() : r, p = typeof o.errorMessage == "string" && o.errorMessage.trim() !== "" ? o.errorMessage.trim() : x("Captcha challenge must be completed."), y = typeof o.sessionKey == "string" && o.sessionKey.trim() !== "" ? o.sessionKey.trim() : null, c = typeof o.value == "string" ? o.value : null;
  return {
    handle: fo(e, o),
    ui: {
      placeholderSelector: d,
      errorMessage: p
    },
    transport: {
      tokenFieldNames: s,
      waitForValueMs: Number.isFinite(l) ? l : i,
      sessionKey: y,
      value: c
    },
    provider: a
  };
}
function po(e, t) {
  const r = e.form || e.root, n = t.ui.placeholderSelector, i = t.handle;
  return {
    form: e.form,
    root: e.root,
    placeholder: {
      query: () => Le(e.root, n),
      getPrimary: () => De(e.root, n),
      observe: (o, a) => co(e.root, n, o, a),
      createContainer: (o) => ao(o),
      clear: (o) => {
        o && (Qe(o), o.innerHTML = "");
      }
    },
    errors: {
      getDefaultMessage: () => t.ui.errorMessage,
      show: (o, a) => {
        so(a || De(e.root, n), o || t.ui.errorMessage, e.form || e.root);
      },
      clear: (o) => {
        Qe(o || De(e.root, n));
      }
    },
    tokens: {
      names: t.transport.tokenFieldNames,
      has: (o = t.transport.tokenFieldNames, a = r) => Ye(a, o),
      read: (o = t.transport.tokenFieldNames[0], a = r) => o ? Er(a, o) : "",
      write: (o, {
        names: a = t.transport.tokenFieldNames,
        root: s = r,
        container: l = e.form
      } = {}) => {
        a.forEach((d) => {
          Sr(s, d, {
            value: o,
            container: l
          });
        });
      },
      clear: (o = t.transport.tokenFieldNames, a = r) => {
        ro(a, o);
      },
      wait: (o = t.transport.waitForValueMs, a = t.transport.tokenFieldNames, s = r) => Ar(s, a, o)
    },
    refresh: {
      providerHandle: i,
      onTokensRefreshed: (o) => {
        const a = ["formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"].map((s) => se(e.root, s, (l) => {
          const d = lo(l), p = uo(d, i);
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
function wr({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = ft,
  setup: i
}) {
  return {
    id: e,
    kind: "captcha",
    match: () => !0,
    setup: async (o) => {
      const a = mo(e, o.options || {}, {
        defaultPlaceholderSelector: t,
        defaultTokenFieldNames: r,
        defaultWaitForValueMs: n
      });
      G.log("Setup module.", {
        moduleId: e,
        placeholderSelector: a.ui.placeholderSelector,
        tokenFieldNames: a.transport.tokenFieldNames
      });
      const s = po(o, a);
      return i({
        ...o,
        options: a,
        services: s
      });
    }
  };
}
function go({
  id: e,
  defaultPlaceholderSelector: t,
  defaultTokenFieldNames: r = [],
  defaultWaitForValueMs: n = ft
}) {
  return wr({
    id: e,
    defaultPlaceholderSelector: t,
    defaultTokenFieldNames: r,
    defaultWaitForValueMs: n,
    setup: async ({ services: i, options: o, root: a }) => {
      const s = [];
      let l = i.placeholder.getPrimary(), d = o.transport.sessionKey, p = o.transport.value || "";
      const y = (u) => {
        !u || !d || (u.innerHTML = "", Sr(u, d, {
          value: p,
          container: u
        }));
      }, c = i.placeholder.observe(
        (u) => {
          l = u, G.log("Passive placeholder visible.", {
            moduleId: e
          }), y(u);
        },
        (u) => {
          l === u && (l = i.placeholder.getPrimary()), u.innerHTML = "";
        }
      );
      return s.push(c.cleanup), y(l), s.push(i.refresh.onTokensRefreshed((u) => {
        d = typeof u.sessionKey == "string" && u.sessionKey.trim() !== "" ? u.sessionKey.trim() : d, p = typeof u.value == "string" ? u.value : "";
        const h = i.placeholder.getPrimary() || l;
        l = h, y(h);
      })), {
        destroy: () => {
          s.forEach((u) => {
            u();
          });
        },
        onBeforeStage: async (u) => {
          if (u.stage !== "screen" || u.action !== "submit")
            return;
          const h = d ? [d] : o.transport.tokenFieldNames;
          if (h.length === 0)
            return;
          if (!await Ar(a, h, o.transport.waitForValueMs)) {
            const f = i.errors.getDefaultMessage();
            i.errors.show(f, l), G.warn("Passive captcha missing token.", {
              moduleId: e,
              tokenFieldNames: h
            }), u.abort(f);
          }
        }
      };
    }
  });
}
function ho(e) {
  return wr({
    id: e.id,
    defaultPlaceholderSelector: e.defaultPlaceholderSelector,
    defaultTokenFieldNames: e.defaultTokenFieldNames,
    setup: async (t) => {
      const r = [], n = /* @__PURE__ */ new Map(), i = /* @__PURE__ */ new Map();
      let o = t.services.placeholder.getPrimary(), a = !1, s = null;
      const l = async () => (s || (G.log("Loading captcha provider API.", {
        moduleId: e.id
      }), s = e.load(t)), s), d = async (u) => {
        const h = n.get(u);
        if (t.services.errors.clear(u), !h) {
          u.innerHTML = "";
          return;
        }
        const A = await l();
        e.unmount && await e.unmount({
          api: A,
          widget: h,
          placeholder: u,
          services: t.services,
          options: t.options,
          provider: t.options.provider
        }), n.delete(u), u.innerHTML = "", t.services.tokens.clear(), G.log("Unmounted captcha placeholder widget.", {
          moduleId: e.id
        }), o === u && (o = t.services.placeholder.getPrimary());
      }, p = async (u) => {
        if (a || n.has(u) || i.has(u))
          return;
        const h = (async () => {
          const A = await l();
          if (a || n.has(u))
            return;
          const f = t.services.placeholder.createContainer(u), v = await e.mount({
            api: A,
            placeholder: u,
            container: f,
            services: t.services,
            options: t.options,
            provider: t.options.provider
          });
          n.set(u, v), o = u, G.log("Mounted captcha placeholder widget.", {
            moduleId: e.id
          });
        })().finally(() => {
          i.delete(u);
        });
        i.set(u, h), await h;
      }, y = t.services.placeholder.observe(
        (u) => {
          o = u, p(u);
        },
        (u) => {
          d(u);
        }
      );
      r.push(y.cleanup);
      const c = async (u) => {
        const A = y.getVisible();
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
              reason: u
            }), t.services.tokens.clear(), t.services.errors.clear(v);
          }
          y.reconcile();
          return;
        }
        for (const f of Array.from(n.keys()))
          await d(f);
        for (const f of A)
          await p(f);
        y.reconcile();
      };
      return r.push(t.services.events.onRoot("formie:submit:result", (u) => {
        const h = u instanceof CustomEvent ? u.detail : null;
        h?.stage !== "validate" && (h?.ok === !1 && h?.stage === "screen" || h?.ok !== !0 && c("submit-result"));
      })), t.form && r.push(t.services.events.onForm(Ue("reset"), () => {
        o = t.services.placeholder.getPrimary() || o, window.setTimeout(() => {
          c("reset-state");
        }, 0);
      })), {
        destroy: async () => {
          a = !0, r.forEach((u) => {
            u();
          });
          for (const u of Array.from(n.keys()))
            await d(u);
        },
        onBeforeStage: async (u) => {
          if (u.stage !== "screen" || u.action !== "submit")
            return;
          const h = y.getVisible();
          if (h.length === 0)
            return;
          let A = h.find((E) => E === o) || h[0];
          await p(A), A = o || A, t.services.errors.clear(A);
          const f = n.get(A);
          if (!f) {
            const E = t.services.errors.getDefaultMessage();
            t.services.errors.show(E, A), G.warn("Captcha widget unavailable at screen stage.", {
              moduleId: e.id
            }), u.abort(E);
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
            stageCtx: u
          });
        }
      };
    }
  });
}
const aa = ho, sa = go, $t = 2500, bo = {
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
function yo(e) {
  return e.replace("{field:", "").replace("{", "").replace("}", "").replace("]", "").split("[").join("][");
}
function vo(e) {
  return `fields[${yo(e)}]`;
}
function Eo(e, t) {
  const r = vo(t), n = Array.from(e.querySelectorAll(`[name="${r}"]`)), i = Array.from(e.querySelectorAll(`[name="${r}[]"]`));
  return (i.length ? i : n).filter((o) => o instanceof HTMLElement);
}
function Vt(e, t) {
  const r = Eo(e, t);
  for (const n of r) {
    const o = n.closest("[data-formie-field-handle]")?.querySelector("[data-formie-field-label]")?.childNodes[0]?.textContent?.trim();
    if (o)
      return o;
  }
  return "";
}
function xe(e) {
  let t = e.replace(/[^\d.,-]/g, "");
  const r = t.includes(","), n = t.includes(".");
  return r && n ? t = t.replace(/\./g, "").replace(/,/, ".") : r && !n ? t = t.replace(/,/, ".") : t = t.replace(/,/g, ""), parseFloat(t);
}
function So(e) {
  return e.replace(/^\{field:/, "").replace(/^\{/, "").replace(/\}$/, "").trim();
}
function de(e) {
  return So(e).replace(/\]/g, "").split("[").join(".").replace(/\.+/g, ".").replace(/^\./, "").replace(/\.$/, "");
}
function Tr(e) {
  const r = de(e).split(".").filter(Boolean);
  if (!r.length)
    return "";
  const [n, ...i] = r;
  return `fields[${n}]${i.map((o) => `[${o}]`).join("")}`;
}
function Ao(e) {
  const r = String(e || "").trim().match(/^fields\[([^\]]+)\](.*)$/);
  if (!r)
    return "";
  const n = r[1] || "", i = r[2] || "", o = Array.from(i.matchAll(/\[([^\]]+)\]/g)).map((a) => a[1] || "").filter(Boolean);
  return [n, ...o].join(".");
}
function wo(e) {
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
function Cr(e) {
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
  const n = (r[1] || "").trim().toLowerCase(), i = (r[2] || "").trim(), [o, a = ""] = i.split("|", 2), { source: s, transforms: l } = wo(o || "");
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
  const d = s.indexOf(":"), p = d === -1 ? s : s.slice(0, d), y = d === -1 ? "" : s.slice(d + 1), c = de(p);
  return {
    raw: t,
    target: "field",
    key: c,
    selector: y.trim(),
    defaultValue: a.trim(),
    transforms: l,
    isToken: !0,
    isValid: c !== ""
  };
}
function To(e) {
  return e instanceof HTMLInputElement || e instanceof HTMLTextAreaElement || e instanceof HTMLSelectElement;
}
function Co(e, t, r) {
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
function Mo(e) {
  const t = /* @__PURE__ */ new Map();
  return Array.from(e.querySelectorAll("[name]")).filter((n) => To(n)).forEach((n) => {
    const i = Ao(n.name);
    i && Co(t, i, n);
  }), t;
}
function Lo(e) {
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
function Mr(e, t) {
  return e.get(de(t)) || null;
}
function Io(e, t) {
  const r = Cr(e), n = r.key, i = Mr(t, n);
  if (!i)
    return {
      key: n,
      value: r.defaultValue,
      found: !1
    };
  const o = Lo(i.inputs);
  return {
    key: n,
    value: o === "" && r.defaultValue !== "" ? r.defaultValue : o,
    found: !0
  };
}
function la(e, t, r) {
  const n = Cr(e), i = n.key;
  if (!i)
    return {
      key: i,
      value: n.defaultValue,
      found: !1
    };
  const o = r ? Mr(r, i) : null, s = (o?.names?.length ? o.names : [Tr(i)]).flatMap((l) => {
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
function Lr(e, t) {
  const r = t.replace(/"/g, '\\"');
  return e.querySelector(`input[name$="[${r}]"]`) || e.querySelector(`input[name$="${r}"]`);
}
function Se(e, t) {
  const r = t.find((n) => {
    const i = Lr(e, n);
    return !i || String(i.value || "").trim() === "";
  });
  return {
    ok: !r,
    missingSuffix: r
  };
}
async function Ir(e, t, r) {
  const n = Se(e, t);
  if (n.ok)
    return n;
  const i = Date.now() + Math.max(r, 0);
  for (; Date.now() < i; ) {
    await mt(120);
    const o = Se(e, t);
    if (o.ok)
      return o;
  }
  return Se(e, t);
}
const Fo = /* @__PURE__ */ new Set([
  "handle",
  "requiredInputSuffixes",
  "waitForValueMs",
  "errorMessage"
]), Ot = "[data-payment-success]", Ht = "[data-payment-error]";
function Ro(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function ko(e, t, r) {
  const n = t || {}, i = Object.entries(n).reduce((l, [d, p]) => (Fo.has(d) || (l[d] = p), l), {}), o = Array.isArray(n.requiredInputSuffixes) ? n.requiredInputSuffixes.map(String).filter(Boolean) : r.defaultRequiredInputSuffixes || [], a = Number(n.waitForValueMs ?? r.defaultWaitForValueMs ?? $t), s = typeof n.errorMessage == "string" && n.errorMessage.trim() !== "" ? n.errorMessage.trim() : "Payment authorization is incomplete.";
  return {
    handle: Ro(e, n),
    transport: {
      requiredInputSuffixes: o,
      waitForValueMs: Number.isFinite(a) ? a : $t,
      errorMessage: s
    },
    provider: i
  };
}
function _t(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function qo(e, t) {
  const r = e.target, n = e.form, i = e.root, o = n || i, a = t.transport.requiredInputSuffixes, s = () => Mo(n || i), l = (b) => {
    const g = Io(b, s()).value;
    return Array.isArray(g) ? g[0] || "" : String(g || "");
  };
  return {
    root: i,
    form: n,
    field: r,
    updateInputs: (b, m) => {
      const g = Array.isArray(b) ? b : [b];
      for (const S of g) {
        const C = Lr(o, S) ?? r.querySelector(`input[name*="${S}"]`);
        C && (C.value = m);
      }
    },
    addError: (b) => {
      const m = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = m.querySelector(Ht);
      g && g.remove();
      const S = document.createElement("div");
      S.setAttribute("data-payment-error", ""), S.textContent = b, V(S, n || i, "fieldError"), m.appendChild(S);
    },
    removeError: () => {
      r.querySelector(Ht)?.remove();
    },
    addSuccess: (b) => {
      const m = r.querySelector("[data-formie-field-type] > div, [data-field-type] > div") || r, g = m.querySelector(Ot);
      g && g.remove();
      const S = document.createElement("div");
      S.setAttribute("data-payment-success", ""), S.textContent = b, V(S, n || i, "successMessage"), m.appendChild(S);
    },
    removeSuccess: () => {
      r.querySelector(Ot)?.remove();
    },
    hasToken: () => Se(o, a).ok,
    waitForToken: (b = t.transport.waitForValueMs) => Ir(o, a, b).then((m) => m.ok),
    getFieldValue: (b, m = "string") => {
      const g = l(b);
      return m === "float" || m === "int" || m === "number" ? xe(g) : g;
    },
    resolveAmount: (b) => {
      const m = n || i, S = String(b.type || "").toLowerCase() === "dynamic" && typeof b.variable == "string" && b.variable.trim() !== "", C = b.value ?? (S ? b.variable : b.fixed), T = String(C ?? "").trim(), F = typeof C == "number" ? C : xe(T);
      if (Number.isFinite(F) && F > 0)
        return { ok: !0, value: F };
      if (T !== "") {
        const k = l(T), w = xe(k);
        if (Number.isFinite(w) && w > 0)
          return { ok: !0, value: w };
        const q = Vt(m, T);
        if (!k)
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
    resolveCurrency: (b) => {
      const m = n || i, S = String(b.type || "").toLowerCase() === "dynamic" && typeof b.variable == "string" && b.variable.trim() !== "", C = b.value ?? (S ? b.variable : b.fixed ?? b.defaultCurrency ?? ""), T = String(C ?? "").trim(), F = T.toUpperCase();
      if (/^[A-Z]{3}$/.test(F) && !S)
        return { ok: !0, value: F };
      if (T !== "") {
        const k = String(l(T) || "").trim(), w = k.toUpperCase();
        if (/^[A-Z]{3}$/.test(w))
          return { ok: !0, value: w };
        const q = Vt(m, T);
        if (!k)
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
    watchFieldValueChanges: (b, m, g = 600) => {
      const S = n || i, C = b.map((q) => String(q || "").trim()).filter(Boolean);
      if (C.length === 0)
        return () => {
        };
      const T = s(), F = /* @__PURE__ */ new Set();
      C.forEach((q) => {
        const N = de(q), B = T.get(N);
        if (B?.names?.length) {
          B.names.forEach((fe) => {
            F.add(fe);
          });
          return;
        }
        const Y = Tr(N);
        Y && (F.add(Y), F.add(`${Y}[]`));
      });
      const k = vr(() => {
        m();
      }, g), w = (q) => {
        const B = q.target?.name || "";
        !B || !F.has(B) || k();
      };
      return S.addEventListener("input", w), S.addEventListener("change", w), () => {
        S.removeEventListener("input", w), S.removeEventListener("change", w);
      };
    },
    triggerSubmit: () => {
      n && n.setAttribute("data-formie-internal-resubmit", "true"), n && typeof n.requestSubmit == "function" ? n.requestSubmit() : n && n.submit();
    },
    releaseSubmitLoading: () => {
      n && (n.removeAttribute("data-formie-internal-resubmit"), Te(n));
    },
    getBillingData: (b) => {
      const m = {};
      if (!b || typeof b != "object")
        return { billing_details: m };
      if (b.billingName) {
        const g = l(b.billingName);
        g && (m.name = g);
      }
      if (b.billingEmail) {
        const g = l(b.billingEmail);
        g && (m.email = g);
      }
      if (b.billingAddress) {
        const g = b.billingAddress, S = {}, C = l(`${g}.address1`), T = l(`${g}.address2`), F = l(`${g}.address3`), k = l(`${g}.city`), w = l(`${g}.zip`), q = l(`${g}.state`), N = l(`${g}.country`);
        C && (S.line1 = C), T && (S.line2 = T), F && (S.line3 = F), k && (S.city = k), w && (S.postal_code = w), q && (S.state = q), N && (S.country = N), Object.keys(S).length && (m.address = S);
      }
      return { billing_details: m };
    },
    events: {
      onForm: (b, m) => n ? _t(n, b, m) : () => {
      },
      onRoot: (b, m) => _t(i, b, m)
    }
  };
}
const U = _("payments");
function Dt(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function Po(e) {
  const t = e.defaultRequiredInputSuffixes ?? bo[e.id] ?? [];
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
      const a = ko(
        e.id,
        r.options || {},
        {
          defaultRequiredInputSuffixes: t
        }
      ), s = qo(r, a), l = {
        ...r,
        options: a,
        services: s
      }, d = [];
      let p = null, y = null, c = null, u = null;
      const h = async () => (p || (U.log("Loading payment provider API.", { moduleId: e.id }), p = e.load(l)), p), A = async () => {
        if (!e.mount || y || !Dt(r.target))
          return;
        const E = await h();
        try {
          y = await e.mount({
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
        c = await e.setup({ ...l, root: E }), c.destroy && d.push(c.destroy);
      }
      e.mount && Dt(r.target) && await A(), ["formie:page:navigate:after", "formie:submit:result"].forEach((E) => {
        const b = () => {
          A();
        };
        r.root.addEventListener(E, b), d.push(() => {
          r.root.removeEventListener(E, b);
        });
      });
      const v = async () => {
        if (U.log("Destroying payment module.", {
          moduleId: e.id,
          handle: a.handle
        }), d.forEach((E) => E()), y && e.unmount) {
          const E = await h();
          await e.unmount({
            api: E,
            widget: y,
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
          if (c?.onBeforeStage) {
            await c.onBeforeStage(E);
            return;
          }
          if (E.stage !== "authorize" || E.action !== "submit" || r.target.closest("[data-formie-page]")?.hasAttribute("data-formie-page-hidden"))
            return;
          await A();
          const g = await h();
          if (e.onBeforeAuthorize) {
            u || (u = (async () => e.onBeforeAuthorize({
              api: g,
              widget: y,
              field: r.target,
              services: s,
              options: a,
              provider: a.provider,
              stageCtx: E
            }))().finally(() => {
              u = null;
            }));
            const T = await u;
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
          const S = r.form || r.root, C = await Ir(
            S,
            a.transport.requiredInputSuffixes,
            a.transport.waitForValueMs
          );
          C.ok || (U.warn("Required payment input(s) missing.", {
            moduleId: e.id,
            handle: a.handle,
            missingSuffix: C.missingSuffix
          }), E.abort(a.transport.errorMessage));
        },
        onAfterStage: async (E, b) => {
          E.stage !== "dispatch" || !e.onAfterSubmit || await e.onAfterSubmit({
            field: r.target,
            services: s,
            options: a,
            provider: a.provider,
            result: b
          });
        }
      };
    }
  };
}
const ua = Po, $o = "[data-formie-address-autocomplete-input]", xt = "[data-formie-address-location]", Vo = {
  autoComplete: "[data-formie-address-autocomplete-input]",
  address1: "[data-formie-address-line1-input]",
  address2: "[data-formie-address-line2-input]",
  address3: "[data-formie-address-line3-input]",
  city: "[data-formie-address-city-input]",
  state: "[data-formie-address-state-input]",
  zip: "[data-formie-address-zip-input]",
  country: "[data-formie-address-country-input]"
}, Oo = /* @__PURE__ */ new Set(["handle"]);
function Ho(e, t) {
  return (typeof t.handle == "string" && t.handle.trim() !== "" ? t.handle.trim() : "") || e;
}
function _o(e, t) {
  const r = t || {}, n = Object.entries(r).reduce((i, [o, a]) => (Oo.has(o) || (i[o] = a), i), {});
  return {
    handle: Ho(e, r),
    provider: n
  };
}
function Do(e, t, r) {
  return e.addEventListener(t, r), () => {
    e.removeEventListener(t, r);
  };
}
function xo(e) {
  const t = e.target, r = e.form, n = e.root, i = $o;
  return {
    root: n,
    field: t,
    form: r,
    input: {
      getAutocomplete: () => t.querySelector(i),
      setValue: (o, a, s) => {
        const l = Vo[o], d = t.querySelector(l);
        d && (d.value = a || s || "");
      }
    },
    location: {
      getButton: () => t.querySelector(xt),
      onUseLocation: (o) => {
        const a = t.querySelector(xt);
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
      onField: (o, a) => Do(t, o, a)
    }
  };
}
const te = _("address");
function Nt(e) {
  const t = e;
  return !t.closest("[data-formie-page-hidden]") && !t.closest("[hidden]");
}
function No(e) {
  return {
    id: e.id,
    kind: "address",
    match: (t) => !!t.target.querySelector("[data-formie-address-autocomplete-input]"),
    setup: async (t) => {
      const r = _o(e.id, t.options || {}), n = xo(t);
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
        if (s || !Nt(t.target))
          return;
        const u = await d();
        s = await e.mount({
          api: u,
          field: t.target,
          services: n,
          options: r,
          provider: r.provider
        }), te.log("Widget mounted.", {
          moduleId: e.id
        });
      };
      Nt(t.target) && await p(), ["formie:page:navigate:after", "formie:submit:result"].forEach((u) => {
        const h = () => {
          p();
        };
        t.root.addEventListener(u, h), o.push(() => {
          t.root.removeEventListener(u, h);
        });
      });
      const c = n.location.onUseLocation((u) => {
        e.onCurrentLocation && (async () => {
          if (await p(), !s)
            return;
          const h = await d();
          await e.onCurrentLocation?.(u, {
            api: h,
            widget: s,
            field: t.target,
            services: n,
            options: r,
            provider: r.provider
          });
        })();
      });
      return c && o.push(c), {
        destroy: async () => {
          if (te.log("Destroying module.", {
            moduleId: e.id
          }), o.forEach((u) => u()), s && e.unmount) {
            const u = await d();
            await e.unmount({
              api: u,
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
const ca = No;
export {
  Qo as $,
  Vo as A,
  Uo as B,
  ta as C,
  Ur as D,
  zr as E,
  Vr as F,
  Ki as G,
  jr as H,
  Br as I,
  ea as J,
  Xt as K,
  Pr as L,
  mr as M,
  Hr as N,
  Or as O,
  Xo as P,
  Ao as Q,
  zt as R,
  Zo as S,
  zo as T,
  Cr as U,
  la as V,
  $r as W,
  Go as X,
  Yo as Y,
  x as Z,
  Ne as _,
  aa as a,
  oa as b,
  na as c,
  ca as d,
  sa as e,
  ra as f,
  Bo as g,
  _ as h,
  Mo as i,
  Tr as j,
  Wo as k,
  Ue as l,
  Ie as m,
  de as n,
  vr as o,
  Jo as p,
  jo as q,
  Io as r,
  mt as s,
  ue as t,
  ua as u,
  Ko as v,
  ia as w,
  V as x,
  le as y,
  Rn as z
};
