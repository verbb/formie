import { r as K, a as O, d as x } from "./shared-B-6T-XE4.js";
import { e as q } from "./styles-C3aqgtek.js";
import { k as B, l as j, m as W, o as Y } from "./index-WW6SLpnK.js";
const $ = "@layer formie-theme{.formie-file-input{padding:var(--formie-space-1);line-height:var(--formie-line-height-base);cursor:pointer}.formie-file-input::file-selector-button,.formie-file-input::-webkit-file-upload-button{appearance:none;-webkit-appearance:none;margin-inline-end:var(--formie-space-2);padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);line-height:1.1;white-space:nowrap;cursor:pointer;transition:border-color .15s ease,background-color .15s ease,color .15s ease,box-shadow .15s ease}.formie-file-input:hover::file-selector-button,.formie-file-input:hover::-webkit-file-upload-button{border-color:color-mix(in srgb,var(--formie-color-border) 70%,var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:focus{outline:0}.formie-file-input:focus-visible::file-selector-button,.formie-file-input:focus-visible::-webkit-file-upload-button{border-color:var(--formie-color-focus-ring)}.formie-field-has-error .formie-file-input::file-selector-button,.formie-field-has-error .formie-file-input::-webkit-file-upload-button{border-color:var(--formie-color-danger)}.formie-file-summary{padding:var(--formie-file-summary-padding);gap:var(--formie-gap-file-summary);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-file-summary-container{margin:0;padding-left:var(--formie-list-indent)}}", _ = 'input[type="file"][data-formie-file-input]', F = j("uploaded"), L = W("reset"), J = "data-formie-file-upload-key", G = "data-formie-file-upload-hydrate-endpoint", I = "data-formie-file-upload-anchor", Q = "data-formie-file-upload-asset-id", X = [
  "fileLimit",
  "fileSizeMinLimit",
  "fileSizeMaxLimit"
], w = "file-upload", E = "file-upload", T = B("repeater", "init-row"), Z = "[data-formie-field-handle]";
q(E, [$]);
function h(e) {
  return !!e && typeof e == "object" && !Array.isArray(e);
}
function b(e) {
  const t = Number(e);
  return Number.isInteger(t) && t > 0 ? t : null;
}
function c(e) {
  return typeof e == "string" ? e.trim() : "";
}
function M(e) {
  return e.getAttribute("data-formie-field-handle")?.trim() || "";
}
function k(e) {
  return e.name.endsWith("[]") ? e.name.slice(0, -2) : e.name;
}
function D(e) {
  return `${k(e)}[]`;
}
function ee(e) {
  return e.getAttribute(J)?.trim() || e.getAttribute("data-formie-input-id")?.trim() || "";
}
function C(e) {
  return Array.from(e.querySelectorAll('input[type="hidden"]')).filter((t) => t instanceof HTMLInputElement);
}
function P(e, t) {
  const r = D(t);
  return C(e).filter((n) => n.name === r && n.value.trim() !== "");
}
function te(e, t) {
  const r = k(t), n = C(e).find((s) => s.hasAttribute(I) || s.name === r && s.value === "");
  if (n)
    return n.setAttribute(I, "true"), n.name = r, n.value = "", n;
  const i = document.createElement("input");
  return i.type = "hidden", i.name = r, i.value = "", i.setAttribute(I, "true"), t.insertAdjacentElement("afterend", i), i;
}
function N(e) {
  if (Array.isArray(e))
    return null;
  const t = b(e);
  if (t)
    return {
      assetId: t,
      filename: ""
    };
  if (!h(e))
    return null;
  const r = b(e.assetId ?? e.id ?? e.value), n = c(e.filename ?? e.title ?? e.label ?? e.name), i = c(e.url) || null;
  return !r && !n ? null : {
    assetId: r,
    filename: n,
    url: i
  };
}
function v(e) {
  if (!Array.isArray(e)) {
    const t = N(e);
    return t ? [t] : [];
  }
  return e.flatMap((t) => {
    if (Array.isArray(t))
      return v(t);
    const r = N(t);
    return r ? [r] : [];
  });
}
function S(e) {
  return e ? Array.from(e.querySelectorAll("[data-formie-file-summary-item]")).map((t) => ({
    assetId: null,
    filename: t.textContent?.trim() || ""
  })).filter((t) => t.filename !== "") : [];
}
function H(e, t) {
  return P(e, t).map((r) => ({
    assetId: b(r.value),
    filename: ""
  })).filter((r) => r.assetId !== null);
}
function U(e, t) {
  return e.length ? e.map((r, n) => ({
    assetId: r.assetId,
    filename: t[n]?.filename || ""
  })) : t;
}
function z(e) {
  return e.map((t) => t.trim().toLowerCase()).filter(Boolean);
}
function re(e, t) {
  const r = z(e.map((i) => i.filename)), n = z(t);
  return !r.length || !n.length ? !1 : r.every((i) => n.includes(i));
}
function ne(e) {
  K(e, w, (t) => {
    t.addValidator("fileLimit", ({ input: r }) => {
      const n = parseInt(r.getAttribute("data-formie-file-limit") || "", 10);
      return r.type !== "file" || !n || !("files" in r) || !r.files?.length ? !0 : r.files.length <= n;
    }, ({ input: r, t: n }) => n("Choose up to {files} files.", {
      files: r.getAttribute("data-formie-file-limit") || ""
    })), t.addValidator("fileSizeMinLimit", ({ input: r }) => {
      const i = parseFloat(r.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
      return r.type !== "file" || !i || !("files" in r) || !r.files?.length ? !0 : Array.from(r.files).every((s) => s.size >= i);
    }, ({ input: r, t: n }) => n("File must be larger than {filesize} MB.", {
      filesize: r.getAttribute("data-formie-size-min-limit") || ""
    })), t.addValidator("fileSizeMaxLimit", ({ input: r }) => {
      const i = parseFloat(r.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
      return r.type !== "file" || !i || !("files" in r) || !r.files?.length ? !0 : Array.from(r.files).every((s) => s.size <= i);
    }, ({ input: r, t: n }) => n("File must be smaller than {filesize} MB.", {
      filesize: r.getAttribute("data-formie-size-max-limit") || ""
    }));
  });
}
function ie(e) {
  O(e, w, X);
}
function V(e, t) {
  const r = e.querySelector("[data-formie-file-summary]");
  if (r)
    return r;
  const n = document.createElement("div");
  return n.className = "formie-file-summary", n.setAttribute("data-formie-file-summary", "true"), t.insertAdjacentElement("afterend", n), n;
}
function y(e) {
  const t = e.pendingFiles.length ? e.pendingFiles : e.uploadedAssets.map((s) => s.filename || (s.assetId ? `Asset #${s.assetId}` : "")).filter(Boolean), r = e.summaryRoot || e.field.querySelector("[data-formie-file-summary]");
  if (!t.length) {
    r && (r.replaceChildren(), r.hidden = !0), e.summaryRoot = r;
    return;
  }
  const n = r || V(e.field, e.input);
  if (!n)
    return;
  n.hidden = !1, e.summaryRoot = n;
  const i = document.createElement("ul");
  i.className = "formie-file-summary-container", i.setAttribute("data-formie-file-summary-container", "true"), t.forEach((s) => {
    const o = document.createElement("li");
    o.className = "formie-file-summary-item", o.setAttribute("data-formie-file-summary-item", "true"), o.textContent = s, i.appendChild(o);
  }), n.replaceChildren(i);
}
function R(e, t, r) {
  let i = te(e, t);
  const s = D(t);
  P(e, t).forEach((o) => {
    o.remove();
  }), r.forEach((o) => {
    if (!o.assetId)
      return;
    const l = document.createElement("input");
    l.type = "hidden", l.name = s, l.value = String(o.assetId), l.setAttribute(Q, "true"), i.insertAdjacentElement("afterend", l), i = l;
  }), t.value = "";
}
function oe(e) {
  if (!e)
    return "";
  const t = e.querySelector('input[name="handle"]');
  return t instanceof HTMLInputElement && t.value.trim() ? t.value.trim() : e.getAttribute("data-formie-handle")?.trim() || "";
}
function se(e, t, r) {
  const n = t.form, i = new FormData(), s = oe(n), o = M(e);
  s && i.append("handle", s), o && i.append("fieldHandle", o);
  const l = n?.querySelector('input[name="submissionUid"]');
  return l instanceof HTMLInputElement && l.value.trim() && i.append("submissionUid", l.value.trim()), r.forEach((a) => {
    i.append("assetIds[]", String(a));
  }), i;
}
async function ae(e, t, r) {
  const n = r.map((a) => a.assetId).filter((a) => a !== null);
  if (!n.length)
    return r;
  const i = t.getAttribute(G)?.trim() || "/actions/formie/file-upload/hydrate", s = await Y(i, {
    method: "POST",
    body: se(e, t, n)
  }), o = v(s.assets);
  if (!o.length)
    return r;
  const l = new Map(o.map((a) => [a.assetId, a]));
  return r.map((a) => {
    if (!a.assetId || !l.has(a.assetId))
      return a;
    const p = l.get(a.assetId);
    return {
      assetId: a.assetId,
      filename: p.filename || a.filename,
      url: p.url ?? a.url
    };
  });
}
function le(e, t) {
  const r = e.detail;
  if (!h(r))
    return [];
  const n = c(r.fieldHandle), i = c(r.inputKey), s = c(r.inputName ?? r.name), o = h(r.data) ? r.data : null, l = c(o?.fieldHandle), a = c(o?.inputKey), p = c(o?.inputName ?? o?.name), d = [];
  let m = !1, u = !1, g = !1;
  if (i !== "" && i === t.inputKey && (m = !0, d.push(r.assets, r.assetIds, r.data)), s !== "" && s === t.inputName && (u = !0, d.push(r.assets, r.assetIds, r.data)), n === t.fieldHandle && (g = !0, d.push(r.assets, r.assetIds, r.data)), o) {
    const f = [
      o.assets,
      o.assetIds,
      o.uploadedAssets,
      o.data
    ];
    a !== "" && a === t.inputKey && (m = !0, d.push(...f)), p !== "" && p === t.inputName && (u = !0, d.push(...f)), l === t.fieldHandle && (g = !0, d.push(...f));
  }
  [r.assets, r.assetIds, r.data].forEach((f) => {
    h(f) && (t.inputKey && t.inputKey in f && (m = !0, d.push(f[t.inputKey])), t.inputName in f && (u = !0, d.push(f[t.inputName])), t.fieldHandle in f && d.push(f[t.fieldHandle]));
  });
  for (const f of d) {
    const A = v(f);
    if (A.length && (m || u || g || re(A, t.pendingFiles)))
      return A;
  }
  return [];
}
function de(e) {
  const t = e.closest(Z);
  return t instanceof HTMLElement ? t : null;
}
function ue(e) {
  return Array.from(e.querySelectorAll(_)).filter((t) => t instanceof HTMLInputElement);
}
function fe(e, t, r) {
  const n = {
    field: e,
    input: t,
    summaryRoot: V(e, t),
    uploadedAssets: U(
      H(e, t),
      S(e.querySelector("[data-formie-file-summary]"))
    ),
    pendingFiles: [],
    hydrationToken: 0
  }, i = () => {
    n.pendingFiles = Array.from(t.files || []).map((d) => d.name), y(n);
  }, s = async (d) => {
    n.hydrationToken += 1;
    const m = n.hydrationToken;
    n.pendingFiles = [], n.uploadedAssets = d, R(e, t, d), y(n);
    try {
      const u = await ae(e, t, d);
      if (m !== n.hydrationToken)
        return;
      n.uploadedAssets = u, y(n), x(e, E, "uploaded-assets-sync", {
        fileUpload: e,
        assets: u
      });
    } catch (u) {
      console.error("[formie] Failed to hydrate uploaded file details.", u);
    }
  }, o = () => {
    n.hydrationToken += 1, n.pendingFiles = [], n.uploadedAssets = [], R(e, t, []), y(n);
  }, l = (d) => {
    const m = M(e);
    if (!m)
      return;
    const u = le(d, {
      fieldHandle: m,
      inputKey: ee(t),
      inputName: t.name,
      pendingFiles: n.pendingFiles
    });
    u.length && s(u);
  }, a = () => {
    o();
  };
  return t.addEventListener("change", i), r?.addEventListener(F, l), r?.addEventListener(L, a), (() => {
    const d = H(e, t), m = S(n.summaryRoot);
    if (n.uploadedAssets = U(d, m), n.uploadedAssets.some((u) => u.assetId && !u.filename)) {
      s(n.uploadedAssets);
      return;
    }
    y(n);
  })(), () => {
    t.removeEventListener("change", i), r?.removeEventListener(F, l), r?.removeEventListener(L, a);
  };
}
const ye = {
  id: E,
  kind: "field",
  match: (e) => !!e.target.querySelector(_),
  setup: async (e) => {
    const t = e.form, r = /* @__PURE__ */ new WeakSet(), n = [], i = (o) => {
      ue(o).forEach((l) => {
        if (r.has(l))
          return;
        const a = de(l);
        a && (r.add(l), n.push(fe(a, l, t)));
      });
    };
    ne(t), i(e.target);
    const s = (o) => {
      const l = o.detail;
      if (!h(l))
        return;
      const a = l.row;
      a instanceof HTMLElement && i(a);
    };
    return t?.addEventListener(T, s), await e.emit("formie:module:file-upload:init", {
      count: n.length
    }), {
      destroy: () => {
        t?.removeEventListener(T, s), n.forEach((o) => {
          o();
        }), ie(t), e.emit("formie:module:file-upload:destroy", {});
      }
    };
  }
};
export {
  ye as fileUploadModule
};
